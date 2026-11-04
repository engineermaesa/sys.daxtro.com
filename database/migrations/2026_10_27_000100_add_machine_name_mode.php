<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_customer_products', function (Blueprint $table) {
            $table->string('machine_name_mode')->default('auto');
        });
    }

    public function down(): void
    {
        Schema::table('service_customer_products', function (Blueprint $table) {
            $table->dropColumn('machine_name_mode');
        });
    }
};
