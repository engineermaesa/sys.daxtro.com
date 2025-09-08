<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Leads\{LeadClaim, LeadMeeting, LeadStatus, LeadStatusLog};

class TrashUnscheduledLeads extends Command
{
    protected $signature = 'leads:trash-unscheduled';

    protected $description = 'Move claimed leads to TRASH_COLD when no meeting is scheduled after 2 days';

    public function handle()
    {
        $threshold = now()->subDays(7);
        $count = 0;

        LeadClaim::with('lead')
            ->whereNull('released_at')
            ->where('claimed_at', '<=', $threshold)
            ->chunkById(100, function ($claims) use (&$count) {
                foreach ($claims as $claim) {
                    $hasMeeting = LeadMeeting::where('lead_id', $claim->lead_id)
                        ->where('created_at', '>=', $claim->claimed_at)
                        ->exists();

                    if (! $hasMeeting && $claim->lead && $claim->lead->status_id == LeadStatus::COLD) {
                        DB::transaction(function () use ($claim, &$count) {
                            $lead = $claim->lead;
                            $firstClaim = $lead->claims()->orderBy('claimed_at')->first();
                            if (! $lead->first_sales_id && $firstClaim) {
                                $lead->first_sales_id = $firstClaim->sales_id;
                            }
                            $lead->update(['status_id' => LeadStatus::TRASH_COLD]);
                            $claim->update(['released_at' => now()]);

                            LeadStatusLog::create([
                                'lead_id'   => $lead->id,
                                'status_id' => LeadStatus::TRASH_COLD,
                            ]);

                            $count++;
                        });
                    }
                }
            });

        $this->info("Moved {$count} unscheduled leads to trash");
    }
}
