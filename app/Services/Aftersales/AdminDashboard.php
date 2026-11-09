<?php

namespace App\Services\Aftersales;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AdminDashboard
{
    private array $scopes = [];

    public const PROGRESS = ['published' => 'Published', 'assigned' => 'Assigned', 'on_site' => 'On Site', 'repair' => 'Report', 'waiting_sparepart' => 'Waiting Sparepart', 'documentation_published' => 'Docs Published', 'satisfaction_submitted' => 'Survey Submitted', 'happy_call_completed' => 'Happy Call Completed'];

    public const FOLLOW_UP = ['survey_due' => 'Survey due', 'link_expired' => 'Link expired', 'need_recoming' => 'Need Recoms', 'blocked_closed' => 'Blocked Closed', 'happy_call_due' => 'Happy Call due', 'ready_to_close' => 'Ready to Close'];

    public const HYGIENE = ['docs_rework' => 'Docs Rework', 'missing_contact' => 'No phone or email', 'missing_warranty' => 'No warranty date', 'spareparts_reorder' => 'Spareparts REORDER'];

    private function live(string $table): Builder
    {
        return SupervisorDashboard::live($table);
    }

    public function scoped(User $user): bool
    {
        return $this->scopes[$user->id] ??= TicketDocuments::isSupervisorScoped($user);
    }

