<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('service_customers', 'maps_link') && Schema::hasColumn('service_customers', 'address_maps_url')) {
            // Preserve Maps values entered through the earlier FE implementation.
            // An existing backend value remains authoritative.
            DB::table('service_customers')->whereNull('address_maps_url')->whereNotNull('maps_link')
                ->update(['address_maps_url' => DB::raw('maps_link')]);
        }
    }

    public function down(): void
    {
        // Copied customer data is retained when rolling back this compatibility migration.
    }
};
