<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('temp_quotations', 'lock_version')) {
            Schema::table('temp_quotations', fn (Blueprint $table) => $table->unsignedInteger('lock_version')->default(0));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('temp_quotations', 'lock_version')) {
            Schema::table('temp_quotations', fn (Blueprint $table) => $table->dropColumn('lock_version'));
        }
    }
};
