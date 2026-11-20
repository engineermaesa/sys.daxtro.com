<?php

namespace Tests\Feature\Leads;

use App\Models\Leads\Lead;
use App\Models\Leads\LeadClaim;
use App\Models\Leads\LeadStatus;
use App\Models\Orders\Quotation;
use App\Models\Orders\QuotationLog;
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

        $this->assertDatabaseCount('quotation_logs', 1);
        $log = QuotationLog::first();
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
        $this->assertDatabaseCount('temp_quotations', 1);
        $this->assertSame(0, DB::table('document_counters')->where('type', 'quotation')->value('last_number'));
        $rejected->refresh();
        $this->assertEquals('QT_DEAL_042', $rejected->quotation_no);
        $this->assertEquals('review', $rejected->status);
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
        $second->assertStatus(500);

        $this->assertDatabaseCount('quotations', 1);
        $this->assertDatabaseCount('temp_quotations', 1);
        $this->assertSame(1, DB::table('document_counters')->where('type', 'quotation')->value('last_number'));
    }

    public function test_final_submit_rolls_back_on_forced_failure(): void
    {
        $claim = $this->makeLeadWithClaim();
        $draft = $this->makeDraftFor($claim);

        // Fail after the draft has been promoted, so header/items/terms/counter must all roll back.
        DB::unprepared("CREATE TRIGGER fail_submit_log BEFORE UPDATE OF action ON quotation_logs BEGIN SELECT RAISE(ABORT, 'forced submit failure'); END");

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
}
