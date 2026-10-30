<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_cycles', function (Blueprint $t) {
            $t->string('sla_mode', 16)->nullable();
            $t->timestamp('sla_started_at')->nullable();
            $t->foreignId('sla_satisfaction_id')->nullable()->constrained('ticket_satisfactions')->nullOnDelete();
        });

        DB::table('tickets')->where('workflow_version', 'v3')->orderBy('id')->chunkById(200, function ($tickets) {
            foreach ($tickets as $ticket) {
                $cycles = DB::table('ticket_cycles')->where('ticket_id', $ticket->id)->orderBy('cycle_no')->get();
                foreach ($cycles as $cycle) {
                    $recoming = DB::table('ticket_recomings')->where('cycle_id', $cycle->id)->first();
                    $start = $cycle->cycle_no == 1 ? $cycle->started_at
                        : ($recoming?->recoming_type === 'phone_call' ? $recoming->planned_call_at : $recoming?->planned_visit_at);
                    $response = DB::table('ticket_satisfactions')->where('ticket_id', $ticket->id)->where('cycle_id', $cycle->id)
                        ->whereNull('deleted_at')->whereNotNull('filled_at')->whereIn('review_status', ['normal', 'needs_review', 'reviewed'])
                        ->when($cycle->ended_at, fn ($q) => $q->where('filled_at', '<=', $cycle->ended_at))
                        ->orderByDesc('filled_at')->orderByDesc('id')->first();
                    DB::table('ticket_cycles')->where('id', $cycle->id)->update([
                        'sla_mode' => $cycle->sla_due_at ? 'deadline' : null, 'sla_started_at' => $start,
                        'sla_stopped_at' => $response?->filled_at, 'sla_satisfaction_id' => $response?->id,
                    ]);
                }
                if ($cycles->isNotEmpty()) {
                    DB::table('tickets')->where('id', $ticket->id)->update([
                        'sla_stopped_at' => DB::table('ticket_cycles')->where('id', $cycles->last()->id)->value('sla_stopped_at'),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('ticket_cycles', function (Blueprint $t) {
            $t->dropConstrainedForeignId('sla_satisfaction_id');
            $t->dropColumn(['sla_mode', 'sla_started_at']);
        });
    }
};
