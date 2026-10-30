<?php

namespace Tests\Feature\Aftersales;

use App\Jobs\SendTicketDocumentEmail;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketAgreement;
use App\Models\Aftersales\TicketDocumentDispatch;
use App\Models\Aftersales\TicketDocumentPublication;
use App\Models\Aftersales\TicketServiceForm;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class DocumentWorkflowV3Test extends TestCase
{
    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a4X8AAAAASUVORK5CYII=';

    private string $url;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locked' => false, 'app.debug' => false, 'app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        Storage::fake('local');
        Queue::fake();
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->softDeletes();
        });
        Schema::create('service_customers', function (Blueprint $t) {
            $t->id();
            $t->string('email')->nullable();
            $t->softDeletes();
        });
        Schema::create('service_customer_products', function (Blueprint $t) {
            $t->id();
            foreach (['capacity', 'refrigerant', 'condenser_type', 'voltage', 'phase', 'hz'] as $field) {
                $t->string($field)->nullable()->default('1');
            }
            $t->softDeletes();
        });
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->string('ticket_code');
            foreach (['assigned_technician_id', 'supervisor_id', 'created_by', 'customer_id', 'customer_product_id', 'updated_by'] as $field) {
                $t->unsignedBigInteger($field)->nullable();
            }
            $t->string('service_type')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        foreach (['ticket_logs', 'ticket_visits', 'ticket_recomings', 'ticket_satisfactions', 'ticket_work_orders'] as $name) {
            Schema::create($name, function (Blueprint $t) use ($name) {
                $t->id();
                $t->unsignedBigInteger('ticket_id');
                if ($name === 'ticket_logs') {
                    $t->string('step');
                    $t->unsignedBigInteger('actor_user_id')->nullable();
                    $t->text('note')->nullable();
                }
                if ($name === 'ticket_visits') {
                    $t->timestamp('scheduled_at')->nullable();
                    $t->timestamp('actual_start')->nullable();
                }
                if ($name === 'ticket_recomings') {
                    $t->unsignedInteger('visit_no');
                    $t->enum('reason', ['sparepart_unavailable', 'further_work_needed', 'site_access', 'other']);
                    $t->timestamp('planned_visit_at');
                    $t->string('from_step');
                    $t->text('notes')->nullable();
                    $t->unsignedBigInteger('created_by_user_id');
                }
                if ($name === 'ticket_satisfactions') {
                    $t->timestamp('filled_at')->nullable();
                    $t->string('review_status')->default('normal');
                }
                $t->timestamps();
                $t->softDeletes();
            });
        }
        Schema::create('ref_inspection_templates', function (Blueprint $t) {
            $t->id();
            $t->integer('version')->default(1);
            $t->string('title')->default('Inspection');
            $t->string('diagnosis_section_title')->default('Diagnosis');
            $t->json('diagnosis_fields')->nullable();
        });
        Schema::create('ref_inspection_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ref_inspection_template_id');
            $t->integer('number');
            $t->softDeletes();
        });
        Schema::create('ticket_inspections', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_service_form_id');
            $t->unsignedBigInteger('ref_inspection_item_id');
        });
        Schema::create('ref_agreement_templates', function (Blueprint $t) {
            $t->id();
            $t->json('sections');
            $t->softDeletes();
        });
        foreach (['ticket_service_forms' => 'ref_inspection_template_id', 'ticket_agreements' => 'ref_agreement_template_id'] as $name => $key) {
            Schema::create($name, function (Blueprint $t) use ($key) {
                $t->id();
                $t->unsignedBigInteger('ticket_id');
                $t->unsignedBigInteger($key)->default(1);
                $t->integer('template_version')->default(1);
                $t->string('status')->default('draft');
                $t->string('customer_name')->nullable();
                $t->json('accepted_clauses')->nullable();
                $t->timestamp('saved_at')->nullable();
                $t->timestamp('first_draft_at')->nullable();
                $t->unsignedBigInteger('saved_by_user_id')->nullable();
                $t->timestamp('unlocked_at')->nullable();
                $t->unsignedBigInteger('unlocked_by_user_id')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
        }
        Schema::create('ticket_diagnosis_entries', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_service_form_id');
            $t->string('field');
            $t->unsignedInteger('visit_no')->default(1);
            $t->date('entry_date')->default('2026-10-30');
            $t->time('visit_time')->default('10:00:00');
            $t->string('name')->nullable();
            $t->text('visit_notes')->nullable();
            $t->unsignedBigInteger('created_by_user_id')->nullable();
            $t->text('body');
            $t->timestamp('locked_at')->nullable();
            $t->timestamps();
            $t->unique(['ticket_service_form_id', 'field', 'visit_no'], 'diagnosis_form_field_visit');
        });
        Schema::create('ticket_photos', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id')->nullable();
            $t->unsignedBigInteger('ticket_service_form_id');
            $t->string('category');
            $t->string('diagnosis_field');
            $t->integer('sequence')->default(1);
            $t->timestamp('locked_at')->nullable();
            $t->string('file_path')->nullable();
            $t->string('file_name')->nullable();
            $t->timestamp('captured_at')->nullable();
            $t->string('caption')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_signatures', function (Blueprint $t) {
            $t->id();
            $t->morphs('signable');
            $t->string('role');
            $t->string('signer_name');
            $t->unsignedBigInteger('signed_by_user_id')->nullable();
            $t->unsignedBigInteger('captured_by_user_id')->nullable();
            $t->string('file_path');
            $t->timestamp('signed_at');
            $t->string('ip_address')->nullable();
            $t->string('user_agent')->nullable();
            $t->timestamps();
            $t->unique(['signable_type', 'signable_id', 'role']);
        });
        Schema::create('ref_survey_templates', function (Blueprint $t) {
            $t->id();
            $t->string('variant')->default('site');
            $t->string('status');
        });
        Schema::create('satisfaction_links', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->unsignedBigInteger('ref_survey_template_id');
            $t->string('token_hash')->unique();
            $t->text('token_encrypted');
            $t->string('status');
            $t->timestamp('expires_at');
            $t->unsignedBigInteger('created_by_user_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_document_dispatches', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            foreach (['document_type', 'recipient_role', 'recipient_email', 'copy_label', 'status', 'error'] as $field) {
                $t->string($field)->nullable();
            }
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
        });
        (require database_path('migrations/2026_10_30_000000_create_ticket_cycles.php'))->up();
        Schema::table('ticket_cycles', fn (Blueprint $t) => $t->unsignedBigInteger('survey_template_id')->nullable());
        (require database_path('migrations/2026_10_30_000100_add_document_publication_workflow.php'))->up();
        (require database_path('migrations/2026_10_30_000200_add_recoming_v3_scheduling.php'))->up();
        Schema::create('user_activity_logs', function (Blueprint $t) {
            $t->id();
            foreach (['user_id', 'action', 'model_type', 'model_id', 'description', 'data', 'ip_address', 'user_agent'] as $field) {
                $t->text($field)->nullable();
            }
            $t->timestamps();
        });
        foreach ([1 => 'Technician', 2 => 'SPV', 3 => 'Admin', 4 => 'Head', 5 => 'Other SPV', 6 => 'Other technician'] as $id => $name) {
            DB::table('users')->insert(['id' => $id, 'name' => $name, 'email' => 'user'.$id.'@example.com']);
        }
        DB::table('service_customers')->insert(['id' => 1, 'email' => 'customer@example.com']);
        DB::table('service_customer_products')->insert(['id' => 1]);
        DB::table('ref_inspection_templates')->insert(['id' => 1, 'diagnosis_fields' => json_encode(\App\Services\Aftersales\InspectionTemplates::DIAGNOSIS)]);
        DB::table('ref_agreement_templates')->insert(['id' => 1, 'sections' => json_encode([['title' => 'Terms', 'clauses' => [['key' => 'A1', 'text' => 'Terms']]]])]);
        DB::table('ref_survey_templates')->insert(['id' => 1, 'status' => 'active']);
        DB::table('ref_survey_templates')->insert(['id' => 2, 'status' => 'active', 'variant' => 'phone_call']);
        $ticket = Ticket::create(['ticket_code' => 'V3-1', 'assigned_technician_id' => 1, 'supervisor_id' => 2, 'created_by' => 3,
            'customer_id' => 1, 'customer_product_id' => 1, 'service_type' => 'repair']);
        $this->url = '/api/aftersales/tickets/'.$ticket->id;
        TicketServiceForm::create(['ticket_id' => $ticket->id]);
        TicketAgreement::create(['ticket_id' => $ticket->id, 'accepted_clauses' => ['A1'], 'customer_name' => 'Customer']);
        DB::table('ticket_work_orders')->insert(['ticket_id' => $ticket->id]);
        foreach (['problem', 'cause', 'action'] as $field) {
            DB::table('ticket_diagnosis_entries')->insert(['ticket_service_form_id' => 1, 'field' => $field, 'body' => 'Real '.$field]);
            DB::table('ticket_photos')->insert(['ticket_service_form_id' => 1, 'category' => 'diagnosis', 'diagnosis_field' => $field]);
        }
        (require database_path('migrations/2026_10_30_000300_scope_diagnosis_to_ticket_cycles.php'))->up();
        (require database_path('migrations/2026_10_30_000400_add_cycle_sla_setup.php'))->up();
        $this->login(1);
    }

    private function login(int $id): void
    {
        $permissions = match ($id) {
            1, 6 => ['aftersales.documents.fill', 'aftersales.tickets.view-own'],
            2, 5 => ['aftersales.documents.sign', 'aftersales.tickets.view-all'],
            3 => ['aftersales.tickets.manage', 'aftersales.documentation.submit'],
            default => ['aftersales.tickets.view-all', 'aftersales.tickets.close'],
        };
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => $id, 'name' => 'User '.$id]);
        $user->shouldReceive('hasPermission')->andReturnUsing(fn ($p) => in_array($p, $permissions, true));
        $this->actingAs($user);
    }

    private function signature(string $type, string $role)
    {
        return $this->postJson($this->url.'/documents/'.$type.'/signatures', ['role' => $role, 'signature' => self::PNG, 'signer_name' => 'Customer']);
    }

    private function prepare(): void
    {
        $this->login(1);
        foreach (['service_form', 'agreement'] as $type) {
            $this->signature($type, 'technician')->assertCreated();
            $this->signature($type, 'customer')->assertCreated();
        }
    }

    private function findings(): array
    {
        return ['diagnosis' => array_map(fn ($field) => ['field' => $field, 'body' => 'New cycle '.$field], ['problem', 'root_cause', 'action'])];
    }

    private function sla(): array
    {
        return ['sla_mode' => 'duration', 'sla_value' => 2, 'sla_unit' => 'day'];
    }

    private function beginRecoming(string $type = 'phone_call'): int
    {
        $this->publishInOrder(['service_form', 'agreement']);
        DB::table('ticket_service_forms')->update(['first_draft_at' => now()->subDay()]);
        $this->login(3);
        $this->postJson($this->url.'/documents/service_form/unlock')->assertOk();
        $response = $this->postJson($this->url.'/recoming', ['recoming_type' => $type, 'reason' => 'unfinished_previous_work',
            $type === 'phone_call' ? 'planned_call_at' : 'planned_visit_at' => now()->addDay()->format('Y-m-d H:i:s')] + $this->sla())->assertCreated();
        $this->login(1);

        return $response->json('data.data.cycle_id');
    }

    public function test_submit_requires_real_content_and_non_spv_signatures_and_rolls_back_partial_input(): void
    {
        $this->postJson($this->url.'/service-form/submit', ['service_type' => 'preventive'])->assertUnprocessable()
            ->assertJsonPath('code', 'service_form_incomplete');
        $this->assertDatabaseHas('tickets', ['service_type' => 'repair']);
        $this->assertDatabaseHas('ticket_cycles', ['documentation_status' => 'incomplete', 'service_form_submitted_at' => null]);
        $this->assertDatabaseHas('ticket_service_forms', ['status' => 'draft', 'saved_at' => null]);
        $this->assertDatabaseCount('satisfaction_links', 0);
        Queue::assertNothingPushed();
        $this->prepare();
        DB::table('ticket_photos')->where('diagnosis_field', 'cause')->delete();
        $this->postJson($this->url.'/service-form/submit')->assertUnprocessable()->assertJsonPath('code', 'service_form_incomplete');
    }

    public function test_submit_is_idempotent_locks_form_and_never_publishes_without_spv(): void
    {
        $this->prepare();
        $this->getJson($this->url.'/documents')->assertOk()->assertJsonPath('data.documentation_status', 'incomplete')
            ->assertJsonPath('data.documents.service_form.can.submit', true)->assertJsonPath('data.documents.service_form.can.save', false);
        $first = $this->postJson($this->url.'/service-form/submit')->assertOk()->assertJsonPath('data.documentation_status', 'awaiting_spv_signature');
        $this->postJson($this->url.'/service-form/submit')->assertOk()->assertJsonPath('data.submitted_at', $first->json('data.submitted_at'));
        $this->putJson($this->url.'/service-form', ['service_type' => 'preventive'])->assertUnprocessable();
        $this->signature('service_form', 'customer')->assertConflict()->assertJsonPath('code', 'document_locked');
        $this->assertDatabaseHas('ticket_service_forms', ['status' => 'awaiting_spv_signature', 'saved_at' => null]);
        $this->assertDatabaseCount('ticket_document_publications', 0);
        $this->assertDatabaseCount('ticket_document_dispatches', 0);
        $this->assertDatabaseCount('satisfaction_links', 0);
    }

    public function test_spv_signatures_publish_once_in_either_order(): void
    {
        $this->publishInOrder(['service_form', 'agreement']);
    }

    public function test_agreement_can_be_signed_first_and_form_signature_finishes_publication(): void
    {
        $this->publishInOrder(['agreement', 'service_form']);
    }

    private function publishInOrder(array $order): void
    {
        $this->prepare();
        $this->postJson($this->url.'/service-form/submit')->assertOk();
        $this->login(2);
        $this->signature($order[0], 'supervisor')->assertCreated()->assertJsonPath('data.status', 'awaiting_spv_signature');
        $this->assertDatabaseCount('ticket_document_publications', 0);
        $this->assertDatabaseCount('ticket_document_dispatches', 0);
        $this->signature($order[1], 'supervisor')->assertCreated()->assertJsonPath('data.status', 'documentation_published');
        $this->signature($order[0], 'supervisor')->assertCreated();
        $this->signature($order[1], 'supervisor')->assertCreated();
        $this->assertDatabaseCount('ticket_document_publications', 1);
        $this->assertDatabaseCount('ticket_logs', 1);
        $this->assertDatabaseCount('satisfaction_links', 1);
        $this->assertDatabaseCount('ticket_document_dispatches', 6);
        $cycle = Ticket::first()->currentCycle;
        $this->assertDatabaseHas('ticket_logs', ['cycle_id' => $cycle->id, 'step' => 'documentation_published']);
        $this->assertDatabaseHas('satisfaction_links', ['cycle_id' => $cycle->id]);
        $this->assertSame(6, TicketDocumentDispatch::where('publication_id', $cycle->publication->id)->count());
        Queue::assertPushed(SendTicketDocumentEmail::class, 6);
    }

    public function test_signing_requires_submission_and_the_assigned_spv(): void
    {
        $this->prepare();
        $this->login(2);
        $this->signature('service_form', 'supervisor')->assertConflict()->assertJsonPath('code', 'service_form_not_submitted');
        $this->login(1);
        $this->postJson($this->url.'/service-form/submit')->assertOk();
        foreach ([1, 3, 4, 5, 6] as $id) {
            $this->login($id);
            $this->signature('service_form', 'supervisor')->assertForbidden();
        }
        foreach ([2, 3, 4, 6] as $id) {
            $this->login($id);
            $this->postJson($this->url.'/service-form/submit')->assertForbidden();
        }
        $this->assertDatabaseCount('ticket_document_publications', 0);
    }

    public function test_manual_publication_and_save_cannot_bypass_v3(): void
    {
        $this->login(3);
        $this->postJson($this->url.'/documentation', ['note' => 'Bypass'])->assertConflict()->assertJsonPath('code', 'manual_documentation_disabled');
        $this->login(1);
        $this->postJson($this->url.'/documents/service_form/save')->assertConflict()->assertJsonPath('code', 'document_save_disabled');
        $this->assertDatabaseCount('ticket_logs', 0);
        $this->assertDatabaseCount('satisfaction_links', 0);
    }

    public function test_last_signature_and_publication_roll_back_together_if_dispatch_persistence_fails(): void
    {
        $this->prepare();
        $this->postJson($this->url.'/service-form/submit')->assertOk();
        $this->login(2);
        $this->signature('service_form', 'supervisor')->assertCreated();
        $before = Storage::disk('local')->allFiles('ticket-signatures');
        TicketDocumentDispatch::creating(fn () => throw new \RuntimeException('Simulated failure'));
        $this->signature('agreement', 'supervisor')->assertStatus(500);
        $this->assertDatabaseHas('ticket_agreements', ['status' => 'draft', 'saved_at' => null]);
        $this->assertDatabaseCount('ticket_signatures', 5);
        $this->assertDatabaseCount('ticket_document_publications', 0);
        $this->assertDatabaseCount('ticket_logs', 0);
        $this->assertDatabaseCount('satisfaction_links', 0);
        $this->assertSame($before, Storage::disk('local')->allFiles('ticket-signatures'));
        Queue::assertNothingPushed();
    }

    public function test_inline_signature_files_are_cleaned_up_when_submit_is_incomplete(): void
    {
        $this->postJson($this->url.'/service-form/submit', ['signatures' => [['role' => 'technician', 'signature' => self::PNG]]])
            ->assertUnprocessable()->assertJsonPath('code', 'service_form_incomplete');
        $this->assertDatabaseCount('ticket_signatures', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('ticket-signatures'));
    }

    public function test_publication_has_a_database_unique_key_per_cycle(): void
    {
        $cycle = Ticket::first()->currentCycle;
        $row = ['ticket_id' => $cycle->ticket_id, 'cycle_id' => $cycle->id, 'published_at' => now()];
        TicketDocumentPublication::create($row);
        $this->expectException(QueryException::class);
        TicketDocumentPublication::create($row);
    }

    public function test_legacy_submit_is_rejected_and_manual_publication_remains_available(): void
    {
        DB::table('tickets')->update(['workflow_version' => 'legacy']);
        $this->postJson($this->url.'/service-form/submit')->assertConflict()->assertJsonPath('code', 'legacy_document_workflow');
        DB::table('ticket_logs')->insert(['ticket_id' => 1, 'step' => 'repair']);
        $this->login(3);
        $this->postJson($this->url.'/documentation', ['note' => 'Legacy'])->assertCreated();
        $this->assertDatabaseCount('satisfaction_links', 1);
    }

    public function test_each_cycle_can_publish_once_without_republishing_the_previous_cycle(): void
    {
        $this->publishInOrder(['service_form', 'agreement']);
        $ticket = Ticket::first();
        \App\Services\Aftersales\TicketCycles::startRecoming($ticket);
        DB::table('ticket_service_forms')->update(['status' => 'draft', 'saved_at' => null]);
        DB::table('ticket_signatures')->where('signable_type', TicketServiceForm::class)->where('role', 'supervisor')->delete();
        $this->login(1);
        $this->postJson($this->url.'/service-form/submit', $this->findings())->assertOk();
        $this->login(2);
        $this->signature('service_form', 'supervisor')->assertCreated()->assertJsonPath('data.status', 'documentation_published');
        $this->signature('service_form', 'supervisor')->assertCreated();
        $this->assertDatabaseCount('ticket_document_publications', 2);
        $this->assertDatabaseCount('satisfaction_links', 2);
        $this->assertSame(2, DB::table('ticket_logs')->where('step', 'documentation_published')->count());
        $this->assertDatabaseCount('ticket_document_dispatches', 12);
        Queue::assertPushed(SendTicketDocumentEmail::class, 12);
    }

    public function test_migration_roundtrip_preserves_published_history_without_reissuing_links_or_mail(): void
    {
        $this->publishInOrder(['service_form', 'agreement']);
        $migration = require database_path('migrations/2026_10_30_000100_add_document_publication_workflow.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('ticket_document_publications'));
        $this->assertDatabaseCount('ticket_signatures', 6);
        $migration->up();
        $this->assertDatabaseCount('ticket_document_publications', 1);
        $this->assertDatabaseHas('ticket_cycles', ['documentation_status' => 'documentation_published']);
        $this->assertDatabaseCount('satisfaction_links', 1);
        $this->assertDatabaseCount('ticket_document_dispatches', 6);
        Queue::assertPushed(SendTicketDocumentEmail::class, 6);
    }

    public function test_closed_and_guest_requests_cannot_submit_or_sign(): void
    {
        $this->prepare();
        DB::table('ticket_logs')->insert(['ticket_id' => 1, 'step' => 'closed']);
        $this->postJson($this->url.'/service-form/submit')->assertUnprocessable();
        $this->login(2);
        $this->signature('service_form', 'supervisor')->assertUnprocessable();
        auth()->logout();
        $this->postJson($this->url.'/service-form/submit')->assertUnauthorized();
        $this->signature('service_form', 'supervisor')->assertUnauthorized();
        $this->assertDatabaseCount('ticket_document_publications', 0);
    }

    public function test_agreement_must_be_complete_when_spv_signs_and_pending_form_cannot_be_downloaded(): void
    {
        $this->prepare();
        $this->postJson($this->url.'/service-form/submit')->assertOk();
        $this->getJson($this->url.'/documents/service_form/pdf?copy=technician')->assertConflict()
            ->assertJsonPath('code', 'documentation_not_published');
        DB::table('ticket_agreements')->update(['accepted_clauses' => '[]']);
        $this->login(2);
        $this->signature('agreement', 'supervisor')->assertUnprocessable()->assertJsonPath('code', 'document_incomplete');
        $this->getJson($this->url.'/documents')->assertOk()->assertJsonPath('data.documents.agreement.can.sign_supervisor', false);
        $this->assertDatabaseCount('ticket_signatures', 4);
    }

    public function test_ticket_capabilities_follow_submission_and_assigned_spv(): void
    {
        $this->prepare();
        $method = new \ReflectionMethod(\App\Http\Controllers\AfterSales\Dats\TicketController::class, 'capabilities');
        $capabilities = fn () => $method->invoke(new \App\Http\Controllers\AfterSales\Dats\TicketController, auth()->user(), Ticket::first());
        $this->assertTrue($capabilities()['can_send_service_form']);
        $this->postJson($this->url.'/service-form/submit')->assertOk();
        $this->assertFalse($capabilities()['can_send_service_form']);
        $this->assertFalse($capabilities()['can_fill_service_form']);
        $this->login(2);
        $this->assertTrue($capabilities()['can_sign_service_form']);
        $this->assertTrue($capabilities()['can_sign_agreement']);
        $this->login(5);
        $this->assertFalse($capabilities()['can_sign_service_form']);
    }

    public function test_camera_upload_rechecks_form_lock_even_with_a_pre_submission_value(): void
    {
        $this->prepare();
        $value = new \App\Models\Aftersales\TicketInspectionValue(['value' => '1']);
        $value->setRelation('inspection', new \App\Models\Aftersales\TicketInspection(['ticket_service_form_id' => 1]));
        $this->postJson($this->url.'/service-form/submit')->assertOk();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        \App\Services\Aftersales\TicketDocuments::attachPhoto($value, \Illuminate\Http\UploadedFile::fake()->image('photo.jpg'), Ticket::first(), 1);
    }

    public function test_unlock_and_recoming_resubmit_preserve_signatures_form_and_agreement(): void
    {
        $this->publishInOrder(['service_form', 'agreement']);
        $signatures = DB::table('ticket_signatures')->orderBy('id')->get()->toJson();
        $agreement = DB::table('ticket_agreements')->first();
        $files = Storage::disk('local')->allFiles('ticket-signatures');
        $this->login(3);
        $this->postJson($this->url.'/documents/service_form/unlock')->assertOk()->assertJsonPath('data.locked', false);
        $this->assertSame($signatures, DB::table('ticket_signatures')->orderBy('id')->get()->toJson());
        $this->assertSame($files, Storage::disk('local')->allFiles('ticket-signatures'));
        $this->postJson($this->url.'/recoming', ['recoming_type' => 'visit_site', 'reason' => 'unfinished_previous_work', 'planned_visit_at' => now()->addDays(2)->format('Y-m-d H:i:s')] + $this->sla())->assertCreated();
        $this->postJson($this->url.'/documents/agreement/unlock')->assertConflict()->assertJsonPath('code', 'agreement_already_published');
        $this->login(1);
        $this->postJson($this->url.'/service-form/submit', $this->findings())->assertOk()->assertJsonPath('data.documentation_status', 'documentation_published');
        $this->postJson($this->url.'/service-form/submit')->assertOk();
        $this->assertSame($signatures, DB::table('ticket_signatures')->orderBy('id')->get()->toJson());
        $this->assertEquals($agreement, DB::table('ticket_agreements')->first());
        $this->assertDatabaseCount('ticket_service_forms', 1);
        $this->assertDatabaseCount('ticket_agreements', 1);
        $this->assertDatabaseCount('ticket_document_publications', 2);
        $this->assertDatabaseCount('satisfaction_links', 2);
        $this->assertDatabaseHas('tickets', ['assigned_technician_id' => 1]);
        Queue::assertPushed(SendTicketDocumentEmail::class, 12);
    }

    public function test_phone_call_publication_does_not_issue_a_site_survey_link(): void
    {
        $this->publishInOrder(['service_form', 'agreement']);
        $this->login(3);
        $this->postJson($this->url.'/documents/service_form/unlock')->assertOk();
        $this->postJson($this->url.'/recoming', ['recoming_type' => 'phone_call', 'reason' => 'unfinished_previous_work', 'planned_call_at' => now()->addDays(2)->format('Y-m-d H:i:s')] + $this->sla())->assertCreated();
        DB::table('ticket_photos')->delete(); // OQ-06: phone-call completion must not require site photos.
        $this->login(1);
        $this->postJson($this->url.'/service-form/submit', $this->findings())->assertOk()->assertJsonPath('data.documentation_status', 'documentation_published');
        $this->assertDatabaseCount('satisfaction_links', 1);
        $this->assertDatabaseHas('ticket_document_publications', ['cycle_id' => 2, 'satisfaction_link_id' => null]);
        $this->assertDatabaseHas('ticket_cycles', ['id' => 2, 'survey_template_id' => 2]);
        $this->assertDatabaseCount('ticket_visits', 0);
    }

    public function test_initial_pending_unlock_clears_signatures_and_legacy_unlock_remains_unchanged(): void
    {
        $this->prepare();
        $this->postJson($this->url.'/service-form/submit')->assertOk();
        $this->login(3);
        $this->postJson($this->url.'/documents/service_form/unlock')->assertOk();
        $this->assertDatabaseCount('ticket_signatures', 2);
        $this->assertDatabaseHas('ticket_cycles', ['documentation_status' => 'incomplete', 'service_form_submitted_at' => null]);
        $this->assertDatabaseHas('ticket_service_forms', ['status' => 'draft']);
        DB::table('tickets')->update(['workflow_version' => 'legacy']);
        DB::table('ticket_agreements')->update(['status' => 'saved']);
        $this->postJson($this->url.'/documents/agreement/unlock')->assertOk();
        $this->assertDatabaseCount('ticket_signatures', 0);
    }

    public function test_first_finding_edit_enters_repair_once_and_maps_root_cause_to_existing_storage(): void
    {
        $cycleId = $this->beginRecoming();
        $this->getJson($this->url.'/service-form')->assertOk()->assertJsonPath('data.diagnosis.current_findings', []);
        $response = $this->putJson($this->url.'/service-form', $this->findings())->assertOk()
            ->assertJsonPath('data.diagnosis.current_cycle_id', $cycleId)->assertJsonPath('data.can.submit', true);
        $this->assertSame(['problem', 'root_cause', 'action'], array_column($response->json('data.diagnosis.current_findings'), 'field'));
        $this->assertDatabaseHas('ticket_diagnosis_entries', ['cycle_id' => $cycleId, 'field' => 'cause', 'body' => 'New cycle root_cause']);
        $this->assertSame('repair', Ticket::first()->progress);
        $cycle = Ticket::first()->currentCycle;
        $this->assertNotNull($cycle->service_form_first_edited_at);
        $this->assertSame(1, $cycle->service_form_first_edited_by);
        $this->putJson($this->url.'/service-form', $this->findings())->assertOk();
        $this->assertSame(1, DB::table('ticket_logs')->where('cycle_id', $cycleId)->where('step', 'repair')->count());
        $this->assertEquals($cycle->service_form_first_edited_at, $cycle->fresh()->service_form_first_edited_at);
        $this->login(3);
        $this->postJson($this->url.'/recoming/reschedule', ['planned_call_at' => now()->addDays(3)->format('Y-m-d H:i:s')])->assertUnprocessable();
        $this->postJson($this->url.'/on-site')->assertUnprocessable();
    }

    public function test_reads_noop_and_failed_form_updates_do_not_begin_repair(): void
    {
        $cycleId = $this->beginRecoming();
        $this->login(6);
        $this->putJson($this->url.'/service-form', $this->findings())->assertForbidden();
        $this->login(1);
        $this->getJson($this->url.'/service-form')->assertOk();
        $this->putJson($this->url.'/service-form', [])->assertOk();
        $this->putJson($this->url.'/service-form', ['service_type' => 'repair'])->assertOk();
        $this->putJson($this->url.'/service-form', $this->findings() + ['inspections' => [['item_number' => 999, 'status' => 'ok']]])->assertUnprocessable();
        $this->assertSame('recoming', Ticket::first()->progress);
        $this->assertNull(Ticket::first()->currentCycle->service_form_first_edited_at);
        $this->assertSame(0, DB::table('ticket_logs')->where('cycle_id', $cycleId)->where('step', 'repair')->count());
        $this->assertSame(0, DB::table('ticket_diagnosis_entries')->where('cycle_id', $cycleId)->count());
    }

    public function test_old_or_foreign_findings_cannot_be_edited_and_alias_duplicates_are_rejected(): void
    {
        $cycleId = $this->beginRecoming();
        $old = DB::table('ticket_diagnosis_entries')->where('field', 'cause')->first();
        $this->putJson($this->url.'/service-form', ['diagnosis' => [['id' => $old->id, 'field' => 'root_cause', 'body' => 'Overwrite']]])
            ->assertConflict()->assertJsonPath('code', 'finding_not_current');
        $other = Ticket::create(['ticket_code' => 'OTHER']);
        $form = TicketServiceForm::create(['ticket_id' => $other->id]);
        $foreign = DB::table('ticket_diagnosis_entries')->insertGetId(['ticket_service_form_id' => $form->id, 'cycle_id' => $other->currentCycle->id, 'field' => 'problem', 'body' => 'Foreign']);
        $this->putJson($this->url.'/service-form', ['diagnosis' => [['id' => $foreign, 'field' => 'problem', 'body' => 'Overwrite']]])->assertNotFound();
        $this->putJson($this->url.'/service-form', ['diagnosis' => [['field' => 'cause', 'body' => 'A'], ['field' => 'root_cause', 'body' => 'B']]])
            ->assertUnprocessable()->assertJsonValidationErrors('diagnosis.0.field');
        $this->assertDatabaseHas('ticket_diagnosis_entries', ['id' => $old->id, 'body' => 'Real cause']);
        $this->assertSame(0, DB::table('ticket_diagnosis_entries')->where('cycle_id', $cycleId)->count());
        $this->assertSame('recoming', Ticket::first()->progress);
    }

    public function test_old_findings_do_not_complete_a_new_cycle_and_submit_rollback_removes_repair_marker(): void
    {
        $cycleId = $this->beginRecoming();
        $this->postJson($this->url.'/service-form/submit', ['service_type' => 'preventive'])->assertUnprocessable()->assertJsonPath('code', 'service_form_incomplete');
        $this->assertDatabaseHas('tickets', ['id' => 1, 'service_type' => 'repair']);
        $this->assertNull(Ticket::first()->currentCycle->service_form_first_edited_at);
        $this->assertSame('recoming', Ticket::first()->progress);
        $this->assertSame(0, DB::table('ticket_logs')->where('cycle_id', $cycleId)->where('step', 'repair')->count());
        $this->postJson($this->url.'/service-form/submit', $this->findings())->assertOk()->assertJsonPath('data.documentation_status', 'documentation_published');
        $this->assertSame(1, DB::table('ticket_logs')->where('cycle_id', $cycleId)->where('step', 'repair')->count());
        $this->assertSame(3, DB::table('ticket_diagnosis_entries')->where('cycle_id', $cycleId)->whereNotNull('locked_at')->count());
    }

    public function test_consecutive_phone_cycles_have_distinct_findings_without_physical_visits(): void
    {
        $second = $this->beginRecoming();
        $this->postJson($this->url.'/service-form/submit', $this->findings())->assertOk();
        $this->login(3);
        $this->postJson($this->url.'/documents/service_form/unlock')->assertOk();
        $third = $this->postJson($this->url.'/recoming', ['recoming_type' => 'phone_call', 'reason' => 'unfinished_previous_work', 'planned_call_at' => now()->addDays(2)->format('Y-m-d H:i:s')] + $this->sla())
            ->assertCreated()->json('data.data.cycle_id');
        $this->login(1);
        $this->putJson($this->url.'/service-form', $this->findings())->assertOk();
        $this->assertNotSame($second, $third);
        $this->assertSame(3, DB::table('ticket_diagnosis_entries')->where('cycle_id', $second)->count());
        $this->assertSame(3, DB::table('ticket_diagnosis_entries')->where('cycle_id', $third)->count());
        $this->assertDatabaseCount('ticket_visits', 0);
        $this->assertSame(1, DB::table('ticket_logs')->where('cycle_id', $third)->where('step', 'repair')->count());
    }

    public function test_scheduler_waits_for_v3_edit_and_still_resumes_legacy_once(): void
    {
        $cycleId = $this->beginRecoming('visit_site');
        $legacy = Ticket::create(['ticket_code' => 'LEGACY', 'workflow_version' => 'legacy', 'assigned_technician_id' => 6]);
        $legacy->visits()->create(['scheduled_at' => now()->subDay()]);
        $legacy->logs()->create(['step' => 'recoming']);
        DB::table('ticket_visits')->where('ticket_id', 1)->update(['scheduled_at' => now()->subDay()]);
        $this->artisan('tickets:resume-recoming')->assertSuccessful();
        $this->artisan('tickets:resume-recoming')->assertSuccessful();
        $this->assertSame('recoming', Ticket::find(1)->progress);
        $this->assertNull(Ticket::find(1)->currentCycle->service_form_first_edited_at);
        $this->assertSame(0, DB::table('ticket_logs')->where('cycle_id', $cycleId)->where('step', 'on_site')->count());
        $this->assertSame(1, $legacy->logs()->where('step', 'on_site')->count());
    }

    public function test_diagnosis_photo_alias_is_an_edit_and_records_repair(): void
    {
        $cycleId = $this->beginRecoming();
        $this->post($this->url.'/service-form/diagnosis/photos', ['field' => 'root_cause', 'photo' => \Illuminate\Http\UploadedFile::fake()->image('cause.jpg')], ['Accept' => 'application/json'])
            ->assertCreated();
        $this->assertSame('repair', Ticket::first()->progress);
        $this->assertSame(2, DB::table('ticket_photos')->where('diagnosis_field', 'cause')->count());
        $this->assertSame(1, DB::table('ticket_logs')->where('cycle_id', $cycleId)->where('step', 'repair')->count());
    }

    public function test_recoming_finding_dates_use_the_cycle_visit_instead_of_other_visit_history(): void
    {
        $cycleId = $this->beginRecoming('visit_site');
        $actual = now()->subDay();
        DB::table('ticket_visits')->where('cycle_id', $cycleId)->update(['actual_start' => $actual]);
        Ticket::first()->visits()->create(['cycle_id' => 1, 'scheduled_at' => now()->addDays(10)]);
        $this->putJson($this->url.'/service-form', $this->findings())->assertOk()
            ->assertJsonPath('data.diagnosis.current_findings.0.entry_date', $actual->toDateString())
            ->assertJsonPath('data.diagnosis.fields.0.visit_date', $actual->toDateString());
    }
}
