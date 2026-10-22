<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('service_customers', 'source')) {
            return;
        }

        Schema::table('service_customers', function (Blueprint $table) {
            // 'manual' = created via Add Customer, 'import' = created via Import Customer.
            $table->string('source', 20)->default('manual')->after('phone');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('service_customers', 'source')) {
            return;
        }

        Schema::table('service_customers', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
