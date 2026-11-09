<?php

namespace App\Services\Aftersales;

use App\Models\Aftersales\TicketCost;
use App\Models\Aftersales\TicketSatisfaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SupervisorDashboard
{
    public const PERIODS = ['this_month', 'last_7_days', 'last_month', 'this_quarter', 'this_year', 'custom'];

    public static function window(array $filters): array
    {
        $now = CarbonImmutable::now('Asia/Jakarta');

        return match ($filters['period'] ?? 'this_month') {
            'last_7_days' => [$now->subDays(6)->startOfDay(), $now->endOfDay()],
            'last_month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            'this_quarter' => [$now->startOfQuarter(), $now->endOfQuarter()],
            'this_year' => [$now->startOfYear(), $now->endOfYear()],
            'custom' => [CarbonImmutable::parse($filters['start_date'], 'Asia/Jakarta')->startOfDay(), CarbonImmutable::parse($filters['end_date'], 'Asia/Jakarta')->endOfDay()],
            default => [$now->startOfMonth(), $now->endOfMonth()],
        };
    }

    public static function live(string $table): Builder
    {
        return DB::table($table)->whereNull($table.'.deleted_at')->where($table.'.is_deleted', 0);
    }

    public function base(array $filters): Builder
    {
        $closed = self::live('ticket_logs')->where('step', 'closed')->selectRaw('ticket_id, MAX(id) as closed_id')->groupBy('ticket_id');
        $latest = self::live('ticket_logs')->selectRaw('ticket_id, MAX(id) as latest_id')->groupBy('ticket_id');
        $parts = self::live('ticket_parts')->join('ref_spareparts as sp', 'sp.id', '=', 'ticket_parts.ref_sparepart_id')
            ->whereNull('sp.deleted_at')->where('sp.is_deleted', 0)->where('type', 'actual')
            ->selectRaw('ticket_id, SUM(qty * unit_price) as part_total')->groupBy('ticket_id');
        $costs = self::live('ticket_costs')->selectRaw('ticket_id, SUM('.TicketCost::amountIdrSql('ticket_costs').') as cost_total, MAX(CASE WHEN currency <> \'idr\' AND (exchange_rate IS NULL OR exchange_rate <= 0) THEN 1 ELSE 0 END) as invalid_rate')->groupBy('ticket_id');
        $recomings = self::live('ticket_recomings')->selectRaw("ticket_id, COUNT(*) as recoming_count, MAX(CASE WHEN reason NOT IN ('sparepart_unavailable', 'part_unavailable', 'site_access') THEN 1 ELSE 0 END) as counted_recoming, MAX(created_at) as last_recoming_at")->groupBy('ticket_id');
        $q = self::live('tickets')->join('service_customer_products as m', 'm.id', '=', 'tickets.customer_product_id')
            ->join('service_customers as c', 'c.id', '=', 'tickets.customer_id')
            ->leftJoin('ref_regions as region', 'region.id', '=', 'c.ref_region_id')
            ->leftJoin('ref_product_types as category', 'category.id', '=', 'm.ref_product_type_id')
            ->leftJoin('ref_products as product', 'product.id', '=', 'm.product_id')
            ->leftJoin('users as tech', 'tech.id', '=', 'tickets.assigned_technician_id')
            ->leftJoinSub($closed, 'cl', 'cl.ticket_id', '=', 'tickets.id')->leftJoin('ticket_logs as closed_log', 'closed_log.id', '=', 'cl.closed_id')
            ->leftJoinSub($latest, 'll', 'll.ticket_id', '=', 'tickets.id')->leftJoin('ticket_logs as last_log', 'last_log.id', '=', 'll.latest_id')
            ->leftJoinSub($parts, 'part_sum', 'part_sum.ticket_id', '=', 'tickets.id')
            ->leftJoinSub($costs, 'cost_sum', 'cost_sum.ticket_id', '=', 'tickets.id')
            ->leftJoinSub($recomings, 'rec', 'rec.ticket_id', '=', 'tickets.id')
            ->whereNull('m.deleted_at')->where('m.is_deleted', 0)->whereNull('c.deleted_at')->where('c.is_deleted', 0)
            ->when(! empty($filters['region']), fn ($q) => $q->where('c.ref_region_id', $filters['region']))
            ->when(! empty($filters['category']), fn ($q) => $q->where('m.ref_product_type_id', $filters['category']))
            ->select('tickets.id', 'tickets.ticket_code', 'tickets.category as problem', 'tickets.created_at', 'tickets.sla_due_at', 'tickets.assigned_technician_id', 'm.ref_product_type_id as machine_category_id', 'c.ref_region_id as region_id')
            ->selectRaw("c.name as customer_name, COALESCE(NULLIF(m.machine_name, ''), product.name, 'Not set') as model, COALESCE(category.name, 'Not set') as machine_category, COALESCE(region.name, 'Not set') as region, COALESCE(tech.name, 'Unassigned') as technician")
            ->selectRaw("CASE WHEN last_log.step = 'closed' THEN closed_log.created_at ELSE NULL END as closed_at")
            ->selectRaw('COALESCE(part_sum.part_total, 0) + COALESCE(cost_sum.cost_total, 0) as total, COALESCE(cost_sum.invalid_rate, 0) as invalid_rate, COALESCE(rec.recoming_count, 0) as recoming_count, COALESCE(rec.counted_recoming, 0) as counted_recoming, rec.last_recoming_at')
            ->selectRaw("CASE WHEN cost_sum.invalid_rate = 1 THEN 'unclassified' WHEN tickets.service_type = 'preventive' THEN 'pm' WHEN tickets.service_type <> 'repair' OR tickets.service_type IS NULL THEN 'unclassified' WHEN m.is_daxtro = 0 THEN 'billed' WHEN m.warranty_end IS NULL THEN 'unclassified' WHEN DATE(m.warranty_end) >= DATE(tickets.created_at) THEN 'warranty' ELSE 'billed' END as bucket");

        return DB::query()->fromSub($q, 'report');
    }

    private function between(Builder $q, string $column, array $window): Builder
    {
        return $q->whereBetween($column, array_map(fn ($date) => $date->toDateTimeString(), $window));
    }

    public function closed(array $filters, ?array $window = null): Builder
    {
        return $this->between($this->base($filters), 'closed_at', $window ?? self::window($filters));
    }

    private function totals(Builder $q): array
    {
        $row = $q->selectRaw("SUM(CASE WHEN bucket = 'billed' THEN total ELSE 0 END) as billed, SUM(CASE WHEN bucket = 'warranty' THEN total ELSE 0 END) as warranty, SUM(CASE WHEN bucket = 'pm' THEN total ELSE 0 END) as pm, SUM(CASE WHEN bucket = 'unclassified' THEN total ELSE 0 END) as unclassified, SUM(CASE WHEN bucket = 'unclassified' THEN 1 ELSE 0 END) as unclassified_count, SUM(invalid_rate) as invalid_rate_count")->first();

        return array_map('floatval', (array) $row);
    }

    public function summary(array $filters): array
    {
        [$start, $end] = $window = self::window($filters);
        $days = (int) $start->diffInDays($end->startOfDay()) + 1;
        $previous = [$start->subDays($days), $start->subDay()->endOfDay()];
        $totals = $this->totals($this->closed($filters));
        $old = $this->totals($this->closed($filters, $previous));
        foreach ([$totals, $old] as $i => $values) {
            if ($i === 0) {
                $totals['net'] = $values['billed'] - $values['warranty'];
            } else {
                $old['net'] = $values['billed'] - $values['warranty'];
            }
        }
        $kpi = [];
        foreach (['billed', 'warranty', 'net', 'pm'] as $key) {
            $kpi[$key] = ['value' => $totals[$key], 'delta' => $old[$key] == 0 ? ($totals[$key] == 0 ? 0 : null) : round(($totals[$key] - $old[$key]) / abs($old[$key]) * 100, 1)];
        }
        $months = [];
        $revenue = ['Billed' => [], 'Warranty' => [], 'PM' => []];
        for ($month = $start->startOfMonth(); $month <= $end; $month = $month->addMonth()) {
            $months[] = $month->format('M Y');
        }
        $monthSql = $this->monthSql('closed_at');
        $monthly = $this->closed($filters)->selectRaw("$monthSql as month, bucket, SUM(total) as amount")->groupBy('month', 'bucket')->get()->groupBy('month');
        for ($month = $start->startOfMonth(); $month <= $end; $month = $month->addMonth()) {
            $amounts = $monthly->get($month->format('Y-m'), collect())->pluck('amount', 'bucket');
            foreach (['Billed' => 'billed', 'Warranty' => 'warranty', 'PM' => 'pm'] as $label => $key) {
                $revenue[$label][] = (float) ($amounts[$key] ?? 0);
            }
        }
        $warranty = [];
        foreach (['model', 'region', 'technician'] as $key) {
            $warranty[$key] = $this->closed($filters)->where('bucket', 'warranty')->select($key)->selectRaw('SUM(total) as amount')->groupBy($key)->orderByDesc('amount')->limit(10)->get()->map(fn ($r) => [$r->{$key}, (float) $r->amount])->all();
        }
        $closedHealth = $this->closed($filters)->selectRaw('COUNT(*) as total, SUM(CASE WHEN sla_due_at IS NOT NULL AND closed_at <= sla_due_at THEN 1 ELSE 0 END) as on_time, SUM(CASE WHEN recoming_count > 0 THEN 1 ELSE 0 END) as recomings')->first();
        $completion = $this->base($filters)->where(function ($q) use ($window) {
            $this->between($q, 'closed_at', $window)->orWhere(function ($q) use ($window) {
                $q->whereNull('closed_at')->where('counted_recoming', 1);
                $this->between($q, 'last_recoming_at', $window);
            });
        })->selectRaw('SUM(CASE WHEN closed_at IS NOT NULL AND recoming_count = 0 THEN 1 ELSE 0 END) as c, SUM(CASE WHEN counted_recoming = 1 THEN 1 ELSE 0 END) as r')->first();
        $rating = $this->rating($filters, $window);
        $linkIds = $this->base($filters)->select('id');
        $links = $this->between(self::live('satisfaction_links')->whereIn('ticket_id', $linkIds)->where('status', '<>', 'replaced'), 'created_at', $window)
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'used' AND EXISTS (SELECT 1 FROM ticket_satisfactions s WHERE s.ticket_id = satisfaction_links.ticket_id AND (s.cycle_id = satisfaction_links.cycle_id OR (s.cycle_id IS NULL AND satisfaction_links.cycle_id IS NULL)) AND s.source = 'link' AND s.deleted_at IS NULL AND s.is_deleted = 0) THEN 1 ELSE 0 END) as used")->first();
        $reasons = self::live('ticket_recomings')->whereIn('ticket_id', $this->closed($filters)->select('id'))->select('reason')->selectRaw('COUNT(DISTINCT ticket_id) as total')->groupBy('reason')->get();
        $labels = ['sparepart_unavailable' => 'Part unavailable', 'part_unavailable' => 'Part unavailable (legacy)', 'further_work_needed' => 'Further work needed', 'site_access' => 'Site access or condition', 'unfinished_previous_work' => 'Unfinished from previous work', 'other' => 'Other (legacy)'];
        $recoming = ['labels' => [], 'counted' => [], 'excluded' => []];
        foreach ($reasons as $row) {
            $counted = ! in_array($row->reason, ['sparepart_unavailable', 'part_unavailable', 'site_access'], true);
            $recoming['labels'][] = $labels[$row->reason] ?? $row->reason;
            $recoming['counted'][] = $counted ? (int) $row->total : 0;
            $recoming['excluded'][] = $counted ? 0 : (int) $row->total;
        }

        return [
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString(), 'previous_start' => $previous[0]->toDateString(), 'previous_end' => $previous[1]->toDateString()],
            'unclassified' => (int) $totals['unclassified_count'], 'unclassified_value' => $totals['unclassified'], 'invalid_rate_count' => (int) $totals['invalid_rate_count'],
            'kpi' => $kpi, 'months' => $months, 'revenue' => $revenue,
            'split' => ['labels' => ['Billed', 'Warranty', 'PM', 'Unclassified'], 'values' => array_map(fn ($k) => $totals[$k], ['billed', 'warranty', 'pm', 'unclassified']), 'ratio' => self::percent($totals['warranty'], $totals['warranty'] + $totals['billed'])],
            'warranty' => $warranty,
            'health' => ['sla' => self::percent($closedHealth->on_time, $closedHealth->total), 'firstVisit' => self::percent($completion->c, $completion->c + $completion->r), 'rating' => $rating, 'ratingMax' => 3, 'recoming' => self::percent($closedHealth->recomings, $closedHealth->total), 'survey' => self::percent($links->used, $links->total)],
            'recoming' => $recoming,
            'problem' => $this->problems($filters, $window),
            'options' => ['regions' => DB::table('ref_regions')->orderBy('name')->get(['id', 'name']), 'categories' => DB::table('ref_product_types')->orderBy('name')->get(['id', 'name'])],
        ];
    }

    private function monthSql(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y-%m', $column)" : "DATE_FORMAT($column, '%Y-%m')";
    }

    private static function percent($numerator, $denominator): ?float
    {
        return $denominator > 0 ? round((float) $numerator / $denominator * 100, 1) : null;
    }

    private function rating(array $filters, array $window): ?float
    {
        $latest = self::live('ticket_satisfactions')->selectRaw('ticket_id, MAX(id) as id')->groupBy('ticket_id');
        $answers = DB::table('ticket_satisfaction_answers')->where('type', 'rating')->whereNotNull('value_number')->selectRaw('ticket_satisfaction_id, AVG(value_number) as score')->groupBy('ticket_satisfaction_id');
        $q = self::live('ticket_satisfactions')->joinSub($latest, 'current_response', 'current_response.id', '=', 'ticket_satisfactions.id')
            ->leftJoinSub($answers, 'scores', 'scores.ticket_satisfaction_id', '=', 'ticket_satisfactions.id')
            ->whereIn('ticket_satisfactions.ticket_id', $this->base($filters)->select('id'))->where('source', 'link')->whereIn('review_status', ['normal', 'reviewed']);
        $this->between($q, 'filled_at', $window);
        $columns = array_keys(TicketSatisfaction::ASPECTS);
        $sum = implode(' + ', array_map(fn ($c) => "COALESCE($c, 0)", $columns));
        $count = implode(' + ', array_map(fn ($c) => "CASE WHEN $c IS NULL THEN 0 ELSE 1 END", $columns));
        $score = "CASE WHEN ref_survey_template_id IS NOT NULL THEN scores.score ELSE (1.0 * ($sum)) / NULLIF(($count), 0) END";
        $value = $q->selectRaw("AVG(ROUND($score, 1)) as rating")->first()->rating;

        return $value === null ? null : round((float) $value, 1);
    }

    private function problems(array $filters, array $window): array
    {
        $result = [];
        $machineOrder = ['Tube', 'Cube', 'Block', 'Flake', 'Slurry', 'Cold Room', 'RO'];
        $machineNames = DB::table('ref_product_types')->orderBy('name')->pluck('name')->all();
        $machineLabel = static function (string $name): string {
            $value = strtolower($name);
            foreach (['tube' => 'Tube', 'cube' => 'Cube', 'block' => 'Block', 'flake' => 'Flake', 'slurry' => 'Slurry', 'cold' => 'Cold Room', 'osmosis' => 'RO'] as $term => $label) {
                if (str_contains($value, $term)) {
                    return $label;
                }
            }

            return strtoupper($name) === 'RO' ? 'RO' : $name;
        };
        
        foreach ($machineOrder as $label) {
            if (! in_array($label, array_map($machineLabel, $machineNames), true)) {
                $machineNames[] = $label;
            }
        }
        usort($machineNames, static function ($a, $b) use ($machineOrder, $machineLabel) {
            $aIndex = array_search($machineLabel($a), $machineOrder, true);
            $bIndex = array_search($machineLabel($b), $machineOrder, true);

            return ($aIndex === false ? 99 : $aIndex) <=> ($bIndex === false ? 99 : $bIndex);
        });
        foreach (['machine' => 'machine_category', 'region' => 'region', 'month' => $this->monthSql('created_at')] as $mode => $column) {
            $rows = $this->between($this->base($filters), 'created_at', $window)->selectRaw("COALESCE(problem, 'not_set') as problem_key, $column as col, COUNT(*) as total")->groupBy('problem_key', 'col')->get();
            $cols = $rows->pluck('col')->unique()->sort()->values()->all();
            if ($mode === 'machine') {
                $cols = array_values(array_unique(array_merge($machineNames, $cols)));
            } elseif ($mode === 'month') {
                $cols = [];
                for ($month = $window[0]->startOfMonth(); $month <= $window[1]; $month = $month->addMonth()) {
                    $cols[] = $month->format('Y-m');
                }
            }
            $names = ['refrigeration_system' => 'Refrigeration', 'mechanical' => 'Mechanical', 'production' => 'Production', 'electrical' => 'Electrical'];
            if ($rows->contains('problem_key', 'not_set')) {
                $names['not_set'] = 'Not set';
            }
            $matrix = [];
            $totals = [];
            foreach ($names as $key => $name) {
                $counts = $rows->where('problem_key', $key)->pluck('total', 'col');
                $matrix[] = array_map(fn ($c) => (int) ($counts[$c] ?? 0), $cols);
                $totals[] = array_sum(end($matrix));
            }
            $result[$mode] = ['cols' => $cols, 'column_labels' => $mode === 'machine' ? array_map($machineLabel, $cols) : $cols, 'rows' => array_values($names), 'keys' => array_keys($names), 'matrix' => $matrix, 'totals' => $totals];
        }

        return $result;
    }

    public function drilldown(array $filters): Builder
    {
        $q = ($filters['metric'] ?? '') === 'problem' ? $this->between($this->base($filters), 'created_at', self::window($filters)) : $this->closed($filters);
        if (($filters['metric'] ?? '') === 'problem') {
            ($filters['problem'] ?? '') === 'not_set' ? $q->whereNull('problem') : $q->where('problem', $filters['problem']);
            if (! empty($filters['column'])) {
                $column = match ($filters['mode'] ?? 'machine') {
                    'region' => 'region', 'month' => $this->monthSql('created_at'), default => 'machine_category'
                };
                $q->whereRaw("$column = ?", [$filters['column']]);
            }
        } else {
            $q->where('bucket', $filters['bucket'] ?? 'warranty');
            if (! empty($filters['column'])) {
                $q->where($filters['mode'] ?? 'model', $filters['column']);
            }
        }

        return $q->orderByDesc('id');
    }
}
