<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_service_forms', fn (Blueprint $t) => $t->unsignedBigInteger('sync_version')->default(1));
    }

    public function down(): void
    {
        if (DB::table('ticket_service_forms')->where('sync_version', '>', 1)->exists()) {
            throw new RuntimeException('Service Form sync revisions exist; rollback requires an explicit preservation migration.');
        }
        Schema::table('ticket_service_forms', fn (Blueprint $t) => $t->dropColumn('sync_version'));
    }
};
