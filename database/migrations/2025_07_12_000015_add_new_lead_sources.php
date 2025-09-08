<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $sources = ['Repeat Order', 'Sales Independen'];
        foreach ($sources as $name) {
            DB::table('lead_sources')->updateOrInsert(['name' => $name], ['name' => $name]);
        }
    }

    public function down(): void
    {
        DB::table('lead_sources')->whereIn('name', ['Repeat Order', 'Sales Independen'])->delete();
    }
};