<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Masters\MeetingType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!MeetingType::where('name', 'EXPO')->exists()) {
            MeetingType::create([
                'name' => 'EXPO'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        MeetingType::where('name', 'EXPO')->delete();
    }
};
