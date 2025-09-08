<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('lead_sources')->updateOrInsert(['name' => 'Aftersales'], ['name' => 'Aftersales']);
    }

    public function down(): void
    {
        DB::table('lead_sources')->where('name', 'Aftersales')->delete();
    }
};
