<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Leads\Lead;
use App\Models\Leads\LeadActivityLog;
use App\Observers\LeadActivityLogObserver;
use App\Observers\LeadObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Lead::observe(LeadObserver::class);
        LeadActivityLog::observe(LeadActivityLogObserver::class);
        view()->addNamespace('errors', resource_path('views/errors'));
    }
}
