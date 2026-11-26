<?php

namespace Tests\Feature\Aftersales;

use App\Models\Aftersales\TicketSatisfaction;
use App\Models\User;
use App\Services\Aftersales\SupervisorDashboard;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class SupervisorDashboardTest extends TestCase
{
    private const URL = '/api/dashboard/aftersales/supervisor';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locked' => false]);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-05 12:00:00', 'Asia/Jakarta'));
        foreach (['ref_regions', 'ref_product_types', 'ref_products'] as $name) {
            Schema::create($name, function (Blueprint $t) {
                $t->id();
                $t->string('name');
            });
        }
        Schema::create('user_roles', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->softDeletes();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedBigInteger('role_id');
            $t->softDeletes();
        });
        Schema::create('service_customers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->integer('ref_region_id')->nullable();
            $this->deleted($t);
        });
        Schema::create('service_customer_products', function (Blueprint $t) {
            $t->id();
            $t->string('machine_name')->nullable();
            $t->integer('product_id')->nullable();
            $t->integer('ref_product_type_id')->nullable();
            $t->boolean('is_daxtro')->default(true);
            $t->date('warranty_end')->nullable();
            $this->deleted($t);
        });
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->string('ticket_code');
            $t->integer('customer_id');
            $t->integer('customer_product_id');
            $t->integer('assigned_technician_id')->nullable();
            $t->string('category')->nullable();
            $t->string('service_type')->nullable();
            $t->string('workflow_version')->nullable();
            $t->timestamps();
            $t->timestamp('sla_due_at')->nullable();
            $this->deleted($t);
        });
        (require database_path('migrations/2026_11_26_100000_create_ticket_technicians_table.php'))->up();
        Schema::create('ticket_logs', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->string('step');
            $t->timestamp('created_at');
            $this->deleted($t);
        });
        Schema::create('ticket_costs', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->string('category')->default('labor');
            $t->double('amount');
            $t->string('currency')->default('idr');
            $t->double('exchange_rate')->nullable();
            $this->deleted($t);
        });
        Schema::create('ticket_parts', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->integer('ref_sparepart_id');
            $t->double('qty');
            $t->double('unit_price');
            $t->string('type');
            $this->deleted($t);
        });
        Schema::create('ref_spareparts', function (Blueprint $t) {
            $t->id();
            $this->deleted($t);
        });
        Schema::create('ticket_recomings', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->string('reason');
            $t->timestamp('created_at');
            $this->deleted($t);
        });
        Schema::create('ticket_satisfactions', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->integer('cycle_id')->nullable();
            $t->integer('ref_survey_template_id')->nullable();
            $t->string('source');
            $t->string('review_status');
            $t->timestamp('filled_at');
            foreach (array_keys(TicketSatisfaction::ASPECTS) as $col) {
                $t->integer($col)->nullable();
            }
            $this->deleted($t);
        });
        Schema::create('ticket_satisfaction_answers', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_satisfaction_id');
            $t->string('type');
            $t->integer('value_number')->nullable();
        });
        Schema::create('satisfaction_links', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->integer('cycle_id')->nullable();
            $t->string('status');
            $t->timestamp('created_at');
            $this->deleted($t);
        });
        DB::table('ref_regions')->insert([['id' => 1, 'name' => 'Jakarta'], ['id' => 2, 'name' => 'Bandung']]);
        DB::table('ref_product_types')->insert([['id' => 1, 'name' => 'Tube'], ['id' => 2, 'name' => 'Cold Room']]);
        DB::table('user_roles')->insert([['id' => 1, 'code' => 'technician'], ['id' => 2, 'code' => 'supervisor_after_sales'], ['id' => 3, 'code' => 'sales']]);
        DB::table('users')->insert(['id' => 1, 'name' => 'Technician', 'role_id' => 1]);
        DB::table('service_customers')->insert(['id' => 1, 'name' => 'Customer', 'ref_region_id' => 1]);
        DB::table('ref_spareparts')->insert(['id' => 1]);
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

    private function login(string $role = 'supervisor_after_sales'): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => 7]);
        $roleModel = new class extends Model {};
        $roleModel->setRawAttributes(['code' => $role]);
        $user->setRelation('role', $roleModel);
        $user->shouldReceive('hasPermission')->andReturn(false);
        $this->actingAs($user);
    }

    private function ticket(array $machine = [], array $ticket = [], ?string $closed = '2026-11-04 12:00:00'): int
    {
        $machineId = DB::table('service_customer_products')->insertGetId($machine + ['machine_name' => 'TD150', 'ref_product_type_id' => 1, 'warranty_end' => '2026-12-31']);
        $id = DB::table('tickets')->insertGetId($ticket + ['ticket_code' => 'T-'.uniqid(), 'customer_id' => 1, 'customer_product_id' => $machineId, 'assigned_technician_id' => 1, 'category' => 'refrigeration_system', 'service_type' => 'repair', 'created_at' => '2026-10-20 12:00:00', 'sla_due_at' => '2026-11-05 12:00:00']);
        if ($closed) {
            DB::table('ticket_logs')->insert(['ticket_id' => $id, 'step' => 'closed', 'created_at' => $closed]);
        }

        return $id;
    }

    private function cost(int $id, float $amount, array $extra = []): void
    {
        DB::table('ticket_costs')->insert($extra + ['ticket_id' => $id, 'amount' => $amount]);
    }

    private function data(): array
    {
        return app(SupervisorDashboard::class)->summary([]);
    }

    public function test_financial_buckets_use_creation_date_actual_parts_and_closed_period(): void
    {
        $w = $this->ticket(['warranty_end' => '2026-10-20']);
        $this->cost($w, 10, ['currency' => 'usd', 'exchange_rate' => 16000]);
        foreach (['actual', 'estimated'] as $type) {
            DB::table('ticket_parts')->insert(['ticket_id' => $w, 'ref_sparepart_id' => 1, 'qty' => 2, 'unit_price' => 100000, 'type' => $type]);
        }
        $this->cost($this->ticket(['warranty_end' => '2026-10-19']), 500000);
        $this->cost($this->ticket(['is_daxtro' => false, 'warranty_end' => null]), 100000);
        $this->cost($this->ticket([], ['service_type' => 'preventive']), 70000);
        $this->cost($this->ticket(['warranty_end' => null]), 30000);
        $this->cost($this->ticket([], [], null), 9000000);
        $this->cost($this->ticket([], [], '2026-10-30 12:00:00'), 9000000);
        $d = $this->data();
        $this->assertEquals(360000, $d['kpi']['warranty']['value']);
        $this->assertEquals(600000, $d['kpi']['billed']['value']);
        $this->assertEquals(240000, $d['kpi']['net']['value']);
        $this->assertEquals(70000, $d['kpi']['pm']['value']);
        $this->assertEquals(30000, $d['unclassified_value']);
        $this->assertEquals([360000], $d['revenue']['Warranty']);
    }

    public function test_deleted_rows_and_missing_exchange_rates_are_not_silently_counted(): void
    {
        $this->cost($this->ticket(['is_deleted' => true]), 100);
        $this->cost($this->ticket([], ['deleted_at' => '2026-11-01']), 100);
        $this->cost($this->ticket(), 100, ['is_deleted' => true]);
        $id = $this->ticket();
        $this->cost($id, 10, ['currency' => 'usd']);
        $this->cost($id, 50);
        $d = $this->data();
        $this->assertEquals(0, $d['kpi']['warranty']['value']);
        $this->assertEquals(1, $d['invalid_rate_count']);
        $this->assertEquals(50, $d['unclassified_value']);
    }

    public function test_completion_excludes_external_reasons_and_counts_open_repeat_work(): void
    {
        $this->ticket();
        $external = $this->ticket();
        $repeat = $this->ticket([], [], null);
        foreach ([[$external, 'sparepart_unavailable'], [$repeat, 'unfinished_previous_work']] as [$id, $reason]) {
            DB::table('ticket_recomings')->insert(['ticket_id' => $id, 'reason' => $reason, 'created_at' => '2026-11-03']);
        }
        $this->assertEquals(50, $this->data()['health']['firstVisit']);
        $this->assertEquals(100, $this->data()['health']['sla']);
    }

    public function test_latest_response_only_is_counted_and_template_answers_are_averaged_per_ticket(): void
    {
        foreach ([[1, 'normal', false], [3, 'needs_review', true], [2, 'reviewed', false]] as [$score, $status, $history]) {
            $id = $this->ticket();
            if ($history) {
                DB::table('ticket_satisfactions')->insert(['ticket_id' => $id, 'source' => 'link', 'review_status' => 'normal', 'filled_at' => '2026-11-01', 'overall_satisfaction' => 3]);
            }
            DB::table('ticket_satisfactions')->insert(['ticket_id' => $id, 'source' => 'link', 'review_status' => $status, 'filled_at' => '2026-11-02', 'overall_satisfaction' => $score]);
        }
        $id = DB::table('ticket_satisfactions')->insertGetId(['ticket_id' => $this->ticket(), 'source' => 'link', 'review_status' => 'normal', 'filled_at' => '2026-11-03', 'ref_survey_template_id' => 1]);
        foreach ([3, 3] as $score) {
            DB::table('ticket_satisfaction_answers')->insert(['ticket_satisfaction_id' => $id, 'type' => 'rating', 'value_number' => $score]);
        }
        $this->assertEquals(2, $this->data()['health']['rating']);
    }

    public function test_general_rating_shares_the_card_rules_and_credits_each_assigned_technician(): void
    {
        DB::table('users')->insert([
            ['id' => 2, 'name' => 'Alpha SPV', 'role_id' => 2, 'deleted_at' => null],
            ['id' => 3, 'name' => 'Unrated Technician', 'role_id' => 1, 'deleted_at' => null],
            ['id' => 4, 'name' => 'Deleted Technician', 'role_id' => 1, 'deleted_at' => '2026-11-03'],
            ['id' => 5, 'name' => 'Sales', 'role_id' => 3, 'deleted_at' => null],
        ]);
        $multi = $this->ticket(ticket: ['workflow_version' => 'v3']);
        DB::table('ticket_technicians')->insert([
            ['ticket_id' => $multi, 'user_id' => 1, 'position' => 0],
            ['ticket_id' => $multi, 'user_id' => 2, 'position' => 1],
        ]);
        $response = function (int $ticket, ?int $score, string $date, array $attributes = []) {
            return DB::table('ticket_satisfactions')->insertGetId($attributes + [
                'ticket_id' => $ticket, 'source' => 'link', 'review_status' => 'normal',
                'filled_at' => $date, 'overall_satisfaction' => $score,
            ]);
        };
        $response($multi, 1, '2026-11-02', ['cycle_id' => 10]);
        $current = $response($multi, null, '2026-11-05', [
            'cycle_id' => 11, 'ref_survey_template_id' => 1, 'source' => 'admin', 'review_status' => 'reviewed',
        ]);
        foreach ([3, 3, 3] as $score) {
            DB::table('ticket_satisfaction_answers')->insert(['ticket_satisfaction_id' => $current, 'type' => 'rating', 'value_number' => $score]);
        }
        DB::table('ticket_satisfaction_answers')->insert(['ticket_satisfaction_id' => $current, 'type' => 'yes_no', 'value_number' => 1]);
        $response($multi, 1, '2026-11-04', ['cycle_id' => 11]); // Higher ID, earlier submission.
        $response($multi, 1, '2026-11-06', ['cycle_id' => 11, 'is_deleted' => true]);
        $response($multi, 3, '2026-11-02', ['cycle_id' => 12]);
        $response($multi, 3, '2026-11-03', ['cycle_id' => 12, 'review_status' => 'needs_review']);
        $response($multi, 3, '2026-11-03', ['cycle_id' => 13, 'source' => 'admin']);
        $response($multi, 3, '2026-11-03'); // An unmapped v3 response must not contribute.
        $response($multi, null, '2026-11-04', ['cycle_id' => 14]);
        $response($this->ticket(), 3, '2026-11-03'); // Legacy primary assignment without pivot rows.
        $response($this->ticket(['ref_product_type_id' => 2], ['assigned_technician_id' => 2]), 1, '2026-11-03');
        DB::table('service_customers')->insert(['id' => 2, 'name' => 'Outside region', 'ref_region_id' => 2]);
        $response($this->ticket(ticket: ['customer_id' => 2]), 1, '2026-11-03');

        $this->login();
        $filters = '?region=1&category=1';
        $this->getJson(self::URL.$filters)->assertOk()->assertJsonPath('data.health.rating', 2.3);
        $result = $this->getJson(self::URL.'/general-rating'.$filters)->assertOk()
            ->assertJsonPath('data.period.key', 'this_month')->assertJsonPath('data.period.start', '2026-11-01')
            ->assertJsonPath('data.rating_scale_max', 3)->assertJsonCount(3, 'data.technicians');
        $this->assertSame([
            ['technician_id' => 2, 'name' => 'Alpha SPV', 'rating' => 2, 'response_count' => 2],
            ['technician_id' => 1, 'name' => 'Technician', 'rating' => 2.3, 'response_count' => 3],
            ['technician_id' => 3, 'name' => 'Unrated Technician', 'rating' => null, 'response_count' => 0],
        ], $result->json('data.technicians'));

        $custom = $filters.'&period=custom&start_date=2026-11-04&end_date=2026-11-05';
        $this->getJson(self::URL.$custom)->assertOk()->assertJsonPath('data.health.rating', 3);
        $this->getJson(self::URL.'/general-rating'.$custom)->assertOk()
            ->assertJsonPath('data.period.key', 'custom')->assertJsonPath('data.period.end', '2026-11-05')
            ->assertJsonPath('data.technicians.0.rating', 3)->assertJsonPath('data.technicians.0.response_count', 1)
            ->assertJsonPath('data.technicians.1.rating', 3)->assertJsonPath('data.technicians.1.response_count', 1);
    }

    public function test_general_rating_is_restricted_to_the_three_prd_roles_and_validates_filters(): void
    {
        $url = self::URL.'/general-rating';
        $this->getJson($url)->assertUnauthorized();
        foreach (['after_sales', 'head_after_sales', 'supervisor_after_sales'] as $role) {
            $this->login($role);
            $this->getJson($url)->assertOk()->assertJsonPath('data.technicians.0.rating', null)
                ->assertJsonPath('data.technicians.0.response_count', 0);
        }
        foreach (['technician', 'sales', 'super_admin'] as $role) {
            $this->login($role);
            $this->getJson($url)->assertForbidden();
        }
        $this->login('after_sales');
        $this->getJson($url.'?period=custom')->assertUnprocessable()->assertJsonValidationErrors(['start_date', 'end_date']);
        $this->getJson($url.'?period=unknown&region=999')->assertUnprocessable()->assertJsonValidationErrors(['period', 'region']);
    }

    public function test_problem_modes_keep_totals_and_drilldown_matches_the_cell(): void
    {
        $this->ticket([], ['created_at' => '2026-11-02']);
        $this->ticket(['ref_product_type_id' => 2], ['created_at' => '2026-11-03', 'category' => null]);
        $d = $this->data();
        foreach ($d['problem'] as $mode) {
            $this->assertEquals(2, array_sum($mode['totals']));
        }
        $q = app(SupervisorDashboard::class)->drilldown(['metric' => 'problem', 'problem' => 'refrigeration_system', 'mode' => 'machine', 'column' => 'Tube']);
        $this->assertEquals(1, $q->count());
        $this->assertEquals('Tube', $q->first()->machine_category);
    }

    public function test_real_master_filters_apply_to_all_financial_series(): void
    {
        $this->cost($this->ticket(), 50);
        $this->cost($this->ticket(['ref_product_type_id' => 2]), 100);
        $d = app(SupervisorDashboard::class)->summary(['category' => 2]);
        $this->assertEquals(100, $d['kpi']['warranty']['value']);
        $this->assertEquals(0, app(SupervisorDashboard::class)->summary(['region' => 2])['kpi']['warranty']['value']);
    }

    public function test_empty_data_uses_null_for_rates_and_zero_for_money(): void
    {
        $d = $this->data();
        $this->assertNull($d['health']['sla']);
        $this->assertNull($d['health']['rating']);
        $this->assertNull($d['split']['ratio']);
        $this->assertEquals(0, $d['kpi']['net']['value']);
        $this->assertSame(['Tube', 'Cube', 'Block', 'Flake', 'Slurry', 'Cold Room', 'RO'], $d['problem']['machine']['column_labels']);
        $this->assertSame(array_fill(0, 4, array_fill(0, 7, 0)), $d['problem']['machine']['matrix']);
        $this->assertSame(['2026-11'], $d['problem']['month']['cols']);
    }

    public function test_summary_and_ticket_endpoints_are_restricted_to_supervisory_roles(): void
    {
        foreach (['after_sales', 'technician', 'sales'] as $role) {
            $this->login($role);
            $this->getJson(self::URL)->assertForbidden();
            $this->getJson(self::URL.'/tickets?metric=unclassified')->assertForbidden();
        }
        foreach (['supervisor_after_sales', 'head_after_sales'] as $role) {
            $this->login($role);
            $this->getJson(self::URL)->assertOk()->assertJsonPath('data.kpi.billed.value', 0);
        }
    }

    public function test_invalid_filters_are_rejected_and_previous_window_has_equal_days(): void
    {
        $this->login();
        $this->getJson(self::URL.'?period=custom')->assertUnprocessable();
        $this->getJson(self::URL.'?period=custom&start_date=2026-11-05&end_date=2026-11-01')->assertUnprocessable();
        $this->getJson(self::URL.'?region=999')->assertUnprocessable();
        $this->getJson(self::URL.'?period=last_7_days')->assertOk()->assertJsonPath('data.period.start', '2026-10-30')->assertJsonPath('data.period.previous_start', '2026-10-23');
    }

    public function test_survey_response_rate_handles_legacy_null_cycles_and_excludes_replaced_links(): void
    {
        $id = $this->ticket();
        DB::table('ticket_satisfactions')->insert(['ticket_id' => $id, 'source' => 'link', 'review_status' => 'normal', 'filled_at' => '2026-11-03', 'overall_satisfaction' => 3]);
        foreach ([[$id, 'used'], [$id, 'replaced'], [$this->ticket(), 'active']] as [$ticket, $status]) {
            DB::table('satisfaction_links')->insert(['ticket_id' => $ticket, 'status' => $status, 'created_at' => '2026-11-02']);
        }
        $this->assertEquals(50, $this->data()['health']['survey']);
    }

    public function test_reopened_tickets_are_excluded_until_the_latest_close_and_history_is_not_double_counted(): void
    {
        $id = $this->ticket([], [], '2026-10-01');
        $this->cost($id, 100);
        DB::table('ticket_logs')->insert(['ticket_id' => $id, 'step' => 'repair', 'created_at' => '2026-11-01']);
        $this->assertEquals(0, $this->data()['kpi']['warranty']['value']);
        DB::table('ticket_logs')->insert(['ticket_id' => $id, 'step' => 'closed', 'created_at' => '2026-11-02']);
        $this->assertEquals(100, $this->data()['kpi']['warranty']['value']);
    }

    public function test_unclassified_drilldown_paginates_without_exposing_survey_or_security_metadata(): void
    {
        $id = $this->ticket(['warranty_end' => null]);
        $this->cost($id, 100);
        $this->login();
        $r = $this->getJson(self::URL.'/tickets?metric=unclassified')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $id);
        $this->assertArrayNotHasKey('feedback', $r->json('data.0'));
        $this->assertArrayNotHasKey('token_hash', $r->json('data.0'));
    }
}
