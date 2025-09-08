<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $bdiId = DB::table('lead_segments')->where('name', 'BDI')->value('id');
        if ($bdiId) {
            DB::table('leads')->where('segment_id', $bdiId)->update(['segment_id' => null]);
        }
        DB::table('lead_segments')->where('name', 'BDI')->delete();
    }

    public function down(): void
    {
        if (! DB::table('lead_segments')->where('name', 'BDI')->exists()) {
            DB::table('lead_segments')->insert(['name' => 'BDI']);
        }
    }
};
