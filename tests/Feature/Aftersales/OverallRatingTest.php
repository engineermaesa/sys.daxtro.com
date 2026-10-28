<?php

namespace Tests\Feature\Aftersales;

use App\Models\Aftersales\TicketSatisfaction;
use App\Models\User;
use App\Services\Aftersales\TechnicianScore;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class OverallRatingTest extends TestCase
{
    private const ENDPOINT = '/api/aftersales/technicians/me/overall-rating';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locked' => false]);
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code');
            $table->unsignedBigInteger('assigned_technician_id');
            $table->softDeletes();
        });
        Schema::create('ticket_satisfactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('ref_survey_template_id')->nullable();
            foreach (array_keys(TicketSatisfaction::ASPECTS) as $aspect) {
                $table->integer($aspect)->nullable();
            }
            $table->string('source')->nullable();
            $table->string('review_status')->nullable();
            $table->timestamp('filled_at')->nullable();
            $table->text('feedback')->nullable();
            $table->string('signer_name')->nullable();
            $table->softDeletes();
        });
        Schema::create('ticket_satisfaction_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_satisfaction_id');
            $table->string('question_key');
            $table->string('type');
            $table->integer('value_number')->nullable();
        });
        foreach (['ticket_logs', 'ticket_visits', 'ticket_recomings'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_id');
                $table->softDeletes();
            });
        }
        Schema::create('ref_survey_templates', function (Blueprint $table) {
            $table->id();
            $table->json('content')->nullable();
            $table->softDeletes();
        });
        Schema::create('technician_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->softDeletes();
        });
    }

    private function login(int $id = 1, bool $allowed = true): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => $id]);
        $user->shouldReceive('hasPermission')->andReturnUsing(fn ($code) => $allowed && $code === 'aftersales.tickets.view-own');
        $this->actingAs($user);
    }

    private function ticket(int $technician = 1): int
    {
        return DB::table('tickets')->insertGetId(['ticket_code' => 'PRIVATE-TICKET-'.uniqid(), 'assigned_technician_id' => $technician]);
    }

    private function response(int $ticket, ?int $score, array $attributes = []): int
    {
        return DB::table('ticket_satisfactions')->insertGetId($attributes + [
            'ticket_id' => $ticket, 'overall_satisfaction' => $score, 'source' => 'link', 'review_status' => 'normal',
            'filled_at' => '2026-10-27 10:00:00', 'feedback' => 'Private feedback', 'signer_name' => 'Private Customer',
        ]);
    }

    public function test_self_rating_returns_only_one_rounded_aggregate_and_scale(): void
    {
        foreach ([2, 3, 3] as $score) {
            $this->response($this->ticket(), $score);
        }
        $this->login();
        $this->getJson(self::ENDPOINT)->assertOk()->assertExactJson(['data' => ['overall_rating' => 2.7, 'rating_scale_max' => 3]]);
    }

    public function test_empty_or_excluded_responses_return_null_instead_of_zero(): void
    {
        $this->login();
        $expected = ['data' => ['overall_rating' => null, 'rating_scale_max' => 3]];
        $this->getJson(self::ENDPOINT)->assertOk()->assertExactJson($expected);
        foreach ([['source' => 'admin', 'review_status' => 'reviewed'], ['review_status' => 'needs_review'], ['review_status' => 'rejected'], ['source' => null], ['review_status' => null]] as $attributes) {
            $this->response($this->ticket(), 1, $attributes);
        }
        $this->response($this->ticket(), null);
        $this->getJson(self::ENDPOINT)->assertOk()->assertExactJson($expected);
    }

    public function test_rating_counts_current_valid_responses_without_falling_back_to_valid_history(): void
    {
        $this->response($this->ticket(), 3, ['review_status' => 'reviewed']);
        $ticket = $this->ticket();
        $this->response($ticket, 1, ['filled_at' => '2026-10-26 10:00:00']);
        $this->response($ticket, 2);
        foreach (['needs_review', 'rejected'] as $status) {
            $ticket = $this->ticket();
            $this->response($ticket, 1, ['filled_at' => '2026-10-26 10:00:00']);
            $this->response($ticket, 1, ['review_status' => $status]);
        }
        $ticket = $this->ticket();
        $this->response($ticket, 1, ['filled_at' => '2026-10-26 10:00:00']);
        $this->response($ticket, 1, ['source' => 'admin', 'review_status' => 'reviewed']);
        $this->login();
        $this->getJson(self::ENDPOINT)->assertOk()->assertJsonPath('data.overall_rating', 2.5);
    }

    public function test_current_response_uses_submission_time_with_id_as_tiebreaker(): void
    {
        $ticket = $this->ticket();
        $this->response($ticket, 3);
        $this->response($ticket, 1, ['filled_at' => '2026-10-25 10:00:00']);
        $tie = $this->ticket();
        $this->response($tie, 1);
        $this->response($tie, 3);
        $this->login();
        $this->getJson(self::ENDPOINT)->assertOk()->assertJsonPath('data.overall_rating', 3);
    }

    public function test_soft_deleted_tickets_and_responses_do_not_contribute(): void
    {
        $this->response($this->ticket(), 3);
        $deletedTicket = $this->ticket();
        $this->response($deletedTicket, 1);
        DB::table('tickets')->where('id', $deletedTicket)->update(['deleted_at' => now()]);
        $this->response($this->ticket(), 1, ['deleted_at' => now()]);
        $this->login();
        $this->getJson(self::ENDPOINT)->assertOk()->assertJsonPath('data.overall_rating', 3);
    }

    public function test_versioned_answers_use_equal_weight_per_response_and_match_manager_rating(): void
    {
        $first = $this->response($this->ticket(), null, ['ref_survey_template_id' => 1]);
        $second = $this->response($this->ticket(), null, ['ref_survey_template_id' => 1]);
        DB::table('ticket_satisfaction_answers')->insert([
            ['ticket_satisfaction_id' => $first, 'question_key' => 'one', 'type' => 'rating', 'value_number' => 1],
            ['ticket_satisfaction_id' => $second, 'question_key' => 'one', 'type' => 'rating', 'value_number' => 3],
            ['ticket_satisfaction_id' => $second, 'question_key' => 'two', 'type' => 'rating', 'value_number' => 3],
            ['ticket_satisfaction_id' => $second, 'question_key' => 'machine_ok', 'type' => 'yes_no', 'value_number' => 0],
        ]);
        $this->login();
        $response = $this->getJson(self::ENDPOINT)->assertOk()->assertJsonPath('data.overall_rating', 2);
        $manager = TechnicianScore::summaries([1], null)[1];
        $this->assertEquals($manager['rating'], $response->json('data.overall_rating'));
        $this->assertSame(2, $manager['rating_count']);
    }

    public function test_identity_and_ticket_filters_cannot_select_another_technician_or_change_the_aggregate(): void
    {
        $this->response($this->ticket(1), 3, ['filled_at' => '2020-01-01 10:00:00']);
        $this->response($this->ticket(2), 1);
        $this->login(1);
        $this->getJson(self::ENDPOINT.'?user_id=2&assigned_technician_id=2&period=30&visit_date=2026-10-28')
            ->assertOk()->assertExactJson(['data' => ['overall_rating' => 3, 'rating_scale_max' => 3]]);
        DB::table('technician_profiles')->insert(['id' => 2, 'user_id' => 2]);
        $this->getJson('/api/aftersales/technicians/2')->assertForbidden();
        $this->login(2);
        $this->getJson(self::ENDPOINT)->assertOk()->assertJsonPath('data.overall_rating', 1);
    }

    public function test_ticket_reassignment_moves_rating_to_the_current_assigned_technician(): void
    {
        $ticket = $this->ticket();
        $this->response($ticket, 3);
        DB::table('tickets')->where('id', $ticket)->update(['assigned_technician_id' => 2]);
        $this->login(1);
        $this->getJson(self::ENDPOINT)->assertOk()->assertJsonPath('data.overall_rating', null);
        $this->login(2);
        $this->getJson(self::ENDPOINT)->assertOk()->assertJsonPath('data.overall_rating', 3);
    }

    public function test_self_rating_requires_login_and_view_own_permission(): void
    {
        $this->getJson(self::ENDPOINT)->assertUnauthorized();
        $this->login(1, false);
        $this->getJson(self::ENDPOINT)->assertForbidden();
    }
}
