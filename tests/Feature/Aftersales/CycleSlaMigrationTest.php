<?php

namespace Tests\Feature\Aftersales;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CycleSlaMigrationTest extends TestCase
{
    public function test_backfill_scopes_surveys_to_cycles_preserves_legacy_and_roundtrips(): void
    {
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->string('workflow_version');
            $t->timestamp('sla_stopped_at')->nullable();
        });
        Schema::create('ticket_cycles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->unsignedInteger('cycle_no');
            foreach (['started_at', 'ended_at', 'sla_due_at', 'sla_stopped_at'] as $field) {
                $t->timestamp($field)->nullable();
            }
        });
        Schema::create('ticket_recomings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('cycle_id');
            $t->string('recoming_type');
            $t->timestamp('planned_call_at')->nullable();
            $t->timestamp('planned_visit_at')->nullable();
        });
        Schema::create('ticket_satisfactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->unsignedBigInteger('cycle_id')->nullable();
            $t->string('review_status');
            $t->timestamp('filled_at')->nullable();
            $t->softDeletes();
        });
        DB::table('tickets')->insert([
            ['id' => 1, 'workflow_version' => 'v3', 'sla_stopped_at' => '2026-10-01 10:00:00'],
            ['id' => 2, 'workflow_version' => 'legacy', 'sla_stopped_at' => '2026-10-01 10:00:00'],
        ]);
        DB::table('ticket_cycles')->insert([
            ['id' => 1, 'ticket_id' => 1, 'cycle_no' => 1, 'started_at' => '2026-10-01 08:00:00', 'ended_at' => '2026-10-03 08:00:00', 'sla_due_at' => '2026-10-02 08:00:00'],
            ['id' => 2, 'ticket_id' => 1, 'cycle_no' => 2, 'started_at' => '2026-10-03 08:00:00', 'ended_at' => null, 'sla_due_at' => null],
            ['id' => 3, 'ticket_id' => 2, 'cycle_no' => 1, 'started_at' => '2026-10-01 08:00:00', 'ended_at' => null, 'sla_due_at' => null],
        ]);
        DB::table('ticket_recomings')->insert(['cycle_id' => 2, 'recoming_type' => 'phone_call', 'planned_call_at' => '2026-10-05 09:00:00']);
        foreach ([
            [1, 1, 'normal', '2026-10-02 09:00:00', null],
            [1, 1, 'reviewed', '2026-10-04 09:00:00', null], // Beyond the archived cycle.
            [1, 2, 'normal', '2026-10-05 10:00:00', null],
            [1, 2, 'needs_review', '2026-10-05 11:00:00', null],
            [1, 2, 'rejected', '2026-10-05 12:00:00', null],
            [1, 2, 'normal', '2026-10-05 13:00:00', '2026-10-06 08:00:00'],
            [1, null, 'normal', '2026-10-05 14:00:00', null],
            [2, 3, 'normal', '2026-10-05 15:00:00', null],
        ] as [$ticket, $cycle, $status, $filled, $deleted]) {
            DB::table('ticket_satisfactions')->insert(['ticket_id' => $ticket, 'cycle_id' => $cycle, 'review_status' => $status, 'filled_at' => $filled, 'deleted_at' => $deleted]);
        }
        $migration = require database_path('migrations/2026_10_30_000400_add_cycle_sla_setup.php');
        $migration->up();
        $this->assertDatabaseHas('ticket_cycles', ['id' => 1, 'sla_mode' => 'deadline', 'sla_satisfaction_id' => 1, 'sla_stopped_at' => '2026-10-02 09:00:00']);
        $this->assertDatabaseHas('ticket_cycles', ['id' => 2, 'sla_mode' => null, 'sla_started_at' => '2026-10-05 09:00:00', 'sla_due_at' => null, 'sla_satisfaction_id' => 4]);
        $this->assertDatabaseHas('tickets', ['id' => 1, 'sla_stopped_at' => '2026-10-05 11:00:00']);
        $this->assertDatabaseHas('tickets', ['id' => 2, 'sla_stopped_at' => '2026-10-01 10:00:00']);
        $this->assertDatabaseHas('ticket_cycles', ['id' => 3, 'sla_stopped_at' => null, 'sla_satisfaction_id' => null]);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('ticket_cycles', 'sla_mode'));
        $this->assertDatabaseCount('ticket_satisfactions', 8);
        $this->assertDatabaseHas('ticket_cycles', ['id' => 1, 'sla_due_at' => '2026-10-02 08:00:00', 'sla_stopped_at' => '2026-10-02 09:00:00']);
        $migration->up();
        $this->assertDatabaseHas('ticket_cycles', ['id' => 2, 'sla_satisfaction_id' => 4]);
    }
}
