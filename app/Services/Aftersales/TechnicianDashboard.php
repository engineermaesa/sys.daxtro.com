<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\TicketSatisfaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class TechnicianDashboard
{
    public const PERIODS = ['this_month', 'last_month', 'this_year'];

    private const STATUSES = ['assigned' => 'Assigned', 'on_site' => 'On Site', 'repair' => 'Repair', 'waiting_sparepart' => 'Waiting'];

    private function live(string $table): Builder
    {
        return SupervisorDashboard::live($table);
    }

    public function tickets(int $userId): Builder
    {
        $q = $this->live('tickets')
            ->leftJoin('service_customers as customer', 'customer.id', '=', 'tickets.customer_id')
            ->leftJoin('service_customer_products as machine', 'machine.id', '=', 'tickets.customer_product_id')
            ->leftJoin('ref_products as product', 'product.id', '=', 'machine.product_id')
            ->leftJoin('ref_product_types as category', 'category.id', '=', 'machine.ref_product_type_id')
            ->leftJoin('ticket_logs as log', 'log.id', '=', DB::raw('(SELECT MAX(l.id) FROM ticket_logs l WHERE l.ticket_id = tickets.id AND l.deleted_at IS NULL AND l.is_deleted = 0)'))
            ->leftJoin('ticket_cycles as cycle', 'cycle.id', '=', DB::raw('(SELECT c.id FROM ticket_cycles c WHERE c.ticket_id = tickets.id ORDER BY c.cycle_no DESC LIMIT 1)'))
            ->leftJoin('ticket_visits as visit', 'visit.id', '=', DB::raw('(SELECT v.id FROM ticket_visits v WHERE v.ticket_id = tickets.id AND v.deleted_at IS NULL AND v.is_deleted = 0 ORDER BY v.scheduled_at DESC, v.id DESC LIMIT 1)'))
            ->leftJoin('ticket_recomings as recoming', 'recoming.id', '=', DB::raw('(SELECT MAX(r.id) FROM ticket_recomings r WHERE r.ticket_id = tickets.id AND r.deleted_at IS NULL AND r.is_deleted = 0)'))
            ->where('tickets.assigned_technician_id', $userId)
            ->select('tickets.id', 'tickets.ticket_code', 'tickets.priority', 'tickets.workflow_version', 'tickets.created_at', 'tickets.sla_due_at', 'tickets.customer_id', 'tickets.customer_product_id')
            ->selectRaw('CASE WHEN customer.deleted_at IS NULL AND COALESCE(customer.is_deleted, 0) = 0 THEN customer.name ELSE NULL END as customer_name')
            ->selectRaw("CASE WHEN machine.deleted_at IS NULL AND COALESCE(machine.is_deleted, 0) = 0 THEN COALESCE(NULLIF(machine.machine_name, ''), product.name) ELSE NULL END as machine_name")
            ->selectRaw('category.name as machine_category, cycle.id as cycle_id, cycle.documentation_status, cycle.service_form_submitted_at')
            ->selectRaw("CASE WHEN log.step = 'closed' THEN log.created_at ELSE NULL END as closed_at")
            ->selectRaw("CASE WHEN log.step IN ('on_site', 'repair', 'waiting_sparepart', 'documentation_published', 'satisfaction_submitted', 'happy_call_completed', 'closed', 'recoming') THEN log.step WHEN visit.scheduled_at <= ? AND NOT (tickets.workflow_version = 'v3' AND COALESCE(recoming.recoming_type, '') = 'phone_call') THEN 'on_site' ELSE 'assigned' END as progress", [CarbonImmutable::now('Asia/Jakarta')->endOfDay()->toDateTimeString()])
            ->selectRaw('visit.id as visit_id, visit.cycle_id as visit_cycle_id, visit.scheduled_at, visit.actual_start, visit.actual_end')
            ->selectRaw('recoming.id as recoming_id, recoming.recoming_type, recoming.cycle_id as recoming_cycle_id, CASE WHEN recoming.recoming_type = \'phone_call\' THEN recoming.planned_call_at ELSE recoming.planned_visit_at END as recoming_at')
            ->selectRaw("CASE WHEN tickets.workflow_version = 'v3' THEN CASE WHEN cycle.sla_stopped_at IS NOT NULL AND cycle.sla_due_at IS NOT NULL AND (cycle.sla_started_at IS NOT NULL OR (cycle.cycle_no = 1 AND cycle.started_at IS NOT NULL)) THEN CASE WHEN cycle.sla_stopped_at <= cycle.sla_due_at THEN 'on_time' ELSE 'overtime' END ELSE NULL END WHEN log.step = 'closed' AND tickets.sla_due_at IS NOT NULL THEN CASE WHEN log.created_at <= tickets.sla_due_at THEN 'on_time' ELSE 'overtime' END ELSE NULL END as sla_result");

        return DB::query()->fromSub($q, 'dashboard_ticket');
    }

    private function closed(int $userId, array $filters): Builder
    {
        [$start, $end] = SupervisorDashboard::window($filters);

        return $this->tickets($userId)->whereBetween('closed_at', [$start->toDateTimeString(), $end->toDateTimeString()]);
    }

    public function documents(int $userId): Builder
    {
        $queries = [];
        foreach (['agreement' => 'ticket_agreements', 'service_form' => 'ticket_service_forms'] as $type => $table) {
            $queries[] = $this->tickets($userId)->join($table.' as draft', 'draft.ticket_id', '=', 'dashboard_ticket.id')
                ->whereNull('draft.deleted_at')->where('draft.status', 'draft')->where('progress', '<>', 'closed')
                ->select('dashboard_ticket.*')->selectRaw('draft.id as document_id, ? as document_type', [$type]);
        }

        return DB::query()->fromSub($queries[0]->unionAll($queries[1]), 'draft_document');
    }

    public function recomingItem(object $row): array
    {
        $at = CarbonImmutable::parse($row->recoming_at, 'Asia/Jakarta');

        return $this->ticketItem($row) + ['recoming_id' => (int) $row->recoming_id,
            'type' => $row->recoming_type ?? 'visit_site', 'scheduled_at' => $at->toIso8601String(),
            'can_reschedule' => $at->greaterThan(CarbonImmutable::now('Asia/Jakarta')->endOfDay())];
    }

    public function summary(int $userId, array $filters): array
    {
        [$start, $end] = SupervisorDashboard::window($filters);
        $counts = $this->tickets($userId)->where('progress', '<>', 'closed')->selectRaw('progress, COUNT(*) as total')->groupBy('progress')->pluck('total', 'progress');
        $statuses = [];
        foreach (self::STATUSES as $key => $label) {
            $statuses[] = ['key' => $key, 'label' => $label, 'count' => (int) ($counts[$key] ?? 0)];
        }
        $closed = $this->closed($userId, $filters)->get(['id', 'closed_at', 'sla_result']);
        $weeks = [];
        for ($cursor = $start->startOfWeek(); $cursor <= $end; $cursor = $cursor->addWeek()) {
            $from = $cursor->max($start);
            $to = $cursor->endOfWeek()->min($end);
            $rows = $closed->filter(fn ($r) => $r->closed_at >= $from->toDateTimeString() && $r->closed_at <= $to->toDateTimeString());
            $weeks[] = ['key' => $from->toDateString(), 'label' => 'W'.(count($weeks) + 1), 'start' => $from->toDateString(), 'end' => $to->toDateString(),
                'total' => $rows->count(), 'on_time' => $rows->where('sla_result', 'on_time')->count(), 'overtime' => $rows->where('sla_result', 'overtime')->count(), 'unknown' => $rows->whereNull('sla_result')->count()];
        }
        $months = [];
        for ($month = $start->startOfMonth(); $month <= $end; $month = $month->addMonth()) {
            $rows = $closed->filter(fn ($r) => substr($r->closed_at, 0, 7) === $month->format('Y-m'));
            $months[] = ['key' => $month->format('Y-m'), 'label' => $month->format('M Y'), 'value' => self::percent($rows->where('sla_result', 'on_time')->count(), $rows->count())];
        }
        $todo = [];
        foreach (['share_survey' => 'Share survey', 'documents' => 'Documents', 'recoming' => 'Recoming'] as $key => $label) {
            $todo[] = ['key' => $key, 'label' => $label, 'count' => $this->drilldown($userId, ['metric' => $key])->count()];
        }
        $today = $this->tasks($userId)->paginate(10, ['*'], 'page', 1);

        return [
            'period' => ['key' => $filters['period'] ?? 'this_month', 'start' => $start->toDateString(), 'end' => $end->toDateString()],
            'timezone' => 'Asia/Jakarta', 'generated_at' => CarbonImmutable::now('Asia/Jakarta')->toIso8601String(),
            'rating' => ['score' => $this->rating($userId), 'max' => 3],
            'tickets' => ['total_active' => array_sum(array_column($statuses, 'count')), 'total_open' => (int) $counts->sum(), 'statuses' => $statuses, 'other_active' => (int) $counts->except(array_keys(self::STATUSES))->sum()],
            'sla' => ['value' => self::percent($closed->where('sla_result', 'on_time')->count(), $closed->count()), 'target' => (float) config('aftersales.dashboard_sla_target', 96),
                'on_time' => $closed->where('sla_result', 'on_time')->count(), 'overtime' => $closed->where('sla_result', 'overtime')->count(), 'unknown' => $closed->whereNull('sla_result')->count(), 'total_closed' => $closed->count(), 'months' => $months],
            'today' => ['date' => CarbonImmutable::now('Asia/Jakarta')->toDateString(), 'total' => $today->total(), 'has_more' => $today->hasMorePages(), 'items' => $today->getCollection()->map(fn ($row) => $this->taskItem($row))->all()],
            'closed' => ['total' => $closed->count(), 'weeks' => $weeks],
            'todo' => ['total' => array_sum(array_column($todo, 'count')), 'items' => $todo],
        ];
    }

    public function tasks(int $userId): Builder
    {
        $today = CarbonImmutable::now('Asia/Jakarta');
        $q = $this->tickets($userId);
        $current = "(workflow_version <> 'v3' OR workflow_version IS NULL OR visit_cycle_id = cycle_id)";
        $phone = "(COALESCE(workflow_version, '') = 'v3' AND COALESCE(recoming_type, '') = 'phone_call' AND COALESCE(recoming_cycle_id, 0) = COALESCE(cycle_id, -1))";

        return $q->where(function ($q) use ($today, $current, $phone) {
            $q->where(fn ($q) => $q->whereRaw($current)->whereRaw("NOT $phone")
                ->whereBetween('scheduled_at', [$today->startOfDay()->toDateTimeString(), $today->endOfDay()->toDateTimeString()]))
                ->orWhere(fn ($q) => $q->whereRaw($phone)->whereBetween('recoming_at', [$today->startOfDay()->toDateTimeString(), $today->endOfDay()->toDateTimeString()]));
        })->select('dashboard_ticket.*')->selectRaw("CASE WHEN $phone THEN recoming_at ELSE scheduled_at END as task_at, CASE WHEN $phone THEN 1 ELSE 0 END as is_phone_task")
            ->orderBy('task_at')->orderBy('id');
    }

    public function taskItem(object $row): array
    {
        $phone = (bool) $row->is_phone_task;
        $status = match (true) {
            $row->progress === 'closed', $row->actual_end !== null && ! $phone => 'done',
            $row->progress === 'waiting_sparepart' => 'waiting',
            $row->actual_start !== null && $row->actual_end === null && ! $phone, $row->progress === 'repair' => 'active',
            default => 'next',
        };

        return $this->ticketItem($row) + [
            'type' => $phone ? 'phone_call' : 'visit_site',
            'visit_id' => $phone ? null : ($row->visit_id === null ? null : (int) $row->visit_id),
            'scheduled_at' => CarbonImmutable::parse($row->task_at, 'Asia/Jakarta')->toIso8601String(),
            'time' => CarbonImmutable::parse($row->task_at, 'Asia/Jakarta')->format('H:i'), 'status' => $status,
        ];
    }

    public function ticketItem(object $row): array
    {
        return ['id' => (int) $row->id, 'ticket_code' => $row->ticket_code, 'priority' => $row->priority, 'progress' => $row->progress,
            'customer_name' => $row->customer_name, 'machine_name' => $row->machine_name, 'machine_category' => $row->machine_category,
            'closed_at' => $row->closed_at === null ? null : CarbonImmutable::parse($row->closed_at, 'Asia/Jakarta')->toIso8601String(), 'sla_result' => $row->sla_result];
    }

    public function drilldown(int $userId, array $filters): Builder
    {
        $metric = $filters['metric'];
        $q = $metric === 'closed' ? $this->closed($userId, $filters) : $this->tickets($userId)->where('progress', '<>', 'closed');
        if ($metric === 'status') {
            if (isset($filters['status'])) {
                $q->where('progress', $filters['status']);
            }
        } elseif ($metric === 'documents') {
            $q->where(function ($q) {
                $q->where(fn ($v3) => $v3->where('workflow_version', 'v3')->where(fn ($draft) => $draft->whereNull('documentation_status')->orWhere('documentation_status', 'incomplete')))
                    ->orWhere(function ($legacy) {
                        $legacy->where(fn ($w) => $w->whereNull('workflow_version')->orWhere('workflow_version', '<>', 'v3'))
                            ->where(function ($draft) {
                                foreach (['ticket_agreements', 'ticket_service_forms'] as $table) {
                                    $draft->orWhereNotExists(fn ($sub) => $sub->selectRaw('1')->from($table)->whereColumn($table.'.ticket_id', 'dashboard_ticket.id')->whereNull($table.'.deleted_at')->where('status', 'saved'));
                                }
                            });
                    });
            });
        } elseif ($metric === 'share_survey') {
            $q->whereIn('progress', ['documentation_published', 'satisfaction_submitted', 'happy_call_completed'])
                ->whereRaw("NOT (COALESCE(workflow_version, '') = 'v3' AND COALESCE(recoming_type, '') = 'phone_call' AND COALESCE(recoming_cycle_id, 0) = COALESCE(cycle_id, -1))")
                ->whereExists(function ($link) {
                    $link->selectRaw('1')->from('satisfaction_links')->whereColumn('ticket_id', 'dashboard_ticket.id')->whereNull('deleted_at')->where('is_deleted', 0)
                        ->where('status', 'active')->whereNull('shared_at')->where('expires_at', '>', CarbonImmutable::now('Asia/Jakarta')->toDateTimeString())
                        ->whereRaw("(dashboard_ticket.workflow_version <> 'v3' OR dashboard_ticket.workflow_version IS NULL OR satisfaction_links.cycle_id = dashboard_ticket.cycle_id)")
                        ->whereRaw('satisfaction_links.id = (SELECT MAX(sl.id) FROM satisfaction_links sl WHERE sl.ticket_id = satisfaction_links.ticket_id AND sl.deleted_at IS NULL AND sl.is_deleted = 0)');
                });
        } elseif ($metric === 'recoming') {
            $now = CarbonImmutable::now('Asia/Jakarta');
            $q->where('progress', 'recoming')
                ->whereRaw("(workflow_version <> 'v3' OR workflow_version IS NULL OR recoming_cycle_id = cycle_id)")
                ->whereBetween('recoming_at', [$now->toDateTimeString(), $now->addDays(7)->endOfDay()->toDateTimeString()]);
        }
        if ($metric === 'closed' && isset($filters['week_start'])) {
            $from = CarbonImmutable::parse($filters['week_start'], 'Asia/Jakarta');
            $q->whereBetween('closed_at', [$from->startOfDay()->toDateTimeString(), $from->endOfWeek()->toDateTimeString()]);
        }

        return $q;
    }

    private function rating(int $userId): ?float
    {
        $answers = DB::table('ticket_satisfaction_answers')->where('type', 'rating')->whereNotNull('value_number')
            ->selectRaw('ticket_satisfaction_id, SUM(value_number) as score_sum, COUNT(*) as score_count')->groupBy('ticket_satisfaction_id');
        $columns = array_keys(TicketSatisfaction::ASPECTS);
        $sum = implode(' + ', array_map(fn ($key) => "COALESCE(s.$key, 0)", $columns));
        $count = implode(' + ', array_map(fn ($key) => "CASE WHEN s.$key IS NULL THEN 0 ELSE 1 END", $columns));
        $sumSql = "CASE WHEN s.ref_survey_template_id IS NOT NULL THEN a.score_sum ELSE ($sum) END";
        $countSql = "CASE WHEN s.ref_survey_template_id IS NOT NULL THEN a.score_count ELSE ($count) END";
        $q = DB::table('ticket_satisfactions as s')->join('tickets as t', 't.id', '=', 's.ticket_id')->leftJoinSub($answers, 'a', 'a.ticket_satisfaction_id', '=', 's.id')
            ->where('t.assigned_technician_id', $userId)->whereNull('t.deleted_at')->where('t.is_deleted', 0)->whereNull('s.deleted_at')->where('s.is_deleted', 0)
            ->whereIn('s.review_status', ['normal', 'reviewed'])
            ->where(function ($q) {
                $q->where(fn ($v3) => $v3->where('t.workflow_version', 'v3')->whereNotNull('s.cycle_id')->whereNotNull('s.filled_at')
                    ->where(fn ($source) => $source->where('s.source', 'link')->orWhere(fn ($admin) => $admin->where('s.source', 'admin')->where('s.review_status', 'reviewed'))))
                    ->orWhere(fn ($legacy) => $legacy->where(fn ($version) => $version->whereNull('t.workflow_version')->orWhere('t.workflow_version', '<>', 'v3'))->where('s.source', 'link'));
            })
            ->whereRaw("s.id = (SELECT current_s.id FROM ticket_satisfactions current_s WHERE current_s.ticket_id = s.ticket_id AND current_s.deleted_at IS NULL AND current_s.is_deleted = 0 AND (t.workflow_version <> 'v3' OR t.workflow_version IS NULL OR current_s.cycle_id = s.cycle_id) ORDER BY current_s.filled_at DESC, current_s.id DESC LIMIT 1)")
            ->selectRaw("CASE WHEN t.workflow_version = 'v3' THEN $sumSql ELSE ROUND(1.0 * ($sumSql) / NULLIF($countSql, 0), 1) END as score_sum, CASE WHEN t.workflow_version = 'v3' THEN $countSql ELSE CASE WHEN ($countSql) > 0 THEN 1 ELSE 0 END END as score_count");
        $rating = DB::query()->fromSub($q, 'rating')->selectRaw('1.0 * SUM(score_sum) / NULLIF(SUM(score_count), 0) as value')->first()->value;

        return $rating === null ? null : round((float) $rating, 1);
    }

    private static function percent(int $value, int $total): ?float
    {
        return $total === 0 ? null : round($value / $total * 100, 1);
    }
}
