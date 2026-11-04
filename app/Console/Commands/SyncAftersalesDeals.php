<?php

namespace App\Console\Commands;

use App\Models\Leads\Lead;
use App\Models\Leads\LeadStatus;
use App\Services\Aftersales\DealConversion;
use Illuminate\Console\Command;

class SyncAftersalesDeals extends Command
{
    protected $signature = 'aftersales:sync-deals {--dry-run : Count eligible Deals without writing data}';

    protected $description = 'Backfill missing Aftersales Deal links; possible matches are queued for review';

    public function handle(DealConversion $service): int
    {
        $query = Lead::where('status_id', LeadStatus::DEAL)->whereNotNull('deal_at')->whereDoesntHave('customerDeal');
        $this->info($query->count().' eligible Deals.');
        if (! $this->option('dry-run')) {
            $query->chunkById(100, function ($leads) use ($service) {
                foreach ($leads as $lead) {
                    $service->sync($lead);
                }
            });
        }

        return self::SUCCESS;
    }
}
