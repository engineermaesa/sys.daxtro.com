<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_recomings', function (Blueprint $t) {
            $t->string('reason', 40)->change();
            $t->unsignedInteger('visit_no')->nullable()->change();
            $t->timestamp('planned_visit_at')->nullable()->change();
            $t->string('recoming_type', 16)->default('visit_site');
            $t->timestamp('planned_call_at')->nullable()->index();
            $t->foreignId('ticket_visit_id')->nullable()->constrained('ticket_visits')->restrictOnDelete();
        });

        DB::table('ticket_recomings')->whereNotNull('cycle_id')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $visits = DB::table('ticket_visits')->where('ticket_id', $row->ticket_id)->where('cycle_id', $row->cycle_id)->pluck('id');
                if ($visits->count() === 1) {
                    DB::table('ticket_recomings')->where('id', $row->id)->update(['ticket_visit_id' => $visits->first()]);
                }
            }
        });
    }

    public function down(): void
    {
        if (DB::table('ticket_recomings')->where('recoming_type', 'phone_call')->orWhere('reason', 'unfinished_previous_work')
            ->orWhereNull('planned_visit_at')->orWhereNull('visit_no')->exists()) {
            throw new RuntimeException('Cannot roll back Recoming v3 while phone-call or new-reason data exists. Preserve/export that data before an explicit rollback migration.');
        }
        Schema::table('ticket_recomings', function (Blueprint $t) {
            $t->dropConstrainedForeignId('ticket_visit_id');
            $t->dropIndex(['planned_call_at']);
            $t->dropColumn(['recoming_type', 'planned_call_at']);
            $t->unsignedInteger('visit_no')->nullable(false)->change();
            $t->timestamp('planned_visit_at')->nullable(false)->change();
            $t->enum('reason', ['sparepart_unavailable', 'further_work_needed', 'site_access', 'other'])->change();
        });
    }
};
