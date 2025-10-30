<?php

namespace App\Http\Controllers;

use App\Models\Orders\Quotation;
use App\Models\Leads\{Lead, LeadStatus};
use App\Models\Masters\Branch;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\User; // tambahkan di atas
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{

    private function claimUserColumn(): string
    {
        // urutan fallback – sesuaikan dengan skema kamu
        if (Schema::hasColumn('lead_claims', 'user_id'))   return 'user_id';
        if (Schema::hasColumn('lead_claims', 'sales_id'))  return 'sales_id';
        if (Schema::hasColumn('lead_claims', 'claimed_by')) return 'claimed_by';

        throw new \RuntimeException('Tabel lead_claims butuh kolom user_id/sales_id/claimed_by.');
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $showOrders = $user->hasPermission('orders');

        // SELALU siapkan $salesUsers
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
        $defaultStart = now()->startOfMonth()->format('Y-m-d');
        $defaultEnd   = now()->endOfMonth()->format('Y-m-d');

        $defaultYtdStart = now()->startOfYear()->format('Y-m-d');
        $defaultYtdEnd   = now()->endOfMonth()->format('Y-m-d');

        return view('pages.dashboard.index', [
            'showOrders'           => $showOrders,
            'quotationStatusStats' => $quotationStatusStats,
            'branches'             => $branches,
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

        // === PETA NAMA BRANCH YANG DIPAKAI DI DB ===
        $BR_JKT = 'Branch Jakarta';
        $BR_MKS = 'Branch Makassar';
        $BR_SBY = 'Branch Surabaya';

        // === TARGET BULANAN PER BRANCH (DUMMY, 12 BULAN, BERBEDA-BEDA) ===
        // Catatan: total per-branch = penjumlahan 12 bulan
        // Target bulanan per branch (Jan..Des)
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

        // === TARGET BULANAN PER BRANCH (Jan..Des) ===
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

        // Urutan JKT → SBY → MKS
        $scopeMap = [
            'global'   => [$BR_JKT, $BR_SBY, $BR_MKS],
            'jakarta'  => [$BR_JKT],
            'surabaya' => [$BR_SBY],
            'makassar' => [$BR_MKS],
        ];

        $wantedBranches = $scopeMap[$scope] ?? $scopeMap['global'];

        // === Query Sales (sum total_billing per bulan) ===
        $start = \Carbon\Carbon::create($year, 1, 1)->toDateString();
        $end   = \Carbon\Carbon::create($year, 12, 31)->toDateString();

        $base = \App\Models\Orders\Order::query()
            ->join('leads', 'orders.lead_id', '=', 'leads.id')
            ->leftJoin('ref_regions', 'leads.region_id', '=', 'ref_regions.id')
            ->leftJoin('ref_branches', 'ref_regions.branch_id', '=', 'ref_branches.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$start, $end])
            ->whereIn('ref_branches.name', $wantedBranches);

        // Role guard (konsisten dgn endpoint lain)
        $roleCode = auth()->user()->role?->code;
        if (in_array($roleCode, ['sales', 'branch_manager'])) {
            $base->where('ref_branches.id', auth()->user()->branch_id);
        }

        // Ambil sales per bulan (sum total_billing)
        $rows = (clone $base)
            ->selectRaw('YEAR(orders.created_at) as y, MONTH(orders.created_at) as m, COALESCE(SUM(orders.total_billing),0) as amt')
            ->groupBy('y', 'm')
            ->orderBy('y')->orderBy('m')
            ->get();

        // Label bulan (Jan..Dec)
        $labels = [];
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = \Carbon\Carbon::create($year, $i, 1)->format('M');
        }

        // Sales bulanan (isi 0 dulu)
        $salesMonthly = array_fill(0, 12, 0.0);
        foreach ($rows as $r) {
            $idx = max(0, min(11, (int)$r->m - 1));
            $salesMonthly[$idx] = (float) $r->amt;
        }

        // Jika role guard aktif, pastikan hanya target branch yg memang terlihat oleh user
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

        // Target bulanan sesuai scope (global = penjumlahan cabang yang dipilih)
        $targetMonthly = array_map('floatval', $globalMonthlyTarget);
        foreach ($wantedBranches as $bn) {
            $t = $monthlyTargets[$bn] ?? array_fill(0, 12, 0.0);
            for ($i = 0; $i < 12; $i++) {
                $targetMonthly[$i] += (float) $t[$i];
            }
        }
        // === Target bulanan (scope) + All Branch Target ===
        // (A) hitung All Branch Target = JKT + SBY + MKS (tetap, tidak terpengaruh scope/role)
        $allBranchTarget = array_fill(0, 12, 0.0);
        foreach ([$BR_JKT, $BR_SBY, $BR_MKS] as $bn) {
            $t = $monthlyTargets[$bn] ?? array_fill(0, 12, 0.0);
            for ($i = 0; $i < 12; $i++) {
                $allBranchTarget[$i] += (float) $t[$i];
            }
        }

        // (B) targetMonthly sesuai scope = penjumlahan target cabang yang dipilih (TIDAK start dari globalMonthlyTarget)
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
                ['label' => 'Target',             'data' => $targetMonthly],
                ['label' => 'Sales',              'data' => $salesMonthly],
                ['label' => 'All Branch Target',  'data' => $allBranchTarget], // ⬅️ baru
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

        // pakai string SQL agar bisa dipakai di selectRaw + bindings (hindari convert Expression ke string)
        $dateLeadSql = "DATE(COALESCE(leads.published_at, leads.created_at))";
        $claimCol    = $this->claimUserColumn(); // user_id / sales_id / claimed_by

        // MULAI dari users TANPA alias agar whereHas('role') aman
        $users = \App\Models\User::query()
            ->whereHas('role', fn($q) => $q->where('code', 'sales'));

        // Role guard
        $roleCode = auth()->user()->role?->code;
        if ($roleCode === 'sales') {
            $users->where('users.id', auth()->id());
        } elseif ($roleCode === 'branch_manager') {
            $users->where('users.branch_id', auth()->user()->branch_id);
        }

        // Filter branch dari request
        if (!empty($validated['branch_id'])) {
            $users->where('users.branch_id', $validated['branch_id']);
        }

        // Join klaim -> lead -> orders
        $users->leftJoin('lead_claims as lc', function ($j) use ($claimCol) {
            $j->on("lc.$claimCol", '=', 'users.id')
                ->whereNull('lc.deleted_at');
        })
            ->leftJoin('leads', 'leads.id', '=', 'lc.lead_id')
            ->leftJoin('orders', function ($j) use ($start, $end) {
                $j->on('orders.lead_id', '=', 'leads.id')
                    ->whereBetween(DB::raw('DATE(orders.created_at)'), [$start, $end]);
            });

        // Select agregasi dengan bindings (tidak menggabungkan Expression ke string)
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

        // FIX: global annual plan (tetap, tidak tergantung range bulan)
        $GLOBAL_ANNUAL_PLAN = 183_150_000_000;

        $start = $validated['start_date'] ?? now()->startOfYear()->toDateString();
        $end   = $validated['end_date']   ?? now()->endOfMonth()->toDateString();

        // Target tahunan per-branch (TOTAL setahun) — sama dengan penjumlahan target bulanan masing2
        $defaultTarget = 10_000_000;
        $targetMapByName = [
            'Branch Jakarta'  => 61_813_125_000,
            'Branch Surabaya' => 48_317_850_000,
            'Branch Makassar' => 27_610_200_000,
        ];

        // === ACHIEVED ===
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
            ->select('ref_branches.id', 'ref_branches.name')
            ->selectRaw('COALESCE(SUM(orders.total_billing),0) as achieved')
            ->groupBy('ref_branches.id', 'ref_branches.name')
            ->get();

        $branches = $branchRows->map(function ($r) use ($targetMapByName, $defaultTarget) {
            $ach = (float) $r->achieved;
            $tgt = (float) ($targetMapByName[$r->name] ?? $defaultTarget);
            return [
                'id'       => (int) $r->id,
                'label'    => $r->name,
                'achieved' => $ach,
                'target'   => $tgt,
                'percent'  => $tgt > 0 ? round(($ach / $tgt) * 100, 2) : 0,
            ];
        })->sortBy(fn($b) => $rank[$b['label']] ?? 999)->values();

        // === PLAN ===
        // Global (fixed)
        $globalTargetFixed = (float) $GLOBAL_ANNUAL_PLAN;

        // All Branch Target (Plan) = total dari semua branch (bukan dari array global)
        $allBranchPlan = array_sum($targetMapByName); // 61.813.125.000 + 48.317.850.000 + 27.610.200.000 = 137.741.175.000

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

        // Nama branch & urutan
        $BR_JKT = 'Branch Jakarta';
        $BR_SBY = 'Branch Surabaya';
        $BR_MKS = 'Branch Makassar';
        $desiredOrder = [$BR_JKT, $BR_SBY, $BR_MKS];

        // Target bulanan per branch (Jan..Des)
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

        // Role guard: batasi sesuai cabang user
        $base = \App\Models\Orders\Order::query()
            ->join('leads', 'orders.lead_id', '=', 'leads.id')
            ->leftJoin('ref_regions', 'leads.region_id', '=', 'ref_regions.id')
            ->leftJoin('ref_branches', 'ref_regions.branch_id', '=', 'ref_branches.id')
            ->whereYear('orders.created_at', $year);

        $roleCode = auth()->user()->role?->code;
        if (in_array($roleCode, ['sales', 'branch_manager'])) {
            $base->where('ref_branches.id', auth()->user()->branch_id);
        }

        // Ambil achieved per branch per bulan
        $rows = (clone $base)
            ->select('ref_branches.name as bname')
            ->selectRaw('MONTH(orders.created_at) as m')
            ->selectRaw('COALESCE(SUM(orders.total_billing),0) as achieved')
            ->groupBy('bname', 'm')
            ->get();

        // Siapkan labels (Jan..Des)
        $labels = [];
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = \Carbon\Carbon::create($year, $i, 1)->format('M');
        }

        // Map achieved bulanan per branch
        $achieved = [];
        foreach ($rows as $r) {
            $b = $r->bname;
            $idx = max(0, min(11, (int)$r->m - 1));
            if (!isset($achieved[$b])) $achieved[$b] = array_fill(0, 12, 0.0);
            $achieved[$b][$idx] = (float)$r->achieved;
        }

        // Tentukan branch yang boleh tampil (urut: JKT -> SBY -> MKS)
        $visible = $desiredOrder;
        if (in_array($roleCode, ['sales', 'branch_manager'])) {
            $visible = \App\Models\Masters\Branch::where('id', auth()->user()->branch_id)->pluck('name')->all();
        }

        // Bentuk datasets % per bulan
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

        // Base: Orders -> Leads -> Claim (sales) -> User/Branch
        $base = \App\Models\Orders\Order::query()
            ->join('leads', 'orders.lead_id', '=', 'leads.id')
            ->leftJoin('lead_claims as lc', function ($j) {
                $j->on('lc.lead_id', '=', 'leads.id')->whereNull('lc.deleted_at');
            })
            // join ke users via kolom klaim yang terdeteksi
            ->leftJoin('users as u', function ($j) use ($claimCol) {
                $j->on('u.id', '=', "lc.$claimCol");
            })
            ->leftJoin('ref_regions', 'leads.region_id', '=', 'ref_regions.id')
            ->leftJoin('ref_branches', 'ref_regions.branch_id', '=', 'ref_branches.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$start, $end]);

        // Role guard
        $roleCode = auth()->user()->role?->code;
        if ($roleCode === 'sales') {
            $base->where("lc.$claimCol", auth()->id());
        } elseif ($roleCode === 'branch_manager') {
            $base->where('u.branch_id', auth()->user()->branch_id);
        }
        if (!empty($validated['branch_id'])) {
            $base->where('u.branch_id', $validated['branch_id']);
        }

        // Tentukan Top 3 sales (atau pilihan user)
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

        // Deret bulan
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

        // Total amount semua sales per bulan
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

        // Amount per bulan per sales terpilih
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

        // Join ke branch agar bisa filter per cabang + ikuti pola query lain
        $query = \App\Models\Orders\Order::query()
            ->join('leads', 'orders.lead_id', '=', 'leads.id')
            ->leftJoin('ref_regions', 'leads.region_id', '=', 'ref_regions.id')
            ->leftJoin('ref_branches', 'ref_regions.branch_id', '=', 'ref_branches.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$start, $end]);

        // Role guard: sales/branch_manager terkunci ke cabangnya
        $roleCode = auth()->user()->role?->code;
        if (in_array($roleCode, ['sales', 'branch_manager'])) {
            $query->where('ref_branches.id', auth()->user()->branch_id);
        }

        // Filter cabang dari UI (opsional)
        if (!empty($validated['branch_id'])) {
            $query->where('ref_branches.id', $validated['branch_id']);
        }

        // Ambil agregasi per bulan
        $rows = $query->selectRaw("
                YEAR(orders.created_at)  as y,
                MONTH(orders.created_at) as m,
                COUNT(*)                 as total_orders,
                COALESCE(SUM(orders.total_billing),0) as total_amount
            ")
            ->groupBy('y', 'm')
            ->orderBy('y')->orderBy('m')
            ->get();

        // Build deret bulan lengkap sesuai range (isi nol bila tidak ada data)
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
                'label'  => $dt->format('M'), // contoh: Jan, Feb, ...
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

        // Base query (ikut pola join branch seperti endpoint lain)
        $baseQuery = \App\Models\Orders\Order::query()
            ->join('leads', 'orders.lead_id', '=', 'leads.id')
            ->leftJoin('ref_regions', 'leads.region_id', '=', 'ref_regions.id')
            ->leftJoin('ref_branches', 'ref_regions.branch_id', '=', 'ref_branches.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$start, $end]);

        // Role guard: sales/branch_manager hanya lihat cabangnya
        $roleCode = auth()->user()->role?->code;
        if (in_array($roleCode, ['sales', 'branch_manager'])) {
            $baseQuery->where('ref_branches.id', auth()->user()->branch_id);
        }

        // Tentukan 3 branch yang akan diplot:
        // - Jika user memilih branch_ids, pakai itu (maks 3)
        // - Jika tidak, ambil Top 3 berdasarkan total amount pada range
        $selectedBranchIds = [];
        if (!empty($validated['branch_ids'])) {
            $selectedBranchIds = array_slice(array_map('intval', $validated['branch_ids']), 0, 3);
            // tetap batasi baseQuery sesuai role guard (sudah dilakukan di atas)
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

        // Jika tidak ada branch sama sekali (data kosong), kembalikan struktur kosong yang aman
        if (empty($selectedBranchIds)) {
            return response()->json([
                'labels' => [],
                'series' => [],
            ]);
        }

        // Ambil agregasi per bulan PER branch untuk branch yang dipilih
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

        // Build deret bulan lengkap sesuai filter
        $period = CarbonPeriod::create(
            Carbon::parse($start)->startOfMonth(),
            '1 month',
            Carbon::parse($end)->startOfMonth()
        );

        $labels = [];
        $months = []; // key: Y-m -> index
        foreach ($period as $idx => $dt) {
            $key = $dt->format('Y-m');
            $months[$key] = $idx;
            // Label pendek: Jan, Feb, ...
            $labels[] = $dt->format('M');
        }

        // Siapkan struktur data per branch (isi nol dulu)
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

        // Isi nilai sesuai bulan
        foreach ($rows as $r) {
            $key = sprintf('%04d-%02d', (int)$r->y, (int)$r->m);
            if (isset($months[$key]) && isset($seriesMap[$r->branch_id])) {
                $seriesMap[$r->branch_id]['data'][$months[$key]] = (float)$r->total_amount;
                // pastikan label pakai nama branch yang benar
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

        // === TARGET COUNT PER BULAN PER BRANCH (flat) ===
        // catatan: tabel ke-3 dianggap HOT (12/9/9)
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
        $statusKey = $validated['status']; // 'cold'|'warm'|'hot'

        // Map status ke konstanta
        $statusMap = [
            'cold' => LeadStatus::COLD,
            'warm' => LeadStatus::WARM,
            'hot'  => LeadStatus::HOT,
        ];
        $statusId = $statusMap[$statusKey];

        $start = $validated['start_date'] ?? now()->startOfYear()->toDateString();
        $end   = $validated['end_date']   ?? now()->endOfMonth()->toDateString();

        $dateExpr = DB::raw('DATE(COALESCE(leads.published_at, leads.created_at))');

        // Subquery nominal per lead
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

        // role guard
        $roleCode = auth()->user()->role?->code;
        if (in_array($roleCode, ['sales', 'branch_manager'])) {
            $base->where('ref_branches.id', auth()->user()->branch_id);
        }

        // Tentukan branch terpilih (<=3)
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

        // Agregasi per bulan per branch
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

        // Deret bulan sesuai range
        $period = CarbonPeriod::create(
            Carbon::parse($start)->startOfMonth(),
            '1 month',
            Carbon::parse($end)->startOfMonth()
        );

        $labels = [];
        $monthIndex = []; // 'Y-m' => idx
        foreach ($period as $i => $dt) {
            $key = $dt->format('Y-m');
            $monthIndex[$key] = $i;
            $labels[] = $dt->format('M');
        }

        // Nama branch
        $names = Branch::whereIn('id', $selected)->pluck('name', 'id');

        // Siapkan struktur seri (per branch)
        $seriesCount  = [];
        $seriesAmount = [];
        foreach ($selected as $bid) {
            $label = $names[$bid] ?? ('Branch ' . $bid);
            $seriesCount[$bid]  = ['label' => $label, 'data' => array_fill(0, count($labels), 0)];
            $seriesAmount[$bid] = ['label' => $label, 'data' => array_fill(0, count($labels), 0)];
        }

        // Juga kumpulkan total leads & amount per bulan (semua selected) untuk hitung rata2 nominal/lead
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

        // === HITUNG GARIS TARGET ===
        // Target COUNT = penjumlahan target branch terpilih per bulan.
        $targetCount = array_fill(0, count($labels), 0);
        $period2 = CarbonPeriod::create(Carbon::parse($start)->startOfMonth(), '1 month', Carbon::parse($end)->startOfMonth());
        $i = 0;
        foreach ($period2 as $dt) {
            $mi = (int)$dt->format('n') - 1; // 0..11
            foreach ($selected as $bid) {
                $bname = $names[$bid] ?? null;
                if (!$bname) continue;
                $tbl = $TARGET_TABLE[$statusKey][$bname] ?? null;
                if (!$tbl) continue;
                $targetCount[$i] += (int)($tbl[$mi] ?? 0);
            }
            $i++;
        }

        // Target NOMINAL = targetCount * rata2 nominal/lead (per bulan) -> skala cocok dgn sumbu rupiah
        $targetAmount = [];
        for ($k = 0; $k < count($labels); $k++) {
            $avg = $sumLeadsByIdx[$k] > 0 ? ($sumAmountByIdx[$k] / $sumLeadsByIdx[$k]) : 0;
            $targetAmount[$k] = $targetCount[$k] * $avg;
        }

        return response()->json([
            'labels'        => $labels,
            'series_count'  => array_values($seriesCount),
            'series_amount' => array_values($seriesAmount),
            // garis target (1 line per chart)
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

            // if (!empty($validated['status_id'])) {
            //     $baseQuery->where('status_id', $validated['status_id']);
            // }

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
                $acquisitionTimeResult = (clone $baseQuery)
                    ->join('lead_claims', 'leads.id', '=', 'lead_claims.lead_id')
                    ->whereNull('lead_claims.deleted_at')
                    ->selectRaw('
                    AVG(TIMESTAMPDIFF(HOUR,
                        COALESCE(leads.published_at, leads.created_at),
                        lead_claims.created_at
                    )) as avg_hours
                ')
                    ->first();

                $acquisitionTime = $acquisitionTimeResult ? round($acquisitionTimeResult->avg_hours, 2) : 0;
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

            $data = $query->get();

            // Hitung total cumulative untuk semua source
            $totalCumulative = $data->sum(function ($item) {
                return $item->cold_count + $item->warm_count + $item->hot_count + $item->deal_count;
            });

            $formattedData = $data->map(function ($item) use ($totalCumulative) {
                $cold = (int) $item->cold_count;
                $warm = (int) $item->warm_count;
                $hot = (int) $item->hot_count;
                $deal = (int) $item->deal_count;

                $cumulative = $cold + $warm + $hot + $deal;

                // Hitung persentase terhadap total cumulative semua source
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
                    'branch_id' => $branchId
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Source Monthly Stats Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan server',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
