<?php

namespace App\Observers;

use App\Models\Leads\LeadActivityLog;
use App\Models\User;
use App\Notifications\Leads\LeadActivityNotification;

class LeadActivityLogObserver
{
    public function created(LeadActivityLog $activityLog): void
    {
        $lead = $activityLog->lead;
        if (!$lead || !$lead->branch_id) return;

        $sales = $activityLog->user;
        if (!$sales) return;

        User::whereHas('role', fn($q) => $q->where('code', 'branch_manager'))
            ->where('branch_id', $lead->branch_id)
            ->get()
            ->each->notify(new LeadActivityNotification($activityLog, $lead, $sales));
    }
}