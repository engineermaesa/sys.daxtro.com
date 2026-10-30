<?php

namespace Tests\Feature\Aftersales;

use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketRecoming;
use App\Models\User;
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
            Schema::create($name, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('ticket_id');
                $t->string('status')->default('active');
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
        $ticket = Ticket::create(['ticket_code' => 'REC-1', 'assigned_technician_id' => 2, 'supervisor_id' => 3]);
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
        return ['recoming_type' => $type, 'reason' => 'unfinished_previous_work',
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
}
