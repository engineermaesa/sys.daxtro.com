<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadStatus;
use App\Models\Leads\LeadClaim;
use App\Models\Orders\Quotation;
use App\Models\Orders\Invoice;


class BMSummaryController extends Controller {

    public function grid(Request $request)
    {
        $user = Auth::user();
        $branchId = $user?->branch_id;

        $target = User::with('role')
            ->where('branch_id', $branchId)
            ->whereHas('role', function ($q) {
                $q->where('code', 'sales');
            })
            ->get()
            ->sum(function (User $u) {
                $monthly = $u->monthly_targets ?? [];

                $sum = 0.0;
                foreach ($monthly as $item) {
                    $sum += (float) ($item['amount'] ?? 0);
                }

                return $sum;
            });
            // Target: jumlah target semua SALES di branch ini.
            // Mendukung 2 format kolom `target`:
            // - "angka" biasa, mis: 1780218000.00
            // - "angka|json", mis: 100000000|{"1":{"percentage":"10","amount":"10000000"}, ...}
            //   Jika JSON punya amount > 0, kita jumlahkan JSON-nya; kalau tidak, pakai angka depannya.
            $target = User::with('role')
                ->where('branch_id', $branchId)
                ->whereHas('role', function ($q) {
                    $q->where('code', 'sales');
                })
                ->get()
                ->sum(function (User $u) {
                    $monthly = $u->monthly_targets ?? [];

                    $jsonSum = 0.0;
                    foreach ($monthly as $item) {
                        $jsonSum += (float) ($item['amount'] ?? 0);
                    }

                    if ($jsonSum > 0) {
                        return $jsonSum;
                    }

                    // fallback ke total di depan '|' atau angka polos
                    return (float) ($u->target_total ?? 0);
                });

        // Closed deals: claim aktif dengan status DEAL hanya untuk sales di branch ini
        $claims = LeadClaim::with(['lead.quotation.proformas.paymentConfirmation'])
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::DEAL))
            ->whereNull('released_at')
            ->whereHas('sales', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });

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

        // Potential dealing: sama seperti DashSummary, tapi hanya leads di branch ini
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
            ->whereIn('leads.status_id', [$warmStatusId, $hotStatusId])
            ->where('leads.branch_id', $branchId);

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

        $paymentLeads = Lead::query()
            ->whereIn('status_id', [$warmStatusId, $hotStatusId])
            ->where('branch_id', $branchId)
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

        $merged = collect()
            ->merge($potentialCollection)
            ->merge($paymentCollection)
            ->unique('id');

        $potentialTotalOpportunity = $merged->count();
        $potentialTotalAmount = $merged->sum('amount');

        $conversionRate = $potentialTotalOpportunity > 0
            ? round(($closedDeals / $potentialTotalOpportunity) * 100, 2)
            : 0;

        // ACTIVE LEADS untuk branch ini saja
        $leadQuery = Lead::query()->where('branch_id', $branchId);

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

    public function ActiveOpportunities()
    {
        return response()->json([
            'message' => 'BM Summary Active Opportunities API',
            'data' => [
                // Sample data for Active Opportunities
                [
                    'id' => 1,
                    'name' => 'Opportunity 1',
                    'status' => 'Active',
                ],
                [
                    'id' => 2,
                    'name' => 'Opportunity 2',
                    'status' => 'Active',
                ],
            ],
        ]);
    }

    public function SalesTrend()
    {
        return response()->json([
            'message' => 'BM Summary Sales Trend API',
            'data' => [
                // Sample data for Sales Trend
                [
                    'month' => 'January',
                    'sales' => 1000,
                ],
                [
                    'month' => 'February',
                    'sales' => 1500,
                ],
            ],
        ]);
    }
}
