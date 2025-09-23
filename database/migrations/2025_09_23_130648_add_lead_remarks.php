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
            $table->text('contact_reason')->nullable()->after('customer_type');
            $table->text('business_reason')->nullable()->after('contact_reason');
            $table->text('competitor_offer')->nullable()->after('business_reason');
            $table->text('tonage_remark')->nullable()->after('tonase');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'contact_reason',
                'business_reason', 
                'competitor_offer',
                'tonage_remark'
            ]);
        });
    }
};
