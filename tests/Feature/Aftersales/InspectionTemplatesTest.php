<?php

namespace Tests\Feature\Aftersales;

use App\Models\Aftersales\RefInspectionField;
use App\Models\Aftersales\RefInspectionTemplate;
use App\Models\Aftersales\ServiceCustomerProduct;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketInspectionValue;
use App\Models\Aftersales\TicketServiceForm;
use App\Models\User;
use App\Services\Aftersales\TicketDocuments;
use Database\Seeders\InspectionItemSeeder;
use Database\Seeders\InspectionVersionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InspectionTemplatesTest extends TestCase
{
    private string $api = '/api/aftersales/inspection-templates';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locked' => false]);
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->softDeletes();
        });
        Schema::create('ref_product_types', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('ref_inspection_items', function (Blueprint $t) {
            $t->id();
            $t->char('group_code', 1);
            $t->unsignedTinyInteger('number')->unique();
            $t->string('name');
            $t->string('standard')->nullable();
            $t->json('only_for_types')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ref_inspection_fields', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->constrained('ref_inspection_items');
            $t->string('key', 40);
            $t->string('label');
            $t->string('type');
            $t->string('unit', 20)->nullable();
            $t->json('options')->nullable();
            $t->string('required_when')->nullable();
            $t->unsignedTinyInteger('sort')->default(0);
            $t->timestamps();
            $t->unique(['item_id', 'key']);
        });
        Schema::create('ticket_service_forms', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('ticket_id')->unique();
            $t->string('status')->default('draft');
            $t->timestamp('first_draft_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_inspections', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('ticket_service_form_id');
            $t->unsignedInteger('ref_inspection_item_id');
            $t->string('status')->nullable();
            $t->timestamps();
            $t->unique(['ticket_service_form_id', 'ref_inspection_item_id']);
        });
        Schema::create('ticket_inspection_values', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('ticket_inspection_id');
            $t->unsignedInteger('ref_inspection_field_id');
            $t->text('value')->nullable();
            $t->timestamps();
            $t->unique(['ticket_inspection_id', 'ref_inspection_field_id']);
        });
        Schema::create('ticket_photos', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('ticket_inspection_value_id')->nullable();
            $t->string('file_path')->nullable();
            $t->string('thumbnail_path')->nullable();
            $t->timestamp('captured_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Aftersales Admin']);
        DB::table('ref_product_types')->insert([['id' => 1, 'name' => 'Tube Ice'], ['id' => 2, 'name' => 'Flake Ice']]);
        $this->seed(InspectionItemSeeder::class);
        TicketServiceForm::create(['ticket_id' => 1, 'first_draft_at' => now()]);
        DB::table('ticket_inspections')->insert(['id' => 1, 'ticket_service_form_id' => 1, 'ref_inspection_item_id' => 1, 'status' => 'ok']);
        DB::table('ticket_inspection_values')->insert(['ticket_inspection_id' => 1, 'ref_inspection_field_id' => 1, 'value' => '220']);
        (require database_path('migrations/2026_10_17_000000_version_inspection_templates.php'))->up();
        $this->manager();
    }

    private function manager(bool $allowed = true): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill(['id' => 1, 'name' => 'Aftersales Admin']);
        $user->shouldReceive('hasPermission')->with('aftersales.tickets.manage')->andReturn($allowed);
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

        return $this->postJson($url.'/activate', ['lock_version' => $draft['lock_version'], 'preview_token' => $token, 'confirmed' => true]);
    }

    private function ticket(int $type = 1): Ticket
    {
        $ticket = new Ticket;
        $ticket->forceFill(['id' => 1]);
        $ticket->setRelation('customerProduct', new ServiceCustomerProduct(['ref_product_type_id' => $type]));
        $ticket->setRelation('logs', collect());
        $ticket->setRelation('visits', collect());

        return $ticket;
    }

    public function test_migration_preserves_legacy_values_and_pins_forms_to_v1(): void
    {
        $template = RefInspectionTemplate::active();
        $form = TicketServiceForm::first();
        $this->assertSame(1, $form->template_version);
        $this->assertSame($template->id, $form->ref_inspection_template_id);
        $this->assertCount(5, $template->groups);
        $this->assertSame(34, $template->items()->count());
        $this->assertSame('rs', RefInspectionField::find(1)->key);
        $this->assertSame('legacy_field_1', RefInspectionField::find(1)->field_key);
        $this->assertSame('220', TicketInspectionValue::first()->value);
        $groups = TicketDocuments::formStructure($this->ticket(), $form);
        $this->assertSame('220', $groups[0]['items'][0]['fields'][0]['value']);
    }

    public function test_single_draft_is_a_full_copy_and_preserves_keys(): void
    {
        $draft = $this->draft();
        $again = $this->draft();
        $this->assertSame($draft['id'], $again['id']);
        $this->assertSame(2, $draft['version']);
        $this->assertSame(RefInspectionTemplate::active()->groups, $draft['groups']);
        $this->assertSame(1, DB::table('inspection_template_audits')->count());
    }

    public function test_save_generates_new_keys_and_rejects_key_changes_deletion_and_stale_saves(): void
    {
        $draft = $this->draft();
        $new = $draft['groups'][0]['items'][0]['fields'][0];
        $new['field_key'] = null;
        $new['label'] = 'New reading';
        $draft['groups'][0]['items'][0]['fields'][] = $new;
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->assertStringStartsWith('f_', $saved['groups'][0]['items'][0]['fields'][3]['field_key']);
        $this->assertSame('legacy_field_1', $saved['groups'][0]['items'][0]['fields'][0]['field_key']);
        $this->save($draft)->assertStatus(409);
        $bad = $saved;
        array_pop($bad['groups'][0]['items'][0]['fields']);
        $this->save($bad)->assertUnprocessable();
        $bad = $saved;
        $bad['groups'][0]['group_key'] = 'forged-key';
        $this->save($bad)->assertUnprocessable();
        $this->assertCount(3, RefInspectionTemplate::active()->groups[0]['items'][0]['fields']);
    }

    public function test_disable_restore_and_reorder_are_version_specific(): void
    {
        $draft = $this->draft();
        $draft['groups'][0]['items'][0]['is_active'] = false;
        $draft['groups'] = array_reverse($draft['groups']);
        $saved = $this->save($draft)->assertOk()->json('data');
        $groups = RefInspectionTemplate::find($saved['id'])->documentGroups();
        $this->assertSame('A', $groups[0]['code']);
        $this->assertSame(1, $groups[0]['items'][0]['number']);
        $this->assertSame(33, collect($groups)->sum(fn ($g) => count($g['items'])));
        $saved['groups'][4]['items'][0]['is_active'] = true;
        $restored = $this->save($saved)->assertOk()->json('data');
        $this->assertSame(34, collect(RefInspectionTemplate::find($restored['id'])->documentGroups())->sum(fn ($g) => count($g['items'])));
    }

    public function test_activation_validates_diagnosis_fields_and_active_content(): void
    {
        $draft = $this->draft();
        $draft['diagnosis_fields']['cause']['hint'] = '';
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->activate($saved)->assertUnprocessable();
        $saved['diagnosis_fields']['cause']['hint'] = 'Describe cause';
        $saved['groups'][0]['is_active'] = false;
        foreach ($saved['groups'] as &$g) {
            $g['is_active'] = false;
        }unset($g);
        $saved = $this->save($saved)->assertOk()->json('data');
        $this->activate($saved)->assertUnprocessable();
        $this->assertSame(1, RefInspectionTemplate::active()->version);
    }

    public function test_invalid_options_bounds_and_product_types_are_rejected(): void
    {
        $draft = $this->draft();
        $draft['groups'][0]['items'][0]['fields'][0]['min_value'] = 100;
        $draft['groups'][0]['items'][0]['fields'][0]['max_value'] = 10;
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->activate($saved)->assertUnprocessable();
        $bad = $saved;
        $bad['groups'][0]['items'][0]['machine_types'] = [9999];
        $this->save($bad)->assertUnprocessable();
        $bad = $saved;
        $bad['diagnosis_fields']['extra'] = ['label' => 'Bad', 'hint' => 'Bad'];
        $this->save($bad)->assertUnprocessable();
        $saved['groups'][0]['items'][0]['fields'][0]['max_value'] = 200;
        $saved['groups'][0]['items'][3]['fields'][0]['options'] = [];
        $saved = $this->save($saved)->assertOk()->json('data');
        $this->activate($saved)->assertUnprocessable();
    }

    public function test_preview_and_confirmation_are_required_and_immutable_versions_cannot_change(): void
    {
        $draft = $this->draft();
        $url = $this->api.'/'.$draft['id'];
        $this->postJson($url.'/activate', ['lock_version' => 0, 'confirmed' => true])->assertUnprocessable();
        $token = $this->postJson($url.'/preview', ['lock_version' => 0])->assertOk()->json('preview_token');
        $this->postJson($url.'/activate', ['lock_version' => 0, 'confirmed' => false, 'preview_token' => $token])->assertUnprocessable();
        $this->activate($draft)->assertOk();
        $this->save($draft)->assertStatus(409);
        $this->deleteJson($url)->assertStatus(405);
        $this->assertSame(1, RefInspectionTemplate::where('status', 'active')->count());
        $this->assertSame('inactive', RefInspectionTemplate::where('version', 1)->first()->status);
        $next = $this->draft();
        $this->assertSame(3, $next['version']);
        $this->assertSame($draft['groups'][0]['group_key'], $next['groups'][0]['group_key']);
    }

    public function test_activation_keeps_old_form_values_and_new_forms_use_new_fields(): void
    {
        $old = TicketServiceForm::first();
        $draft = $this->draft();
        $draft['groups'][0]['title'] = 'Updated electrical group';
        $draft['groups'][0]['items'][0]['fields'][0]['label'] = 'Updated RS reading';
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->activate($saved)->assertOk();
        $this->assertSame(1, $old->fresh()->template_version);
        $this->assertSame('R-S', TicketDocuments::formStructure($this->ticket(), $old)[0]['items'][0]['fields'][0]['label']);
        $new = TicketServiceForm::create(['ticket_id' => 2, 'ref_inspection_template_id' => $saved['id'], 'template_version' => 2, 'first_draft_at' => now()]);
        $newGroups = TicketDocuments::formStructure($this->ticket(), $new);
        $this->assertSame('Updated electrical group', $newGroups[0]['title']);
        $this->assertSame('Updated RS reading', $newGroups[0]['items'][0]['fields'][0]['label']);
        $this->assertNotSame($newGroups[0]['items'][0]['fields'][0]['id'], 1);
        $this->assertSame(['template_draft_created', 'template_draft_saved', 'template_activated'], DB::table('inspection_template_audits')->orderBy('id')->pluck('action')->all());
    }

    public function test_applicability_uses_product_type_ids_and_warns_on_new_types(): void
    {
        $draft = $this->draft();
        $draft['groups'][0]['items'][0]['machine_types'] = [1];
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->activate($saved)->assertOk();
        $item = RefInspectionTemplate::active()->items()->where('number', 1)->first();
        $this->assertTrue($item->appliesToType(1));
        $this->assertFalse($item->appliesToType(2));
        $this->assertFalse($item->appliesToType(null));
        DB::table('ref_product_types')->insert(['id' => 3, 'name' => 'New Ice Type']);
        $warnings = $this->getJson($this->api.'/'.$saved['id'])->assertOk()->json('data.warnings');
        $warning = collect($warnings)->firstWhere('item_key', $item->item_key);
        $this->assertContains('New Ice Type', $warning['excluded_types']);
        $form = TicketServiceForm::create(['ticket_id' => 2, 'ref_inspection_template_id' => $saved['id'], 'template_version' => 2, 'first_draft_at' => now()]);
        $groups = TicketDocuments::formStructure($this->ticket(2), $form);
        $this->assertFalse($groups[0]['items'][0]['applicable']);
        $this->assertSame('na', $groups[0]['items'][0]['status']);
    }

    public function test_new_numeric_field_bounds_and_units_are_enforced_when_saving_values(): void
    {
        $draft = $this->draft();
        $f = &$draft['groups'][0]['items'][0]['fields'][0];
        $f['min_value'] = 200;
        $f['max_value'] = 240;
        $f['units'] = ['V', 'mV'];
        unset($f);
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->activate($saved)->assertOk();
        $form = TicketServiceForm::create(['ticket_id' => 2, 'ref_inspection_template_id' => $saved['id'], 'template_version' => 2, 'first_draft_at' => now()]);
        $actor = User::find(1);
        $ticket = $this->ticket();
        TicketDocuments::saveFormDraft($ticket, $form, ['inspections' => [['item_number' => 1, 'status' => 'ok', 'values' => ['rs' => ['value' => 220, 'unit' => 'mV']]]]], $actor);
        $value = $form->inspections()->first()->values()->first();
        $this->assertSame('220', $value->value);
        $this->assertSame('mV', $value->unit);
        foreach ([['value' => 300, 'unit' => 'V'], ['value' => 220, 'unit' => 'invalid']] as $raw) {
            try {
                TicketDocuments::saveFormDraft($ticket, $form, ['inspections' => [['item_number' => 1, 'status' => 'ok', 'values' => ['rs' => $raw]]]], $actor);
                $this->fail('Invalid reading should fail');
            } catch (HttpException $e) {
                $this->assertSame(422, $e->getStatusCode());
            }
        }
        $this->assertSame('220', $value->fresh()->value);
    }

    public function test_seeders_do_not_overwrite_published_versions(): void
    {
        $before = RefInspectionField::find(1);
        $before->update(['label' => 'Preserved legacy reading']);
        $this->seed([InspectionItemSeeder::class, InspectionVersionSeeder::class]);
        $this->assertSame('Preserved legacy reading', $before->fresh()->label);
        $this->assertSame(1, RefInspectionTemplate::count());
    }

    public function test_multiple_checklist_options_are_saved_validated_and_read_as_an_array(): void
    {
        $draft = $this->draft();
        $draft['groups'][0]['items'][3]['fields'][0]['is_multiple'] = true;
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->activate($saved)->assertOk();
        $form = TicketServiceForm::create(['ticket_id' => 2, 'ref_inspection_template_id' => $saved['id'], 'template_version' => 2, 'first_draft_at' => now()]);
        TicketDocuments::saveFormDraft($this->ticket(), $form, ['inspections' => [['item_number' => 4, 'status' => 'rep', 'values' => ['condition' => ['Good', 'Loose']]]]], User::find(1));
        $groups = TicketDocuments::formStructure($this->ticket(), $form);
        $this->assertSame(['Good', 'Loose'], $groups[0]['items'][3]['fields'][0]['value']);
        try {
            TicketDocuments::saveFormDraft($this->ticket(), $form, ['inspections' => [['item_number' => 4, 'values' => ['condition' => ['Bad option']]]]], User::find(1));
            $this->fail('Unknown option must fail');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    public function test_numeric_fields_and_photos_keep_their_original_version_when_new_versions_activate(): void
    {
        DB::table('ticket_photos')->insert(['ticket_inspection_value_id' => 1, 'file_path' => 'legacy.jpg', 'captured_at' => now()]);
        $draft = $this->draft();
        $draft['groups'][0]['items'][0]['is_active'] = false;
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->activate($saved)->assertOk();
        $old = TicketServiceForm::first();
        $groups = TicketDocuments::formStructure($this->ticket(), $old);
        $this->assertSame('220', $groups[0]['items'][0]['fields'][0]['value']);
        $this->assertNotNull($groups[0]['items'][0]['fields'][0]['photo']);
        $this->assertSame('legacy.jpg', DB::table('ticket_photos')->value('file_path'));
        $this->assertSame(33, RefInspectionTemplate::active()->items()->count());
    }

    public function test_users_without_aftersales_management_access_are_denied(): void
    {
        $this->manager(false);
        $this->getJson($this->api)->assertForbidden();
        $this->getJson($this->api.'/1')->assertForbidden();
        $this->postJson($this->api.'/draft')->assertForbidden();
        $this->putJson($this->api.'/1', [])->assertForbidden();
        $this->postJson($this->api.'/1/preview', [])->assertForbidden();
        $this->postJson($this->api.'/1/activate', [])->assertForbidden();
    }

    public function test_newly_assigned_forms_pin_the_active_version_and_keep_it_on_later_ensure(): void
    {
        Schema::create('ticket_agreements', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('ticket_id');
            $t->softDeletes();
        });
        Schema::create('ticket_work_orders', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('ticket_id');
            $t->softDeletes();
        });
        DB::table('ticket_agreements')->insert(['ticket_id' => 3]);
        DB::table('ticket_work_orders')->insert(['ticket_id' => 3]);
        $draft = $this->draft();
        $this->activate($draft)->assertOk();
        $ticket = $this->ticket();
        $ticket->forceFill(['id' => 3, 'assigned_technician_id' => 1]);
        TicketDocuments::ensure($ticket);
        $form = TicketServiceForm::where('ticket_id', 3)->first();
        $this->assertSame(2, $form->template_version);
        $this->assertSame($draft['id'],$form->ref_inspection_template_id);
        $next = $this->draft();
        $this->activate($next)->assertOk();
        TicketDocuments::ensure($ticket);
        $this->assertSame(2,$form->fresh()->template_version);
        $this->assertSame($draft['id'],$form->fresh()->ref_inspection_template_id);
    }
}
