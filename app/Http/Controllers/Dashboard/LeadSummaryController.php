<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leads\{LeadClaim, LeadStatus, Lead};
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeadSummaryController extends Controller
{
    public function grid(Request $request){
        $user = Auth::user();

        // target comes from user (set by superadmin)
        $target = $user && $user->target ? (float) $user->target : 0;

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
            ->leftJoin('lead_claims', function ($join) 
            {
                $join->on('lead_claims.lead_id', '=', 'leads.id')
                    ->whereNull('lead_claims.deleted_at')
                    ->whereNull('lead_claims.released_at');})
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
                'AchievementTarget' => [
                    'target' => $target,
                    'achievment' => $monetaryActual,
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

        $activeClaims = LeadClaim::whereNull('released_at')
            ->with(['lead.product', 'lead.segment', 'lead.latestStatusLog', 'lead.quotation']);

        if ($roleCode === 'sales') {
            $activeClaims->where('sales_id', $user?->id);
        } elseif ($roleCode === 'branch_manager') {
            $activeClaims->whereHas('sales', function ($q) use ($user) {
                $q->where('branch_id', $user?->branch_id);
            });
        }

        $claims = $activeClaims->get();

        $uniqueLeads = $claims->pluck('lead')->filter()->unique('id');

        // Only include Cold, Warm, Hot stages (exclude Deal and trash)
        $allowedStatuses = [LeadStatus::COLD, LeadStatus::WARM, LeadStatus::HOT];
        $uniqueLeads = $uniqueLeads->filter(function ($l) use ($allowedStatuses) {
            return in_array($l->status_id, $allowedStatuses);
        });

        $result = $uniqueLeads->map(function ($lead) {
            $amount = (float) ($lead->quotation->grand_total ?? 0);
            $stage = $lead->status?->name ?? $lead->status_id;
            $product = $lead->product?->name ?? null;
            $segment = $lead->segment?->name ?? null;
            $lastActivity = $lead->latestStatusLog?->created_at ?? $lead->updated_at ?? null;

            return [
                'id' => $lead->id,
                'customer_name' => $lead->name ?? $lead->company,
                'stage' => $stage,
                'amount' => $amount,
                'product' => $product,
                'segment' => $segment,
                'last_activity' => $lastActivity ? $lastActivity->toDateTimeString() : null,
                'created_at' => $lead->created_at ? $lead->created_at->toDateString() : null,
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'Data' => $result,
        ]);
    }

    public function LeadsPerformance(Request $request)
    {
        // For Leads Performance
    }

    public function PersonalTrend(Request $request)
    {
        // For Personal Trend
    }
    public function Summary(Request $request)
    {
        // For Summary
    }
}
