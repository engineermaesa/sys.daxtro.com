<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('segment_id')
                ->constrained('ref_branches')
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });

        DB::statement('UPDATE leads l JOIN ref_regions r ON l.region_id = r.id SET l.branch_id = r.branch_id');
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
