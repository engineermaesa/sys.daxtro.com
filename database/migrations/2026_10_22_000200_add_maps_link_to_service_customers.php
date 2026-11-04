<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('service_customers', 'maps_link')) {
            return;
        }

        Schema::table('service_customers', function (Blueprint $table) {
            $table->string('maps_link', 2048)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('service_customers', 'maps_link')) {
            return;
        }

        Schema::table('service_customers', function (Blueprint $table) {
            $table->dropColumn('maps_link');
        });
    }
};
