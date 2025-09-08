<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Leads\{LeadMeeting, LeadStatus, LeadStatusLog};

class ExpireMeetings extends Command
{
    protected $signature = 'meetings:expire';

    protected $description = 'Mark leads as TRASH_COLD when meeting expired';

    public function handle()
    {
        $meetings = LeadMeeting::with('lead')
            ->whereNull('result')
            ->where('scheduled_end_at', '<=', now()->subDays(30))
            ->get();

        foreach ($meetings as $meeting) {
            $lead = $meeting->lead;

            if($lead->status_id == LeadStatus::COLD){
                $firstClaim = $lead->claims()->orderBy('claimed_at')->first();
                if (! $lead->first_sales_id && $firstClaim) {
                    $lead->first_sales_id = $firstClaim->sales_id;
                }
                $lead->status_id = LeadStatus::TRASH_COLD;
                $lead->save();
            }

            $meeting->result = 'expired';
            $meeting->save();

            LeadStatusLog::create([
                'lead_id'   => $lead->id,
                'status_id' => LeadStatus::TRASH_COLD,
            ]);
        }

        $this->info('Expired '.count($meetings).' meetings');
    }
}
