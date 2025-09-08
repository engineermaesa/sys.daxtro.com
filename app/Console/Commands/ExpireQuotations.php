<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Orders\Quotation;
use App\Models\UserBalanceLog;
use App\Models\Leads\{LeadStatus, LeadStatusLog};

class ExpireQuotations extends Command
{
    protected $signature = 'quotations:expire';

    protected $description = 'Expire quotations and update user balance logs';

    public function handle()
    {
        $quotations = Quotation::whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now())
            ->where('status', '!=', 'expired')
            ->get();

        $countLogs = 0;
        $countQuotations = 0;
        $countLeads = 0;

        foreach ($quotations as $quotation) {
            // Update user balance logs
            $updatedLogs = UserBalanceLog::where('quotation_id', $quotation->id)
                ->where('status', 'pending')
                ->update(['status' => 'expired']);
            $countLogs += $updatedLogs;

            $previousStatus = $quotation->status;

            // Update quotation status
            $quotation->update(['status' => 'expired']);
            $countQuotations++;

            if ($previousStatus !== 'published' && $quotation->lead) {
                $lead = $quotation->lead;

                if ($lead->status_id == LeadStatus::WARM) {
                    $firstClaim = $lead->claims()->orderBy('claimed_at')->first();
                    if (! $lead->first_sales_id && $firstClaim) {
                        $lead->first_sales_id = $firstClaim->sales_id;
                    }
                    $lead->update(['status_id' => LeadStatus::TRASH_WARM]);

                    LeadStatusLog::create([
                        'lead_id'   => $lead->id,
                        'status_id' => LeadStatus::TRASH_WARM,
                    ]);

                    $countLeads++;
                }
            }
        }

        $this->info("Expired {$countLogs} user balance logs");
        $this->info("Expired {$countQuotations} quotations");
        $this->info("Moved {$countLeads} leads to trash warm");
    }
}