<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE quotation_logs MODIFY quotation_id BIGINT UNSIGNED NULL');

        Schema::table('quotation_logs', function (Blueprint $table) {
            $table->foreignId('temp_quotation_id')->nullable()->after('quotation_id')->constrained('temp_quotations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotation_logs', function (Blueprint $table) {
            $table->dropForeign(['temp_quotation_id']);
            $table->dropColumn('temp_quotation_id');
        });

        DB::statement('ALTER TABLE quotation_logs MODIFY quotation_id BIGINT UNSIGNED NOT NULL');
    }
};
