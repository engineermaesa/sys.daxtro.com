<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leads\{LeadClaim, LeadStatus, Lead, LeadActivityLog};
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LeadSummaryController extends Controller
{
    public function grid(Request $request)
    {
        $user = Auth::user();
        
        $monthKey = (string) Carbon::now('Asia/Jakarta')->month;
        
        $getMonthlyTarget = function ($raw, string $field, string $monthKey): float {
            if (empty($raw)) {
                return 0;
            }

            // Format: "40|{...json...}"
            [$default, $jsonPart] = array_pad(explode('|', (string) $raw, 2), 2, null);

            if (!empty($jsonPart)) {
                $decoded = json_decode($jsonPart, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return (float) ($decoded[$monthKey][$field] ?? 0);
                }
            }

            // fallback kalau ternyata hanya angka
            return is_numeric($default) ? (float) $default : 0;
        };

        // target comes from user (set by superadmin)
        $target_amount = $getMonthlyTarget($user->target ?? null, 'amount', $monthKey);
        $target_lead = $getMonthlyTarget($user->target_leads ?? null, 'leads', $monthKey);
        $target_visit = $getMonthlyTarget($user->target_visit ?? $user->target_visits ?? null, 'visits', $monthKey);

        // Align with `/api/leads/my/deal/list`: deals are sourced from active LeadClaims with status DEAL.
        $claims = LeadClaim::with(['lead.quotation.proformas.paymentConfirmation'])
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::DEAL))
            ->whereNull('released_at');

        $roleCode = $user?->role?->code;

        if ($roleCode === 'sales') {
            $claims->where('sales_id', $user?->id);
        } elseif ($roleCode === 'branch_manager') {
            $claims->whereHas('sales', function ($q) use ($user) {
                $q->where('branch_id', $user?->branch_id);
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $claims->whereHas('lead.quotation', function ($q) use ($request) {
                $q->firstTermPaidBetween($request->start_date, $request->end_date);
            });
        }

        $completedDeals = 0;
        $monetaryActual = 0;
        $leadsActual = 0;
        $visitsActual = 0;

        foreach ($claims->get() as $claim) {
            $lead = $claim->lead;

            $claimDate = $claim->claimed_at ?? $lead?->published_at ?? null;
            if ($claimDate) {
                $claimMonth = (string) Carbon::parse($claimDate)->month;

                if ($claimMonth === $monthKey) {
                    $leadsActual++;

                    if ((int) ($lead?->source_id ?? 0) === 9) {
                        $visitsActual++;
                    }
                }
            }

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
        $achievementPercentage = $target_amount > 0
            ? round(($monetaryActual / $target_amount) * 100, 2)
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

        if ($roleCode === 'sales') {
            $potentialLeads->where('lead_claims.sales_id', $user?->id);
        } elseif ($roleCode === 'branch_manager') {
            $potentialLeads->where('leads.branch_id', $user?->branch_id);
        }

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

        if ($roleCode === 'sales') {
            $paymentLeads->whereHas('claims', fn($q) => $q->whereNull('released_at')->where('sales_id', $user?->id));
        } elseif ($roleCode === 'branch_manager') {
            $paymentLeads->where('branch_id', $user?->branch_id);
        }

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


        $activeClaims = LeadClaim::whereNull('released_at')->with('lead');

        if ($roleCode === 'sales') {
            $activeClaims->where('sales_id', $user?->id);
        } elseif ($roleCode === 'branch_manager') {
            $activeClaims->whereHas('sales', function ($q) use ($user) {
                $q->where('branch_id', $user?->branch_id);
            });
        }

        // Get active claims, but count unique leads to avoid double-counting
        $claims = $activeClaims->get();

        // Extract lead models from claims, remove nulls, and ensure uniqueness by lead id
        $uniqueLeads = $claims->pluck('lead')->filter()->unique('id');

        // Group unique leads by their status and count per status
        $counts = $uniqueLeads->groupBy('status_id')->map->count();

        $cold = $counts[LeadStatus::COLD] ?? 0;
        $warm = $counts[LeadStatus::WARM] ?? 0;
        $hot  = $counts[LeadStatus::HOT] ?? 0;
        $deal = $counts[LeadStatus::DEAL] ?? 0;

        // Total active leads should be derived from unique leads (exclude trash statuses)
        $trash = ($counts[LeadStatus::TRASH_COLD] ?? 0)
            + ($counts[LeadStatus::TRASH_WARM] ?? 0)
            + ($counts[LeadStatus::TRASH_HOT] ?? 0);

        $totalActive = $uniqueLeads->reject(function ($l) {
            return in_array($l->status_id, [LeadStatus::TRASH_COLD, LeadStatus::TRASH_WARM, LeadStatus::TRASH_HOT]);
        })->count();

        // Also expose published and any "other" statuses not in cold/warm/hot/deal
        $published = $counts[LeadStatus::PUBLISHED] ?? 0;
        $other = $totalActive - ($cold + $warm + $hot + $deal);
        if ($other < 0) $other = 0;

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
                    'target_amount' => $target_amount,
                    'target_leads' => $target_lead,
                    'target_visits' => $target_visit,
                    'leads_actual' => $leadsActual,
                    'visits_actual' => $visitsActual,
                    'achievement_amount' => $monetaryActual,
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
        $user = Auth::user();
        $roleCode = $user?->role?->code;

        $perPage = $request->get('per_page', 5);

        $query = LeadClaim::whereNull('released_at')
            ->with([
                'lead.product',
                'lead.segment',
                'lead.latestStatusLog',
                'lead.quotation.items.product',
                'lead.status'
            ]);

        if ($roleCode === 'sales') {
            $query->where('sales_id', $user?->id);
        } elseif ($roleCode === 'branch_manager') {
            $query->whereHas('sales', function ($q) use ($user) {
                $q->where('branch_id', $user?->branch_id);
            });
        }

        $allowedStatuses = [LeadStatus::COLD, LeadStatus::WARM, LeadStatus::HOT];

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

    public function LeadsPerformance(Request $request)
    {
        $user = Auth::user();
        $roleCode = $user?->role?->code;

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
            ->when($roleCode === 'sales', function ($q) use ($user) {
                $q->where('lead_claims.sales_id', $user?->id);
            })
            ->when($roleCode === 'branch_manager', function ($q) use ($user) {
                $q->where('leads.branch_id', $user?->branch_id);
            })
            ->when($request->source_id, function ($q) use ($request) {
                $q->where('leads.source_id', $request->source_id);
            })
            ->when($request->segment_id, function ($q) use ($request) {
                $q->where('leads.segment_id', $request->segment_id);
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

        $rows = $rows->filter(fn($r) => $r['total'] > 0)->values();

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

    public function PersonalTrend(Request $request)
    {
        $year = $request->year ?? now()->year;
        $month = $request->month;
        $monthFrom = $request->month_from;
        $monthTo = $request->month_to;

        $groupBy = 'month';

        // Determine grouping
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

        // Legacy implementation relied on a non-existent `sales` table.
        // When that table is missing, we derive personal trend data from
        // the same achievement logic used in `grid`, using the logged-in
        // user's monthly target and realized achievement per period.
        if (! Schema::hasTable('sales')) {
            $user = Auth::user();
            $monthlyTarget = $user && $user->target ? (float) $user->target : 0.0;

            $labels = [];
            $sales = [];
            $targets = [];

            if ($groupBy === 'month') {
                $from = $monthFrom ? (int) $monthFrom : 1;
                $to = $monthTo ? (int) $monthTo : 12;

                for ($m = $from; $m <= $to; $m++) {
                    $periodStart = Carbon::create($year, $m, 1)->startOfMonth()->toDateString();
                    $periodEnd = Carbon::create($year, $m, 1)->endOfMonth()->toDateString();

                    $amount = $this->calculateUserAchievementForPeriod($user, $periodStart, $periodEnd);

                    $labels[] = date('M', mktime(0, 0, 0, $m, 1));
                    $sales[] = (int) round($amount);
                    $targets[] = (int) round($monthlyTarget);
                }
            } elseif ($groupBy === 'quarter') {
                for ($q = 1; $q <= 4; $q++) {
                    $startMonth = 1 + (($q - 1) * 3);
                    $endMonth = $startMonth + 2;

                    $periodStart = Carbon::create($year, $startMonth, 1)->startOfMonth()->toDateString();
                    $periodEnd = Carbon::create($year, $endMonth, 1)->endOfMonth()->toDateString();

                    $amount = $this->calculateUserAchievementForPeriod($user, $periodStart, $periodEnd);

                    $labels[] = 'Q' . $q;
                    $sales[] = (int) round($amount);
                    $targets[] = (int) round($monthlyTarget * 3);
                }
            } else {
                // For weekly view (groupBy === 'week'), fall back to a
                // single aggregated point for the selected month.
                $monthVal = $month ?: now()->month;
                $periodStart = Carbon::create($year, $monthVal, 1)->startOfMonth()->toDateString();
                $periodEnd = Carbon::create($year, $monthVal, 1)->endOfMonth()->toDateString();

                $amount = $this->calculateUserAchievementForPeriod($user, $periodStart, $periodEnd);

                $labels[] = 'MTD';
                $sales[] = (int) round($amount);
                $targets[] = (int) round($monthlyTarget);
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
                'datasets' => [
                    [
                        'name' => 'Target',
                        'data' => $targets,
                    ],
                    [
                        'name' => 'Sales',
                        'data' => $sales,
                    ],
                ],
            ]);
        }

        $query = DB::table('sales')
            ->selectRaw('SUM(amount) as sales, SUM(target) as target');

        if ($groupBy == 'week') {

            $query->selectRaw('WEEK(created_at,1) - WEEK(DATE_SUB(created_at, INTERVAL DAYOFMONTH(created_at)-1 DAY),1) + 1 as label')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->groupBy('label');
        } elseif ($groupBy == 'month') {

            $query->selectRaw('MONTH(created_at) as label')
                ->whereYear('created_at', $year)
                ->whereBetween(DB::raw('MONTH(created_at)'), [$monthFrom, $monthTo])
                ->groupBy('label');
        } elseif ($groupBy == 'quarter') {

            $query->selectRaw('QUARTER(created_at) as label')
                ->whereYear('created_at', $year)
                ->groupBy('label');
        }
        $data = $query->orderBy('label')->get();
        $labels = [];
        $sales = [];
        $targets = [];

        foreach ($data as $row) {

            if ($groupBy == 'week') {
                $labels[] = "Week " . $row->label;
            }

            if ($groupBy == 'month') {
                $labels[] = date("M", mktime(0, 0, 0, $row->label, 1));
            }

            if ($groupBy == 'quarter') {
                $labels[] = "Q" . $row->label;
            }

            $sales[] = (int) $row->sales;
            $targets[] = (int) $row->target;
        }

        return response()->json([
            "status" => "success",
            "filter" => [
                "year" => $year,
                "month" => $month,
                "month_from" => $monthFrom,
                "month_to" => $monthTo
            ],
            "group_by" => $groupBy,
            "labels" => $labels,
            "datasets" => [
                [
                    "name" => "Target",
                    "data" => $targets
                ],
                [
                    "name" => "Sales",
                    "data" => $sales
                ]
            ]
        ]);
    }

    private function calculateUserAchievementForPeriod($user, string $startDate, string $endDate): float
    {
        $claims = LeadClaim::with(['lead.quotation.proformas.paymentConfirmation'])
            ->whereHas('lead', function ($q) {
                $q->where('status_id', LeadStatus::DEAL);
            })
            ->whereNull('released_at');

        $roleCode = $user?->role?->code;

        if ($roleCode === 'sales') {
            $claims->where('sales_id', $user?->id);
        } elseif ($roleCode === 'branch_manager') {
            $claims->whereHas('sales', function ($q) use ($user) {
                $q->where('branch_id', $user?->branch_id);
            });
        }

        // Use the same first-term payment window semantics as `grid`
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

            // Only count deals that have all proformas confirmed, as in `grid`.
            if ($totalPayments > 0 && $approvedPayments >= $totalPayments) {
                $monetaryActual += (float) $confirmedProformas->sum(function ($p) {
                    return (float) ($p->paymentConfirmation->amount ?? $p->amount ?? 0);
                });
            }
        }

        return round($monetaryActual, 2);
    }

    public function Summary(Request $request)
    {
        $user = Auth::user();
        $roleCode = $user?->role?->code;

        // Find lead ids owned by the user's active claims
        $claimsQuery = LeadClaim::whereNull('released_at');

        if ($roleCode === 'sales') {
            $claimsQuery->where('sales_id', $user?->id);
        } elseif ($roleCode === 'branch_manager') {
            $claimsQuery->whereHas('sales', function ($q) use ($user) {
                $q->where('branch_id', $user?->branch_id);
            });
        }

        $leadIds = $claimsQuery->pluck('lead_id')->filter()->unique()->values()->all();

        // Map of lowercase activity names to canonical labels we want in output
        $wantedMap = [
            'telepon pertama' => 'telpon_pertama',
            'visit scheduled' => 'visit_scheduled',
            'quotation sent' => 'quotation_sent',
        ];

        // Initialize counts with zero so missing activities return 0
        $activityCounts = array_fill_keys(array_values($wantedMap), 0);

        if (!empty($leadIds)) {
            $rows = DB::table('lead_activity_logs')
                ->join('lead_activity_lists', 'lead_activity_lists.id', '=', 'lead_activity_logs.activity_id')
                ->whereIn('lead_activity_logs.lead_id', $leadIds)
                ->whereNull('lead_activity_logs.deleted_at')
                ->whereIn(DB::raw('LOWER(lead_activity_lists.name)'), array_keys($wantedMap))
                ->select(DB::raw('LOWER(lead_activity_lists.name) as lname'), DB::raw('COUNT(DISTINCT lead_activity_logs.lead_id) as total'))
                ->groupBy('lname')
                ->get();

            foreach ($rows as $r) {
                $lname = $r->lname;
                if (isset($wantedMap[$lname])) {
                    $activityCounts[$wantedMap[$lname]] = (int) $r->total;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'Data' => $activityCounts,
        ]);
    }
}
