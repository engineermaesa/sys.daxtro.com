<?php

namespace Tests\Feature\Aftersales;

use App\Models\Aftersales\TicketSatisfaction;
use App\Models\User;
use App\Models\UserRole;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class TechnicianDashboardTest extends TestCase
{
    private const URL = '/api/dashboard/aftersales/technician';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locked' => false]);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-05 12:00:00', 'Asia/Jakarta'));
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->string('ticket_code');
            $t->integer('assigned_technician_id');
            $t->integer('customer_id')->default(1);
            $t->integer('customer_product_id')->default(1);
            $t->string('priority')->default('medium');
            $t->string('workflow_version')->nullable();
            $t->timestamp('sla_due_at')->nullable();
            $t->timestamps();
            $this->deleted($t);
        });
        foreach (['service_customers', 'service_customer_products', 'ref_products', 'ref_product_types'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name')->nullable();
                $t->string('machine_name')->nullable();
                $t->integer('product_id')->nullable();
                $t->integer('ref_product_type_id')->nullable();
                $this->deleted($t);
            });
        }
        Schema::create('ticket_logs', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->string('step');
            $t->timestamp('created_at');
            $this->deleted($t);
        });
        Schema::create('ticket_cycles', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->integer('cycle_no');
            $t->timestamp('started_at')->nullable()->default('2026-10-01 00:00:00');
            $t->timestamp('sla_started_at')->nullable();
            $t->string('documentation_status')->default('incomplete');
            $t->timestamp('service_form_submitted_at')->nullable();
            $t->timestamp('sla_stopped_at')->nullable();
            $t->timestamp('sla_due_at')->nullable();
        });
        Schema::create('ticket_visits', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->integer('cycle_id')->nullable();
            $t->timestamp('scheduled_at');
            $t->timestamp('actual_start')->nullable();
            $t->timestamp('actual_end')->nullable();
            $this->deleted($t);
        });
        Schema::create('ticket_recomings', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->integer('cycle_id')->nullable();
            $t->string('recoming_type')->default('visit_site');
            $t->timestamp('planned_visit_at')->nullable();
            $t->timestamp('planned_call_at')->nullable();
            $this->deleted($t);
        });
        foreach (['ticket_agreements', 'ticket_service_forms'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->integer('ticket_id');
                $t->string('status');
                $t->softDeletes();
            });
        }
        Schema::create('satisfaction_links', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->integer('cycle_id')->nullable();
            $t->string('status')->default('active');
            $t->timestamp('expires_at');
            $t->timestamp('shared_at')->nullable();
            $this->deleted($t);
        });
        Schema::create('ticket_satisfactions', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->integer('cycle_id')->nullable();
            $t->integer('ref_survey_template_id')->nullable();
            $t->string('source')->default('link');
            $t->string('review_status')->default('normal');
            $t->timestamp('filled_at');
            foreach (array_keys(TicketSatisfaction::ASPECTS) as $aspect) {
                $t->integer($aspect)->nullable();
            }
            $this->deleted($t);
        });
        Schema::create('ticket_satisfaction_answers', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_satisfaction_id');
            $t->string('type')->default('rating');
            $t->integer('value_number')->nullable();
        });
        DB::table('service_customers')->insert(['id' => 1, 'name' => 'Own customer']);
        DB::table('service_customer_products')->insert(['id' => 1, 'machine_name' => 'TD 150', 'ref_product_type_id' => 1]);
        DB::table('ref_product_types')->insert(['id' => 1, 'name' => 'Tube']);
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

    private function login(string $role = 'technician', bool $permission = true, bool $sharePermission = true): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => 1]);
        $user->setRelation('role', new UserRole(['code' => $role]));
        $user->shouldReceive('hasPermission')->andReturnUsing(fn ($code) => $permission && ($code !== 'aftersales.survey-link.share' || $sharePermission));
        $this->actingAs($user);
    }

    private function ticket(string $step = 'assigned', array $attrs = [], string $at = '2026-11-04 10:00:00'): int
    {
        $id = DB::table('tickets')->insertGetId($attrs + ['ticket_code' => 'T-'.uniqid(), 'assigned_technician_id' => 1, 'created_at' => '2026-10-01 10:00:00', 'sla_due_at' => '2026-11-04 12:00:00']);
        DB::table('ticket_logs')->insert(['ticket_id' => $id, 'step' => $step, 'created_at' => $at]);

        return $id;
    }

    private function data(string $query = ''): array
    {
        return $this->getJson(self::URL.$query)->assertOk()->json('data');
    }

    public function test_empty_dashboard_has_zero_counts_null_rates_and_complete_week_buckets(): void
    {
        $data = $this->data();
        $this->assertSame(['score' => null, 'max' => 3], $data['rating']);
        $this->assertNull($data['sla']['value']);
        $this->assertSame(0, $data['tickets']['total_active']);
        $this->assertCount(6, $data['closed']['weeks']);
        $this->assertSame('2026-11-01', $data['closed']['weeks'][0]['start']);
        $this->assertSame('2026-11-30', $data['closed']['weeks'][5]['end']);
        $this->assertSame([], $data['today']['items']);
    }

    public function test_access_and_filter_validation_cannot_select_another_technician(): void
    {
        foreach (['sales', 'after_sales', 'supervisor_after_sales', 'head_after_sales'] as $role) {
            $this->login($role);
            foreach (['', '/tasks', '/tickets?metric=status', '/documents', '/recomings', '/survey-links'] as $suffix) {
                $this->getJson(self::URL.$suffix)->assertForbidden();
            }
        }
        $this->login('technician', false);
        $this->getJson(self::URL)->assertForbidden();
        $this->login();
        foreach (['?period=custom', '?user_id=2', '?technician_id=2', '?assigned_technician_id=2', '?per_page=101', '?page=0'] as $query) {
            $this->getJson(self::URL.$query)->assertUnprocessable();
        }
        $this->getJson(self::URL.'/tickets?metric=unknown')->assertUnprocessable();
        $this->getJson(self::URL.'/tickets?metric=status&status=closed')->assertUnprocessable();
        auth()->forgetGuards();
        $this->getJson(self::URL)->assertUnauthorized();
    }

    public function test_ownership_soft_deletes_and_derived_progress_apply_to_summary_and_lists(): void
    {
        $this->ticket();
        $onSite = $this->ticket();
        DB::table('ticket_visits')->insert(['ticket_id' => $onSite, 'scheduled_at' => '2026-11-05 20:00:00']);
        $this->ticket('repair');
        $this->ticket('documentation_published');
        $this->ticket('repair', ['assigned_technician_id' => 2]);
        $this->ticket('repair', ['is_deleted' => true]);
        $this->ticket('repair', ['deleted_at' => '2026-11-01']);
        $data = $this->data();
        $this->assertSame(3, $data['tickets']['total_active']);
        $this->assertSame(4, $data['tickets']['total_open']);
        $this->assertSame([1, 1, 1, 0], array_column($data['tickets']['statuses'], 'count'));
        $this->assertSame(1, $data['tickets']['other_active']);
        $rows = $this->getJson(self::URL.'/tickets?metric=status')->assertOk()->json('data');
        $this->assertCount(4, $rows);
        $this->assertArrayNotHasKey('assigned_technician_id', $rows[0]);
        $this->getJson(self::URL.'/tickets?metric=status&status=on_site')->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $onSite);
    }

    public function test_period_limits_only_closed_metrics_and_v3_uses_cycle_stop_not_close_time(): void
    {
        $this->ticket('closed');
        $this->ticket('closed', [], '2026-11-04 13:00:00');
        $this->ticket('closed', ['sla_due_at' => null]);
        $v3 = $this->ticket('closed', ['workflow_version' => 'v3'], '2026-11-20 12:00:00');
        DB::table('ticket_cycles')->insert(['ticket_id' => $v3, 'cycle_no' => 1, 'sla_due_at' => '2026-11-03 12:00:00', 'sla_stopped_at' => '2026-11-03 11:00:00']);
        $this->ticket('closed', [], '2026-10-04 10:00:00');
        $this->ticket('closed', ['assigned_technician_id' => 2]);
        $this->ticket('repair');
        $data = $this->data();
        $this->assertEquals(50, $data['sla']['value']);
        $this->assertSame([2, 1, 1, 4], [$data['sla']['on_time'], $data['sla']['overtime'], $data['sla']['unknown'], $data['sla']['total_closed']]);
        $this->assertSame(4, array_sum(array_column($data['closed']['weeks'], 'total')));
        $this->assertSame(1, $data['tickets']['total_active']);
        $previous = $this->data('?period=last_month');
        $this->assertSame(1, $previous['closed']['total']);
        $this->assertSame(1, $previous['tickets']['total_active']);
        $this->getJson(self::URL.'/tickets?metric=closed&week_start=2026-11-02')->assertJsonPath('total', 3);
        $this->getJson(self::URL.'/tickets?metric=closed&period=last_month')->assertJsonPath('total', 1);
    }

    public function test_today_uses_latest_visit_current_cycle_and_phone_call_with_stable_pagination(): void
    {
        $id = $this->ticket('repair');
        DB::table('ticket_visits')->insert([
            ['ticket_id' => $id, 'scheduled_at' => '2026-11-05 08:00:00'],
            ['ticket_id' => $id, 'scheduled_at' => '2026-11-06 08:00:00'],
        ]);
        $id = $this->ticket('repair');
        DB::table('ticket_visits')->insert(['ticket_id' => $id, 'scheduled_at' => '2026-11-05 09:00:00', 'actual_start' => '2026-11-05 09:00:00']);
        $foreign = $this->ticket('repair', ['assigned_technician_id' => 2]);
        DB::table('ticket_visits')->insert(['ticket_id' => $foreign, 'scheduled_at' => '2026-11-05 07:00:00']);
        $phone = $this->ticket('repair', ['workflow_version' => 'v3']);
        $cycle = DB::table('ticket_cycles')->insertGetId(['ticket_id' => $phone, 'cycle_no' => 2]);
        DB::table('ticket_recomings')->insert(['ticket_id' => $phone, 'cycle_id' => $cycle, 'recoming_type' => 'phone_call', 'planned_call_at' => '2026-11-05 13:00:00']);
        DB::table('ticket_visits')->insert(['ticket_id' => $phone, 'scheduled_at' => '2026-11-05 08:00:00']);
        $data = $this->data('?period=last_month');
        $this->assertSame(2, $data['today']['total']);
        $this->assertSame(['09:00', '13:00'], array_column($data['today']['items'], 'time'));
        $this->assertSame('active', $data['today']['items'][0]['status']);
        $this->assertSame('phone_call', $data['today']['items'][1]['type']);
        $this->assertNull($data['today']['items'][1]['visit_id']);
        $this->getJson(self::URL.'/tasks?per_page=1&page=2')->assertOk()->assertJsonPath('total', 2)->assertJsonPath('data.0.id', $phone);
    }

    public function test_todo_counts_match_lists_and_ignore_expired_shared_and_previous_cycle_links(): void
    {
        $doc = $this->ticket('repair', ['workflow_version' => 'v3']);
        DB::table('ticket_cycles')->insert(['ticket_id' => $doc, 'cycle_no' => 1]);
        $submitted = $this->ticket('repair', ['workflow_version' => 'v3']);
        DB::table('ticket_cycles')->insert(['ticket_id' => $submitted, 'cycle_no' => 1, 'documentation_status' => 'awaiting_spv_signature']);
        $share = $this->ticket('documentation_published', ['workflow_version' => 'v3']);
        $cycle = DB::table('ticket_cycles')->insertGetId(['ticket_id' => $share, 'cycle_no' => 2, 'documentation_status' => 'documentation_published']);
        DB::table('satisfaction_links')->insert(['ticket_id' => $share, 'cycle_id' => $cycle, 'expires_at' => '2026-11-06 12:00:00']);
        foreach ([['expires_at' => '2026-11-01'], ['shared_at' => '2026-11-05'], ['cycle_id' => 999]] as $attrs) {
            $ticket = $this->ticket('documentation_published', ['workflow_version' => 'v3']);
            $ownCycle = DB::table('ticket_cycles')->insertGetId(['ticket_id' => $ticket, 'cycle_no' => 1, 'documentation_status' => 'documentation_published']);
            DB::table('satisfaction_links')->insert($attrs + ['ticket_id' => $ticket, 'cycle_id' => $ownCycle, 'expires_at' => '2026-11-06 12:00:00']);
        }
        $rec = $this->ticket('recoming', ['workflow_version' => 'v3']);
        $recCycle = DB::table('ticket_cycles')->insertGetId(['ticket_id' => $rec, 'cycle_no' => 2, 'documentation_status' => 'awaiting_spv_signature']);
        DB::table('ticket_recomings')->insert(['ticket_id' => $rec, 'cycle_id' => $recCycle, 'planned_visit_at' => '2026-11-10 10:00:00']);
        $data = $this->data();
        $this->assertSame([1, 1, 1], array_column($data['todo']['items'], 'count'));
        foreach ($data['todo']['items'] as $item) {
            $this->getJson(self::URL.'/tickets?metric='.$item['key'])->assertOk()->assertJsonPath('total', $item['count']);
        }
    }

    public function test_rating_is_all_time_current_per_cycle_question_weighted_and_exposes_no_response_details(): void
    {
        $id = $this->ticket('closed', ['workflow_version' => 'v3']);
        foreach ([[1, 9, 1, 'link', 'normal'], [2, 7, 3, 'admin', 'reviewed']] as [$cycle, $count, $score, $source, $status]) {
            $response = DB::table('ticket_satisfactions')->insertGetId(['ticket_id' => $id, 'cycle_id' => $cycle, 'ref_survey_template_id' => 1, 'source' => $source, 'review_status' => $status, 'filled_at' => '2026-10-01 10:00:00']);
            for ($i = 0; $i < $count; $i++) {
                DB::table('ticket_satisfaction_answers')->insert(['ticket_satisfaction_id' => $response, 'value_number' => $score]);
            }
        }
        $foreign = $this->ticket('closed', ['assigned_technician_id' => 2]);
        DB::table('ticket_satisfactions')->insert(['ticket_id' => $foreign, 'overall_satisfaction' => 3, 'filled_at' => '2026-11-01 10:00:00']);
        $rating = $this->data()['rating'];
        $this->assertEquals(['score' => 1.9, 'max' => 3], $rating);
        DB::table('ticket_satisfactions')->insert(['ticket_id' => $id, 'cycle_id' => 2, 'review_status' => 'needs_review', 'overall_satisfaction' => 1, 'filled_at' => '2026-11-01 10:00:00']);
        $this->assertEquals(['score' => 1, 'max' => 3], $this->data()['rating']);
    }

    public function test_summary_preview_stays_on_first_page_and_tasks_paginate_without_duplicates(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $ticket = $this->ticket();
            DB::table('ticket_visits')->insert(['ticket_id' => $ticket, 'scheduled_at' => '2026-11-05 10:00:00']);
        }
        $data = $this->data('?page=2');
        $this->assertSame(12, $data['today']['total']);
        $this->assertTrue($data['today']['has_more']);
        $this->assertCount(10, $data['today']['items']);
        $this->assertSame(1, $data['today']['items'][0]['id']);
        $page = $this->getJson(self::URL.'/tasks?per_page=10&page=2')->assertOk()->json();
        $this->assertSame([11, 12], array_column($page['data'], 'id'));
    }

    public function test_v3_historical_visits_and_sla_do_not_replace_current_cycle_data(): void
    {
        $ticket = $this->ticket('closed', ['workflow_version' => 'v3']);
        $old = DB::table('ticket_cycles')->insertGetId(['ticket_id' => $ticket, 'cycle_no' => 1, 'sla_due_at' => '2026-11-04 12:00:00', 'sla_stopped_at' => '2026-11-04 11:00:00']);
        DB::table('ticket_cycles')->insert(['ticket_id' => $ticket, 'cycle_no' => 2]);
        DB::table('ticket_visits')->insert(['ticket_id' => $ticket, 'cycle_id' => $old, 'scheduled_at' => '2026-11-05 09:00:00']);
        $data = $this->data();
        $this->assertEquals(0, $data['sla']['value']);
        $this->assertSame(1, $data['sla']['unknown']);
        $this->assertSame(0, $data['today']['total']);
        // A new physical cycle must not inherit the task type from an older phone call.
        $ticket = $this->ticket('repair', ['workflow_version' => 'v3']);
        $old = DB::table('ticket_cycles')->insertGetId(['ticket_id' => $ticket, 'cycle_no' => 1]);
        $current = DB::table('ticket_cycles')->insertGetId(['ticket_id' => $ticket, 'cycle_no' => 2]);
        DB::table('ticket_recomings')->insert(['ticket_id' => $ticket, 'cycle_id' => $old, 'recoming_type' => 'phone_call', 'planned_call_at' => '2026-11-05 13:00:00']);
        DB::table('ticket_visits')->insert(['ticket_id' => $ticket, 'cycle_id' => $current, 'scheduled_at' => '2026-11-05 11:00:00']);
        $this->getJson(self::URL.'/tasks')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.type', 'visit_site');
    }

    public function test_deleted_logs_and_surveys_do_not_override_live_state_and_legacy_drafts_count_once(): void
    {
        $ticket = $this->ticket('repair');
        DB::table('ticket_logs')->insert(['ticket_id' => $ticket, 'step' => 'closed', 'created_at' => '2026-11-04 12:00:00', 'is_deleted' => true]);
        DB::table('ticket_satisfactions')->insert(['ticket_id' => $ticket, 'overall_satisfaction' => 2, 'filled_at' => '2026-10-01 10:00:00']);
        DB::table('ticket_satisfactions')->insert(['ticket_id' => $ticket, 'overall_satisfaction' => 3, 'filled_at' => '2026-11-01 10:00:00', 'is_deleted' => true]);
        DB::table('ticket_agreements')->insert(['ticket_id' => $ticket, 'status' => 'draft']);
        DB::table('ticket_service_forms')->insert(['ticket_id' => $ticket, 'status' => 'draft']);
        $data = $this->data();
        $this->assertEquals(2, $data['rating']['score']);
        $this->assertSame(0, $data['closed']['total']);
        $this->assertSame(1, $data['tickets']['total_active']);
        $this->getJson(self::URL.'/tickets?metric=documents')->assertJsonPath('total', 1);
        DB::table('ticket_agreements')->update(['status' => 'saved']);
        DB::table('ticket_service_forms')->update(['status' => 'saved']);
        $this->getJson(self::URL.'/tickets?metric=documents')->assertJsonPath('total', 0);
    }

    public function test_document_queue_lists_only_live_drafts_on_own_open_tickets_with_stable_pagination(): void
    {
        $own = $this->ticket('repair', ['workflow_version' => 'v3']);
        $foreign = $this->ticket('repair', ['assigned_technician_id' => 2]);
        $closed = $this->ticket('closed');
        $deleted = $this->ticket('repair', ['is_deleted' => true]);
        foreach (['ticket_agreements', 'ticket_service_forms'] as $table) {
            foreach ([$own, $foreign, $closed, $deleted] as $id) {
                DB::table($table)->insert(['ticket_id' => $id, 'status' => 'draft']);
            }
            DB::table($table)->insert(['ticket_id' => $own, 'status' => 'saved']);
            DB::table($table)->insert(['ticket_id' => $own, 'status' => 'draft', 'deleted_at' => '2026-11-04']);
        }
        $this->getJson(self::URL.'/documents?per_page=1')->assertOk()->assertJsonPath('total', 2)
            ->assertJsonPath('data.0.id', $own)->assertJsonPath('data.0.document_type', 'agreement')->assertJsonPath('data.0.status', 'draft');
        $this->getJson(self::URL.'/documents?per_page=1&page=2')->assertOk()->assertJsonPath('total', 2)
            ->assertJsonPath('data.0.document_type', 'service_form');
        $this->getJson(self::URL.'/documents?technician_id=2')->assertUnprocessable();
    }

    public function test_survey_queue_requires_permission_and_only_lists_current_active_unshared_own_site_links(): void
    {
        $eligible = [];
        foreach (['valid', 'shared', 'expired', 'used', 'foreign', 'deleted_link', 'deleted_ticket', 'old_cycle', 'phone_call', 'newer_used_link'] as $kind) {
            $ticket = $this->ticket('documentation_published', ['workflow_version' => 'v3',
                'assigned_technician_id' => $kind === 'foreign' ? 2 : 1, 'is_deleted' => $kind === 'deleted_ticket']);
            $cycle = DB::table('ticket_cycles')->insertGetId(['ticket_id' => $ticket, 'cycle_no' => 1]);
            DB::table('satisfaction_links')->insert(['ticket_id' => $ticket, 'cycle_id' => $cycle,
                'status' => $kind === 'used' ? 'used' : 'active',
                'shared_at' => $kind === 'shared' ? '2026-11-05 10:00:00' : null,
                'expires_at' => $kind === 'expired' ? '2026-11-04 10:00:00' : '2026-11-08 10:00:00',
                'is_deleted' => $kind === 'deleted_link']);
            if ($kind === 'old_cycle') {
                DB::table('ticket_cycles')->insert(['ticket_id' => $ticket, 'cycle_no' => 2]);
            }
            if ($kind === 'phone_call') {
                DB::table('ticket_recomings')->insert(['ticket_id' => $ticket, 'cycle_id' => $cycle, 'recoming_type' => 'phone_call']);
            }
            if ($kind === 'newer_used_link') {
                DB::table('satisfaction_links')->insert(['ticket_id' => $ticket, 'cycle_id' => $cycle, 'status' => 'used', 'expires_at' => '2026-11-08 10:00:00']);
            }
            if ($kind === 'valid') {
                $eligible[] = $ticket;
            }
        }
        $this->getJson(self::URL.'/survey-links')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $eligible[0]);
        $this->assertSame(1, $this->data()['todo']['items'][0]['count']);
        $this->getJson(self::URL.'/survey-links?technician_id=2')->assertUnprocessable();
        $this->login('technician', true, false);
        $this->getJson(self::URL.'/survey-links')->assertForbidden();
        $this->assertFalse($this->data()['capabilities']['can_share_survey']);
    }

    public function test_upcoming_recomings_are_own_current_cycle_next_seven_days_and_sorted_by_schedule(): void
    {
        foreach ([
            ['2026-11-05 13:00:00', 1, 'visit_site', false],
            ['2026-11-12 23:59:59', 1, 'phone_call', false],
            ['2026-11-06 09:00:00', 1, 'visit_site', false],
            ['2026-11-13 00:00:00', 1, 'visit_site', false],
            ['2026-11-04 10:00:00', 1, 'visit_site', false],
            ['2026-11-06 08:00:00', 2, 'visit_site', false],
            ['2026-11-07 08:00:00', 1, 'visit_site', true],
        ] as [$at, $owner, $type, $historical]) {
            $ticket = $this->ticket('recoming', ['assigned_technician_id' => $owner, 'workflow_version' => 'v3']);
            $cycle = DB::table('ticket_cycles')->insertGetId(['ticket_id' => $ticket, 'cycle_no' => 1]);
            DB::table('ticket_recomings')->insert(['ticket_id' => $ticket, 'cycle_id' => $cycle, 'recoming_type' => $type,
                $type === 'phone_call' ? 'planned_call_at' : 'planned_visit_at' => $at]);
            if ($historical) {
                DB::table('ticket_cycles')->insert(['ticket_id' => $ticket, 'cycle_no' => 2]);
            }
        }
        $response = $this->getJson(self::URL.'/recomings')->assertOk()->assertJsonPath('total', 3);
        $this->assertSame(['2026-11-05T13:00:00+07:00', '2026-11-06T09:00:00+07:00', '2026-11-12T23:59:59+07:00'], array_column($response->json('data'), 'scheduled_at'));
        $response->assertJsonPath('data.0.can_reschedule', false)->assertJsonPath('data.1.can_reschedule', true)->assertJsonPath('data.2.type', 'phone_call');
        $this->getJson(self::URL.'/recomings?assigned_technician_id=2')->assertUnprocessable();
    }
}
