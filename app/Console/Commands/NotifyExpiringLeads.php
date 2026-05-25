<?php

namespace App\Console\Commands;

use App\Models\Leads\{LeadClaim, LeadStatus};
use App\Models\User;
use App\Notifications\Leads\LeadExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NotifyExpiringLeads extends Command
{
    protected $signature = 'leads:notify-expiring';
    protected $description = 'Send notifications for leads that will be auto-trashed in 3 days';

    public function handle(): void
    {
        $this->info('Checking leads expiring in 3 days...');

        $initCodes   = ['A01', 'A02', 'A03', 'A04'];
        $windowStart = now('Asia/Jakarta')->subDays(28);
        $windowEnd   = now('Asia/Jakarta')->subDays(27);

        $coldClaims = LeadClaim::with(['lead.status', 'sales'])
            ->whereHas('lead', function ($q) use ($initCodes) {
                $q->where('status_id', LeadStatus::COLD)
                    ->whereDoesntHave('meetings')
                    ->whereDoesntHave('activityLogs', function ($lq) use ($initCodes) {
                        $lq->whereHas('activity', fn($a) => $a->whereIn('code', $initCodes));
                    });
            })
            ->whereNull('released_at')
            ->whereNull('trash_note')
            ->whereBetween('claimed_at', [$windowStart, $windowEnd])
            ->get();

        $warmClaims = LeadClaim::with(['lead.status', 'sales'])
            ->whereHas('lead', function ($q) {
                $q->where('status_id', LeadStatus::WARM)
                    ->whereDoesntHave('quotation');
            })
            ->whereNull('released_at')
            ->whereNull('trash_note')
            ->whereBetween('claimed_at', [$windowStart, $windowEnd])
            ->get();

        $hotClaims = LeadClaim::with(['lead.status', 'sales'])
            ->whereHas('lead', fn($q) => $q->where('status_id', LeadStatus::HOT))
            ->whereNull('released_at')
            ->whereNull('trash_note')
            ->whereBetween('claimed_at', [$windowStart, $windowEnd])
            ->get();

        $allClaims     = $coldClaims->merge($warmClaims)->merge($hotClaims);
        $notifiedCount = 0;
        $today         = now('Asia/Jakarta')->startOfDay();

        foreach ($allClaims as $claim) {
            $lead = $claim->lead;

            // Guard: skip if this lead was already notified today
            $alreadySent = DB::table('notifications')
                ->where('type', LeadExpiringNotification::class)
                ->where('created_at', '>=', $today)
                ->whereRaw("JSON_EXTRACT(data, '$.lead_id') = ?", [$lead->id])
                ->exists();

            if ($alreadySent) {
                continue;
            }

            if ($claim->sales) {
                $claim->sales->notify(new LeadExpiringNotification($lead, $claim));
            }

            User::whereHas('role', fn($q) => $q->where('code', 'branch_manager'))
                ->where('branch_id', $lead->branch_id)
                ->each(fn($bm) => $bm->notify(new LeadExpiringNotification($lead, $claim)));

            $notifiedCount++;
        }

        $this->info("Done. Sent expiring notifications for {$notifiedCount} lead(s).");
    }
}
