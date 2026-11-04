<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('service_customer_products', 'trader_name')) {
            return;
        }

        Schema::table('service_customer_products', function (Blueprint $table) {
            $table->string('trader_name')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('service_customer_products', 'trader_name')) {
            return;
        }

        Schema::table('service_customer_products', function (Blueprint $table) {
            $table->dropColumn('trader_name');
        });
    }
};
