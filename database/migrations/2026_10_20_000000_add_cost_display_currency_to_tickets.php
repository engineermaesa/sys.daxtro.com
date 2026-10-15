<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tickets', 'cost_display_currency')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            // mata uang tampilan Final Cost yang terakhir dipilih di Step Cost
            $table->enum('cost_display_currency', ['idr', 'usd', 'cny'])->default('idr')->after('sla_due_at');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('tickets', 'cost_display_currency')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropColumn('cost_display_currency');
            });
        }
    }
};
