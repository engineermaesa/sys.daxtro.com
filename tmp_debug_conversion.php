<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Leads\LeadClaim;
use App\Models\Leads\LeadStatus;
use App\Models\Leads\Lead;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$monthKey = (string) Carbon::now('Asia/Jakarta')->month;
$yearKey = (int) Carbon::now('Asia/Jakarta')->year;
$selectedMonthStart = Carbon::createFromDate($yearKey, (int) $monthKey, 1, 'Asia/Jakarta')->startOfMonth();
$selectedMonthEnd = (clone $selectedMonthStart)->endOfMonth();
$periodStart = $selectedMonthStart->toDateTimeString();
$periodEnd = $selectedMonthEnd->toDateTimeString();

$claims = LeadClaim::with(['lead.quotation.proformas.paymentConfirmation'])
    ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::DEAL))
    ->whereNull('released_at')
    ->whereHas('lead.quotation', function ($q) use ($periodStart, $periodEnd) {
        $q->firstTermPaidBetween($periodStart, $periodEnd);
    })
    ->get();

$completedDeals = 0;
$monetaryActual = 0;
foreach ($claims as $claim) {
    $quotation = $claim->lead?->quotation;
    if (! $quotation) continue;
    $proformas = $quotation->proformas ?? collect();
    $totalPayments = $proformas->count();
    $confirmedProformas = $proformas->filter(function ($p) {
        return $p->paymentConfirmation && $p->paymentConfirmation->confirmed_at;
    });
    $approvedPayments = $confirmedProformas->count();
    if ($totalPayments > 0 && $approvedPayments >= $totalPayments) {
        $completedDeals++;
        $monthlyConfirmed = $confirmedProformas->filter(function ($p) use ($monthKey, $yearKey) {
            $d = $p->paymentConfirmation->confirmed_at ?? null;
            if (!$d) return false;
            $dt = Carbon::parse($d, 'Asia/Jakarta');
            return (string)$dt->month === $monthKey && (int)$dt->year === $yearKey;
        });
        $monetaryActual += (float) $monthlyConfirmed->sum(function ($p) {
            return (float) ($p->paymentConfirmation->amount ?? $p->amount ?? 0);
        });
    }
}

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

$potentialCollection = Lead::query()
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
    ->whereIn('leads.status_id', [$warmStatusId, $hotStatusId])
    ->select(['leads.id', 'quotations.grand_total'])
    ->distinct()
    ->get()
    ->map(fn($lead) => ['id' => $lead->id, 'amount' => (float)($lead->grand_total ?? 0)]);

$paymentCollection = Lead::query()
    ->whereIn('status_id', [$warmStatusId, $hotStatusId])
    ->whereHas('quotation.proformas.paymentConfirmation', function ($q) use ($start, $end) {
        $q->whereNotNull('confirmed_at')
            ->whereBetween('confirmed_at', [$start, $end]);
    })
    ->with(['quotation.proformas.paymentConfirmation'])
    ->get()
    ->map(function ($lead) {
        $proformas = $lead->quotation->proformas ?? collect();
        $amount = (float) $proformas->filter(function ($p) {
            return $p->paymentConfirmation && $p->paymentConfirmation->confirmed_at;
        })->sum(function ($p) {
            return (float) ($p->paymentConfirmation->amount ?? $p->amount ?? 0);
        });
        return ['id' => $lead->id, 'amount' => $amount];
    });

$merged = collect()->merge($potentialCollection)->merge($paymentCollection)->unique('id');
$potentialTotalOpportunity = $merged->count();
$conversionRate = $potentialTotalOpportunity > 0 ? round(($completedDeals / $potentialTotalOpportunity) * 100, 2) : 0;

$activeClaims = LeadClaim::whereNull('released_at')->with('lead')->get();
$uniqueLeads = $activeClaims->pluck('lead')->filter()->unique('id');
$counts = $uniqueLeads->groupBy('status_id')->map->count();
$warm = (int)($counts[LeadStatus::WARM] ?? 0);
$hot = (int)($counts[LeadStatus::HOT] ?? 0);
$deal = (int)($counts[LeadStatus::DEAL] ?? 0);

echo "period={$periodStart}..{$periodEnd}\n";
echo "active_warm={$warm}, active_hot={$hot}, active_deal={$deal}\n";
echo "deal_claims_in_period={$claims->count()}, completed_deals={$completedDeals}, monetary_actual={$monetaryActual}\n";
echo "potential_opportunity={$potentialTotalOpportunity}, conversion_rate={$conversionRate}\n";
