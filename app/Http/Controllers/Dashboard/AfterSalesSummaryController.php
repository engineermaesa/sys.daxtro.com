<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Leads\Customer;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadStatus;
use App\Models\Masters\Province;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AfterSalesSummaryController extends Controller
{
    public function grid(Request $request)
    {
        abort_unless($request->user()?->hasPermission('customers.view'), 403);

        $now = Carbon::now('Asia/Jakarta');
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $previousMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();

        $pendingTotal = Lead::query()
            ->where('status_id', LeadStatus::DEAL)
            ->whereDoesntHave('customer')
            ->count();

        $completedTotal = Customer::query()->count();

        $completedThisMonth = Customer::query()
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->count();

        $completedPreviousMonth = Customer::query()
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'pending_profiles' => [
                    'total' => $pendingTotal,
                ],
                'completed_profiles' => [
                    'total' => $completedTotal,
                    'percentage_change' => $this->percentageChange($completedPreviousMonth, $completedThisMonth),
                    'trend' => $this->trend($completedPreviousMonth, $completedThisMonth),
                ],
            ],
        ]);
    }

    public function provinces(Request $request)
    {
        abort_unless($request->user()?->hasPermission('customers.view'), 403);

        return response()->json([
            'status' => 'success',
            'data' => Province::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function provinceDistribution(Request $request)
    {
        abort_unless($request->user()?->hasPermission('customers.view'), 403);

        $validated = $request->validate([
            'province_id' => 'nullable|integer|exists:ref_provinces,id',
            'timeframe' => 'nullable|in:monthly,yearly',
        ]);

        $timeframe = $validated['timeframe'] ?? 'monthly';
        $now = Carbon::now('Asia/Jakarta');

        if ($timeframe === 'yearly') {
            $periodStart = $now->copy()->startOfYear();
            $periodEnd = $now->copy()->endOfYear();
        } else {
            $periodStart = $now->copy()->startOfMonth();
            $periodEnd = $now->copy()->endOfMonth();
        }

        $query = Customer::query()
            ->join('leads', 'customers.leads_id', '=', 'leads.id')
            ->join('ref_regions', 'ref_regions.id', '=', 'leads.region_id')
            ->whereBetween('leads.deal_at', [$periodStart, $periodEnd]);

        if (! empty($validated['province_id'])) {
            $query->where('ref_regions.province_id', $validated['province_id']);
        }

        $data = $query
            ->selectRaw('ref_regions.name as region, COUNT(*) as total')
            ->groupBy('ref_regions.name')
            ->orderByDesc('total')
            ->orderBy('ref_regions.name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'filters' => [
                'province_id' => $validated['province_id'] ?? null,
                'timeframe' => $timeframe,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ],
        ]);
    }

    private function percentageChange(int $previous, int $current): float
    {
        if ($previous === 0) {
            return $current === 0 ? 0.0 : 100.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function trend(int $previous, int $current): string
    {
        if ($current > $previous) {
            return 'up';
        }

        if ($current < $previous) {
            return 'down';
        }

        return 'flat';
    }
}
