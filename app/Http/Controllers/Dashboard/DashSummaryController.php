<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadStatus;
use App\Models\Leads\LeadClaim;
use App\Models\Orders\Quotation;
use App\Models\Orders\Invoice;
use App\Models\Leads\LeadSource;
use Carbon\Carbon;

class DashSummaryController extends Controller
{


    public function grid(Request $request)
    {
        $user = Auth::user();
        $roleCode = $user?->role?->code;

        // target global: jumlah dari seluruh target user di tabel users
        $target = User::all()->sum(function (User $u) {
            $raw = $u->target;
            if (! $raw) {
                return 0.0;
            }

            // Format bisa "angka" biasa atau "angka|json".
            if (is_string($raw) && str_contains($raw, '|')) {
                [$number] = explode('|', $raw, 2);
                return (float) $number;
            }

            return (float) $raw;
        });

        // Closed deals: ambil SEMUA claim aktif dengan status DEAL (global)
        $claims = LeadClaim::with(['lead.quotation.proformas.paymentConfirmation'])
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::DEAL))
            ->whereNull('released_at');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $claims->whereHas('lead.quotation', function ($q) use ($request) {
                $q->firstTermPaidBetween($request->start_date, $request->end_date);
            });
        }

        $completedDeals = 0;
        $monetaryActual = 0;

        foreach ($claims->get() as $claim) {
            $quotation = $claim->lead?->quotation;
            if (! $quotation) {
                continue;
            }

            $proformas = $quotation->proformas ?? collect();
            $totalPayments = $proformas->count();

            $confirmedProformas = $proformas->filter(function ($p) {
                return $p->paymentConfirmation && $p->paymentConfirmation->confirmed_at;
            });

            $approvedPayments = $confirmedProformas->count();

            // Only count deals that have all proformas confirmed.
            if ($totalPayments > 0 && $approvedPayments >= $totalPayments) {
                $completedDeals++;
                $monetaryActual += (float) $confirmedProformas->sum(function ($p) {
                    return (float) ($p->paymentConfirmation->amount ?? $p->amount ?? 0);
                });
            }
        }

        $monetaryActual = round($monetaryActual, 2);
        $achievementPercentage = $target > 0
            ? round(($monetaryActual / $target) * 100, 2)
            : 0;

        $closedDeals = $completedDeals;
        $closedAmount = round($monetaryActual, 2);

        // Potential dealing: similar logic as DashboardController::potentialDealing
        $end = $request->filled('end_date') ? $request->end_date : now()->toDateString();
        $start = $request->filled('start_date') ? $request->start_date : now()->subDays(30)->toDateString();

        $warmStatusId = LeadStatus::WARM;
        $hotStatusId = LeadStatus::HOT;

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

        $potentialLeads = Lead::query()
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
            ->whereIn('leads.status_id', [$warmStatusId, $hotStatusId]);

        $potentialCollection = $potentialLeads
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
                'quotations.grand_total',
            ])
            ->distinct()
            ->get()
            ->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'amount' => (float) ($lead->grand_total ?? 0),
                ];
            });

        // Also include leads that already have confirmed payments (even if quotation status isn't published)
        $paymentLeads = Lead::query()
            ->whereIn('status_id', [$warmStatusId, $hotStatusId])
            ->whereHas('quotation.proformas.paymentConfirmation', fn($q) => $q->whereNotNull('confirmed_at'));

        $paymentCollection = $paymentLeads->with(['quotation.proformas.paymentConfirmation'])
            ->get()
            ->map(function ($lead) {
                $proformas = $lead->quotation->proformas ?? collect();
                $amount = (float) $proformas->filter(function ($p) {
                    return $p->paymentConfirmation && $p->paymentConfirmation->confirmed_at;
                })->sum(function ($p) {
                    return (float) ($p->paymentConfirmation->amount ?? $p->amount ?? 0);
                });

                return [
                    'id' => $lead->id,
                    'amount' => $amount,
                ];
            });

        // Merge both collections by lead id to avoid double-counting
        $merged = collect()
            ->merge($potentialCollection)
            ->merge($paymentCollection)
            ->unique('id');

        $potentialTotalOpportunity = $merged->count();
        $potentialTotalAmount = $merged->sum('amount');

        $conversionRate = $potentialTotalOpportunity > 0
            ? round(($closedDeals / $potentialTotalOpportunity) * 100, 2)
            : 0;

        // ==============================
        // ACTIVE LEADS (COLD/WARM/HOT)
        // ==============================
        $leadQuery = Lead::query();

        if ($roleCode === 'sales') {
            // Hanya leads yang diklaim oleh sales ini dan belum dirilis
            $leadQuery->whereHas('claims', function ($q) use ($user) {
                $q->whereNull('released_at')
                    ->where('sales_id', $user?->id);
            });
        } elseif ($roleCode === 'branch_manager') {
            // Hanya leads di branch manager ini
            $leadQuery->where('branch_id', $user?->branch_id);
        }

        // Hitung semua leads per status langsung dari tabel leads
        $counts = $leadQuery
            ->select('status_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status_id')
            ->pluck('aggregate', 'status_id');

        $cold = (int) ($counts[LeadStatus::COLD] ?? 0);
        $warm = (int) ($counts[LeadStatus::WARM] ?? 0);
        $hot  = (int) ($counts[LeadStatus::HOT] ?? 0);

        $trash = (int) (($counts[LeadStatus::TRASH_COLD] ?? 0)
            + ($counts[LeadStatus::TRASH_WARM] ?? 0)
            + ($counts[LeadStatus::TRASH_HOT] ?? 0));

        // Total active = semua leads dengan status cold/warm/hot
        $totalActive = $cold + $warm + $hot;

        $activeLeads = [
            'total' => $totalActive,
            'trash' => $trash,
            'cold' => $cold,
            'warm' => $warm,
            'hot' => $hot,
        ];

        $data = [
            'status' => 'success',
            'Data' => [
                'achievement_target' => [
                    'target' => $target,
                    'achievement' => $monetaryActual,
                    'percentage' => $achievementPercentage,
                ],
                'closed_deal' => [
                    'total_deals' => $closedDeals,
                    'total_amount' => $closedAmount,
                    'conversion_rate' => $conversionRate,
                ],
                'active_leads' => $activeLeads,
                'potential_dealing' => [
                    'total_amount' => $potentialTotalAmount,
                    'total_opportunity' => $potentialTotalOpportunity,
                ],
            ],
        ];

        return response()->json($data);
    }

    public function ActiveOpportunities(Request $request)
    {
        $perPage = $request->get('per_page', 5);

        $query = LeadClaim::whereNull('released_at')
            ->with([
                'lead.product',
                'lead.segment',
                'lead.latestStatusLog',
                'lead.quotation.items.product',
                'lead.status',
                'sales',
            ]);

        $allowedStatuses = [LeadStatus::COLD, LeadStatus::WARM, LeadStatus::HOT];

        // Filter hanya berdasarkan data lead (global, tanpa filter per sales/branch)
        $query->whereHas('lead', function ($q) use ($allowedStatuses, $request) {

            $q->whereIn('status_id', $allowedStatuses);

            if ($request->filled('stage')) {
                $q->where('status_id', $request->stage);
            }

            if ($request->filled('source_id')) {
                $q->where('source_id', $request->source_id);
            }

            if ($request->filled('segment')) {
                $q->where('segment_id', $request->segment);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%");
                });
            }

            if ($request->filled('start_date')) {
                $q->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $q->whereDate('created_at', '<=', $request->end_date);
            }
        });

        // =========================
        // GET LATEST CLAIM PER LEAD
        // =========================
        $query->whereIn('id', function ($q) {
            $q->select(DB::raw('MAX(id)'))
                ->from('lead_claims as lc2')
                ->whereColumn('lc2.lead_id', 'lead_claims.lead_id')
                ->groupBy('lead_id');
        });

        $amountQuery = clone $query;

        $totalAmount = $amountQuery->get()->sum(function ($claim) {
            return (float) ($claim->lead->quotation->grand_total ?? 0);
        });

        $paginated = $query->orderByDesc('id')->paginate($perPage);

        // =========================
        // TRANSFORM DATA
        // =========================
        $paginated->getCollection()->transform(function ($claim) {

            $lead = $claim->lead;

            $amount = (float) ($lead->quotation->grand_total ?? 0);
            $stage = $lead->status?->name ?? null;

            $product = $lead->product?->name
                ?? ($lead->quotation?->items->first()?->product?->name ?? null);

            $segment = $lead->segment?->name ?? $lead->customer_type ?? null;

            $salesName = $claim->sales?->name ?? null;

            $lastActivity = $lead->latestStatusLog?->created_at
                ?? $lead->updated_at;

            $validationChecks = [
                'contact_info' => !empty($lead->phone) || !empty($lead->email),
                'business_reason' => !empty($lead->business_reason),
                'quotation_exists' => !empty($lead->quotation?->quotation_no),
                'quotation_amount' => !empty($lead->quotation?->grand_total) && ($lead->quotation->grand_total > 0),
                'regional_info' => !empty($lead->region_id),
                'product_info' => !empty($lead->product_id),
            ];

            $passed = count(array_filter($validationChecks));

            if ($passed >= 5) {
                $dataValidation = 'Complete';
            } elseif ($passed === 4) {
                $dataValidation = 'Moderate';
            } else {
                $dataValidation = 'Incomplete';
            }

            return [
                'id' => $lead->id,
                'customer_name' => $lead->name ?? $lead->company,
                'stage' => $stage,
                'amount' => $amount,
                'product' => $product,
                'segment' => $segment,
                'sales_name' => $salesName,
                'data_status' => $passed . '/6',
                'last_activity' => $lastActivity?->toDateTimeString(),
                'data_validation' => $dataValidation,
                'created_at' => $lead->created_at?->toDateString(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $paginated->items(),
            'total' => $paginated->total(),
            'total_amount' => $totalAmount,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    public function SourceConversionLists()
    {
        // Ambil filter dari request
        $year  = request()->get('year');   // misal 2025
        $month = request()->get('month');  // misal 12

        // Query dengan filter fleksibel
        $rows = LeadSource::leftJoin('leads', 'lead_sources.id', '=', 'leads.source_id')
            ->selectRaw("
            lead_sources.name as source,
            SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as cold,
            SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as warm,
            SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as hot,
            SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as deal
        ", [
                LeadStatus::COLD,
                LeadStatus::WARM,
                LeadStatus::HOT,
                LeadStatus::DEAL
            ])
            // Filter tahun & bulan jika dikirim
            ->when($year, function ($q) use ($year) {
                $q->whereYear('leads.created_at', $year);
            })
            ->when($month, function ($q) use ($month) {
                $q->whereMonth('leads.created_at', $month);
            })
            ->groupBy('lead_sources.id', 'lead_sources.name')
            ->orderBy('lead_sources.name')
            ->get()
            ->map(function ($row) {
                $cold = (int) $row->cold;
                $warm = (int) $row->warm;
                $hot  = (int) $row->hot;
                $deal = (int) $row->deal;

                $total = $cold + $warm + $hot + $deal;

                return [
                    'source' => $row->source,
                    'total_source'  => $total,
                    'persen_cum'    => 0, // sementara, nanti dioverride
                    'cold'   => $cold,
                    'persen_cold' => $total > 0 ? round(($cold / $total) * 100, 1) : 0,
                    'warm'   => $warm,
                    'persen_warm' => $total > 0 ? round(($warm / $total) * 100, 1) : 0,
                    'hot'    => $hot,
                    'persen_hot'  => $total > 0 ? round(($hot / $total) * 100, 1) : 0,
                    'deal'   => $deal,
                    'persen_deal' => $total > 0 ? round(($deal / $total) * 100, 1) : 0,
                ];
            });

        // 🔥 Hitung grand total
        $grandCold  = $rows->sum('cold');
        $grandWarm  = $rows->sum('warm');
        $grandHot   = $rows->sum('hot');
        $grandDeal  = $rows->sum('deal');
        $grandTotal = $grandCold + $grandWarm + $grandHot + $grandDeal;

        // 🔥 Update persen_cum per source
        $rows = $rows->map(function ($row) use ($grandTotal) {
            $row['persen_cum'] = $grandTotal > 0
                ? round(($row['total_source'] / $grandTotal) * 100, 1)
                : 0;
            return $row;
        });

        // 🔥 Tambahkan row total
        $rows->push([
            'source'       => 'Total',
            'total_source' => $grandTotal,
            'persen_cum'   => 100,
            'cold'   => $grandCold,
            'persen_cold' => $grandTotal > 0 ? round(($grandCold / $grandTotal) * 100, 1) : 0,
            'warm'   => $grandWarm,
            'persen_warm' => $grandTotal > 0 ? round(($grandWarm / $grandTotal) * 100, 1) : 0,
            'hot'    => $grandHot,
            'persen_hot'  => $grandTotal > 0 ? round(($grandHot / $grandTotal) * 100, 1) : 0,
            'deal'   => $grandDeal,
            'persen_deal' => $grandTotal > 0 ? round(($grandDeal / $grandTotal) * 100, 1) : 0,
        ]);

        return response()->json([
            'status' => 'success',
            'filters' => [
                'year'  => $year,
                'month' => $month
            ],
            'data' => $rows
        ]);
    }

    public function SalesSegmentPerformance()
    {
        $rows = Lead::join('lead_segments', 'lead_segments.id', '=', 'leads.segment_id')
            ->selectRaw("
            lead_segments.name as segment,
            SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as cold,
            SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as warm,
            SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as hot,
            SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as deal
        ", [
                LeadStatus::COLD,
                LeadStatus::WARM,
                LeadStatus::HOT,
                LeadStatus::DEAL
            ])
            ->groupBy('lead_segments.id', 'lead_segments.name')
            ->orderBy('lead_segments.name')
            ->get()
            ->map(function ($row) {

                $cold = (int) $row->cold;
                $warm = (int) $row->warm;
                $hot  = (int) $row->hot;
                $deal = (int) $row->deal;

                $total = $cold + $warm + $hot + $deal;

                return [
                    'segment' => $row->segment,
                    'cum'     => $total,

                    // sementara isi dulu (dioverride nanti)
                    'persen_cum' => '0,0',

                    'cold'   => $cold,
                    'persen_cold' => $total > 0 ? number_format(($cold / $total) * 100, 1, ',', '') : '0,0',

                    'warm'   => $warm,
                    'persen_warm' => $total > 0 ? number_format(($warm / $total) * 100, 1, ',', '') : '0,0',

                    'hot'    => $hot,
                    'persen_hot'  => $total > 0 ? number_format(($hot / $total) * 100, 1, ',', '') : '0,0',

                    'deal'   => $deal,
                    'persen_deal' => $total > 0 ? number_format(($deal / $total) * 100, 1, ',', '') : '0,0',

                    'total_segment' => $total,
                ];
            });

        // 🔥 GRAND TOTAL
        $grandCold = $rows->sum('cold');
        $grandWarm = $rows->sum('warm');
        $grandHot  = $rows->sum('hot');
        $grandDeal = $rows->sum('deal');
        $grandTotal = $grandCold + $grandWarm + $grandHot + $grandDeal;

        // 🔥 UPDATE persen_cum pakai GRAND TOTAL
        $rows = $rows->map(function ($row) use ($grandTotal) {

            if ($row['segment'] !== 'Total') {
                $row['persen_cum'] = $grandTotal > 0
                    ? number_format(($row['total_segment'] / $grandTotal) * 100, 1, ',', '')
                    : '0,0';
            }

            return $row;
        });

        $rows->push([
            'segment' => 'Total',
            'cum'     => $grandTotal,
            'cold'    => $grandCold,
            'warm'    => $grandWarm,
            'hot'     => $grandHot,
            'deal'    => $grandDeal,
            'total_stage' => $grandTotal,
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $rows,
        ]);
    }

    public function SalesTrend(Request $request)
    {
        $year = $request->year ?? now()->year;
        $month = $request->month;
        $monthFrom = $request->month_from;
        $monthTo = $request->month_to;

        $groupBy = 'month';

        if ($month) {
            $groupBy = 'week';
            $monthFrom = $month;
            $monthTo = $month;
        } elseif ($monthFrom && $monthTo) {
            if ($monthFrom == 1 && $monthTo == 12) {
                $groupBy = 'quarter';
            } else {
                $groupBy = 'month';
            }
        }

        $labels = [];
        $periods = [];

        if ($groupBy === 'month') {
            $from = $monthFrom ? (int) $monthFrom : 1;
            $to = $monthTo ? (int) $monthTo : 12;

            for ($m = $from; $m <= $to; $m++) {
                $periodStart = Carbon::create($year, $m, 1)->startOfMonth()->toDateString();
                $periodEnd = Carbon::create($year, $m, 1)->endOfMonth()->toDateString();

                $labels[] = date('M', mktime(0, 0, 0, $m, 1));
                $periods[] = [
                    'start' => $periodStart,
                    'end' => $periodEnd,
                    'target_multiplier' => 1,
                ];
            }
        } elseif ($groupBy === 'quarter') {
            for ($q = 1; $q <= 4; $q++) {
                $startMonth = 1 + (($q - 1) * 3);
                $endMonth = $startMonth + 2;

                $periodStart = Carbon::create($year, $startMonth, 1)->startOfMonth()->toDateString();
                $periodEnd = Carbon::create($year, $endMonth, 1)->endOfMonth()->toDateString();

                $labels[] = 'Q' . $q;
                $periods[] = [
                    'start' => $periodStart,
                    'end' => $periodEnd,
                    'target_multiplier' => 3,
                ];
            }
        } else {
            // groupBy === 'week' -> fallback ke 1 titik MTD
            $monthVal = $month ?: now()->month;
            $periodStart = Carbon::create($year, $monthVal, 1)->startOfMonth()->toDateString();
            $periodEnd = Carbon::create($year, $monthVal, 1)->endOfMonth()->toDateString();

            $labels[] = 'MTD';
            $periods[] = [
                'start' => $periodStart,
                'end' => $periodEnd,
                'target_multiplier' => 1,
            ];
        }

        // Ambil semua user sales (atau role lain jika diperlukan nantinya)
        $users = User::with('role')
            ->whereHas('role', function ($q) {
                $q->where('code', 'sales');
            })
            ->get();

        $salesTrends = [];

        foreach ($users as $user) {
            $monthlyTarget = $user && $user->target ? (float) $user->target : 0.0;

            $salesData = [];
            $targetData = [];

            foreach ($periods as $period) {
                $amount = $this->calculateSalesAchievementForPeriod($user, $period['start'], $period['end']);

                $salesData[] = (int) round($amount);
                $targetData[] = (int) round($monthlyTarget * $period['target_multiplier']);
            }

            $salesTrends[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'datasets' => [
                    [
                        'name' => 'Target',
                        'data' => $targetData,
                    ],
                    [
                        'name' => 'Sales',
                        'data' => $salesData,
                    ],
                ],
            ];
        }

        return response()->json([
            'status' => 'success',
            'filter' => [
                'year' => $year,
                'month' => $month,
                'month_from' => $monthFrom,
                'month_to' => $monthTo,
            ],
            'group_by' => $groupBy,
            'labels' => $labels,
            'data' => $salesTrends,
        ]);
    }

    public function LeadsPerformance(Request $request)
    {
        $latestQuotationSubquery = DB::table('quotations')
            ->select('lead_id', DB::raw('MAX(created_at) as latest_date'))
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->groupBy('lead_id');

        $rows = Lead::leftJoin('lead_sources', 'lead_sources.id', '=', 'leads.source_id')
            ->leftJoin('lead_segments', 'lead_segments.id', '=', 'leads.segment_id')
            ->leftJoin('lead_claims', function ($join) {
                $join->on('lead_claims.lead_id', '=', 'leads.id')
                    ->whereNull('lead_claims.released_at');
            })
            ->leftJoinSub($latestQuotationSubquery, 'latest_quo', function ($join) {
                $join->on('latest_quo.lead_id', '=', 'leads.id');
            })
            ->leftJoin('quotations', function ($join) {
                $join->on('quotations.lead_id', '=', 'latest_quo.lead_id')
                    ->on('quotations.created_at', '=', 'latest_quo.latest_date');
            })
            // Global (tidak dibatasi per user / per branch). Hanya filter by source jika dikirim.
            ->when($request->source_id, function ($q) use ($request) {
                $q->where('leads.source_id', $request->source_id);
            })
            ->selectRaw(
                "lead_sources.name as source,
        COALESCE(lead_segments.name, leads.customer_type) as segment,

        SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as cold,
        SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as warm,
        SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as hot,
        SUM(CASE WHEN leads.status_id = ? THEN 1 ELSE 0 END) as deal,

        SUM(CASE WHEN leads.status_id = ? THEN COALESCE(quotations.grand_total,0) ELSE 0 END) as nominal_warm,
        SUM(CASE WHEN leads.status_id = ? THEN COALESCE(quotations.grand_total,0) ELSE 0 END) as nominal_hot,
        SUM(CASE WHEN leads.status_id = ? THEN COALESCE(quotations.grand_total,0) ELSE 0 END) as nominal_deal
        ",
                [
                    LeadStatus::COLD,
                    LeadStatus::WARM,
                    LeadStatus::HOT,
                    LeadStatus::DEAL,
                    LeadStatus::WARM,
                    LeadStatus::HOT,
                    LeadStatus::DEAL
                ]
            )
            ->groupBy(
                'lead_sources.id',
                'lead_sources.name',
                DB::raw('COALESCE(lead_segments.name, leads.customer_type)')
            )
            ->orderBy('lead_sources.name')
            ->get()
            ->map(function ($row) {

                $cold = (int)$row->cold;
                $warm = (int)$row->warm;
                $hot  = (int)$row->hot;
                $deal = (int)$row->deal;

                $total = $cold + $warm + $hot + $deal;

                $nominalWarm = (float)$row->nominal_warm;
                $nominalHot  = (float)$row->nominal_hot;
                $nominalDeal = (float)$row->nominal_deal;

                return [
                    'source' => $row->source,
                    'segment' => $row->segment,

                    'cold' => $cold,
                    'warm' => $warm,
                    'hot' => $hot,
                    'deal' => $deal,

                    'nominal_warm' => $nominalWarm,
                    'nominal_hot' => $nominalHot,
                    'nominal_deal' => $nominalDeal,

                    'amount_cum' => $nominalWarm + $nominalHot + $nominalDeal,
                    'total' => $total
                ];
            });

        // buang baris yang total-nya 0
        $rows = $rows->filter(fn($r) => $r['total'] > 0)->values();

        // group by source
        $bySource = $rows->groupBy('source')->map(function ($items, $source) {

            return [
                'source' => $source,

                'total' => $items->sum('total'),
                'cold' => $items->sum('cold'),
                'warm' => $items->sum('warm'),
                'hot' => $items->sum('hot'),
                'deal' => $items->sum('deal'),

                'nominal_warm' => $items->sum('nominal_warm'),
                'nominal_hot' => $items->sum('nominal_hot'),
                'nominal_deal' => $items->sum('nominal_deal'),
                'amount_cum' => $items->sum('amount_cum'),

                'segments' => $items->values()
            ];
        })->values();

        $summaryBySource = [
            'total_all' => $bySource->sum('total'),
            'total_cold' => $bySource->sum('cold'),
            'total_warm' => $bySource->sum('warm'),
            'total_hot' => $bySource->sum('hot'),
            'total_deal' => $bySource->sum('deal'),

            'nominal_total_warm' => $bySource->sum('nominal_warm'),
            'nominal_total_hot' => $bySource->sum('nominal_hot'),
            'nominal_total_deal' => $bySource->sum('nominal_deal'),
            'nominal_total' => $bySource->sum('amount_cum')
        ];

        // group by segment
        $bySegment = $rows->groupBy('segment')->map(function ($items, $segment) {

            return [
                'segment' => $segment,

                'total' => $items->sum('total'),
                'cold' => $items->sum('cold'),
                'warm' => $items->sum('warm'),
                'hot' => $items->sum('hot'),
                'deal' => $items->sum('deal'),

                'nominal_warm' => $items->sum('nominal_warm'),
                'nominal_hot' => $items->sum('nominal_hot'),
                'nominal_deal' => $items->sum('nominal_deal'),
                'amount_cum' => $items->sum('amount_cum'),

                'sources' => $items->values()
            ];
        })->values();

        $summaryBySegment = [
            'total_all' => $bySegment->sum('total'),
            'total_cold' => $bySegment->sum('cold'),
            'total_warm' => $bySegment->sum('warm'),
            'total_hot' => $bySegment->sum('hot'),
            'total_deal' => $bySegment->sum('deal'),

            'nominal_total_warm' => $bySegment->sum('nominal_warm'),
            'nominal_total_hot' => $bySegment->sum('nominal_hot'),
            'nominal_total_deal' => $bySegment->sum('nominal_deal'),
            'nominal_total' => $bySegment->sum('amount_cum')
        ];

        return response()->json([
            'status' => 'success',

            'by_source' => [
                'data' => $bySource,
                'summary' => $summaryBySource
            ],

            'by_segment' => [
                'data' => $bySegment,
                'summary' => $summaryBySegment
            ]
        ]);
    }

    private function calculateSalesAchievementForPeriod(User $user, string $startDate, string $endDate): float
    {
        $claims = LeadClaim::with(['lead.quotation.proformas.paymentConfirmation'])
            ->whereHas('lead', function ($q) {
                $q->where('status_id', LeadStatus::DEAL);
            })
            ->whereNull('released_at')
            ->where('sales_id', $user->id);

        // Gunakan jendela pembayaran term pertama seperti di grid
        $claims->whereHas('lead.quotation', function ($q) use ($startDate, $endDate) {
            $q->firstTermPaidBetween($startDate, $endDate);
        });

        $monetaryActual = 0;

        foreach ($claims->get() as $claim) {
            $quotation = $claim->lead?->quotation;
            if (! $quotation) {
                continue;
            }

            $proformas = $quotation->proformas ?? collect();
            $totalPayments = $proformas->count();

            $confirmedProformas = $proformas->filter(function ($p) {
                return $p->paymentConfirmation && $p->paymentConfirmation->confirmed_at;
            });

            $approvedPayments = $confirmedProformas->count();

            // Hanya hitung deal yang semua proforma-nya sudah confirmed
            if ($totalPayments > 0 && $approvedPayments >= $totalPayments) {
                $monetaryActual += (float) $confirmedProformas->sum(function ($p) {
                    return (float) ($p->paymentConfirmation->amount ?? $p->amount ?? 0);
                });
            }
        }

        return round($monetaryActual, 2);
    }

    public function SourceMonitoringChart(): JsonResponse
    {
        $year  = request()->get('year', now()->year);
        $month = request()->get('month'); // optional

        $monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];

        $rawData = LeadSource::leftJoin('leads', function ($join) use ($year, $month) {
            $join->on('lead_sources.id', '=', 'leads.source_id')
                ->whereYear('leads.created_at', '=', $year);

            if ($month) {
                $join->whereMonth('leads.created_at', '=', $month);
            }
        })
            ->selectRaw("
            lead_sources.name as source,
            MONTH(leads.created_at) as month,
            COUNT(leads.id) as total
        ")
            ->groupBy('lead_sources.id', 'lead_sources.name', 'month')
            ->get();

        $sources = LeadSource::pluck('name');

        $result = [];

        foreach ($sources as $source) {

            // kalau filter month → hanya 1 bulan
            if ($month) {
                $monthlyData = [0];

                foreach ($rawData as $row) {
                    if ($row->source === $source) {
                        $monthlyData[0] = (int) $row->total;
                    }
                }
            } else {
                // full 12 bulan
                $monthlyData = array_fill(1, 12, 0);

                foreach ($rawData as $row) {
                    if ($row->source === $source && $row->month) {
                        $monthlyData[$row->month] = (int) $row->total;
                    }
                }

                $monthlyData = array_values($monthlyData);
            }

            $result[] = [
                'source' => $source,
                'data'   => $monthlyData
            ];
        }

        return response()->json([
            'status' => 'success',
            'year'   => (int) $year,
            'labels' => $month ? [$monthNames[$month]] : array_values($monthNames),
            'data'   => $result
        ]);
    }
}
