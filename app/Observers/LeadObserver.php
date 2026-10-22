<?php

namespace App\Observers;

use App\Models\Leads\Lead;
use App\Models\Leads\LeadStatus;
use App\Models\User;
use App\Notifications\Leads\AvailableLeadNotification;
use Illuminate\Support\Facades\DB;

class LeadObserver
{
    public function created(Lead $lead): void
    {
        if ($lead->status_id === LeadStatus::DEAL && $lead->deal_at) {
            $this->syncAfterCommit($lead);
        }
        if ($lead->status_id === LeadStatus::PUBLISHED) {
            $this->notifySales($lead);
        }
    }

    public function updated(Lead $lead): void
    {
        if (($lead->status_id === LeadStatus::DEAL || (int) $lead->getRawOriginal('status_id') === LeadStatus::DEAL)
            && $lead->wasChanged(['status_id', 'deal_at', 'name', 'phone', 'email', 'company_address', 'factory_city_id', 'factory_province'])) {
            $this->syncAfterCommit($lead);
        }
        if ($lead->isDirty('status_id') && $lead->status_id === LeadStatus::PUBLISHED) {
            $this->notifySales($lead);
        }
    }

    private function notifySales(Lead $lead): void
    {
        if (! $lead->branch_id) {
            return;
        }

        $users = User::whereHas('role', fn ($q) => $q->where('code', 'sales'))
            ->where('branch_id', $lead->branch_id)
            ->whereNotNull('branch_id')
            ->get();

        foreach ($users as $user) {
            try {
                $user->notify(new AvailableLeadNotification($lead));
            } catch (\Exception $e) {
                \Log::error("Failed to notify sales user {$user->id}: ".$e->getMessage());
            }
        }
    }

    private function syncAfterCommit(Lead $lead): void
    {
        $snapshot = clone $lead;
        DB::afterCommit(fn () => app(\App\Services\Aftersales\DealConversion::class)->sync($snapshot));
    }
}
