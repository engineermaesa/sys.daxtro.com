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
use App\Models\Masters\Agent;
use App\Models\Masters\Province;
use App\Models\Masters\Region;
use Carbon\Carbon;


class BMSummaryController extends Controller
{

    public function grid(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (empty($user->branch_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Branch manager branch is not configured.',
            ], 422);
        }

        $validated = $request->validate([
            'sales_id' => 'nullable|integer|exists:users,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'start_date_grid' => 'nullable|date_format:Y-m-d',
            'end_date_grid' => 'nullable|date_format:Y-m-d',
            'compare_start_date' => 'nullable|date_format:Y-m-d',
            'compare_end_date' => 'nullable|date_format:Y-m-d|after_or_equal:compare_start_date',
        ]);

        $branchId = $user->branch_id;
        $salesId = $this->resolveBranchManagerAgentSummarySalesId(
            $validated['sales_id'] ?? ($validated['user_id'] ?? null),
            (int) $branchId
        );

        $nowJakarta = Carbon::now('Asia/Jakarta');
        $selectedPeriodStart = (clone $nowJakarta)->startOfMonth();
        $selectedPeriodEnd = (clone $nowJakarta)->endOfMonth();

        if (!empty($validated['start_date_grid']) && !empty($validated['end_date_grid'])) {
            try {
                $selectedPeriodStart = Carbon::createFromFormat('Y-m-d', (string) $validated['start_date_grid'], 'Asia/Jakarta')->startOfDay();
                $selectedPeriodEnd = Carbon::createFromFormat('Y-m-d', (string) $validated['end_date_grid'], 'Asia/Jakarta')->endOfDay();

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

        $allSalesUsers = User::query()
            ->whereHas('role', function ($q) {
                $q->where('code', 'sales');
            })
            ->where('branch_id', $branchId)
            ->when($salesId, function ($q) use ($salesId) {
                $q->where('id', $salesId);
            })
            ->get();

        $targetAmount = $allSalesUsers->sum(function (User $u) use ($getMonthlyTarget, $monthKey) {
            return $getMonthlyTarget($u->target ?? null, 'amount', $monthKey);
        });

        $targetLead = $allSalesUsers->sum(function (User $u) use ($getMonthlyTarget, $monthKey) {
            return $getMonthlyTarget($u->target_leads ?? null, 'leads', $monthKey);
        });

        $targetVisit = $allSalesUsers->sum(function (User $u) use ($getMonthlyTarget, $monthKey) {
            return $getMonthlyTarget($u->target_visit ?? $u->target_visits ?? null, 'visits', $monthKey);
        });

        $claims = LeadClaim::with(['lead.quotation.proformas.paymentConfirmation'])
            ->whereHas('lead', function ($q) {
                $q->where('status_id', LeadStatus::DEAL);
            })
            ->whereNull('released_at')
            ->whereHas('sales', function ($q) use ($branchId, $salesId) {
                $q->where('branch_id', $branchId);
                if ($salesId) {
                    $q->where('id', $salesId);
                }
            });

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
            if ($totalPayments > 0) {
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

        $leadsQuery = Lead::query()
            ->where('branch_id', $branchId)
            ->whereHas('claims', function ($cq) use ($periodStart, $periodEnd, $salesId, $branchId) {

                $cq->whereBetween('claimed_at', [$periodStart, $periodEnd])
                    ->whereHas('user', function ($uq) use ($branchId) {
                        $uq->where('role_id', 2);
                        $uq->where('branch_id', $branchId);
                    });

                // jika filter sales dipilih
                if (!empty($salesId)) {
                    $cq->where('sales_id', $salesId);
                }
            });
        $leadsActual = $leadsQuery->distinct('id')->count('id');


        $visitsQuery = Lead::query()
            ->where('branch_id', $branchId)
            ->where('source_id', 9)
            ->whereHas('claims', function ($cq) use ($periodStart, $periodEnd, $salesId, $branchId) {

                $cq->whereBetween('claimed_at', [$periodStart, $periodEnd])
                    ->whereHas('user', function ($uq) use ($branchId) {
                        $uq->where('role_id', 2)
                            ->where('branch_id', $branchId);
                    });

                // jika filter sales dipilih
                if (!empty($salesId)) {
                    $cq->where('sales_id', $salesId);
                }
            });

        $visitsActual = $visitsQuery->distinct('id')->count('id');

        $monetaryActual = round($monetaryActual, 2);
        $achievementPercentage = $targetAmount > 0
            ? round(($monetaryActual / $targetAmount) * 100, 2)
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
            ->whereIn('leads.status_id', [$warmStatusId, $hotStatusId])
            ->where('leads.branch_id', $branchId)
            ->when($salesId, function ($q) use ($salesId) {
                $q->where('lead_claims.sales_id', $salesId);
            });

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
            ->where('branch_id', $branchId)
            ->when($salesId, function ($q) use ($salesId) {
                $q->whereHas('claims', function ($claimQ) use ($salesId) {
                    $claimQ->whereNull('released_at')
                        ->where('sales_id', $salesId);
                });
            })
            ->whereHas('quotation.proformas.paymentConfirmation', function ($q) use ($start, $end) {
                $q->whereNotNull('confirmed_at')
                    ->whereBetween('confirmed_at', [$start, $end]);
            });

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

        $claimedQuery = DB::table('lead_claims')
            ->join('leads', 'lead_claims.lead_id', '=', 'leads.id')
            ->when(!empty($branchId), function ($q) use ($branchId) {
                $q->join('users as sales', 'lead_claims.sales_id', '=', 'sales.id')
                ->where('sales.branch_id', $branchId);
            })
            ->whereBetween('lead_claims.claimed_at', [$periodStart, $periodEnd])
            ->whereIn('leads.status_id', [
                LeadStatus::COLD,
                LeadStatus::WARM,
                LeadStatus::HOT,
                LeadStatus::DEAL,
            ])
            ->whereNull('lead_claims.trash_note')
            ->whereNull('lead_claims.released_at')
            ->whereNull('lead_claims.deleted_at')
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
            ->where('leads.status_id', LeadStatus::PUBLISHED) // kalau status published = NEW / 1
            ->when(!empty($branchId), function ($q) use ($branchId) {
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
        $cold = (int) ($counts[LeadStatus::COLD] ?? 0);
        $warm = (int) ($counts[LeadStatus::WARM] ?? 0);
        $hot  = (int) ($counts[LeadStatus::HOT] ?? 0);

        // Total active leads should be derived from unique leads (exclude trash statuses)
        $trash = (int) (($counts[LeadStatus::TRASH_COLD] ?? 0)
            + ($counts[LeadStatus::TRASH_WARM] ?? 0)
            + ($counts[LeadStatus::TRASH_HOT] ?? 0));

        $totalActive = $uniqueLeads->reject(function ($lead) {
            return in_array($lead->status_id, [LeadStatus::TRASH_COLD, LeadStatus::TRASH_WARM, LeadStatus::TRASH_HOT]);
        })->count();

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
                    'target_amount' => $targetAmount,
                    'target_leads' => $targetLead,
                    'target_visits' => $targetVisit,
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

        if (!empty($validated['compare_start_date']) && !empty($validated['compare_end_date'])) {
            $baseSnapshot = $this->calculateBranchManagerGridCompareSnapshot(
                $validated['compare_start_date'],
                (int) $branchId,
                $salesId
            );
            $compareSnapshot = $this->calculateBranchManagerGridCompareSnapshot(
                $validated['compare_end_date'],
                (int) $branchId,
                $salesId
            );

            $data['Data']['compare'] = [
                'enabled' => true,
                'start_date' => $validated['compare_start_date'],
                'end_date' => $validated['compare_end_date'],
                'general_kpi' => [
                    'achievement_amount' => $this->formatCompareMetric($baseSnapshot['achievement_amount'], $compareSnapshot['achievement_amount']),
                    'leads_actual' => $this->formatCompareMetric($baseSnapshot['leads_actual'], $compareSnapshot['leads_actual']),
                    'visits_actual' => $this->formatCompareMetric($baseSnapshot['visits_actual'], $compareSnapshot['visits_actual']),
                    'closed_deal_total_deals' => $this->formatCompareMetric($baseSnapshot['closed_deal_total_deals'], $compareSnapshot['closed_deal_total_deals']),
                    'closed_deal_total_amount' => $this->formatCompareMetric($baseSnapshot['closed_deal_total_amount'], $compareSnapshot['closed_deal_total_amount']),
                    'active_leads_total' => $this->formatCompareMetric($baseSnapshot['active_leads_total'], $compareSnapshot['active_leads_total']),
                    'active_leads_published' => $this->formatCompareMetric($baseSnapshot['active_leads_published'], $compareSnapshot['active_leads_published']),
                    'active_leads_cold' => $this->formatCompareMetric($baseSnapshot['active_leads_cold'], $compareSnapshot['active_leads_cold']),
                    'active_leads_warm' => $this->formatCompareMetric($baseSnapshot['active_leads_warm'], $compareSnapshot['active_leads_warm']),
                    'active_leads_hot' => $this->formatCompareMetric($baseSnapshot['active_leads_hot'], $compareSnapshot['active_leads_hot']),
                    'potential_dealing_total_amount' => $this->formatCompareMetric($baseSnapshot['potential_dealing_total_amount'], $compareSnapshot['potential_dealing_total_amount']),
                    'potential_dealing_total_opportunity' => $this->formatCompareMetric($baseSnapshot['potential_dealing_total_opportunity'], $compareSnapshot['potential_dealing_total_opportunity']),
                ],
            ];
        }

        return response()->json($data);
    }

    public function ActiveOpportunities(Request $request)
    {
        $bm = Auth::user();

        if (! $bm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        $branchId = $bm->branch_id;
        $salesId = $request->filled('sales_id') ? (int) $request->input('sales_id') : null;
        if ($salesId !== null && $salesId <= 0) {
            $salesId = null;
        }

        $perPage = $request->get('per_page', 5);

        $startDate = $request->filled('start_date') ? (string) $request->start_date : null;
        $endDate = $request->filled('end_date') ? (string) $request->end_date : null;

        if ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $periodStart = $startDate ? Carbon::parse($startDate)->startOfDay()->toDateTimeString() : null;
        $periodEnd = $endDate ? Carbon::parse($endDate)->endOfDay()->toDateTimeString() : null;

        $query = LeadClaim::query()
            ->join('leads', 'lead_claims.lead_id', '=', 'leads.id')
            ->join('lead_statuses', 'leads.status_id', '=', 'lead_statuses.id')
            ->with([
                'lead.product',
                'lead.segment',
                'lead.latestStatusLog',
                'lead.quotation.items.product',
                'lead.status',
                'sales',
            ])
            ->whereNull('lead_claims.released_at')
            ->whereNull('lead_claims.deleted_at')
            ->whereNull('lead_claims.trash_note')
            ->whereIn('lead_statuses.id', [LeadStatus::COLD, LeadStatus::WARM, LeadStatus::HOT, LeadStatus::DEAL])
            ->when($periodStart && $periodEnd, function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('lead_claims.claimed_at', [$periodStart, $periodEnd]);
            })
            ->when($periodStart && ! $periodEnd, function ($q) use ($periodStart) {
                $q->where('lead_claims.claimed_at', '>=', $periodStart);
            })
            ->when(! $periodStart && $periodEnd, function ($q) use ($periodEnd) {
                $q->where('lead_claims.claimed_at', '<=', $periodEnd);
            })
            ->when(!empty($branchId) && empty($salesId), function ($q) use ($branchId) {
                $q->join('users as sales', 'lead_claims.sales_id', '=', 'sales.id')
                    ->where('sales.branch_id', $branchId);
            })
            ->when(!empty($salesId), function ($q) use ($salesId) {
                $q->where('lead_claims.sales_id', $salesId);
            })
            ->when($request->filled('stage'), function ($q) use ($request) {
                $q->where('leads.status_id', $request->stage);
            })
            ->when($request->filled('source_id'), function ($q) use ($request) {
                $q->where('leads.source_id', $request->source_id);
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

            // Prefer `needs` for display; fallback to product
            $needs = format_needs_label($lead->needs ?? $product ?? null);
            $salesName = $claim->sales?->name ?? null;

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
                'sales' => $salesName,
                'data_status' => $passed . '/6',
                'last_activity' => $lastActivity?->toDateTimeString(),
                'data_validation' => $dataValidation,
                'created_at' => $lead->created_at?->toDateString(),
            ];
        });

        $sales = User::query()
            ->where('branch_id', $branchId)
            ->whereHas('role', function ($q) {
                $q->where('code', 'sales');
            })
            ->select(['id', 'name', 'branch_id'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'sales' => $sales,
            'data' => $paginated->items(),
            'total' => $paginated->total(),
            'total_amount' => $totalAmount,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    public function SalesTrend(Request $request)
    {
        $bm = Auth::user();

        if (! $bm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        $branchId = $bm->branch_id;
        $salesId = $request->filled('sales_id') ? (int) $request->input('sales_id') : null;
        if ($salesId !== null && $salesId <= 0) {
            $salesId = null;
        }

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

        // Ambil hanya user sales di branch BM yang login
        $users = User::with('role')
            ->where('branch_id', $branchId)
            ->whereHas('role', function ($q) {
                $q->where('code', 'sales');
            })
            ->when($salesId, function ($q) use ($salesId) {
                $q->where('id', $salesId);
            })
            ->get();

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

        $salesTrends = [];

        foreach ($users as $user) {
            $salesData = [];
            $targetData = [];

            foreach ($periods as $period) {
                $amount = $this->calculateSalesAchievementForPeriod($user, $period['start'], $period['end']);

                $salesData[] = (int) round($amount);

                if ($groupBy === 'quarter') {
                    $startMonth = Carbon::parse($period['start'])->month;
                    $endMonth = Carbon::parse($period['end'])->month;

                    $quarterTarget = 0.0;
                    for ($m = $startMonth; $m <= $endMonth; $m++) {
                        $quarterTarget += $getUserTargetAmountByMonth($user, $m);
                    }

                    $targetData[] = (int) round($quarterTarget);
                } else {
                    $monthNumber = Carbon::parse($period['start'])->month;
                    $targetData[] = (int) round($getUserTargetAmountByMonth($user, $monthNumber));
                }
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

    public function leadVolume(Request $request): JsonResponse
    {
        $bm = Auth::user();

        if (! $bm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (empty($bm->branch_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Branch manager branch is not configured.',
            ], 422);
        }

        $validated = $request->validate([
            'sales_id' => 'nullable|integer|exists:users,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'compare_start_date' => 'nullable|date_format:Y-m-d',
            'compare_end_date' => 'nullable|date_format:Y-m-d|after_or_equal:compare_start_date',
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

        $branchId = $bm->branch_id;
        $salesId = $this->resolveBranchManagerAgentSummarySalesId(
            $validated['sales_id'] ?? ($validated['user_id'] ?? null),
            (int) $branchId
        );
        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;

        $provinceExpression = $this->regionalReachProvinceExpression();
        $branchExpression = $this->regionalReachBranchExpression();
        $leadBaseQuery = $this->buildRegionalReachLeadBaseQuery($branchId, $salesId, $startDate, $endDate);

        $topSummarySource = (clone $leadBaseQuery)
            ->whereRaw($provinceExpression . ' IS NOT NULL')
            ->selectRaw($provinceExpression . ' as province');
        
        $topSummary = DB::query()
            ->fromSub($topSummarySource, 'province_leads')
            ->selectRaw('province, COUNT(*) as total_leads')
            ->groupBy('province')
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

        if (!empty($validated['compare_start_date']) && !empty($validated['compare_end_date'])) {
            $baseCoverageSnapshot = $this->calculateRegionalReachCoverageCompareSnapshot(
                (int) $branchId,
                $salesId,
                $validated['compare_start_date']
            );
            $compareCoverageSnapshot = $this->calculateRegionalReachCoverageCompareSnapshot(
                (int) $branchId,
                $salesId,
                $validated['compare_end_date']
            );

            $coverage['compare'] = [
                'enabled' => true,
                'start_date' => $validated['compare_start_date'],
                'end_date' => $validated['compare_end_date'],
                'reached_provinces' => $this->formatCompareMetric(
                    $baseCoverageSnapshot['reached_provinces'],
                    $compareCoverageSnapshot['reached_provinces']
                ),
                'reached_cities' => $this->formatCompareMetric(
                    $baseCoverageSnapshot['reached_cities'],
                    $compareCoverageSnapshot['reached_cities']
                ),
            ];
        }

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

    private function calculateRegionalReachCoverageCompareSnapshot(int $branchId, ?int $salesId, string $date): array
    {
        $periodEnd = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Jakarta')->endOfDay();
        $periodStart = (clone $periodEnd)->startOfMonth()->startOfDay();

        $coverageSummaryQuery = $this->buildRegionalReachLeadBaseQuery(
            $branchId,
            $salesId,
            $periodStart->toDateString(),
            $periodEnd->toDateString()
        )
            ->whereNotNull('leads.region_id')
            ->whereNotNull('ref_regions.province_id');

        $scopedMappingBranchId = $this->resolveRegionalReachScopeBranchId($branchId, $salesId);
        $coverageTotalsQuery = Region::query();

        if (!empty($scopedMappingBranchId)) {
            $coverageTotalsQuery->where('branch_id', $scopedMappingBranchId);
        }

        return [
            'total_provinces' => !empty($scopedMappingBranchId)
                ? (clone $coverageTotalsQuery)->whereNotNull('province_id')->distinct()->count('province_id')
                : Province::query()->count(),
            'reached_provinces' => (clone $coverageSummaryQuery)->distinct()->count('ref_regions.province_id'),
            'total_cities' => !empty($scopedMappingBranchId)
                ? (clone $coverageTotalsQuery)->count()
                : Region::query()->count(),
            'reached_cities' => (clone $coverageSummaryQuery)->distinct()->count('ref_regions.id'),
        ];
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

    private function formatCompareMetric($baseValue, $compareValue): array
    {
        $base = round((float) $baseValue, 2);
        $compare = round((float) $compareValue, 2);

        return [
            'base' => $base,
            'compare' => $compare,
            'delta' => round($compare - $base, 2),
        ];
    }

    private function calculateBranchManagerGridCompareSnapshot(string $date, int $branchId, ?int $salesId): array
    {
        $periodEnd = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Jakarta')->endOfDay();
        $periodStart = (clone $periodEnd)->startOfMonth()->startOfDay();

        return $this->calculateBranchManagerGridMetricsForPeriod($periodStart, $periodEnd, $branchId, $salesId);
    }

    private function calculateBranchManagerGridMetricsForPeriod(Carbon $selectedPeriodStart, Carbon $selectedPeriodEnd, int $branchId, ?int $salesId): array
    {
        $periodStart = $selectedPeriodStart->toDateTimeString();
        $periodEnd = $selectedPeriodEnd->toDateTimeString();

        $isInSelectedPeriod = function ($date) use ($selectedPeriodStart, $selectedPeriodEnd): bool {
            if (empty($date)) {
                return false;
            }

            $d = Carbon::parse($date, 'Asia/Jakarta');

            return $d->between($selectedPeriodStart, $selectedPeriodEnd);
        };

        $claims = LeadClaim::with(['lead.quotation.proformas.paymentConfirmation'])
            ->whereHas('lead', function ($q) {
                $q->where('status_id', LeadStatus::DEAL);
            })
            ->whereNull('released_at')
            ->whereHas('sales', function ($q) use ($branchId, $salesId) {
                $q->where('branch_id', $branchId);

                if (!empty($salesId)) {
                    $q->where('id', $salesId);
                }
            });

        $claims->whereHas('lead.quotation', function ($q) use ($periodStart, $periodEnd) {
            $q->firstTermPaidBetween($periodStart, $periodEnd);
        });

        $closedDeals = 0;
        $closedAmount = 0;

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

            if ($totalPayments <= 0 || $approvedPayments < $totalPayments) {
                continue;
            }

            $closedDeals++;

            $periodConfirmed = $confirmedProformas->filter(function ($p) use ($isInSelectedPeriod) {
                return $isInSelectedPeriod($p->paymentConfirmation->confirmed_at ?? null);
            });

            $closedAmount += (float) $periodConfirmed->sum(function ($p) {
                return (float) ($p->paymentConfirmation->amount ?? $p->amount ?? 0);
            });
        }

        $leadsQuery = Lead::query()
            ->where('branch_id', $branchId)
            ->whereHas('claims', function ($cq) use ($periodStart, $periodEnd, $salesId, $branchId) {
                $cq->whereBetween('claimed_at', [$periodStart, $periodEnd])
                    ->whereHas('user', function ($uq) use ($branchId) {
                        $uq->where('role_id', 2)
                            ->where('branch_id', $branchId);
                    });

                if (!empty($salesId)) {
                    $cq->where('sales_id', $salesId);
                }
            });

        $leadsActual = $leadsQuery->distinct('id')->count('id');

        $visitsQuery = Lead::query()
            ->where('branch_id', $branchId)
            ->where('source_id', 9)
            ->whereHas('claims', function ($cq) use ($periodStart, $periodEnd, $salesId, $branchId) {
                $cq->whereBetween('claimed_at', [$periodStart, $periodEnd])
                    ->whereHas('user', function ($uq) use ($branchId) {
                        $uq->where('role_id', 2)
                            ->where('branch_id', $branchId);
                    });

                if (!empty($salesId)) {
                    $cq->where('sales_id', $salesId);
                }
            });

        $visitsActual = $visitsQuery->distinct('id')->count('id');

        $warmStatusId = LeadStatus::WARM;
        $hotStatusId = LeadStatus::HOT;

        $latestQuotationSubquery = DB::table('quotations')
            ->select('lead_id', DB::raw('MAX(created_at) as latest_date'))
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($periodStart, $periodEnd) {
                $query->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->orWhere(function ($q) use ($periodStart, $periodEnd) {
                        $q->where('created_at', '<=', $periodEnd)
                            ->whereRaw('DATE_ADD(created_at, INTERVAL 30 DAY) >= ?', [$periodStart]);
                    });
            })
            ->groupBy('lead_id');

        $potentialLeads = Lead::query()
            ->join('quotations', function ($join) use ($periodStart, $periodEnd) {
                $join->on('quotations.lead_id', '=', 'leads.id')
                    ->where('quotations.status', 'published')
                    ->whereNull('quotations.deleted_at')
                    ->where(function ($query) use ($periodStart, $periodEnd) {
                        $query->whereBetween('quotations.created_at', [$periodStart, $periodEnd])
                            ->orWhere(function ($q) use ($periodStart, $periodEnd) {
                                $q->where('quotations.created_at', '<=', $periodEnd)
                                    ->whereRaw('DATE_ADD(quotations.created_at, INTERVAL 30 DAY) >= ?', [$periodStart]);
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
            ->where('leads.branch_id', $branchId)
            ->when(!empty($salesId), function ($q) use ($salesId) {
                $q->where('lead_claims.sales_id', $salesId);
            });

        $potentialCollection = $potentialLeads
            ->select([
                'leads.id',
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

        $paymentLeads = Lead::query()
            ->whereIn('status_id', [$warmStatusId, $hotStatusId])
            ->where('branch_id', $branchId)
            ->when(!empty($salesId), function ($q) use ($salesId) {
                $q->whereHas('claims', function ($claimQ) use ($salesId) {
                    $claimQ->whereNull('released_at')
                        ->where('sales_id', $salesId);
                });
            })
            ->whereHas('quotation.proformas.paymentConfirmation', function ($q) use ($periodStart, $periodEnd) {
                $q->whereNotNull('confirmed_at')
                    ->whereBetween('confirmed_at', [$periodStart, $periodEnd]);
            });

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

        $merged = collect()
            ->merge($potentialCollection)
            ->merge($paymentCollection)
            ->unique('id');

        $claimedQuery = DB::table('lead_claims')
            ->join('leads', 'lead_claims.lead_id', '=', 'leads.id')
            ->join('users as sales', 'lead_claims.sales_id', '=', 'sales.id')
            ->where('sales.branch_id', $branchId)
            ->whereBetween('lead_claims.claimed_at', [$periodStart, $periodEnd])
            ->whereIn('leads.status_id', [
                LeadStatus::COLD,
                LeadStatus::WARM,
                LeadStatus::HOT,
                LeadStatus::DEAL,
            ])
            ->whereNull('lead_claims.trash_note')
            ->whereNull('lead_claims.released_at')
            ->whereNull('lead_claims.deleted_at')
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
            ->where('leads.status_id', LeadStatus::PUBLISHED)
            ->where('leads.branch_id', $branchId)
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

        $counts = $uniqueLeads->groupBy('status_id')->map->count();
        $published = (int) ($counts[LeadStatus::PUBLISHED] ?? 0);
        $cold = (int) ($counts[LeadStatus::COLD] ?? 0);
        $warm = (int) ($counts[LeadStatus::WARM] ?? 0);
        $hot = (int) ($counts[LeadStatus::HOT] ?? 0);
        $totalActive = $uniqueLeads->reject(function ($lead) {
            return in_array($lead->status_id, [LeadStatus::TRASH_COLD, LeadStatus::TRASH_WARM, LeadStatus::TRASH_HOT], true);
        })->count();

        return [
            'achievement_amount' => round($closedAmount, 2),
            'leads_actual' => (int) $leadsActual,
            'visits_actual' => (int) $visitsActual,
            'closed_deal_total_deals' => (int) $closedDeals,
            'closed_deal_total_amount' => round($closedAmount, 2),
            'active_leads_total' => (int) $totalActive,
            'active_leads_published' => $published,
            'active_leads_cold' => $cold,
            'active_leads_warm' => $warm,
            'active_leads_hot' => $hot,
            'potential_dealing_total_amount' => round((float) $merged->sum('amount'), 2),
            'potential_dealing_total_opportunity' => (int) $merged->count(),
        ];
    }

    private function resolveBranchManagerAgentSummarySalesId(?int $salesId, int $branchId): ?int
    {
        if (empty($salesId)) {
            return null;
        }

        return User::query()
            ->whereKey($salesId)
            ->where('branch_id', $branchId)
            ->whereHas('role', function ($q) {
                $q->where('code', 'sales');
            })
            ->value('id');
    }

    public function AgentSummary(Request $request): JsonResponse
    {
        $bm = Auth::user();

        if (! $bm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (empty($bm->branch_id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Branch manager branch is not configured.',
            ], 422);
        }

        $validated = $request->validate([
            'sales_id' => 'nullable|integer|exists:users,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'start_date_grid' => 'nullable|date_format:Y-m-d',
            'end_date_grid' => 'nullable|date_format:Y-m-d',
            'compare_start_date' => 'nullable|date_format:Y-m-d',
            'compare_end_date' => 'nullable|date_format:Y-m-d|after_or_equal:compare_start_date',
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|between:1,12',
            'month_from' => 'nullable|integer|between:1,12',
            'month_to' => 'nullable|integer|between:1,12',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'province_id' => 'nullable|integer|exists:ref_provinces,id',
            'region_id' => 'nullable|integer|exists:ref_regions,id',
            'status' => 'nullable|in:1,0,active,inactive',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
        ]);

        $branchId = (int) $bm->branch_id;
        $requestedSalesId = $validated['sales_id'] ?? ($validated['user_id'] ?? null);
        $salesId = $this->resolveBranchManagerAgentSummarySalesId(
            !empty($requestedSalesId) ? (int) $requestedSalesId : null,
            $branchId
        );
        $nowJakarta = Carbon::now('Asia/Jakarta');

        $selectedPeriodStart = (clone $nowJakarta)->startOfMonth();
        $selectedPeriodEnd = (clone $nowJakarta)->endOfMonth();

        if ($request->filled('start_date_grid') && $request->filled('end_date_grid')) {
            $selectedPeriodStart = Carbon::createFromFormat('Y-m-d', (string) $validated['start_date_grid'], 'Asia/Jakarta')->startOfDay();
            $selectedPeriodEnd = Carbon::createFromFormat('Y-m-d', (string) $validated['end_date_grid'], 'Asia/Jakarta')->endOfDay();

            if ($selectedPeriodStart->gt($selectedPeriodEnd)) {
                [$selectedPeriodStart, $selectedPeriodEnd] = [$selectedPeriodEnd, $selectedPeriodStart];
                $selectedPeriodStart = $selectedPeriodStart->startOfDay();
                $selectedPeriodEnd = $selectedPeriodEnd->endOfDay();
            }
        }

        $trend = $this->buildAgentSummaryTrendPeriods(
            (int) ($validated['year'] ?? $nowJakarta->year),
            $validated['month'] ?? null,
            $validated['month_from'] ?? null,
            $validated['month_to'] ?? null
        );

        $agentTrendData = [];
        foreach ($trend['periods'] as $period) {
            $agentTrendData[] = (int) round($this->calculateAgentAchievementForPeriod(
                $branchId,
                $salesId,
                $period['start'],
                $period['end']
            ));
        }

        $agentBranchScope = $this->resolveAgentSummaryBranchScope($branchId, $salesId);
        $agentsQuery = Agent::query()
            ->when(!empty($agentBranchScope), function ($q) use ($agentBranchScope) {
                $q->where('branch_id', $agentBranchScope);
            });

        $agentAchievementAmount = $this->calculateAgentAchievementForPeriod(
            $branchId,
            $salesId,
            $selectedPeriodStart->toDateTimeString(),
            $selectedPeriodEnd->toDateTimeString()
        );

        $agentLeads = $this->calculateAgentLeadsMetricsForPeriod(
            $selectedPeriodStart,
            $selectedPeriodEnd,
            $branchId,
            $salesId
        );

        $agentDetailPage = max((int) ($validated['page'] ?? 1), 1);
        $agentDetailPerPage = min(max((int) ($validated['per_page'] ?? 5), 1), 100);
        $agentDetailSearch = trim((string) ($validated['search'] ?? ''));
        $agentDetailProvinceId = $validated['province_id'] ?? null;
        $agentDetailProvinceName = !empty($agentDetailProvinceId)
            ? Province::query()->whereKey($agentDetailProvinceId)->value('name')
            : null;
        $agentDetailRegionId = $validated['region_id'] ?? null;
        $agentDetailStatus = $validated['status'] ?? null;
        $agentDetailStartDate = $validated['start_date'] ?? null;
        $agentDetailEndDate = $validated['end_date'] ?? null;

        if ($agentDetailStartDate && $agentDetailEndDate && $agentDetailStartDate > $agentDetailEndDate) {
            [$agentDetailStartDate, $agentDetailEndDate] = [$agentDetailEndDate, $agentDetailStartDate];
        }

        $agentDetailQuery = Agent::with([
            'branch:id,name',
            'region:id,name,province_id,regional_id,branch_id',
            'region.province:id,name',
            'region.regional:id,name',
        ])
            ->where('branch_id', $agentBranchScope)
            ->when(!empty($agentDetailProvinceId), function ($q) use ($agentDetailProvinceId, $agentDetailProvinceName) {
                $q->where(function ($provinceQ) use ($agentDetailProvinceId, $agentDetailProvinceName) {
                    $provinceQ->whereHas('region', function ($regionQ) use ($agentDetailProvinceId) {
                        $regionQ->where('province_id', $agentDetailProvinceId);
                    });

                    if (!empty($agentDetailProvinceName)) {
                        $provinceQ->orWhere('province', $agentDetailProvinceName);
                    }
                });
            })
            ->when(!empty($agentDetailRegionId), function ($q) use ($agentDetailRegionId) {
                $q->where('region_id', $agentDetailRegionId);
            })
            ->when($agentDetailStatus !== null && $agentDetailStatus !== '', function ($q) use ($agentDetailStatus) {
                $isActive = in_array((string) $agentDetailStatus, ['1', 'active'], true) ? 1 : 0;

                $q->where('is_active', $isActive);
            })
            ->when(!empty($agentDetailStartDate) && !empty($agentDetailEndDate), function ($q) use ($agentDetailStartDate, $agentDetailEndDate) {
                $q->whereBetween(DB::raw('DATE(ref_agents.created_at)'), [$agentDetailStartDate, $agentDetailEndDate]);
            })
            ->when(!empty($agentDetailStartDate) && empty($agentDetailEndDate), function ($q) use ($agentDetailStartDate) {
                $q->whereDate('ref_agents.created_at', '>=', $agentDetailStartDate);
            })
            ->when(empty($agentDetailStartDate) && !empty($agentDetailEndDate), function ($q) use ($agentDetailEndDate) {
                $q->whereDate('ref_agents.created_at', '<=', $agentDetailEndDate);
            })
            ->when($agentDetailSearch !== '', function ($q) use ($agentDetailSearch) {
                $like = '%' . $agentDetailSearch . '%';

                $q->where(function ($searchQ) use ($like) {
                    $searchQ->where('name', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('company_name', 'like', $like)
                        ->orWhere('province', 'like', $like)
                        ->orWhereHas('branch', function ($branchQ) use ($like) {
                            $branchQ->where('name', 'like', $like);
                        })
                        ->orWhereHas('region', function ($regionQ) use ($like) {
                            $regionQ->where('name', 'like', $like);
                        })
                        ->orWhereHas('region.province', function ($provinceQ) use ($like) {
                            $provinceQ->where('name', 'like', $like);
                        });
                });
            });

        $agentDetailPaginator = $agentDetailQuery
            ->latest('created_at')
            ->paginate($agentDetailPerPage, ['*'], 'page', $agentDetailPage);

        $agentDetailRows = $agentDetailPaginator->getCollection()
            ->map(function (Agent $agent) {
                return [
                    'id' => $agent->id,
                    'branch_id' => $agent->branch_id,
                    'branch_name' => $agent->branch->name ?? '-',
                    'agent_name' => $agent->name ?? '-',
                    'active_month' => optional($agent->created_at)->format('M Y'),
                    'regional' => $agent->region?->regional?->name ?? '-',
                    'province_id' => $agent->region?->province_id,
                    'province_name' => $agent->region?->province?->name ?? $agent->province ?? '-',
                    'region_id' => $agent->region_id,
                    'city_name' => $agent->region?->name ?? '-',
                    'created_at' => optional($agent->created_at)->format('d M Y'),
                    'is_active' => (int) $agent->is_active,
                    'status_name' => $agent->is_active ? 'Active' : 'Inactive',
                ];
            })
            ->values();

        $data = [
            'status' => 'success',
            'agent_trends' => [
                'labels' => $trend['labels'],
                'categories' => $trend['labels'],
                'group_by' => $trend['group_by'],
                'series' => [
                    [
                        'name' => 'Agent Achievement',
                        'data' => $agentTrendData,
                    ],
                ],
            ],
            'kpi' => [
                'agent_achievement' => [
                    'achievement_amount' => round($agentAchievementAmount, 2),
                ],
                'total_active' => [
                    'total' => (clone $agentsQuery)->count(),
                    'active' => (clone $agentsQuery)->where('is_active', true)->count(),
                    'inactive' => (clone $agentsQuery)->where('is_active', false)->count(),
                ],
                'total_agent_leads' => $agentLeads,
            ],
            'agent-detail' => [
                'data' => $agentDetailRows,
                'pagination' => [
                    'current_page' => $agentDetailPaginator->currentPage(),
                    'last_page' => $agentDetailPaginator->lastPage(),
                    'per_page' => $agentDetailPaginator->perPage(),
                    'total' => $agentDetailPaginator->total(),
                    'from' => $agentDetailPaginator->firstItem() ?? 0,
                    'to' => $agentDetailPaginator->lastItem() ?? 0,
                ],
            ],
        ];

        if (!empty($validated['compare_start_date']) && !empty($validated['compare_end_date'])) {
            $baseSnapshot = $this->calculateAgentSummaryCompareSnapshot(
                $validated['compare_start_date'],
                $branchId,
                $salesId
            );
            $compareSnapshot = $this->calculateAgentSummaryCompareSnapshot(
                $validated['compare_end_date'],
                $branchId,
                $salesId
            );

            $data['compare'] = [
                'enabled' => true,
                'start_date' => $validated['compare_start_date'],
                'end_date' => $validated['compare_end_date'],
                'agent_kpi' => [
                    'achievement_amount' => $this->formatCompareMetric($baseSnapshot['achievement_amount'], $compareSnapshot['achievement_amount']),
                    'total_agents_total' => $this->formatCompareMetric($baseSnapshot['total_agents_total'], $compareSnapshot['total_agents_total']),
                    'total_agents_active' => $this->formatCompareMetric($baseSnapshot['total_agents_active'], $compareSnapshot['total_agents_active']),
                    'total_agents_inactive' => $this->formatCompareMetric($baseSnapshot['total_agents_inactive'], $compareSnapshot['total_agents_inactive']),
                    'active_agent_leads_total' => $this->formatCompareMetric($baseSnapshot['active_agent_leads_total'], $compareSnapshot['active_agent_leads_total']),
                    'active_agent_leads_published' => $this->formatCompareMetric($baseSnapshot['active_agent_leads_published'], $compareSnapshot['active_agent_leads_published']),
                    'active_agent_leads_cold' => $this->formatCompareMetric($baseSnapshot['active_agent_leads_cold'], $compareSnapshot['active_agent_leads_cold']),
                    'active_agent_leads_warm' => $this->formatCompareMetric($baseSnapshot['active_agent_leads_warm'], $compareSnapshot['active_agent_leads_warm']),
                    'active_agent_leads_hot' => $this->formatCompareMetric($baseSnapshot['active_agent_leads_hot'], $compareSnapshot['active_agent_leads_hot']),
                    'active_agent_leads_deal' => $this->formatCompareMetric($baseSnapshot['active_agent_leads_deal'], $compareSnapshot['active_agent_leads_deal']),
                ],
            ];
        }

        return response()->json($data);
    }

    private function buildAgentSummaryTrendPeriods(int $year, $month = null, $monthFrom = null, $monthTo = null): array
    {
        $selectedMonth = $month !== null && $month !== '' ? (int) $month : null;
        $from = $monthFrom !== null && $monthFrom !== '' ? (int) $monthFrom : null;
        $to = $monthTo !== null && $monthTo !== '' ? (int) $monthTo : null;
        $groupBy = 'month';

        if ($selectedMonth) {
            $groupBy = 'week';
            $from = $selectedMonth;
            $to = $selectedMonth;
        } else {
            if ($from && ! $to) {
                $to = $from;
            } elseif (! $from && $to) {
                $from = $to;
            }

            if ($from && $to && $from > $to) {
                [$from, $to] = [$to, $from];
            }

            if ($from === 1 && $to === 12) {
                $groupBy = 'quarter';
            }
        }

        $labels = [];
        $periods = [];

        if ($groupBy === 'quarter') {
            for ($q = 1; $q <= 4; $q++) {
                $startMonth = 1 + (($q - 1) * 3);
                $endMonth = $startMonth + 2;

                $labels[] = 'Q' . $q;
                $periods[] = [
                    'start' => Carbon::create($year, $startMonth, 1, 0, 0, 0, 'Asia/Jakarta')->startOfMonth()->toDateTimeString(),
                    'end' => Carbon::create($year, $endMonth, 1, 0, 0, 0, 'Asia/Jakarta')->endOfMonth()->toDateTimeString(),
                ];
            }

            return [
                'labels' => $labels,
                'periods' => $periods,
                'group_by' => $groupBy,
            ];
        }

        if ($groupBy === 'week') {
            $monthNumber = $selectedMonth ?: (int) ($from ?: Carbon::now('Asia/Jakarta')->month);
            $periodStart = Carbon::create($year, $monthNumber, 1, 0, 0, 0, 'Asia/Jakarta')->startOfMonth();
            $periodEnd = Carbon::create($year, $monthNumber, 1, 0, 0, 0, 'Asia/Jakarta')->endOfMonth();

            $labels[] = 'MTD';
            $periods[] = [
                'start' => $periodStart->toDateTimeString(),
                'end' => $periodEnd->toDateTimeString(),
            ];

            return [
                'labels' => $labels,
                'periods' => $periods,
                'group_by' => $groupBy,
            ];
        }

        $from = $from ?: 1;
        $to = $to ?: 12;

        for ($m = $from; $m <= $to; $m++) {
            $labels[] = date('M', mktime(0, 0, 0, $m, 1));
            $periods[] = [
                'start' => Carbon::create($year, $m, 1, 0, 0, 0, 'Asia/Jakarta')->startOfMonth()->toDateTimeString(),
                'end' => Carbon::create($year, $m, 1, 0, 0, 0, 'Asia/Jakarta')->endOfMonth()->toDateTimeString(),
            ];
        }

        return [
            'labels' => $labels,
            'periods' => $periods,
            'group_by' => $groupBy,
        ];
    }

    private function resolveAgentSummaryBranchScope(?int $branchId, ?int $salesId): ?int
    {
        return !empty($branchId) ? $branchId : null;
    }

    private function calculateAgentLeadsMetricsForPeriod(Carbon $selectedPeriodStart, Carbon $selectedPeriodEnd, ?int $branchId, ?int $salesId): array
    {
        $periodStart = $selectedPeriodStart->toDateTimeString();
        $periodEnd = $selectedPeriodEnd->toDateTimeString();
        $agentBranchScope = $this->resolveAgentSummaryBranchScope($branchId, $salesId);

        $claimedQuery = DB::table('lead_claims')
            ->join('leads', 'lead_claims.lead_id', '=', 'leads.id')
            ->join('users as sales', 'lead_claims.sales_id', '=', 'sales.id')
            ->whereNotNull('leads.agent_id')
            ->when(!empty($agentBranchScope), function ($q) use ($agentBranchScope) {
                $q->where('leads.branch_id', $agentBranchScope)
                    ->where('sales.branch_id', $agentBranchScope);
            })
            ->whereBetween('lead_claims.claimed_at', [$periodStart, $periodEnd])
            ->whereIn('leads.status_id', [
                LeadStatus::COLD,
                LeadStatus::WARM,
                LeadStatus::HOT,
                LeadStatus::DEAL,
            ])
            ->whereNull('lead_claims.trash_note')
            ->whereNull('lead_claims.released_at')
            ->whereNull('lead_claims.deleted_at')
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
            ->whereNotNull('leads.agent_id')
            ->whereBetween('leads.published_at', [$periodStart, $periodEnd])
            ->where('leads.status_id', LeadStatus::PUBLISHED)
            ->when(!empty($agentBranchScope), function ($q) use ($agentBranchScope) {
                $q->where('leads.branch_id', $agentBranchScope);
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

        $counts = $uniqueLeads->groupBy('status_id')->map->count();
        $published = (int) ($counts[LeadStatus::PUBLISHED] ?? 0);
        $cold = (int) ($counts[LeadStatus::COLD] ?? 0);
        $warm = (int) ($counts[LeadStatus::WARM] ?? 0);
        $hot = (int) ($counts[LeadStatus::HOT] ?? 0);
        $deal = (int) ($counts[LeadStatus::DEAL] ?? 0);
        $totalActive = $uniqueLeads->reject(function ($lead) {
            return in_array($lead->status_id, [LeadStatus::TRASH_COLD, LeadStatus::TRASH_WARM, LeadStatus::TRASH_HOT], true);
        })->count();

        return [
            'total' => (int) $totalActive,
            'published' => $published,
            'cold' => $cold,
            'warm' => $warm,
            'hot' => $hot,
            'deal' => $deal,
        ];
    }

    private function calculateAgentSummaryCompareSnapshot(string $date, ?int $branchId, ?int $salesId): array
    {
        $periodEnd = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Jakarta')->endOfDay();
        $periodStart = (clone $periodEnd)->startOfMonth()->startOfDay();
        $agentBranchScope = $this->resolveAgentSummaryBranchScope($branchId, $salesId);
        $agentsQuery = Agent::query()
            ->where('created_at', '<=', $periodEnd->toDateTimeString())
            ->when(!empty($agentBranchScope), function ($q) use ($agentBranchScope) {
                $q->where('branch_id', $agentBranchScope);
            });
        $agentLeads = $this->calculateAgentLeadsMetricsForPeriod($periodStart, $periodEnd, $branchId, $salesId);

        return [
            'achievement_amount' => $this->calculateAgentAchievementForPeriod(
                $branchId,
                $salesId,
                $periodStart->toDateTimeString(),
                $periodEnd->toDateTimeString()
            ),
            'total_agents_total' => (clone $agentsQuery)->count(),
            'total_agents_active' => (clone $agentsQuery)->where('is_active', true)->count(),
            'total_agents_inactive' => (clone $agentsQuery)->where('is_active', false)->count(),
            'active_agent_leads_total' => $agentLeads['total'],
            'active_agent_leads_published' => $agentLeads['published'],
            'active_agent_leads_cold' => $agentLeads['cold'],
            'active_agent_leads_warm' => $agentLeads['warm'],
            'active_agent_leads_hot' => $agentLeads['hot'],
            'active_agent_leads_deal' => $agentLeads['deal'],
        ];
    }

    private function calculateAgentAchievementForPeriod(?int $branchId, ?int $salesId, string $startDate, string $endDate): float
    {
        $periodStart = Carbon::parse($startDate, 'Asia/Jakarta')->startOfDay();
        $periodEnd = Carbon::parse($endDate, 'Asia/Jakarta')->endOfDay();
        $periodStartString = $periodStart->toDateTimeString();
        $periodEndString = $periodEnd->toDateTimeString();
        $agentBranchScope = $this->resolveAgentSummaryBranchScope($branchId, $salesId);

        $claims = LeadClaim::with(['lead.quotation.proformas.paymentConfirmation'])
            ->whereHas('lead', function ($q) use ($agentBranchScope) {
                $q->where('status_id', LeadStatus::DEAL)
                    ->whereNotNull('agent_id')
                    ->when(!empty($agentBranchScope), function ($leadQ) use ($agentBranchScope) {
                        $leadQ->where('branch_id', $agentBranchScope);
                    });
            })
            ->when(!empty($salesId), function ($q) use ($salesId) {
                $q->where('sales_id', $salesId);
            })
            ->when(!empty($agentBranchScope), function ($q) use ($agentBranchScope) {
                $q->whereHas('sales', function ($salesQ) use ($agentBranchScope) {
                    $salesQ->where('branch_id', $agentBranchScope);
                });
            })
            ->whereNull('released_at');

        $claims->whereHas('lead.quotation', function ($q) use ($periodStartString, $periodEndString) {
            $q->firstTermPaidBetween($periodStartString, $periodEndString);
        });

        $achievementAmount = 0;

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

            if ($totalPayments <= 0 || $confirmedProformas->count() < $totalPayments) {
                continue;
            }

            $periodConfirmed = $confirmedProformas->filter(function ($p) use ($periodStart, $periodEnd) {
                $confirmedAt = $p->paymentConfirmation->confirmed_at ?? null;

                if (empty($confirmedAt)) {
                    return false;
                }

                return Carbon::parse($confirmedAt, 'Asia/Jakarta')->between($periodStart, $periodEnd);
            });

            $achievementAmount += (float) $periodConfirmed->sum(function ($p) {
                return (float) ($p->paymentConfirmation->amount ?? $p->amount ?? 0);
            });
        }

        return round($achievementAmount, 2);
    }

    private function calculateSalesAchievementForPeriod(User $user, string $startDate, string $endDate): float
    {
        $claims = LeadClaim::with(['lead.quotation.proformas.paymentConfirmation'])
            ->whereHas('lead', function ($q) {
                $q->where('status_id', LeadStatus::DEAL);
            })
            ->whereNull('released_at')
            ->where('sales_id', $user->id);

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

            if ($totalPayments > 0 && $approvedPayments >= $totalPayments) {
                $monetaryActual += (float) $confirmedProformas->sum(function ($p) {
                    return (float) ($p->paymentConfirmation->amount ?? $p->amount ?? 0);
                });
            }
        }

        return round($monetaryActual, 2);
    }

    public function LeadsPerformance(Request $request)
    {
        $bm = Auth::user();

        if (! $bm) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        $validated = $request->validate([
            'sales_id' => 'nullable|integer|exists:users,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'source_id' => 'nullable|integer|exists:lead_sources,id',
            'segment_id' => 'nullable|integer|exists:lead_segments,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $branchId = $bm->branch_id;
        $salesId = $validated['sales_id'] ?? ($validated['user_id'] ?? null);
        $sourceId = $validated['source_id'] ?? null;
        $segmentId = $validated['segment_id'] ?? null;

        // Keep compatibility with BM page params while aligning core query with DashSummaryController.
        $startDate = $validated['start_date']
            ?? $request->input('start_date_source')
            ?? $request->input('start_date_segment');
        $endDate = $validated['end_date']
            ?? $request->input('end_date_source')
            ?? $request->input('end_date_segment');

        if ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $periodStart = $startDate ? Carbon::parse($startDate)->startOfDay()->toDateTimeString() : null;
        $periodEnd = $endDate ? Carbon::parse($endDate)->endOfDay()->toDateTimeString() : null;

        $latestQuotationSubquery = DB::table('quotations')
            ->select('lead_id', DB::raw('MAX(created_at) as latest_date'))
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->groupBy('lead_id');

        $rows = LeadClaim::query()
            ->join('leads', 'lead_claims.lead_id', '=', 'leads.id')
            ->join('lead_statuses', 'leads.status_id', '=', 'lead_statuses.id')
            ->whereNull('lead_claims.released_at')
            ->whereNull('lead_claims.deleted_at')
            ->whereNull('lead_claims.trash_note')
            ->whereIn('lead_statuses.id', [LeadStatus::PUBLISHED, LeadStatus::COLD, LeadStatus::WARM, LeadStatus::HOT, LeadStatus::DEAL])
            ->when($periodStart && $periodEnd, function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('lead_claims.claimed_at', [$periodStart, $periodEnd]);
            })
            ->when($periodStart && ! $periodEnd, function ($q) use ($periodStart) {
                $q->where('lead_claims.claimed_at', '>=', $periodStart);
            })
            ->when(! $periodStart && $periodEnd, function ($q) use ($periodEnd) {
                $q->where('lead_claims.claimed_at', '<=', $periodEnd);
            })
            ->when(!empty($branchId) && empty($salesId), function ($q) use ($branchId) {
                $q->join('users as sales', 'lead_claims.sales_id', '=', 'sales.id')
                    ->where('sales.branch_id', $branchId);
            })
            ->when(!empty($salesId), function ($q) use ($salesId) {
                $q->where('lead_claims.sales_id', $salesId);
            })
            ->when(!empty($sourceId), function ($q) use ($sourceId) {
                $q->where('leads.source_id', $sourceId);
            })
            ->when(!empty($segmentId), function ($q) use ($segmentId) {
                $q->where('leads.segment_id', $segmentId);
            })
            ->whereIn('lead_claims.id', function ($q) {
                $q->select(DB::raw('MAX(lc2.id)'))
                    ->from('lead_claims as lc2')
                    ->whereNull('lc2.released_at')
                    ->whereNull('lc2.deleted_at')
                    ->whereColumn('lc2.lead_id', 'lead_claims.lead_id')
                    ->groupBy('lc2.lead_id');
            })
            ->leftJoin('lead_sources', 'lead_sources.id', '=', 'leads.source_id')
            ->leftJoin('lead_segments', 'lead_segments.id', '=', 'leads.segment_id')
            ->leftJoinSub($latestQuotationSubquery, 'latest_quo', function ($join) {
                $join->on('latest_quo.lead_id', '=', 'leads.id');
            })
            ->leftJoin('quotations', function ($join) {
                $join->on('quotations.lead_id', '=', 'latest_quo.lead_id')
                    ->on('quotations.created_at', '=', 'latest_quo.latest_date');
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
}
