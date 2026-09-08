<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_customers', function (Blueprint $table) {
            $table->foreignId('ref_province_id')->nullable()->after('address')->constrained('ref_provinces')->nullOnDelete();
            $table->foreignId('ref_region_id')->nullable()->after('ref_province_id')->constrained('ref_regions')->nullOnDelete();
        });

        Schema::table('service_customers', function (Blueprint $table) {
            $table->dropColumn(['city', 'province']);
        });
    }

    public function down(): void
    {
        Schema::table('service_customers', function (Blueprint $table) {
            $table->string('city')->nullable()->after('address');
            $table->string('province')->nullable()->after('city');
        });

        Schema::table('service_customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ref_region_id');
            $table->dropConstrainedForeignId('ref_province_id');
        });
    }
};
