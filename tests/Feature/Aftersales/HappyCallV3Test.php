<?php

namespace Tests\Feature\Aftersales;

use App\Http\Controllers\AfterSales\Dats\TicketController;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketSatisfaction;
use App\Models\User;
use App\Services\Aftersales\TicketCycles;
use App\Services\Aftersales\TicketHappyCalls;
use App\Services\Aftersales\TicketReminders;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class HappyCallV3Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locked' => false]);
        Carbon::setTestNow(Carbon::parse('2026-10-30 10:00:00', 'Asia/Jakarta'));
        Schema::create('user_roles', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->softDeletes();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('role_id');
            $t->string('name');
            $t->softDeletes();
        });
        Schema::create('service_customers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->softDeletes();
        });
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->string('ticket_code');
            $t->string('workflow_version')->default('v3');
            foreach (['customer_id', 'assigned_technician_id', 'created_by'] as $c) {
                $t->unsignedBigInteger($c)->nullable();
            }
            $t->unsignedInteger('sla_value')->nullable();
            $t->string('sla_unit')->nullable();
            $t->timestamp('sla_due_at')->nullable();
            $t->boolean('happy_call_required')->default(true);
            $t->timestamp('happy_call_due_at')->nullable();
            $t->timestamp('sla_stopped_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_cycles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->unsignedInteger('cycle_no');
            $t->string('documentation_status')->default('incomplete');
            $t->string('sla_mode')->nullable();
            $t->unsignedInteger('sla_value')->nullable();
            $t->string('sla_unit')->nullable();
            $t->unsignedBigInteger('sla_satisfaction_id')->nullable();
            foreach (['started_at', 'ended_at', 'sla_started_at', 'sla_due_at', 'sla_stopped_at', 'happy_call_due_at'] as $c) {
                $t->timestamp($c)->nullable();
            }
            $t->timestamps();
            $t->unique(['ticket_id', 'id']);
            $t->unique(['ticket_id', 'cycle_no']);
        });
        Schema::create('ticket_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->unsignedBigInteger('cycle_id')->nullable();
            $t->string('step');
            $t->unsignedBigInteger('actor_user_id')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_satisfactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->unsignedBigInteger('cycle_id')->nullable();
            $t->unsignedInteger('overall_satisfaction')->nullable();
            $t->string('source')->default('link');
            $t->string('review_status')->default('normal');
            $t->text('review_note')->nullable();
            $t->timestamp('filled_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_satisfaction_answers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_satisfaction_id');
            $t->string('type');
            $t->integer('value_number')->nullable();
        });
        Schema::create('ticket_visits', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->timestamp('scheduled_at')->nullable();
            $t->softDeletes();
        });
        Schema::create('ticket_recomings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->unsignedBigInteger('cycle_id')->nullable();
            $t->string('recoming_type');
            foreach (['visit_no', 'ticket_visit_id', 'created_by_user_id'] as $c) {
                $t->unsignedBigInteger($c)->nullable();
            }
            foreach (['reason', 'notes', 'from_step'] as $c) {
                $t->text($c)->nullable();
            }
            $t->timestamp('planned_visit_at')->nullable();
            $t->timestamp('planned_call_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_service_forms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->string('status')->default('draft');
            $t->timestamp('saved_at')->nullable();
            $t->softDeletes();
        });
        Schema::create('satisfaction_links', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->string('status')->default('inactive');
            $t->timestamp('shared_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        (require database_path('migrations/2026_05_20_113809_create_notifications_table.php'))->up();
        $this->migration()->up();
        foreach ([1 => 'after_sales', 2 => 'head_after_sales', 3 => 'supervisor_after_sales', 4 => 'technician'] as $id => $code) {
            DB::table('user_roles')->insert(['id' => $id, 'code' => $code]);
        }
        foreach ([1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 1, 6 => 2, 7 => 2] as $id => $role) {
            DB::table('users')->insert(['id' => $id, 'role_id' => $role, 'name' => 'User '.$id, 'deleted_at' => $id === 7 ? now() : null]);
        }
        DB::table('service_customers')->insert(['id' => 1, 'name' => 'Customer A']);
        $this->login();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function migration()
    {
        return require database_path('migrations/2026_10_30_000600_add_happy_call_cycle_tracking.php');
    }

    private function login(int $id = 1, bool $forgedClose = false): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill(User::findOrFail($id)->getAttributes());
        $user->shouldReceive('hasPermission')->andReturnUsing(fn ($p) => ($forgedClose && $p === 'aftersales.tickets.close') || in_array($p, match ($id) {
            1, 5 => ['aftersales.tickets.manage'], 2, 6 => ['aftersales.tickets.view-all', 'aftersales.tickets.close'],
            3 => ['aftersales.tickets.view-all'], default => ['aftersales.tickets.view-own'],
        }, true));
        $this->actingAs($user);
    }

    private function ticket(bool $published = true, string $workflow = 'v3'): Ticket
    {
        $ticket = Ticket::create(['ticket_code' => 'T-'.(Ticket::withTrashed()->count() + 1), 'workflow_version' => $workflow,
            'customer_id' => 1, 'assigned_technician_id' => 4, 'created_by' => 1, 'sla_value' => 7, 'sla_unit' => 'day', 'sla_due_at' => now()->addDays(7)]);
        DB::table('ticket_service_forms')->insert(['ticket_id' => $ticket->id]);
        if ($published) {
            $this->publish($ticket);
        }

        return $ticket->fresh();
    }

    private function publish(Ticket $ticket): void
    {
        $ticket->currentCycle->update(['documentation_status' => 'documentation_published']);
        $ticket->logs()->create(['cycle_id' => $ticket->currentCycle->id, 'step' => 'documentation_published']);
    }

    private function survey(Ticket $ticket, string $status = 'normal', string $source = 'link'): TicketSatisfaction
    {
        return TicketSatisfaction::create(['ticket_id' => $ticket->id, 'source' => $source, 'review_status' => $status,
            'overall_satisfaction' => 2, 'filled_at' => now(), 'review_note' => 'SECRET-REVIEW']);
    }

    private function url(Ticket $ticket, string $action): string
    {
        return '/api/aftersales/tickets/'.$ticket->id.'/'.$action;
    }

    public function test_happy_call_requires_current_published_documentation_and_valid_survey(): void
    {
        $ticket = $this->ticket();
        $this->postJson($this->url($ticket, 'happy-call'))->assertUnprocessable()->assertJsonPath('code', 'survey_not_valid');
        $response = $this->survey($ticket, 'needs_review', 'admin');
        $this->assertSame('2026-11-02 10:00:00', $ticket->fresh()->happy_call_due_at->format('Y-m-d H:i:s'));
        $this->postJson($this->url($ticket, 'happy-call'))->assertUnprocessable()->assertJsonPath('code', 'survey_not_valid');
        $response->update(['review_status' => 'reviewed']);
        $this->postJson($this->url($ticket, 'happy-call'), ['note' => 'Customer contacted'])->assertCreated()->assertJsonPath('data.satisfaction_id', $response->id);
        $other = $this->ticket(false);
        $this->survey($other);
        $this->postJson($this->url($other, 'happy-call'))->assertUnprocessable()->assertJsonPath('code', 'documentation_not_published');
    }

    public function test_happy_call_is_idempotent_and_close_is_mandatory_and_head_only(): void
    {
        $ticket = $this->ticket();
        $response = $this->survey($ticket);
        $ticket->update(['happy_call_required' => false]);
        $this->login(2);
        $this->postJson($this->url($ticket, 'close'))->assertUnprocessable()->assertJsonPath('code', 'happy_call_required');
        foreach ([2, 3, 4] as $id) {
            $this->login($id);
            $this->postJson($this->url($ticket, 'happy-call'))->assertForbidden();
        }
        $this->login(1, true);
        $first = $this->postJson($this->url($ticket, 'happy-call'))->assertCreated()->json('data.id');
        $this->postJson($this->url($ticket, 'happy-call'))->assertCreated()->assertJsonPath('data.id', $first);
        $this->postJson($this->url($ticket, 'close'))->assertForbidden();
        $this->assertDatabaseHas('ticket_logs', ['id' => $first, 'cycle_id' => $ticket->currentCycle->id, 'satisfaction_id' => $response->id, 'actor_user_id' => 1]);
        $this->login(2);
        $this->postJson($this->url($ticket, 'close'))->assertCreated();
        $this->postJson($this->url($ticket, 'close'))->assertUnprocessable();
        $this->assertSame(1, $ticket->logs()->where('step', 'closed')->count());
        $this->assertSame(1, $ticket->logs()->where('step', 'happy_call_completed')->count());
    }

    public function test_replacement_response_even_at_same_timestamp_requires_another_happy_call(): void
    {
        $ticket = $this->ticket();
        $first = $this->survey($ticket);
        $this->postJson($this->url($ticket, 'happy-call'))->assertCreated();
        $second = $this->survey($ticket, 'needs_review', 'admin');
        $this->assertEquals($first->filled_at, $second->filled_at);
        $this->assertFalse(TicketHappyCalls::state($ticket->fresh())['completed']);
        $this->login(2);
        $this->postJson($this->url($ticket, 'close'))->assertUnprocessable()->assertJsonPath('code', 'survey_not_valid');
        $second->update(['review_status' => 'reviewed']);
        $this->postJson($this->url($ticket, 'close'))->assertUnprocessable()->assertJsonPath('code', 'happy_call_required');
        $this->login(1);
        $this->postJson($this->url($ticket, 'happy-call'))->assertCreated()->assertJsonPath('data.satisfaction_id', $second->id);
        $this->assertSame(2, $ticket->logs()->where('step', 'happy_call_completed')->count());
        $this->login(2);
        $this->postJson($this->url($ticket, 'close'))->assertCreated();
    }

    public function test_recoming_clears_active_deadline_and_old_survey_and_call_do_not_satisfy_close(): void
    {
        $ticket = $this->ticket();
        $this->survey($ticket);
        $this->postJson($this->url($ticket, 'happy-call'))->assertCreated();
        $oldCycle = $ticket->fresh()->currentCycle;
        $this->postJson($this->url($ticket, 'recoming'), ['recoming_type' => 'phone_call', 'reason' => 'unfinished_previous_work',
            'planned_call_at' => '2026-11-01 10:00:00', 'sla_mode' => 'duration', 'sla_value' => 2, 'sla_unit' => 'day'])->assertCreated();
        $this->assertNull($ticket->fresh()->happy_call_due_at);
        $this->assertNotNull($oldCycle->fresh()->happy_call_due_at);
        $this->postJson($this->url($ticket, 'happy-call'))->assertUnprocessable()->assertJsonPath('code', 'ticket_in_recoming');
        $this->publish($ticket->fresh());
        $this->login(2);
        $this->postJson($this->url($ticket, 'close'))->assertUnprocessable()->assertJsonPath('code', 'survey_not_valid');
        $this->survey($ticket->fresh(), 'reviewed', 'admin');
        $this->postJson($this->url($ticket, 'close'))->assertUnprocessable()->assertJsonPath('code', 'happy_call_required');
    }

    public function test_context_mismatch_is_rejected_before_recording_or_closing(): void
    {
        $ticket = $this->ticket();
        $response = $this->survey($ticket);
        $this->postJson($this->url($ticket, 'happy-call'), ['cycle_id' => 999])->assertConflict()->assertJsonPath('code', 'ticket_cycle_changed');
        $this->postJson($this->url($ticket, 'happy-call'), ['satisfaction_id' => 999])->assertConflict()->assertJsonPath('code', 'survey_response_changed');
        $this->assertSame(0, $ticket->logs()->where('step', 'happy_call_completed')->count());
        $this->postJson($this->url($ticket, 'happy-call'), ['cycle_id' => $ticket->currentCycle->id, 'satisfaction_id' => $response->id])->assertCreated();
        $this->login(2);
        $this->postJson($this->url($ticket, 'close'), ['satisfaction_id' => 999])->assertConflict();
        $this->assertSame(0, $ticket->logs()->where('step', 'closed')->count());
    }

    public function test_pending_deadline_is_submit_plus_three_days_and_overdue_notifications_are_deduplicated(): void
    {
        $ticket = $this->ticket();
        $response = $this->survey($ticket, 'needs_review', 'admin');
        Carbon::setTestNow(Carbon::parse('2026-11-02 10:00:00', 'Asia/Jakarta'));
        $this->artisan('tickets:notify-happy-call-overdue')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 0);
        Carbon::setTestNow(now()->addSecond());
        $this->artisan('tickets:notify-happy-call-overdue')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 3);
        $this->assertSame([1, 2, 6], DB::table('notifications')->orderBy('notifiable_id')->pluck('notifiable_id')->all());
        $payload = json_decode(DB::table('notifications')->value('data'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('happy_call_overdue', $payload['type']);
        $this->assertSame($ticket->currentCycle->id, $payload['cycle_id']);
        $this->assertStringNotContainsString('SECRET-', json_encode($payload));
        $this->artisan('tickets:notify-happy-call-overdue')->assertSuccessful();
        $this->assertDatabaseCount('ticket_happy_call_escalations', 3);
        $this->assertDatabaseCount('notifications', 3);
        $this->postJson($this->url($ticket, 'happy-call'))->assertUnprocessable();
        $response->update(['review_status' => 'reviewed']);
        $this->assertSame('2026-11-02 10:00:00', $ticket->fresh()->happy_call_due_at->format('Y-m-d H:i:s'));
        $this->postJson($this->url($ticket, 'happy-call'))->assertCreated();
        $this->artisan('tickets:notify-happy-call-overdue')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 3);
    }

    public function test_notification_and_receipt_writes_are_atomic_and_retryable_after_failure(): void
    {
        $ticket = $this->ticket();
        $this->survey($ticket);
        Carbon::setTestNow(now()->addDays(4));
        $writes = 0;
        DatabaseNotification::creating(function () use (&$writes) {
            if (++$writes === 2) {
                throw new \RuntimeException('Notification write failed');
            }
        });
        try {
            TicketHappyCalls::notifyOverdue($ticket->id);
            $this->fail('Expected storage failure.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Notification write failed', $e->getMessage());
        }
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('ticket_happy_call_escalations', 0);
        DatabaseNotification::flushEventListeners();
        $this->assertSame(3, TicketHappyCalls::notifyOverdue($ticket->id));
        $this->assertDatabaseCount('notifications', 3);
        $this->assertDatabaseCount('ticket_happy_call_escalations', 3);
    }

    public function test_escalation_receipts_survive_notification_deletion_and_new_cycle_can_escalate(): void
    {
        $ticket = $this->ticket();
        $this->survey($ticket);
        Carbon::setTestNow(now()->addDays(4));
        $this->assertSame(3, TicketHappyCalls::notifyOverdue($ticket->id));
        DB::table('notifications')->delete();
        $this->assertSame(0, TicketHappyCalls::notifyOverdue($ticket->id));
        $cycle = TicketCycles::startRecoming($ticket->fresh());
        $this->publish($ticket->fresh());
        $this->survey($ticket->fresh());
        Carbon::setTestNow(now()->addDays(4));
        $this->assertSame(3, TicketHappyCalls::notifyOverdue($ticket->id));
        $this->assertDatabaseCount('ticket_happy_call_escalations', 6);
        $this->assertSame(3, DB::table('ticket_happy_call_escalations')->where('cycle_id', $cycle->id)->count());
    }

    public function test_scheduler_ignores_closed_completed_deleted_legacy_rejected_and_old_cycles(): void
    {
        $done = $this->ticket();
        $this->survey($done);
        $this->postJson($this->url($done, 'happy-call'))->assertCreated();
        $closed = $this->ticket();
        $this->survey($closed);
        $this->postJson($this->url($closed, 'happy-call'))->assertCreated();
        $this->login(2);
        $this->postJson($this->url($closed, 'close'))->assertCreated();
        $this->login();
        $deleted = $this->ticket();
        $this->survey($deleted);
        $deleted->delete();
        $legacy = $this->ticket(true, 'legacy');
        $this->survey($legacy);
        $legacy->currentCycle->update(['happy_call_due_at' => now()]);
        $rejected = $this->ticket();
        $this->survey($rejected, 'rejected');
        $old = $this->ticket();
        $this->survey($old);
        TicketCycles::startRecoming($old);
        $waiting = $this->ticket();
        Carbon::setTestNow(now()->addDays(4));
        $this->artisan('tickets:notify-happy-call-overdue')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 0);
        $this->assertSame(0, $waiting->logs()->where('step', 'closed')->count());
    }

    public function test_reject_delete_restore_recalculates_deadline_without_touching_latest_cycle(): void
    {
        $ticket = $this->ticket();
        $response = $this->survey($ticket);
        $due = $ticket->fresh()->happy_call_due_at;
        $response->update(['review_status' => 'rejected']);
        $this->assertNull($ticket->fresh()->happy_call_due_at);
        $response->update(['review_status' => 'reviewed']);
        $this->assertEquals($due, $ticket->fresh()->happy_call_due_at);
        $response->delete();
        $this->assertNull($ticket->fresh()->happy_call_due_at);
        $response->restore();
        $this->assertEquals($due, $ticket->fresh()->happy_call_due_at);
        TicketCycles::startRecoming($ticket);
        $response->update(['review_status' => 'normal']);
        $this->assertNull($ticket->fresh()->happy_call_due_at);
    }

    public function test_ready_queue_paginates_only_latest_published_open_v3_cycles_in_database(): void
    {
        $complete = $this->ticket();
        $this->survey($complete);
        $this->postJson($this->url($complete, 'happy-call'))->assertCreated();
        $pending = $this->ticket();
        $this->survey($pending, 'needs_review', 'admin');
        $waiting = $this->ticket();
        for ($i = 0; $i < 5; $i++) {
            $this->ticket(false);
        }
        $old = $this->ticket();
        TicketCycles::startRecoming($old);
        $this->ticket(true, 'legacy');
        $deleted = $this->ticket();
        $deleted->delete();
        $closed = $this->ticket();
        $this->survey($closed);
        $this->postJson($this->url($closed, 'happy-call'))->assertCreated();
        $this->login(2);
        $this->postJson($this->url($closed, 'close'))->assertCreated();
        $url = '/api/aftersales/tickets/ready-to-close';
        DB::enableQueryLog();
        DB::flushQueryLog();
        $result = $this->getJson($url.'?per_page=2&page=2')->assertOk()->assertJsonPath('total', 3)->assertJsonPath('last_page', 2)->assertJsonCount(1, 'data');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertTrue(collect($queries)->contains(fn ($q) => str_contains(strtolower($q['query']), 'limit 2 offset 2')));
        $this->assertSame($complete->id, $result->json('data.0.id'));
        $this->assertTrue($result->json('data.0.can_close'));
        $this->assertStringNotContainsString('SECRET-', $result->getContent());
        $this->getJson($url.'?search='.$complete->ticket_code)->assertOk()->assertJsonPath('total', 1);
        $this->getJson($url.'?per_page=101')->assertUnprocessable();
        $this->login();
        $this->getJson('/api/aftersales/tickets?ready_to_close=1&per_page=2&page=2')->assertOk()->assertJsonPath('total', 3)->assertJsonPath('data.0.can_close', false);
        $this->login(3);
        $this->getJson($url)->assertOk();
        $this->login(4);
        $this->getJson($url)->assertForbidden();
    }

    public function test_v3_replaces_survey_due_and_h_plus_one_reminders_while_legacy_is_preserved(): void
    {
        $ticket = $this->ticket();
        $this->survey($ticket);
        $legacy = $this->ticket(true, 'legacy');
        $this->survey($legacy);
        Carbon::setTestNow(now()->addDays(2));
        $state = TicketReminders::for($ticket->fresh());
        $this->assertFalse($state['survey_due']);
        $this->assertNull($state['survey_due_at']);
        $this->assertFalse($state['happy_call_due']);
        $this->assertTrue(TicketReminders::for($legacy->fresh())['happy_call_due']);
        Carbon::setTestNow(now()->addDays(2));
        $this->assertTrue(TicketReminders::for($ticket->fresh())['happy_call_due']);
    }

    public function test_capabilities_match_current_survey_happy_call_and_head_role(): void
    {
        $ticket = $this->ticket();
        $this->survey($ticket);
        $ticket->setRelation('serviceForm', null)->setRelation('agreement', null);
        $method = new \ReflectionMethod(TicketController::class, 'capabilities');
        $head = $method->invoke(app(TicketController::class), $this->headUser(), $ticket);
        $this->assertFalse($head['can_close']);
        $this->postJson($this->url($ticket, 'happy-call'))->assertCreated();
        $ticket = $ticket->fresh()->setRelation('serviceForm', null)->setRelation('agreement', null);
        $head = $method->invoke(app(TicketController::class), $this->headUser(), $ticket);
        $this->assertTrue($head['can_close']);
    }

    private function headUser(): User
    {
        $this->login(2);
        $user = auth()->user();
        $this->login();

        return $user;
    }

    public function test_migration_roundtrip_preserves_data_and_backfills_only_unambiguous_v3_happy_call(): void
    {
        $this->migration()->down();
        $ticket = $this->ticket();
        $response = $this->survey($ticket);
        $legacy = $this->ticket(true, 'legacy');
        $this->survey($legacy);
        foreach ([$ticket, $ticket, $legacy] as $owner) {
            DB::table('ticket_logs')->insert(['ticket_id' => $owner->id, 'cycle_id' => $owner->currentCycle->id,
                'step' => 'happy_call_completed', 'created_at' => now()->addMinute(), 'updated_at' => now()->addMinute()]);
        }
        $this->migration()->up();
        $this->assertSame(1, DB::table('ticket_logs')->where('satisfaction_id', $response->id)->count());
        $this->assertDatabaseHas('ticket_logs', ['ticket_id' => $legacy->id, 'step' => 'happy_call_completed', 'satisfaction_id' => null]);
        $this->assertNull($legacy->fresh()->happy_call_due_at);
        $this->assertSame('2026-11-02 10:00:00', $ticket->fresh()->happy_call_due_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseCount('ticket_satisfactions', 2);
        try {
            $this->migration()->down();
            $this->fail('Rollback must preserve links.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('history exist', $e->getMessage());
        }
        $this->assertTrue(Schema::hasColumn('ticket_logs', 'satisfaction_id'));
        $this->expectException(QueryException::class);
        DB::table('ticket_logs')->insert(['ticket_id' => $ticket->id, 'cycle_id' => $ticket->currentCycle->id, 'satisfaction_id' => $response->id, 'step' => 'happy_call_completed']);
    }
}
