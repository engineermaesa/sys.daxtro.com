<?php

namespace Tests\Feature\Leads;

use App\Models\Leads\Lead;
use App\Models\Leads\LeadClaim;
use App\Models\Leads\LeadStatus;
use App\Models\Orders\Quotation;
use App\Models\Orders\QuotationLog;
use App\Models\Orders\QuotationReview;
use App\Models\Orders\TempQuotation;
use App\Models\Orders\TempQuotationItem;
use App\Models\Orders\TempQuotationPaymentTerm;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\Orders\QuotationSubmittedNotification;
use App\Services\Orders\QuotationRevisions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Support\BuildsQuotationSubmissionSchema;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('quotation-revision')]
class WarmLeadFinalSubmitQuotationTest extends TestCase
{
    use BuildsQuotationSubmissionSchema;

    private string $originalConnection;

    protected User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = DB::getDefaultConnection();
        config(['database.connections.quotation_submission_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('quotation_submission_test');
        $this->buildQuotationSubmissionSchema();
        Notification::fake();

        // Minimal reference data required by the NOT NULL FKs on `leads`.
        $sourceId = DB::table('lead_sources')->insertGetId(['name' => 'Test Source']);
        DB::table('lead_statuses')->insert([
            ['id' => LeadStatus::WARM, 'name' => 'Warm'],
        ]);

        $role = UserRole::create(['name' => 'Sales', 'code' => 'sales']);
        $this->sales = User::factory()->create(['role_id' => $role->id]);

        $this->sourceId = $sourceId;
    }

    protected function tearDown(): void
    {
        DB::purge('quotation_submission_test');
        DB::setDefaultConnection($this->originalConnection);

        parent::tearDown();
    }

    protected int $sourceId;

    protected function makeLeadWithClaim(): LeadClaim
    {
        $lead = Lead::create([
            'source_id' => $this->sourceId,
            'status_id' => LeadStatus::WARM,
            'name' => 'Test Lead',
        ]);

        return LeadClaim::factory()->create([
            'lead_id' => $lead->id,
            'sales_id' => $this->sales->id,
            'claimed_at' => now(),
        ]);
    }

    protected function makeDraftFor(LeadClaim $claim): TempQuotation
    {
        $draft = TempQuotation::factory()->create([
            'lead_id' => $claim->lead_id,
            'status' => 'draft',
            'created_by' => $this->sales->id,
        ]);

        TempQuotationItem::create([
            'temp_quotation_id' => $draft->id,
            'product_id' => null,
            'qty' => 1,
            'description' => 'Item A',
            'unit_price' => 1000000,
            'discount_pct' => 0,
            'line_total' => 1000000,
            'is_visible_pdf' => true,
        ]);

        TempQuotationPaymentTerm::create([
            'temp_quotation_id' => $draft->id,
            'term_no' => 1,
            'percentage' => 100,
            'description' => 'Full Payment',
        ]);

        QuotationLog::create([
            'temp_quotation_id' => $draft->id,
            'quotation_id' => null,
            'action' => 'draft_created',
            'user_id' => $this->sales->id,
            'logged_at' => now(),
        ]);

        return $draft;
    }

    public function test_submit_retains_start_event_and_records_actual_submitter_separately(): void
    {
        $claim = $this->makeLeadWithClaim();
        $draft = $this->makeDraftFor($claim);
        $started = QuotationLog::firstOrFail();
        $startedAt = $started->getRawOriginal('logged_at');
        $role = UserRole::create(['name' => 'BM', 'code' => 'branch_manager']);
        $bm = User::factory()->create(['role_id' => $role->id, 'branch_id' => 1]);
        $this->actingAs($bm)->postJson(route('leads.my.warm.quotation.final-submit', $claim->id))->assertOk();
        $this->assertSame('draft_created', $started->fresh()->action);
        $this->assertSame($this->sales->id, $started->fresh()->user_id);
        $this->assertSame($startedAt, $started->fresh()->getRawOriginal('logged_at'));
        $submitted = QuotationLog::where('action', 'submitted')->sole();
        $this->assertSame($bm->id, $submitted->user_id);
        $this->assertSame($draft->id, $submitted->temp_quotation_id);
        $this->assertSame('QT_DEAL_001_R0', $submitted->revision_code);
        $this->assertSame($submitted->revision_code, $started->fresh()->revision_code);
    }

    public function test_final_submit_creates_new_quotation_for_lead_with_no_prior_quotation(): void
    {
        $claim = $this->makeLeadWithClaim();
        $draft = $this->makeDraftFor($claim);

        $response = $this->actingAs($this->sales)
            ->postJson(route('leads.my.warm.quotation.final-submit', $claim->id));

        $response->assertOk();

        $this->assertDatabaseCount('quotations', 1);
        $quotation = Quotation::where('lead_id', $claim->lead_id)->first();
        $this->assertNotNull($quotation);
        $this->assertMatchesRegularExpression('/^QT_DEAL_\d{3}$/', $quotation->quotation_no);
        $this->assertEquals('review', $quotation->status);
        $this->assertSame(0, $quotation->revision_no);
        $this->assertSame($quotation->quotation_no.'_R0', $quotation->revision_code);
        $this->assertEquals(1, $quotation->items()->count());
        $this->assertEquals(1, $quotation->paymentTerms()->count());

        $draft->refresh();
        $this->assertEquals('submitted', $draft->status);
        $this->assertSame($quotation->id, $draft->quotation_id);
        $this->assertSame(0, $draft->revision_no);
        $this->assertSame($quotation->revision_code, $draft->revision_code);
        $this->assertTrue($quotation->revisions->sole()->is($draft));
        $this->assertEquals($quotation->only([
            'subtotal', 'tax_pct', 'tax_total', 'total_discount', 'grand_total', 'booking_fee',
        ]), $draft->only([
            'subtotal', 'tax_pct', 'tax_total', 'total_discount', 'grand_total', 'booking_fee',
        ]));

        $this->assertDatabaseCount('quotation_logs', 2);
        $this->assertSame('draft_created', QuotationLog::first()->action);
        $log = QuotationLog::where('action', 'submitted')->sole();
        $this->assertEquals($quotation->id, $log->quotation_id);
        $this->assertSame($draft->id, $log->temp_quotation_id);
        $this->assertEquals('submitted', $log->action);
    }

    public function test_final_submit_redraft_of_rejected_quotation_keeps_same_quotation_no(): void
    {
        $claim = $this->makeLeadWithClaim();

        $rejected = Quotation::factory()->create([
            'lead_id' => $claim->lead_id,
            'quotation_no' => 'QT_DEAL_042',
            'status' => 'rejected',
            'created_by' => $this->sales->id,
        ]);

        $draft = $this->makeDraftFor($claim);

        $response = $this->actingAs($this->sales)
            ->postJson(route('leads.my.warm.quotation.final-submit', $claim->id));

        $response->assertOk();

        $this->assertDatabaseCount('quotations', 1);
        $this->assertDatabaseCount('temp_quotations', 2);
        $this->assertSame(0, DB::table('document_counters')->where('type', 'quotation')->value('last_number'));
        $rejected->refresh();
        $this->assertEquals('QT_DEAL_042', $rejected->quotation_no);
        $this->assertEquals('review', $rejected->status);
        $this->assertSame(1, $rejected->revision_no);
        $this->assertSame('QT_DEAL_042_R1', $rejected->revision_code);
        $this->assertEquals(1, $rejected->items()->count());

        $draft->refresh();
        $this->assertEquals('submitted', $draft->status);
    }

    public function test_second_final_submit_call_is_rejected_without_creating_duplicate(): void
    {
        $claim = $this->makeLeadWithClaim();
        $this->makeDraftFor($claim);

        $first = $this->actingAs($this->sales)
            ->postJson(route('leads.my.warm.quotation.final-submit', $claim->id));
        $first->assertOk();

        $second = $this->actingAs($this->sales)
            ->postJson(route('leads.my.warm.quotation.final-submit', $claim->id));
        $second->assertStatus(409);

        $this->assertDatabaseCount('quotations', 1);
        $this->assertDatabaseCount('temp_quotations', 1);
        $this->assertSame(1, DB::table('document_counters')->where('type', 'quotation')->value('last_number'));
    }

    public function test_final_submit_rolls_back_on_forced_failure(): void
    {
        $claim = $this->makeLeadWithClaim();
        $draft = $this->makeDraftFor($claim);

        // Fail after the draft has been promoted, so header/items/terms/counter must all roll back.
        DB::unprepared("CREATE TRIGGER fail_submit_log BEFORE INSERT ON quotation_logs WHEN NEW.action = 'submitted' BEGIN SELECT RAISE(ABORT, 'forced submit failure'); END");

        $response = $this->actingAs($this->sales)
            ->postJson(route('leads.my.warm.quotation.final-submit', $claim->id));

        $response->assertStatus(500);
        $this->assertDatabaseCount('quotations', 0);

        $draft->refresh();
        $this->assertEquals('draft', $draft->status);
        $this->assertNull($draft->quotation_id);
        $this->assertNull($draft->revision_no);
        $this->assertNull($draft->revision_code);
        $this->assertDatabaseCount('quotation_items', 0);
        $this->assertDatabaseCount('quotation_payment_terms', 0);
        $this->assertCount(1, $draft->items);
        $this->assertCount(1, $draft->paymentTerms);
        $this->assertSame(0, DB::table('document_counters')->where('type', 'quotation')->value('last_number'));
        Notification::assertNothingSent();

        $log = QuotationLog::first();
        $this->assertEquals('draft_created', $log->action);
        $this->assertNull($log->quotation_id);
    }

    public function test_r0_preserves_hidden_and_merged_items_and_multiple_payment_terms(): void
    {
        $claim = $this->makeLeadWithClaim();
        $draft = $this->makeDraftFor($claim);
        DB::table('ref_products')->insert(['id' => 1]);
        $parent = $draft->items()->firstOrFail();
        $parent->id = 50;
        $parent->product_id = 1;
        $parent->save();
        $addon = $draft->items()->create([
            'qty' => 2, 'description' => 'Bundled addon', 'unit_price' => 50000,
            'discount_pct' => 10, 'line_total' => 90000,
            'is_visible_pdf' => false, 'merge_into_item_id' => $parent->id,
        ]);
        $draft->paymentTerms()->firstOrFail()->update(['percentage' => 40, 'description' => 'Deposit']);
        $draft->paymentTerms()->create(['term_no' => 2, 'percentage' => 60, 'description' => 'Delivery']);

        $this->actingAs($this->sales)
            ->postJson(route('leads.my.warm.quotation.final-submit', $claim->id))->assertOk();

        $quotation = Quotation::firstOrFail();
        $officialItems = $quotation->items()->orderBy('id')->get();
        $snapshot = $quotation->revisions->sole();
        $this->assertSame($draft->id, $snapshot->id);
        $this->assertCount(2, $snapshot->items);
        $this->assertCount(2, $officialItems);
        $this->assertSame(1, $officialItems[0]->product_id);
        $this->assertNull($officialItems[1]->product_id);
        $this->assertFalse($officialItems[1]->is_visible_pdf);
        $this->assertSame($officialItems[0]->id, $officialItems[1]->merge_into_item_id);
        $this->assertSame(50, $addon->fresh()->merge_into_item_id);
        $this->assertNotEquals($officialItems[0]->id, $addon->fresh()->merge_into_item_id);
        $fields = ['product_id', 'qty', 'description', 'unit_price', 'discount_pct', 'line_total', 'is_visible_pdf'];
        $this->assertEquals($snapshot->items->map->only($fields)->all(), $officialItems->map->only($fields)->all());
        $fields = ['term_no', 'percentage', 'description'];
        $this->assertEquals($snapshot->paymentTerms->map->only($fields)->all(), $quotation->paymentTerms->map->only($fields)->all());
        $this->assertSame(['Deposit', 'Delivery'], $snapshot->paymentTerms->pluck('description')->all());
    }

    public function test_opening_submitted_r0_reuses_snapshot_and_keeps_its_items_unchanged(): void
    {
        $claim = $this->makeLeadWithClaim();
        $draft = $this->makeDraftFor($claim);
        $this->assertDatabaseCount('quotations', 0);
        $this->assertNull($draft->revision_code);
        $this->actingAs($this->sales)
            ->postJson(route('leads.my.warm.quotation.final-submit', $claim->id))->assertOk();
        $quotation = Quotation::firstOrFail();
        $quotation->items()->firstOrFail()->update(['description' => 'Subsequent live edit']);

        $snapshot = app(QuotationRevisions::class)->ensureInitialSnapshot($quotation);

        $this->assertSame($draft->id, $snapshot->id);
        $this->assertSame('Item A', $snapshot->items->sole()->description);
        $this->assertDatabaseCount('temp_quotations', 1);
        $this->assertDatabaseCount('temp_quotation_items', 1);
        $this->assertDatabaseCount('temp_quotation_payment_terms', 1);
    }

    public function test_submit_creates_the_r0_snapshot_before_notifying_the_branch_manager(): void
    {
        $claim = $this->makeLeadWithClaim();
        $draft = $this->makeDraftFor($claim);
        $role = UserRole::create(['name' => 'Branch Manager', 'code' => 'branch_manager']);
        $bm = User::factory()->create(['role_id' => $role->id, 'branch_id' => 1]);

        $this->actingAs($this->sales)
            ->postJson(route('leads.my.warm.quotation.final-submit', $claim->id))->assertOk();

        Notification::assertSentTo($bm, QuotationSubmittedNotification::class, function ($notification) use ($draft) {
            return $notification->quotation->revision_code === $notification->quotation->quotation_no.'_R0'
                && $notification->quotation->status === 'review'
                && $notification->quotation->revisions->sole()->id === $draft->id
                && DB::transactionLevel() === 0;
        });
    }

    public function test_first_submit_without_a_draft_creation_log_still_links_the_log_to_r0(): void
    {
        $claim = $this->makeLeadWithClaim();
        $draft = $this->makeDraftFor($claim);
        QuotationLog::query()->forceDelete();

        $this->actingAs($this->sales)
            ->postJson(route('leads.my.warm.quotation.final-submit', $claim->id))->assertOk();

        $log = QuotationLog::sole();
        $this->assertSame('submitted', $log->action);
        $this->assertSame($draft->id, $log->temp_quotation_id);
        $this->assertSame(Quotation::firstOrFail()->id, $log->quotation_id);
        $this->assertSame('QT_DEAL_001_R0', $log->tempQuotation->revision_code);
    }

    private function currentQuotation(string $status): array
    {
        $claim = $this->makeLeadWithClaim();
        $r0 = $this->makeDraftFor($claim);
        $this->actingAs($this->sales)
            ->postJson(route('leads.my.warm.quotation.final-submit', $claim->id))->assertOk();
        $quotation = Quotation::where('lead_id', $claim->lead_id)->firstOrFail();
        $quotation->update(['status' => $status]);

        return [$claim, $quotation, $r0];
    }

    private function revisionPayload(): array
    {
        return [
            'product_id' => [null, null], 'description' => ['Revised machine', 'Included service'],
            'qty' => [2, 1], 'unit_price' => [500000, 100000], 'discount_pct' => [0, 0],
            'tax_pct' => 11, 'payment_type' => 'down_payment',
            'term_percentage' => [40, 60], 'term_description' => ['Deposit', 'Delivery'],
            'is_visible_pdf' => [1, 0], 'merge_into_item_id' => [null, 0],
        ];
    }

    public function test_nominal_discount_survives_draft_reload_and_submission(): void
    {
        $claim = $this->makeLeadWithClaim();
        $draft = $this->makeDraftFor($claim);
        $payload = array_replace($this->revisionPayload(), [
            'draft_id' => $draft->id,
            'unit_price' => [600000, 100000],
            'discount_amount' => ['100003.25', '100000'],
            'discount_pct' => [25, 25],
        ]);

        $this->actingAs($this->sales);
        $storeUrl = route('leads.my.warm.quotation.store', $claim->id);
        foreach ([-1, 1200000.01] as $invalidAmount) {
            $this->postJson($storeUrl, array_replace($payload, ['discount_amount' => [$invalidAmount, null]]))
                ->assertStatus(422);
        }
        $this->assertSame('Item A', $draft->items()->sole()->description);

        $saved = $this->postJson($storeUrl, $payload)->assertOk();
        $draft->refresh();
        $this->assertEquals(200003.25, $draft->total_discount);
        $this->assertEquals(1099996.75, $draft->subtotal);
        $items = $draft->items()->orderBy('id')->get();
        $this->assertEquals(8.33, $items[0]->discount_pct);
        $this->assertEquals(100, $items[1]->discount_pct);
        $this->assertEquals(0, $items[1]->line_total);

        $reopened = $this->getJson(route('leads.my.warm.quotation.create', $claim->id))->assertOk();
        $reopenedItem = TempQuotationItem::findOrFail($reopened->json('draft.items.0.id'));
        $discountFields = view('pages.leads.warm.partials.quotation-discount', [
            'discountItem' => $reopenedItem,
            'disabled' => '',
        ])->render();
        $this->assertStringContainsString('value="100.003,25"', $discountFields);
        $this->assertStringContainsString('value="8.33" readonly', $discountFields);
        $this->postJson(route('leads.my.warm.quotation.final-submit', $claim->id), [
            'draft_id' => $draft->id,
            'draft_version' => $saved->json('data.draft_version'),
        ])->assertOk();

        $quotation = Quotation::sole();
        $this->assertEquals(200003.25, $quotation->total_discount);
        $this->assertEquals(1099996.75, $quotation->items()->orderBy('id')->firstOrFail()->line_total);
        $this->assertEqualsWithDelta(1220996.3925, $quotation->grand_total, 0.0001);
    }

    public function test_rejected_and_bm_approved_forms_prefill_current_contents_and_resume_saved_edits(): void
    {
        foreach (['rejected', 'approved_by_bm'] as $status) {
            [$claim, $quotation, $r0] = $this->currentQuotation($status);
            $before = $quotation->getRawOriginal();
            $url = route('leads.my.warm.quotation.create', $claim->id);
            $response = $this->getJson($url)->assertOk()
                ->assertJsonPath('isDraftMode', true)->assertJsonPath('isEditable', true)
                ->assertJsonPath('draft.revision_no', 1)
                ->assertJsonPath('draft.revision_code', $quotation->quotation_no.'_R1')
                ->assertJsonPath('draft.items.0.description', 'Item A')
                ->assertJsonPath('draft.payment_terms.0.description', 'Full Payment');
            $draft = TempQuotation::findOrFail($response->json('draft.id'));
            $this->assertSame($quotation->id, $draft->quotation_id);
            $this->assertEquals($before, $quotation->fresh()->getRawOriginal());
            $this->assertSame('submitted', $r0->fresh()->status);
            $draft->items()->firstOrFail()->update(['description' => 'Saved negotiation edit']);

            $this->getJson($url)->assertOk()->assertJsonPath('draft.id', $draft->id)
                ->assertJsonPath('draft.items.0.description', 'Saved negotiation edit');
            $this->assertSame(1, TempQuotation::where('lead_id', $claim->lead_id)->where('status', 'draft')->count());
        }
    }

    public function test_revision_submit_advances_r0_to_r1_then_r2_and_preserves_snapshots_and_review_history(): void
    {
        foreach (['rejected', 'approved_by_bm'] as $status) {
            [$claim, $quotation, $r0] = $this->currentQuotation($status);
            $review = QuotationReview::create([
                'quotation_id' => $quotation->id, 'revision_no' => 0,
                'reviewer_id' => $this->sales->id, 'role' => $status === 'rejected' ? 'FIN' : 'BM',
                'decision' => $status === 'rejected' ? 'reject' : 'approve', 'notes' => 'Original decision',
            ]);
            $counter = DB::table('document_counters')->where('type', 'quotation')->value('last_number');
            $draftId = $this->getJson(route('leads.my.warm.quotation.create', $claim->id))->assertOk()->json('draft.id');
            $this->postJson(route('leads.my.warm.quotation.store', $claim->id), $this->revisionPayload())->assertOk();
            $this->assertSame('Item A', $quotation->items->sole()->description);
            $this->assertSame($status, $quotation->fresh()->status);
            $this->postJson(route('leads.my.warm.quotation.final-submit', $claim->id))->assertOk();

            $quotation->refresh();
            $this->assertSame('review', $quotation->status);
            $this->assertSame(1, $quotation->revision_no);
            $this->assertSame($quotation->quotation_no.'_R1', $quotation->revision_code);
            $r1 = $quotation->revisions()->where('status', 'submitted')->where('revision_no', 1)->sole();
            $this->assertSame($draftId, $r1->id);
            $this->assertSame('Revised machine', $r1->items->first()->description);
            $this->assertCount(2, $r1->paymentTerms);
            $official = $quotation->items()->orderBy('id')->get();
            $this->assertFalse($official[1]->is_visible_pdf);
            $this->assertSame($official[0]->id, $official[1]->merge_into_item_id);
            $this->assertSame('Item A', $r0->items->sole()->description);
            $this->assertSame('Original decision', $review->fresh()->notes);
            $this->assertTrue($review->fresh()->trashed());
            $this->assertSame(0, $review->fresh()->revision_no);
            $this->assertSame(0, $quotation->reviews()->count());
            $this->assertSame($counter, DB::table('document_counters')->where('type', 'quotation')->value('last_number'));

            $quotation->update(['status' => 'rejected']);
            $this->getJson(route('leads.my.warm.quotation.create', $claim->id))->assertOk()
                ->assertJsonPath('draft.revision_no', 2)
                ->assertJsonPath('draft.items.0.description', 'Revised machine');
            $this->postJson(route('leads.my.warm.quotation.final-submit', $claim->id))->assertOk();
            $this->assertSame(2, $quotation->fresh()->revision_no);
            $this->assertSame([2, 1, 0], $quotation->revisions()->where('status', 'submitted')->pluck('revision_no')->all());
        }
    }

    public function test_revision_submission_rolls_back_current_contents_snapshot_and_review_on_failure(): void
    {
        [$claim, $quotation] = $this->currentQuotation('approved_by_bm');
        $review = QuotationReview::create(['quotation_id' => $quotation->id, 'role' => 'BM', 'decision' => 'approve']);
        $draftId = $this->getJson(route('leads.my.warm.quotation.create', $claim->id))->assertOk()->json('draft.id');
        $this->postJson(route('leads.my.warm.quotation.store', $claim->id), $this->revisionPayload())->assertOk();
        DB::unprepared("CREATE TRIGGER fail_revision_log BEFORE INSERT ON quotation_logs WHEN NEW.action = 'submitted' BEGIN SELECT RAISE(ABORT, 'forced revision failure'); END");
        Notification::fake();

        $this->postJson(route('leads.my.warm.quotation.final-submit', $claim->id))->assertStatus(500);

        $this->assertSame('approved_by_bm', $quotation->fresh()->status);
        $this->assertSame(0, $quotation->fresh()->revision_no);
        $this->assertSame('Item A', $quotation->items->sole()->description);
        $this->assertSame('draft', TempQuotation::findOrFail($draftId)->status);
        $this->assertSame(1, $quotation->revisions()->where('status', 'submitted')->count());
        $this->assertFalse($review->fresh()->trashed());
        Notification::assertNothingSent();
    }

    public function test_stale_revision_target_is_rejected_on_both_save_and_submit(): void
    {
        [$claim, $quotation] = $this->currentQuotation('approved_by_bm');
        $draftId = $this->getJson(route('leads.my.warm.quotation.create', $claim->id))->assertOk()->json('draft.id');
        TempQuotation::findOrFail($draftId)->update(['revision_no' => 99, 'revision_code' => $quotation->quotation_no.'_R99']);

        $this->postJson(route('leads.my.warm.quotation.store', $claim->id), $this->revisionPayload())->assertStatus(409);
        $this->postJson(route('leads.my.warm.quotation.final-submit', $claim->id))->assertStatus(409);

        $this->assertSame(0, $quotation->fresh()->revision_no);
        $this->assertSame('Item A', TempQuotation::findOrFail($draftId)->items->sole()->description);
    }

    public function test_status_change_while_revision_is_open_blocks_both_save_and_submit(): void
    {
        foreach (['review', 'pending_finance', 'published'] as $status) {
            [$claim, $quotation] = $this->currentQuotation('approved_by_bm');
            $draftId = $this->getJson(route('leads.my.warm.quotation.create', $claim->id))->assertOk()->json('draft.id');
            $quotation->update(['status' => $status]);

            $this->postJson(route('leads.my.warm.quotation.store', $claim->id), $this->revisionPayload())->assertStatus(409);
            $this->postJson(route('leads.my.warm.quotation.final-submit', $claim->id))->assertStatus(409);
            $this->assertSame($status, $quotation->fresh()->status);
            $this->assertSame('draft', TempQuotation::findOrFail($draftId)->status);
        }
    }

    public function test_finance_cannot_open_a_revision_form_or_create_a_revision_draft(): void
    {
        [$claim, $quotation] = $this->currentQuotation('approved_by_bm');
        $role = UserRole::create(['name' => 'Finance', 'code' => 'finance']);
        $finance = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($finance)->getJson(route('leads.my.warm.quotation.create', $claim->id))->assertForbidden();

        $this->assertSame(0, TempQuotation::where('lead_id', $claim->lead_id)->where('status', 'draft')->count());
        $this->assertSame('approved_by_bm', $quotation->fresh()->status);
    }

    public function test_revision_submission_notifies_the_bm_with_the_new_revision(): void
    {
        [$claim, $quotation] = $this->currentQuotation('approved_by_bm');
        $role = UserRole::create(['name' => 'Branch Manager', 'code' => 'branch_manager']);
        $bm = User::factory()->create(['role_id' => $role->id, 'branch_id' => 1]);
        $this->getJson(route('leads.my.warm.quotation.create', $claim->id))->assertOk();

        $this->postJson(route('leads.my.warm.quotation.final-submit', $claim->id))->assertOk();

        Notification::assertSentTo($bm, QuotationSubmittedNotification::class, function ($notification) use ($quotation) {
            return $notification->quotation->id === $quotation->id
                && $notification->quotation->revision_no === 1
                && $notification->quotation->revision_code === $quotation->quotation_no.'_R1'
                && $notification->quotation->status === 'review';
        });
    }
}
