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
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('industry_remark')->nullable()->after('industry_id');
            $table->unsignedBigInteger('factory_city_id')->nullable()->after('industry_remark');
            $table->string('factory_province')->nullable()->after('factory_city_id');
            $table->unsignedBigInteger('factory_industry_id')->nullable()->after('factory_province');
            
            $table->foreign('factory_city_id')->references('id')->on('ref_regions');
            $table->foreign('factory_industry_id')->references('id')->on('ref_industries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['factory_city_id']);
            $table->dropForeign(['factory_industry_id']);
            $table->dropColumn(['factory_city_id', 'factory_province', 'factory_industry_id', 'industry_remark']);
        });
    }
};