    public function tickets(User $user): Builder
    {
        $v3 = "tickets.workflow_version = 'v3'";
        $legacyCycle = "l.id > COALESCE((SELECT MAX(rec.id) FROM ticket_logs rec WHERE rec.ticket_id = tickets.id AND rec.step = 'recoming' AND rec.deleted_at IS NULL AND rec.is_deleted = 0), 0)";
        $logCycle = "(($v3 AND l.cycle_id = cycle.id) OR (COALESCE(tickets.workflow_version, '') <> 'v3' AND $legacyCycle))";
        $q = $this->live('tickets')
            ->when($this->scoped($user), fn ($q) => $q->where('tickets.supervisor_id', $user->id))
            ->leftJoin('service_customers as customer', 'customer.id', '=', 'tickets.customer_id')
            ->leftJoin('service_customer_products as machine', 'machine.id', '=', 'tickets.customer_product_id')
            ->leftJoin('ref_products as product', 'product.id', '=', 'machine.product_id')
            ->leftJoin('users as technician', 'technician.id', '=', 'tickets.assigned_technician_id')
            ->leftJoin('ticket_cycles as cycle', 'cycle.id', '=', DB::raw('(SELECT c.id FROM ticket_cycles c WHERE c.ticket_id = tickets.id ORDER BY c.cycle_no DESC LIMIT 1)'))
            ->leftJoin('ticket_logs as log', 'log.id', '=', DB::raw('(SELECT MAX(l.id) FROM ticket_logs l WHERE l.ticket_id = tickets.id AND l.deleted_at IS NULL AND l.is_deleted = 0)'))
            ->leftJoin('ticket_visits as visit', 'visit.id', '=', DB::raw('(SELECT v.id FROM ticket_visits v WHERE v.ticket_id = tickets.id AND v.deleted_at IS NULL AND v.is_deleted = 0 ORDER BY v.scheduled_at DESC, v.id DESC LIMIT 1)'))
            ->leftJoin('ticket_recomings as recoming', 'recoming.id', '=', DB::raw('(SELECT MAX(r.id) FROM ticket_recomings r WHERE r.ticket_id = tickets.id AND r.deleted_at IS NULL AND r.is_deleted = 0)'))
            ->leftJoin('ticket_satisfactions as response', 'response.id', '=', DB::raw("CASE WHEN $v3 THEN (SELECT s.id FROM ticket_satisfactions s WHERE s.ticket_id = tickets.id AND s.deleted_at IS NULL AND s.is_deleted = 0 AND s.cycle_id = cycle.id ORDER BY s.filled_at DESC, s.id DESC LIMIT 1) ELSE (SELECT MAX(s.id) FROM ticket_satisfactions s WHERE s.ticket_id = tickets.id AND s.deleted_at IS NULL AND s.is_deleted = 0) END"))
            ->leftJoin('satisfaction_links as link', 'link.id', '=', DB::raw("(SELECT MAX(sl.id) FROM satisfaction_links sl WHERE sl.ticket_id = tickets.id AND sl.deleted_at IS NULL AND sl.is_deleted = 0 AND (COALESCE(tickets.workflow_version, '') <> 'v3' OR sl.cycle_id = cycle.id))"))
            ->select('tickets.id', 'tickets.ticket_code', 'tickets.priority', 'tickets.workflow_version', 'tickets.customer_id', 'tickets.customer_product_id', 'tickets.assigned_technician_id', 'tickets.supervisor_id', 'tickets.happy_call_required')
            ->selectRaw('CASE WHEN customer.deleted_at IS NULL AND COALESCE(customer.is_deleted, 0) = 0 THEN customer.name ELSE NULL END as customer_name')
            ->selectRaw("CASE WHEN machine.deleted_at IS NULL AND COALESCE(machine.is_deleted, 0) = 0 THEN COALESCE(NULLIF(machine.machine_name, ''), product.name) ELSE NULL END as machine_name")
            ->selectRaw('CASE WHEN technician.deleted_at IS NULL THEN technician.name ELSE NULL END as technician_name')
            ->selectRaw("CASE WHEN log.step IN ('on_site', 'repair', 'waiting_sparepart', 'documentation_published', 'satisfaction_submitted', 'happy_call_completed', 'closed', 'recoming') THEN log.step WHEN tickets.assigned_technician_id IS NULL THEN 'published' WHEN visit.scheduled_at <= ? AND NOT (COALESCE(tickets.workflow_version, '') = 'v3' AND COALESCE(recoming.recoming_type, '') = 'phone_call') THEN 'on_site' ELSE 'assigned' END as progress", [CarbonImmutable::now('Asia/Jakarta')->endOfDay()->toDateTimeString()])
            ->selectRaw("CASE WHEN $v3 THEN cycle.sla_due_at ELSE tickets.sla_due_at END as sla_due_at")
            ->selectRaw("CASE WHEN $v3 THEN cycle.sla_stopped_at ELSE NULL END as sla_stopped_at")
            ->selectRaw("CASE WHEN $v3 AND cycle.sla_started_at IS NULL AND (cycle.cycle_no <> 1 OR cycle.started_at IS NULL) THEN 0 ELSE 1 END as sla_configured")
            ->selectRaw('cycle.id as cycle_id, cycle.documentation_status, cycle.cycle_no, visit.scheduled_at, visit.cycle_id as visit_cycle_id, recoming.cycle_id as recoming_cycle_id, recoming.recoming_type, recoming.planned_visit_at, recoming.planned_call_at')
            ->selectRaw('response.id as response_id, response.source as response_source, response.review_status, response.machine_ok, response.filled_at, response.cycle_id as response_cycle_id')
            ->selectRaw('link.status as link_status, link.expires_at as link_expires_at')
            ->selectRaw("(SELECT MAX(l.created_at) FROM ticket_logs l WHERE l.ticket_id = tickets.id AND l.step = 'documentation_published' AND l.deleted_at IS NULL AND l.is_deleted = 0 AND $logCycle) as published_at")
            ->selectRaw("CASE WHEN EXISTS (SELECT 1 FROM ticket_logs l WHERE l.ticket_id = tickets.id AND l.step = 'happy_call_completed' AND l.deleted_at IS NULL AND l.is_deleted = 0 AND $logCycle AND (COALESCE(tickets.workflow_version, '') <> 'v3' OR l.satisfaction_id = response.id)) THEN 1 ELSE 0 END as happy_call_done")
            ->selectRaw("CASE WHEN EXISTS (SELECT 1 FROM satisfaction_links sl WHERE sl.ticket_id = tickets.id AND sl.deleted_at IS NULL AND sl.is_deleted = 0 AND sl.shared_at IS NOT NULL AND (COALESCE(tickets.workflow_version, '') <> 'v3' OR sl.cycle_id = cycle.id)) THEN 1 ELSE 0 END as survey_shared")
            ->selectRaw("CASE WHEN EXISTS (SELECT 1 FROM ticket_service_forms form WHERE form.ticket_id = tickets.id AND form.deleted_at IS NULL AND form.status = 'draft' AND form.unlocked_at IS NOT NULL) OR EXISTS (SELECT 1 FROM ticket_agreements agreement WHERE agreement.ticket_id = tickets.id AND agreement.deleted_at IS NULL AND agreement.status = 'draft' AND agreement.unlocked_at IS NOT NULL) THEN 1 ELSE 0 END as docs_rework");
        $report = DB::query()->fromSub($q, 'ticket_report')->select('ticket_report.*')
            ->selectRaw("CASE WHEN published_at IS NOT NULL AND (COALESCE(workflow_version, '') <> 'v3' OR documentation_status = 'documentation_published') THEN 1 ELSE 0 END as docs_ready")
            ->selectRaw("CASE WHEN review_status IN ('normal', 'reviewed') AND (response_source = 'link' OR (workflow_version = 'v3' AND review_status = 'reviewed' AND response_source = 'admin')) AND (COALESCE(workflow_version, '') <> 'v3' OR (response_cycle_id IS NOT NULL AND filled_at IS NOT NULL)) THEN 1 ELSE 0 END as survey_valid")
            ->selectRaw("CASE WHEN sla_due_at IS NULL OR sla_configured = 0 THEN 'not_configured' WHEN sla_stopped_at IS NOT NULL THEN CASE WHEN sla_stopped_at <= sla_due_at THEN 'on_track' ELSE 'overdue' END WHEN sla_due_at < ? THEN 'overdue' WHEN sla_due_at < ? THEN 'at_risk' ELSE 'on_track' END as sla_bucket", [CarbonImmutable::now('Asia/Jakarta')->toDateTimeString(), CarbonImmutable::now('Asia/Jakarta')->addDay()->toDateTimeString()]);

        return DB::query()->fromSub($report, 'admin_ticket');
    }

