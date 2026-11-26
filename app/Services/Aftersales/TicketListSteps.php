<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketLog;
use App\Models\Aftersales\TicketRecoming;
use App\Models\Aftersales\TicketVisit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class TicketListSteps
{
    public static function apply(Builder $query, ?array $steps, ?string $visitDate): void
    {
        $latestLog = TicketLog::query()->select('id')->whereColumn('ticket_id', 'tickets.id')->latest('id')->limit(1);
        $latestVisit = TicketVisit::query()->select('id')->whereColumn('ticket_id', 'tickets.id')
            ->latest('scheduled_at')->latest('id')->limit(1);
        $latestRecoming = TicketRecoming::query()->select('id')->whereColumn('ticket_id', 'tickets.id')->latest('id')->limit(1);

        $query->select('tickets.*')
            ->leftJoin('ticket_logs as list_log', fn ($join) => $join->where('list_log.id', '=', $latestLog))
            ->leftJoin('ticket_visits as list_visit', fn ($join) => $join->where('list_visit.id', '=', $latestVisit))
            ->leftJoin('ticket_recomings as list_recoming', fn ($join) => $join->where('list_recoming.id', '=', $latestRecoming));

        if ($visitDate !== null) {
            $day = Carbon::createFromFormat('!Y-m-d', $visitDate, 'Asia/Jakarta');
            $query->where('list_visit.scheduled_at', '>=', $day->toDateTimeString())
                ->where('list_visit.scheduled_at', '<', $day->addDay()->toDateTimeString());
        }

        $advanced = array_slice(TicketLog::STEPS, 2);
        $advancedBindings = implode(', ', array_fill(0, count($advanced), '?'));
        $step = "CASE
            WHEN list_log.step IN ($advancedBindings) THEN list_log.step
            WHEN tickets.assigned_technician_id IS NOT NULL AND list_visit.scheduled_at <= ?
                AND NOT (COALESCE(tickets.workflow_version, '') = ? AND COALESCE(list_recoming.recoming_type, '') = ?) THEN ?
            WHEN tickets.assigned_technician_id IS NOT NULL OR list_log.step = ? THEN ?
            ELSE ? END";
        $bindings = [
            ...$advanced, Carbon::now('Asia/Jakarta')->endOfDay()->toDateTimeString(),
            Ticket::WORKFLOW_V3, TicketRecoming::TYPE_PHONE_CALL, TicketLog::STEP_ON_SITE,
            TicketLog::STEP_ASSIGNED, TicketLog::STEP_ASSIGNED, TicketLog::STEP_PUBLISHED,
        ];

        if ($steps === []) {
            $query->whereRaw('0 = 1');
        } elseif ($steps !== null) {
            $placeholders = implode(', ', array_fill(0, count($steps), '?'));
            $query->whereRaw("($step) IN ($placeholders)", [...$bindings, ...$steps]);
        }

        $first = TicketLog::STEP_HAPPY_CALL_COMPLETED;
        $last = TicketLog::STEP_CLOSED;
        $order = [$first, ...array_reverse(array_diff(TicketLog::STEPS, [$first, $last])), $last];
        $ranks = [];
        foreach ($order as $rank => $value) {
            $ranks[] = 'WHEN ? THEN '.$rank;
        }

        $activity = ['tickets.created_at', 'tickets.updated_at', 'list_log.created_at', 'list_visit.updated_at', 'list_recoming.updated_at'];
        $latest = $query->getModel()->getConnection()->getDriverName() === 'sqlite' ? 'MAX' : 'GREATEST';
        $timestamps = implode(', ', array_map(fn ($column) => "COALESCE($column, '1000-01-01 00:00:00')", $activity));
        $query->orderByRaw("CASE ($step) ".implode(' ', $ranks).' END', [...$bindings, ...$order])
            ->orderByRaw("$latest($timestamps) DESC")
            ->orderByDesc('tickets.id');
    }
}
