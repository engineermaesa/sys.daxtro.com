<?php

namespace Tests\Feature\Aftersales;

use App\Models\Aftersales\RefInspectionTemplate;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketServiceForm;
use App\Models\User;
use App\Services\Aftersales\InspectionTemplates;
use App\Services\Aftersales\ServiceDiagnosis;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ServiceDiagnosisTest extends TestCase
{
    private Ticket $ticket;

    private TicketServiceForm $form;

    private User $technician;

    private ServiceDiagnosis $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Schema::create('users', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->softDeletes()]);
        Schema::create('ref_inspection_templates', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('diagnosis_section_title');
            $t->json('diagnosis_fields');
            $t->timestamps();
        });
        Schema::create('ticket_service_forms', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('ticket_id');
            $t->unsignedInteger('ref_inspection_template_id');
            $t->string('status')->default('draft');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_visits', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('ticket_id');
            $t->timestamp('scheduled_at');
            $t->timestamp('actual_start')->nullable();
            $t->softDeletes();
        });
        Schema::create('ticket_photos', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('ticket_id');
            $t->string('category');
            $t->integer('sequence');
            $t->string('file_path');
            $t->string('file_name')->nullable();
            $t->timestamp('captured_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Technician']);
        (require database_path('migrations/2026_10_19_000000_add_service_form_diagnosis.php'))->up();
        $template = RefInspectionTemplate::create(['title' => 'Service Inspection & Report', 'diagnosis_section_title' => 'Custom diagnosis title', 'diagnosis_fields' => InspectionTemplates::DIAGNOSIS]);
        $this->form = TicketServiceForm::create(['ticket_id' => 1, 'ref_inspection_template_id' => $template->id]);
        $this->ticket = new Ticket;
        $this->ticket->forceFill(['id' => 1, 'assigned_technician_id' => 1]);
        $this->ticket->setRelation('logs', collect());
        $this->ticket->setRelation('visits', collect());
        $this->technician = Mockery::mock(User::class)->makePartial();
        $this->technician->forceFill(['id' => 1]);
        $this->technician->shouldReceive('hasPermission')->with('aftersales.documents.fill')->andReturn(true);
        $this->service = app(ServiceDiagnosis::class);
    }

    private function notes(): array
    {
        return array_map(fn ($field) => ['field' => $field, 'name' => 'Finding', 'body' => 'Detailed '.$field, 'visit_notes' => 'Visit note'], ServiceDiagnosis::FIELDS);
    }

    public function test_diagnosis_is_saved_with_template_labels_and_automatic_visit_metadata(): void
    {
        $this->service->save($this->ticket, $this->form, $this->notes(), $this->technician);
        $result = $this->service->structure($this->ticket, $this->form);
        $this->assertSame('Custom diagnosis title', $result['title']);
        $this->assertSame(['problem', 'cause', 'action'], array_column($result['fields'], 'key'));
        $this->assertSame('Detailed problem', $result['fields'][0]['entries'][0]['body']);
        $this->assertSame(1, $result['fields'][0]['entries'][0]['visit_no']);
        $this->assertCount(3, $this->service->missing($this->form));
    }

    public function test_photo_upload_is_compressed_and_limited_to_five_per_field(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $photo = $this->service->upload($this->ticket, $this->form, 'problem', UploadedFile::fake()->image('proof.png', 2000, 1000), 'Caption', $this->technician);
        }
        $size = getimagesizefromstring(Storage::disk('local')->get($photo->file_path));
        $this->assertSame(1600, $size[0]);
        $this->assertSame('image/jpeg', $size['mime']);
        try {
            $this->service->upload($this->ticket, $this->form, 'problem', UploadedFile::fake()->image('sixth.jpg'), null, $this->technician);
            $this->fail('A sixth photo must fail');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
        $this->assertCount(5, Storage::disk('local')->allFiles('diagnosis'));
    }

    public function test_completion_requires_text_and_photos_for_all_three_fields(): void
    {
        $this->assertCount(6, $this->service->missing($this->form));
        $this->service->save($this->ticket, $this->form, $this->notes(), $this->technician);
        foreach (ServiceDiagnosis::FIELDS as $field) {
            $this->service->upload($this->ticket, $this->form, $field, UploadedFile::fake()->image('proof.jpg'), null, $this->technician);
        }
        $this->assertSame([], $this->service->missing($this->form));
    }

    public function test_locked_notes_remain_immutable_after_unlock_and_new_notes_append(): void
    {
        $this->service->save($this->ticket, $this->form, $this->notes(), $this->technician);
        $this->service->lock($this->form);
        $old = $this->form->diagnosisEntries()->first();
        try {
            $this->service->save($this->ticket, $this->form, [['id' => $old->id, 'field' => 'problem', 'body' => 'Changed']], $this->technician);
            $this->fail('Saved notes must be immutable');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
        $this->service->save($this->ticket, $this->form, [['field' => 'problem', 'body' => 'Follow-up finding']], $this->technician);
        $this->assertSame('Detailed problem', $old->fresh()->body);
        $this->assertSame(2, $this->form->diagnosisEntries()->whereNull('locked_at')->first()->visit_no);
    }

    public function test_caption_delete_and_saved_photo_protection(): void
    {
        $photo = $this->service->upload($this->ticket, $this->form, 'action', UploadedFile::fake()->image('proof.jpg'), null, $this->technician);
        $this->service->changePhoto($this->ticket, $this->form, $photo, $this->technician, 'Repair completed');
        $this->assertSame('Repair completed', $photo->fresh()->caption);
        $this->service->lock($this->form);
        try {
            $this->service->changePhoto($this->ticket, $this->form, $photo, $this->technician, null, true);
            $this->fail('Saved photo must be protected');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
        $this->assertSame(1, $this->form->diagnosisPhotos()->count());
    }

    public function test_manager_and_saved_forms_cannot_edit_diagnosis(): void
    {
        $admin = Mockery::mock(User::class)->makePartial();
        $admin->forceFill(['id' => 2]);
        try {
            $this->service->save($this->ticket, $this->form, $this->notes(), $admin);
            $this->fail('Only assigned technician may edit');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->form->update(['status' => 'saved']);
        try {
            $this->service->save($this->ticket, $this->form, $this->notes(), $this->technician);
            $this->fail('Saved form may not change');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    public function test_diagnosis_evidence_can_be_viewed_through_the_authorized_photo_endpoint(): void
    {
        $photo = $this->service->upload($this->ticket, $this->form, 'problem', UploadedFile::fake()->image('proof.jpg'), null, $this->technician);
      
        $photo->setRawAttributes(array_replace($photo->getAttributes(), ['ticket_id' => (string) $this->ticket->id]));
        $viewer = Mockery::mock(User::class)->makePartial();
        $viewer->forceFill(['id' => 1]);
        $viewer->shouldReceive('hasPermission')->andReturn(true);
        $request = \Illuminate\Http\Request::create('/');
        $request->setUserResolver(fn () => $viewer);
        $response = app(\App\Http\Controllers\AfterSales\Dats\ServiceFormController::class)->photo($request, $this->ticket, $photo);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/jpeg', $response->headers->get('Content-Type'));
        ob_start();
        try {
            $response->sendContent();
            $bytes = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $this->assertSame(Storage::disk('local')->get($photo->file_path), $bytes);
    }

    public function test_diagnosis_evidence_from_another_ticket_is_not_served(): void
    {
        $photo = $this->service->upload($this->ticket, $this->form, 'problem', UploadedFile::fake()->image('proof.jpg'), null, $this->technician);
        $photo->setRawAttributes(array_replace($photo->getAttributes(), ['ticket_id' => '2']));
        $viewer = Mockery::mock(User::class)->makePartial();
        $viewer->shouldReceive('hasPermission')->andReturn(true);
        $request = \Illuminate\Http\Request::create('/');
        $request->setUserResolver(fn () => $viewer);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(0);
        try {
            app(\App\Http\Controllers\AfterSales\Dats\ServiceFormController::class)->photo($request, $this->ticket, $photo);
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
            throw $exception;
        }
    }

    public function test_pdf_renders_diagnosis_entries_using_the_pinned_template(): void
    {
        $this->service->save($this->ticket, $this->form, $this->notes(), $this->technician);
        $diagnosis = $this->service->structure($this->ticket, $this->form);
        foreach (['customerProduct', 'customer', 'technician'] as $relation) {
            $this->ticket->setRelation($relation, null);
        }
        $html = view('pdfs.aftersales.service_form', ['doc' => $this->form, 'ticket' => $this->ticket, 'docNumber' => 'TEST-1', 'groups' => [], 'photos' => [], 'diagnosis' => $diagnosis, 'signatures' => [], 'copyLabel' => 'Test'])->render();
        $this->assertStringContainsString('Custom diagnosis title', $html);
        $this->assertStringContainsString('Detailed problem', $html);
        $this->assertStringContainsString('Detailed cause', $html);
        $this->assertStringContainsString('Detailed action', $html);
    }
}
