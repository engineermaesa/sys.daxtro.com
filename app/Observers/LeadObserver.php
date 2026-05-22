<?php

namespace App\Observers;

use App\Models\Leads\Lead;
use App\Models\Leads\LeadStatus;
use App\Models\User;
use App\Notifications\Leads\AvailableLeadNotification;

class LeadObserver
{
    public function created(Lead $lead): void
    {
        if ($lead->status_id === LeadStatus::PUBLISHED) {
            $this->notifySales($lead);
        }
    }

    public function updated(Lead $lead): void
    {
        if ($lead->isDirty('status_id') && $lead->status_id === LeadStatus::PUBLISHED) {
            $this->notifySales($lead);
        }
    }

    private function notifySales(Lead $lead): void
    {
        if (!$lead->branch_id) return;

        $users = User::whereHas('role', fn($q) => $q->where('code', 'sales'))
            ->where('branch_id', $lead->branch_id)
            ->whereNotNull('branch_id')
            ->get();

        foreach ($users as $user) {
            try {
                $user->notify(new AvailableLeadNotification($lead));
            } catch (\Exception $e) {
                \Log::error("Failed to notify sales user {$user->id}: " . $e->getMessage());
            }
        }
    }
}
