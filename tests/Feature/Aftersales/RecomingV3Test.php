<?php

namespace Tests\Feature\Aftersales;

use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketRecoming;
use App\Models\Aftersales\TicketSatisfaction;
use App\Models\User;
use App\Services\Aftersales\TicketSla;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class RecomingV3Test extends TestCase
{
    private const URL = '/api/aftersales/tickets/1';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locked' => false, 'app.debug' => false]);
        Carbon::setTestNow(Carbon::parse('2026-10-30 10:00:00', 'Asia/Jakarta'));
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->softDeletes();
        });
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->string('ticket_code');
            $t->unsignedBigInteger('assigned_technician_id')->nullable();
            $t->unsignedBigInteger('supervisor_id')->nullable();
            $t->unsignedInteger('sla_value')->nullable();
            $t->string('sla_unit')->nullable();
            $t->timestamp('sla_due_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_recomings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->unsignedInteger('visit_no');
            $t->enum('reason', ['sparepart_unavailable', 'further_work_needed', 'site_access', 'other']);
            $t->text('notes')->nullable();
            $t->timestamp('planned_visit_at');
            $t->string('from_step');
            $t->unsignedBigInteger('created_by_user_id');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_visits', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->timestamp('scheduled_at');
            $t->timestamp('actual_start')->nullable();
            $t->timestamp('actual_end')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->string('step');
            $t->unsignedBigInteger('actor_user_id')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        foreach (['ticket_satisfactions', 'satisfaction_links'] as $name) {
            Schema::create($name, function (Blueprint $t) use ($name) {
                $t->id();
                $t->unsignedBigInteger('ticket_id');
                $t->string('status')->default('active');
                if ($name === 'ticket_satisfactions') {
                    $t->timestamp('filled_at')->nullable();
                    $t->string('review_status')->default('normal');
                    $t->string('source')->default('link');
                }
                $t->timestamps();
                $t->softDeletes();
            });
        }
        Schema::create('ticket_service_forms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->string('status')->default('draft');
            $t->softDeletes();
        });
        Schema::create('user_activity_logs', function (Blueprint $t) {
            $t->id();
            foreach (['user_id', 'action', 'model_type', 'model_id', 'description', 'data', 'ip_address', 'user_agent'] as $field) {
                $t->text($field)->nullable();
            }
            $t->timestamps();
        });
        (require database_path('migrations/2026_10_30_000000_create_ticket_cycles.php'))->up();
        (require database_path('migrations/2026_10_30_000200_add_recoming_v3_scheduling.php'))->up();
        (require database_path('migrations/2026_10_30_000400_add_cycle_sla_setup.php'))->up();
        $ticket = Ticket::create(['ticket_code' => 'REC-1', 'assigned_technician_id' => 2, 'supervisor_id' => 3,
            'sla_value' => 1, 'sla_unit' => 'day', 'sla_due_at' => '2026-10-31 10:00:00']);
        DB::table('ticket_service_forms')->insert(['ticket_id' => $ticket->id]);
        $ticket->visits()->create(['scheduled_at' => '2026-10-29 09:00:00']);
        $ticket->logs()->create(['step' => 'repair']);
        $this->login(1);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function login(int $id): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => $id, 'name' => 'User '.$id]);
        // Intentionally grant old Recoming permission to Technician to prove the endpoint guard changed.
        $permissions = match ($id) {
            1 => ['aftersales.tickets.manage'], 2 => ['aftersales.tickets.recoming', 'aftersales.documents.fill'], default => ['aftersales.tickets.view-all']
        };
        $user->shouldReceive('hasPermission')->andReturnUsing(fn ($p) => in_array($p, $permissions, true));
        $this->actingAs($user);
    }

    private function payload(string $type = 'phone_call'): array
    {
        return ['recoming_type' => $type, 'reason' => 'unfinished_previous_work', 'sla_mode' => 'duration', 'sla_value' => 2, 'sla_unit' => 'day',
            $type === 'phone_call' ? 'planned_call_at' : 'planned_visit_at' => '2026-11-01 14:30:00'];
    }

    public function test_phone_call_creates_no_physical_visit_and_keeps_assignment_and_old_visits(): void
    {
        $response = $this->postJson(self::URL.'/recoming', $this->payload())->assertCreated()
            ->assertJsonPath('data.data.recoming_type', 'phone_call')->assertJsonPath('data.data.visit_no', null)
            ->assertJsonPath('data.data.ticket_visit_id', null)->assertJsonPath('data.data.planned_visit_at', null);
        $this->assertNotNull($response->json('data.data.planned_call_at'));
        $this->assertDatabaseCount('ticket_visits', 1);
        $this->assertDatabaseCount('ticket_cycles', 2);
        $this->assertDatabaseHas('tickets', ['assigned_technician_id' => 2, 'supervisor_id' => 3]);
        $this->assertDatabaseHas('ticket_visits', ['scheduled_at' => '2026-10-29 09:00:00']);
        $this->assertFalse(Ticket::first()->hasVisitDue());
        $this->artisan('tickets:resume-recoming')->assertSuccessful();
        $this->assertSame('recoming', Ticket::first()->progress);
    }

    public function test_visit_site_links_exactly_one_physical_visit_to_its_cycle(): void
    {
        $response = $this->postJson(self::URL.'/recoming', $this->payload('visit_site'))->assertCreated()
            ->assertJsonPath('data.data.planned_call_at', null)->assertJsonPath('data.data.visit_no', 2);
        $this->assertDatabaseHas('ticket_visits', ['id' => $response->json('data.data.ticket_visit_id'), 'cycle_id' => $response->json('data.data.cycle_id')]);
        $this->assertDatabaseCount('ticket_visits', 2);
    }

    public function test_type_specific_fields_reasons_and_assignment_changes_are_validated(): void
    {
        foreach ([
            array_diff_key($this->payload(), ['recoming_type' => true]),
            array_replace($this->payload(), ['reason' => 'other']),
            array_replace($this->payload(), ['planned_call_at' => '2026-11-01']),
            $this->payload() + ['planned_visit_at' => '2026-11-01'],
            $this->payload('visit_site') + ['planned_call_at' => '2026-11-01 10:00:00'],
            $this->payload() + ['assigned_technician_id' => 6],
        ] as $payload) {
            $this->postJson(self::URL.'/recoming', $payload)->assertUnprocessable();
        }
        $this->assertDatabaseCount('ticket_cycles', 1);
        $this->assertDatabaseCount('ticket_recomings', 0);
    }

    public function test_only_admin_can_create_or_reschedule_even_with_old_technician_permission(): void
    {
        foreach ([2, 3, 4] as $id) {
            $this->login($id);
            $this->postJson(self::URL.'/recoming', $this->payload())->assertForbidden();
        }
        $this->login(1);
        $this->postJson(self::URL.'/recoming', $this->payload())->assertCreated();
        $this->login(2);
        $this->postJson(self::URL.'/recoming/reschedule', ['planned_call_at' => '2026-11-02 10:00:00'])->assertForbidden();
        $this->postJson(self::URL.'/visits', ['scheduled_at' => '2026-11-02 10:00:00'])->assertForbidden();
    }

    public function test_phone_call_reschedule_keeps_cycle_and_never_touches_visits(): void
    {
        $created = $this->postJson(self::URL.'/recoming', array_replace($this->payload(), ['planned_call_at' => '2026-11-01T14:30']))->assertCreated();
        $this->postJson(self::URL.'/recoming/reschedule', ['planned_call_at' => '2026-11-02T04:00:00.000Z'])->assertOk()
            ->assertJsonPath('data.data.cycle_id', $created->json('data.data.cycle_id'));
        $this->assertDatabaseHas('ticket_recomings', ['planned_call_at' => '2026-11-02 11:00:00']);
        $this->assertDatabaseCount('ticket_cycles', 2);
        $this->assertDatabaseCount('ticket_visits', 1);
        $this->postJson(self::URL.'/visits', ['scheduled_at' => '2026-11-03 10:00:00'])->assertConflict()->assertJsonPath('code', 'phone_call_has_no_visit');
        $this->postJson(self::URL.'/on-site')->assertConflict()->assertJsonPath('code', 'phone_call_has_no_visit');
    }

    public function test_reschedule_checks_day_and_progress_on_both_endpoints(): void
    {
        $this->postJson(self::URL.'/recoming', $this->payload('visit_site'))->assertCreated();
        $this->postJson(self::URL.'/visits', ['scheduled_at' => '2026-11-02 10:00:00'])->assertOk();
        $this->assertDatabaseHas('ticket_visits', ['id' => 2, 'scheduled_at' => '2026-11-02 10:00:00']);
        Carbon::setTestNow(Carbon::parse('2026-11-02 00:01:00', 'Asia/Jakarta'));
        $this->postJson(self::URL.'/recoming/reschedule', ['planned_visit_at' => '2026-11-03'])->assertUnprocessable();
        $this->postJson(self::URL.'/visits', ['scheduled_at' => '2026-11-03'])->assertUnprocessable();
        Carbon::setTestNow(Carbon::parse('2026-10-30 10:00:00', 'Asia/Jakarta'));
        Ticket::first()->logs()->create(['step' => 'repair']);
        $this->postJson(self::URL.'/recoming/reschedule', ['planned_visit_at' => '2026-11-03'])->assertUnprocessable();
        $this->postJson(self::URL.'/visits', ['scheduled_at' => '2026-11-03'])->assertUnprocessable();
        $this->assertDatabaseCount('ticket_cycles', 2);
    }

    public function test_scheduling_failure_rolls_back_cycle_visit_log_and_link_changes(): void
    {
        DB::table('satisfaction_links')->insert(['ticket_id' => 1, 'cycle_id' => 1, 'status' => 'active']);
        TicketRecoming::creating(fn () => throw new \RuntimeException('Simulated failure'));
        $this->postJson(self::URL.'/recoming', $this->payload('visit_site'))->assertStatus(500);
        $this->assertDatabaseCount('ticket_cycles', 1);
        $this->assertDatabaseCount('ticket_visits', 1);
        $this->assertDatabaseCount('ticket_logs', 1);
        $this->assertDatabaseHas('ticket_cycles', ['ended_at' => null]);
        $this->assertDatabaseHas('satisfaction_links', ['status' => 'active']);
    }

    public function test_legacy_keeps_visit_only_request_and_existing_other_reason(): void
    {
        DB::table('tickets')->update(['workflow_version' => 'legacy']);
        $this->postJson(self::URL.'/recoming', ['reason' => 'other', 'planned_visit_at' => '2026-11-01'])->assertUnprocessable();
        $this->postJson(self::URL.'/recoming', ['reason' => 'further_work_needed', 'planned_visit_at' => '2026-11-01'])->assertCreated();
        DB::table('ticket_recomings')->update(['reason' => 'other']);
        $this->assertSame('other', TicketRecoming::first()->reason);
        $this->assertSame('visit_site', TicketRecoming::first()->recoming_type);
    }

    public function test_rollback_refuses_to_discard_new_phone_or_reason_data(): void
    {
        $this->postJson(self::URL.'/recoming', $this->payload())->assertCreated();
        $migration = require database_path('migrations/2026_10_30_000200_add_recoming_v3_scheduling.php');
        try {
            $migration->down();
            $this->fail('Rollback must preserve unsupported historical data.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Cannot roll back', $e->getMessage());
        }
        $this->assertTrue(Schema::hasColumn('ticket_recomings', 'planned_call_at'));
        $this->assertDatabaseHas('ticket_recomings', ['recoming_type' => 'phone_call', 'reason' => 'unfinished_previous_work']);
    }

    public function test_migration_roundtrip_preserves_legacy_and_links_only_unambiguous_cycles(): void
    {
        $migration = require database_path('migrations/2026_10_30_000200_add_recoming_v3_scheduling.php');
        $migration->down();
        DB::table('ticket_recomings')->insert(['ticket_id' => 1, 'cycle_id' => 1, 'visit_no' => 1, 'reason' => 'other',
            'planned_visit_at' => '2026-10-29', 'from_step' => 'repair', 'created_by_user_id' => 1, 'deleted_at' => now()]);
        $migration->up();
        $this->assertDatabaseHas('ticket_recomings', ['reason' => 'other', 'recoming_type' => 'visit_site', 'ticket_visit_id' => 1]);
        $migration->down();
        $this->assertDatabaseHas('ticket_recomings', ['reason' => 'other']);
        $this->assertFalse(Schema::hasColumn('ticket_recomings', 'recoming_type'));
    }

    public function test_sla_setup_is_required_and_invalid_setup_cannot_allocate_a_cycle(): void
    {
        foreach ([
            array_diff_key($this->payload(), array_flip(TicketSla::FIELDS)),
            array_replace($this->payload(), ['sla_value' => 0]),
            array_replace($this->payload(), ['sla_value' => 2147483647]),
            $this->payload() + ['sla_due_at' => '2026-11-05'],
            array_replace(array_diff_key($this->payload(), ['sla_value' => true, 'sla_unit' => true]), ['sla_mode' => 'deadline', 'sla_due_at' => '2026-10-31 12:00:00']),
        ] as $payload) {
            $this->postJson(self::URL.'/recoming', $payload)->assertUnprocessable();
        }
        $this->assertDatabaseCount('ticket_cycles', 1);
        $this->assertDatabaseCount('ticket_visits', 1);
        $this->assertDatabaseHas('ticket_cycles', ['cycle_no' => 1, 'ended_at' => null]);
    }

    public function test_duration_sla_uses_schedule_and_reschedule_recalculates_same_cycle(): void
    {
        $response = $this->postJson(self::URL.'/recoming', $this->payload())->assertCreated();
        $cycleId = $response->json('data.data.cycle_id');
        $this->assertDatabaseHas('ticket_cycles', ['id' => $cycleId, 'sla_mode' => 'duration', 'sla_started_at' => '2026-11-01 14:30:00', 'sla_due_at' => '2026-11-03 14:30:00']);
        $this->assertNotNull(Ticket::first()->cycles()->where('cycle_no', 1)->first()->ended_at);
        $summary = TicketSla::summary(Ticket::first());
        $this->assertSame('running', $summary['state']);
        $this->assertSame('Cycle 2', $summary['label']);
        $this->assertSame(0, $summary['elapsed_seconds']);
        $this->postJson(self::URL.'/recoming/reschedule', ['planned_call_at' => '2026-11-02 09:00:00'])->assertOk();
        $this->assertDatabaseHas('ticket_cycles', ['id' => $cycleId, 'sla_started_at' => '2026-11-02 09:00:00', 'sla_due_at' => '2026-11-04 09:00:00']);
        $this->assertDatabaseHas('tickets', ['sla_due_at' => '2026-10-31 10:00:00']);
        $this->postJson(self::URL.'/recoming/reschedule', ['planned_call_at' => '2026-11-03 09:00:00', 'sla_mode' => 'deadline'])->assertUnprocessable();
        $this->assertDatabaseCount('ticket_cycles', 2);
    }

    public function test_deadline_sla_remains_fixed_when_visit_is_moved_beyond_deadline(): void
    {
        $payload = array_replace(array_diff_key($this->payload('visit_site'), ['sla_value' => true, 'sla_unit' => true]), ['sla_mode' => 'deadline', 'sla_due_at' => '2026-11-02 17:00:00']);
        $response = $this->postJson(self::URL.'/recoming', $payload)->assertCreated();
        $cycleId = $response->json('data.data.cycle_id');
        $this->postJson(self::URL.'/visits', ['scheduled_at' => '2026-11-04 09:00:00'])->assertOk();
        $this->assertDatabaseHas('ticket_cycles', ['id' => $cycleId, 'sla_mode' => 'deadline', 'sla_value' => null, 'sla_unit' => null, 'sla_started_at' => '2026-11-04 09:00:00', 'sla_due_at' => '2026-11-02 17:00:00']);
    }

    public function test_admin_survey_pending_review_stops_sla_at_submission_not_close_or_approval(): void
    {
        $this->postJson(self::URL.'/recoming', $this->payload())->assertCreated();
        Carbon::setTestNow(Carbon::parse('2026-11-02 14:30:00', 'Asia/Jakarta'));
        $ticket = Ticket::first();
        $response = TicketSatisfaction::create(['ticket_id' => 1, 'cycle_id' => $ticket->currentCycle->id, 'source' => 'admin', 'review_status' => 'needs_review', 'filled_at' => now()]);
        $this->assertSame('On Time', Ticket::first()->sla_status);
        $this->assertEquals($response->filled_at, Ticket::first()->sla_stopped_at);
        $this->assertSame($response->id, Ticket::first()->currentCycle->sla_satisfaction_id);
        Carbon::setTestNow(Carbon::parse('2026-11-10 14:30:00', 'Asia/Jakarta'));
        $response->update(['review_status' => 'reviewed']);
        $summary = TicketSla::summary(Ticket::first());
        $this->assertSame('stopped', $summary['state']);
        $this->assertSame(86400, $summary['elapsed_seconds']);
        $this->assertSame('On Time', $summary['result']);
        $this->assertArrayNotHasKey('sla_satisfaction_id', Ticket::first()->currentCycle->toArray());
    }

    public function test_rejection_falls_back_to_latest_accepted_response_or_resumes_original_clock(): void
    {
        $this->postJson(self::URL.'/recoming', $this->payload())->assertCreated();
        $cycleId = Ticket::first()->currentCycle->id;
        Carbon::setTestNow(Carbon::parse('2026-11-02 14:30:00', 'Asia/Jakarta'));
        $first = TicketSatisfaction::create(['ticket_id' => 1, 'cycle_id' => $cycleId, 'filled_at' => now(), 'review_status' => 'normal']);
        Carbon::setTestNow(Carbon::parse('2026-11-04 14:30:00', 'Asia/Jakarta'));
        $second = TicketSatisfaction::create(['ticket_id' => 1, 'cycle_id' => $cycleId, 'filled_at' => now(), 'review_status' => 'needs_review']);
        $this->assertSame('Overtime', Ticket::first()->sla_status);
        $second->update(['review_status' => 'rejected']);
        $this->assertEquals($first->filled_at, Ticket::first()->sla_stopped_at);
        $this->assertSame('On Time', Ticket::first()->sla_status);
        $first->update(['review_status' => 'rejected']);
        $this->assertNull(Ticket::first()->sla_stopped_at);
        $summary = TicketSla::summary(Ticket::first());
        $this->assertSame('running', $summary['state']);
        $this->assertTrue($summary['is_overdue']);
        $this->assertSame(259200, $summary['elapsed_seconds']);
        $this->assertDatabaseHas('ticket_cycles', ['id' => $cycleId, 'sla_due_at' => '2026-11-03 14:30:00']);
    }

    public function test_old_cycle_review_cannot_stop_or_restart_the_current_cycle(): void
    {
        $this->postJson(self::URL.'/recoming', $this->payload())->assertCreated();
        Carbon::setTestNow(Carbon::parse('2026-11-02 10:00:00', 'Asia/Jakarta'));
        $oldCycle = Ticket::first()->currentCycle;
        $oldResponse = TicketSatisfaction::create(['ticket_id' => 1, 'cycle_id' => $oldCycle->id, 'filled_at' => now(), 'review_status' => 'needs_review']);
        Ticket::first()->logs()->create(['step' => 'repair']);
        $this->postJson(self::URL.'/recoming', array_replace($this->payload(), ['planned_call_at' => '2026-11-03 10:00:00']))->assertCreated();
        $this->assertNull(Ticket::first()->sla_stopped_at);
        $closedAt = $oldCycle->fresh()->ended_at;
        $oldResponse->update(['review_status' => 'rejected']);
        $this->assertNull(Ticket::first()->sla_stopped_at);
        $this->assertEquals($closedAt, $oldCycle->fresh()->ended_at);
        Carbon::setTestNow(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $newResponse = TicketSatisfaction::create(['ticket_id' => 1, 'filled_at' => now(), 'review_status' => 'normal']);
        $oldResponse->update(['review_status' => 'reviewed']);
        $this->assertEquals($newResponse->filled_at, Ticket::first()->sla_stopped_at);
        $this->assertEquals($oldResponse->filled_at, $oldCycle->fresh()->sla_stopped_at);
    }

    public function test_failed_response_transaction_does_not_leave_sla_stopped_and_soft_delete_recalculates(): void
    {
        $this->postJson(self::URL.'/recoming', $this->payload())->assertCreated();
        try {
            DB::transaction(function () {
                TicketSatisfaction::create(['ticket_id' => 1, 'filled_at' => now(), 'review_status' => 'normal']);
                throw new \RuntimeException('Submission failed');
            });
        } catch (\RuntimeException $e) {
            $this->assertSame('Submission failed', $e->getMessage());
        }
        $this->assertDatabaseCount('ticket_satisfactions', 0);
        $this->assertNull(Ticket::first()->sla_stopped_at);
        $response = TicketSatisfaction::create(['ticket_id' => 1, 'filled_at' => now(), 'review_status' => 'normal']);
        $response->delete();
        $this->assertNull(Ticket::first()->sla_stopped_at);
        $response->restore();
        $this->assertEquals($response->filled_at, Ticket::first()->sla_stopped_at);
    }

    public function test_legacy_verdict_remains_closed_based(): void
    {
        DB::table('tickets')->update(['workflow_version' => 'legacy']);
        TicketSatisfaction::create(['ticket_id' => 1, 'filled_at' => now(), 'review_status' => 'normal']);
        $this->assertNull(Ticket::first()->sla_stopped_at);
        $this->assertSame('Pending', Ticket::first()->sla_status);
        $this->assertNull(TicketSla::summary(Ticket::first()));
        Ticket::first()->logs()->create(['step' => 'closed']);
        $this->assertSame('On Time', Ticket::first()->sla_status);
    }
}
