<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_deals', function (Blueprint $table) {
            $table->string('review_status')->default('resolved')->index();
        });
        DB::table('customer_deals')->where('status', 'needs_review')->whereNull('service_customer_id')
            ->update(['review_status' => 'pending_review']);
    }

    public function down(): void
    {
        Schema::table('customer_deals', function (Blueprint $table) {
            $table->dropIndex(['review_status']);
            $table->dropColumn('review_status');
        });
    }
};
