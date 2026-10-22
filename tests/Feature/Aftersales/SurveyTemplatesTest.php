<?php

namespace Tests\Feature\Aftersales;

use App\Models\Aftersales\RefSurveyTemplate;
use App\Models\Aftersales\SatisfactionLink;
use App\Models\Aftersales\SatisfactionOtp;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketSatisfaction;
use App\Models\User;
use App\Services\Aftersales\SurveyResponses;
use Database\Seeders\SurveyTemplateSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class SurveyTemplatesTest extends TestCase
{
    private string $api = '/api/aftersales/survey-templates';

    private string $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a4X8AAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/2026_10_21_000000_create_user_login_audits_table.php'))->up();
        config(['app.locked' => false]);
        Storage::fake('local');
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->softDeletes();
        });
        Schema::create('service_customers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('phone')->nullable();
            $t->string('pic_name')->nullable();
            $t->softDeletes();
        });
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->string('ticket_code');
            $t->unsignedInteger('customer_id')->nullable();
            $t->unsignedInteger('assigned_technician_id')->nullable();
            $t->unsignedInteger('supervisor_id')->nullable();
            $t->softDeletes();
        });
        Schema::create('ticket_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('ticket_id');
            $t->string('step');
            $t->unsignedInteger('actor_user_id')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_visits', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('ticket_id');
            $t->timestamp('scheduled_at')->nullable();
            $t->timestamp('actual_start')->nullable();
            $t->softDeletes();
        });
        Schema::create('ticket_work_orders', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('ticket_id');
            $t->string('wo_number')->nullable();
            $t->softDeletes();
        });
        Schema::create('sessions', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('user_id');
            $t->string('ip_address')->nullable();
            $t->integer('last_activity');
        });
        Schema::create('service_customer_phone_changes', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('customer_id');
            $t->timestamp('changed_at');
        });
        Schema::create('satisfaction_links', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('ticket_id');
            $t->string('token_hash')->unique();
            $t->text('token_encrypted');
            $t->string('status');
            $t->timestamp('expires_at');
            foreach (['created_by_user_id', 'replaced_by_id', 'shared_by_user_id'] as $c) {
                $t->unsignedInteger($c)->nullable();
            }
            foreach (['used_at', 'shared_at'] as $c) {
                $t->timestamp($c)->nullable();
            }$t->string('shared_via')->nullable();
            $t->string('regenerate_reason')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('satisfaction_otps', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('link_id');
            $t->string('phone_masked')->nullable();
            $t->string('code_hash')->nullable();
            $t->string('session_hash')->nullable();
            $t->integer('attempts')->default(0);
            foreach (['sent_at', 'expires_at', 'verified_at', 'locked_until', 'session_expires_at'] as $c) {
                $t->timestamp($c)->nullable();
            }$t->timestamps();
        });
        Schema::create('ticket_satisfactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('ticket_id');
            foreach (array_keys(TicketSatisfaction::ASPECTS) as $c) {
                $t->unsignedTinyInteger($c)->nullable();
            }$t->boolean('machine_ok')->nullable();
            foreach (['machine_issue_note', 'feedback', 'signer_name', 'signer_position', 'signature_path', 'source', 'review_status', 'review_reasons', 'admin_input_reason', 'otp_phone_masked', 'shared_via', 'ip_address', 'user_agent'] as $c) {
                $t->text($c)->nullable();
            }
            foreach (['reviewed_by_user_id', 'link_created_by_user_id', 'duration_seconds', 'filled_by_user_id'] as $c) {
                $t->unsignedInteger($c)->nullable();
            }
            foreach (['reviewed_at', 'otp_verified_at', 'filled_at'] as $c) {
                $t->timestamp($c)->nullable();
            }$t->timestamps();
            $t->softDeletes();
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Aftersales Admin']);
        DB::table('service_customers')->insert(['id' => 1, 'name' => 'Customer', 'phone' => '081234567890']);
        DB::table('tickets')->insert(['id' => 1, 'ticket_code' => 'TEST-1', 'customer_id' => 1, 'assigned_technician_id' => 1]);
        DB::table('ticket_logs')->insert(['ticket_id' => 1, 'step' => 'documentation_published']);
        DB::table('satisfaction_links')->insert(['ticket_id' => 1, 'token_hash' => hash('sha256', 'old-token'), 'token_encrypted' => Crypt::encryptString('old-token'), 'status' => 'active', 'expires_at' => now()->addDays(7)]);
        DB::table('ticket_satisfactions')->insert(['ticket_id' => 1, 'timeliness' => 4, 'technician_attitude' => 5, 'machine_ok' => true, 'feedback' => 'Legacy feedback', 'source' => 'link']);
        (require database_path('migrations/2026_10_18_000000_version_survey_templates.php'))->up();
        $this->manager();
    }

    private function manager(bool $allowed = true): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill(['id' => 1, 'name' => 'Aftersales Admin']);
        $user->shouldReceive('hasPermission')->andReturn($allowed);
        $this->actingAs($user);
    }

    private function draft(): array
    {
        return $this->postJson($this->api.'/draft')->assertOk()->json('data');
    }

    private function save(array $draft)
    {
        return $this->putJson($this->api.'/'.$draft['id'], $draft);
    }

    private function activate(array $draft)
    {
        $url = $this->api.'/'.$draft['id'];
        $token = $this->postJson($url.'/preview', ['lock_version' => $draft['lock_version']])->assertOk()->json('preview_token');

        return $this->postJson($url.'/activate', ['lock_version' => $draft['lock_version'], 'confirmed' => true, 'preview_token' => $token]);
    }

    private function answerInput(RefSurveyTemplate $template): array
    {
        $answers = [];
        foreach ($template->questions() as $q) {
            $answers[$q['question_key']] = match ($q['type']) {
                'rating' => 3,'yes_no' => true,'short_text','long_text' => 'Text answer','choice' => $q['options'][0]
            };
        }

        return ['answers' => $answers, 'signer_name' => 'Customer', 'signer_position' => 'Manager', 'signature' => $this->signature];
    }

    private function validated(RefSurveyTemplate $template, array $input): array
    {
        return app(SurveyResponses::class)->validate(Request::create('/', 'POST', $input), $template);
    }

    public function test_migration_pins_legacy_links_and_responses_and_preserves_scores(): void
    {
        $template = RefSurveyTemplate::active();
        $this->assertCount(11, $template->questions());
        $this->assertCount(3, $template->content['scale_labels']);
        $this->assertSame($template->id, SatisfactionLink::first()->ref_survey_template_id);
        $response = TicketSatisfaction::first();
        $this->assertSame($template->id, $response->ref_survey_template_id);
        $this->assertSame(4.5, $response->average_score);
        $this->assertSame('Legacy feedback', $response->answers->firstWhere('question_key', 'feedback')->value_text);
    }

    public function test_only_one_draft_is_created_as_a_full_copy(): void
    {
        $draft = $this->draft();
        $again = $this->draft();
        $this->assertSame($draft['id'], $again['id']);
        $this->assertSame(2, $draft['version']);
        $this->assertSame(RefSurveyTemplate::active()->content, $draft['content']);
        $this->assertSame(1, DB::table('survey_template_audits')->count());
    }

    public function test_editing_generates_stable_keys_and_rejects_stale_or_forged_content(): void
    {
        $draft = $this->draft();
        $q = $draft['content']['sections'][0]['questions'][0];
        $q['question_key'] = null;
        $q['label'] = 'New rating';
        $draft['content']['sections'][0]['questions'][] = $q;
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->assertStringStartsWith('q_', $saved['content']['sections'][0]['questions'][9]['question_key']);
        $this->save($draft)->assertStatus(409);
        $bad = $saved;
        array_pop($bad['content']['sections'][0]['questions']);
        $this->save($bad)->assertUnprocessable();
        $bad = $saved;
        $bad['content']['sections'][0]['questions'][0]['question_key'] = 'changed';
        $this->save($bad)->assertUnprocessable();
    }

    public function test_core_question_cannot_be_disabled_made_optional_or_changed_type(): void
    {
        $draft = $this->draft();
        foreach (['is_active' => false, 'required' => false, 'type' => 'rating'] as $field => $value) {
            $bad = $draft;
            $bad['content']['sections'][1]['questions'][0][$field] = $value;
            $this->save($bad)->assertUnprocessable();
        }
        $bad = $draft;
        $bad['content']['sections'][1]['is_active'] = false;
        $this->save($bad)->assertUnprocessable();
        $draft['content']['sections'][1]['questions'][0]['label'] = 'Is your machine operational?';
        $this->save($draft)->assertOk();
    }

    public function test_activation_requires_rating_labels_active_content_preview_and_confirmation(): void
    {
        $draft = $this->draft();
        $this->postJson($this->api.'/'.$draft['id'].'/activate', ['lock_version' => 0, 'confirmed' => true])->assertUnprocessable();
        $draft['content']['scale_labels'][0] = '';
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->activate($saved)->assertUnprocessable();
        $saved['content']['scale_labels'][0] = 'Very Poor';
        foreach ($saved['content']['sections'][0]['questions'] as &$q) {
            $q['is_active'] = false;
        }unset($q);
        $saved = $this->save($saved)->assertOk()->json('data');
        $this->activate($saved)->assertUnprocessable();
        $this->assertSame(1, RefSurveyTemplate::active()->version);
    }

    public function test_disable_restore_reordering_and_audit_history(): void
    {
        $draft = $this->draft();
        $draft['content']['sections'][0]['questions'][0]['is_active'] = false;
        $draft['content']['sections'] = array_reverse($draft['content']['sections']);
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->assertCount(10, RefSurveyTemplate::find($saved['id'])->questions());
        $this->activate($saved)->assertOk();
        $this->save($saved)->assertStatus(409);
        $this->deleteJson($this->api.'/'.$saved['id'])->assertStatus(405);
        $next = $this->draft();
        $next['content']['sections'][2]['questions'][0]['is_active'] = true;
        $next = $this->save($next)->assertOk()->json('data');
        $this->assertCount(11, RefSurveyTemplate::find($next['id'])->questions());
        $this->assertSame(1, RefSurveyTemplate::where('status', 'active')->count());
        $this->assertSame('template_activated', DB::table('survey_template_audits')->where('action', 'template_activated')->value('action'));
    }

    public function test_regenerate_preserves_old_version_and_new_cycle_uses_current_active_version(): void
    {
        $old = SatisfactionLink::first();
        $draft = $this->draft();
        $this->activate($draft)->assertOk();
        $this->postJson('/api/aftersales/tickets/1/survey-link/regenerate', ['reason' => 'Replace link'])->assertCreated();
        $new = SatisfactionLink::latest('id')->first();
        $this->assertSame($old->ref_survey_template_id, $new->ref_survey_template_id);
        SatisfactionLink::deactivateFor(Ticket::find(1));
        $cycle = SatisfactionLink::issue(Ticket::find(1), 1);
        $this->assertSame($draft['id'], $cycle->ref_survey_template_id);
    }

    public function test_all_five_question_types_are_validated_and_saved(): void
    {
        $draft = $this->draft();
        $base = $draft['content']['sections'][0]['questions'][0];
        foreach (['short_text', 'long_text', 'choice', 'yes_no'] as $type) {
            $q = $base;
            $q['question_key'] = null;
            $q['type'] = $type;
            $q['label'] = $type;
            $q['options'] = $type === 'choice' ? ['Option A', 'Option B'] : [];
            $draft['content']['sections'][0]['questions'][] = $q;
        }
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->activate($saved)->assertOk();
        $template = RefSurveyTemplate::active();
        $input = $this->answerInput($template);
        $data = $this->validated($template, $input);
        $response = TicketSatisfaction::create(collect($data['attributes'])->except('signature')->all() + ['ticket_id' => 1]);
        app(SurveyResponses::class)->store($response, $data['answers']);
        $this->assertCount(15, $response->answers);
        $this->assertSame(3.0, $response->average_score);
        $choice = collect($template->questions())->firstWhere('type', 'choice');
        $input['answers'][$choice['question_key']] = 'Invalid option';
        $this->expectException(ValidationException::class);
        $this->validated($template, $input);
    }

    public function test_rating_range_required_answers_and_no_explanation_are_enforced(): void
    {
        $template = RefSurveyTemplate::active();
        foreach ([['timeliness' => 4], ['timeliness' => 6], ['timeliness' => null], ['machine_ok' => false]] as $change) {
            $input = $this->answerInput($template);
            $input['answers'] = array_replace($input['answers'], $change);
            try {
                $this->validated($template, $input);
                $this->fail('Invalid answers must fail');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
        $input = $this->answerInput($template);
        $input['answers']['machine_ok'] = false;
        $input['machine_issue_note'] = 'Machine still leaks';
        $this->assertFalse($this->validated($template, $input)['attributes']['machine_ok']);
    }

    public function test_inactive_or_unknown_questions_cannot_be_answered(): void
    {
        $draft = $this->draft();
        $draft['content']['sections'][0]['questions'][0]['is_active'] = false;
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->activate($saved)->assertOk();
        $template = RefSurveyTemplate::active();
        $input = $this->answerInput($template);
        $input['answers']['timeliness'] = 3;
        $this->expectException(ValidationException::class);
        $this->validated($template, $input);
    }

    public function test_recent_technician_login_is_recorded_with_file_sessions_and_flags_same_ip(): void
    {
        config(['session.driver' => 'file']);
        $request = Request::create('/login', 'POST', ['email' => 'technician@example.test', 'password' => 'test-password'], [], [], ['REMOTE_ADDR' => '192.0.2.10', 'HTTP_USER_AGENT' => 'Technician browser', 'HTTP_ACCEPT' => 'application/json']);
        $request->setLaravelSession(app('session')->driver('array'));
        $authManager = \Illuminate\Support\Facades\Auth::getFacadeRoot();
        \Illuminate\Support\Facades\Auth::shouldReceive('check')->andReturn(true);
        \Illuminate\Support\Facades\Auth::shouldReceive('attempt')->once()->with(['email' => 'technician@example.test', 'password' => 'test-password'], false)->andReturn(true);
        \Illuminate\Support\Facades\Auth::shouldReceive('user')->andReturn(User::find(1));
        $login = app(\App\Http\Controllers\Auth\AuthenticatedSessionController::class)->store($request);
        $this->assertSame(200, $login->getStatusCode());
        \Illuminate\Support\Facades\Auth::swap($authManager);
        $this->assertSame(1, DB::table('user_login_audits')->count());
        $this->otp();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])->withHeader('X-Survey-Session', 'verified-session')
            ->postJson('/api/survey/old-token/submit', $this->answerInput(RefSurveyTemplate::active()))->assertCreated();
        $response = TicketSatisfaction::latest('id')->first();
        $this->assertSame('needs_review', $response->review_status);
        $this->assertContains('same_network_as_technician', $response->review_reasons);
    }

    public function test_recent_matching_browser_flags_review_even_when_ip_changes(): void
    {
        DB::table('user_login_audits')->insert(['user_id' => 1, 'ip_address' => '192.0.2.10', 'user_agent_hash' => hash('sha256', 'Same browser'), 'logged_in_at' => now()->subHour()]);
        $this->otp();
        $this->withHeader('User-Agent', 'Same browser')->withHeader('X-Survey-Session', 'verified-session')
            ->postJson('/api/survey/old-token/submit', $this->answerInput(RefSurveyTemplate::active()))->assertCreated();
        $response = TicketSatisfaction::latest('id')->first();
        $this->assertSame('needs_review', $response->review_status);
        $this->assertContains('same_browser_as_technician', $response->review_reasons);
    }

    public function test_fast_completion_flags_review_without_matching_login(): void
    {
        $this->otp();
        SatisfactionOtp::query()->update(['verified_at' => now()->subSeconds(10)]);
        $this->withHeader('X-Survey-Session', 'verified-session')
            ->postJson('/api/survey/old-token/submit', $this->answerInput(RefSurveyTemplate::active()))->assertCreated();
        $response = TicketSatisfaction::latest('id')->first();
        $this->assertSame('needs_review', $response->review_status);
        $this->assertContains('fast_completion', $response->review_reasons);
    }

    public function test_old_or_other_users_login_does_not_flag_a_normal_response(): void
    {
        DB::table('users')->insert(['id' => 2, 'name' => 'Other technician']);
        DB::table('user_login_audits')->insert([
            ['user_id' => 1, 'ip_address' => '127.0.0.1', 'user_agent_hash' => hash('sha256', 'Same browser'), 'logged_in_at' => now()->subHours(25)],
            ['user_id' => 2, 'ip_address' => '127.0.0.1', 'user_agent_hash' => hash('sha256', 'Same browser'), 'logged_in_at' => now()],
        ]);
        $this->otp();
        $this->withHeader('User-Agent', 'Same browser')->withHeader('X-Survey-Session', 'verified-session')
            ->postJson('/api/survey/old-token/submit', $this->answerInput(RefSurveyTemplate::active()))->assertCreated();
        $response = TicketSatisfaction::latest('id')->first();
        $this->assertSame('normal', $response->review_status);
        $this->assertEmpty($response->review_reasons);
    }

    public function test_customer_form_requires_otp_and_uses_the_pinned_template(): void
    {
        $draft = $this->draft();
        $draft['content']['sections'][0]['questions'][0]['label'] = 'Updated arrival';
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->activate($saved)->assertOk();
        $this->getJson('/api/survey/old-token/form')->assertUnauthorized();
        $this->otp();
        $this->withHeader('X-Survey-Session', 'verified-session')->getJson('/api/survey/old-token/form')->assertOk()->assertJsonPath('data.template.version', 1)->assertJsonPath('data.template.sections.0.questions.0.label', 'Arrival punctuality');
    }

    public function test_generated_survey_link_opens_the_public_customer_page_without_login(): void
    {
        $link = SatisfactionLink::firstOrFail();
        $this->assertSame(url('/survey/old-token'), $link->url);
        $this->get($link->url)->assertOk()
            ->assertSee('data-token="old-token"', false)
            ->assertSee('customer-survey.js')
            ->assertSee('Verify your WhatsApp number');
    }

    private function otp(): void
    {
        SatisfactionOtp::create(['link_id' => SatisfactionLink::first()->id, 'verified_at' => now()->subMinute(), 'session_hash' => hash('sha256', 'verified-session'), 'session_expires_at' => now()->addMinutes(20)]);
    }

    public function test_customer_submission_stores_answers_on_pinned_version_and_is_single_use(): void
    {
        $this->otp();
        $input = $this->answerInput(RefSurveyTemplate::active());
        $this->withHeader('X-Survey-Session', 'verified-session')->postJson('/api/survey/old-token/submit', $input)->assertCreated();
        $response = TicketSatisfaction::latest('id')->first();
        $this->assertSame(1, $response->template->version);
        $this->assertCount(11, $response->answers);
        $this->assertSame(3.0, $response->average_score);
        $this->assertSame('used', SatisfactionLink::first()->status);
        $this->withHeader('X-Survey-Session', 'verified-session')->postJson('/api/survey/old-token/submit', $input)->assertStatus(410);
    }

    public function test_reporting_uses_only_present_ratings_and_excludes_admin_assisted_responses(): void
    {
        $template = RefSurveyTemplate::active();
        $data = $this->validated($template, $this->answerInput($template));
        foreach ([null, 'Admin assistance'] as $reason) {
            $response = TicketSatisfaction::create(collect($data['attributes'])->except('signature')->all() + ['ticket_id' => 1, 'admin_input_reason' => $reason]);
            app(SurveyResponses::class)->store($response, $data['answers']);
        }
        $scores = $this->getJson($this->api.'/question-scores')->assertOk()->json('data');
        $arrival = collect($scores)->firstWhere('question_key', 'timeliness');
        $this->assertSame(2, $arrival['response_count']);
        $this->assertSame(3.5, $arrival['average_score']);
    }

    public function test_customer_can_submit_dynamic_questions_on_a_newly_activated_link(): void
    {
        $draft = $this->draft();
        $q = $draft['content']['sections'][0]['questions'][0];
        $q['question_key'] = null;
        $q['type'] = 'choice';
        $q['label'] = 'Preferred follow-up';
        $q['options'] = ['Phone', 'Email'];
        $draft['content']['sections'][2]['questions'][] = $q;
        $draft['content']['sections'][0]['questions'][0]['is_active'] = false;
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->activate($saved)->assertOk();
        SatisfactionLink::deactivateFor(Ticket::find(1));
        $link = SatisfactionLink::issue(Ticket::find(1), 1);
        $token = Crypt::decryptString($link->token_encrypted);
        SatisfactionOtp::create(['link_id' => $link->id, 'verified_at' => now()->subMinute(), 'session_hash' => hash('sha256', 'new-session'), 'session_expires_at' => now()->addMinutes(20)]);
        $this->withHeader('X-Survey-Session', 'new-session')->postJson('/api/survey/'.$token.'/submit', $this->answerInput(RefSurveyTemplate::active()))->assertCreated();
        $response = TicketSatisfaction::latest('id')->first();
        $this->assertSame(2, $response->template->version);
        $this->assertNull($response->timeliness);
        $this->assertSame('Phone', $response->answers->firstWhere('type', 'choice')->value_text);
        $this->getJson('/api/aftersales/satisfaction-surveys/'.$response->id.'/form')->assertOk()->assertJsonPath('data.template.version', 2);
    }

    public function test_seed_does_not_overwrite_templates_and_access_is_restricted(): void
    {
        $template = RefSurveyTemplate::active();
        $template->update(['title' => 'Preserved title']);
        $this->seed(SurveyTemplateSeeder::class);
        $this->assertSame('Preserved title', $template->fresh()->title);
        $this->manager(false);
        $this->getJson($this->api)->assertForbidden();
        $this->postJson($this->api.'/draft')->assertForbidden();
        $this->putJson($this->api.'/1', [])->assertForbidden();
        $this->postJson($this->api.'/1/activate', [])->assertForbidden();
        $this->getJson($this->api.'/question-scores')->assertForbidden();
    }
}
