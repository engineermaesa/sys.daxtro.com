<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketCycle;
use App\Models\Aftersales\TicketSatisfaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketSla
{
    public const FIELDS = ['sla_mode', 'sla_value', 'sla_unit', 'sla_due_at'];

    public static function setup(Request $request, Carbon $scheduledAt): array
    {
        $duration = $request->input('sla_mode') === 'duration';
        $data = $request->validate([
            'sla_mode' => 'required|in:duration,deadline',
            'sla_value' => [$duration ? 'required' : 'prohibited', 'nullable', 'integer', 'min:1', 'max:2147483647'],
            'sla_unit' => ($duration ? 'required' : 'prohibited').'|nullable|in:hour,day',
            'sla_due_at' => ($duration ? 'prohibited' : 'required').'|nullable|date',
        ]);
        $due = $duration ? self::durationDue($scheduledAt, (int) $data['sla_value'], $data['sla_unit'])
            : Carbon::parse($data['sla_due_at'], 'Asia/Jakarta')->timezone('Asia/Jakarta');
        if ($due->lt($scheduledAt)) {
            throw ValidationException::withMessages(['sla_due_at' => 'The SLA deadline must not precede the Recoming schedule.']);
        }
        self::validateTimestamp($due);

        return ['sla_mode' => $data['sla_mode'], 'sla_value' => $duration ? (int) $data['sla_value'] : null,
            'sla_unit' => $duration ? $data['sla_unit'] : null, 'sla_due_at' => $due, 'sla_started_at' => $scheduledAt];
    }

    private static function durationDue(Carbon $start, int $value, string $unit): Carbon
    {
        $due = $unit === 'hour' ? $start->copy()->addHours($value) : $start->copy()->addDays($value);
        self::validateTimestamp($due);

        return $due;
    }

    private static function validateTimestamp(Carbon $date): void
    {
        if ($date->format('Y-m-d H:i:s') > '2038-01-19 03:14:07' || $date->format('Y-m-d H:i:s') < '1970-01-01 00:00:01') {
            throw ValidationException::withMessages(['sla_due_at' => 'The SLA deadline exceeds the supported database timestamp range.']);
        }
    }

    public static function apply(Ticket $ticket, TicketCycle $cycle, array $setup): void
    {
        $cycle->update($setup + ['sla_stopped_at' => null, 'sla_satisfaction_id' => null]);
        $ticket->update(['sla_stopped_at' => null]);
    }

    public static function syncInitial(Ticket $ticket, string $mode): void
    {
        if (! TicketDocumentWorkflow::enabled($ticket)) {
            return;
        }
        $cycle = $ticket->currentCycle()->lockForUpdate()->firstOrFail();
        if ($cycle->cycle_no !== 1) {
            return;
        }
        $cycle->update(['sla_mode' => $mode, 'sla_started_at' => $ticket->created_at ?? $cycle->started_at,
            'sla_value' => $ticket->sla_value, 'sla_unit' => $ticket->sla_unit, 'sla_due_at' => $ticket->sla_due_at]);
        $ticket->unsetRelation('currentCycle')->unsetRelation('cycles');
    }

    public static function reschedule(Request $request, Ticket $ticket, TicketCycle $cycle, Carbon $date): void
    {
        if (! $cycle->sla_mode && ! $cycle->sla_due_at) {
            self::apply($ticket, $cycle, self::setup($request, $date));

            return;
        }
        $request->validate(array_fill_keys(self::FIELDS, 'prohibited'));
        $update = ['sla_started_at' => $date];
        if ($cycle->sla_mode === 'duration') {
            $update['sla_due_at'] = self::durationDue($date, $cycle->sla_value, $cycle->sla_unit);
        }
        $cycle->update($update);
    }

    public static function responseChanged(TicketSatisfaction $response): void
    {
        if ($response->cycle_id === null) {
            return;
        }
        self::recompute((int) $response->ticket_id, (int) $response->cycle_id);
    }

    public static function recompute(int $ticketId, int $cycleId): void
    {
        DB::transaction(function () use ($ticketId, $cycleId) {
            $ticket = Ticket::withTrashed()->whereKey($ticketId)->lockForUpdate()->firstOrFail();
            if (! TicketDocumentWorkflow::enabled($ticket)) {
                return;
            }
            $cycle = $ticket->cycles()->whereKey($cycleId)->lockForUpdate()->firstOrFail();
            $latest = $ticket->satisfactions()->where('cycle_id', $cycleId)->whereNotNull('filled_at')
                ->whereIn('review_status', [TicketSatisfaction::REVIEW_NORMAL, TicketSatisfaction::REVIEW_NEEDS_REVIEW, TicketSatisfaction::REVIEW_REVIEWED])
                ->when($cycle->ended_at, fn ($q) => $q->where('filled_at', '<=', $cycle->ended_at))
                ->orderByDesc('filled_at')->orderByDesc('id')->first();
            $cycle->update(['sla_stopped_at' => $latest?->filled_at, 'sla_satisfaction_id' => $latest?->id]);
            if ($ticket->currentCycle()->value('id') === $cycle->id) {
                $ticket->update(['sla_stopped_at' => $latest?->filled_at]);
            }
        });
    }

    public static function guardSurveyCycle(Ticket $ticket, ?int $cycleId): void
    {
        if (TicketDocumentWorkflow::enabled($ticket) && $ticket->currentCycle?->id !== $cycleId) {
            TicketDocumentWorkflow::error(409, 'survey_cycle_not_current', 'This survey belongs to a previous ticket cycle.');
        }
    }

    public static function summary(Ticket $ticket): ?array
    {
        if (! TicketDocumentWorkflow::enabled($ticket)) {
            return null;
        }
        $cycle = $ticket->currentCycle;
        if (! $cycle) {
            return null;
        }
        $start = $cycle->sla_started_at ?? ($cycle->cycle_no === 1 ? $cycle->started_at : null);
        $now = now('Asia/Jakarta');
        $end = $cycle->sla_stopped_at ?? $cycle->ended_at ?? $now;
        $configured = $start && $cycle->sla_due_at;
        $state = ! $configured ? 'not_configured' : ($cycle->sla_stopped_at ? 'stopped' : ($cycle->ended_at ? 'closed' : 'running'));

        return ['cycle_id' => $cycle->id, 'cycle_no' => $cycle->cycle_no,
            'mode' => $cycle->sla_mode ?? ($cycle->cycle_no === 1 && $cycle->sla_due_at ? 'deadline' : null),
            'state' => $state, 'label' => $state === 'running' ? ($cycle->cycle_no > 1 ? 'Cycle '.$cycle->cycle_no : 'Running') : ucfirst(str_replace('_', ' ', $state)),
            'started_at' => $start, 'due_at' => $cycle->sla_due_at, 'stopped_at' => $cycle->sla_stopped_at, 'closed_at' => $cycle->ended_at,
            'elapsed_seconds' => $start ? max(0, (int) $start->diffInSeconds($end, false)) : null,
            'is_overdue' => $configured ? $end->gt($cycle->sla_due_at) : null,
            'result' => $configured && $cycle->sla_stopped_at ? ($cycle->sla_stopped_at->lte($cycle->sla_due_at) ? 'On Time' : 'Overtime') : null];
    }
}
