<?php

namespace App\Services;

use App\Models\Leads\{LeadClaim, LeadStatus, LeadStatusLog};
use Illuminate\Support\Facades\DB;

class AutoTrashService
{
    public static function trashExpiredLeads()
    {
        try {
            DB::beginTransaction();

            $expiredColdClaims = LeadClaim::with('lead')
                ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::COLD))
                ->whereNull('released_at')
                ->where('claimed_at', '<', now()->subDays(3))
                ->get();

            foreach ($expiredColdClaims as $claim) {
                $lead = $claim->lead;
                $firstClaim = $lead->claims()->orderBy('claimed_at')->first();
                if (!$lead->first_sales_id && $firstClaim) {
                    $lead->first_sales_id = $firstClaim->sales_id;
                }
                $lead->update(['status_id' => LeadStatus::TRASH_COLD]);
                $claim->update([
                    'released_at' => now(),
                    'trash_note' => 'Auto trashed - Cold lead expired after 10 days'
                ]);
                LeadStatusLog::create([
                    'lead_id' => $lead->id,
                    'status_id' => LeadStatus::TRASH_COLD,
                ]);
            }

            $expiredWarmClaims = LeadClaim::with('lead')
                ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::WARM))
                ->whereNull('released_at')
                ->where('claimed_at', '<', now()->subDays(7))
                ->get();

            foreach ($expiredWarmClaims as $claim) {
                $lead = $claim->lead;
                $firstClaim = $lead->claims()->orderBy('claimed_at')->first();
                if (!$lead->first_sales_id && $firstClaim) {
                    $lead->first_sales_id = $firstClaim->sales_id;
                }
                $lead->update(['status_id' => LeadStatus::TRASH_WARM]);
                $claim->update([
                    'released_at' => now(),
                    'trash_note' => 'Auto trashed - Warm lead expired after 30 days'
                ]);
                LeadStatusLog::create([
                    'lead_id' => $lead->id,
                    'status_id' => LeadStatus::TRASH_WARM,
                ]);
            }

            $expiredHotClaims = LeadClaim::with('lead')
                ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::HOT))
                ->whereNull('released_at')
                ->where('claimed_at', '<', now()->subDays(30))
                ->get();

            foreach ($expiredHotClaims as $claim) {
                $lead = $claim->lead;
                $firstClaim = $lead->claims()->orderBy('claimed_at')->first();
                if (!$lead->first_sales_id && $firstClaim) {
                    $lead->first_sales_id = $firstClaim->sales_id;
                }
                $lead->update(['status_id' => LeadStatus::TRASH_HOT]);
                $claim->update([
                    'released_at' => now(),
                    'trash_note' => 'Auto trashed - Hot lead expired after 30 days'
                ]);
                LeadStatusLog::create([
                    'lead_id' => $lead->id,
                    'status_id' => LeadStatus::TRASH_HOT,
                ]);
            }

            DB::commit();
            return [
                'cold_trashed' => $expiredColdClaims->count(),
                'warm_trashed' => $expiredWarmClaims->count(),
                'hot_trashed' => $expiredHotClaims->count(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function triggerIfNeeded()
    {
        $lastRun = cache()->get('auto_trash_last_run');
        if (!$lastRun || now()->diffInMinutes($lastRun) >= 60) {
            try {
                $result = self::trashExpiredLeads();
                cache()->put('auto_trash_last_run', now(), 3600);
                return $result;
            } catch (\Exception $e) {
                return ['cold_trashed' => 0, 'warm_trashed' => 0, 'hot_trashed' => 0];
            }
        }
        return ['cold_trashed' => 0, 'warm_trashed' => 0, 'hot_trashed' => 0];
    }
}
