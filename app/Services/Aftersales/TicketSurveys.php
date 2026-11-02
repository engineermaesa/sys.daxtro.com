<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\RefSurveyTemplate;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketCycle;
use App\Models\Aftersales\TicketSatisfaction;
use Illuminate\Support\Collection;

class TicketSurveys
{
    public static function variant(Ticket $ticket, ?TicketCycle $cycle = null): string
    {
        if (! TicketDocumentWorkflow::enabled($ticket)) {
            return 'site';
        }
        $cycle ??= $ticket->currentCycle;

        return $cycle?->recoming?->recoming_type === 'phone_call' ? 'phone_call' : 'site';
    }

    public static function pin(Ticket $ticket, TicketCycle $cycle): RefSurveyTemplate
    {
        if (! $cycle->survey_template_id) {
            $cycle->update(['survey_template_id' => RefSurveyTemplate::active(self::variant($ticket, $cycle))->id]);
            $cycle->unsetRelation('surveyTemplate');
        }

        return $cycle->surveyTemplate;
    }

    public static function current(Ticket $ticket, ?int $cycleId): ?TicketSatisfaction
    {
        $responses = $ticket->satisfactions;
        if (TicketDocumentWorkflow::enabled($ticket)) {
            $responses = $responses->where('cycle_id', $cycleId);
        }

        return $responses->sort(fn ($a, $b) => ($b->filled_at <=> $a->filled_at) ?: ($b->id <=> $a->id))->first();
    }

    public static function exclusion(Ticket $ticket, TicketSatisfaction $response): ?string
    {
        $v3 = TicketDocumentWorkflow::enabled($ticket);

        return match (true) {
            $v3 && (! $response->cycle_id || ! $response->filled_at) => 'Unmapped cycle or submission time',
            self::current($ticket, $response->cycle_id)?->id !== $response->id => 'Not current',
            ! $v3 && $response->source === TicketSatisfaction::SOURCE_ADMIN => 'Entered by admin',
            ! in_array($response->source, $v3 ? ['link', 'admin'] : ['link'], true) => 'Invalid source',
            $response->review_status === TicketSatisfaction::REVIEW_NEEDS_REVIEW => 'Needs review',
            $response->review_status === TicketSatisfaction::REVIEW_REJECTED => 'Rejected',
            ! in_array($response->review_status, ['normal', 'reviewed'], true) => 'Invalid review status',
            $v3 && $response->source === 'admin' && $response->review_status !== 'reviewed' => 'Admin review required',
            default => null,
        };
    }

    public static function values(TicketSatisfaction $response): Collection
    {
        return $response->ref_survey_template_id
            ? $response->answers->where('type', 'rating')->whereNotNull('value_number')->pluck('value_number')
            : collect(array_keys(TicketSatisfaction::ASPECTS))->map(fn ($key) => $response->{$key})->filter(fn ($value) => $value !== null)->values();
    }

    public static function average(Ticket $ticket): ?float
    {
        $valid = $ticket->satisfactions->filter(fn ($response) => self::exclusion($ticket, $response) === null);
        $values = $valid->flatMap(fn ($response) => self::values($response));

        return $values->isEmpty() ? null : round($values->avg(), 1);
    }

    public static function constrainQuestionScores($query): void
    {
        $query->where(function ($q) {
            $q->where(function ($legacy) {
                $legacy->where(fn ($w) => $w->whereNull('t.workflow_version')->orWhere('t.workflow_version', '<>', 'v3'))
                    ->whereNull('s.admin_input_reason');
            })->orWhere(function ($v3) {
                $v3->where('t.workflow_version', 'v3')->whereNotNull('s.cycle_id')->whereNotNull('s.filled_at')
                    ->where(fn ($w) => $w->where(fn ($link) => $link->where('s.source', 'link')->whereIn('s.review_status', ['normal', 'reviewed']))
                        ->orWhere(fn ($admin) => $admin->where('s.source', 'admin')->where('s.review_status', 'reviewed')))
                    ->whereRaw('s.id = (SELECT current_s.id FROM ticket_satisfactions current_s WHERE current_s.ticket_id = s.ticket_id AND current_s.cycle_id = s.cycle_id AND current_s.deleted_at IS NULL ORDER BY current_s.filled_at DESC, current_s.id DESC LIMIT 1)');
            });
        });
    }
}
