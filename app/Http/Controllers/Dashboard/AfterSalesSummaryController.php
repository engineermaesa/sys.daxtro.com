<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Leads\Customer;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadStatus;
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
