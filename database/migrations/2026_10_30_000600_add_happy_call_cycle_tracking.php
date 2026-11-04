<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_logs', function (Blueprint $t) {
            $t->foreignId('satisfaction_id')->nullable()->constrained('ticket_satisfactions')->restrictOnDelete();
            $t->unique(['cycle_id', 'satisfaction_id'], 'happy_call_cycle_response_unique');
        });
        Schema::create('ticket_happy_call_escalations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->unsignedBigInteger('cycle_id');
            $t->foreign(['ticket_id', 'cycle_id'])->references(['ticket_id', 'id'])->on('ticket_cycles')->restrictOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->uuid('notification_id')->unique();
            $t->timestamp('due_at');
            $t->timestamp('notified_at');
            $t->unique(['cycle_id', 'user_id'], 'happy_call_cycle_recipient_unique');
        });
        DB::table('tickets')->where('workflow_version', 'v3')->orderBy('id')->chunkById(200, function ($tickets) {
            foreach ($tickets as $ticket) {
                $cycles = DB::table('ticket_cycles')->where('ticket_id', $ticket->id)->orderBy('cycle_no')->get();
                foreach ($cycles as $cycle) {
                    $responses = DB::table('ticket_satisfactions')->where('ticket_id', $ticket->id)->where('cycle_id', $cycle->id)
                        ->whereNull('deleted_at')->when($cycle->ended_at, fn ($q) => $q->where('filled_at', '<=', $cycle->ended_at));
                    $current = (clone $responses)->orderByDesc('filled_at')->orderByDesc('id')->first();
                    $accepted = $current && $current->filled_at && in_array($current->review_status, ['normal', 'needs_review', 'reviewed'], true)
                        && in_array($current->source, ['link', 'admin'], true) && ! ($current->source === 'admin' && $current->review_status === 'normal');
                    $due = $accepted ? Carbon::parse($current->filled_at, 'Asia/Jakarta')->addDays(3) : null;
                    DB::table('ticket_cycles')->where('id', $cycle->id)->update(['happy_call_due_at' => $due]);
                    if ($accepted && $current->review_status !== 'needs_review' && (clone $responses)->whereNotNull('filled_at')->count() === 1) {
                        $log = DB::table('ticket_logs')->where('ticket_id', $ticket->id)->where('cycle_id', $cycle->id)
                            ->where('step', 'happy_call_completed')->whereNull('deleted_at')->where('created_at', '>=', $current->filled_at)->orderBy('id')->first();
                        if ($log) {
                            DB::table('ticket_logs')->where('id', $log->id)->update(['satisfaction_id' => $current->id]);
                        }
                    }
                }
                if ($cycles->isNotEmpty()) {
                    DB::table('tickets')->where('id', $ticket->id)->update(['happy_call_due_at' => DB::table('ticket_cycles')->where('id', $cycles->last()->id)->value('happy_call_due_at')]);
                }
            }
        });
    }

    public function down(): void
    {
        if (DB::table('ticket_happy_call_escalations')->exists() || DB::table('ticket_logs')->whereNotNull('satisfaction_id')->exists()) {
            throw new RuntimeException('Happy Call survey links or escalation history exist; rollback requires an explicit preservation migration.');
        }
        Schema::dropIfExists('ticket_happy_call_escalations');
        Schema::table('ticket_logs', function (Blueprint $t) {
            $t->dropUnique('happy_call_cycle_response_unique');
            $t->dropConstrainedForeignId('satisfaction_id');
        });
    }
};
