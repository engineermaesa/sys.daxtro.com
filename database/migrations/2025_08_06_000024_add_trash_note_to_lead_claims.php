<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_claims', function (Blueprint $table) {
            $table->text('trash_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lead_claims', function (Blueprint $table) {
            $table->dropColumn('trash_note');
        });
    }
};
