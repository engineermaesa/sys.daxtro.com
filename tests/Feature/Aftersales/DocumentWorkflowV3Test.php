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
                $t->timestamps();
                $t->softDeletes();
            });
        }
        Schema::create('ticket_diagnosis_entries', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_service_form_id');
            $t->string('field');
            $t->text('body');
            $t->timestamp('locked_at')->nullable();
            $t->timestamps();
        });
        Schema::create('ticket_photos', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_service_form_id');
            $t->string('category');
            $t->string('diagnosis_field');
            $t->integer('sequence')->default(1);
            $t->timestamp('locked_at')->nullable();
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
        (require database_path('migrations/2026_10_30_000100_add_document_publication_workflow.php'))->up();
        foreach ([1 => 'Technician', 2 => 'SPV', 3 => 'Admin', 4 => 'Head', 5 => 'Other SPV', 6 => 'Other technician'] as $id => $name) {
            DB::table('users')->insert(['id' => $id, 'name' => $name, 'email' => 'user'.$id.'@example.com']);
        }
        DB::table('service_customers')->insert(['id' => 1, 'email' => 'customer@example.com']);
        DB::table('service_customer_products')->insert(['id' => 1]);
        DB::table('ref_inspection_templates')->insert(['id' => 1]);
        DB::table('ref_agreement_templates')->insert(['id' => 1, 'sections' => json_encode([['title' => 'Terms', 'clauses' => [['key' => 'A1', 'text' => 'Terms']]]])]);
        DB::table('ref_survey_templates')->insert(['id' => 1, 'status' => 'active']);
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
        $this->postJson($this->url.'/service-form/submit')->assertOk();
        $this->login(2);
        $this->signature('service_form', 'supervisor')->assertCreated()->assertJsonPath('data.status', 'documentation_published');
        $this->signature('service_form', 'supervisor')->assertCreated();
        $this->assertDatabaseCount('ticket_document_publications', 2);
        $this->assertDatabaseCount('satisfaction_links', 2);
        $this->assertDatabaseCount('ticket_logs', 2);
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
}
