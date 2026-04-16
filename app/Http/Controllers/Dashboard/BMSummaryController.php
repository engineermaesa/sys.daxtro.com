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

        $branchId = $user->branch_id;
        $salesId = $request->filled('sales_id') ? (int) $request->input('sales_id') : null;
        if ($salesId !== null && $salesId <= 0) {
            $salesId = null;
        }

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

            // Only count deals that have all proformas confirmed.
            if ($totalPayments > 0 && $approvedPayments >= $totalPayments) {
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

        $validated = $request->validate([
            'sales_id' => 'nullable|integer|exists:users,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $branchId = $bm->branch_id;
        $salesId = $validated['sales_id'] ?? ($validated['user_id'] ?? null);
        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;

        $buildLeadVolumeBaseQuery = function () use ($branchId, $salesId, $startDate, $endDate) {
            $query = LeadClaim::query()
                ->join('leads', 'lead_claims.lead_id', '=', 'leads.id')
                ->join('lead_statuses', 'leads.status_id', '=', 'lead_statuses.id')
                ->leftJoin('users as sales_users', 'sales_users.id', '=', 'lead_claims.sales_id')
                ->whereIn('lead_statuses.id', [1, 2, 3, 4, 5])
                ->whereNull('lead_claims.trash_note')
                ->whereNotNull('leads.province')
                ->whereRaw("TRIM(leads.province) <> ''")
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
        };

        $topProvinceRows = $buildLeadVolumeBaseQuery()
            ->selectRaw('leads.province as province, COUNT(*) as total_leads')
            ->groupBy('leads.province')
            ->orderByDesc('total_leads')
            ->orderBy('leads.province')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'province' => $row->province,
                    'total_leads' => (int) $row->total_leads,
                ];
            });

        if ($topProvinceRows->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data' => [],
            ]);
        }

        $topProvinces = $topProvinceRows->pluck('province')->all();

        $leadDetails = $buildLeadVolumeBaseQuery()
            ->leftJoin('ref_regions', 'ref_regions.id', '=', 'leads.region_id')
            ->leftJoin('ref_branches as region_branches', 'region_branches.id', '=', 'ref_regions.branch_id')
            ->leftJoin('ref_branches as lead_branches', 'lead_branches.id', '=', 'leads.branch_id')
            ->leftJoin('lead_sources', 'lead_sources.id', '=', 'leads.source_id')
            ->whereIn('leads.province', $topProvinces)
            ->orderBy('leads.province')
            ->orderByDesc('lead_claims.id')
            ->get([
                'leads.id as lead_id',
                'leads.name',
                'leads.company',
                'leads.needs',
                'leads.province',
                'leads.created_at',
                'lead_claims.claimed_at',
                'sales_users.name as sales_name',
                'ref_regions.name as city_name',
                'lead_sources.name as source_name',
                'lead_statuses.name as lead_stage',
                'lead_branches.name as branch_name',
            ])
            ->map(function ($row) {
                return [
                    'id' => $row->lead_id,
                    'nama' => $row->name ?: $row->company,
                    'nama_branch' => $row->branch_name ?? '-',
                    'nama_sales' => $row->sales_name ?? '-',
                    'nama_kota' => $row->city_name ?? '-',
                    'nama_provinsi' => $row->province,
                    'needs' => format_needs_label($row->needs ?? null),
                    'source' => $row->source_name ?? '-',
                    'lead_stage' => $row->lead_stage ?? '-',
                    'created_at' => $row->created_at,
                    'claimed_at' => $row->claimed_at,
                ];
            });

        $leadDetailsByProvince = $leadDetails->groupBy('nama_provinsi');

        $data = $topProvinceRows->map(function ($provinceRow) use ($leadDetailsByProvince) {
            return [
                'province' => $provinceRow['province'],
                'total_leads' => $provinceRow['total_leads'],
                'leads' => $leadDetailsByProvince->get($provinceRow['province'], collect())->values()->all(),
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => $data,
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
