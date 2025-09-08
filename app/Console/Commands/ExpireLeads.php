<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Leads\{LeadClaim, LeadStatus, LeadStatusLog};

class ExpireLeads extends Command
{
    protected $signature = 'leads:expire';

    protected $description = 'Expire inactive claimed leads';

    public function handle()
    {
        $claims = LeadClaim::with('lead')
            ->where('claimed_at', '<=', now()->subDays(30))
            ->whereNull('released_at')
            ->get();

        foreach ($claims as $claim) {
            $lead = $claim->lead;
            
            $firstClaim = $lead->claims()->orderBy('claimed_at')->first();
            if (! $lead->first_sales_id && $firstClaim) {
                $lead->first_sales_id = $firstClaim->sales_id;
            }
            $lead->status_id = LeadStatus::TRASH_COLD;

            $lead->save();

            $claim->released_at = now();
            $claim->save();

            LeadStatusLog::create([
                'lead_id'   => $lead->id,
                'status_id' => LeadStatus::TRASH_COLD,
            ]);
        }

        $this->info('Expired '.count($claims).' leads');
    }
}
