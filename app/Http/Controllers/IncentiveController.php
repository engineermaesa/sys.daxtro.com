<?php

namespace App\Http\Controllers;

use App\Models\UserBalance;
use App\Models\UserBalanceLog;

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

        return $this->render('pages.incentives.dashboard', [
            'balance' => $balance,
            'logs' => $logs,
        ]);
    }
}
