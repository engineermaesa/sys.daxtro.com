<?php

namespace Tests\Feature\Aftersales;

use App\Models\User;
use App\Models\UserRole;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    private const URL = '/api/dashboard/aftersales/admin';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locked' => false]);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-05 12:00:00', 'Asia/Jakarta'));
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->string('ticket_code');
            $t->integer('assigned_technician_id')->nullable();
            $t->integer('supervisor_id')->nullable();
            $t->integer('customer_id')->default(1);
            $t->integer('customer_product_id')->default(1);
            $t->string('priority')->default('medium');
            $t->string('workflow_version')->nullable();
            $t->boolean('happy_call_required')->default(true);
            $t->timestamp('sla_due_at')->nullable();
            $this->deleted($t);
        });
        Schema::create('service_customers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $this->deleted($t);
        });
        Schema::create('service_customer_products', function (Blueprint $t) {
            $t->id();
            $t->integer('customer_id')->default(1);
            $t->string('machine_name')->nullable();
            $t->integer('product_id')->nullable();
            $t->boolean('is_daxtro')->nullable()->default(true);
            $t->date('warranty_end')->nullable();
            $this->deleted($t);
        });
        Schema::create('ref_products', function (Blueprint $t) {
            $t->id();
            $t->string('name');
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->integer('role_id');
            $t->softDeletes();
        });
        Schema::create('user_roles', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->softDeletes();
        });
        Schema::create('ticket_logs', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->integer('cycle_id')->nullable();
            $t->integer('satisfaction_id')->nullable();
            $t->string('step');
            $t->timestamp('created_at');
            $this->deleted($t);
        });
        Schema::create('ticket_cycles', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->integer('cycle_no');
            $t->timestamp('started_at')->nullable()->default('2026-11-01 00:00:00');
            $t->timestamp('sla_started_at')->nullable();
            $t->timestamp('sla_due_at')->nullable();
            $t->timestamp('sla_stopped_at')->nullable();
            $t->string('documentation_status')->default('incomplete');
        });
        Schema::create('ticket_visits', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->integer('cycle_id')->nullable();
            $t->timestamp('scheduled_at');
            $this->deleted($t);
        });
        Schema::create('ticket_recomings', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->integer('cycle_id')->nullable();
            $t->string('recoming_type')->default('visit_site');
            $t->timestamp('planned_call_at')->nullable();
            $t->timestamp('planned_visit_at')->nullable();
            $this->deleted($t);
        });
        Schema::create('ticket_satisfactions', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->integer('cycle_id')->nullable();
            $t->string('source')->default('link');
            $t->string('review_status')->default('normal');
            $t->boolean('machine_ok')->nullable();
            $t->timestamp('filled_at')->nullable();
            $this->deleted($t);
        });
        Schema::create('satisfaction_links', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->integer('cycle_id')->nullable();
            $t->string('status')->default('active');
            $t->timestamp('expires_at');
            $t->timestamp('shared_at')->nullable();
            $this->deleted($t);
        });
        foreach (['ticket_service_forms', 'ticket_agreements'] as $name) {
            Schema::create($name, function (Blueprint $t) {
                $t->id();
                $t->integer('ticket_id');
                $t->string('status')->default('draft');
                $t->timestamp('unlocked_at')->nullable();
                $t->softDeletes();
            });
        }
        Schema::create('ref_spareparts', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('part_number')->nullable();
            $t->integer('stock')->default(0);
            $t->integer('min_stock')->default(0);
            $this->deleted($t);
        });
        Schema::create('ticket_parts', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->integer('ref_sparepart_id');
            $this->deleted($t);
        });
        DB::table('user_roles')->insert([['id' => 1, 'code' => 'technician'], ['id' => 2, 'code' => 'sales']]);
        DB::table('users')->insert([['id' => 10, 'role_id' => 1, 'name' => 'Tech A'], ['id' => 11, 'role_id' => 1, 'name' => 'Tech B'], ['id' => 12, 'role_id' => 1, 'name' => 'Tech C'], ['id' => 13, 'role_id' => 2, 'name' => 'Sales']]);
        DB::table('service_customers')->insert(['id' => 1, 'name' => 'Customer', 'phone' => '08000', 'email' => 'example@example.test']);
        DB::table('service_customer_products')->insert(['id' => 1, 'machine_name' => 'TD150', 'warranty_end' => '2027-01-01']);
        $this->login();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function deleted(Blueprint $t): void
    {
        $t->softDeletes();
        $t->boolean('is_deleted')->default(false);
    }

    private function login(string $role = 'after_sales', bool $allowed = true): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => 2]);
        $user->setRelation('role', new UserRole(['code' => $role]));
        $user->shouldReceive('hasPermission')->andReturnUsing(fn ($key) => $allowed && ($key === 'dashboard' || ($role === 'supervisor_after_sales' ? in_array($key, ['aftersales.tickets.view-all', 'aftersales.documents.sign'], true) : $key === 'aftersales.tickets.manage')));
        $this->actingAs($user);
    }

    private function ticket(?string $progress = 'assigned', array $attrs = []): int
    {
        $id = DB::table('tickets')->insertGetId($attrs + ['ticket_code' => 'T-'.uniqid(), 'assigned_technician_id' => 10, 'supervisor_id' => 2]);
        if ($progress) {
            $this->log($id, $progress);
        }

        return $id;
    }

    private function log(int $id, string $step, array $attrs = []): void
    {
        DB::table('ticket_logs')->insert($attrs + ['ticket_id' => $id, 'step' => $step, 'created_at' => '2026-11-04 10:00:00']);
    }

    private function data(): array
    {
        return $this->getJson(self::URL)->assertOk()->json('data');
    }

    private function counts(array $items): array
    {
        return array_column($items, 'count', 'key');
    }

    public function test_empty_summary_keeps_all_cards_and_four_days_and_standby_roster(): void
    {
        $d = $this->data();
        $this->assertCount(8, $d['progress']['items']);
        $this->assertCount(6, $d['follow_up']);
        $this->assertCount(4, $d['hygiene']);
        $this->assertCount(4, $d['visits']['days']);
        $this->assertSame(0, $d['progress']['total_open']);
        $this->assertSame([0, 0, 0, 0], array_column($d['visits']['days'], 'total'));
        $this->assertSame(3, $d['technicians']['total']);
        $this->assertSame(['standby', 'standby', 'standby'], array_column($d['technicians']['items'], 'status'));
    }

    public function test_access_validation_and_authentication_are_enforced(): void
    {
        foreach (['technician', 'sales', 'branch_manager'] as $role) {
            $this->login($role);
            foreach (['', '/tickets?queue=all', '/hygiene?queue=missing_contact', '/technicians'] as $suffix) {
                $this->getJson(self::URL.$suffix)->assertForbidden();
            }
        }
        $this->login('after_sales', false);
        $this->getJson(self::URL)->assertForbidden();
        $this->login();
        foreach (['?per_page=101', '?page=0', '?period=this_month'] as $suffix) {
            $this->getJson(self::URL.$suffix)->assertUnprocessable();
        }
        foreach (['queue=invalid', 'queue=progress', 'queue=sla&sla=invalid', 'queue=visits&date=bad', 'queue=all&technician_id=-1'] as $query) {
            $this->getJson(self::URL.'/tickets?'.$query)->assertUnprocessable();
        }
        $this->getJson(self::URL.'/hygiene?queue=invalid')->assertUnprocessable();
        auth()->forgetGuards();
        $this->getJson(self::URL)->assertUnauthorized();
    }

    public function test_progress_is_derived_and_closed_deleted_and_reopened_tickets_are_handled(): void
    {
        $this->ticket(null, ['assigned_technician_id' => null]);
        $this->ticket();
        $site = $this->ticket();
        DB::table('ticket_visits')->insert(['ticket_id' => $site, 'scheduled_at' => '2026-11-05 20:00:00']);
        $this->ticket('repair');
        $this->ticket('closed');
        $this->ticket('repair', ['is_deleted' => true]);
        $this->ticket('repair', ['deleted_at' => '2026-11-04']);
        $rec = $this->ticket('closed');
        $this->log($rec, 'recoming');
        $d = $this->data();
        $counts = $this->counts($d['progress']['items']);
        $this->assertSame([1, 1, 1, 1], [$counts['published'], $counts['assigned'], $counts['on_site'], $counts['repair']]);
        $this->assertSame(5, $d['progress']['total_open']);
        $this->assertSame(1, $d['progress']['recoming']);
        $this->getJson(self::URL.'/tickets?queue=progress&progress=repair')->assertJsonPath('total', 1);
        $this->getJson(self::URL.'/tickets?queue=all&technician_id=10')->assertJsonPath('total', 4);
    }

    public function test_sla_uses_exact_24_hour_boundary_and_freezes_stopped_v3_clocks(): void
    {
        foreach (['2026-11-06 12:00:00', '2026-11-06 11:59:59', '2026-11-05 11:59:59', null] as $due) {
            $this->ticket('repair', ['sla_due_at' => $due]);
        }
        foreach (['2026-11-03 11:00:00', '2026-11-03 13:00:00'] as $stop) {
            $ticket = $this->ticket('repair', ['workflow_version' => 'v3']);
            DB::table('ticket_cycles')->insert(['ticket_id' => $ticket, 'cycle_no' => 1, 'sla_due_at' => '2026-11-03 12:00:00', 'sla_stopped_at' => $stop]);
        }
        $this->ticket('closed', ['sla_due_at' => '2026-11-01']);
        $sla = $this->data()['sla'];
        $this->assertSame([2, 1, 2, 1, 6, 5], array_values($sla));
        foreach (['on_track' => 2, 'at_risk' => 1, 'overdue' => 2, 'not_configured' => 1] as $key => $total) {
            $this->getJson(self::URL.'/tickets?queue=sla&sla='.$key)->assertJsonPath('total', $total);
        }
    }

    public function test_schedule_does_not_double_count_recoming_or_use_rescheduled_or_previous_cycle_visits(): void
    {
        $visit = $this->ticket('repair');
        DB::table('ticket_visits')->insert([['ticket_id' => $visit, 'scheduled_at' => '2026-11-05 09:00:00'], ['ticket_id' => $visit, 'scheduled_at' => '2026-11-06 09:00:00']]);
        $rec = $this->ticket('recoming', ['workflow_version' => 'v3']);
        $cycle = DB::table('ticket_cycles')->insertGetId(['ticket_id' => $rec, 'cycle_no' => 2]);
        DB::table('ticket_visits')->insert(['ticket_id' => $rec, 'cycle_id' => $cycle, 'scheduled_at' => '2026-11-05 10:00:00']);
        DB::table('ticket_recomings')->insert(['ticket_id' => $rec, 'cycle_id' => $cycle, 'planned_visit_at' => '2026-11-05 10:00:00']);
        $phone = $this->ticket('recoming', ['workflow_version' => 'v3']);
        $cycle = DB::table('ticket_cycles')->insertGetId(['ticket_id' => $phone, 'cycle_no' => 2]);
        DB::table('ticket_recomings')->insert(['ticket_id' => $phone, 'cycle_id' => $cycle, 'recoming_type' => 'phone_call', 'planned_call_at' => '2026-11-05 13:00:00']);
        DB::table('ticket_visits')->insert(['ticket_id' => $phone, 'scheduled_at' => '2026-11-05 08:00:00']);
        $old = $this->ticket('repair', ['workflow_version' => 'v3']);
        DB::table('ticket_cycles')->insert(['ticket_id' => $old, 'cycle_no' => 2]);
        DB::table('ticket_visits')->insert(['ticket_id' => $old, 'cycle_id' => null, 'scheduled_at' => '2026-11-05 08:00:00']);
        $days = $this->data()['visits']['days'];
        $this->assertSame([1, 1, 0, 0], array_column($days, 'total'));
        $this->assertSame(1, $days[0]['recomings']);
        $this->assertSame(1, $days[0]['phone_calls']);
        $this->getJson(self::URL.'/tickets?queue=visits&date=2026-11-05')->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $rec);
        $this->getJson(self::URL.'/tickets?queue=visits&date=2026-11-05&type=phone_call')->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $phone);
    }

    private function published(array $attrs = [], string $at = '2026-11-01 10:00:00'): array
    {
        $id = $this->ticket(null, $attrs + ['workflow_version' => 'v3']);
        $cycle = DB::table('ticket_cycles')->insertGetId(['ticket_id' => $id, 'cycle_no' => 1, 'documentation_status' => 'documentation_published']);
        $this->log($id, 'documentation_published', ['cycle_id' => $cycle, 'created_at' => $at]);

        return [$id, $cycle];
    }

    private function response(int $ticket, int $cycle, array $attrs = []): int
    {
        return DB::table('ticket_satisfactions')->insertGetId($attrs + ['ticket_id' => $ticket, 'cycle_id' => $cycle, 'filled_at' => '2026-11-04 10:00:00']);
    }

    public function test_follow_up_uses_current_survey_and_happy_call_for_that_response(): void
    {
        [$ready, $cycle] = $this->published();
        $response = $this->response($ready, $cycle, ['machine_ok' => true]);
        $this->log($ready, 'happy_call_completed', ['cycle_id' => $cycle, 'satisfaction_id' => $response]);
        [$blocked, $blockedCycle] = $this->published();
        $old = $this->response($blocked, $blockedCycle);
        $this->log($blocked, 'happy_call_completed', ['cycle_id' => $blockedCycle, 'satisfaction_id' => $old]);
        $this->response($blocked, $blockedCycle, ['review_status' => 'needs_review', 'filled_at' => '2026-11-05 10:00:00']);
        [$pending, $pendingCycle] = $this->published();
        $this->response($pending, $pendingCycle, ['machine_ok' => false]);
        $counts = $this->counts($this->data()['follow_up']);
        $this->assertSame(1, $counts['ready_to_close']);
        $this->assertSame(1, $counts['blocked_closed']);
        $this->assertSame(1, $counts['need_recoming']);
        $this->assertSame(1, $counts['happy_call_due']);
        foreach (['ready_to_close', 'blocked_closed', 'need_recoming', 'happy_call_due'] as $key) {
            $rows = $this->getJson(self::URL.'/tickets?queue='.$key)->assertOk()->json();
            $this->assertSame($counts[$key], $rows['total']);
            $this->assertArrayNotHasKey('machine_ok', $rows['data'][0]);
            $this->assertArrayNotHasKey('response_id', $rows['data'][0]);
        }
        // A new current response invalidates completion for the old survey.
        $this->response($ready, $cycle, ['filled_at' => '2026-11-05 11:00:00']);
        $this->assertSame(0, $this->counts($this->data()['follow_up'])['ready_to_close']);
    }

    public function test_follow_up_does_not_inherit_surveys_or_publication_from_previous_cycles(): void
    {
        [$id, $cycle] = $this->published();
        $response = $this->response($id, $cycle, ['machine_ok' => false]);
        $this->log($id, 'happy_call_completed', ['cycle_id' => $cycle, 'satisfaction_id' => $response]);
        DB::table('ticket_cycles')->insert(['ticket_id' => $id, 'cycle_no' => 2]);
        $this->log($id, 'recoming');
        $counts = $this->counts($this->data()['follow_up']);
        $this->assertSame([0, 0, 0, 0, 0, 0], array_values($counts));
    }

    public function test_survey_due_and_expired_links_respect_age_share_and_replacement(): void
    {
        [$due, $cycle] = $this->published();
        DB::table('satisfaction_links')->insert(['ticket_id' => $due, 'cycle_id' => $cycle, 'expires_at' => '2026-11-04 10:00:00']);
        [$shared, $sharedCycle] = $this->published();
        DB::table('satisfaction_links')->insert(['ticket_id' => $shared, 'cycle_id' => $sharedCycle, 'expires_at' => '2026-11-06', 'shared_at' => '2026-11-01']);
        $this->published([], '2026-11-02 12:00:00'); // Exactly 3 days is not more than 3 days.
        DB::table('satisfaction_links')->insert(['ticket_id' => $shared, 'cycle_id' => $sharedCycle, 'status' => 'replaced', 'expires_at' => '2026-11-01']);
        $counts = $this->counts($this->data()['follow_up']);
        $this->assertSame(1, $counts['survey_due']);
        $this->assertSame(1, $counts['link_expired']);
        $this->getJson(self::URL.'/tickets?queue=survey_due')->assertJsonPath('total', 1);
        $this->getJson(self::URL.'/tickets?queue=link_expired')->assertJsonPath('total', 1);
    }

    public function test_hygiene_counts_entities_not_tickets_and_only_actual_document_rework(): void
    {
        DB::table('service_customers')->insert(['id' => 2, 'name' => 'Missing', 'phone' => '  ', 'email' => null]);
        DB::table('service_customer_products')->insert([['id' => 2, 'machine_name' => 'Missing', 'is_daxtro' => true], ['id' => 3, 'machine_name' => 'Other brand', 'is_daxtro' => false]]);
        DB::table('ref_spareparts')->insert([['id' => 1, 'name' => 'Below', 'stock' => 1, 'min_stock' => 2], ['id' => 2, 'name' => 'No threshold', 'stock' => 0, 'min_stock' => 0]]);
        $ticket = $this->ticket('repair');
        foreach (['ticket_agreements', 'ticket_service_forms'] as $table) {
            DB::table($table)->insert(['ticket_id' => $ticket, 'unlocked_at' => '2026-11-01']);
            DB::table($table)->insert(['ticket_id' => $this->ticket(), 'unlocked_at' => null]);
        }
        $counts = $this->counts($this->data()['hygiene']);
        $this->assertSame([1, 1, 1, 1], array_values($counts));
        foreach ($counts as $key => $total) {
            $this->getJson(self::URL.'/hygiene?queue='.$key)->assertOk()->assertJsonPath('total', $total);
        }
        $this->getJson(self::URL.'/hygiene?queue=missing_contact')->assertJsonPath('entity', 'customer')->assertJsonPath('data.0.phone_missing', true);
    }

    public function test_workload_is_ranked_paginated_and_reopened_tickets_remain_active(): void
    {
        $id = $this->ticket('closed');
        $this->log($id, 'recoming');
        $this->ticket('repair', ['assigned_technician_id' => 11]);
        $this->ticket('closed', ['assigned_technician_id' => 11]);
        $this->ticket('repair', ['assigned_technician_id' => 10, 'is_deleted' => true]);
        $items = $this->data()['technicians']['items'];
        $this->assertSame([10, 11, 12], array_column($items, 'id'));
        $this->assertSame([1, 1, 0], array_column($items, 'active_tickets'));
        $this->assertSame(['on_duty', 'on_duty', 'standby'], array_column($items, 'status'));
        $this->getJson(self::URL.'/technicians?per_page=1&page=2')->assertOk()->assertJsonPath('data.0.id', 11)->assertJsonPath('data.0.rank', 2)->assertJsonPath('total', 3);
    }

    public function test_supervisor_scope_applies_to_every_panel_and_drilldown(): void
    {
        $own = $this->ticket('repair');
        $this->ticket('repair', ['supervisor_id' => 99, 'assigned_technician_id' => 11]);
        DB::table('service_customers')->insert(['id' => 2, 'name' => 'Outside', 'phone' => null, 'email' => null]);
        DB::table('service_customer_products')->insert(['id' => 2, 'machine_name' => 'Outside']);
        $this->login('supervisor_after_sales');
        $d = $this->data();
        $this->assertSame('supervisor', $d['scope']);
        $this->assertSame(1, $d['progress']['total_open']);
        $this->assertSame(1, $d['technicians']['total']);
        $this->assertSame([0, 0, 0, 0], array_values($this->counts($d['hygiene'])));
        $this->getJson(self::URL.'/tickets?queue=all')->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $own);
        $this->getJson(self::URL.'/tickets?queue=all&technician_id=11')->assertJsonPath('total', 0);
        $this->getJson(self::URL.'/hygiene?queue=missing_contact')->assertJsonPath('total', 0);
    }
}
