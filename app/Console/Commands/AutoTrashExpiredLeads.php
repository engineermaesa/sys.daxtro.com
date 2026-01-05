<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AutoTrashExpiredLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:auto-trash {--force : Force run without time check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto trash expired cold (10+ days) and warm (30+ days) leads';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting auto trash process...');

        try {
            if ($this->option('force')) {
                // Force run without time check
                $result = \App\Services\AutoTrashService::trashExpiredLeads();
            } else {
                // Run with time check
                $result = \App\Services\AutoTrashService::triggerIfNeeded();
            }

            $coldTrashed = $result['cold_trashed'];
            $warmTrashed = $result['warm_trashed'];
            $total = $coldTrashed + $warmTrashed;

            if ($total > 0) {
                $this->info("Auto trash completed successfully!");
                $this->line("- Cold leads trashed: {$coldTrashed}");
                $this->line("- Warm leads trashed: {$warmTrashed}");
                $this->line("- Total trashed: {$total}");
            } else {
                $this->info("No expired leads found to trash.");
            }

        } catch (\Exception $e) {
            $this->error("Auto trash failed: " . $e->getMessage());
        }
    }
}
