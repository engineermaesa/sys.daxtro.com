<?php

namespace Tests\Feature\Leads;

use App\Models\Leads\Lead;
use App\Models\Leads\LeadClaim;
use App\Models\Leads\LeadStatus;
use App\Models\Orders\Proforma;
use App\Models\Orders\Quotation;
use App\Models\Orders\QuotationLog;
use App\Models\Orders\TempQuotation;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\Orders\QuotationPendingFinanceNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\BuildsQuotationSubmissionSchema;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('quotation-revision')]
class QuotationDraftCancellationTest extends TestCase
{
    use BuildsQuotationSubmissionSchema;

    private string $originalConnection;
    private User $sales;
    private User $bm;
    private LeadClaim $claim;
    private TempQuotation $draft;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = DB::getDefaultConnection();
        config(['database.connections.quotation_cancel_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('quotation_cancel_test');
        $this->buildQuotationSubmissionSchema();
        Notification::fake();
        $this->sales = $this->user('sales');
        $this->bm = $this->user('branch_manager');
        $sourceId = DB::table('lead_sources')->insertGetId(['name' => 'Test Source']);
        DB::table('lead_statuses')->insert(['id' => LeadStatus::WARM, 'name' => 'Warm']);
        $lead = Lead::create(['source_id' => $sourceId, 'status_id' => LeadStatus::WARM, 'name' => 'Customer']);
        $this->claim = LeadClaim::factory()->create(['lead_id' => $lead->id, 'sales_id' => $this->sales->id]);
        $this->draft = TempQuotation::factory()->create(['lead_id' => $lead->id, 'created_by' => $this->sales->id]);
        $item = $this->draft->items()->create([
            'description' => 'Saved machine', 'qty' => 1, 'unit_price' => 1000000,
            'discount_pct' => 0, 'line_total' => 1000000, 'is_visible_pdf' => true,
        ]);
        $this->draft->items()->create([
            'description' => 'Hidden addon', 'qty' => 1, 'unit_price' => 50000,
            'discount_pct' => 0, 'line_total' => 50000, 'is_visible_pdf' => false, 'merge_into_item_id' => $item->id,
        ]);
        $this->draft->paymentTerms()->create(['term_no' => 1, 'percentage' => 40, 'description' => 'Deposit']);
        $this->draft->paymentTerms()->create(['term_no' => 2, 'percentage' => 60, 'description' => 'Delivery']);
        QuotationLog::create([
            'temp_quotation_id' => $this->draft->id, 'user_id' => $this->sales->id,
            'action' => 'draft_created', 'logged_at' => Carbon::now('Asia/Jakarta'),
        ]);
        $this->actingAs($this->sales);
    }

    protected function tearDown(): void
    {
        DB::purge('quotation_cancel_test');
        DB::setDefaultConnection($this->originalConnection);
        parent::tearDown();
    }

    private function user(string $role): User
    {
        $userRole = UserRole::create(['code' => $role, 'name' => $role]);

        return User::factory()->create(['role_id' => $userRole->id, 'branch_id' => 1]);
    }

    private function cancel(int $id, ?User $actor = null)
    {
        return $this->actingAs($actor ?? $this->sales)->postJson(route('quotations.drafts.cancel', $id));
    }

    private function submitInitial(): Quotation
    {
        $this->actingAs($this->sales)->postJson(route('leads.my.warm.quotation.final-submit', $this->claim->id),
            ['draft_id' => $this->draft->id])->assertOk();
        Notification::fake();

        return Quotation::firstOrFail();
    }

    private function revision(string $status = 'approved_by_bm'): array
    {
        $quotation = $this->submitInitial();
        $this->actingAs($this->bm)->postJson(route('quotations.'.($status === 'rejected' ? 'reject' : 'approve'), $quotation->id),
            ['revision_no' => 0, 'notes' => 'BM review notes'])->assertRedirect();
        $id = $this->actingAs($this->sales)->getJson(route('leads.my.warm.quotation.create', $this->claim->id))
            ->assertOk()->json('draft.id');
        Notification::fake();

        return [$quotation->fresh(), TempQuotation::findOrFail($id)];
    }

    private function savePayload(int $draftId): array
    {
        return [
            'draft_id' => $draftId, 'product_id' => [null], 'description' => ['Stale draft edit'],
            'qty' => [1], 'unit_price' => [2000000], 'discount_pct' => [0], 'tax_pct' => 11,
            'term_percentage' => [100], 'term_description' => ['Full payment'], 'payment_type' => 'down_payment',
        ];
    }

    public function test_discarding_r0_soft_deletes_only_the_draft_and_retains_contents_for_audit(): void
    {
        $items = $this->draft->items()->orderBy('id')->get()->map->getRawOriginal()->all();
        $terms = $this->draft->paymentTerms()->get()->map->getRawOriginal()->all();
        $lead = $this->claim->lead->getRawOriginal();

        $this->cancel($this->draft->id)->assertOk()->assertJsonPath('message', 'Draft discarded successfully')
            ->assertJsonPath('data.redirect_url', route('leads.my'));

        $deleted = TempQuotation::withTrashed()->findOrFail($this->draft->id);
        $this->assertTrue($deleted->trashed());
        $this->assertSame('draft', $deleted->status);
        $this->assertSame($items, $deleted->items()->orderBy('id')->get()->map->getRawOriginal()->all());
        $this->assertSame($terms, $deleted->paymentTerms()->get()->map->getRawOriginal()->all());
        $this->assertSame($lead, $this->claim->lead->fresh()->getRawOriginal());
        $this->assertSame(0, Quotation::count());
        $this->assertSame(0, TempQuotation::count());
        $this->assertSame(0, DB::table('document_counters')->where('type', 'quotation')->value('last_number'));
        $log = QuotationLog::where('action', 'draft_cancelled')->sole();
        $this->assertNull($log->quotation_id);
        $this->assertSame($this->sales->id, $log->user_id);
        $this->assertSame($deleted->id, $log->temp_quotation_id);
        $this->assertTrue($log->tempQuotation->trashed());
        Notification::assertNothingSent();
    }

    public static function revisionStatuses(): array
    {
        return [['rejected'], ['approved_by_bm']];
    }

    #[DataProvider('revisionStatuses')]
    public function test_cancel_revision_preserves_official_quotation_snapshot_reviews_and_proforma(string $status): void
    {
        [$quotation, $draft] = $this->revision($status);
        $draft->items()->firstOrFail()->update(['description' => 'Customer changes not submitted']);
        Proforma::create(['quotation_id' => $quotation->id, 'amount' => 1110000, 'proforma_no' => 'PF_LEGACY']);
        $tables = ['quotations', 'quotation_items', 'quotation_payment_terms', 'quotation_reviews', 'proformas'];
        $before = [];
        foreach ($tables as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->all();
        }
        $snapshot = $quotation->revisions()->where('status', 'submitted')->sole();
        $snapshotBefore = $snapshot->getRawOriginal();

        $this->cancel($draft->id)->assertOk()->assertJsonPath('message', 'Revision cancelled successfully');

        foreach ($tables as $table) {
            $this->assertEquals($before[$table], DB::table($table)->orderBy('id')->get()->all(), $table);
        }
        $this->assertSame($snapshotBefore, $snapshot->fresh()->getRawOriginal());
        $this->assertSame(0, $quotation->fresh()->revision_no);
        $this->assertSame($status, $quotation->fresh()->status);
        $this->assertSame('Saved machine', $quotation->items->first()->description);
        $this->assertNull($quotation->lead->tempQuotation);
        $log = QuotationLog::where('action', 'draft_cancelled')->sole();
        $this->assertSame($quotation->id, $log->quotation_id);
        $this->assertSame('QT_DEAL_001_R1', $log->tempQuotation->revision_code);
        $this->assertTrue($log->tempQuotation->trashed());
        Notification::assertNothingSent();
    }

    public function test_cancelled_revision_can_be_restarted_from_current_data_with_a_new_draft_id(): void
    {
        [$quotation, $draft] = $this->revision();
        $draft->items()->firstOrFail()->update(['description' => 'Discarded changes']);
        $this->cancel($draft->id)->assertOk();

        $response = $this->getJson(route('leads.my.warm.quotation.create', $this->claim->id))->assertOk()
            ->assertJsonPath('draft.revision_no', 1)->assertJsonPath('draft.items.0.description', 'Saved machine');
        $newId = $response->json('draft.id');
        $this->assertNotSame($draft->id, $newId);
        $this->cancel($draft->id)->assertStatus(409);
        $this->assertNotNull(TempQuotation::find($newId));
        $this->assertSame(0, $quotation->fresh()->revision_no);
        $this->assertSame(1, QuotationLog::where('action', 'draft_cancelled')->count());
    }

    public function test_a_submitted_snapshot_cannot_be_cancelled_or_delete_an_open_revision(): void
    {
        [$quotation, $draft] = $this->revision();
        $this->cancel($this->draft->id)->assertStatus(409);

        $this->assertNotNull(TempQuotation::find($this->draft->id));
        $this->assertNotNull(TempQuotation::find($draft->id));
        $this->assertSame('approved_by_bm', $quotation->fresh()->status);
        $this->assertSame(0, QuotationLog::where('action', 'draft_cancelled')->count());
    }

    public function test_only_sales_can_cancel_a_draft_as_specified_by_the_prd(): void
    {
        foreach ([$this->bm, $this->user('finance'), $this->user('finance_director'), $this->user('super_admin')] as $actor) {
            $this->cancel($this->draft->id, $actor)->assertForbidden();
        }
        $this->assertNotNull($this->draft->fresh());
        $this->assertSame(0, QuotationLog::where('action', 'draft_cancelled')->count());
        Notification::assertNothingSent();
    }

    public function test_cancellation_requires_the_current_active_sales_assignment(): void
    {
        $otherSales = User::factory()->create(['role_id' => $this->sales->role_id]);
        $this->cancel($this->draft->id, $otherSales)->assertForbidden();
        $this->claim->update(['released_at' => Carbon::now('Asia/Jakarta')]);
        $this->cancel($this->draft->id)->assertForbidden();
        $this->claim->update(['released_at' => null, 'trash_note' => 'Trashed']);
        $this->cancel($this->draft->id)->assertForbidden();

        $this->assertNotNull($this->draft->fresh());
        $this->assertSame(0, QuotationLog::where('action', 'draft_cancelled')->count());
    }

    public function test_cancellation_failure_rolls_back_the_soft_delete_and_audit_log(): void
    {
        [$quotation, $draft] = $this->revision();
        $before = $quotation->getRawOriginal();
        DB::unprepared("CREATE TRIGGER fail_draft_cancel BEFORE INSERT ON quotation_logs WHEN NEW.action = 'draft_cancelled' BEGIN SELECT RAISE(ABORT, 'forced cancel failure'); END");

        $this->cancel($draft->id)->assertStatus(500);

        $this->assertNotNull($draft->fresh());
        $this->assertFalse($draft->fresh()->trashed());
        $this->assertSame($before, $quotation->fresh()->getRawOriginal());
        $this->assertSame(0, QuotationLog::where('action', 'draft_cancelled')->count());
        Notification::assertNothingSent();
    }

    public function test_cancel_allows_cleanup_of_an_unsubmitted_draft_after_official_status_changes(): void
    {
        [$quotation, $draft] = $this->revision();
        $quotation->update(['status' => 'pending_finance']);
        $before = $quotation->getRawOriginal();

        $this->cancel($draft->id)->assertOk();

        $this->assertSame($before, $quotation->fresh()->getRawOriginal());
        $this->assertSame('pending_finance', $quotation->fresh()->status);
        $this->assertNull($quotation->lead->tempQuotation);
    }

    public function test_cancelling_revision_unblocks_finance_submit_without_losing_bm_approval(): void
    {
        $finance = $this->user('finance');
        $financeDirector = $this->user('finance_director');
        [$quotation, $draft] = $this->revision();
        $this->cancel($draft->id)->assertOk();

        $this->postJson(route('quotations.submit-to-finance', $quotation->id), ['revision_no' => 0])->assertOk();

        $this->assertSame('pending_finance', $quotation->fresh()->status);
        $this->assertSame(0, $quotation->fresh()->revision_no);
        $this->assertSame('BM', $quotation->currentReviews()->sole()->role);
        Notification::assertSentTo([$finance, $financeDirector], QuotationPendingFinanceNotification::class);
    }

    public function test_discard_redirects_to_my_leads_and_does_not_automatically_create_a_new_draft(): void
    {
        $this->from(route('leads.my.warm.quotation.create', $this->claim->id))
            ->post(route('quotations.drafts.cancel', $this->draft->id))
            ->assertRedirect(route('leads.my'))->assertSessionHas('status', 'Draft discarded successfully');

        $this->assertSame(0, TempQuotation::count());
        $this->assertSame(0, Quotation::count());
    }

    public function test_save_and_submit_cannot_reuse_a_cancelled_draft_id_after_a_new_draft_is_opened(): void
    {
        $this->cancel($this->draft->id)->assertOk();
        $newId = $this->getJson(route('leads.my.warm.quotation.create', $this->claim->id))->assertOk()->json('draft.id');
        $newDraftBefore = TempQuotation::findOrFail($newId)->getRawOriginal();

        $this->postJson(route('leads.my.warm.quotation.store', $this->claim->id), $this->savePayload($this->draft->id))
            ->assertStatus(409)->assertJsonPath('message', 'This draft is no longer active. Reload the quotation.');
        $this->postJson(route('leads.my.warm.quotation.final-submit', $this->claim->id), ['draft_id' => $this->draft->id])
            ->assertStatus(409)->assertJsonPath('message', 'This draft is no longer active. Reload the quotation.');

        $this->assertSame($newDraftBefore, TempQuotation::findOrFail($newId)->getRawOriginal());
        $this->assertSame(0, Quotation::count());
        $this->assertSame(0, DB::table('document_counters')->where('type', 'quotation')->value('last_number'));
    }

    public function test_saving_or_submitting_after_discard_without_a_replacement_is_rejected(): void
    {
        $this->cancel($this->draft->id)->assertOk();

        $this->postJson(route('leads.my.warm.quotation.store', $this->claim->id), $this->savePayload($this->draft->id))->assertStatus(409);
        $this->postJson(route('leads.my.warm.quotation.final-submit', $this->claim->id), ['draft_id' => $this->draft->id])->assertStatus(409);

        $this->assertSame(0, Quotation::count());
        $this->assertSame(0, TempQuotation::count());
    }
}
