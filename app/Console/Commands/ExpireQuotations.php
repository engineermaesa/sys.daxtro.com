<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Orders\Quotation;
use App\Models\UserBalanceLog;
use App\Models\Leads\{LeadStatus, LeadStatusLog};
use App\Services\Orders\QuotationConcurrency;
use Carbon\Carbon;

class ExpireQuotations extends Command
{
    protected $signature = 'quotations:expire';

    protected $description = 'Expire quotations and update user balance logs';

    public function handle()
    {
        $quotations = Quotation::whereNotNull('expiry_date')
            ->where('expiry_date', '<=', Carbon::now('Asia/Jakarta'))
            ->where('status', '!=', 'expired')
            ->get();

        $countLogs = 0;
        $countQuotations = 0;
        $countLeads = 0;

        foreach ($quotations as $candidate) {
            [$logs, $expired, $moved] = QuotationConcurrency::transaction(function () use ($candidate) {
                QuotationConcurrency::lockLead($candidate->lead_id);
                $quotation = Quotation::with('lead')->whereKey($candidate->id)
                    ->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', Carbon::now('Asia/Jakarta'))
                    ->where('status', '!=', 'expired')->lockForUpdate()->first();
                if (!$quotation) return [0, 0, 0];

                $updatedLogs = UserBalanceLog::where('quotation_id', $quotation->id)
                    ->where('status', 'pending')->update(['status' => 'expired']);
                $previousStatus = $quotation->status;
                $quotation->update(['status' => 'expired']);
                $movedLead = 0;
                if ($previousStatus !== 'published' && $quotation->lead) {
                    $lead = $quotation->lead;
                    if ($lead->status_id == LeadStatus::WARM) {
                        $firstClaim = $lead->claims()->orderBy('claimed_at')->first();
                        if (!$lead->first_sales_id && $firstClaim) {
                            $lead->first_sales_id = $firstClaim->sales_id;
                        }
                        $lead->update(['status_id' => LeadStatus::TRASH_WARM]);
                        LeadStatusLog::create(['lead_id' => $lead->id, 'status_id' => LeadStatus::TRASH_WARM]);
                        $movedLead = 1;
                    }
                }
                return [$updatedLogs, 1, $movedLead];
            });
            $countLogs += $logs;
            $countQuotations += $expired;
            $countLeads += $moved;
        }

        $this->info("Expired {$countLogs} user balance logs");
        $this->info("Expired {$countQuotations} quotations");
        $this->info("Moved {$countLeads} leads to trash warm");
    }
}
