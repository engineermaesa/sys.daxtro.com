<?php

namespace App\Http\Controllers;

use App\Models\Orders\Quotation;
use App\Models\Leads\{Lead, LeadStatus, LeadSource};
use App\Models\Masters\Branch;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{

    private function claimUserColumn(): string
    {
        if (Schema::hasColumn('lead_claims', 'user_id'))   return 'user_id';
        if (Schema::hasColumn('lead_claims', 'sales_id'))  return 'sales_id';
        if (Schema::hasColumn('lead_claims', 'claimed_by')) return 'claimed_by';

        throw new \RuntimeException('Tabel lead_claims butuh kolom user_id/sales_id/claimed_by.');
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $showOrders = $user->hasPermission('orders');

        $salesQuery = User::query()
            ->whereHas('role', fn($q) => $q->where('code', 'sales'));

        $roleCode = $user->role?->code;
        if ($roleCode === 'sales') {
            $salesQuery->where('id', $user->id);
        } elseif ($roleCode === 'branch_manager') {
            $salesQuery->where('branch_id', $user->branch_id);
        }
        $salesUsers = $salesQuery->orderBy('name')->get(['id', 'name', 'branch_id']);

        $quotationStatusStats = [];
        if ($showOrders) {
            $quotationQuery = Quotation::query();

            if ($roleCode === 'sales') {
                $quotationQuery->whereHas('lead.region', fn($q) => $q->where('branch_id', $user->branch_id));
            } elseif ($roleCode === 'branch_manager') {
                $quotationQuery->whereHas('lead.region', fn($q) => $q->where('branch_id', $user->branch_id));
            }

            $counts = $quotationQuery->select(
                'status',
                DB::raw('count(*) as total'),
                DB::raw('sum(grand_total) as amount')
            )
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            $statuses = ['draft', 'review', 'pending_finance', 'published', 'rejected', 'expired'];
            foreach ($statuses as $status) {
                $quotationStatusStats[$status] = [
                    'total'  => $counts[$status]->total ?? 0,
                    'amount' => $counts[$status]->amount ?? 0,
                ];
            }
        }

        $branches = Branch::all();
        $leadSources = LeadSource::orderBy('name')->get();

        $defaultStart = now()->startOfMonth()->format('Y-m-d');
        $defaultEnd   = now()->endOfMonth()->format('Y-m-d');

        $defaultYtdStart = now()->startOfYear()->format('Y-m-d');
        $defaultYtdEnd   = now()->endOfMonth()->format('Y-m-d');

        return view('pages.dashboard.index', [
            'showOrders'           => $showOrders,
            'quotationStatusStats' => $quotationStatusStats,
            'branches'             => $branches,
            'leadSources'          => $leadSources,
            'currentBranchId'      => $user->branch_id,
            'defaultStart'         => $defaultStart,
            'defaultEnd'           => $defaultEnd,
            'defaultYtdStart'      => $defaultYtdStart,
            'defaultYtdEnd'        => $defaultYtdEnd,
            'salesUsers'           => $salesUsers,
        ]);
    }

    public function targetVsSalesMonthly(Request $request)
    {
        $validated = $request->validate([
            'year'  => 'nullable|integer|min:2000|max:2100',
            'scope' => 'nullable|string|in:global,jakarta,makassar,surabaya',
        ]);

        $year  = $validated['year']  ?? now()->year;
        $scope = $validated['scope'] ?? 'global';

        $BR_JKT = 'Branch Jakarta';
        $BR_MKS = 'Branch Makassar';
        $BR_SBY = 'Branch Surabaya';

        $globalMonthlyTarget = [
            12_820_500_000,
            10_989_000_000,
            9_157_500_000,
            7_326_000_000,
            12_820_500_000,
            16_483_500_000,
            16_483_500_000,
            18_315_000_000,
            18_315_000_000,
            21_978_000_000,
            21_978_000_000,
            16_483_500_000,
        ];

        $monthlyTargets = [
            $BR_JKT => [
                4_326_918_750,
                3_708_787_500,
                3_090_656_250,
                2_472_525_000,
                4_326_918_750,
                5_563_181_250,
                5_563_181_250,
                6_181_312_500,
                6_181_312_500,
                7_417_575_000,
                7_417_575_000,
                5_563_181_250,
            ],
            $BR_SBY => [
                3_382_249_500,
                2_899_071_000,
                2_415_892_500,
                1_932_714_000,
                3_382_249_500,
                4_348_606_500,
                4_348_606_500,
                4_831_785_000,
                4_831_785_000,
                5_798_142_000,
                5_798_142_000,
                4_348_606_500,
            ],
            $BR_MKS => [
                1_932_714_000,
                1_656_612_000,
                1_380_510_000,
                1_104_408_000,
                1_932_714_000,
                2_484_918_000,
                2_484_918_000,
                2_761_020_000,
                2_761_020_000,
                3_313_224_000,
                3_313_224_000,
                2_484_918_000,
            ],
        ];

        $scopeMap = [
            'global'   => [$BR_JKT, $BR_SBY, $BR_MKS],
            'jakarta'  => [$BR_JKT],
            'surabaya' => [$BR_SBY],
            'makassar' => [$BR_MKS],
        ];

        $wantedBranches = $scopeMap[$scope] ?? $scopeMap['global'];

        $start = \Carbon\Carbon::create($year, 1, 1)->toDateString();
        $end   = \Carbon\Carbon::create($year, 12, 31)->toDateString();

        $base = \App\Models\Orders\Order::query()
            ->join('leads', 'orders.lead_id', '=', 'leads.id')
            ->leftJoin('ref_regions', 'leads.region_id', '=', 'ref_regions.id')
            ->leftJoin('ref_branches', 'ref_regions.branch_id', '=', 'ref_branches.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$start, $end])
            ->whereIn('ref_branches.name', $wantedBranches);

        $roleCode = auth()->user()->role?->code;
        if (in_array($roleCode, ['sales', 'branch_manager'])) {
            $base->where('ref_branches.id', auth()->user()->branch_id);
        }

        $rows = (clone $base)
            ->selectRaw('YEAR(orders.created_at) as y, MONTH(orders.created_at) as m, COALESCE(SUM(orders.total_billing),0) as amt')
            ->groupBy('y', 'm')
            ->orderBy('y')->orderBy('m')
            ->get();

        $labels = [];
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = \Carbon\Carbon::create($year, $i, 1)->format('M');
        }

        $salesMonthly = array_fill(0, 12, 0.0);
        foreach ($rows as $r) {
            $idx = max(0, min(11, (int)$r->m - 1));
            $salesMonthly[$idx] = (float) $r->amt;
        }

        if (in_array($roleCode, ['sales', 'branch_manager'])) {
            $visibleNames = \App\Models\Masters\Branch::whereIn('id', [auth()->user()->branch_id])->pluck('name')->all();
            $wantedBranches = array_values(array_intersect($wantedBranches, $visibleNames));
            if (empty($wantedBranches)) {
                return response()->json([
                    'labels' => $labels,
                    'series' => [
                        ['label' => 'Target', 'data' => array_fill(0, 12, 0.0)],
                        ['label' => 'Sales',  'data' => $salesMonthly],
                    ],
                    'year'  => $year,
                    'scope' => $scope,
                ]);
            }
        }

        $targetMonthly = array_map('floatval', $globalMonthlyTarget);
        foreach ($wantedBranches as $bn) {
            $t = $monthlyTargets[$bn] ?? array_fill(0, 12, 0.0);
            for ($i = 0; $i < 12; $i++) {
                $targetMonthly[$i] += (float) $t[$i];
            }
        }

        $allBranchTarget = array_fill(0, 12, 0.0);
        foreach ([$BR_JKT, $BR_SBY, $BR_MKS] as $bn) {
            $t = $monthlyTargets[$bn] ?? array_fill(0, 12, 0.0);
            for ($i = 0; $i < 12; $i++) {
                $allBranchTarget[$i] += (float) $t[$i];
            }
        }

        $targetMonthly = array_fill(0, 12, 0.0);
        foreach ($wantedBranches as $bn) {
            $t = $monthlyTargets[$bn] ?? array_fill(0, 12, 0.0);
            for ($i = 0; $i < 12; $i++) {
                $targetMonthly[$i] += (float) $t[$i];
            }
        }

        return response()->json([
            'labels' => $labels,
            'series' => [
                ['label' => 'Target', 'data' => $targetMonthly],
                ['label' => 'Sales', 'data' => $salesMonthly],
                ['label' => 'All Branch Target', 'data' => $allBranchTarget],
            ],
            'year'  => $year,
            'scope' => $scope,
        ]);
    }


    public function salesPerformanceBar(Request $request)
    {
        $validated = $request->validate([
            'branch_id'  => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $start = $validated['start_date'] ?? now()->startOfYear()->toDateString();
        $end   = $validated['end_date']   ?? now()->endOfMonth()->toDateString();

        $dateLeadSql = "DATE(COALESCE(leads.published_at, leads.created_at))";
        $claimCol    = $this->claimUserColumn();

        $users = \App\Models\User::query()
            ->whereHas('role', fn($q) => $q->where('code', 'sales'));

        $roleCode = auth()->user()->role?->code;
        if ($roleCode === 'sales') {
            $users->where('users.id', auth()->id());
        } elseif ($roleCode === 'branch_manager') {
            $users->where('users.branch_id', auth()->user()->branch_id);
        }

        if (!empty($validated['branch_id'])) {
            $users->where('users.branch_id', $validated['branch_id']);
        }

        $users->leftJoin('lead_claims as lc', function ($j) use ($claimCol) {
            $j->on("lc.$claimCol", '=', 'users.id')
                ->whereNull('lc.deleted_at');
        })
            ->leftJoin('leads', 'leads.id', '=', 'lc.lead_id')
            ->leftJoin('orders', function ($j) use ($start, $end) {
                $j->on('orders.lead_id', '=', 'leads.id')
                    ->whereBetween(DB::raw('DATE(orders.created_at)'), [$start, $end]);
            });

        $rows = $users
            ->select('users.id', 'users.name')
            ->selectRaw(
                "COUNT(DISTINCT CASE WHEN $dateLeadSql BETWEEN ? AND ? AND leads.status_id = ? THEN leads.id END) as cold_count",
                [$start, $end, \App\Models\Leads\LeadStatus::COLD]
            )
            ->selectRaw(
                "COUNT(DISTINCT CASE WHEN $dateLeadSql BETWEEN ? AND ? AND leads.status_id = ? THEN leads.id END) as warm_count",
                [$start, $end, \App\Models\Leads\LeadStatus::WARM]
            )
            ->selectRaw(
                "COUNT(DISTINCT CASE WHEN $dateLeadSql BETWEEN ? AND ? AND leads.status_id = ? THEN leads.id END) as hot_count",
                [$start, $end, \App\Models\Leads\LeadStatus::HOT]
            )
            ->selectRaw("COUNT(DISTINCT orders.id) as deal_count")
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name')
            ->get();

        return response()->json([
            'labels' => $rows->pluck('name')->all(),
            'datasets' => [
                ['label' => 'Cold', 'data' => $rows->pluck('cold_count')->map(fn($v) => (int)$v)->all(), 'color' => '#4e73df'],
                ['label' => 'Warm', 'data' => $rows->pluck('warm_count')->map(fn($v) => (int)$v)->all(), 'color' => '#f6c23e'],
                ['label' => 'Hot', 'data' => $rows->pluck('hot_count')->map(fn($v) => (int)$v)->all(), 'color' => '#e74a3b'],
                ['label' => 'Deal', 'data' => $rows->pluck('deal_count')->map(fn($v) => (int)$v)->all(), 'color' => '#1cc88a'],
            ]
        ]);
    }

    public function salesAchievementDonut(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $GLOBAL_ANNUAL_PLAN = 183_150_000_000;

        $start = $validated['start_date'] ?? now()->startOfYear()->toDateString();
        $end   = $validated['end_date']   ?? now()->endOfMonth()->toDateString();

        $base = \App\Models\Orders\Order::query()
            ->join('leads', 'orders.lead_id', '=', 'leads.id')
            ->leftJoin('ref_regions', 'leads.region_id', '=', 'ref_regions.id')
            ->leftJoin('ref_branches', 'ref_regions.branch_id', '=', 'ref_branches.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$start, $end]);

        $roleCode = auth()->user()->role?->code;
        if (in_array($roleCode, ['sales', 'branch_manager'])) {
            $base->where('ref_branches.id', auth()->user()->branch_id);
        }

        $globalAchieved = (clone $base)->sum('orders.total_billing');

        $desiredOrder = ['Branch Jakarta', 'Branch Surabaya', 'Branch Makassar'];
        $rank = array_flip($desiredOrder);

        $branchRows = (clone $base)
            ->select([
                'ref_branches.id',
                'ref_branches.name',
                'ref_branches.target'
            ])
            ->selectRaw('COALESCE(SUM(orders.total_billing),0) as achieved')
            ->groupBy('ref_branches.id', 'ref_branches.name', 'ref_branches.target')
            ->get();

        $branches = $branchRows->map(function ($r) {
            $ach = (float) $r->achieved;
            $tgt = (float) $r->target;

            return [
                'id'       => (int) $r->id,
                'label'    => $r->name,
                'achieved' => $ach,
                'target'   => $tgt,
                'percent'  => $tgt > 0 ? round(($ach / $tgt) * 100, 2) : 0,
            ];
        })->sortBy(fn($b) => $rank[$b['label']] ?? 999)->values();

        $globalTargetFixed = (float) $GLOBAL_ANNUAL_PLAN;

        $allBranchPlan = $branchRows->sum('target');

        return response()->json([
            'global' => [
                'achieved' => (float) $globalAchieved,
                'target'   => $globalTargetFixed,
                'percent'  => $globalTargetFixed > 0 ? round(($globalAchieved / $globalTargetFixed) * 100, 2) : 0,
            ],
            'all_branch' => [
                'achieved' => (float) $globalAchieved,
                'target'   => (float) $allBranchPlan,
                'percent'  => $allBranchPlan > 0 ? round(($globalAchieved / $allBranchPlan) * 100, 2) : 0,
            ],
            'branches' => $branches,
            'start'    => $start,
            'end'      => $end,
        ]);
    }


    public function salesAchievementMonthlyPercent(Request $request)
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);
        $year = $validated['year'] ?? now()->year;

        $BR_JKT = 'Branch Jakarta';
        $BR_SBY = 'Branch Surabaya';
        $BR_MKS = 'Branch Makassar';
        $desiredOrder = [$BR_JKT, $BR_SBY, $BR_MKS];

        $monthlyTargets = [
            $BR_JKT => [
                4_326_918_750,
                3_708_787_500,
                3_090_656_250,
                2_472_525_000,
                4_326_918_750,
                5_563_181_250,
                5_563_181_250,
                6_181_312_500,
                6_181_312_500,
                7_417_575_000,
                7_417_575_000,
                5_563_181_250,
            ],
            $BR_SBY => [
                3_382_249_500,
                2_899_071_000,
                2_415_892_500,
                1_932_714_000,
                3_382_249_500,
                4_348_606_500,
                4_348_606_500,
                4_831_785_000,
                4_831_785_000,
                5_798_142_000,
                5_798_142_000,
                4_348_606_500,
            ],
            $BR_MKS => [
                1_932_714_000,
                1_656_612_000,
                1_380_510_000,
                1_104_408_000,
                1_932_714_000,
                2_484_918_000,
                2_484_918_000,
                2_761_020_000,
                2_761_020_000,
                3_313_224_000,
                3_313_224_000,
                2_484_918_000,
            ],
        ];

        $base = \App\Models\Orders\Order::query()
            ->join('leads', 'orders.lead_id', '=', 'leads.id')
            ->leftJoin('ref_regions', 'leads.region_id', '=', 'ref_regions.id')
            ->leftJoin('ref_branches', 'ref_regions.branch_id', '=', 'ref_branches.id')
            ->whereYear('orders.created_at', $year);

        $roleCode = auth()->user()->role?->code;
        if (in_array($roleCode, ['sales', 'branch_manager'])) {
            $base->where('ref_branches.id', auth()->user()->branch_id);
        }

        $rows = (clone $base)
            ->select('ref_branches.name as bname')
            ->selectRaw('MONTH(orders.created_at) as m')
            ->selectRaw('COALESCE(SUM(orders.total_billing),0) as achieved')
            ->groupBy('bname', 'm')
            ->get();

        $labels = [];
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = \Carbon\Carbon::create($year, $i, 1)->format('M');
        }

        $achieved = [];
        foreach ($rows as $r) {
            $b = $r->bname;
            $idx = max(0, min(11, (int)$r->m - 1));
            if (!isset($achieved[$b])) $achieved[$b] = array_fill(0, 12, 0.0);
            $achieved[$b][$idx] = (float)$r->achieved;
        }

        $visible = $desiredOrder;
        if (in_array($roleCode, ['sales', 'branch_manager'])) {
            $visible = \App\Models\Masters\Branch::where('id', auth()->user()->branch_id)->pluck('name')->all();
        }

        $palette = ['#4e73df', '#e74a3b', '#1cc88a'];
        $datasets = [];
        foreach ($desiredOrder as $i => $bn) {
            if (!in_array($bn, $visible)) continue;

            $ach = $achieved[$bn] ?? array_fill(0, 12, 0.0);
            $tgt = $monthlyTargets[$bn] ?? array_fill(0, 12, 0.0);

            $pct = [];
            for ($k = 0; $k < 12; $k++) {
                $pct[$k] = ($tgt[$k] > 0) ? round(($ach[$k] / $tgt[$k]) * 100, 2) : 0.0;
            }

            $datasets[] = [
                'label' => $bn,
                'data'  => $pct,
                'color' => $palette[$i % count($palette)],
            ];
        }

        return response()->json([
            'labels'   => $labels,
            'datasets' => $datasets,
            'year'     => $year,
        ]);
    }


    public function salesAchievementTrend(Request $request)
    {
        $validated = $request->validate([
            'sales_ids'   => 'nullable|array',
            'sales_ids.*' => 'integer',
            'branch_id'   => 'nullable|integer',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date',
        ]);

        $start = $validated['start_date'] ?? now()->startOfYear()->toDateString();
        $end   = $validated['end_date']   ?? now()->endOfMonth()->toDateString();

        $claimCol = $this->claimUserColumn();

        $base = \App\Models\Orders\Order::query()
            ->join('leads', 'orders.lead_id', '=', 'leads.id')
            ->leftJoin('lead_claims as lc', function ($j) {
                $j->on('lc.lead_id', '=', 'leads.id')->whereNull('lc.deleted_at');
            })
            ->leftJoin('users as u', function ($j) use ($claimCol) {
                $j->on('u.id', '=', "lc.$claimCol");
            })
            ->leftJoin('ref_regions', 'leads.region_id', '=', 'ref_regions.id')
            ->leftJoin('ref_branches', 'ref_regions.branch_id', '=', 'ref_branches.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$start, $end]);

        $roleCode = auth()->user()->role?->code;
        if ($roleCode === 'sales') {
            $base->where("lc.$claimCol", auth()->id());
        } elseif ($roleCode === 'branch_manager') {
            $base->where('u.branch_id', auth()->user()->branch_id);
        }
        if (!empty($validated['branch_id'])) {
            $base->where('u.branch_id', $validated['branch_id']);
        }

        if (!empty($validated['sales_ids'])) {
            $selected = array_slice(array_map('intval', $validated['sales_ids']), 0, 3);
        } else {
            $selected = (clone $base)
                ->select('u.id as uid')
                ->selectRaw('COALESCE(SUM(orders.total_billing),0) as amt')
                ->groupBy('uid')
                ->orderByDesc('amt')
                ->limit(3)
                ->pluck('uid')
                ->filter()
                ->all();
        }

        $period = CarbonPeriod::create(
            Carbon::parse($start)->startOfMonth(),
            '1 month',
            Carbon::parse($end)->startOfMonth()
        );
        $labels = [];
        $midx = [];
        foreach ($period as $i => $dt) {
            $k = $dt->format('Y-m');
            $midx[$k] = $i;
            $labels[] = $dt->format('M');
        }

        $totals = (clone $base)
            ->selectRaw("YEAR(orders.created_at) as y, MONTH(orders.created_at) as m, COALESCE(SUM(orders.total_billing),0) as amt")
            ->groupBy('y', 'm')->orderBy('y')->orderBy('m')->get();

        $totalByMonth = array_fill(0, count($labels), 0.0);
        foreach ($totals as $r) {
            $k = sprintf('%04d-%02d', (int)$r->y, (int)$r->m);
            if (isset($midx[$k])) $totalByMonth[$midx[$k]] = (float)$r->amt;
        }

        if (empty($selected)) {
            return response()->json(['labels' => $labels, 'series' => []]);
        }

        $rows = (clone $base)
            ->whereIn('u.id', $selected)
            ->selectRaw("
                u.id as uid, u.name,
                YEAR(orders.created_at)  as y,
                MONTH(orders.created_at) as m,
                COALESCE(SUM(orders.total_billing),0) as amt
            ")
            ->groupBy('uid', 'u.name', 'y', 'm')
            ->orderBy('y')->orderBy('m')
            ->get();

        $names = User::whereIn('id', $selected)->pluck('name', 'id');
        $seriesMap = [];
        foreach ($selected as $uid) {
            $seriesMap[$uid] = ['label' => $names[$uid] ?? ('Sales ' . $uid), 'data' => array_fill(0, count($labels), 0)];
        }
        foreach ($rows as $r) {
            $k = sprintf('%04d-%02d', (int)$r->y, (int)$r->m);
            if (!isset($midx[$k])) continue;
            $idx = $midx[$k];
            $tot = $totalByMonth[$idx] ?: 0;
            $pct = $tot > 0 ? ((float)$r->amt / $tot) * 100.0 : 0.0;
            $seriesMap[$r->uid]['label'] = $r->name;
            $seriesMap[$r->uid]['data'][$idx] = round($pct, 2);
        }

        return response()->json([
            'labels' => $labels,
            'series' => array_values($seriesMap),
        ]);
    }

    public function ordersMonthlyStats(Request $request)
    {
        $validated = $request->validate([
            'branch_id'  => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $start = $validated['start_date'] ?? now()->startOfYear()->toDateString();
        $end   = $validated['end_date']   ?? now()->endOfMonth()->toDateString();

        $query = \App\Models\Orders\Order::query()
            ->join('leads', 'orders.lead_id', '=', 'leads.id')
            ->leftJoin('ref_regions', 'leads.region_id', '=', 'ref_regions.id')
            ->leftJoin('ref_branches', 'ref_regions.branch_id', '=', 'ref_branches.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$start, $end]);

        $roleCode = auth()->user()->role?->code;
        if (in_array($roleCode, ['sales', 'branch_manager'])) {
            $query->where('ref_branches.id', auth()->user()->branch_id);
        }

        if (!empty($validated['branch_id'])) {
            $query->where('ref_branches.id', $validated['branch_id']);
        }

        $rows = $query->selectRaw("
                YEAR(orders.created_at)  as y,
                MONTH(orders.created_at) as m,
                COUNT(*)                 as total_orders,
                COALESCE(SUM(orders.total_billing),0) as total_amount
            ")
            ->groupBy('y', 'm')
            ->orderBy('y')->orderBy('m')
            ->get();

        $period = CarbonPeriod::create(
            Carbon::parse($start)->startOfMonth(),
            '1 month',
            Carbon::parse($end)->startOfMonth()
        );

        $result = [];
        foreach ($period as $dt) {
            $y = (int) $dt->format('Y');
            $m = (int) $dt->format('n');

            $found = $rows->first(fn($r) => (int)$r->y === $y && (int)$r->m === $m);

            $result[] = [
                'month'  => $dt->format('Y-m'),
                'label'  => $dt->format('M'),
                'count'  => (int)   ($found->total_orders  ?? 0),
                'amount' => (float) ($found->total_amount  ?? 0),
            ];
        }

        return response()->json($result);
    }

    public function leadSourceStats(Request $request)
    {
        $validated = $request->validate([
            'status_id'  => 'required|integer',
            'branch_id'  => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $query = Lead::query()
            ->join('lead_sources', 'lead_sources.id', '=', 'leads.source_id')
            ->select('lead_sources.name as source', DB::raw('count(*) as total'))
            ->where('leads.status_id', $validated['status_id']);

        if (!empty($validated['branch_id'])) {
            $query->where('leads.branch_id', $validated['branch_id']);
        }
        if (!empty($validated['start_date'])) {
            $query->whereDate('leads.published_at', '>=', $validated['start_date']);
        }
        if (!empty($validated['end_date'])) {
            $query->whereDate('leads.published_at', '<=', $validated['end_date']);
        }

        $data = $query->groupBy('lead_sources.name')
            ->orderBy('lead_sources.name')
            ->get();

        return response()->json($data);
    }

    public function quotationStatusStats(Request $request)
    {
        $validated = $request->validate([
            'status'     => 'required|string|in:review,published,rejected',
            'branch_id'  => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $query = Quotation::query()
            ->join('leads', 'leads.id', '=', 'quotations.lead_id')
            ->where('quotations.status', $validated['status']);

        if (!empty($validated['branch_id'])) {
            $query->where('leads.branch_id', $validated['branch_id']);
        }
        if (!empty($validated['start_date'])) {
            $query->whereDate('quotations.created_at', '>=', $validated['start_date']);
        }
        if (!empty($validated['end_date'])) {
            $query->whereDate('quotations.created_at', '<=', $validated['end_date']);
        }

        $total = $query->count();

        return response()->json(['total' => $total]);
    }

    public function branchSalesTrend(Request $request)
    {
        $validated = $request->validate([
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $start = $validated['start_date'] ?? now()->startOfYear()->toDateString();
        $end   = $validated['end_date']   ?? now()->endOfMonth()->toDateString();

        $baseQuery = \App\Models\Orders\Order::query()
            ->join('leads', 'orders.lead_id', '=', 'leads.id')
            ->leftJoin('ref_regions', 'leads.region_id', '=', 'ref_regions.id')
            ->leftJoin('ref_branches', 'ref_regions.branch_id', '=', 'ref_branches.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$start, $end]);

        $roleCode = auth()->user()->role?->code;
        if (in_array($roleCode, ['sales', 'branch_manager'])) {
            $baseQuery->where('ref_branches.id', auth()->user()->branch_id);
        }

        $selectedBranchIds = [];
        if (!empty($validated['branch_ids'])) {
            $selectedBranchIds = array_slice(array_map('intval', $validated['branch_ids']), 0, 3);
            $topQuery = (clone $baseQuery)->whereIn('ref_branches.id', $selectedBranchIds);
        } else {
            $topQuery = (clone $baseQuery)
                ->select('ref_branches.id')
                ->selectRaw('COALESCE(SUM(orders.total_billing),0) as total_amount')
                ->groupBy('ref_branches.id')
                ->orderByDesc('total_amount')
                ->limit(3);
            $selectedBranchIds = $topQuery->pluck('ref_branches.id')->all();
        }

        if (empty($selectedBranchIds)) {
            return response()->json([
                'labels' => [],
                'series' => [],
            ]);
        }

        $rows = (clone $baseQuery)
            ->whereIn('ref_branches.id', $selectedBranchIds)
            ->selectRaw('
            ref_branches.id as branch_id,
            ref_branches.name as branch_name,
            YEAR(orders.created_at)  as y,
            MONTH(orders.created_at) as m,
            COALESCE(SUM(orders.total_billing),0) as total_amount
        ')
            ->groupBy('branch_id', 'branch_name', 'y', 'm')
            ->orderBy('y')->orderBy('m')
            ->get();

        $period = CarbonPeriod::create(
            Carbon::parse($start)->startOfMonth(),
            '1 month',
            Carbon::parse($end)->startOfMonth()
        );

        $labels = [];
        $months = [];
        foreach ($period as $idx => $dt) {
            $key = $dt->format('Y-m');
            $months[$key] = $idx;
            $labels[] = $dt->format('M');
        }

        $branchNames = (clone $baseQuery)
            ->whereIn('ref_branches.id', $selectedBranchIds)
            ->distinct()
            ->pluck('ref_branches.name', 'ref_branches.id');

        $seriesMap = [];
        foreach ($selectedBranchIds as $bid) {
            $seriesMap[$bid] = [
                'label' => $branchNames[$bid] ?? ('Branch ' . $bid),
                'data'  => array_fill(0, count($labels), 0),
            ];
        }

        foreach ($rows as $r) {
            $key = sprintf('%04d-%02d', (int)$r->y, (int)$r->m);
            if (isset($months[$key]) && isset($seriesMap[$r->branch_id])) {
                $seriesMap[$r->branch_id]['data'][$months[$key]] = (float)$r->total_amount;
                $seriesMap[$r->branch_id]['label'] = $r->branch_name;
            }
        }

        return response()->json([
            'labels' => $labels,
            'series' => array_values($seriesMap),
        ]);
    }

    public function leadsBranchTrend(Request $request)
    {
        $validated = $request->validate([
            'status'       => 'required|string|in:cold,warm,hot',
            'branch_ids'   => 'nullable|array',
            'branch_ids.*' => 'integer',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date',
        ]);

        $TARGET_TABLE = [
            'cold' => [
                'Branch Jakarta'  => array_fill(0, 12, 80),
                'Branch Surabaya' => array_fill(0, 12, 60),
                'Branch Makassar' => array_fill(0, 12, 60),
            ],
            'warm' => [
                'Branch Jakarta'  => array_fill(0, 12, 25),
                'Branch Surabaya' => array_fill(0, 12, 15),
                'Branch Makassar' => array_fill(0, 12, 15),
            ],
            'hot' => [
                'Branch Jakarta'  => array_fill(0, 12, 12),
                'Branch Surabaya' => array_fill(0, 12, 9),
                'Branch Makassar' => array_fill(0, 12, 9),
            ],
        ];
        $statusKey = $validated['status'];

        $statusMap = [
            'cold' => LeadStatus::COLD,
            'warm' => LeadStatus::WARM,
            'hot'  => LeadStatus::HOT,
        ];
        $statusId = $statusMap[$statusKey];

        $start = $validated['start_date'] ?? now()->startOfYear()->toDateString();
        $end   = $validated['end_date']   ?? now()->endOfMonth()->toDateString();

        $dateExpr = DB::raw('DATE(COALESCE(leads.published_at, leads.created_at))');

        $amountSub = DB::table('quotations')
            ->select('lead_id', DB::raw('SUM(grand_total) as amount_per_lead'))
            ->whereNull('deleted_at')
            ->groupBy('lead_id');

        $base = Lead::query()
            ->leftJoin('ref_regions', 'leads.region_id', '=', 'ref_regions.id')
            ->leftJoin('ref_branches', 'ref_regions.branch_id', '=', 'ref_branches.id')
            ->leftJoinSub($amountSub, 'q', function ($join) {
                $join->on('q.lead_id', '=', 'leads.id');
            })
            ->where('leads.status_id', $statusId)
            ->whereBetween($dateExpr, [$start, $end]);

        $roleCode = auth()->user()->role?->code;
        if (in_array($roleCode, ['sales', 'branch_manager'])) {
            $base->where('ref_branches.id', auth()->user()->branch_id);
        }

        if (!empty($validated['branch_ids'])) {
            $selected = array_slice(array_map('intval', $validated['branch_ids']), 0, 3);
        } else {
            $selected = (clone $base)
                ->whereNotNull('ref_branches.id')
                ->select('ref_branches.id')
                ->selectRaw('COUNT(DISTINCT leads.id) as cnt')
                ->groupBy('ref_branches.id')
                ->orderByDesc('cnt')
                ->limit(3)
                ->pluck('ref_branches.id')
                ->all();
        }

        if (empty($selected)) {
            return response()->json(['labels' => [], 'series_count' => [], 'series_amount' => [], 'target_count' => [], 'target_amount' => []]);
        }

        $rows = (clone $base)
            ->whereIn('ref_branches.id', $selected)
            ->selectRaw('
            ref_branches.id   as branch_id,
            ref_branches.name as branch_name,
            YEAR(COALESCE(leads.published_at, leads.created_at))  as y,
            MONTH(COALESCE(leads.published_at, leads.created_at)) as m,
            COUNT(DISTINCT leads.id)                               as total_leads,
            COALESCE(SUM(q.amount_per_lead), 0)                    as total_amount
        ')
            ->groupBy('branch_id', 'branch_name', 'y', 'm')
            ->orderBy('y')->orderBy('m')
            ->get();

        $period = CarbonPeriod::create(
            Carbon::parse($start)->startOfMonth(),
            '1 month',
            Carbon::parse($end)->startOfMonth()
        );

        $labels = [];
        $monthIndex = [];
        foreach ($period as $i => $dt) {
            $key = $dt->format('Y-m');
            $monthIndex[$key] = $i;
            $labels[] = $dt->format('M');
        }

        $names = Branch::whereIn('id', $selected)->pluck('name', 'id');

        $seriesCount  = [];
        $seriesAmount = [];
        foreach ($selected as $bid) {
            $label = $names[$bid] ?? ('Branch ' . $bid);
            $seriesCount[$bid]  = ['label' => $label, 'data' => array_fill(0, count($labels), 0)];
            $seriesAmount[$bid] = ['label' => $label, 'data' => array_fill(0, count($labels), 0)];
        }

        $sumLeadsByIdx  = array_fill(0, count($labels), 0);
        $sumAmountByIdx = array_fill(0, count($labels), 0);

        foreach ($rows as $r) {
            $key = sprintf('%04d-%02d', (int)$r->y, (int)$r->m);
            if (isset($monthIndex[$key])) {
                $idx = $monthIndex[$key];

                if (isset($seriesCount[$r->branch_id])) {
                    $seriesCount[$r->branch_id]['label']  = $r->branch_name;
                    $seriesAmount[$r->branch_id]['label'] = $r->branch_name;

                    $seriesCount[$r->branch_id]['data'][$idx]  = (int)$r->total_leads;
                    $seriesAmount[$r->branch_id]['data'][$idx] = (float)$r->total_amount;
                }

                $sumLeadsByIdx[$idx]  += (int)$r->total_leads;
                $sumAmountByIdx[$idx] += (float)$r->total_amount;
            }
        }

        $targetCount = array_fill(0, count($labels), 0);
        $period2 = CarbonPeriod::create(Carbon::parse($start)->startOfMonth(), '1 month', Carbon::parse($end)->startOfMonth());
        $i = 0;
        foreach ($period2 as $dt) {
            $mi = (int)$dt->format('n') - 1;
            foreach ($selected as $bid) {
                $bname = $names[$bid] ?? null;
                if (!$bname) continue;
                $tbl = $TARGET_TABLE[$statusKey][$bname] ?? null;
                if (!$tbl) continue;
                $targetCount[$i] += (int)($tbl[$mi] ?? 0);
            }
            $i++;
        }

        $targetAmount = [];
        for ($k = 0; $k < count($labels); $k++) {
            $avg = $sumLeadsByIdx[$k] > 0 ? ($sumAmountByIdx[$k] / $sumLeadsByIdx[$k]) : 0;
            $targetAmount[$k] = $targetCount[$k] * $avg;
        }

        return response()->json([
            'labels'        => $labels,
            'series_count'  => array_values($seriesCount),
            'series_amount' => array_values($seriesAmount),
            'target_count'  => $targetCount,
            'target_amount' => $targetAmount,
        ]);
    }


    public function leadStatusTotal(Request $request)
    {
        $validated = $request->validate([
            'status_id'  => 'required|integer',
            'branch_id'  => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $query = Lead::query()->where('status_id', $validated['status_id']);

        if (!empty($validated['branch_id'])) {
            $query->where('branch_id', $validated['branch_id']);
        }
        if (!empty($validated['start_date'])) {
            $query->whereDate('published_at', '>=', $validated['start_date']);
        }
        if (!empty($validated['end_date'])) {
            $query->whereDate('published_at', '<=', $validated['end_date']);
        }

        $total = $query->count();

        return response()->json(['total' => $total]);
    }

    public function leadOverviewStats(Request $request)
    {
        $validated = $request->validate([
            'branch_id'  => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $baseQuery = Lead::query();

        if (!empty($validated['branch_id'])) {
            $baseQuery->where('branch_id', $validated['branch_id']);
        }
        if (!empty($validated['start_date'])) {
            $baseQuery->whereDate('published_at', '>=', $validated['start_date']);
        }
        if (!empty($validated['end_date'])) {
            $baseQuery->whereDate('published_at', '<=', $validated['end_date']);
        }

        $leadIn = (clone $baseQuery)
            ->whereDoesntHave('claims')
            ->count();

        $pending = (clone $baseQuery)
            ->whereIn('status_id', [LeadStatus::COLD, LeadStatus::WARM])
            ->whereHas('claims', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->count();

        $acquired = (clone $baseQuery)
            ->whereIn('status_id', [LeadStatus::HOT, LeadStatus::DEAL])
            ->whereHas('claims', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->count();

        $data = [
            ['source' => 'Leads In', 'total' => $leadIn],
            ['source' => 'Leads Pending', 'total' => $pending],
            ['source' => 'Leads Terakuisisi', 'total' => $acquired],
        ];

        return response()->json($data);
    }

    public function coldToWarmStats(Request $request)
    {
        $validated = $request->validate([
            'branch_id'  => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $statusIds = [
            LeadStatus::TRASH_COLD,
            LeadStatus::WARM,
        ];

        $query = Lead::query()->whereIn('status_id', $statusIds);

        if (!empty($validated['branch_id'])) {
            $query->where('branch_id', $validated['branch_id']);
        }
        if (!empty($validated['start_date'])) {
            $query->whereDate('published_at', '>=', $validated['start_date']);
        }
        if (!empty($validated['end_date'])) {
            $query->whereDate('published_at', '<=', $validated['end_date']);
        }

        $counts = $query->select('status_id', DB::raw('count(*) as total'))
            ->groupBy('status_id')
            ->get()
            ->keyBy('status_id');

        $data = [
            [
                'source' => 'Trash Cold',
                'total'  => $counts[LeadStatus::TRASH_COLD]->total ?? 0,
            ],
            [
                'source' => 'Warm',
                'total'  => $counts[LeadStatus::WARM]->total ?? 0,
            ],
        ];

        return response()->json($data);
    }

    public function warmToHotStats(Request $request)
    {
        $validated = $request->validate([
            'branch_id'  => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $statusIds = [
            LeadStatus::TRASH_WARM,
            LeadStatus::HOT,
        ];

        $query = Lead::query()->whereIn('status_id', $statusIds);

        if (!empty($validated['branch_id'])) {
            $query->where('branch_id', $validated['branch_id']);
        }
        if (!empty($validated['start_date'])) {
            $query->whereDate('published_at', '>=', $validated['start_date']);
        }
        if (!empty($validated['end_date'])) {
            $query->whereDate('published_at', '<=', $validated['end_date']);
        }

        $counts = $query->select('status_id', DB::raw('count(*) as total'))
            ->groupBy('status_id')
            ->get()
            ->keyBy('status_id');

        $data = [
            [
                'source' => 'Trash Warm',
                'total'  => $counts[LeadStatus::TRASH_WARM]->total ?? 0,
            ],
            [
                'source' => 'Hot',
                'total'  => $counts[LeadStatus::HOT]->total ?? 0,
            ],
        ];

        return response()->json($data);
    }
    public function mkt5a(Request $request)
    {
        $validated = $request->validate([
            'branch_id'  => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
            'status_id'  => 'nullable|integer',
            'region_id'  => 'nullable|integer',
            'source_id'  => 'nullable|integer',
        ]);

        try {
            $allowedStatuses = [
                LeadStatus::COLD,
                LeadStatus::WARM,
                LeadStatus::HOT,
                LeadStatus::DEAL
            ];

            $baseQuery = Lead::query()->whereIn('status_id', $allowedStatuses);

            if (!empty($validated['branch_id'])) {
                $baseQuery->where('branch_id', $validated['branch_id']);
            }

            if (!empty($validated['region_id'])) {
                $baseQuery->where('region_id', $validated['region_id']);
            }

            if (!empty($validated['source_id'])) {
                $baseQuery->where('source_id', $validated['source_id']);
            }

            if (!empty($validated['start_date'])) {
                $baseQuery->where(function ($q) use ($validated) {
                    $q->whereDate('published_at', '>=', $validated['start_date'])
                        ->orWhere(function ($q2) use ($validated) {
                            $q2->whereNull('published_at')
                                ->whereDate('created_at', '>=', $validated['start_date']);
                        });
                });
            }

            if (!empty($validated['end_date'])) {
                $baseQuery->where(function ($q) use ($validated) {
                    $q->whereDate('published_at', '<=', $validated['end_date'])
                        ->orWhere(function ($q2) use ($validated) {
                            $q2->whereNull('published_at')
                                ->whereDate('created_at', '<=', $validated['end_date']);
                        });
                });
            }

            $allLeadsQty = $baseQuery->count();

            $acquisitionQty = (clone $baseQuery)
                ->whereHas('claims', function ($q) {
                    $q->whereNull('deleted_at');
                })
                ->count();

            $acquisitionPercentage = $allLeadsQty > 0
                ? round(($acquisitionQty / $allLeadsQty) * 100, 2)
                : 0;

            $acquisitionTime = 0;
            try {
                $filteredLeadIds = (clone $baseQuery)
                    ->whereHas('claims', function ($q) {
                        $q->whereNull('deleted_at');
                    })
                    ->pluck('id');

                if ($filteredLeadIds->isNotEmpty()) {
                    $acquisitionTimeResult = DB::table('lead_claims')
                        ->join('leads', 'leads.id', '=', 'lead_claims.lead_id')
                        ->whereNull('lead_claims.deleted_at')
                        ->whereIn('lead_claims.lead_id', $filteredLeadIds)
                        ->selectRaw('
                            AVG(
                                CASE
                                    WHEN TIMESTAMPDIFF(HOUR,
                                        COALESCE(leads.published_at, leads.created_at),
                                        lead_claims.created_at
                                    ) >= 0
                                    THEN TIMESTAMPDIFF(HOUR,
                                        COALESCE(leads.published_at, leads.created_at),
                                        lead_claims.created_at
                                    )
                                    ELSE NULL
                                END
                            ) as avg_hours
                        ')
                        ->first();

                    $acquisitionTime = $acquisitionTimeResult && $acquisitionTimeResult->avg_hours !== null
                        ? round($acquisitionTimeResult->avg_hours, 2)
                        : 0;
                }
            } catch (\Exception $e) {
                $acquisitionTime = 0;
            }

            $availableLeads = (clone $baseQuery)
                ->whereDoesntHave('claims', function ($q) {
                    $q->whereNull('deleted_at');
                })
                ->count();

            $meetingQty = 0;
            $meetingPercentage = 0;
            $avgMeetingTime = 0;

            try {
                if (Schema::hasTable('lead_meetings')) {
                    $meetingQuery = DB::table('lead_meetings')
                        ->whereNull('deleted_at');

                    if (!empty($validated['start_date'])) {
                        $meetingQuery->whereDate('created_at', '>=', $validated['start_date']);
                    }
                    if (!empty($validated['end_date'])) {
                        $meetingQuery->whereDate('created_at', '<=', $validated['end_date']);
                    }

                    $meetingQty = $meetingQuery->count();

                    $meetingPercentage = $allLeadsQty > 0
                        ? round(($meetingQty / $allLeadsQty) * 100, 2)
                        : 0;

                    $meetingTimeQuery = DB::table('lead_meetings')
                        ->join('lead_claims', 'lead_meetings.lead_id', '=', 'lead_claims.lead_id')
                        ->whereNull('lead_meetings.deleted_at')
                        ->whereNull('lead_claims.deleted_at');

                    if (!empty($validated['start_date'])) {
                        $meetingTimeQuery->whereDate('lead_meetings.created_at', '>=', $validated['start_date']);
                    }
                    if (!empty($validated['end_date'])) {
                        $meetingTimeQuery->whereDate('lead_meetings.created_at', '<=', $validated['end_date']);
                    }

                    $meetingTimeResult = $meetingTimeQuery
                        ->selectRaw('
                        AVG(TIMESTAMPDIFF(HOUR,
                            lead_claims.created_at,
                            COALESCE(lead_meetings.updated_at, lead_meetings.scheduled_end_at)
                        )) as avg_hours
                    ')
                        ->first();

                    $avgMeetingTime = $meetingTimeResult ? round($meetingTimeResult->avg_hours, 2) : 0;
                }
            } catch (\Exception $e) {
                $meetingQty = 0;
                $meetingPercentage = 0;
                $avgMeetingTime = 0;
            }

            $myLeads = 0;
            try {
                $myLeads = (clone $baseQuery)
                    ->whereHas('claims', function ($q) use ($request) {
                        $q->whereNull('deleted_at')
                            ->where('sales_id', $request->user()->id);
                    })
                    ->count();
            } catch (\Exception $e) {
                $myLeads = 0;
            }

            $quotationQty = 0;
            $quotationAmount = 0;
            $quotationPercentage = 0;
            $avgQuotationTime = 0;

            try {
                if (Schema::hasTable('quotations')) {
                    $quotationQuery = DB::table('quotations')
                        ->whereNull('deleted_at');

                    if (!empty($validated['start_date'])) {
                        $quotationQuery->whereDate('created_at', '>=', $validated['start_date']);
                    }
                    if (!empty($validated['end_date'])) {
                        $quotationQuery->whereDate('created_at', '<=', $validated['end_date']);
                    }

                    $quotationQty = $quotationQuery->count();

                    $quotationAmountResult = $quotationQuery
                        ->selectRaw('COALESCE(SUM(grand_total), 0) as total_amount')
                        ->first();

                    $quotationAmount = $quotationAmountResult ? (float) $quotationAmountResult->total_amount : 0;

                    $quotationPercentage = $allLeadsQty > 0
                        ? round(($quotationQty / $allLeadsQty) * 100, 2)
                        : 0;

                    $quotationTimeQuery = DB::table('quotations')
                        ->join('leads', 'quotations.lead_id', '=', 'leads.id')
                        ->leftJoin('lead_meetings', function ($join) {
                            $join->on('leads.id', '=', 'lead_meetings.lead_id')
                                ->where('lead_meetings.result', 'yes')
                                ->whereNull('lead_meetings.deleted_at');
                        })
                        ->whereNull('quotations.deleted_at');

                    if (!empty($validated['start_date'])) {
                        $quotationTimeQuery->whereDate('quotations.created_at', '>=', $validated['start_date']);
                    }
                    if (!empty($validated['end_date'])) {
                        $quotationTimeQuery->whereDate('quotations.created_at', '<=', $validated['end_date']);
                    }

                    $quotationTimeResult = $quotationTimeQuery
                        ->selectRaw('
                        AVG(TIMESTAMPDIFF(HOUR,
                            COALESCE(lead_meetings.updated_at, lead_meetings.scheduled_end_at),
                            quotations.created_at
                        )) as avg_hours
                    ')
                        ->first();

                    $avgQuotationTime = $quotationTimeResult ? round($quotationTimeResult->avg_hours, 2) : 0;
                }
            } catch (\Exception $e) {
                $quotationQty = 0;
                $quotationAmount = 0;
                $quotationPercentage = 0;
                $avgQuotationTime = 0;
            }

            $invoiceQty = 0;
            $invoiceAmount = 0;
            $invoicePercentage = 0;
            $avgInvoiceTime = 0;

            try {
                if (Schema::hasTable('invoices')) {
                    $invoiceQuery = DB::table('invoices')
                        ->whereNull('deleted_at');

                    if (!empty($validated['start_date'])) {
                        $invoiceQuery->whereDate('issued_at', '>=', $validated['start_date'])
                            ->orWhere(function ($q) use ($validated) {
                                $q->whereNull('issued_at')
                                    ->whereDate('created_at', '>=', $validated['start_date']);
                            });
                    }
                    if (!empty($validated['end_date'])) {
                        $invoiceQuery->whereDate('issued_at', '<=', $validated['end_date'])
                            ->orWhere(function ($q) use ($validated) {
                                $q->whereNull('issued_at')
                                    ->whereDate('created_at', '<=', $validated['end_date']);
                            });
                    }

                    $invoiceQty = $invoiceQuery->count();

                    $invoiceAmountResult = $invoiceQuery
                        ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
                        ->first();

                    $invoiceAmount = $invoiceAmountResult ? (float) $invoiceAmountResult->total_amount : 0;

                    $invoicePercentage = $allLeadsQty > 0
                        ? round(($invoiceQty / $allLeadsQty) * 100, 2)
                        : 0;

                    $invoiceTimeQuery = DB::table('invoices')
                        ->join('proformas', 'invoices.proforma_id', '=', 'proformas.id')
                        ->join('quotations', 'proformas.quotation_id', '=', 'quotations.id')
                        ->join('leads', 'quotations.lead_id', '=', 'leads.id')
                        ->leftJoin('lead_meetings', function ($join) {
                            $join->on('leads.id', '=', 'lead_meetings.lead_id')
                                ->whereNull('lead_meetings.deleted_at');
                        })
                        ->whereNull('invoices.deleted_at')
                        ->whereNull('proformas.deleted_at')
                        ->whereNull('quotations.deleted_at');

                    if (!empty($validated['start_date'])) {
                        $invoiceTimeQuery->whereDate('invoices.issued_at', '>=', $validated['start_date'])
                            ->orWhere(function ($q) use ($validated) {
                                $q->whereNull('invoices.issued_at')
                                    ->whereDate('invoices.created_at', '>=', $validated['start_date']);
                            });
                    }
                    if (!empty($validated['end_date'])) {
                        $invoiceTimeQuery->whereDate('invoices.issued_at', '<=', $validated['end_date'])
                            ->orWhere(function ($q) use ($validated) {
                                $q->whereNull('invoices.issued_at')
                                    ->whereDate('invoices.created_at', '<=', $validated['end_date']);
                            });
                    }

                    $invoiceTimeResult = $invoiceTimeQuery
                        ->selectRaw('
                        AVG(TIMESTAMPDIFF(HOUR,
                            COALESCE(lead_meetings.created_at, quotations.created_at),
                            COALESCE(invoices.issued_at, invoices.created_at)
                        )) as avg_hours
                    ')
                        ->first();

                    $avgInvoiceTime = $invoiceTimeResult ? round($invoiceTimeResult->avg_hours, 2) : 0;
                }
            } catch (\Exception $e) {
                $invoiceQty = 0;
                $invoiceAmount = 0;
                $invoicePercentage = 0;
                $avgInvoiceTime = 0;
            }

            return response()->json([
                'aware' => [
                    'all_leads_qty' => $allLeadsQty,
                    'all_leads_percentage' => 100.00,
                    'acquisition_in_qty' => $acquisitionQty,
                    'acquisition_in_percentage' => $acquisitionPercentage,
                    'acquisition_time_avg_hours' => $acquisitionTime,
                ],
                'appeal' => [
                    'meeting_in_qty' => $meetingQty,
                    'meeting_in_percentage' => $meetingPercentage,
                    'meeting_time_avg_hours' => $avgMeetingTime,
                    'my_leads' => $myLeads
                ],
                'quotation' => [
                    'quotation_in_qty' => $quotationQty,
                    'quotation_in_amount' => $quotationAmount,
                    'quotation_in_percentage' => $quotationPercentage,
                    'quotation_time_avg_hours' => $avgQuotationTime
                ],
                'act' => [
                    'invoice_in_qty' => $invoiceQty,
                    'invoice_in_amount' => $invoiceAmount,
                    'invoice_in_percentage' => $invoicePercentage,
                    'invoice_time_avg_hours' => $avgInvoiceTime
                ],
                'filters' => [
                    'branch_id' => $validated['branch_id'] ?? null,
                    'start_date' => $validated['start_date'] ?? null,
                    'end_date' => $validated['end_date'] ?? null,
                    'status_id' => $validated['status_id'] ?? null,
                    'region_id' => $validated['region_id'] ?? null,
                    'source_id' => $validated['source_id'] ?? null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'aware' => [
                    'all_leads_qty' => 0,
                    'all_leads_percentage' => 0,
                    'acquisition_in_qty' => 0,
                    'acquisition_in_percentage' => 0,
                    'acquisition_time_avg_hours' => 0,
                ],
                'appeal' => [
                    'meeting_in_qty' => 0,
                    'meeting_in_percentage' => 0,
                    'meeting_time_avg_hours' => 0,
                    'my_leads' => 0
                ],
                'quotation' => [
                    'quotation_in_qty' => 0,
                    'quotation_in_amount' => 0,
                    'quotation_in_percentage' => 0,
                    'quotation_time_avg_hours' => 0
                ],
                'act' => [
                    'invoice_in_qty' => 0,
                    'invoice_in_amount' => 0,
                    'invoice_in_percentage' => 0,
                    'invoice_time_avg_hours' => 0
                ],
                'filters' => $validated,
                'error' => 'Terjadi kesalahan dalam memproses data: ' . $e->getMessage()
            ], 500);
        }
    }
    public function sourceConversion(Request $request)
    {
        $validated = $request->validate([
            'branch_id'  => 'nullable|integer',
            'source'     => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
        ]);

        $start = $validated['start_date'] ?? now()->startOfYear()->toDateString();
        $end   = $validated['end_date']   ?? now()->endOfMonth()->toDateString();

        try {
            $coldStatus = LeadStatus::COLD;
            $warmStatus = LeadStatus::WARM;
            $hotStatus = LeadStatus::HOT;
            $dealStatus = LeadStatus::DEAL;

            $query = Lead::query()
                ->join('lead_sources', 'leads.source_id', '=', 'lead_sources.id')
                ->select(
                    'lead_sources.name as source',
                    DB::raw("SUM(CASE WHEN leads.status_id = {$coldStatus} THEN 1 ELSE 0 END) as cold_count"),
                    DB::raw("SUM(CASE WHEN leads.status_id = {$warmStatus} THEN 1 ELSE 0 END) as warm_count"),
                    DB::raw("SUM(CASE WHEN leads.status_id = {$hotStatus} THEN 1 ELSE 0 END) as hot_count"),
                    DB::raw("SUM(CASE WHEN leads.status_id = {$dealStatus} THEN 1 ELSE 0 END) as deal_count")
                )
                ->whereBetween(DB::raw('DATE(COALESCE(leads.published_at, leads.created_at))'), [$start, $end])
                ->groupBy('lead_sources.id', 'lead_sources.name')
                ->orderBy('lead_sources.name');

            if (!empty($validated['branch_id'])) {
                $query->where('leads.branch_id', $validated['branch_id']);
            }

            if (!empty($validated['source'])) {
                $query->where('lead_sources.name', $validated['source']);
            }

            $data = $query->get();

            $totalCumulative = $data->sum(function ($item) {
                return $item->cold_count + $item->warm_count + $item->hot_count + $item->deal_count;
            });

            $formattedData = $data->map(function ($item) use ($totalCumulative) {
                $cold = (int) $item->cold_count;
                $warm = (int) $item->warm_count;
                $hot = (int) $item->hot_count;
                $deal = (int) $item->deal_count;

                $cumulative = $cold + $warm + $hot + $deal;

                $cumulativePercentage = $totalCumulative > 0 ? round(($cumulative / $totalCumulative) * 100, 2) : 0;
                $coldPercentage = $cumulative > 0 ? round(($cold / $cumulative) * 100, 2) : 0;
                $warmPercentage = $cumulative > 0 ? round(($warm / $cumulative) * 100, 2) : 0;
                $hotPercentage = $cumulative > 0 ? round(($hot / $cumulative) * 100, 2) : 0;
                $dealPercentage = $cumulative > 0 ? round(($deal / $cumulative) * 100, 2) : 0;

                return [
                    'source'      => $item->source,
                    'cumulative'  => $cumulative,
                    'cumulative_percentage' => $cumulativePercentage,
                    'cold'        => $cold,
                    'cold_percentage' => $coldPercentage,
                    'warm'        => $warm,
                    'warm_percentage' => $warmPercentage,
                    'hot'         => $hot,
                    'hot_percentage' => $hotPercentage,
                    'deal'        => $deal,
                    'deal_percentage' => $dealPercentage,
                ];
            });

            return response()->json([
                'data' => $formattedData,
                'summary' => [
                    'total_cumulative' => $totalCumulative,
                    'total_sources' => $data->count()
                ],
                'filters' => [
                    'start_date' => $start,
                    'end_date' => $end,
                    'branch_id' => $validated['branch_id'] ?? null,
                    'source' => $validated['source'] ?? null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan server',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function sourceMonthlyStats(Request $request)
    {
        try {
            $validated = $request->validate([
                'year' => 'nullable|integer|min:2000|max:2100',
                'branch_id' => 'nullable|integer',
                'source' => 'nullable|string',
            ]);

            $year = $validated['year'] ?? now()->year;
            $branchId = $validated['branch_id'] ?? null;

            $startDate = Carbon::create($year, 1, 1)->startOfDay();
            $endDate = Carbon::create($year, 12, 31)->endOfDay();

            $query = Lead::query()
                ->join('lead_sources', 'leads.source_id', '=', 'lead_sources.id')
                ->select(
                    'lead_sources.name as source',
                    DB::raw('MONTH(COALESCE(leads.published_at, leads.created_at)) as month'),
                    DB::raw('COUNT(leads.id) as lead_count')
                )
                ->whereBetween(DB::raw('DATE(COALESCE(leads.published_at, leads.created_at))'), [
                    $startDate->toDateString(),
                    $endDate->toDateString()
                ])
                ->groupBy('lead_sources.name', 'month')
                ->orderBy('lead_sources.name')
                ->orderBy('month');

            if (!empty($branchId)) {
                $query->where('leads.branch_id', $branchId);
            }

            if (!empty($validated['source'])) {
                $query->where('lead_sources.name', $validated['source']);
            }

            $monthlyData = $query->get();

            $sources = \App\Models\Leads\LeadSource::orderBy('name')->pluck('name');

            $result = $sources->map(function ($source) use ($monthlyData) {
                $monthlyCounts = array_fill(0, 12, 0);

                $sourceData = $monthlyData->where('source', $source);

                foreach ($sourceData as $data) {
                    $monthIndex = (int)$data->month - 1;
                    if ($monthIndex >= 0 && $monthIndex < 12) {
                        $monthlyCounts[$monthIndex] = (int)$data->lead_count;
                    }
                }

                return [
                    'source' => $source,
                    'months' => $monthlyCounts,
                    'total' => array_sum($monthlyCounts)
                ];
            })->filter(fn($item) => $item['total'] > 0)->values();

            $monthLabels = [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec'
            ];

            return response()->json([
                'data' => $result,
                'month_labels' => $monthLabels,
                'year' => $year,
                'filters' => [
                    'branch_id' => $branchId,
                    'source' => $validated['source'] ?? null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => $formattedData,
                'summary' => [
                    'total_percentage' => $totalPercentage,
                    'total_cumulative' => $totalCumulative,
                    'province_reached' => $provinceReached,
                    'total_provinces' => $data->count()
                ],
                'filters' => [
                    'year' => $year,
                    'month' => $month,
                    'month_name' => Carbon::create($year, $month, 1)->format('F'),
                    'start_date' => $start,
                    'end_date' => $end,
                    'branch_id' => $validated['branch_id'] ?? null,
                    'province' => $validated['province'] ?? null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan server',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function dealingList(Request $request)
    {
        $validated = $request->validate([
            'branch_id'  => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
            'user_id'    => 'nullable|integer',
        ]);

        $currentYear = now()->year;
        $start = $validated['start_date'] ?? Carbon::create($currentYear, 1, 1)->toDateString();
        $end = $validated['end_date'] ?? Carbon::create($currentYear, 12, 31)->toDateString();

        $statusId = LeadStatus::DEAL;

        try {
            $users = User::query()
                ->with(['branch'])
                ->where('role_id', 2)
                ->leftJoin('lead_claims as lc', 'lc.sales_id', '=', 'users.id')
                ->leftJoin('leads', function ($join) use ($statusId, $start, $end) {
                    $join->on('leads.id', '=', 'lc.lead_id')
                        ->where('leads.status_id', $statusId)
                        ->whereBetween(DB::raw('DATE(COALESCE(leads.published_at, leads.created_at))'), [$start, $end]);
                })
                ->leftJoin('quotations', function ($join) {
                    $join->on('quotations.lead_id', '=', 'leads.id')
                        ->whereNull('quotations.deleted_at');
                })
                ->leftJoin('quotation_items', function ($join) {
                    $join->on('quotation_items.quotation_id', '=', 'quotations.id')
                        ->whereNull('quotation_items.deleted_at');
                })
                ->leftJoin('proformas', function ($join) {
                    $join->on('proformas.quotation_id', '=', 'quotations.id')
                        ->whereNull('proformas.deleted_at');
                })
                ->leftJoin('invoices', function ($join) {
                    $join->on('invoices.proforma_id', '=', 'proformas.id')
                        ->whereNull('invoices.deleted_at');
                })
                ->when(!empty($validated['branch_id']), function ($q) use ($validated) {
                    $q->where('users.branch_id', $validated['branch_id']);
                })
                ->when(!empty($validated['user_id']), function ($q) use ($validated) {
                    $q->where('users.id', $validated['user_id']);
                })
                ->select([
                    'users.id',
                    'users.name',
                    'users.target',
                    'users.branch_id',
                    DB::raw('COUNT(DISTINCT leads.id) as total_leads'),
                    DB::raw('COUNT(DISTINCT invoices.id) as total_orders'),
                    DB::raw('COALESCE(SUM(invoices.amount), 0) as achievement_amount'),
                    DB::raw('COALESCE(SUM(quotation_items.qty), 0) as total_unit_sales')
                ])
                ->groupBy('users.id', 'users.name', 'users.target', 'users.branch_id')
                ->orderBy('users.name')
                ->get();

            $results = $users->map(function ($user) use ($start, $end, $currentYear) {
                $targetAmount = (float) ($user->target ?? 0);
                $achievementAmount = (float) ($user->achievement_amount ?? 0);
                $achievementPercentage = $targetAmount > 0 ? round(($achievementAmount / $targetAmount) * 100, 2) : 0;

                return [
                    'sales_id' => $user->id,
                    'nama_sales' => $user->name ?? '-',
                    'target_amount' => $targetAmount,
                    'achievement_amount' => $achievementAmount,
                    'achievement_percentage' => $achievementPercentage,
                    'unit_sales' => (int) ($user->total_unit_sales ?? 0),
                    'branch' => $user->branch->name ?? '-',
                    'total_leads' => (int) ($user->total_leads ?? 0),
                    'periode' => "{$start} s/d {$end}",
                    'tahun' => $currentYear
                ];
            });

            $uniqueResults = $results->unique('sales_id')->values();

            $monthlyData = $this->getMonthlyData($validated, $start, $end, $statusId);

            return response()->json([
                'success' => true,
                'data' => $uniqueResults,
                'monthly_data' => $monthlyData,
                'periode' => [
                    'start_date' => $start,
                    'end_date' => $end,
                    'status_id' => $statusId,
                    'tahun' => $currentYear
                ],
                'summary' => [
                    'total_sales' => $uniqueResults->count(),
                    'total_achievement' => $uniqueResults->sum('achievement_amount'),
                    'total_target' => $uniqueResults->sum('target_amount'),
                    'average_achievement_percentage' => $uniqueResults->avg('achievement_percentage') ?: 0,
                    'total_unit_sales' => $uniqueResults->sum('unit_sales')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getMonthlyData($filters, $start, $end, $statusId)
    {
        $monthlyQuery = User::query()
            ->where('role_id', 2)
            ->leftJoin('lead_claims as lc', 'lc.sales_id', '=', 'users.id')
            ->leftJoin('leads', function ($join) use ($statusId, $start, $end) {
                $join->on('leads.id', '=', 'lc.lead_id')
                    ->where('leads.status_id', $statusId)
                    ->whereBetween(DB::raw('DATE(COALESCE(leads.published_at, leads.created_at))'), [$start, $end]);
            })
            ->leftJoin('quotations', function ($join) {
                $join->on('quotations.lead_id', '=', 'leads.id')
                    ->whereNull('quotations.deleted_at');
            })
            ->leftJoin('quotation_items', function ($join) {
                $join->on('quotation_items.quotation_id', '=', 'quotations.id')
                    ->whereNull('quotation_items.deleted_at');
            })
            ->leftJoin('proformas', function ($join) {
                $join->on('proformas.quotation_id', '=', 'quotations.id')
                    ->whereNull('proformas.deleted_at');
            })
            ->leftJoin('invoices', function ($join) {
                $join->on('invoices.proforma_id', '=', 'proformas.id')
                    ->whereNull('invoices.deleted_at');
            })
            ->when(!empty($filters['branch_id']), function ($q) use ($filters) {
                $q->where('users.branch_id', $filters['branch_id']);
            })
            ->when(!empty($filters['user_id']), function ($q) use ($filters) {
                $q->where('users.id', $filters['user_id']);
            })
            ->select([
                'users.id as sales_id',
                'users.name as sales_name',
                'users.target as target_amount',
                DB::raw('YEAR(COALESCE(leads.published_at, leads.created_at)) as year'),
                DB::raw('MONTH(COALESCE(leads.published_at, leads.created_at)) as month'),
                DB::raw('CONCAT(YEAR(COALESCE(leads.published_at, leads.created_at)), "-", LPAD(MONTH(COALESCE(leads.published_at, leads.created_at)), 2, "0")) as period'),
                DB::raw('COALESCE(SUM(invoices.amount), 0) as achievement_amount'),
                DB::raw('COALESCE(SUM(quotation_items.qty), 0) as unit_sales'),
                DB::raw('COUNT(DISTINCT leads.id) as total_leads'),
                DB::raw('COUNT(DISTINCT invoices.id) as total_orders')
            ])
            ->whereNotNull('leads.id')
            ->groupBy('users.id', 'users.name', 'users.target', 'year', 'month', 'period')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('users.name')
            ->get();

        $allMonths = $this->generateAllMonths($start, $end);

        $activeSales = User::where('role_id', 2)
            ->when(!empty($filters['branch_id']), function ($q) use ($filters) {
                $q->where('branch_id', $filters['branch_id']);
            })
            ->when(!empty($filters['user_id']), function ($q) use ($filters) {
                $q->where('id', $filters['user_id']);
            })
            ->select(['id as sales_id', 'name as sales_name', 'target as target_amount'])
            ->get();

        $monthlyData = collect($allMonths)->map(function ($month) use ($monthlyQuery, $activeSales) {
            $monthSales = $monthlyQuery->where('period', $month['period']);

            $sales_data = $activeSales->map(function ($sales) use ($monthSales, $month) {
                $salesMonthData = $monthSales->where('sales_id', $sales->sales_id)->first();
                $achievementAmount = $salesMonthData ? (float) $salesMonthData->achievement_amount : 0;
                $targetAmount = (float) $sales->target_amount;
                $achievementPercentage = $targetAmount > 0 ? round(($achievementAmount / $targetAmount) * 100, 2) : 0;

                return [
                    'sales_id' => $sales->sales_id,
                    'sales_name' => $sales->sales_name,
                    'target_amount' => $targetAmount,
                    'achievement_amount' => $achievementAmount,
                    'achievement_percentage' => $achievementPercentage,
                    'unit_sales' => $salesMonthData ? (int) $salesMonthData->unit_sales : 0,
                    'total_leads' => $salesMonthData ? (int) $salesMonthData->total_leads : 0,
                    'total_orders' => $salesMonthData ? (int) $salesMonthData->total_orders : 0,
                ];
            });

            $totalAchievement = $sales_data->sum('achievement_amount');
            $totalUnitSales = $sales_data->sum('unit_sales');
            $totalLeads = $sales_data->sum('total_leads');
            $totalOrders = $sales_data->sum('total_orders');

            return [
                'period' => $month['period'],
                'month_name' => $month['month_name'],
                'year' => $month['year'],
                'month' => $month['month'],
                'total_achievement_amount' => $totalAchievement,
                'total_unit_sales' => $totalUnitSales,
                'total_leads' => $totalLeads,
                'total_orders' => $totalOrders,
                'sales_data' => $sales_data
            ];
        });

        return $monthlyData;
    }

    private function generateAllMonths($start, $end)
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);
        $months = [];

        $current = $start->copy()->startOfMonth();

        while ($current <= $end) {
            $months[] = [
                'period' => $current->format('Y-m'),
                'month_name' => $current->translatedFormat('F Y'),
                'year' => $current->year,
                'month' => $current->month,
            ];
            $current->addMonth();
        }

        return $months;
    }
    public function warmHotList(Request $request)
    {
        $validated = $request->validate([
            'branch_id'  => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
            'user_id'    => 'nullable|integer',
        ]);

        $currentYear = now()->year;
        $start = $validated['start_date'] ?? Carbon::create($currentYear, 1, 1)->toDateString();
        $end = $validated['end_date'] ?? Carbon::create($currentYear, 12, 31)->toDateString();

        $coldStatusId = LeadStatus::COLD;
        $warmStatusId = LeadStatus::WARM;
        $hotStatusId = LeadStatus::HOT;

        try {
            $users = User::query()
                ->with(['branch'])
                ->where('role_id', 2)
                ->leftJoin('lead_claims as lc', function ($join) {
                    $join->on('lc.sales_id', '=', 'users.id')
                        ->whereNull('lc.deleted_at')
                        ->whereNull('lc.released_at');
                })
                ->leftJoin('leads', function ($join) use ($coldStatusId, $warmStatusId, $hotStatusId, $start, $end) {
                    $join->on('leads.id', '=', 'lc.lead_id')
                        ->whereIn('leads.status_id', [$coldStatusId, $warmStatusId, $hotStatusId])
                        ->where(function ($query) use ($start, $end) {
                            $query->whereBetween(DB::raw('DATE(COALESCE(leads.published_at, leads.created_at))'), [$start, $end])
                                ->orWhere(function ($q) use ($start, $end) {
                                    $q->where(DB::raw('DATE(COALESCE(leads.published_at, leads.created_at))'), '<=', $end)
                                        ->whereRaw('DATE_ADD(DATE(COALESCE(leads.published_at, leads.created_at)), INTERVAL 30 DAY) >= ?', [$start]);
                                });
                        });
                })
                ->leftJoin('quotations', function ($join) use ($start, $end) {
                    $join->on('quotations.lead_id', '=', 'leads.id')
                        ->where('quotations.status', 'published')
                        ->whereNull('quotations.deleted_at')
                        ->where(function ($query) use ($start, $end) {
                            $query->whereBetween('quotations.created_at', [$start, $end])
                                ->orWhere(function ($q) use ($start, $end) {
                                    $q->where('quotations.created_at', '<=', $end)
                                        ->whereRaw('DATE_ADD(quotations.created_at, INTERVAL 30 DAY) >= ?', [$start]);
                                });
                        });
                })
                ->leftJoin('quotation_items', function ($join) {
                    $join->on('quotation_items.quotation_id', '=', 'quotations.id')
                        ->whereNull('quotation_items.deleted_at');
                })
                ->when(!empty($validated['branch_id']), function ($q) use ($validated) {
                    $q->where('users.branch_id', $validated['branch_id']);
                })
                ->when(!empty($validated['user_id']), function ($q) use ($validated) {
                    $q->where('users.id', $validated['user_id']);
                })
                ->select([
                    'users.id',
                    'users.name',
                    'users.branch_id',
                    DB::raw('COUNT(DISTINCT leads.id) as total_leads'),
                    DB::raw('SUM(CASE WHEN leads.status_id = ' . $coldStatusId . ' THEN 1 ELSE 0 END) as cold_count'),
                    DB::raw('SUM(CASE WHEN leads.status_id = ' . $warmStatusId . ' THEN 1 ELSE 0 END) as warm_count'),
                    DB::raw('SUM(CASE WHEN leads.status_id = ' . $hotStatusId . ' THEN 1 ELSE 0 END) as hot_count'),
                    DB::raw('COALESCE(SUM(quotations.subtotal), 0) as subtotal_amount'),
                    DB::raw('COALESCE(SUM(quotations.grand_total), 0) as grand_total_amount'),
                    DB::raw('COALESCE(SUM(quotations.tax_total), 0) as tax_total_amount'),
                    DB::raw('COALESCE(SUM(quotation_items.qty), 0) as total_qty'),
                    DB::raw('COALESCE(AVG(quotation_items.discount_pct), 0) as avg_item_discount'),
                    DB::raw('COALESCE(SUM(quotation_items.discount_pct * quotation_items.qty * quotation_items.unit_price / 100), 0) as total_item_discount_amount'),
                    DB::raw('MIN(quotations.created_at) as first_quotation_date'),
                    DB::raw('MAX(quotations.created_at) as last_quotation_date')
                ])
                ->groupBy('users.id', 'users.name', 'users.branch_id')
                ->orderBy('users.name')
                ->get();

            $results = $users->map(function ($user) use ($start, $end, $currentYear) {
                $coldCount = (int) ($user->cold_count ?? 0);
                $warmCount = (int) ($user->warm_count ?? 0);
                $hotCount = (int) ($user->hot_count ?? 0);
                $totalLeads = $warmCount + $hotCount;

                $subtotalAmount = (float) ($user->subtotal_amount ?? 0);
                $grandTotalAmount = (float) ($user->grand_total_amount ?? 0);
                $taxTotalAmount = (float) ($user->tax_total_amount ?? 0);

                $discountAmount = 0;
                $avgDiscount = 0;

                $documentDiscountAmount = $subtotalAmount - ($grandTotalAmount - $taxTotalAmount);

                $itemDiscountAmount = (float) ($user->total_item_discount_amount ?? 0);

                $avgItemDiscount = (float) ($user->avg_item_discount ?? 0);

                if ($documentDiscountAmount > 0) {
                    $discountAmount = $documentDiscountAmount;
                    $avgDiscount = $subtotalAmount > 0 ? round(($discountAmount / $subtotalAmount) * 100, 2) : 0;
                } elseif ($itemDiscountAmount > 0) {
                    $discountAmount = $itemDiscountAmount;
                    $avgDiscount = $subtotalAmount > 0 ? round(($discountAmount / $subtotalAmount) * 100, 2) : 0;
                } else {
                    $discountAmount = $subtotalAmount * ($avgItemDiscount / 100);
                    $avgDiscount = $avgItemDiscount;
                }

                if ($discountAmount < 0) {
                    $discountAmount = 0;
                }
                if ($avgDiscount < 0) {
                    $avgDiscount = 0;
                }
                if ($avgDiscount > 100) {
                    $avgDiscount = 100;
                }

                $firstQuotationDate = $user->first_quotation_date ? Carbon::parse($user->first_quotation_date) : null;
                $lastQuotationDate = $user->last_quotation_date ? Carbon::parse($user->last_quotation_date) : null;

                $validityInfo = "Data termasuk lead dengan validity period 30 hari dalam range {$start} hingga {$end}";

                if ($firstQuotationDate && $lastQuotationDate) {
                    $earliestValidityEnd = $firstQuotationDate->copy()->addDays(30)->format('Y-m-d');
                    $latestValidityEnd = $lastQuotationDate->copy()->addDays(30)->format('Y-m-d');
                    $validityInfo .= " (Quotation: {$firstQuotationDate->format('Y-m-d')} hingga {$lastQuotationDate->format('Y-m-d')})";
                }

                return [
                    'sales_id' => $user->id,
                    'nama_sales' => $user->name ?? '-',
                    'cold_count' => $coldCount,
                    'warm_hot_amount' => $grandTotalAmount,
                    'warm_hot_qty' => $totalLeads,
                    'warm_count' => $warmCount,
                    'hot_count' => $hotCount,
                    'avg_discount' => $avgDiscount,
                    'subtotal_amount' => $subtotalAmount,
                    'discount_amount' => $discountAmount,
                    'tax_total_amount' => $taxTotalAmount,
                    'net_amount' => $grandTotalAmount - $taxTotalAmount,
                    'discount_calculation_method' => $documentDiscountAmount > 0 ? 'document_level' : ($itemDiscountAmount > 0 ? 'item_level_amount' : 'item_level_percentage'),
                    'branch' => $user->branch->name ?? '-',
                    'periode' => "{$start} s/d {$end}",
                    'tahun' => $currentYear,
                    'validity_period_info' => $validityInfo,
                    'validity_period_detail' => [
                        'filter_start' => $start,
                        'filter_end' => $end,
                        'first_quotation_date' => $firstQuotationDate?->format('Y-m-d'),
                        'last_quotation_date' => $lastQuotationDate?->format('Y-m-d'),
                        'earliest_validity_end' => $firstQuotationDate?->copy()->addDays(30)->format('Y-m-d'),
                        'latest_validity_end' => $lastQuotationDate?->copy()->addDays(30)->format('Y-m-d')
                    ]
                ];
            });

            $filteredResults = $results->filter(function ($item) {
                return $item['warm_hot_qty'] > 0 && $item['warm_hot_amount'] > 0;
            })->values();

            $uniqueResults = $filteredResults->unique('sales_id')->values();

            return response()->json([
                'success' => true,
                'data' => $uniqueResults,
                'periode' => [
                    'start_date' => $start,
                    'end_date' => $end,
                    'cold_status_id' => $coldStatusId,
                    'warm_status_id' => $warmStatusId,
                    'hot_status_id' => $hotStatusId,
                    'tahun' => $currentYear,
                    'business_rules' => [
                        'validity_period_days' => 30,
                        'description' => 'Data mencakup lead Warm/Hot dengan quotation published yang memiliki validity period 30 hari overlap dengan filter tanggal',
                        'inclusion_criteria' => [
                            'Lead status: Cold (' . $coldStatusId . '), Warm (' . $warmStatusId . ') atau Hot (' . $hotStatusId . ')',
                            'Quotation status: Published',
                            'Validity period: Quotation date + 30 hari overlap dengan filter range',
                            'Sales role: User dengan role_id = 2',
                            'Lead claim: Aktif (tidak deleted dan tidak released)'
                        ],
                        'filter_applied' => [
                            'date_range' => "{$start} to {$end}",
                            'validity_overlap' => 'true',
                            'exclude_empty_quotations' => 'true'
                        ]
                    ]
                ],
                'summary' => [
                    'total_sales' => $uniqueResults->count(),
                    'total_cold_count' => $uniqueResults->sum('cold_count'),
                    'total_warm_hot_amount' => $uniqueResults->sum('warm_hot_amount'),
                    'total_warm_hot_qty' => $uniqueResults->sum('warm_hot_qty'),
                    'total_warm_count' => $uniqueResults->sum('warm_count'),
                    'total_hot_count' => $uniqueResults->sum('hot_count'),
                    'average_discount' => $uniqueResults->avg('avg_discount') ?: 0,
                    'total_discount_amount' => $uniqueResults->sum('discount_amount'),
                    'total_tax_amount' => $uniqueResults->sum('tax_total_amount'),
                    'data_quality_note' => 'Menampilkan sales dengan lead Cold/Warm/Hot yang memiliki quotation published dalam validity period. Cold count tersedia untuk semua sales.'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data Cold + Warm + Hot',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function potentialDealing(Request $request)
    {
        $validated = $request->validate([
            'branch_id'  => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
            'user_id'    => 'nullable|integer',
        ]);

        $end = $validated['end_date'] ?? now()->toDateString();
        $start = $validated['start_date'] ?? now()->subDays(30)->toDateString();

        $warmStatusId = LeadStatus::WARM;
        $hotStatusId = LeadStatus::HOT;

        try {
            $latestQuotationSubquery = DB::table('quotations')
                ->select('lead_id', DB::raw('MAX(created_at) as latest_date'))
                ->where('status', 'published')
                ->whereNull('deleted_at')
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('created_at', [$start, $end])
                        ->orWhere(function ($q) use ($start, $end) {
                            $q->where('created_at', '<=', $end)
                                ->whereRaw('DATE_ADD(created_at, INTERVAL 30 DAY) >= ?', [$start]);
                        });
                })
                ->groupBy('lead_id');

            $leads = Lead::query()
                ->with([
                    'region',
                    'product',
                    'status',
                    'claims',
                    'industry'
                ])
                ->join('quotations', function ($join) use ($start, $end) {
                    $join->on('quotations.lead_id', '=', 'leads.id')
                        ->where('quotations.status', 'published')
                        ->whereNull('quotations.deleted_at')
                        ->where(function ($query) use ($start, $end) {
                            $query->whereBetween('quotations.created_at', [$start, $end])
                                ->orWhere(function ($q) use ($start, $end) {
                                    $q->where('quotations.created_at', '<=', $end)
                                        ->whereRaw('DATE_ADD(quotations.created_at, INTERVAL 30 DAY) >= ?', [$start]);
                                });
                        });
                })
                ->joinSub($latestQuotationSubquery, 'latest_quo', function ($join) {
                    $join->on('quotations.lead_id', '=', 'latest_quo.lead_id')
                        ->on('quotations.created_at', '=', 'latest_quo.latest_date');
                })
                ->leftJoin('lead_claims', function ($join) {
                    $join->on('lead_claims.lead_id', '=', 'leads.id')
                        ->whereNull('lead_claims.deleted_at')
                        ->whereNull('lead_claims.released_at');
                })
                ->leftJoin('users', 'users.id', '=', 'lead_claims.sales_id')
                ->whereIn('leads.status_id', [$warmStatusId, $hotStatusId])
                ->when(!empty($validated['branch_id']), function ($q) use ($validated) {
                    $q->where('leads.branch_id', $validated['branch_id']);
                })
                ->when(!empty($validated['user_id']), function ($q) use ($validated) {
                    $q->where('lead_claims.sales_id', $validated['user_id']);
                })
                ->select([
                    'leads.id',
                    'leads.name as customer_name',
                    'leads.company',
                    'leads.status_id',
                    'leads.region_id',
                    'leads.product_id',
                    'leads.published_at',
                    'leads.updated_at',
                    'leads.phone',
                    'leads.email',
                    'leads.contact_reason',
                    'leads.business_reason',
                    'leads.industry_id',
                    'leads.other_industry',
                    'quotations.quotation_no',
                    'quotations.grand_total',
                    'quotations.created_at as quotation_created_at',
                    'users.name as sales_name',
                    'users.id as sales_id'
                ])
                ->distinct()
                ->get()
                ->map(function ($lead) {
                    $quotationDate = Carbon::parse($lead->quotation_created_at);
                    $validityStart = $quotationDate->format('Y-m-d');
                    $validityEnd = $quotationDate->copy()->addDays(30)->format('Y-m-d');

                    $validationStatus = $this->checkDataValidation($lead);

                    $industryName = $lead->industry->name ?? '-';
                    $otherIndustry = $lead->other_industry ?? null;

                    $fullIndustry = $industryName;
                    if ($otherIndustry) {
                        $fullIndustry = $industryName . ' - ' . $otherIndustry;
                    }

                    return [
                        'customer_name' => $lead->customer_name ?? $lead->company ?? 'N/A',
                        'company' => $lead->company ?? '-',
                        'status' => $lead->status->name ?? '-',
                        'status_id' => $lead->status_id,
                        'amount' => (float) ($lead->grand_total ?? 0),
                        'regional' => $lead->region->name ?? '-',
                        'product' => $lead->product->name ?? '-',
                        'industry' => $fullIndustry,
                        'industry_detail' => [
                            'industry' => $industryName,
                            'other_industry' => $otherIndustry
                        ],
                        'last_activity' => $lead->updated_at->format('Y-m-d H:i'),
                        'last_activity_date' => $lead->updated_at->format('Y-m-d'),
                        'data_validation' => $validationStatus,
                        'sales_name' => $lead->sales_name ?? '-',
                        'sales_id' => $lead->sales_id ?? null,
                        'quotation_no' => $lead->quotation_no ?? '-',
                        'quotation_date' => $quotationDate->format('Y-m-d'),
                        'validity_period' => [
                            'start' => $validityStart,
                            'end' => $validityEnd,
                            'is_active' => now()->between($validityStart, $validityEnd)
                        ],
                        'contact_info' => [
                            'phone' => $lead->phone,
                            'email' => $lead->email
                        ],
                        'business_reason' => $lead->business_reason,
                        'contact_reason' => $lead->contact_reason
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $leads,
                'filters' => [
                    'start_date' => $start,
                    'end_date' => $end,
                    'branch_id' => $validated['branch_id'] ?? null,
                    'user_id' => $validated['user_id'] ?? null,
                    'status_ids' => [$warmStatusId, $hotStatusId]
                ],
                'summary' => [
                    'total_potential' => $leads->count(),
                    'total_amount' => $leads->sum('amount'),
                    'by_status' => $leads->groupBy('status')->map->count(),
                    'by_regional' => $leads->groupBy('regional')->map->count(),
                    'by_industry' => $leads->groupBy('industry')->map->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data Potential Dealing',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    private function checkDataValidation($lead)
    {
        $validationChecks = [
            'contact_info' => !empty($lead->phone) || !empty($lead->email),
            'business_reason' => !empty($lead->business_reason),
            'quotation_exists' => !empty($lead->quotation_no),
            'quotation_amount' => !empty($lead->grand_total) && $lead->grand_total > 0,
            'regional_info' => !empty($lead->region_id),
            'product_info' => !empty($lead->product_id)
        ];

        $passedChecks = count(array_filter($validationChecks));
        $totalChecks = count($validationChecks);
        $score = round(($passedChecks / $totalChecks) * 100, 2);

        if ($score >= 80) return 'complete';
        if ($score >= 60) return 'moderate';
        if ($score >= 40) return 'basic';
        return 'incomplete';
    }
}