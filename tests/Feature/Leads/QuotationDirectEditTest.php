<?php

namespace Tests\Feature\Leads;

use App\Models\Leads\Lead;
use App\Models\Leads\LeadClaim;
use App\Models\Leads\LeadStatus;
use App\Models\Orders\PaymentConfirmation;
use App\Models\Orders\Proforma;
use App\Models\Orders\Quotation;
use App\Models\Orders\QuotationLog;
use App\Models\Orders\QuotationReview;
use App\Models\Orders\TempQuotation;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\Orders\QuotationSubmittedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Support\BuildsQuotationSubmissionSchema;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('quotation-revision')]
class QuotationDirectEditTest extends TestCase
{
    use BuildsQuotationSubmissionSchema;

    private string $originalConnection;
    private User $sales;
    private User $bm;
    private LeadClaim $claim;
    private Quotation $quotation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = DB::getDefaultConnection();
        config(['database.connections.quotation_direct_edit_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('quotation_direct_edit_test');
        $this->buildQuotationSubmissionSchema();
        Notification::fake();
        $this->sales = $this->user('sales');
        $this->bm = $this->user('branch_manager');
        $source = DB::table('lead_sources')->insertGetId(['name' => 'Test Source']);
        DB::table('lead_statuses')->insert(['id' => LeadStatus::WARM, 'name' => 'Warm']);
        $lead = Lead::create(['source_id' => $source, 'status_id' => LeadStatus::WARM, 'name' => 'Customer']);
        $this->claim = LeadClaim::factory()->create(['lead_id' => $lead->id, 'sales_id' => $this->sales->id]);
        $draft = TempQuotation::factory()->create(['lead_id' => $lead->id, 'created_by' => $this->sales->id]);
        $draft->items()->create([
            'description' => 'Original machine', 'qty' => 1, 'unit_price' => 1000000,
            'discount_pct' => 0, 'line_total' => 1000000, 'is_visible_pdf' => true,
        ]);
        $draft->paymentTerms()->create(['term_no' => 1, 'percentage' => 100, 'description' => 'Original terms']);
        $this->actingAs($this->sales)
            ->postJson(route('leads.my.warm.quotation.final-submit', $this->claim->id))->assertOk();
        $this->quotation = Quotation::firstOrFail();
        Notification::fake();
    }

    protected function tearDown(): void
    {
        DB::purge('quotation_direct_edit_test');
        DB::setDefaultConnection($this->originalConnection);
        parent::tearDown();
    }

    private function user(string $role): User
    {
        $userRole = UserRole::firstOrCreate(['code' => $role], ['name' => $role]);

        return User::factory()->create(['role_id' => $userRole->id, 'branch_id' => 1]);
    }

    private function payload(): array
    {
        return [
            'revision_no' => $this->quotation->fresh()->revision_no,
            'product_id' => [null, null], 'description' => ['Edited machine', 'Bundled addon'],
            'qty' => [2, 1], 'unit_price' => [500000, 100000], 'discount_pct' => [0, 0],
            'tax_pct' => 11, 'payment_type' => 'booking_fee', 'booking_fee' => 100000,
            'term_percentage' => [40, 60], 'term_description' => ['Deposit', 'Delivery'],
            'is_visible_pdf' => [1, 0], 'merge_into_item_id' => [null, 0],
        ];
    }

    private function proforma(): Proforma
    {
        return Proforma::create([
            'quotation_id' => $this->quotation->id, 'term_no' => 1, 'proforma_no' => 'PF_EXISTING_001',
            'proforma_type' => 'down_payment', 'amount' => 1110000, 'status' => 'confirmed',
            'issued_by' => $this->bm->id, 'issued_at' => Carbon::now('Asia/Jakarta'), 'attachment_id' => 987,
        ]);
    }

    public function test_sales_and_bm_edits_during_review_or_finance_create_snapshots_and_restart_review(): void
    {
        foreach ([$this->sales, $this->bm] as $actor) {
            foreach (['review', 'pending_finance'] as $status) {
                $this->quotation->update(['status' => $status]);
                $oldRevision = $this->quotation->revision_no;
                $review = QuotationReview::create([
                    'quotation_id' => $this->quotation->id, 'revision_no' => $oldRevision,
                    'role' => 'BM', 'decision' => 'approve', 'notes' => 'Previous approval',
                ]);
                $this->actingAs($actor)->postJson(route('leads.my.warm.quotation.store', $this->claim->id), $this->payload())->assertOk();

                $this->quotation->refresh();
                $this->assertSame($oldRevision + 1, $this->quotation->revision_no);
                $this->assertSame('review', $this->quotation->status);
                $this->assertSame('QT_DEAL_001_R'.($oldRevision + 1), $this->quotation->revision_code);
                $this->assertSame($this->sales->id, $this->quotation->created_by);
                $snapshot = $this->quotation->revisions()->where('revision_no', $oldRevision + 1)->sole();
                $this->assertSame('submitted', $snapshot->status);
                $this->assertSame($actor->id, $snapshot->created_by);
                $this->assertSame($this->quotation->revision_code, $snapshot->revision_code);
                $this->assertEquals(1221000, $snapshot->grand_total);
                $this->assertEquals(100000, $snapshot->booking_fee);
                $items = $snapshot->items()->orderBy('id')->get();
                $this->assertCount(2, $items);
                $this->assertFalse($items[1]->is_visible_pdf);
                $this->assertSame($items[0]->id, $items[1]->merge_into_item_id);
                $this->assertSame(['Deposit', 'Delivery'], $snapshot->paymentTerms->pluck('description')->all());
                $this->assertTrue($review->fresh()->trashed());
                $this->assertSame($oldRevision, $review->fresh()->revision_no);
                $this->assertSame('Previous approval', $review->fresh()->notes);
                $log = QuotationLog::where('action', 'revision_in_place')->latest('id')->firstOrFail();
                $this->assertSame($snapshot->id, $log->temp_quotation_id);
                $this->assertSame($actor->id, $log->user_id);
                $this->assertSame(1, DB::table('document_counters')->where('type', 'quotation')->value('last_number'));
            }
        }
        $r0 = $this->quotation->revisions()->where('revision_no', 0)->sole();
        $this->assertSame('Original machine', $r0->items->sole()->description);
        $this->assertSame('Original terms', $r0->paymentTerms->sole()->description);
        $this->assertSame([4, 3, 2, 1, 0], $this->quotation->revisions()->pluck('revision_no')->all());
        Notification::assertSentTo($this->bm, QuotationSubmittedNotification::class,
            fn ($notification) => $notification->quotation->revision_code === 'QT_DEAL_001_R4');
    }

    public function test_direct_edit_calculates_and_snapshots_nominal_discounts(): void
    {
        $payload = array_replace($this->payload(), ['discount_amount' => [12345, null]]);
        $this->actingAs($this->sales)
            ->postJson(route('leads.my.warm.quotation.store', $this->claim->id), $payload)->assertOk();

        $this->quotation->refresh();
        $this->assertEquals(12345, $this->quotation->total_discount);
        $this->assertEquals(987655, $this->quotation->items()->orderBy('id')->firstOrFail()->line_total);
        $snapshot = $this->quotation->revisions()->where('revision_no', $this->quotation->revision_no)->sole();
        $this->assertEquals(12345, $snapshot->total_discount);
        $this->assertEquals(1.23, $snapshot->items()->orderBy('id')->firstOrFail()->discount_pct);
    }

    public function test_bm_can_edit_published_without_payment_and_proforma_is_preserved(): void
    {
        $this->quotation->update(['status' => 'published']);
        $proforma = $this->proforma();
        $before = DB::table('proformas')->where('id', $proforma->id)->first();

        $this->actingAs($this->bm)
            ->postJson(route('leads.my.warm.quotation.store', $this->claim->id), $this->payload())->assertOk();

        $this->assertSame(1, $this->quotation->fresh()->revision_no);
        $this->assertSame('review', $this->quotation->fresh()->status);
        $this->assertSame($this->bm->id, $this->quotation->revisions()->where('revision_no', 1)->sole()->created_by);
        $this->assertEquals($before, DB::table('proformas')->where('id', $proforma->id)->first());
        $this->assertSame(1, Proforma::count());
        $log = QuotationLog::where('action', 'bm_published_revision')->sole();
        $this->assertSame('QT_DEAL_001_R1', $log->revision_code);
        $this->assertSame(1, $log->revision_no);
        $this->assertSame($this->bm->id, $log->user_id);
    }

    public function test_published_edits_are_denied_for_sales_and_for_bm_after_payment_confirmation(): void
    {
        $this->quotation->update(['status' => 'published']);
        $proforma = $this->proforma();
        $this->actingAs($this->sales)
            ->postJson(route('leads.my.warm.quotation.store', $this->claim->id), $this->payload())->assertForbidden();
        PaymentConfirmation::create(['proforma_id' => $proforma->id, 'amount' => 100000]);
        $this->actingAs($this->bm)
            ->postJson(route('leads.my.warm.quotation.store', $this->claim->id), $this->payload())->assertForbidden();

        $this->assertSame('published', $this->quotation->fresh()->status);
        $this->assertSame(0, $this->quotation->fresh()->revision_no);
        $this->assertSame(1, $this->quotation->revisions()->count());
        $this->assertSame('Original machine', $this->quotation->items->sole()->description);
        Notification::assertNothingSent();
    }

    public function test_finance_cannot_edit_quotation_contents(): void
    {
        $finance = $this->user('finance');
        $this->quotation->update(['status' => 'pending_finance']);
        $this->actingAs($finance)
            ->postJson(route('leads.my.warm.quotation.store', $this->claim->id), $this->payload())->assertForbidden();
        $this->assertSame(0, $this->quotation->fresh()->revision_no);
        $this->assertSame(1, $this->quotation->revisions()->count());
    }

    public function test_a_stale_form_cannot_overwrite_a_later_revision(): void
    {
        $payload = $this->payload();
        $this->postJson(route('leads.my.warm.quotation.store', $this->claim->id), $payload)->assertOk();
        $before = $this->quotation->fresh()->getRawOriginal();

        $payload['description'][0] = 'Stale browser edit';
        $this->postJson(route('leads.my.warm.quotation.store', $this->claim->id), $payload)->assertStatus(409);

        $this->assertEquals($before, $this->quotation->fresh()->getRawOriginal());
        $this->assertSame('Edited machine', $this->quotation->items->first()->description);
        $this->assertSame(2, $this->quotation->revisions()->count());
    }

    public function test_failed_snapshot_rolls_back_live_data_review_history_and_snapshot_rows(): void
    {
        $this->quotation->update(['status' => 'pending_finance']);
        $review = QuotationReview::create(['quotation_id' => $this->quotation->id, 'role' => 'BM', 'decision' => 'approve']);
        $r0 = $this->quotation->revisions()->sole();
        DB::unprepared('CREATE TRIGGER fail_edit_snapshot BEFORE INSERT ON temp_quotation_payment_terms '
            .'WHEN NEW.temp_quotation_id <> '.(int) $r0->id." BEGIN SELECT RAISE(ABORT, 'forced edit failure'); END");

        $this->postJson(route('leads.my.warm.quotation.store', $this->claim->id), $this->payload())->assertStatus(500);

        $this->assertSame(0, $this->quotation->fresh()->revision_no);
        $this->assertSame('pending_finance', $this->quotation->fresh()->status);
        $this->assertSame('Original machine', $this->quotation->items->sole()->description);
        $this->assertSame('Original terms', $this->quotation->paymentTerms->sole()->description);
        $this->assertFalse($review->fresh()->trashed());
        $this->assertSame(1, $this->quotation->revisions()->count());
        $this->assertSame(1, DB::table('temp_quotation_items')->count());
        $this->assertSame(0, QuotationLog::whereIn('action', ['revision_in_place', 'bm_published_revision'])->count());
        Notification::assertNothingSent();
    }

    public function test_direct_edit_preserves_a_lazy_legacy_r0_before_replacing_live_items(): void
    {
        $r0 = $this->quotation->revisions()->sole();
        QuotationLog::where('temp_quotation_id', $r0->id)->update(['temp_quotation_id' => null]);
        $r0->forceDelete();
        $this->quotation->update(['revision_code' => null]);

        $this->postJson(route('leads.my.warm.quotation.store', $this->claim->id), $this->payload())->assertOk();

        $snapshots = $this->quotation->revisions()->get();
        $this->assertSame([1, 0], $snapshots->pluck('revision_no')->all());
        $this->assertSame('Edited machine', $snapshots[0]->items->first()->description);
        $this->assertSame('Original machine', $snapshots[1]->items->sole()->description);
        $this->assertSame('QT_DEAL_001_R0', $snapshots[1]->revision_code);
    }

    public function test_invalid_booking_fee_does_not_start_a_transaction_or_create_a_revision(): void
    {
        $payload = $this->payload();
        $payload['booking_fee'] = 999999999;
        $this->postJson(route('leads.my.warm.quotation.store', $this->claim->id), $payload)->assertStatus(422);

        $this->assertSame(0, DB::transactionLevel());
        $this->assertSame(0, $this->quotation->fresh()->revision_no);
        $this->assertSame(1, $this->quotation->revisions()->count());
    }
}
