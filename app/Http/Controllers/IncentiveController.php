<?php

namespace App\Http\Controllers;

use App\Models\UserBalance;
use App\Models\UserBalanceLog;
use App\Models\Leads\LeadSource;
use App\Models\Masters\Branch;
use App\Models\User;

class IncentiveController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (! $user->hasPermission('incentives.view')) {
            abort(403);
        }

        $balance = UserBalance::firstOrCreate(['user_id' => $user->id], ['total_balance' => 0]);
        $logs = UserBalanceLog::where('user_id', $user->id)->orderByDesc('created_at')->get();

        $leadSources = LeadSource::orderBy('name')->get();

        $branches = Branch::all();

        $defaultStart = now()->startOfMonth()->format('Y-m-d');
        $defaultEnd   = now()->endOfMonth()->format('Y-m-d');

        $defaultYtdStart = now()->startOfYear()->format('Y-m-d');
        $defaultYtdEnd   = now()->endOfMonth()->format('Y-m-d');

        $salesQuery = User::query()->whereHas('role', fn($q) => $q->where('code', 'sales'));
        $roleCode = $user->role?->code;
        if ($roleCode === 'sales') {
            $salesQuery->where('id', $user->id);
        } elseif ($roleCode === 'branch_manager') {
            $salesQuery->where('branch_id', $user->branch_id);
        }
        $salesUsers = $salesQuery->orderBy('name')->get(['id', 'name', 'branch_id']);

        $showOrders = $user->hasPermission('orders');

        return $this->render('pages.incentives.dashboard', [
            'balance' => $balance,
            'logs' => $logs,
            'leadSources' => $leadSources,
            'branches' => $branches,
            'currentBranchId' => $user->branch_id,
            'defaultStart' => $defaultStart,
            'defaultEnd' => $defaultEnd,
            'defaultYtdStart' => $defaultYtdStart,
            'defaultYtdEnd' => $defaultYtdEnd,
            'salesUsers' => $salesUsers,
            'showOrders' => $showOrders,
        ]);
    }
}
