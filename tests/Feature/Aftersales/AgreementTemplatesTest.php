<?php

namespace Tests\Feature\Aftersales;

use App\Models\Aftersales\RefAgreementTemplate;
use App\Models\Aftersales\TicketAgreement;
use App\Models\User;
use Database\Seeders\AgreementTemplateSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class AgreementTemplatesTest extends TestCase
{
    private string $endpoint = '/api/aftersales/agreement-templates';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locked' => false]);
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->softDeletes();
        });
        Schema::create('ref_agreement_templates', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('version')->unique();
            $t->string('title');
            $t->json('sections');
            $t->text('closing_statement')->nullable();
            $t->boolean('is_active')->default(false);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_agreements', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('ticket_id');
            $t->foreignId('ref_agreement_template_id')->constrained('ref_agreement_templates');
            $t->unsignedInteger('template_version');
            $t->json('accepted_clauses')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        (require database_path('migrations/2026_10_16_000000_add_agreement_template_version_management.php'))->up();
        DB::table('users')->insert(['id' => 1, 'name' => 'Aftersales Admin']);
        $this->seed(AgreementTemplateSeeder::class);
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
        return $this->postJson($this->endpoint.'/draft')->assertOk()->json('data');
    }

    private function save(array $draft)
    {
        return $this->putJson($this->endpoint.'/'.$draft['id'], $draft);
    }

    public function test_draft_is_a_full_copy_and_only_one_draft_is_created(): void
    {
        $first = $this->draft();
        $second = $this->draft();
        $this->assertSame($first['id'], $second['id']);
        $this->assertSame(2, $first['version']);
        $this->assertSame('draft', $first['status']);
        $this->assertCount(15, $first['sections']);
        $this->assertCount(31, RefAgreementTemplate::find($first['id'])->clauseKeys());
        $this->assertSame(1, DB::table('agreement_template_audits')->count());
        $this->assertSame(1, RefAgreementTemplate::active()->version);
    }

    public function test_save_generates_keys_preserves_old_keys_and_rejects_stale_saves(): void
    {
        $draft = $this->draft();
        $originalKey = $draft['sections'][0]['clauses'][0]['key'];
        $draft['sections'][0]['clauses'][0]['text'] = 'Updated clause';
        $draft['sections'][0]['clauses'][] = ['key' => null, 'text' => 'New clause', 'is_active' => true];
        $draft['sections'][] = ['section_key' => null, 'title' => 'New section', 'is_active' => true, 'clauses' => [['key' => null, 'text' => 'Another clause', 'is_active' => true]]];
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->assertSame($originalKey, $saved['sections'][0]['clauses'][0]['key']);
        $this->assertStringStartsWith('c_', $saved['sections'][0]['clauses'][3]['key']);
        $this->assertStringStartsWith('s_', $saved['sections'][15]['section_key']);
        $this->save($draft)->assertStatus(409);
        $this->assertNotSame('Updated clause', RefAgreementTemplate::active()->sections[0]['clauses'][0]['text']);
        $saved['sections'][0]['clauses'][0]['key'] = 'changed-key';
        $this->save($saved)->assertUnprocessable();
    }

    public function test_items_cannot_be_deleted_and_can_be_disabled_and_restored(): void
    {
        $draft = $this->draft();
        $deleted = $draft;
        array_pop($deleted['sections'][0]['clauses']);
        $this->save($deleted)->assertUnprocessable();
        $draft['sections'][0]['clauses'][0]['is_active'] = false;
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->assertCount(30, RefAgreementTemplate::find($draft['id'])->clauseKeys());
        $saved['sections'][0]['clauses'][0]['is_active'] = true;
        $this->save($saved)->assertOk();
        $this->assertCount(31, RefAgreementTemplate::find($draft['id'])->clauseKeys());
    }

    public function test_activation_requires_preview_confirmation_and_valid_content(): void
    {
        $draft = $this->draft();
        $url = $this->endpoint.'/'.$draft['id'];
        $this->postJson($url.'/activate', ['lock_version' => 0, 'confirmed' => true])->assertUnprocessable();
        $token = $this->postJson($url.'/preview', ['lock_version' => 0])->assertOk()->json('preview_token');
        $this->postJson($url.'/activate', ['lock_version' => 0, 'confirmed' => false, 'preview_token' => $token])->assertUnprocessable();
        $draft['sections'][0]['clauses'] = array_map(fn ($c) => array_replace($c, ['is_active' => false]), $draft['sections'][0]['clauses']);
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->postJson($url.'/activate', ['lock_version' => $saved['lock_version'], 'confirmed' => true, 'preview_token' => $token])->assertUnprocessable();
        $token = $this->postJson($url.'/preview', ['lock_version' => $saved['lock_version']])->assertOk()->json('preview_token');
        $this->postJson($url.'/activate', ['lock_version' => $saved['lock_version'], 'confirmed' => true, 'preview_token' => $token])->assertUnprocessable();
        $this->assertSame(1, RefAgreementTemplate::active()->version);
    }

    public function test_activation_accepts_current_draft_id_returned_as_a_string(): void
    {
        Schema::table('agreement_template_state', function (Blueprint $table) {
            $table->string('draft_template_id')->nullable()->change();
        });
        $draft = $this->draft();
        $this->assertSame((string) $draft['id'], DB::table('agreement_template_state')->value('draft_template_id'));
        $url = $this->endpoint.'/'.$draft['id'];
        $token = $this->postJson($url.'/preview', ['lock_version' => $draft['lock_version']])->assertOk()->json('preview_token');
        $this->postJson($url.'/activate', ['lock_version' => $draft['lock_version'], 'confirmed' => true, 'preview_token' => $token])
            ->assertOk()->assertJsonPath('data.status', 'active');
    }

    public function test_activation_rejects_a_draft_that_is_no_longer_current(): void
    {
        $draft = $this->draft();
        $url = $this->endpoint.'/'.$draft['id'];
        $token = $this->postJson($url.'/preview', ['lock_version' => $draft['lock_version']])->assertOk()->json('preview_token');
        DB::table('agreement_template_state')->where('id', 1)->update(['draft_template_id' => null]);
        $this->postJson($url.'/activate', ['lock_version' => $draft['lock_version'], 'confirmed' => true, 'preview_token' => $token])
            ->assertStatus(409);
        $this->assertSame('draft', RefAgreementTemplate::find($draft['id'])->status);
        $this->assertSame(1, RefAgreementTemplate::active()->version);
    }

    public function test_activation_keeps_historical_agreements_and_versions_immutable(): void
    {
        $old = RefAgreementTemplate::active();
        $doc = TicketAgreement::create(['ticket_id' => 7, 'ref_agreement_template_id' => $old->id, 'template_version' => 1, 'accepted_clauses' => ['A1']]);
        $draft = $this->draft();
        $draft['sections'][0]['clauses'][0]['is_active'] = false;
        $draft['sections'] = array_reverse($draft['sections']);
        $saved = $this->save($draft)->assertOk()->json('data');
        $url = $this->endpoint.'/'.$draft['id'];
        $token = $this->postJson($url.'/preview', ['lock_version' => $saved['lock_version']])->assertOk()->json('preview_token');
        $this->postJson($url.'/activate', ['lock_version' => $saved['lock_version'], 'confirmed' => true, 'preview_token' => $token])->assertOk()->assertJsonPath('data.status', 'active');
        $this->assertSame(2, RefAgreementTemplate::active()->version);
        $this->assertSame(1, RefAgreementTemplate::where('is_active', true)->count());
        $this->assertSame('inactive', $old->fresh()->status);
        $this->assertSame(1, $doc->fresh()->template_version);
        $this->assertContains('A1', $doc->fresh()->template->clauseKeys());
        $this->assertNotContains('A1', RefAgreementTemplate::active()->clauseKeys());
        $this->assertSame('A', RefAgreementTemplate::active()->documentSections()[0]['code']);
        $this->save($saved)->assertStatus(409);
        $this->putJson($this->endpoint.'/'.$old->id, array_replace($draft, ['lock_version' => 0]))->assertStatus(409);
        $this->deleteJson($url)->assertStatus(405);
        $this->assertSame(['template_draft_created', 'template_draft_saved', 'template_activated'], DB::table('agreement_template_audits')->orderBy('id')->pluck('action')->all());
        $next = $this->draft();
        $this->assertSame(3, $next['version']);
        $this->assertSame($saved['sections'][0]['section_key'], $next['sections'][0]['section_key']);
    }

    public function test_seeder_does_not_overwrite_published_content_or_reactivate_v1(): void
    {
        $first = RefAgreementTemplate::active();
        $first->update(['title' => 'Existing production title']);
        $this->seed(AgreementTemplateSeeder::class);
        $this->assertSame('Existing production title', $first->fresh()->title);
    }

    public function test_legacy_migration_preserves_existing_keys_and_content(): void
    {
        $before = RefAgreementTemplate::active()->sections;
        $migration = require database_path('migrations/2026_10_16_000000_add_agreement_template_version_management.php');
        $migration->down();
        $migration->up();
        $template = RefAgreementTemplate::active();
        $this->assertSame('active', $template->status);
        $this->assertSame($before, $template->sections);
        $this->assertSame('A1', $template->clauseKeys()[0]);
        $this->assertSame($template->id, DB::table('agreement_template_state')->value('active_template_id'));
    }

    public function test_activation_rejects_all_disabled_sections_and_stale_preview_revision(): void
    {
        $draft = $this->draft();
        $url = $this->endpoint.'/'.$draft['id'];
        $token = $this->postJson($url.'/preview', ['lock_version' => 0])->assertOk()->json('preview_token');
        $draft['sections'] = array_map(fn ($s) => array_replace($s, ['is_active' => false]), $draft['sections']);
        $saved = $this->save($draft)->assertOk()->json('data');
        $this->postJson($url.'/activate', ['lock_version' => 0, 'confirmed' => true, 'preview_token' => $token])->assertStatus(409);
        $token = $this->postJson($url.'/preview', ['lock_version' => $saved['lock_version']])->assertOk()->json('preview_token');
        $this->postJson($url.'/activate', ['lock_version' => $saved['lock_version'], 'confirmed' => true, 'preview_token' => $token])->assertUnprocessable();
        $this->assertSame(1, RefAgreementTemplate::active()->version);
    }

    public function test_pdf_uses_the_pinned_template_title_and_active_clauses(): void
    {
        $draft = $this->draft();
        $draft['title'] = 'Custom agreement title';
        $draft['sections'][0]['clauses'][0]['text'] = 'A disabled clause must not print';
        $draft['sections'][0]['clauses'][0]['is_active'] = false;
        $saved = $this->save($draft)->assertOk()->json('data');
        $template = RefAgreementTemplate::find($saved['id']);
        $doc = new TicketAgreement(['template_version' => 2]);
        $doc->setRelation('template', $template);
        $ticket = new \App\Models\Aftersales\Ticket(['ticket_code' => 'TEST-7']);
        foreach (['customer', 'technician'] as $relation) {
            $ticket->setRelation($relation, null);
        }
        $html = view('pdfs.aftersales.agreement', [
            'doc' => $doc, 'ticket' => $ticket, 'sections' => $template->documentSections(),
            'closing' => $template->closing_statement, 'accepted' => [], 'signatures' => [],
            'copyLabel' => 'Test', 'docNumber' => 'TEST-7',
        ])->render();
        $this->assertStringContainsString('Custom agreement title', $html);
        $this->assertStringNotContainsString('A disabled clause must not print', $html);
        $this->assertStringContainsString($draft['sections'][0]['clauses'][1]['text'], $html);
    }

    public function test_unauthorized_users_cannot_read_or_mutate_templates(): void
    {
        $this->manager(false);
        $this->getJson($this->endpoint)->assertForbidden();
        $this->getJson($this->endpoint.'/1')->assertForbidden();
        $this->postJson($this->endpoint.'/draft')->assertForbidden();
        $this->putJson($this->endpoint.'/1', [])->assertForbidden();
        $this->postJson($this->endpoint.'/1/preview', [])->assertForbidden();
        $this->postJson($this->endpoint.'/1/activate', [])->assertForbidden();
    }
}
