<?php

namespace Tests\Feature\Aftersales;

use App\Models\Aftersales\SatisfactionLink;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketCycle;
use App\Models\Aftersales\TicketLog;
use App\Models\Aftersales\TicketSatisfaction;
use App\Models\Aftersales\TicketVisit;
use App\Services\Aftersales\TicketCycles;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketCyclesTest extends TestCase
{
    private function migration()
    {
        return require database_path('migrations/2026_10_30_000000_create_ticket_cycles.php');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->string('ticket_code')->nullable();
            $t->unsignedInteger('sla_value')->nullable();
            $t->string('sla_unit')->nullable();
            $t->timestamp('sla_due_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        foreach (['ticket_recomings', 'ticket_visits', 'ticket_logs', 'ticket_satisfactions', 'satisfaction_links'] as $name) {
            Schema::create($name, function (Blueprint $t) use ($name) {
                $t->id();
                $t->foreignId('ticket_id')->constrained('tickets');
                if ($name === 'ticket_recomings') {
                    $t->unsignedInteger('visit_no');
                    $t->timestamp('planned_visit_at');
                }
                if ($name === 'ticket_logs') {
                    $t->string('step');
                }
                if ($name === 'ticket_visits') {
                    $t->timestamp('scheduled_at');
                }
                if ($name === 'ticket_satisfactions') {
                    $t->timestamp('filled_at')->nullable();
                    $t->string('review_status')->default('normal');
                }
                $t->timestamps();
                $t->softDeletes();
            });
        }
    }

    private function ticket(): Ticket
    {
        if (! Schema::hasColumn('ticket_cycles', 'sla_mode')) {
            (require database_path('migrations/2026_10_30_000400_add_cycle_sla_setup.php'))->up();
        }
        $ticket = Ticket::create(['workflow_version' => Ticket::WORKFLOW_V3, 'sla_value' => 3, 'sla_unit' => 'day', 'sla_due_at' => '2026-11-02 10:00:00']);
        TicketCycles::initial($ticket);

        return $ticket;
    }

    public function test_backfill_keeps_legacy_workflow_and_includes_deleted_history_without_guessing_cycles(): void
    {
        $ticket = Ticket::create(['ticket_code' => 'old', 'sla_value' => 2, 'sla_unit' => 'day', 'sla_due_at' => '2026-11-01 10:00:00']);
        $ticket->delete();
        foreach ([2, 4] as $visitNo) {
            DB::table('ticket_recomings')->insert([
                'ticket_id' => $ticket->id, 'visit_no' => $visitNo,
                'planned_visit_at' => '2026-11-02 10:00:00', 'created_at' => '2026-10-30 10:00:00',
                'deleted_at' => $visitNo === 2 ? now() : null,
            ]);
        }
        DB::table('ticket_logs')->insert(['ticket_id' => $ticket->id, 'step' => 'happy_call_completed']);
        DB::table('ticket_satisfactions')->insert(['ticket_id' => $ticket->id]);

        $this->migration()->up();

        $ticket = Ticket::withTrashed()->findOrFail($ticket->id);
        $this->assertSame('legacy', $ticket->workflow_version);
        $this->assertSame([1, 2, 3], $ticket->cycles()->orderBy('cycle_no')->pluck('cycle_no')->all());
        $this->assertSame(3, $ticket->currentCycle->cycle_no);
        $this->assertSame('2026-11-01 10:00:00', $ticket->cycles()->first()->sla_due_at->format('Y-m-d H:i:s'));
        $this->assertNull($ticket->currentCycle->sla_due_at);
        $this->assertNull($ticket->sla_stopped_at);
        $this->assertNull($ticket->happy_call_due_at);
        $this->assertNotNull(DB::table('ticket_recomings')->first()->cycle_id);
        $this->assertNull(DB::table('ticket_logs')->first()->cycle_id);
        $this->assertNull(DB::table('ticket_satisfactions')->first()->cycle_id);
    }

    public function test_single_cycle_legacy_children_are_backfilled_including_deleted_rows(): void
    {
        $ticket = Ticket::create(['ticket_code' => 'single']);
        foreach (['ticket_logs', 'ticket_visits', 'ticket_satisfactions', 'satisfaction_links'] as $name) {
            $row = ['ticket_id' => $ticket->id, 'deleted_at' => now()];
            if ($name === 'ticket_logs') {
                $row['step'] = 'documentation_published';
            }
            if ($name === 'ticket_visits') {
                $row['scheduled_at'] = now();
            }
            DB::table($name)->insert($row);
        }
        $this->migration()->up();
        $id = $ticket->fresh()->currentCycle->id;
        foreach (['ticket_logs', 'ticket_visits', 'ticket_satisfactions', 'satisfaction_links'] as $name) {
            $this->assertSame($id, DB::table($name)->value('cycle_id'));
        }
    }

    public function test_initial_cycle_is_idempotent_and_new_writes_follow_the_current_cycle(): void
    {
        $this->migration()->up();
        $ticket = $this->ticket();
        $initial = TicketCycles::initial($ticket);
        $this->assertSame($initial->id, TicketCycles::initial($ticket)->id);
        $this->assertSame(1, $ticket->cycles()->count());
        $log = TicketLog::create(['ticket_id' => $ticket->id, 'step' => 'documentation_published']);
        $this->assertSame($initial->id, $log->cycle_id);

        $next = DB::transaction(function () use ($ticket) {
            $cycle = TicketCycles::startRecoming($ticket);
            $visit = TicketVisit::create(['ticket_id' => $ticket->id, 'scheduled_at' => now()]);
            $log = TicketLog::create(['ticket_id' => $ticket->id, 'step' => 'happy_call_completed']);
            $response = TicketSatisfaction::create(['ticket_id' => $ticket->id]);
            $link = SatisfactionLink::create(['ticket_id' => $ticket->id]);
            foreach ([$visit, $log, $response, $link] as $model) {
                $this->assertSame($cycle->id, $model->cycle_id);
                $this->assertTrue($model->cycle->is($cycle));
            }

            return $cycle;
        });
        $this->assertSame(2, $next->cycle_no);
        $this->assertSame($next->id, $ticket->currentCycle->id);
        $this->assertNotNull($initial->fresh()->ended_at);
        $this->assertNull($next->sla_due_at);
        $this->assertSame($next->id, $ticket->fresh()->currentCycle->id);
        $this->assertSame(1, $next->surveyResponses()->count());
        $this->assertSame(1, $next->surveyLinks()->count());
        $this->assertSame(1, $initial->logs()->count());
    }

    public function test_new_orm_ticket_uses_v3_default_and_gets_an_initial_cycle(): void
    {
        $this->migration()->up();
        $ticket = Ticket::create(['ticket_code' => 'new']);
        $this->assertSame('v3', $ticket->workflow_version);
        $this->assertSame(1, $ticket->cycles()->count());
        $this->assertSame(1, $ticket->currentCycle->cycle_no);
        $this->assertNull($ticket->fresh()->happy_call_due_at);
        $this->assertNull($ticket->fresh()->sla_stopped_at);
    }

    public function test_explicit_survey_cycle_is_preserved_after_a_new_cycle_starts(): void
    {
        $this->migration()->up();
        $ticket = $this->ticket();
        $initial = $ticket->currentCycle;
        TicketCycles::startRecoming($ticket);
        $response = TicketSatisfaction::create(['ticket_id' => $ticket->id, 'cycle_id' => $initial->id]);
        $this->assertSame($initial->id, $response->cycle_id);
    }

    public function test_recoming_transaction_rollback_restores_previous_cycle_and_does_not_consume_sequence(): void
    {
        $this->migration()->up();
        $ticket = $this->ticket();
        try {
            DB::transaction(function () use ($ticket) {
                TicketCycles::startRecoming($ticket);
                throw new \RuntimeException('Scheduling failed');
            });
        } catch (\RuntimeException $e) {
            $this->assertSame('Scheduling failed', $e->getMessage());
        }
        $this->assertSame(1, $ticket->cycles()->count());
        $this->assertNull($ticket->fresh()->currentCycle->ended_at);
        $this->assertSame(2, TicketCycles::startRecoming($ticket)->cycle_no);
    }

    public function test_foreign_key_rejects_a_cycle_owned_by_another_ticket(): void
    {
        $this->migration()->up();
        $first = $this->ticket();
        $second = $this->ticket();
        $this->expectException(QueryException::class);
        TicketLog::create(['ticket_id' => $first->id, 'cycle_id' => $second->currentCycle->id, 'step' => 'documentation_published']);
    }

    public function test_duplicate_cycle_number_is_rejected_by_database(): void
    {
        $this->migration()->up();
        $ticket = $this->ticket();
        $this->expectException(QueryException::class);
        TicketCycle::create(['ticket_id' => $ticket->id, 'cycle_no' => 1, 'started_at' => now()]);
    }

    public function test_down_and_up_preserve_existing_business_data(): void
    {
        $ticket = Ticket::create(['ticket_code' => 'preserved']);
        $migration = $this->migration();
        $migration->up();
        TicketLog::create(['ticket_id' => $ticket->id, 'step' => 'happy_call_completed']);
        $migration->down();
        $this->assertFalse(Schema::hasTable('ticket_cycles'));
        $this->assertFalse(Schema::hasColumn('tickets', 'workflow_version'));
        $this->assertFalse(Schema::hasColumn('ticket_logs', 'cycle_id'));
        $this->assertSame('preserved', DB::table('tickets')->value('ticket_code'));
        $this->assertSame('happy_call_completed', DB::table('ticket_logs')->value('step'));
        $migration->up();
        $this->assertSame(1, TicketCycle::count());
        $this->assertNotNull(DB::table('ticket_logs')->value('cycle_id'));
    }
}
