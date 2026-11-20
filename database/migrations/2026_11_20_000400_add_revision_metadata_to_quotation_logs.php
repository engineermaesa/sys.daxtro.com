<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('quotation_logs', 'revision_no')) {
            Schema::table('quotation_logs', fn (Blueprint $table) => $table->unsignedInteger('revision_no')->nullable());
        }
        if (!Schema::hasColumn('quotation_logs', 'revision_code')) {
            Schema::table('quotation_logs', fn (Blueprint $table) => $table->string('revision_code')->nullable());
        }
    }

    public function down(): void
    {
        foreach (['revision_code', 'revision_no'] as $column) {
            if (Schema::hasColumn('quotation_logs', $column)) {
                Schema::table('quotation_logs', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
