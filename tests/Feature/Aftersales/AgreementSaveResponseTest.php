<?php

namespace Tests\Feature\Aftersales;

use App\Jobs\SendTicketDocumentEmail;
use App\Models\Aftersales\TicketAgreement;
use App\Models\Aftersales\TicketDocumentDispatch;
use App\Models\Aftersales\TicketLog;
use App\Models\Aftersales\TicketServiceForm;
use App\Models\Aftersales\TicketSignature;
use App\Models\User;
use App\Services\Aftersales\ServiceDiagnosis;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AgreementSaveResponseTest extends TestCase
{
    private const URL = '/api/aftersales/tickets/1/documents/agreement/save';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locked' => false, 'app.debug' => false]);
        Queue::fake();
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('email')->nullable();
            $t->softDeletes();
        });
        Schema::create('service_customers', function (Blueprint $t) {
            $t->id();
            $t->string('email')->nullable();
            $t->softDeletes();
        });
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->string('ticket_code');
            foreach (['assigned_technician_id', 'supervisor_id', 'created_by', 'customer_id', 'customer_product_id'] as $key) {
                $t->integer($key)->nullable();
            }
            $t->boolean('happy_call_required')->default(false);
            $t->string('service_type')->nullable();
            $t->softDeletes();
        });
        Schema::create('ref_agreement_templates', function (Blueprint $t) {
            $t->id();
            $t->json('sections');
            $t->softDeletes();
        });
        foreach (['ticket_agreements' => 'ref_agreement_template_id', 'ticket_service_forms' => 'ref_inspection_template_id'] as $table => $templateKey) {
            Schema::create($table, function (Blueprint $t) use ($templateKey) {
                $t->id();
                $t->integer('ticket_id');
                $t->integer($templateKey)->default(1);
                $t->integer('template_version')->default(1);
                $t->string('status')->default('draft');
                $t->string('customer_name')->nullable();
                $t->json('accepted_clauses')->nullable();
                $t->timestamp('saved_at')->nullable();
                $t->integer('saved_by_user_id')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
        }
        foreach (['ticket_work_orders', 'ticket_visits'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->integer('ticket_id');
                $t->timestamp('scheduled_at')->nullable();
                $t->softDeletes();
            });
        }
        Schema::create('ticket_logs', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->string('step');
            $t->integer('actor_user_id')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_signatures', function (Blueprint $t) {
            $t->id();
            $t->morphs('signable');
            $t->string('role');
            $t->string('signer_name')->nullable();
            $t->timestamp('signed_at')->nullable();
        });
        Schema::create('ticket_document_dispatches', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            foreach (['document_type', 'recipient_role', 'recipient_email', 'copy_label', 'status', 'error'] as $key) {
                $t->string($key)->nullable();
            }
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
        });
        DB::table('users')->insert([['id' => 1, 'email' => 'tech@example.com'], ['id' => 2, 'email' => 'office@example.com']]);
        DB::table('service_customers')->insert(['id' => 1, 'email' => 'customer@example.com']);
        DB::table('tickets')->insert(['id' => 1, 'ticket_code' => 'DAX-AF-000123', 'assigned_technician_id' => 1, 'supervisor_id' => 2, 'created_by' => 2, 'customer_id' => 1]);
        DB::table('ref_agreement_templates')->insert(['id' => 1, 'sections' => json_encode([['title' => 'Terms', 'clauses' => [['key' => 'clause_a', 'text' => 'Agreement terms']]]])]);
        DB::table('ticket_agreements')->insert(['id' => 1, 'ticket_id' => 1, 'accepted_clauses' => '["clause_a"]', 'customer_name' => 'Customer Representative']);
        DB::table('ticket_service_forms')->insert(['id' => 1, 'ticket_id' => 1]);
        DB::table('ticket_work_orders')->insert(['ticket_id' => 1]);
        $this->signatures(TicketAgreement::class);
        $this->login();
    }

    private function login(int $id = 1, array $permissions = ['aftersales.documents.fill']): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => $id]);
        $user->shouldReceive('hasPermission')->andReturnUsing(fn ($permission) => in_array($permission, $permissions, true));
        $this->actingAs($user);
    }

    private function signatures(string $model): void
    {
        foreach (TicketSignature::ROLES as $role) {
            DB::table('ticket_signatures')->insert(['signable_type' => $model, 'signable_id' => 1, 'role' => $role]);
        }
    }

    public function test_success_contains_modal_feedback_ticket_identity_and_saved_state(): void
    {
        $response = $this->postJson(self::URL)->assertOk()
            ->assertJsonPath('status', 'success')->assertJsonPath('title', 'Saved!')
            ->assertJsonPath('message', 'Service Agreement for ticket DAX-AF-000123 has been saved successfully.')
            ->assertJsonPath('data.ticket_id', 1)->assertJsonPath('data.ticket_code', 'DAX-AF-000123')
            ->assertJsonPath('data.type', 'agreement')->assertJsonPath('data.status', 'saved')
            ->assertJsonPath('data.status_label', 'Saved')->assertJsonPath('data.locked', true)
            ->assertJsonPath('data.can.fill', false)->assertJsonPath('data.completeness.complete', true)
            ->assertJsonPath('data.documentation_published', false)->assertJsonPath('data.warnings', []);
        $this->assertNotNull($response->json('data.saved_at'));
        $this->assertDatabaseHas('ticket_agreements', ['id' => 1, 'status' => 'saved', 'saved_by_user_id' => 1, 'accepted_clauses' => '["clause_a"]']);
        $this->assertDatabaseCount('ticket_document_dispatches', 3);
        Queue::assertPushed(SendTicketDocumentEmail::class, 3);
    }

    public function test_missing_email_is_a_success_warning_not_a_save_failure(): void
    {
        DB::table('service_customers')->where('id', 1)->update(['email' => null]);
        $this->postJson(self::URL)->assertOk()->assertJsonPath('title', 'Saved!')
            ->assertJsonPath('data.status', 'saved')->assertJsonPath('data.warnings.0', 'Customer has no email address; email skipped.');
        $this->assertDatabaseHas('ticket_document_dispatches', ['recipient_role' => 'customer', 'status' => 'skipped']);
        Queue::assertPushed(SendTicketDocumentEmail::class, 2);
    }

    public function test_saving_second_document_preserves_documentation_publication_and_survey_link(): void
    {
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        Schema::create('ref_survey_templates', function (Blueprint $t) {
            $t->id();
            $t->string('status');
        });
        Schema::create('satisfaction_links', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_id');
            $t->integer('ref_survey_template_id');
            $t->string('token_hash');
            $t->text('token_encrypted');
            $t->string('status');
            $t->timestamp('expires_at');
            $t->integer('created_by_user_id');
            $t->timestamps();
            $t->softDeletes();
        });
        DB::table('ref_survey_templates')->insert(['id' => 1, 'status' => 'active']);
        DB::table('ticket_service_forms')->where('id', 1)->update(['status' => 'saved']);
        $this->postJson(self::URL)->assertOk()->assertJsonPath('title', 'Saved!')->assertJsonPath('data.documentation_published', true);
        $this->assertDatabaseHas('ticket_logs', ['ticket_id' => 1, 'step' => TicketLog::STEP_DOCUMENTATION_PUBLISHED]);
        $this->assertDatabaseHas('satisfaction_links', ['ticket_id' => 1, 'status' => 'active']);
    }

    public function test_guest_cannot_save(): void
    {
        auth()->logout();
        $this->postJson(self::URL)->assertUnauthorized()->assertJsonMissingPath('title');
        $this->assertDatabaseHas('ticket_agreements', ['status' => 'draft']);
        Queue::assertNothingPushed();
    }

    public function test_incomplete_agreement_returns_existing_error_without_success_feedback(): void
    {
        DB::table('ticket_agreements')->where('id', 1)->update(['accepted_clauses' => '[]']);
        DB::table('ticket_signatures')->where('role', 'customer')->delete();
        $response = $this->postJson(self::URL)->assertUnprocessable();
        $this->assertStringContainsString('Cannot save yet:', $response->json('message'));
        $this->assertArrayNotHasKey('title', $response->json());
        $this->assertArrayNotHasKey('data', $response->json());
        $this->assertDatabaseHas('ticket_agreements', ['id' => 1, 'status' => 'draft', 'saved_at' => null]);
        $this->assertDatabaseCount('ticket_document_dispatches', 0);
        Queue::assertNothingPushed();
    }

    public function test_repeated_save_is_rejected_without_duplicate_dispatches(): void
    {
        $this->postJson(self::URL)->assertOk();
        $this->postJson(self::URL)->assertUnprocessable()->assertJsonPath('message', 'This document is already saved.')->assertJsonMissingPath('title');
        $this->assertDatabaseCount('ticket_document_dispatches', 3);
        Queue::assertPushed(SendTicketDocumentEmail::class, 3);
    }

    public function test_unassigned_and_view_only_users_cannot_save(): void
    {
        foreach ([[3, ['aftersales.documents.fill']], [1, ['aftersales.tickets.view-own']], [3, ['aftersales.tickets.manage']]] as [$id, $permissions]) {
            $this->login($id, $permissions);
            $this->postJson(self::URL)->assertForbidden()->assertJsonMissingPath('title');
        }
        $this->assertDatabaseHas('ticket_agreements', ['status' => 'draft']);
        Queue::assertNothingPushed();
    }

    public function test_assigned_supervisor_can_save_and_receive_dispatch_summary(): void
    {
        $this->login(2, ['aftersales.tickets.manage']);
        $this->postJson(self::URL)->assertOk()->assertJsonPath('title', 'Saved!')->assertJsonCount(3, 'data.dispatches');
        $this->assertDatabaseHas('ticket_agreements', ['saved_by_user_id' => 2]);
    }

    public function test_closed_and_recoming_tickets_do_not_return_success(): void
    {
        foreach ([TicketLog::STEP_CLOSED => 'This ticket is already closed.', TicketLog::STEP_RECOMING => 'The ticket is in Recoming until the follow-up visit.'] as $step => $message) {
            DB::table('ticket_logs')->insert(['ticket_id' => 1, 'step' => $step]);
            $this->postJson(self::URL)->assertUnprocessable()->assertJsonPath('message', $message)->assertJsonMissingPath('title');
        }
        $this->assertDatabaseHas('ticket_agreements', ['status' => 'draft']);
        Queue::assertNothingPushed();
    }

    public function test_storage_failure_rolls_back_save_and_does_not_return_success(): void
    {
        TicketDocumentDispatch::creating(fn () => throw new \RuntimeException('Simulated persistence failure'));
        try {
            $this->postJson(self::URL)->assertStatus(500)->assertJsonMissingPath('title');
        } finally {
            TicketDocumentDispatch::flushEventListeners();
        }
        $this->assertDatabaseHas('ticket_agreements', ['status' => 'draft', 'saved_at' => null]);
        $this->assertDatabaseCount('ticket_document_dispatches', 0);
        Queue::assertNothingPushed();
    }

    public function test_unknown_document_type_and_missing_ticket_return_not_found(): void
    {
        $this->postJson('/api/aftersales/tickets/1/documents/unknown/save')->assertNotFound()->assertJsonMissingPath('title');
        $this->postJson('/api/aftersales/tickets/999/documents/agreement/save')->assertNotFound()->assertJsonMissingPath('title');
        Queue::assertNothingPushed();
    }

    public function test_service_form_keeps_existing_success_response(): void
    {
        // Isolate diagnosis validation: this test checks the shared controller response contract.
        $this->mock(ServiceDiagnosis::class, function ($mock) {
            $mock->shouldReceive('missing')->andReturn([]);
            $mock->shouldReceive('lock')->once();
        });
        Schema::create('ref_inspection_templates', fn (Blueprint $t) => $t->id());
        DB::table('ref_inspection_templates')->insert(['id' => 1]);
        Schema::create('ref_inspection_items', function (Blueprint $t) {
            $t->id();
            $t->integer('ref_inspection_template_id');
            $t->integer('number');
            $t->softDeletes();
        });
        Schema::create('ticket_inspections', function (Blueprint $t) {
            $t->id();
            $t->integer('ticket_service_form_id');
        });
        Schema::create('service_customer_products', function (Blueprint $t) {
            $t->id();
            foreach (['capacity', 'refrigerant', 'condenser_type', 'voltage', 'phase', 'hz'] as $key) {
                $t->string($key)->default('1');
            }
            $t->softDeletes();
        });
        DB::table('service_customer_products')->insert(['id' => 1]);
        DB::table('tickets')->where('id', 1)->update(['service_type' => 'repair', 'customer_product_id' => 1]);
        $this->signatures(TicketServiceForm::class);
        $this->postJson('/api/aftersales/tickets/1/documents/service_form/save')->assertOk()
            ->assertJsonPath('status', 'success')->assertJsonPath('message', 'Document saved and locked')
            ->assertJsonPath('data.type', 'service_form')->assertJsonPath('data.status', 'saved')
            ->assertJsonMissingPath('title')->assertJsonMissingPath('data.status_label')->assertJsonMissingPath('data.ticket_code');
    }
}
