<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ticket_service_forms', 'unlocked_with_recoming_signature_policy')) {
            Schema::table('ticket_service_forms', fn (Blueprint $table) => $table
                ->boolean('unlocked_with_recoming_signature_policy')->default(false));
        }
        if (! Schema::hasColumn('ticket_cycles', 'service_form_resign_required')) {
            Schema::table('ticket_cycles', fn (Blueprint $table) => $table
                ->boolean('service_form_resign_required')->default(false));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ticket_cycles', 'service_form_resign_required')) {
            Schema::table('ticket_cycles', fn (Blueprint $table) => $table->dropColumn('service_form_resign_required'));
        }
        if (Schema::hasColumn('ticket_service_forms', 'unlocked_with_recoming_signature_policy')) {
            Schema::table('ticket_service_forms', fn (Blueprint $table) => $table->dropColumn('unlocked_with_recoming_signature_policy'));
        }
    }
};
