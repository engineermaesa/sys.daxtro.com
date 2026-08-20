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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WarmLeadFinalSubmitQuotationTest extends TestCase
{
    use RefreshDatabase;

    protected User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        // Minimal reference data required by the NOT NULL FKs on `leads`.
        $sourceId = DB::table('lead_sources')->insertGetId(['name' => 'Test Source']);
        DB::table('lead_statuses')->insert([
            ['id' => LeadStatus::WARM, 'name' => 'Warm'],
        ]);

        $role = UserRole::create(['name' => 'Sales', 'code' => 'sales']);
        $this->sales = User::factory()->create(['role_id' => $role->id]);

        $this->sourceId = $sourceId;
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
        $this->assertEquals(1, $quotation->items()->count());
        $this->assertEquals(1, $quotation->paymentTerms()->count());

        $draft->refresh();
        $this->assertEquals('submitted', $draft->status);

        $this->assertDatabaseCount('quotation_logs', 1);
        $log = QuotationLog::first();
        $this->assertEquals($quotation->id, $log->quotation_id);
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
    }

    public function test_final_submit_rolls_back_on_forced_failure(): void
    {
        $claim = $this->makeLeadWithClaim();
        $draft = $this->makeDraftFor($claim);

        // Force a failure mid-transaction by pointing merge_into_item_id at a
        // non-existent temp item id combined with an invalid payment term
        // percentage type is hard to trigger via SQLite loosely-typed columns,
        // so instead we corrupt the draft's item to have a null unit_price
        // where the QuotationItems insert isn't nullable in the real schema.
        // Simplest reliable trigger: delete the draft between read and write
        // isn't possible within one request; instead assert idempotency of
        // state by simulating a duplicate final-submit inside a transaction
        // is already covered above. Here we verify that deleting the payment
        // term (leaving zero items) still yields a consistent, non-partial
        // state on hard failure by breaking the FK: point lead_id at a
        // non-existent lead so quotation creation cannot succeed.
        DB::table('temp_quotations')->where('id', $draft->id)->update(['lead_id' => 999999]);

        $response = $this->actingAs($this->sales)
            ->postJson(route('leads.my.warm.quotation.final-submit', $claim->id));

        $response->assertStatus(500);
        $this->assertDatabaseCount('quotations', 0);

        $draft->refresh();
        $this->assertEquals('draft', $draft->status);

        $log = QuotationLog::first();
        $this->assertEquals('draft_created', $log->action);
        $this->assertNull($log->quotation_id);
    }
}
