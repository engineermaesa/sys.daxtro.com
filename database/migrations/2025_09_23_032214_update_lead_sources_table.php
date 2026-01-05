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
        DB::table('lead_sources')
            ->where('name', 'Expo')
            ->update(['name' => 'Expo RHVAC Jakarta 2025']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('lead_sources')
            ->where('name', 'Expo RHVAC Jakarta 2025')
            ->update(['name' => 'Expo']);
    }
};
