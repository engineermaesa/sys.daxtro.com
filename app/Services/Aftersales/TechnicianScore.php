<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketLog;
use App\Models\Aftersales\TicketRecoming;
use App\Models\Aftersales\TicketSatisfaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TechnicianScore
{
    public const PERIODS = ['all' => null, '30' => 30, '90' => 90, '365' => 365];

    private const COUNTED_REASONS = ['further_work_needed', 'other'];

    private const SURVEY_STEPS = [TicketLog::STEP_DOCUMENTATION_PUBLISHED, TicketLog::STEP_SATISFACTION_SUBMITTED, TicketLog::STEP_HAPPY_CALL_COMPLETED];

    public static function since(?string $period): ?Carbon
    {
        $days = self::PERIODS[$period ?? 'all'] ?? null;

        return $days ? Carbon::now('Asia/Jakarta')->subDays($days)->startOfDay() : null;
    }

    public static function summaries(array $userIds, ?Carbon $since): array
    {
        $tickets = self::tickets($userIds)->groupBy('assigned_technician_id');

        return collect($userIds)->mapWithKeys(fn ($id) => [$id => self::summary($tickets->get($id, collect()), $since)])->all();
    }

    public static function overallRating(int $userId): array
    {
        $tickets = Ticket::where('assigned_technician_id', $userId)->with('satisfactions.answers')->get();
        $rating = self::rating($tickets, null);

        return ['overall_rating' => $rating['rating'], 'rating_scale_max' => $rating['rating_scale_max']];
    }

    public static function detail(int $userId, ?Carbon $since): array
    {
        $tickets = self::tickets([$userId]);
        $responses = self::responses($tickets, $since);
        $counted = $responses->where('counted', true);

        $aspects = $counted->flatMap(fn ($r) => $r['model']->ratingItems())->groupBy('key')
            ->map(fn ($items) => ['key' => $items[0]['key'], 'label' => $items[0]['label'], 'average' => round($items->avg('score'), 1)])
            ->values();

        $reasonTickets = [];
        foreach ($tickets as $ticket) {
            foreach ($ticket->recomings as $r) {
                $key = self::fromSurvey($r) ? 'survey' : $r->reason;
                $reasonTickets[$key][$ticket->id] = true;
            }
        }
        $reasons = collect([
            'further_work_needed' => ['Further work needed', true],
            'other' => ['Other', true],
            'survey' => ['Survey (Machine: No)', true],
            'sparepart_unavailable' => ['Part unavailable', false],
            'site_access' => ['Site access or condition', false],
        ])->map(fn ($v, $key) => ['reason' => $key, 'label' => $v[0], 'tickets' => count($reasonTickets[$key] ?? []), 'counted' => $v[1]])->values();

        return self::summary($tickets, $since) + [
            'aspects' => $aspects,
            'recoming_reasons' => $reasons,
            'responses' => $responses->map(fn ($r) => [
                'id' => $r['model']->id,
                'ticket_code' => $r['ticket_code'],
                'submitted_at' => $r['model']->filled_at,
                'score' => $r['model']->average_score,
                'review_status' => $r['model']->review_status,
                'counted' => $r['counted'],
                'not_counted_reason' => $r['reason'],
            ])->values(),
        ];
    }

    private static function summary(Collection $tickets, ?Carbon $since): array
    {
        $c = 0;
        $r = 0;

        foreach ($tickets as $ticket) {
            $recomings = $ticket->recomings;
            $countedRecomings = $recomings->filter(fn ($x) => in_array($x->reason, self::COUNTED_REASONS, true) || self::fromSurvey($x));
            $closedAt = $ticket->progress === TicketLog::STEP_CLOSED
                ? $ticket->logs->where('step', TicketLog::STEP_CLOSED)->sortByDesc('id')->first()?->created_at
                : null;

            if ($countedRecomings->isNotEmpty()) {
                $at = $closedAt ?? $countedRecomings->max('created_at');
                $r += (! $since || $at >= $since) ? 1 : 0;
            } elseif ($recomings->isEmpty() && $closedAt) {
                $c += (! $since || $closedAt >= $since) ? 1 : 0;
            }
        }

        return self::rating($tickets, $since) + [
            'completion' => $c + $r > 0 ? (int) round($c / ($c + $r) * 100) : null,
            'completion_closed' => $c,
            'completion_total' => $c + $r,
        ];
    }

    private static function rating(Collection $tickets, ?Carbon $since): array
    {
        $counted = self::responses($tickets, $since)->where('counted', true);
        $values = $counted->flatMap(fn ($r) => $r['v3'] ? TicketSurveys::values($r['model']) : [$r['model']->average_score]);

        return [
            'rating_scale_max' => 3,
            'rating' => $values->isEmpty() ? null : round($values->avg(), 1),
            'rating_count' => $counted->count(),
        ];
    }

    private static function responses(Collection $tickets, ?Carbon $since): Collection
    {
        return $tickets->flatMap(function (Ticket $ticket) use ($since) {
            return $ticket->satisfactions
                ->filter(fn ($s) => ! $since || ($s->filled_at && $s->filled_at >= $since))
                ->map(function (TicketSatisfaction $s) use ($ticket) {
                    $reason = TicketSurveys::exclusion($ticket, $s);

                    return ['model' => $s, 'v3' => TicketDocumentWorkflow::enabled($ticket), 'ticket_code' => $ticket->ticket_code, 'counted' => $reason === null && $s->average_score !== null, 'reason' => $reason];
                });
        })->sortByDesc(fn ($r) => $r['model']->filled_at)->values();
    }

    private static function tickets(array $userIds): Collection
    {
        return Ticket::whereIn('assigned_technician_id', $userIds)
            ->with(['logs', 'visits', 'recomings', 'satisfactions.template', 'satisfactions.answers'])
            ->get();
    }

    private static function fromSurvey(TicketRecoming $recoming): bool
    {
        return in_array($recoming->from_step, self::SURVEY_STEPS, true);
    }
}
