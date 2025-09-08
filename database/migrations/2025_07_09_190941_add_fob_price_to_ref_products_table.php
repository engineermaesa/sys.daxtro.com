<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ref_products', function (Blueprint $table) {
            $table->double('fob_price', 15)->nullable()->after('vat');
        });    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ref_products', function (Blueprint $table) {
            $table->dropColumn('fob_price');
        });
    }
};
