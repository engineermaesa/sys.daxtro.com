<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Leads\{LeadClaim, LeadStatus, Lead, LeadActivityLog};
use App\Models\Masters\Province;
use App\Models\Masters\Region;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LeadSummaryController extends Controller
{
    public function grid(Request $request)
    {
        $user = Auth::user();

        $nowJakarta = Carbon::now('Asia/Jakarta');
        $selectedPeriodStart = (clone $nowJakarta)->startOfMonth();
        $selectedPeriodEnd = (clone $nowJakarta)->endOfMonth();

        if ($request->filled('start_date_grid') && $request->filled('end_date_grid')) {
            try {
                $selectedPeriodStart = Carbon::createFromFormat('Y-m-d', (string) $request->input('start_date_grid'), 'Asia/Jakarta')->startOfDay();
                $selectedPeriodEnd = Carbon::createFromFormat('Y-m-d', (string) $request->input('end_date_grid'), 'Asia/Jakarta')->endOfDay();

                if ($selectedPeriodStart->gt($selectedPeriodEnd)) {
                    [$selectedPeriodStart, $selectedPeriodEnd] = [$selectedPeriodEnd, $selectedPeriodStart];
                    $selectedPeriodStart = $selectedPeriodStart->startOfDay();
                    $selectedPeriodEnd = $selectedPeriodEnd->endOfDay();
                }
            } catch (\Throwable $e) {
                $selectedPeriodStart = (clone $nowJakarta)->startOfMonth();
                $selectedPeriodEnd = (clone $nowJakarta)->endOfMonth();
            }
        }

        $monthKey = (string) $selectedPeriodStart->month;
        $yearKey = (int) $selectedPeriodStart->year;
        $periodStart = $selectedPeriodStart->toDateTimeString();
        $periodEnd = $selectedPeriodEnd->toDateTimeString();
        
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

        $isInSelectedPeriod = function ($date) use ($selectedPeriodStart, $selectedPeriodEnd): bool {
            if (empty($date)) {
                return false;
            }

            $d = Carbon::parse($date, 'Asia/Jakarta');

            // Sinkronkan achievement ke rentang tanggal grid yang aktif
            return $d->between($selectedPeriodStart, $selectedPeriodEnd);
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

        // Force closed deal calculation to selected month/year window.
        $claims->whereHas('lead.quotation', function ($q) use ($periodStart, $periodEnd) {
            $q->firstTermPaidBetween($periodStart, $periodEnd);
        });

        $completedDeals = 0;
        $monetaryActual = 0;
        $leadsActual = 0;
        $visitsActual = 0;

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
            // && $approvedPayments >= $totalPayments (gunain ini kalau mau full payment)


            // Only count deals that have all proformas confirmed.
            if ($totalPayments > 0 ) {
                $completedDeals++;

                // Achievement amount khusus pembayaran yang confirmed di periode grid
                $monthlyConfirmed = $confirmedProformas->filter(function ($p) use ($isInSelectedPeriod) {
                    return $isInSelectedPeriod($p->paymentConfirmation->confirmed_at ?? null);
                });

                $monetaryActual += (float) $monthlyConfirmed->sum(function ($p) {
                    return (float) ($p->paymentConfirmation->amount ?? $p->amount ?? 0);
                });
            }
        }

        // Align with BM summary: count unique leads that were claimed in the
        // selected month, but scoped only to the logged-in sales user.
        $leadsQuery = Lead::query()
            ->where('branch_id', $user?->branch_id)
            ->whereHas('claims', function ($cq) use ($periodStart, $periodEnd, $user) {
                $cq->whereBetween('claimed_at', [$periodStart, $periodEnd])
                    ->where('sales_id', $user?->id)
                    ->whereHas('user', function ($uq) use ($user) {
                        $uq->where('role_id', $user?->role_id)
                            ->where('branch_id', $user?->branch_id);
                    });
            });

        $leadsActual = $leadsQuery->distinct('id')->count('id');

        // Align with BM summary: count unique visit leads claimed in the
        // selected month, but scoped only to the logged-in sales user.
        $visitsQuery = Lead::query()
            ->where('branch_id', $user?->branch_id)
            ->where('source_id', 9)
            ->whereHas('claims', function ($cq) use ($periodStart, $periodEnd, $user) {
                $cq->whereBetween('claimed_at', [$periodStart, $periodEnd])
                    ->where('sales_id', $user?->id)
                    ->whereHas('user', function ($uq) use ($user) {
                        $uq->where('role_id', $user?->role_id)
                            ->where('branch_id', $user?->branch_id);
                    });
            });

        $visitsActual = $visitsQuery->distinct('id')->count('id');

        $monetaryActual = round($monetaryActual, 2);
        $achievementPercentage = $target_amount > 0
            ? round(($monetaryActual / $target_amount) * 100, 2)
            : 0;

        $closedDeals = $completedDeals;
        $closedAmount = round($monetaryActual, 2);

        // Potential dealing uses the same selected month/year window for consistent conversion rate denominator.
        $start = $periodStart;
        $end = $periodEnd;

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
            ->whereHas('quotation.proformas.paymentConfirmation', function ($q) use ($start, $end) {
                $q->whereNotNull('confirmed_at')
                    ->whereBetween('confirmed_at', [$start, $end]);
            });

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
        $branchId = $user?->branch_id;
        $salesId = $user?->id;

        $claimedQuery = DB::table('lead_claims')
            ->join('leads', 'lead_claims.lead_id', '=', 'leads.id')
            ->whereBetween('lead_claims.claimed_at', [$periodStart, $periodEnd])
            ->whereIn('leads.status_id', [2, 3, 4, 5])
            ->whereNull('lead_claims.trash_note')
            ->whereNull('lead_claims.released_at')
            ->when(!empty($branchId) && empty($salesId), function ($q) use ($branchId) {
                $q->where('leads.branch_id', $branchId);
            })
            ->when(!empty($salesId), function ($q) use ($salesId) {
                $q->where('lead_claims.sales_id', $salesId);
            })
            ->selectRaw("
                leads.id AS lead_id,
                lead_claims.id AS claim_id,
                lead_claims.sales_id,
                lead_claims.claimed_at AS activity_date,
                leads.published_at,
                leads.status_id,
                leads.branch_id,
                'claim' AS source_type
            ");

        $publishedQuery = DB::table('leads')
            ->whereBetween('leads.published_at', [$periodStart, $periodEnd])
            ->where('leads.status_id', 1)
            ->when(!empty($branchId) && empty($salesId), function ($q) use ($branchId) {
                $q->where('leads.branch_id', $branchId);
            })
            ->selectRaw("
                leads.id AS lead_id,
                NULL AS claim_id,
                NULL AS sales_id,
                NULL AS activity_date,
                leads.published_at,
                leads.status_id,
                leads.branch_id,
                'published' AS source_type
            ");

        $unionRows = $claimedQuery
            ->unionAll($publishedQuery)
            ->get();

        $leadIds = $unionRows->pluck('lead_id')->filter()->unique()->values();

        $uniqueLeads = Lead::query()
            ->whereIn('id', $leadIds)
            ->get()
            ->unique('id');

        // Group unique leads by their status and count per status
        $counts = $uniqueLeads->groupBy('status_id')->map->count();

        $published = (int) ($counts[LeadStatus::PUBLISHED] ?? 0);
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
            'published' => $published,
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
        abort_if($roleCode !== 'sales', 403);

        $validated = $request->validate([
            'source_id' => 'nullable|integer|exists:lead_sources,id',
        ]);

        $salesId = $user?->id;
        $sourceId = $validated['source_id'] ?? null;
        $startDate = $request->filled('start_date') ? (string) $request->start_date : null;
        $endDate = $request->filled('end_date') ? (string) $request->end_date : null;

        if ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $periodStart = $startDate ? Carbon::parse($startDate)->startOfDay()->toDateTimeString() : null;
        $periodEnd = $endDate ? Carbon::parse($endDate)->endOfDay()->toDateTimeString() : null;

        $perPage = $request->get('per_page', 5);

        $query = LeadClaim::query()
            ->join('leads', 'lead_claims.lead_id', '=', 'leads.id')
            ->join('lead_statuses', 'leads.status_id', '=', 'lead_statuses.id')
            ->with([
                'lead.product',
                'lead.segment',
                'lead.branch',
                'lead.latestStatusLog',
                'lead.quotation.items.product',
                'lead.status',
                'sales',
                'sales.branch',
            ])
            ->whereNull('lead_claims.released_at')
            ->whereNull('lead_claims.deleted_at')
            ->whereNull('lead_claims.trash_note')
            ->whereIn('lead_statuses.id', [
                LeadStatus::COLD,
                LeadStatus::WARM,
                LeadStatus::HOT,
                LeadStatus::DEAL,
            ])
            ->where('lead_claims.sales_id', $salesId)
            ->when($periodStart && $periodEnd, function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('lead_claims.claimed_at', [$periodStart, $periodEnd]);
            })
            ->when($periodStart && ! $periodEnd, function ($q) use ($periodStart) {
                $q->where('lead_claims.claimed_at', '>=', $periodStart);
            })
            ->when(! $periodStart && $periodEnd, function ($q) use ($periodEnd) {
                $q->where('lead_claims.claimed_at', '<=', $periodEnd);
            })
            ->when(! empty($sourceId), function ($q) use ($sourceId) {
                $q->where('leads.source_id', $sourceId);
            })
            ->when($request->filled('stage'), function ($q) use ($request) {
                $q->where('leads.status_id', $request->stage);
            })
            ->when($request->filled('segment'), function ($q) use ($request) {
                $q->where('leads.segment_id', $request->segment);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('leads.name', 'like', "%{$search}%")
                        ->orWhere('leads.phone', 'like', "%{$search}%")
                        ->orWhere('leads.company', 'like', "%{$search}%");
                });
            });

        // =========================
        // GET LATEST CLAIM PER LEAD
        // =========================
        $query->whereIn('lead_claims.id', function ($q) {
            $q->select(DB::raw('MAX(id)'))
                ->from('lead_claims as lc2')
                ->whereNull('lc2.released_at')
                ->whereNull('lc2.deleted_at')
                ->whereColumn('lc2.lead_id', 'lead_claims.lead_id')
                ->groupBy('lead_id');
        })->select('lead_claims.*');

        $amountQuery = clone $query;

        $totalAmount = $amountQuery->get()->sum(function ($claim) {
            return (float) ($claim->lead->quotation->grand_total ?? 0);
        });

        $paginated = $query->orderByDesc('lead_claims.id')->paginate($perPage);

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

            $needs = format_needs_label($lead->needs ?? $product ?? null);
            $salesName = $claim->sales?->name ?? null;
            $branchName = $claim->sales?->branch?->name
                ?? $lead->branch?->name
                ?? null;

            $lastActivity = $lead->latestStatusLog?->created_at
                ?? $lead->updated_at;

            $validationChecks = [
                'contact_info' => !empty($lead->phone) || !empty($lead->email),
                'business_reason' => !empty($lead->business_reason),
                'quotation_exists' => !empty($lead->quotation?->quotation_no) || !empty($lead->quotation_no) || !empty($lead->tonase),
                'quotation_amount' => (
                    (!empty($lead->quotation?->grand_total) && $lead->quotation->grand_total > 0)
                    || (!empty($lead->tonase) && floatval($lead->tonase) > 0)
                ),
                'regional_info' => !empty($lead->region_id) || !empty($lead->province) || !empty($lead->factory_city_id),
                'product_info' => !empty($lead->product_id) || !empty($lead->needs) || !empty($lead->product),
            ];

            $passed = count(array_filter($validationChecks));

            if ($passed >= 5) {
                $dataValidation = 'Complete';
            } elseif ($passed === 4) {
                $dataValidation = 'Moderate';
            } else {
                $dataValidation = 'Incomplete';
            }

            $failedKeys = array_keys(array_filter($validationChecks, function ($v) {
                return ! $v;
            }));

            return [
                'id' => $lead->id,
                'customer_name' => $lead->name ?? $lead->company,
                'stage' => $stage,
                'amount' => $amount,
                'product' => $product,
                'needs' => $needs,
                'segment' => $segment,
                'branch' => $branchName,
                'sales' => $salesName,
                'sales_name' => $salesName,
                'data_status' => $passed . '/6',
                'last_activity' => $lastActivity?->toDateTimeString(),
                'data_validation' => $dataValidation,
                'validation' => $validationChecks,
                'missing_fields' => $failedKeys,
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
        $search = trim((string) $request->input('search', ''));
        $sourceStartDate = $request->input('start_date_source');
        $sourceEndDate = $request->input('end_date_source');
        $segmentStartDate = $request->input('start_date_segment');
        $segmentEndDate = $request->input('end_date_segment');

        $latestQuotationSubquery = DB::table('quotations')
            ->select('lead_id', DB::raw('MAX(created_at) as latest_date'))
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->groupBy('lead_id');

        $buildPerformanceQuery = function () use ($latestQuotationSubquery, $roleCode, $user, $search) {
            return Lead::leftJoin('lead_sources', 'lead_sources.id', '=', 'leads.source_id')
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
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('lead_sources.name', 'like', "%{$search}%")
                            ->orWhere('lead_segments.name', 'like', "%{$search}%")
                            ->orWhere('leads.customer_type', 'like', "%{$search}%");
                    });
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
                );
        };

        $mapPerformanceRows = function ($query, string $orderBy) {
            return $query
                ->orderBy($orderBy)
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
                })
                ->filter(fn($r) => $r['total'] > 0)
                ->values();
        };

        $sourceRows = $mapPerformanceRows(
            $buildPerformanceQuery()
                ->when($request->source_id, function ($q) use ($request) {
                    $q->where('leads.source_id', $request->source_id);
                })
                ->when($sourceStartDate && $sourceEndDate, function ($q) use ($sourceStartDate, $sourceEndDate) {
                    $q->whereDate('leads.created_at', '>=', $sourceStartDate)
                        ->whereDate('leads.created_at', '<=', $sourceEndDate);
                }),
            'lead_sources.name'
        );

        $segmentRows = $mapPerformanceRows(
            $buildPerformanceQuery()
                ->when($request->segment_id, function ($q) use ($request) {
                    $q->where('leads.segment_id', $request->segment_id);
                })
                ->when($segmentStartDate && $segmentEndDate, function ($q) use ($segmentStartDate, $segmentEndDate) {
                    $q->whereDate('leads.created_at', '>=', $segmentStartDate)
                        ->whereDate('leads.created_at', '<=', $segmentEndDate);
                }),
            'lead_sources.name'
        );

        $bySource = $sourceRows->groupBy('source')->map(function ($items, $source) {

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

        $bySegment = $segmentRows->groupBy('segment')->map(function ($items, $segment) {

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

    public function leadVolume(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'top_province' => 'nullable|string',
            'top_region' => 'nullable|string',
            'top_search' => 'nullable|string',
            'top_page' => 'nullable|integer|min:1',
            'top_per_page' => 'nullable|integer|min:1|max:100',
            'coverage_province' => 'nullable|integer|exists:ref_provinces,id',
            'coverage_region' => 'nullable|integer|exists:ref_regions,id',
            'coverage_search' => 'nullable|string',
            'coverage_page' => 'nullable|integer|min:1',
            'coverage_per_page' => 'nullable|integer|min:1|max:100',
            'mapping_branch' => 'nullable|integer|exists:ref_branches,id',
            'mapping_province' => 'nullable|integer|exists:ref_provinces,id',
            'mapping_region' => 'nullable|integer|exists:ref_regions,id',
            'mapping_search' => 'nullable|string',
            'mapping_page' => 'nullable|integer|min:1',
            'mapping_per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $branchId = $user->branch_id;
        $salesId = $user->id;
        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;

        $provinceExpression = $this->regionalReachProvinceExpression();
        $branchExpression = $this->regionalReachBranchExpression();
        $leadBaseQuery = $this->buildRegionalReachLeadBaseQuery($branchId, $salesId, $startDate, $endDate);

        $topSummary = (clone $leadBaseQuery)
            ->whereRaw($provinceExpression . ' IS NOT NULL')
            ->selectRaw($provinceExpression . ' as province, COUNT(*) as total_leads')
            ->groupBy(DB::raw($provinceExpression))
            ->orderByDesc('total_leads')
            ->orderBy('province')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'province' => $row->province,
                    'total_leads' => (int) $row->total_leads,
                ];
            })
            ->values();

        $topProvinceNames = $topSummary->pluck('province')->filter()->values()->all();
        $topProvinceFilter = $validated['top_province'] ?? null;
        $topRegionFilter = $validated['top_region'] ?? null;
        $topSearch = $validated['top_search'] ?? null;
        $topPage = (int) ($validated['top_page'] ?? 1);
        $topPerPage = $this->resolveRegionalReachPerPage($validated['top_per_page'] ?? 10);

        $topItemsQuery = (clone $leadBaseQuery)
            ->whereRaw($provinceExpression . ' IS NOT NULL');

        if (empty($topProvinceNames)) {
            $topItemsQuery->whereRaw('1 = 0');
        } else {
            $topItemsQuery->whereIn(DB::raw($provinceExpression), $topProvinceNames);
        }

        if (!empty($topProvinceFilter)) {
            $topItemsQuery->whereRaw($provinceExpression . ' = ?', [$topProvinceFilter]);
        }

        if (!empty($topRegionFilter)) {
            $topItemsQuery->where('ref_regions.name', $topRegionFilter);
        }

        if (!empty($topSearch)) {
            $this->applyRegionalReachLeadSearch($topItemsQuery, $topSearch, $provinceExpression, $branchExpression);
        }

        $topPaginator = $topItemsQuery
            ->select([
                'leads.id as lead_id',
                'leads.name',
                'leads.company',
                'leads.needs',
                'leads.created_at',
                'lead_claims.claimed_at',
                'sales_users.name as sales_name',
                'ref_regions.name as region_name',
                'lead_sources.name as source_name',
                'lead_statuses.name as lead_stage',
                DB::raw($provinceExpression . ' as province_name'),
                DB::raw($branchExpression . ' as branch_name'),
            ])
            ->orderByDesc('lead_claims.id')
            ->paginate($topPerPage, ['*'], 'top_page', $topPage);

        $topRegionFiltersQuery = (clone $leadBaseQuery)
            ->whereRaw($provinceExpression . ' IS NOT NULL');

        if (empty($topProvinceNames)) {
            $topRegionFiltersQuery->whereRaw('1 = 0');
        } else {
            $topRegionFiltersQuery->whereIn(DB::raw($provinceExpression), $topProvinceNames);
        }

        if (!empty($topProvinceFilter)) {
            $topRegionFiltersQuery->whereRaw($provinceExpression . ' = ?', [$topProvinceFilter]);
        }

        $topTenProvinces = [
            'summary' => $topSummary->all(),
            'items' => collect($topPaginator->items())
                ->map(function ($row) {
                    return $this->mapRegionalReachLeadRow($row, false);
                })
                ->values()
                ->all(),
            'pagination' => $this->formatRegionalReachPagination($topPaginator),
            'filters' => [
                'provinces' => $topSummary->map(function (array $row) {
                    return [
                        'value' => $row['province'],
                        'label' => $row['province'],
                    ];
                })->values()->all(),
                'regions' => $topRegionFiltersQuery
                    ->whereNotNull('ref_regions.name')
                    ->whereRaw("TRIM(ref_regions.name) <> ''")
                    ->select('ref_regions.name as name')
                    ->distinct()
                    ->orderBy('name')
                    ->get()
                    ->map(function ($row) {
                        return [
                            'value' => $row->name,
                            'label' => $row->name,
                        ];
                    })
                    ->values()
                    ->all(),
            ],
        ];

        $coverageBaseQuery = (clone $leadBaseQuery)
            ->whereNotNull('leads.region_id')
            ->whereNotNull('ref_regions.province_id');

        $coverageProvinceFilter = $validated['coverage_province'] ?? null;
        $coverageRegionFilter = $validated['coverage_region'] ?? null;
        $coverageSearch = $validated['coverage_search'] ?? null;
        $coveragePage = (int) ($validated['coverage_page'] ?? 1);
        $coveragePerPage = $this->resolveRegionalReachPerPage($validated['coverage_per_page'] ?? 10);

        $coverageSummaryQuery = clone $coverageBaseQuery;
        $coverageItemsQuery = clone $coverageBaseQuery;

        if (!empty($coverageProvinceFilter)) {
            $coverageItemsQuery->where('ref_provinces.id', $coverageProvinceFilter);
        }

        if (!empty($coverageRegionFilter)) {
            $coverageItemsQuery->where('ref_regions.id', $coverageRegionFilter);
        }

        if (!empty($coverageSearch)) {
            $this->applyRegionalReachLeadSearch($coverageItemsQuery, $coverageSearch, $provinceExpression, $branchExpression);
        }

        $coveragePaginator = $coverageItemsQuery
            ->select([
                'leads.id as lead_id',
                'leads.name',
                'leads.company',
                'leads.needs',
                'leads.created_at',
                'lead_claims.claimed_at',
                'sales_users.name as sales_name',
                'ref_regions.id as region_id',
                'ref_regions.name as region_name',
                'ref_provinces.id as province_id',
                'ref_provinces.name as province_name',
                'lead_sources.name as source_name',
                'lead_statuses.name as lead_stage',
                DB::raw($branchExpression . ' as branch_name'),
            ])
            ->orderByDesc('lead_claims.claimed_at')
            ->orderByDesc('lead_claims.id')
            ->paginate($coveragePerPage, ['*'], 'coverage_page', $coveragePage);

        $coverageRegionFiltersQuery = clone $coverageSummaryQuery;
        if (!empty($coverageProvinceFilter)) {
            $coverageRegionFiltersQuery->where('ref_provinces.id', $coverageProvinceFilter);
        }

        $scopedMappingBranchId = $this->resolveRegionalReachScopeBranchId($branchId, $salesId);

        $coverageTotalsQuery = Region::query();
        if (!empty($scopedMappingBranchId)) {
            $coverageTotalsQuery->where('branch_id', $scopedMappingBranchId);
        }

        $coverage = [
            'total_provinces' => !empty($scopedMappingBranchId)
                ? (clone $coverageTotalsQuery)->whereNotNull('province_id')->distinct()->count('province_id')
                : Province::query()->count(),
            'reached_provinces' => (clone $coverageSummaryQuery)->distinct()->count('ref_regions.province_id'),
            'total_cities' => !empty($scopedMappingBranchId)
                ? (clone $coverageTotalsQuery)->count()
                : Region::query()->count(),
            'reached_cities' => (clone $coverageSummaryQuery)->distinct()->count('ref_regions.id'),
            'items' => collect($coveragePaginator->items())
                ->map(function ($row) {
                    return $this->mapRegionalReachLeadRow($row, true);
                })
                ->values()
                ->all(),
            'pagination' => $this->formatRegionalReachPagination($coveragePaginator),
            'filters' => [
                'provinces' => (clone $coverageSummaryQuery)
                    ->whereNotNull('ref_provinces.id')
                    ->select('ref_provinces.id', 'ref_provinces.name')
                    ->distinct()
                    ->orderBy('ref_provinces.name')
                    ->get()
                    ->map(function ($row) {
                        return [
                            'id' => (int) $row->id,
                            'name' => $row->name,
                        ];
                    })
                    ->values()
                    ->all(),
                'regions' => $coverageRegionFiltersQuery
                    ->select('ref_regions.id', 'ref_regions.name')
                    ->distinct()
                    ->orderBy('ref_regions.name')
                    ->get()
                    ->map(function ($row) {
                        return [
                            'id' => (int) $row->id,
                            'name' => $row->name,
                        ];
                    })
                    ->values()
                    ->all(),
            ],
        ];

        $mappingBaseQuery = Region::query()
            ->join('ref_branches', 'ref_branches.id', '=', 'ref_regions.branch_id')
            ->leftJoin('ref_provinces', 'ref_provinces.id', '=', 'ref_regions.province_id');

        if (!empty($scopedMappingBranchId)) {
            $mappingBaseQuery->where('ref_regions.branch_id', $scopedMappingBranchId);
        }

        $mappingBranchFilter = $validated['mapping_branch'] ?? null;
        $mappingProvinceFilter = $validated['mapping_province'] ?? null;
        $mappingRegionFilter = $validated['mapping_region'] ?? null;
        $mappingSearch = $validated['mapping_search'] ?? null;
        $mappingPage = (int) ($validated['mapping_page'] ?? 1);
        $mappingPerPage = $this->resolveRegionalReachPerPage($validated['mapping_per_page'] ?? 10);

        $mappingSummaryQuery = clone $mappingBaseQuery;
        $mappingItemsQuery = clone $mappingBaseQuery;

        if (!empty($mappingBranchFilter)) {
            $mappingItemsQuery->where('ref_regions.branch_id', $mappingBranchFilter);
        }

        if (!empty($mappingProvinceFilter)) {
            $mappingItemsQuery->where('ref_regions.province_id', $mappingProvinceFilter);
        }

        if (!empty($mappingRegionFilter)) {
            $mappingItemsQuery->where('ref_regions.id', $mappingRegionFilter);
        }

        if (!empty($mappingSearch)) {
            $like = '%' . trim($mappingSearch) . '%';
            $mappingItemsQuery->where(function ($q) use ($like) {
                $q->where('ref_branches.name', 'like', $like)
                    ->orWhere('ref_regions.name', 'like', $like)
                    ->orWhere('ref_provinces.name', 'like', $like);
            });
        }

        $mappingPaginator = $mappingItemsQuery
            ->select([
                'ref_branches.id as branch_id',
                'ref_branches.name as branch_name',
                'ref_provinces.id as province_id',
                'ref_provinces.name as province_name',
                'ref_regions.id as region_id',
                'ref_regions.name as region_name',
            ])
            ->orderBy('ref_branches.name')
            ->orderBy('ref_provinces.name')
            ->orderBy('ref_regions.name')
            ->paginate($mappingPerPage, ['*'], 'mapping_page', $mappingPage);

        $mappingProvinceFiltersQuery = clone $mappingSummaryQuery;
        if (!empty($mappingBranchFilter)) {
            $mappingProvinceFiltersQuery->where('ref_regions.branch_id', $mappingBranchFilter);
        }

        $mappingRegionFiltersQuery = clone $mappingProvinceFiltersQuery;
        if (!empty($mappingProvinceFilter)) {
            $mappingRegionFiltersQuery->where('ref_regions.province_id', $mappingProvinceFilter);
        }

        $branchMapping = [
            'total_branches' => (clone $mappingSummaryQuery)->distinct()->count('ref_regions.branch_id'),
            'total_regions' => (clone $mappingSummaryQuery)->count(),
            'items' => collect($mappingPaginator->items())
                ->map(function ($row) {
                    return [
                        'branch_id' => (int) $row->branch_id,
                        'branch_name' => $row->branch_name,
                        'province_id' => !empty($row->province_id) ? (int) $row->province_id : null,
                        'province_name' => $row->province_name ?? '-',
                        'region_id' => (int) $row->region_id,
                        'region_name' => $row->region_name,
                    ];
                })
                ->values()
                ->all(),
            'pagination' => $this->formatRegionalReachPagination($mappingPaginator),
            'filters' => [
                'branches' => (clone $mappingSummaryQuery)
                    ->select('ref_branches.id', 'ref_branches.name')
                    ->distinct()
                    ->orderBy('ref_branches.name')
                    ->get()
                    ->map(function ($row) {
                        return [
                            'id' => (int) $row->id,
                            'name' => $row->name,
                        ];
                    })
                    ->values()
                    ->all(),
                'provinces' => $mappingProvinceFiltersQuery
                    ->whereNotNull('ref_provinces.id')
                    ->select('ref_provinces.id', 'ref_provinces.name')
                    ->distinct()
                    ->orderBy('ref_provinces.name')
                    ->get()
                    ->map(function ($row) {
                        return [
                            'id' => (int) $row->id,
                            'name' => $row->name,
                        ];
                    })
                    ->values()
                    ->all(),
                'regions' => $mappingRegionFiltersQuery
                    ->select('ref_regions.id', 'ref_regions.name')
                    ->distinct()
                    ->orderBy('ref_regions.name')
                    ->get()
                    ->map(function ($row) {
                        return [
                            'id' => (int) $row->id,
                            'name' => $row->name,
                        ];
                    })
                    ->values()
                    ->all(),
            ],
        ];

        return response()->json([
            'status' => 'success',
            'top_10_provinces' => $topTenProvinces,
            'coverage' => $coverage,
            'branch_mapping' => $branchMapping,
            'data' => $topTenProvinces['summary'],
        ]);
    }

    private function buildRegionalReachLeadBaseQuery(?int $branchId, ?int $salesId, ?string $startDate, ?string $endDate)
    {
        $query = LeadClaim::query()
            ->join('leads', 'lead_claims.lead_id', '=', 'leads.id')
            ->join('lead_statuses', 'leads.status_id', '=', 'lead_statuses.id')
            ->leftJoin('users as sales_users', 'sales_users.id', '=', 'lead_claims.sales_id')
            ->leftJoin('ref_regions', 'ref_regions.id', '=', 'leads.region_id')
            ->leftJoin('ref_provinces', 'ref_provinces.id', '=', 'ref_regions.province_id')
            ->leftJoin('ref_branches as region_branches', 'region_branches.id', '=', 'ref_regions.branch_id')
            ->leftJoin('ref_branches as lead_branches', 'lead_branches.id', '=', 'leads.branch_id')
            ->leftJoin('lead_sources', 'lead_sources.id', '=', 'leads.source_id')
            ->whereIn('lead_statuses.id', [LeadStatus::COLD, LeadStatus::WARM, LeadStatus::HOT, LeadStatus::DEAL])
            ->whereNull('lead_claims.trash_note')
            ->where('sales_users.branch_id', $branchId)
            ->when(!empty($salesId), function ($q) use ($salesId) {
                $q->where('lead_claims.sales_id', $salesId);
            });

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('lead_claims.claimed_at', [
                Carbon::parse($startDate)->startOfDay()->toDateTimeString(),
                Carbon::parse($endDate)->endOfDay()->toDateTimeString(),
            ]);
        } elseif (!empty($startDate)) {
            $query->where('lead_claims.claimed_at', '>=', Carbon::parse($startDate)->startOfDay()->toDateTimeString());
        } elseif (!empty($endDate)) {
            $query->where('lead_claims.claimed_at', '<=', Carbon::parse($endDate)->endOfDay()->toDateTimeString());
        }

        return $query;
    }

    private function resolveRegionalReachScopeBranchId(?int $branchId, ?int $salesId): ?int
    {
        return !empty($branchId) ? (int) $branchId : null;
    }

    private function regionalReachProvinceExpression(): string
    {
        return "COALESCE(NULLIF(TRIM(ref_provinces.name), ''), NULLIF(TRIM(leads.province), ''))";
    }

    private function regionalReachBranchExpression(): string
    {
        return "COALESCE(NULLIF(TRIM(lead_branches.name), ''), NULLIF(TRIM(region_branches.name), ''))";
    }

    private function applyRegionalReachLeadSearch($query, string $search, string $provinceExpression, string $branchExpression): void
    {
        $like = '%' . trim($search) . '%';

        $query->where(function ($q) use ($like, $provinceExpression, $branchExpression) {
            $q->where('leads.name', 'like', $like)
                ->orWhere('leads.company', 'like', $like)
                ->orWhere('leads.needs', 'like', $like)
                ->orWhere('sales_users.name', 'like', $like)
                ->orWhere('lead_sources.name', 'like', $like)
                ->orWhere('lead_statuses.name', 'like', $like)
                ->orWhere('ref_regions.name', 'like', $like)
                ->orWhereRaw($provinceExpression . ' LIKE ?', [$like])
                ->orWhereRaw($branchExpression . ' LIKE ?', [$like]);
        });
    }

    private function mapRegionalReachLeadRow($row, bool $includeIds = true): array
    {
        $item = [
            'lead_id' => (int) $row->lead_id,
            'lead_name' => $row->name ?: $row->company ?: '-',
            'branch_name' => $row->branch_name ?? '-',
            'sales_name' => $row->sales_name ?? '-',
            'region_name' => $row->region_name ?? '-',
            'province_name' => $row->province_name ?? '-',
            'needs' => format_needs_label($row->needs ?? null),
            'source' => $row->source_name ?? '-',
            'lead_stage' => $row->lead_stage ?? '-',
            'created_at' => $row->created_at,
            'claimed_at' => $row->claimed_at,
        ];

        if ($includeIds) {
            $item['region_id'] = !empty($row->region_id) ? (int) $row->region_id : null;
            $item['province_id'] = !empty($row->province_id) ? (int) $row->province_id : null;
        } else {
            $item['province'] = $item['province_name'];
            $item['region'] = $item['region_name'];
        }

        return $item;
    }

    private function resolveRegionalReachPerPage($value, int $default = 10): int
    {
        $perPage = (int) $value;

        if ($perPage <= 0) {
            return $default;
        }

        return min($perPage, 100);
    }

    private function formatRegionalReachPagination($paginator): array
    {
        return [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem() ?? 0,
            'to' => $paginator->lastItem() ?? 0,
        ];
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
            $getUserTargetAmountByMonth = function ($user, int $month): float {
                $raw = (string) ($user?->target ?? '');
                if ($raw === '') {
                    return 0.0;
                }

                [$default, $jsonPart] = array_pad(explode('|', $raw, 2), 2, null);

                if (!empty($jsonPart)) {
                    $decoded = json_decode($jsonPart, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $monthData = $decoded[(string) $month] ?? $decoded[$month] ?? null;
                        $amount = is_array($monthData) ? ($monthData['amount'] ?? null) : null;

                        if (is_numeric($amount)) {
                            return (float) $amount;
                        }
                    }
                }

                // Fallback ke angka sebelum separator '|'
                return is_numeric($default) ? (float) $default : 0.0;
            };

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
                    $targetAmount = $getUserTargetAmountByMonth($user, $m);

                    $labels[] = date('M', mktime(0, 0, 0, $m, 1));
                    $sales[] = (int) round($amount);
                    $targets[] = (int) round($targetAmount);
                }
            } elseif ($groupBy === 'quarter') {
                for ($q = 1; $q <= 4; $q++) {
                    $startMonth = 1 + (($q - 1) * 3);
                    $endMonth = $startMonth + 2;

                    $periodStart = Carbon::create($year, $startMonth, 1)->startOfMonth()->toDateString();
                    $periodEnd = Carbon::create($year, $endMonth, 1)->endOfMonth()->toDateString();

                    $amount = $this->calculateUserAchievementForPeriod($user, $periodStart, $periodEnd);
                    $quarterTarget = 0.0;
                    for ($m = $startMonth; $m <= $endMonth; $m++) {
                        $quarterTarget += $getUserTargetAmountByMonth($user, $m);
                    }

                    $labels[] = 'Q' . $q;
                    $sales[] = (int) round($amount);
                    $targets[] = (int) round($quarterTarget);
                }
            } else {
                // For weekly view (groupBy === 'week'), fall back to a
                // single aggregated point for the selected month.
                $monthVal = $month ?: now()->month;
                $periodStart = Carbon::create($year, $monthVal, 1)->startOfMonth()->toDateString();
                $periodEnd = Carbon::create($year, $monthVal, 1)->endOfMonth()->toDateString();

                $amount = $this->calculateUserAchievementForPeriod($user, $periodStart, $periodEnd);
                $targetAmount = $getUserTargetAmountByMonth($user, (int) $monthVal);

                $labels[] = 'MTD';
                $sales[] = (int) round($amount);
                $targets[] = (int) round($targetAmount);
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