    private function open(User $user): Builder
    {
        return $this->tickets($user)->where('progress', '<>', 'closed');
    }

    public function summary(User $user): array
    {
        $progress = $this->open($user)->selectRaw('progress, COUNT(*) as total')->groupBy('progress')->pluck('total', 'progress');
        $sla = $this->open($user)->selectRaw('sla_bucket, COUNT(*) as total')->groupBy('sla_bucket')->pluck('total', 'sla_bucket');
        $followUp = [];
        foreach (self::FOLLOW_UP as $key => $label) {
            $followUp[] = ['key' => $key, 'label' => $label, 'count' => $this->queue($user, ['queue' => $key])->count()];
        }
        $hygiene = [];
        foreach (self::HYGIENE as $key => $label) {
            $hygiene[] = ['key' => $key, 'label' => $label, 'entity' => $this->hygieneEntity($key), 'count' => $this->hygiene($user, $key)->count()];
        }
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $schedule = $this->schedule($user)->whereBetween('schedule_at', [$today->toDateTimeString(), $today->addDays(3)->endOfDay()->toDateTimeString()])->get(['schedule_at', 'schedule_type']);
        $days = [];
        for ($offset = 0; $offset <= 3; $offset++) {
            $date = $today->addDays($offset)->toDateString();
            $rows = $schedule->filter(fn ($r) => substr($r->schedule_at, 0, 10) === $date);
            $visits = $rows->where('schedule_type', 'visit')->count();
            $recomings = $rows->where('schedule_type', 'recoming')->count();
            $calls = $rows->where('schedule_type', 'phone_call')->count();
            $days[] = ['date' => $date, 'label' => $offset === 0 ? 'Today' : '+'.$offset.' day'.($offset === 1 ? '' : 's'), 'visits' => $visits, 'recomings' => $recomings, 'total' => $visits + $recomings, 'phone_calls' => $calls];
        }
        $workload = $this->technicians($user)->paginate(20, ['*'], 'page', 1);

        return ['timezone' => 'Asia/Jakarta', 'generated_at' => CarbonImmutable::now('Asia/Jakarta')->toIso8601String(), 'scope' => $this->scoped($user) ? 'supervisor' : 'all',
            'progress' => ['total_open' => (int) $progress->sum(), 'recoming' => (int) ($progress['recoming'] ?? 0), 'items' => collect(self::PROGRESS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'count' => (int) ($progress[$key] ?? 0)])->values()->all()],
            'sla' => ['on_track' => (int) ($sla['on_track'] ?? 0), 'at_risk' => (int) ($sla['at_risk'] ?? 0), 'overdue' => (int) ($sla['overdue'] ?? 0), 'not_configured' => (int) ($sla['not_configured'] ?? 0), 'total_open' => (int) $sla->sum(), 'classified_open' => (int) $sla->except('not_configured')->sum()],
            'visits' => ['start' => $today->toDateString(), 'end' => $today->addDays(3)->toDateString(), 'days' => $days],
            'follow_up' => $followUp, 'hygiene' => $hygiene,
            'technicians' => ['total' => $workload->total(), 'has_more' => $workload->hasMorePages(), 'max_active' => (int) ($this->technicians($user)->first()->active_tickets ?? 0), 'items' => $workload->getCollection()->map(fn ($r, $i) => $this->technicianItem($r, $i + 1))->all()]];
    }

    public function schedule(User $user): Builder
    {
        $rec = "(recoming_cycle_id = cycle_id OR COALESCE(workflow_version, '') <> 'v3')";
        $rows = $this->open($user)->select('admin_ticket.*')
            ->selectRaw("CASE WHEN COALESCE(recoming_type, '') = 'phone_call' AND $rec THEN planned_call_at WHEN $rec AND planned_visit_at IS NOT NULL THEN planned_visit_at ELSE scheduled_at END as schedule_at")
            ->selectRaw("CASE WHEN COALESCE(recoming_type, '') = 'phone_call' AND $rec THEN 'phone_call' WHEN $rec AND planned_visit_at IS NOT NULL THEN 'recoming' ELSE 'visit' END as schedule_type")
            ->where(function ($q) use ($rec) {
                $q->whereRaw($rec)->where(function ($q) {
                    $q->where(fn ($q) => $q->where('recoming_type', 'phone_call')->whereNotNull('planned_call_at'))
                        ->orWhere(fn ($q) => $q->where(fn ($w) => $w->whereNull('recoming_type')->orWhere('recoming_type', '<>', 'phone_call'))->whereNotNull('planned_visit_at'));
                })->orWhere(function ($q) {
                    $q->whereNotNull('scheduled_at')->whereRaw("(COALESCE(workflow_version, '') <> 'v3' OR visit_cycle_id = cycle_id)")
                        ->whereRaw("NOT (COALESCE(workflow_version, '') = 'v3' AND COALESCE(recoming_type, '') = 'phone_call' AND COALESCE(recoming_cycle_id, 0) = COALESCE(cycle_id, -1))");
                });
            });

        return DB::query()->fromSub($rows, 'scheduled_ticket');
    }

    public function queue(User $user, array $filters): Builder
    {
        $key = $filters['queue'];
        $now = CarbonImmutable::now('Asia/Jakarta');
        $q = $key === 'visits' ? $this->schedule($user) : $this->open($user);
        if ($key === 'progress') {
            $q->where('progress', $filters['progress']);
        } elseif ($key === 'sla') {
            $q->where('sla_bucket', $filters['sla']);
        } elseif ($key === 'visits') {
            $day = CarbonImmutable::parse($filters['date'], 'Asia/Jakarta');
            $q->whereBetween('schedule_at', [$day->startOfDay()->toDateTimeString(), $day->endOfDay()->toDateTimeString()]);
            if (! empty($filters['type'])) {
                $q->where('schedule_type', $filters['type']);
            } else {
                $q->where('schedule_type', '<>', 'phone_call');
            }
        } elseif ($key === 'survey_due') {
            $q->where('progress', 'documentation_published')->where('published_at', '<', $now->subDays(3)->toDateTimeString())->where('survey_shared', 0);
        } elseif ($key === 'link_expired') {
            $q->where('link_status', 'active')->where('link_expires_at', '<=', $now->toDateTimeString());
        } elseif ($key === 'need_recoming') {
            $q->where('progress', '<>', 'recoming')->where('survey_valid', 1)->where('machine_ok', 0);
        } elseif ($key === 'blocked_closed') {
            $q->whereIn('review_status', ['needs_review', 'rejected']);
        } elseif ($key === 'happy_call_due') {
            $q->where('happy_call_done', 0)->where('progress', '<>', 'recoming')->where(function ($q) {
                $q->where(fn ($v3) => $v3->where('workflow_version', 'v3')->where('docs_ready', 1)->where('survey_valid', 1))
                    ->orWhere(fn ($legacy) => $legacy->whereRaw("COALESCE(workflow_version, '') <> 'v3'")->where('happy_call_required', 1)->where('progress', 'satisfaction_submitted'));
            });
        } elseif ($key === 'ready_to_close') {
            $q->where('progress', '<>', 'recoming')->where('docs_ready', 1)->where(function ($q) {
                $q->where(fn ($v3) => $v3->where('workflow_version', 'v3')->where('survey_valid', 1)->where('happy_call_done', 1))
                    ->orWhere(fn ($legacy) => $legacy->whereRaw("COALESCE(workflow_version, '') <> 'v3'")
                        ->where(fn ($review) => $review->whereNull('review_status')->orWhereNotIn('review_status', ['needs_review', 'rejected']))
                        ->where(fn ($call) => $call->where('happy_call_required', 0)->orWhere('happy_call_done', 1)));
            });
        } elseif ($key === 'docs_rework') {
            $q->where('docs_rework', 1);
        }

        return $q->when(isset($filters['technician_id']), fn ($q) => $q->where('assigned_technician_id', $filters['technician_id']));
    }

    public function hygieneEntity(string $queue): string
    {
        return match ($queue) {
            'docs_rework' => 'ticket', 'missing_contact' => 'customer', 'missing_warranty' => 'machine', default => 'sparepart',
        };
    }

    public function hygiene(User $user, string $queue): Builder
    {
        if ($queue === 'docs_rework') {
            return $this->queue($user, ['queue' => $queue]);
        }
        if ($queue === 'missing_contact') {
            return $this->live('service_customers')->where(fn ($q) => $q->whereRaw("TRIM(COALESCE(phone, '')) = ''")->orWhereRaw("TRIM(COALESCE(email, '')) = ''"))
                ->when($this->scoped($user), fn ($q) => $q->whereIn('id', $this->tickets($user)->select('customer_id')));
        }
        if ($queue === 'missing_warranty') {
            return $this->live('service_customer_products')->where(fn ($q) => $q->where('is_daxtro', 1)->orWhereNull('is_daxtro'))->whereNull('warranty_end')
                ->when($this->scoped($user), fn ($q) => $q->whereIn('id', $this->tickets($user)->select('customer_product_id')));
        }

        return $this->live('ref_spareparts')->where('min_stock', '>', 0)->whereColumn('stock', '<=', 'min_stock')
            ->when($this->scoped($user), fn ($q) => $q->whereIn('id', $this->live('ticket_parts')->whereIn('ticket_id', $this->tickets($user)->select('id'))->select('ref_sparepart_id')));
    }

    public function technicians(User $user): Builder
    {
        $counts = $this->open($user)->whereNotNull('assigned_technician_id')->selectRaw('assigned_technician_id, COUNT(*) as active_tickets')->groupBy('assigned_technician_id');

        return DB::table('users')->join('user_roles as role', 'role.id', '=', 'users.role_id')
            ->leftJoinSub($counts, 'workload', 'workload.assigned_technician_id', '=', 'users.id')
            ->whereNull('users.deleted_at')->whereNull('role.deleted_at')->where('role.code', 'technician')
            ->when($this->scoped($user), fn ($q) => $q->whereIn('users.id', $this->tickets($user)->whereNotNull('assigned_technician_id')->select('assigned_technician_id')))
            ->select('users.id', 'users.name')->selectRaw('COALESCE(workload.active_tickets, 0) as active_tickets')
            ->orderByDesc('active_tickets')->orderBy('users.id');
    }

    public function technicianItem(object $row, int $rank): array
    {
        return ['id' => (int) $row->id, 'name' => $row->name, 'rank' => $rank, 'active_tickets' => (int) $row->active_tickets, 'status' => $row->active_tickets > 0 ? 'on_duty' : 'standby'];
    }

    public function ticketItem(object $row): array
    {
        return ['id' => (int) $row->id, 'ticket_code' => $row->ticket_code, 'priority' => $row->priority, 'progress' => $row->progress,
            'customer' => ['id' => (int) $row->customer_id, 'name' => $row->customer_name], 'machine' => ['id' => (int) $row->customer_product_id, 'name' => $row->machine_name],
            'technician' => $row->assigned_technician_id === null ? null : ['id' => (int) $row->assigned_technician_id, 'name' => $row->technician_name],
            'sla' => ['bucket' => $row->sla_bucket, 'due_at' => $this->timestamp($row->sla_due_at), 'stopped_at' => $this->timestamp($row->sla_stopped_at)],
            ...(isset($row->schedule_at) ? ['scheduled_at' => $this->timestamp($row->schedule_at), 'schedule_type' => $row->schedule_type] : [])];
    }

    public function hygieneItem(object $row, string $queue): array
    {
        return match ($queue) {
            'docs_rework' => $this->ticketItem($row),
            'missing_contact' => ['id' => (int) $row->id, 'name' => $row->name, 'phone_missing' => blank($row->phone), 'email_missing' => blank($row->email)],
            'missing_warranty' => ['id' => (int) $row->id, 'customer_id' => (int) $row->customer_id, 'machine_name' => $row->machine_name, 'warranty_end' => null],
            default => ['id' => (int) $row->id, 'name' => $row->name, 'part_number' => $row->part_number, 'stock' => (int) $row->stock, 'min_stock' => (int) $row->min_stock],
        };
    }

    private function timestamp(?string $value): ?string
    {
        return $value === null ? null : CarbonImmutable::parse($value, 'Asia/Jakarta')->toIso8601String();
    }
}
