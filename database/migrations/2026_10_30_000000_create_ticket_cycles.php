<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CHILDREN = ['ticket_recomings', 'ticket_visits', 'ticket_logs', 'ticket_satisfactions', 'satisfaction_links'];

    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('workflow_version', 16)->default('legacy')->index();
            $table->timestamp('happy_call_due_at')->nullable();
            $table->timestamp('sla_stopped_at')->nullable();
        });

        Schema::create('ticket_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->unsignedInteger('cycle_no');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('sla_value')->nullable();
            $table->string('sla_unit', 16)->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('sla_stopped_at')->nullable();
            $table->timestamp('happy_call_due_at')->nullable();
            $table->timestamps();
            $table->unique(['ticket_id', 'cycle_no']);
            $table->unique(['ticket_id', 'id']);
        });

        foreach (self::CHILDREN as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->unsignedBigInteger('cycle_id')->nullable()->index();
                $table->foreign(['ticket_id', 'cycle_id'])
                    ->references(['ticket_id', 'id'])->on('ticket_cycles')->restrictOnDelete();
                if ($name === 'ticket_recomings') {
                    $table->unique('cycle_id');
                }
            });
        }

        DB::table('tickets')->orderBy('id')->chunkById(200, function ($tickets) {
            foreach ($tickets as $ticket) {
                $recomings = DB::table('ticket_recomings')->where('ticket_id', $ticket->id)->orderBy('id')->get();
                $start = $ticket->created_at ?? now();
                $initial = DB::table('ticket_cycles')->insertGetId([
                    'ticket_id' => $ticket->id, 'cycle_no' => 1, 'started_at' => $start,
                    'ended_at' => $recomings->isEmpty() ? null : ($recomings->first()->created_at ?? $start),
                    'sla_value' => $ticket->sla_value ?? null, 'sla_unit' => $ticket->sla_unit ?? null,
                    'sla_due_at' => $ticket->sla_due_at ?? null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                foreach ($recomings as $index => $recoming) {
                    $id = DB::table('ticket_cycles')->insertGetId([
                        'ticket_id' => $ticket->id, 'cycle_no' => $index + 2,
                        'started_at' => $recoming->created_at ?? $start,
                        'ended_at' => $index + 1 < $recomings->count() ? ($recomings->get($index + 1)->created_at ?? $start) : null,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    DB::table('ticket_recomings')->where('id', $recoming->id)->update(['cycle_id' => $id]);
                }
                if ($recomings->isEmpty()) {
                    foreach (array_diff(self::CHILDREN, ['ticket_recomings']) as $name) {
                        DB::table($name)->where('ticket_id', $ticket->id)->update(['cycle_id' => $initial]);
                    }
                }
            }
        });

        Schema::table('tickets', fn (Blueprint $table) => $table->string('workflow_version', 16)->default('v3')->change());
    }

    public function down(): void
    {
        foreach (array_reverse(self::CHILDREN) as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->dropForeign(['ticket_id', 'cycle_id']);
                if ($name === 'ticket_recomings') {
                    $table->dropUnique(['cycle_id']);
                }
                $table->dropIndex(['cycle_id']);
                $table->dropColumn('cycle_id');
            });
        }
        Schema::dropIfExists('ticket_cycles');
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['workflow_version']);
            $table->dropColumn(['workflow_version', 'happy_call_due_at', 'sla_stopped_at']);
        });
    }
};
