<?php

namespace Tests\Feature\Leads;

use App\Models\Leads\{Lead, LeadClaim, LeadStatus};
use App\Models\Orders\{Quotation, QuotationReview, TempQuotation};
use App\Models\{User, UserRole};
use App\Services\Orders\QuotationRevisions;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, File, Notification, Schema};
use Tests\Support\BuildsQuotationSubmissionSchema;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

class QuotationRevisionHistoryTest extends TestCase
{
    use BuildsQuotationSubmissionSchema;

    private string $originalConnection;
    private User $sales;
    private User $bm;
    private User $finance;
    private Quotation $quotation;
    private LeadClaim $claim;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->originalConnection = DB::getDefaultConnection();
        config(['database.connections.quotation_history_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('quotation_history_test');
        $this->buildQuotationSubmissionSchema();
        foreach (['quotation_signed_documents', 'payment_logs'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('quotation_id'); $table->softDeletes();
            });
        }
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('lead_id'); $table->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('proforma_id'); $table->softDeletes();
        });
        Schema::create('finance_requests', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('reference_id'); $table->string('request_type'); $table->softDeletes();
        });
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id(); $table->string('code'); $table->softDeletes();
        });
        Schema::create('user_role_permissions', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('role_id'); $table->unsignedBigInteger('permission_id'); $table->softDeletes();
        });
        Notification::fake();
        $this->sales = $this->user('sales', 'Original Sales');
        $this->bm = $this->user('branch_manager', 'Branch Manager');
        $this->finance = $this->user('finance', 'Finance Reviewer');
        $source = DB::table('lead_sources')->insertGetId(['name' => 'Test Source']);
        DB::table('lead_statuses')->insert(['id' => LeadStatus::WARM, 'name' => 'Warm']);
        $lead = Lead::create(['source_id' => $source, 'status_id' => LeadStatus::WARM, 'name' => 'History Customer']);
        $this->claim = LeadClaim::factory()->create(['lead_id' => $lead->id, 'sales_id' => $this->sales->id]);
        $this->quotation = Quotation::create([
            'lead_id' => $lead->id, 'quotation_no' => 'QT_DEAL_900', 'revision_no' => 0,
            'revision_code' => 'QT_DEAL_900_R0', 'status' => 'review', 'created_by' => $this->sales->id,
        ]);
        $item = $this->quotation->items()->create([
            'description' => 'Machine R0', 'qty' => 1, 'unit_price' => 1000000,
            'discount_pct' => 0, 'line_total' => 1000000, 'is_visible_pdf' => true,
        ]);
        $term = $this->quotation->paymentTerms()->create(['term_no' => 1, 'percentage' => 100, 'description' => 'Terms R0']);
        foreach (range(0, 2) as $number) {
            $amount = ($number + 1) * 1000000;
            $this->quotation->update([
                'revision_no' => $number, 'revision_code' => 'QT_DEAL_900_R'.$number,
                'subtotal' => $amount, 'tax_pct' => 11, 'tax_total' => $amount * .11, 'grand_total' => $amount * 1.11,
            ]);
            $item->update(['description' => 'Machine R'.$number, 'unit_price' => $amount, 'line_total' => $amount]);
            $term->update(['description' => 'Terms R'.$number]);
            $snapshot = app(QuotationRevisions::class)->snapshotCurrentRevision($this->quotation, $number === 1 ? $this->bm->id : $this->sales->id);
            // The revision number, rather than creation time, determines ordering.
            $snapshot->forceFill(['created_at' => Carbon::parse('2026-11-'.(10 - $number).' 08:30', 'Asia/Jakarta')])->save();
        }
        $this->review(0, 'BM', 'reject', 'R0 BM rejected')->delete();
        $this->review(1, 'BM', 'approve', 'R1 BM approved')->delete();
        $this->review(1, 'FIN', 'reject', 'R1 Finance rejected')->delete();
        $this->review(2, 'BM', 'approve', 'R2 BM approved');
        $this->quotation->update(['status' => 'approved_by_bm']);
        $this->actingAs($this->sales);
    }

    protected function tearDown(): void
    {
        DB::purge('quotation_history_test');
        DB::setDefaultConnection($this->originalConnection);
        parent::tearDown();
    }

    private function user(string $role, ?string $name = null): User
    {
        $userRole = UserRole::firstOrCreate(['code' => $role], ['name' => $role]);
        return User::factory()->create(['role_id' => $userRole->id, 'branch_id' => 1, 'name' => $name ?? $role]);
    }

    private function review(int $number, string $role, string $decision, string $notes): QuotationReview
    {
        return QuotationReview::create([
            'quotation_id' => $this->quotation->id, 'revision_no' => $number, 'role' => $role,
            'reviewer_id' => $role === 'BM' ? $this->bm->id : $this->finance->id,
            'decision' => $decision, 'notes' => $notes, 'decided_at' => Carbon::now('Asia/Jakarta'),
        ]);
    }

    public function test_history_is_newest_first_with_one_current_badge_and_per_revision_outcomes(): void
    {
        $response = $this->get(route('quotations.revisions.index', $this->quotation->id))->assertOk()
            ->assertViewIs('pages.orders.quotation-revisions')
            ->assertSeeInOrder(['data-revision="2"', 'data-revision="1"', 'data-revision="0"'], false)
            ->assertSee('History Customer')->assertSee('Original Sales')->assertSee('Approved by BM')
            ->assertSee('Rp3.330.000')->assertSee('Rp2.220.000')->assertSee('Rp1.110.000')
            ->assertSee('R0 BM rejected')->assertSee('R1 Finance rejected')->assertSee('R2 BM approved')
            ->assertSee('WIB')->assertSee('Open')->assertSee('Download PDF');
        $this->assertSame(1, substr_count($response->getContent(), '>Current</span>'));
        $this->assertSame([2, 1, 0], $response->viewData('revisions')->pluck('revision_no')->all());
        $groups = $response->viewData('reviewsByRevision');
        $this->assertSame(['R0 BM rejected'], $groups[0]->pluck('notes')->all());
        $this->assertCount(2, $groups[1]);
        $this->assertSame(['R2 BM approved'], $groups[2]->pluck('notes')->all());
        Notification::assertNothingSent();
    }

    public function test_history_excludes_open_cancelled_and_deleted_snapshots(): void
    {
        $draft = app(QuotationRevisions::class)->startRevision($this->quotation, $this->sales->id);
        $deleted = $this->quotation->revisions()->create([
            'lead_id' => $this->quotation->lead_id, 'revision_no' => 99, 'revision_code' => 'DELETED_R99',
            'status' => 'submitted', 'created_by' => $this->sales->id,
        ]);
        $deleted->delete();
        $this->get(route('quotations.revisions.index', $this->quotation->id))->assertOk()
            ->assertDontSee('data-revision="3"', false)->assertDontSee('DELETED_R99')
            ->assertViewHas('revisions', fn ($rows) => $rows->count() === 3);
        $draft->delete();
        $this->get(route('quotations.revisions.index', $this->quotation->id))->assertOk()
            ->assertViewHas('revisions', fn ($rows) => $rows->count() === 3);
    }

    public function test_deleted_creators_and_reviewers_remain_visible_and_notes_are_escaped(): void
    {
        $this->review(0, 'BM', 'reject', '<script>alert("unsafe")</script>')->delete();
        $this->bm->delete();
        $this->finance->delete();
        $this->get(route('quotations.revisions.index', $this->quotation->id))->assertOk()
            ->assertSee('Branch Manager')->assertSee('Finance Reviewer')
            ->assertSee('&lt;script&gt;alert(&quot;unsafe&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("unsafe")</script>', false);
    }

    public function test_history_always_displays_r0_and_initializes_legacy_snapshot_once(): void
    {
        $legacy = Quotation::create([
            'lead_id' => $this->quotation->lead_id, 'quotation_no' => 'QT_DEAL_901', 'status' => 'published',
            'created_by' => $this->sales->id, 'grand_total' => 777000, 'tax_pct' => 11,
        ]);
        $raw = $legacy->getRawOriginal();
        foreach (range(1, 2) as $visit) {
            $this->get(route('quotations.revisions.index', $legacy->id))->assertOk()
                ->assertSee('QT_DEAL_901_R0')->assertSee('Current')->assertSee('No decision recorded')
                ->assertViewHas('revisions', fn ($rows) => $rows->count() === 1);
        }
        $this->assertSame(1, $legacy->revisions()->count());
        $this->assertEquals(777000, $legacy->revisions()->sole()->grand_total);
        $this->assertSame($raw['updated_at'], $legacy->fresh()->getRawOriginal('updated_at'));
        $this->assertSame('published', $legacy->fresh()->status);
    }

    public function test_history_and_snapshot_actions_follow_existing_quotation_view_roles(): void
    {
        foreach ([$this->sales, $this->bm, $this->finance, $this->user('finance_director'), $this->user('super_admin')] as $actor) {
            $this->actingAs($actor)->get(route('quotations.revisions.index', $this->quotation->id))->assertOk();
            $this->get(route('quotations.revisions.show', [$this->quotation->id, 0]))->assertOk();
        }
        $this->actingAs($this->user('purchasing'));
        foreach (['index' => [$this->quotation->id], 'show' => [$this->quotation->id, 0], 'download' => [$this->quotation->id, 0]] as $action => $parameters) {
            $this->get(route('quotations.revisions.'.$action, $parameters))->assertForbidden();
        }
    }

    public function test_open_uses_historical_contents_and_current_opens_the_existing_detail(): void
    {
        $before = $this->quotation->fresh()->getRawOriginal();
        $this->get(route('quotations.revisions.show', [$this->quotation->id, 0]))->assertOk()
            ->assertSee('Viewing revision R0 (read-only)')->assertSee('Machine R0')->assertSee('Terms R0')
            ->assertSee('R0 BM rejected')->assertDontSee('Machine R2')->assertDontSee('Terms R2')
            ->assertDontSee('R2 BM approved')->assertDontSee('Submit to Finance')->assertDontSee('Revise Quotation');
        $this->get(route('quotations.revisions.show', [$this->quotation->id, 2]))
            ->assertOk()->assertViewIs('pages.orders.quotation-show')->assertSee('Viewing revision R2 (current)');
        $this->assertSame($before, $this->quotation->fresh()->getRawOriginal());
    }

    public function test_actions_reject_missing_draft_deleted_and_other_quotation_revisions(): void
    {
        $draft = app(QuotationRevisions::class)->startRevision($this->quotation, $this->sales->id);
        $other = Quotation::create([
            'lead_id' => $this->quotation->lead_id, 'quotation_no' => 'QT_DEAL_902', 'status' => 'review',
            'revision_no' => 7, 'revision_code' => 'QT_DEAL_902_R7', 'created_by' => $this->sales->id, 'tax_pct' => 11,
        ]);
        app(QuotationRevisions::class)->snapshotCurrentRevision($other, $this->sales->id);
        $this->quotation->revisions()->where('revision_no', 1)->firstOrFail()->delete();
        foreach (['show', 'download'] as $action) {
            foreach ([1, $draft->revision_no, 7, 999] as $number) {
                $this->get(route('quotations.revisions.'.$action, [$this->quotation->id, $number]))->assertNotFound();
            }
        }
        $this->get(route('quotations.revisions.index', 999))->assertNotFound();
        $this->quotation->delete();
        $this->get(route('quotations.revisions.index', $this->quotation->id))->assertNotFound();
    }

    public function test_revision_url_stays_read_only_when_a_new_current_revision_is_submitted(): void
    {
        $url = route('quotations.revisions.show', [$this->quotation->id, 2]);
        $this->get($url)->assertOk()->assertSee('Viewing revision R2 (current)')->assertViewHas('canSubmitToFinance', true);
        $this->quotation->update(['revision_no' => 3, 'revision_code' => 'QT_DEAL_900_R3', 'status' => 'review']);
        $this->quotation->items()->firstOrFail()->update(['description' => 'Machine R3']);
        app(QuotationRevisions::class)->snapshotCurrentRevision($this->quotation, $this->sales->id);
        $this->get($url)->assertOk()->assertViewIs('pages.orders.quotation-revision')
            ->assertSee('Viewing revision R2 (read-only)')->assertSee('Machine R2')->assertDontSee('Machine R3')
            ->assertSee('Back to revision history')->assertSee('Open current revision (R3)')->assertDontSee('Submit to Finance');
        $this->get(route('quotations.show', $this->quotation->id))->assertOk()
            ->assertSee('Viewing revision R3 (current)')->assertSee('Machine R3');
        $this->actingAs($this->bm)->postJson(route('quotations.approve', $this->quotation->id), [
            'revision_no' => 2, 'notes' => 'Decision from the old detail page',
        ])->assertStatus(409);
        $this->assertSame(3, $this->quotation->fresh()->revision_no);
    }

    public function test_historical_details_have_no_business_actions_for_any_allowed_role(): void
    {
        $before = $this->quotation->fresh()->getRawOriginal();
        foreach ([$this->sales, $this->bm, $this->finance, $this->user('finance_director'), $this->user('super_admin')] as $actor) {
            $this->actingAs($actor)->get(route('quotations.revisions.show', [$this->quotation->id, 1]))->assertOk()
                ->assertSee('Viewing revision R1 (read-only)')->assertSee('Machine R1')->assertSee('Terms R1')
                ->assertSee('R1 BM approved')->assertSee('R1 Finance rejected')->assertDontSee('R2 BM approved')
                ->assertDontSee('id="approve"', false)->assertDontSee('id="reject"', false)
                ->assertDontSee('Submit to Finance')->assertDontSee('Revise Quotation')->assertDontSee('Edit Quotation')
                ->assertDontSee('Cancel Revision')->assertDontSee('type="file"', false)
                ->assertDontSee(route('quotations.signed-documents.upload', $this->quotation->id), false)
                ->assertSee(route('quotations.revisions.index', $this->quotation->id), false)
                ->assertSee(route('quotations.revisions.download', [$this->quotation->id, 1]), false);
        }
        $this->assertSame($before, $this->quotation->fresh()->getRawOriginal());
        Notification::assertNothingSent();
    }

    public function test_current_actions_follow_roles_and_approval_stages(): void
    {
        $actors = ['sales' => $this->sales, 'branch_manager' => $this->bm, 'finance' => $this->finance,
            'finance_director' => $this->user('finance_director'), 'super_admin' => $this->user('super_admin')];
        foreach (['review', 'approved_by_bm', 'pending_finance', 'rejected', 'published'] as $stage) {
            $this->quotation->currentReviews()->forceDelete();
            $this->quotation->update(['status' => $stage]);
            if (in_array($stage, ['approved_by_bm', 'pending_finance', 'published'], true)) {
                $this->review(2, 'BM', 'approve', 'BM notes for current revision');
            }
            foreach ($actors as $role => $actor) {
                $isManager = in_array($role, ['sales', 'branch_manager'], true);
                $canReview = ($stage === 'review' && $role === 'branch_manager')
                    || ($stage === 'pending_finance' && in_array($role, ['finance', 'finance_director'], true));
                $canEdit = ($isManager && in_array($stage, ['review', 'pending_finance'], true))
                    || ($role === 'branch_manager' && $stage === 'published');
                $response = $this->actingAs($actor)->get(route('quotations.revisions.show', [$this->quotation->id, 2]))
                    ->assertOk()->assertViewIs('pages.orders.quotation-show')
                    ->assertViewHas('canReview', $canReview)->assertViewHas('canEdit', $canEdit)
                    ->assertViewHas('canRevise', $isManager && in_array($stage, ['approved_by_bm', 'rejected'], true))
                    ->assertViewHas('canSubmitToFinance', $isManager && $stage === 'approved_by_bm')
                    ->assertSee('Viewing revision R2 (current)')->assertSee('Back to revision history');
                if ($canReview) {
                    $response->assertSee('id="approve"', false)->assertSee('id="reject"', false)
                        ->assertSee('name="revision_no" value="2"', false);
                } else {
                    $response->assertDontSee('id="approve"', false)->assertDontSee('id="reject"', false);
                }
                if ($stage === 'pending_finance') {
                    $response->assertSee('BM notes for current revision')->assertDontSee('R1 BM approved');
                }
            }
        }
    }

    public function test_current_actions_require_current_review_and_correct_branch_or_active_assignment(): void
    {
        $url = route('quotations.revisions.show', [$this->quotation->id, 2]);
        $this->quotation->currentReviews()->forceDelete();
        $this->get($url)->assertOk()->assertViewHas('canSubmitToFinance', false);
        $this->quotation->update(['status' => 'pending_finance']);
        $this->actingAs($this->finance)->get($url)->assertOk()->assertViewHas('canReview', false);
        $this->review(2, 'BM', 'approve', 'Valid BM approval');
        $this->get($url)->assertOk()->assertViewHas('canReview', true);
        $this->review(2, 'FIN', 'approve', 'Already reviewed');
        $this->get($url)->assertOk()->assertViewHas('canReview', false);

        $this->quotation->update(['status' => 'review']);
        $this->actingAs($this->bm)->get($url)->assertOk()->assertViewHas('canReview', false);
        $this->quotation->currentReviews()->forceDelete();
        $this->bm->update(['branch_id' => 2]);
        $this->actingAs($this->bm)->get($url)->assertOk()->assertViewHas('canReview', false)->assertViewHas('canEdit', false);
        $this->actingAs($this->user('sales'))->get($url)->assertOk()->assertViewHas('canEdit', false);
        $this->claim->update(['trash_note' => 'Trashed']);
        $this->actingAs($this->sales)->get($url)->assertOk()->assertViewHas('canEdit', false)
            ->assertDontSee('type="file"', false);
        $this->claim->update(['trash_note' => null, 'released_at' => Carbon::now('Asia/Jakarta')]);
        $this->get($url)->assertOk()->assertViewHas('canEdit', false);
    }

    public function test_open_revision_displays_continue_and_cancel_actions_only_for_authorized_roles(): void
    {
        $draft = app(QuotationRevisions::class)->startRevision($this->quotation, $this->sales->id);
        $url = route('quotations.revisions.show', [$this->quotation->id, 2]);
        $this->get($url)->assertOk()->assertViewHas('canSubmitToFinance', false)
            ->assertSee('Continue Revision (R3)')->assertSee('Cancel Revision')->assertSee('data-confirm-title="Cancel Revision?"', false);
        $this->actingAs($this->bm)->get($url)->assertOk()->assertSee('Continue Revision (R3)')
            ->assertViewHas('canSubmitToFinance', false)->assertViewHas('canCancelRevision', false);
        $this->actingAs($this->finance)->get($url)->assertOk()->assertViewHas('canRevise', false)->assertViewHas('canCancelRevision', false);
        $this->actingAs($this->sales)->postJson(route('quotations.drafts.cancel', $draft->id))->assertOk();
        $this->get($url)->assertOk()->assertViewHas('canSubmitToFinance', true)->assertSee('Revise Quotation');
    }

    public function test_published_bm_edit_is_disabled_after_any_payment_confirmation(): void
    {
        $this->quotation->update(['status' => 'published']);
        $url = route('quotations.revisions.show', [$this->quotation->id, 2]);
        $this->actingAs($this->bm)->get($url)->assertOk()->assertViewHas('canEdit', true);
        $proforma = \App\Models\Orders\Proforma::create([
            'quotation_id' => $this->quotation->id, 'term_no' => 1, 'proforma_no' => 'PF_DETAIL',
            'proforma_type' => 'down_payment', 'amount' => 3330000, 'status' => 'pending',
        ]);
        $this->get($url)->assertOk()->assertViewHas('canEdit', true);
        \App\Models\Orders\PaymentConfirmation::create(['proforma_id' => $proforma->id, 'amount' => 100000]);
        $this->get($url)->assertOk()->assertViewHas('canEdit', false)->assertDontSee('Edit Quotation');
    }

    public static function pdfRevisions(): array
    {
        return ['R0' => [0], 'R1' => [1], 'R2' => [2], 'current download' => [null]];
    }

    #[DataProvider('pdfRevisions')]
    public function test_pdf_action_receives_selected_snapshot_and_preserves_official_data(?int $number): void
    {
        $selected = $number ?? 2;
        // Live header edits and an unsubmitted revision must never leak into a PDF.
        $this->quotation->items()->firstOrFail()->update(['description' => 'Unversioned live edit']);
        $this->quotation->paymentTerms()->firstOrFail()->update(['description' => 'Unversioned terms']);
        $this->quotation->update(['grand_total' => 9999999]);
        $draft = app(QuotationRevisions::class)->startRevision($this->quotation, $this->sales->id);
        $draft->items()->firstOrFail()->update(['description' => 'Unsubmitted machine']);
        $before = $this->quotation->fresh()->getRawOriginal();
        $capturedPath = null;
        File::partialMock()->shouldReceive('ensureDirectoryExists')->once();
        File::shouldReceive('exists')->twice()->andReturnTrue();
        Pdf::shouldReceive('loadView')->once()->with('pdfs.quotation_body', \Mockery::on(function ($data) use ($selected) {
            $quotation = $data['quotation'];
            return $quotation->quotation_no === 'QT_DEAL_900'
                && $quotation->revision_code === 'QT_DEAL_900_R'.$selected
                && $quotation->items->sole()->description === 'Machine R'.$selected
                && $quotation->paymentTerms->sole()->description === 'Terms R'.$selected
                && (float) $quotation->grand_total === (float) (($selected + 1) * 1110000);
        }))->andReturnSelf();
        Pdf::shouldReceive('setPaper')->with('A4', 'portrait')->once()->andReturnSelf();
        Pdf::shouldReceive('save')->once()->with(\Mockery::on(function ($path) use (&$capturedPath, $selected) {
            $capturedPath = $path;
            return str_contains($path, '_R'.$selected.'_');
        }));
        $merger = \Mockery::mock();
        PDFMerger::shouldReceive('init')->once()->andReturn($merger);
        $merger->shouldReceive('addPDF')->once()->with(\Mockery::on(function ($path) use (&$capturedPath) {
            return $path === $capturedPath;
        }), 'all');
        $merger->shouldReceive('merge')->once();
        $merger->shouldReceive('save')->once()->with(\Mockery::on(function ($path) {
            file_put_contents($path, '%PDF-1.4 test');
            return true;
        }));
        $url = $number === null ? route('quotations.download', $this->quotation->id)
            : route('quotations.revisions.download', [$this->quotation->id, $number]);
        $response = $this->get($url)->assertOk()->assertDownload('QT_DEAL_900_R'.$selected.'.pdf');
        unlink($response->baseResponse->getFile()->getPathname());
        $this->assertSame($before, $this->quotation->fresh()->getRawOriginal());
        $this->assertSame('Unversioned live edit', $this->quotation->fresh()->items->sole()->description);
    }

    public function test_pdf_header_items_merged_amounts_and_payment_terms_render_from_snapshot(): void
    {
        $snapshot = $this->quotation->revisions()->where('revision_no', 1)->firstOrFail()->load('items', 'paymentTerms');
        $item = $snapshot->items->sole();
        $item->setRelation('product', new \App\Models\Masters\Product(['name' => 'Changed master product', 'sku' => 'SKU-1']));
        $snapshot->items()->create([
            'description' => 'Hidden addon', 'qty' => 1, 'unit_price' => 50000, 'line_total' => 50000,
            'is_visible_pdf' => false, 'merge_into_item_id' => $item->id,
        ]);
        $snapshot->load('items');
        foreach ($snapshot->items as $snapshotItem) {
            $snapshotItem->setRelation('product', $snapshotItem->id === $item->id ? $item->product : null);
        }
        $document = clone $this->quotation;
        $document->forceFill($snapshot->only(['revision_no', 'revision_code', 'subtotal', 'tax_pct', 'tax_total', 'grand_total', 'booking_fee']));
        $document->setRelation('items', $snapshot->items)->setRelation('paymentTerms', $snapshot->paymentTerms);
        $html = view('pdfs.quotation_body', ['quotation' => $document, 'user' => $this->sales, 'claim' => $this->claim])->render();
        $this->assertStringContainsString('Quotation No: QT_DEAL_900_R1', $html);
        $this->assertStringContainsString('Machine R1', $html);
        $this->assertStringContainsString('Terms R1', $html);
        $this->assertStringContainsString('2.050.000', $html);
        $this->assertStringNotContainsString('Changed master product', $html);
        $this->assertStringNotContainsString('Machine R2', $html);
        $this->assertStringNotContainsString('Hidden addon', $html);
        $this->assertStringContainsString('PT Pandu Naradipta Danendra', $html);
        $this->assertStringContainsString('Your Reliable Business Partner', $html);
        $this->assertStringContainsString('href="https://daxtro.id/"', $html);
        $this->assertStringNotContainsString('footer-slice.png', $html);
        $this->assertStringNotContainsString('PT Pandu Mahardika Perdana', $html);
    }

    public function test_download_does_not_fall_back_to_live_contents_when_snapshot_is_missing_or_invalid(): void
    {
        Pdf::shouldReceive('loadView')->never();
        $snapshot = $this->quotation->revisions()->where('revision_no', 2)->firstOrFail();
        $snapshot->update(['revision_code' => 'WRONG_REVISION']);
        $this->get(route('quotations.download', $this->quotation->id))->assertStatus(409);
        $snapshot->delete();
        $this->get(route('quotations.download', $this->quotation->id))->assertNotFound();
    }
}
