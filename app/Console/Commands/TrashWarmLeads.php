<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Leads\{Lead, LeadStatus, LeadStatusLog};

class TrashWarmLeads extends Command
{
    protected $signature   = 'leads:trash-warm';
    protected $description = 'Move warm leads to TRASH_WARM after 30 days without status change';

    public function handle()
    {
        $threshold = now()->subDays(30);
        $count     = 0;

        Lead::where('status_id', LeadStatus::WARM)
            // only those whose latestStatusLog is still WARM *and* <= $threshold
            ->whereHas('latestStatusLog', function($q) use ($threshold) {
                $q->where('status_id', LeadStatus::WARM)
                  ->where('created_at', '<=', $threshold);
            })
            // chunk for memory‐safety in case you have lots of leads
            ->chunkById(100, function($leads) use (&$count) {
                foreach ($leads as $lead) {
                    $firstClaim = $lead->claims()->orderBy('claimed_at')->first();
                    if (! $lead->first_sales_id && $firstClaim) {
                        $lead->first_sales_id = $firstClaim->sales_id;
                    }
                    $lead->status_id = LeadStatus::TRASH_WARM;
                    $lead->save();

                    LeadStatusLog::create([
                        'lead_id'   => $lead->id,
                        'status_id' => LeadStatus::TRASH_WARM,
                    ]);

                    $count++;
                }
            });

        $this->info("Moved {$count} warm leads to trash");
    }
}