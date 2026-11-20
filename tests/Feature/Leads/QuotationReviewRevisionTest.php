<?php

namespace Tests\Feature\Leads;

use App\Models\Leads\Lead;
use App\Models\Leads\LeadClaim;
use App\Models\Leads\LeadStatus;
use App\Models\Orders\Quotation;
use App\Models\Orders\QuotationLog;
use App\Models\Orders\QuotationReview;
use App\Models\Orders\TempQuotation;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\Orders\QuotationPendingFinanceNotification;
use App\Notifications\Orders\QuotationReviewedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\BuildsQuotationSubmissionSchema;
use Tests\TestCase;

class QuotationReviewRevisionTest extends TestCase
{
    use BuildsQuotationSubmissionSchema;

    private string $originalConnection;
    private User $sales;
    private User $bm;
    private User $finance;
    private User $financeDirector;
    private LeadClaim $claim;
    private Quotation $quotation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = DB::getDefaultConnection();
        config(['database.connections.quotation_review_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('quotation_review_test');
        $this->buildQuotationSubmissionSchema();
        Notification::fake();
        $this->sales = $this->user('sales');
        $this->bm = $this->user('branch_manager');
        $this->finance = $this->user('finance');
        $this->financeDirector = $this->user('finance_director');
        $source = DB::table('lead_sources')->insertGetId(['name' => 'Test Source']);
        DB::table('lead_statuses')->insert(['id' => LeadStatus::WARM, 'name' => 'Warm']);
        $lead = Lead::create(['source_id' => $source, 'status_id' => LeadStatus::WARM, 'name' => 'Customer']);
        $this->claim = LeadClaim::factory()->create(['lead_id' => $lead->id, 'sales_id' => $this->sales->id]);
        $draft = TempQuotation::factory()->create(['lead_id' => $lead->id, 'created_by' => $this->sales->id]);
        $draft->items()->create([
            'qty' => 1, 'description' => 'Machine', 'unit_price' => 1000000,
            'discount_pct' => 0, 'line_total' => 1000000, 'is_visible_pdf' => true,
        ]);
        $draft->paymentTerms()->create(['term_no' => 1, 'percentage' => 100]);
        $this->actingAs($this->sales)
            ->postJson(route('leads.my.warm.quotation.final-submit', $this->claim->id))->assertOk();
        $this->quotation = Quotation::firstOrFail();
        Notification::fake();
    }

    protected function tearDown(): void
    {
        DB::purge('quotation_review_test');
        DB::setDefaultConnection($this->originalConnection);
        parent::tearDown();
    }

    private function user(string $role): User
    {
        $userRole = UserRole::create(['code' => $role, 'name' => $role]);

        return User::factory()->create(['role_id' => $userRole->id, 'branch_id' => 1]);
    }

    private function edit(): void
    {
        $this->actingAs($this->sales)->postJson(route('leads.my.warm.quotation.store', $this->claim->id), [
            'revision_no' => $this->quotation->fresh()->revision_no,
            'product_id' => [null], 'description' => ['Revised machine'], 'qty' => [1],
            'unit_price' => [2000000], 'discount_pct' => [0], 'tax_pct' => 11,
            'payment_type' => 'down_payment', 'term_percentage' => [100], 'term_description' => ['Full payment'],
        ])->assertOk();
        $this->quotation->refresh();
    }

    private function review(User $actor, string $decision, int $revision, string $notes = 'Review notes')
    {
        return $this->actingAs($actor)->postJson(route('quotations.'.$decision, $this->quotation->id), [
            'revision_no' => $revision, 'notes' => $notes,
        ]);
    }

    private function historicApproval(int $revision, string $role = 'BM'): QuotationReview
    {
        return QuotationReview::create([
            'quotation_id' => $this->quotation->id, 'revision_no' => $revision,
            'reviewer_id' => $role === 'BM' ? $this->bm->id : $this->finance->id,
            'role' => $role, 'decision' => 'approve', 'notes' => 'Historic approval',
            'decided_at' => Carbon::parse('2026-10-01 10:00:00', 'Asia/Jakarta'),
        ]);
    }

    private function fakeProformaFiles(): void
    {
        Storage::fake('public');
        File::partialMock()->shouldReceive('exists')->with(storage_path('app/public/proformas'))->andReturnTrue();
        $view = \Mockery::mock(\Illuminate\Contracts\View\View::class);
        $view->shouldReceive('render')->andReturn('<p>Proforma</p>');
        View::partialMock()->shouldReceive('make')->with('pdfs.proforma', \Mockery::type('array'), [])->andReturn($view);
        Pdf::shouldReceive('loadHTML')->andReturnSelf();
        Pdf::shouldReceive('setPaper')->with('A4', 'portrait')->andReturnSelf();
        Pdf::shouldReceive('output')->andReturn('%PDF-1.4 test');
    }

    public function test_bm_reject_then_revision_and_approval_preserve_both_decisions_with_their_revision(): void
    {
        $this->review($this->bm, 'reject', 0, 'Change specifications')->assertRedirect();
        $old = $this->quotation->currentReviews()->sole();
        $this->assertSame(0, $old->revision_no);
        $this->actingAs($this->sales)->getJson(route('leads.my.warm.quotation.create', $this->claim->id))->assertOk();
        $this->postJson(route('leads.my.warm.quotation.final-submit', $this->claim->id))->assertOk();
        $this->review($this->bm, 'approve', 1, 'Revised specifications approved')->assertRedirect();

        $current = $this->quotation->currentReviews()->sole();
        $this->assertSame(1, $current->revision_no);
        $this->assertSame('approve', $current->decision);
        $this->assertSame($this->bm->id, $current->reviewer_id);
        $this->assertSame('Change specifications', $old->fresh()->notes);
        $this->assertTrue($old->fresh()->trashed());
        $this->assertSame(0, $old->fresh()->revision_no);
        $this->assertSame([1, 0], $this->quotation->reviewHistory()->pluck('revision_no')->all());
        $this->assertSame(2, $this->quotation->reviewHistory()->count());
        $log = QuotationLog::where('action', 'approve')->sole();
        $this->assertSame('QT_DEAL_001_R1', $log->tempQuotation->revision_code);
        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
    }

    public static function financeRoles(): array
    {
        return [['finance'], ['finance_director']];
    }

    #[DataProvider('financeRoles')]
    public function test_finance_rejection_is_attached_to_the_current_revision(string $role): void
    {
        $this->edit();
        $this->quotation->update(['status' => 'pending_finance']);
        $this->historicApproval(1);
        $actor = $role === 'finance' ? $this->finance : $this->financeDirector;

        $this->review($actor, 'reject', 1, 'Reconsider payment terms')->assertRedirect();

        $decision = $this->quotation->currentReviews()->where('role', 'FIN')->sole();
        $this->assertSame(1, $decision->revision_no);
        $this->assertSame($actor->id, $decision->reviewer_id);
        $this->assertSame('Reconsider payment terms', $decision->notes);
        $this->assertSame('rejected', $this->quotation->fresh()->status);
        $this->assertSame('QT_DEAL_001_R1', QuotationLog::where('action', 'reject')->sole()->tempQuotation->revision_code);
    }

    #[DataProvider('financeRoles')]
    public function test_finance_approval_ignores_older_finance_decisions_and_records_current_revision(string $role): void
    {
        $this->edit();
        $old = $this->historicApproval(0, 'FIN');
        $this->historicApproval(1);
        $this->quotation->update(['status' => 'pending_finance']);
        $actor = $role === 'finance' ? $this->finance : $this->financeDirector;
        $this->fakeProformaFiles();

        $this->review($actor, 'approve', 1, 'Finance approved R1')->assertRedirect();

        $decision = $this->quotation->currentReviews()->where('role', 'FIN')->sole();
        $this->assertSame(1, $decision->revision_no);
        $this->assertSame($actor->id, $decision->reviewer_id);
        $this->assertSame('Finance approved R1', $decision->notes);
        $this->assertSame(0, $old->fresh()->revision_no);
        $this->assertSame('Historic approval', $old->fresh()->notes);
        $this->assertSame('published', $this->quotation->fresh()->status);
        $this->assertSame(3, $this->quotation->reviewHistory()->count());
        $this->assertSame('QT_DEAL_001_R1', QuotationLog::where('action', 'approve')->sole()->tempQuotation->revision_code);
    }

    public function test_bm_approval_from_an_older_revision_cannot_authorize_finance_approval(): void
    {
        $this->edit();
        $old = $this->historicApproval(0);
        $this->quotation->update(['status' => 'pending_finance']);

        $this->review($this->finance, 'approve', 1)->assertForbidden();

        $this->assertSame(0, $this->quotation->currentReviews()->count());
        $this->assertSame(0, $old->fresh()->revision_no);
        $this->assertSame(1, $this->quotation->reviewHistory()->count());
        $this->assertSame('pending_finance', $this->quotation->fresh()->status);
    }

    public function test_stale_approval_and_rejection_forms_are_rejected_without_recording_a_decision(): void
    {
        $this->edit();
        Notification::fake();
        $this->review($this->bm, 'approve', 0)->assertStatus(409);
        $this->review($this->bm, 'reject', 0)->assertStatus(409);

        $this->assertSame(0, $this->quotation->reviewHistory()->count());
        $this->assertSame('review', $this->quotation->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_duplicate_decision_for_the_same_revision_is_rejected(): void
    {
        $this->review($this->bm, 'approve', 0)->assertRedirect();
        $this->review($this->bm, 'approve', 0)->assertStatus(409);
        $this->review($this->bm, 'reject', 0)->assertStatus(409);

        $this->assertSame(1, $this->quotation->reviewHistory()->count());
        $this->assertSame('approve', $this->quotation->reviewHistory()->sole()->decision);
    }

    public function test_archiving_reviews_never_reassigns_their_original_revision(): void
    {
        $old = $this->historicApproval(0);
        $this->edit();
        $old->restore();
        $current = $this->historicApproval(1);
        $this->edit();

        $this->assertSame(0, $old->fresh()->revision_no);
        $this->assertSame(1, $current->fresh()->revision_no);
        $this->assertSame('Historic approval', $old->fresh()->notes);
        $this->assertTrue($old->fresh()->trashed());
        $this->assertTrue($current->fresh()->trashed());
        $this->assertSame(0, $this->quotation->currentReviews()->count());
        $this->assertSame([1, 0], $this->quotation->reviewHistory()->pluck('revision_no')->all());
    }

    public function test_current_review_eager_loading_is_scoped_independently_for_each_quotation(): void
    {
        $this->edit();
        $this->historicApproval(0);
        $first = $this->historicApproval(1);
        $other = Quotation::factory()->create([
            'lead_id' => $this->claim->lead_id, 'created_by' => $this->sales->id,
            'quotation_no' => 'QT_DEAL_002', 'revision_no' => 2, 'revision_code' => 'QT_DEAL_002_R2',
        ]);
        $second = QuotationReview::create(['quotation_id' => $other->id, 'revision_no' => 2, 'role' => 'FIN', 'decision' => 'reject']);
        QuotationReview::create(['quotation_id' => $other->id, 'revision_no' => 1, 'role' => 'BM', 'decision' => 'approve']);

        $quotations = Quotation::with('currentReviews')->get()->keyBy('id');

        $this->assertSame($first->id, $quotations[$this->quotation->id]->currentReviews->sole()->id);
        $this->assertSame($second->id, $quotations[$other->id]->currentReviews->sole()->id);
    }

    public function test_review_and_status_roll_back_if_audit_log_insertion_fails(): void
    {
        DB::unprepared("CREATE TRIGGER fail_review_log BEFORE INSERT ON quotation_logs WHEN NEW.action IN ('approve', 'reject') BEGIN SELECT RAISE(ABORT, 'forced review failure'); END");
        foreach (['approve', 'reject'] as $decision) {
            $this->review($this->bm, $decision, 0)->assertStatus(500);
            $this->assertSame(0, $this->quotation->reviewHistory()->count());
            $this->assertSame('review', $this->quotation->fresh()->status);
        }
        Notification::assertNothingSent();
    }

    public function test_bm_approval_waits_for_customer_and_notifies_only_sales(): void
    {
        $expiry = $this->quotation->expiry_date->toDateString();
        $this->review($this->bm, 'approve', 0, 'Approved for customer presentation')->assertRedirect();

        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
        $this->assertSame(0, $this->quotation->fresh()->revision_no);
        $this->assertSame($expiry, $this->quotation->fresh()->expiry_date->toDateString());
        $this->assertSame(0, DB::table('proformas')->count());
        $this->assertSame(0, DB::table('user_balance_logs')->count());
        Notification::assertSentTo($this->sales, QuotationReviewedNotification::class, function ($notification, $channels) {
            $data = $notification->toDatabase($this->sales);
            $this->assertSame(['database', 'broadcast'], $channels);
            $this->assertSame('quotation_reviewed', $data['type']);
            $this->assertSame('approved_by_bm', $data['quotation_status']);
            $this->assertSame('BM', $data['reviewer_role']);
            $this->assertSame('approve', $data['decision']);
            $this->assertSame(0, $data['revision_no']);
            $this->assertSame('QT_DEAL_001_R0', $data['revision_code']);
            $this->assertTrue($data['ready_for_customer']);
            $this->assertSame('Quotation Approved by BM', $data['title']);
            $this->assertStringContainsString('ready to share with the customer', $data['message']);
            $this->assertSame('Approved for customer presentation', $data['notes']);
            $this->assertSame(route('quotations.show', $this->quotation->id), $data['url']);
            $this->assertSame($data, $notification->toBroadcast($this->sales));

            return true;
        });
        Notification::assertNotSentTo([$this->finance, $this->financeDirector], QuotationPendingFinanceNotification::class);
        Notification::assertNotSentTo([$this->finance, $this->financeDirector], QuotationReviewedNotification::class);
        Notification::assertCount(1);
    }

    public function test_finance_cannot_review_until_the_bm_approved_quotation_is_submitted_to_finance(): void
    {
        $this->review($this->bm, 'approve', 0)->assertRedirect();
        foreach ([$this->finance, $this->financeDirector] as $actor) {
            $this->review($actor, 'approve', 0)->assertStatus(409);
            $this->review($actor, 'reject', 0)->assertStatus(409);
        }

        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
        $this->assertSame(1, $this->quotation->currentReviews()->count());
        $this->assertSame('BM', $this->quotation->currentReviews()->sole()->role);
    }

    public function test_customer_revision_requires_bm_approval_again_and_preserves_the_original_notification(): void
    {
        $this->review($this->bm, 'approve', 0, 'R0 approved')->assertRedirect();
        $originalNotification = Notification::sent($this->sales, QuotationReviewedNotification::class)->sole();
        $this->actingAs($this->sales)->getJson(route('leads.my.warm.quotation.create', $this->claim->id))->assertOk()
            ->assertJsonPath('draft.revision_no', 1);
        $this->postJson(route('leads.my.warm.quotation.final-submit', $this->claim->id))->assertOk();
        $this->assertSame('review', $this->quotation->fresh()->status);
        $this->review($this->bm, 'approve', 1, 'R1 approved')->assertRedirect();

        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
        $this->assertSame([1, 0], $this->quotation->reviewHistory()->pluck('revision_no')->all());
        $this->assertSame('QT_DEAL_001_R0', $originalNotification->toDatabase($this->sales)['revision_code']);
        Notification::assertSentTo($this->sales, QuotationReviewedNotification::class,
            fn ($notification) => $notification->toDatabase($this->sales)['revision_code'] === 'QT_DEAL_001_R1'
                && $notification->toDatabase($this->sales)['ready_for_customer']);
        Notification::assertNotSentTo([$this->finance, $this->financeDirector], QuotationPendingFinanceNotification::class);
    }

    public function test_status_migration_preserves_legacy_finance_queue_and_related_records(): void
    {
        $this->quotation->update(['status' => 'pending_finance']);
        $this->historicApproval(0);
        $tables = ['quotations', 'quotation_items', 'quotation_payment_terms', 'quotation_reviews',
            'temp_quotations', 'temp_quotation_items', 'temp_quotation_payment_terms', 'quotation_logs'];
        $before = [];
        foreach ($tables as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->all();
        }
        $migration = require database_path('migrations/2026_11_20_000300_add_bm_approved_quotation_status.php');

        $migration->up();
        $migration->up();

        foreach ($tables as $table) {
            $this->assertEquals($before[$table], DB::table($table)->orderBy('id')->get()->all(), $table);
        }
        $this->assertSame('pending_finance', $this->quotation->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_status_migration_can_roll_back_without_rewriting_existing_quotations(): void
    {
        $migration = require database_path('migrations/2026_11_20_000300_add_bm_approved_quotation_status.php');
        $migration->down();
        $this->assertSame('review', $this->quotation->fresh()->status);
        $this->assertSame('Machine', $this->quotation->items->sole()->description);
        $this->assertSame(1, $this->quotation->revisions()->count());
        try {
            DB::table('quotations')->where('id', $this->quotation->id)->update(['status' => 'approved_by_bm']);
            $this->fail('The legacy status constraint must reject approved_by_bm.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('CHECK constraint failed', $exception->getMessage());
        }
        $migration->up();
        $this->quotation->update(['status' => 'approved_by_bm']);
        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
    }

    public function test_status_rollback_refuses_to_discard_bm_approval_including_soft_deleted_quotations(): void
    {
        $this->quotation->update(['status' => 'approved_by_bm']);
        $this->quotation->delete();
        $migration = require database_path('migrations/2026_11_20_000300_add_bm_approved_quotation_status.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('workflow state');
        $migration->down();
    }

    private function approveForCustomer(): void
    {
        $this->review($this->bm, 'approve', $this->quotation->fresh()->revision_no, 'BM approval notes')->assertRedirect();
        Notification::fake();
    }

    private function submitFinance(User $actor, array $data = ['revision_no' => 0])
    {
        return $this->actingAs($actor)->postJson(route('quotations.submit-to-finance', $this->quotation->id), $data);
    }

    public static function submitterRoles(): array
    {
        return [['sales'], ['branch_manager']];
    }

    #[DataProvider('submitterRoles')]
    public function test_sales_and_bm_can_submit_the_approved_current_revision_to_finance(string $role): void
    {
        $this->approveForCustomer();
        $before = $this->quotation->fresh()->only([
            'quotation_no', 'revision_no', 'revision_code', 'grand_total', 'booking_fee', 'expiry_date', 'created_by',
        ]);
        $actor = $role === 'sales' ? $this->sales : $this->bm;

        $this->submitFinance($actor)->assertOk()->assertJsonPath('data.status', 'pending_finance')
            ->assertJsonPath('data.revision_code', 'QT_DEAL_001_R0');

        $this->assertSame('pending_finance', $this->quotation->fresh()->status);
        $this->assertEquals($before, $this->quotation->fresh()->only(array_keys($before)));
        $this->assertSame(1, $this->quotation->revisions()->count());
        $this->assertSame(1, $this->quotation->currentReviews()->count());
        $log = QuotationLog::where('action', 'submit_to_finance')->sole();
        $this->assertSame($actor->id, $log->user_id);
        $this->assertSame('QT_DEAL_001_R0', $log->tempQuotation->revision_code);
        $this->assertSame(0, DB::table('proformas')->count());
        $this->assertSame(0, DB::table('user_balance_logs')->count());
        Notification::assertSentTo([$this->finance, $this->financeDirector], QuotationPendingFinanceNotification::class,
            function ($notification) {
                $this->assertSame($this->bm->id, $notification->branchManager->id);
                $this->assertSame($this->sales->id, $notification->sales->id);
                $this->assertSame('BM approval notes', $notification->notes);
                $this->assertSame('pending_finance', $notification->quotation->status);
                $this->assertSame('QT_DEAL_001_R0', $notification->quotation->revision_code);

                return true;
            });
        Notification::assertCount(2);
    }

    public function test_submit_to_finance_requires_sales_or_bm_role(): void
    {
        $this->approveForCustomer();
        foreach ([$this->finance, $this->financeDirector, $this->user('super_admin'), $this->user('sales_director')] as $actor) {
            $this->submitFinance($actor)->assertForbidden();
        }
        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
        $this->assertSame(0, QuotationLog::where('action', 'submit_to_finance')->count());
        Notification::assertNothingSent();
    }

    public function test_only_bm_approved_status_can_be_submitted_to_finance(): void
    {
        foreach (['draft', 'review', 'pending_finance', 'published', 'rejected', 'expired'] as $status) {
            $this->quotation->update(['status' => $status]);
            $this->submitFinance($this->sales)->assertStatus(409);
            $this->assertSame($status, $this->quotation->fresh()->status);
        }
        $this->assertSame(0, QuotationLog::where('action', 'submit_to_finance')->count());
        Notification::assertNothingSent();
    }

    public function test_submit_requires_an_explicit_valid_revision_number(): void
    {
        $this->approveForCustomer();
        foreach ([[], ['revision_no' => -1], ['revision_no' => 'invalid']] as $payload) {
            $this->submitFinance($this->sales, $payload)->assertUnprocessable();
        }
        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_stale_submit_is_rejected_and_the_current_revision_can_be_submitted(): void
    {
        $this->approveForCustomer();
        $this->actingAs($this->sales)->getJson(route('leads.my.warm.quotation.create', $this->claim->id))->assertOk();
        $this->postJson(route('leads.my.warm.quotation.final-submit', $this->claim->id))->assertOk();
        $this->approveForCustomer();

        $this->submitFinance($this->sales, ['revision_no' => 0])->assertStatus(409);
        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
        Notification::assertNothingSent();
        $this->submitFinance($this->sales, ['revision_no' => 1])->assertOk();

        $this->assertSame('QT_DEAL_001_R1', QuotationLog::where('action', 'submit_to_finance')->sole()->tempQuotation->revision_code);
        $this->assertSame(2, $this->quotation->revisions()->count());
    }

    public function test_open_revision_blocks_finance_submit_including_unlinked_legacy_drafts(): void
    {
        $this->approveForCustomer();
        $draftId = $this->actingAs($this->sales)
            ->getJson(route('leads.my.warm.quotation.create', $this->claim->id))->assertOk()->json('draft.id');
        $this->submitFinance($this->sales)->assertStatus(409)
            ->assertJsonPath('message', 'An open revision exists. Submit or cancel that revision before submitting to Finance.');
        TempQuotation::findOrFail($draftId)->delete();
        $legacyDraft = TempQuotation::factory()->create([
            'lead_id' => $this->claim->lead_id, 'created_by' => $this->sales->id, 'status' => 'draft',
        ]);
        $this->submitFinance($this->bm)->assertStatus(409);
        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
        $this->assertSame(0, QuotationLog::where('action', 'submit_to_finance')->count());
        Notification::assertNothingSent();
        $legacyDraft->delete();

        $this->submitFinance($this->sales)->assertOk();
    }

    public function test_repeat_finance_submit_does_not_duplicate_logs_or_notifications(): void
    {
        $this->approveForCustomer();
        $this->submitFinance($this->sales)->assertOk();
        $this->submitFinance($this->bm)->assertStatus(409);

        $this->assertSame(1, QuotationLog::where('action', 'submit_to_finance')->count());
        Notification::assertCount(2);
    }

    public function test_submit_checks_sales_assignment_and_bm_branch(): void
    {
        $this->approveForCustomer();
        $otherSales = User::factory()->create(['role_id' => $this->sales->role_id, 'branch_id' => 1]);
        $otherBm = User::factory()->create(['role_id' => $this->bm->role_id, 'branch_id' => 2]);
        $this->submitFinance($otherSales)->assertForbidden();
        $this->submitFinance($otherBm)->assertForbidden();
        $this->claim->update(['released_at' => Carbon::now('Asia/Jakarta')]);
        $this->submitFinance($this->sales)->assertForbidden();
        $this->claim->update(['released_at' => null, 'trash_note' => 'Trashed']);
        $this->submitFinance($this->sales)->assertForbidden();

        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_submit_requires_bm_approval_for_the_current_revision(): void
    {
        $this->edit();
        $this->historicApproval(0);
        $this->quotation->update(['status' => 'approved_by_bm']);
        Notification::fake();

        $this->submitFinance($this->sales, ['revision_no' => 1])->assertStatus(409)
            ->assertJsonPath('message', 'BM approval is required for the current revision.');

        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_submit_requires_a_snapshot_for_the_current_approved_revision(): void
    {
        $this->edit();
        $this->approveForCustomer();
        $this->quotation->revisions()->where('revision_no', 1)->delete();

        $this->submitFinance($this->sales, ['revision_no' => 1])->assertStatus(409)
            ->assertJsonPath('message', 'The current revision snapshot is unavailable. Reload the quotation.');

        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_submit_rolls_back_status_if_audit_log_insertion_fails(): void
    {
        $this->approveForCustomer();
        DB::unprepared("CREATE TRIGGER fail_finance_submit BEFORE INSERT ON quotation_logs WHEN NEW.action = 'submit_to_finance' BEGIN SELECT RAISE(ABORT, 'forced submit failure'); END");

        $this->submitFinance($this->sales)->assertStatus(500);

        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
        $this->assertSame(0, QuotationLog::where('action', 'submit_to_finance')->count());
        $this->assertSame(1, $this->quotation->currentReviews()->count());
        Notification::assertNothingSent();
    }

    public function test_regular_submit_form_redirects_back_instead_of_displaying_json(): void
    {
        $this->approveForCustomer();
        $this->actingAs($this->sales)->from(route('leads.my'))->post(
            route('quotations.submit-to-finance', $this->quotation->id), ['revision_no' => 0]
        )->assertRedirect(route('leads.my'))->assertSessionHas('status', 'Quotation submitted to Finance successfully');

        $this->assertSame('pending_finance', $this->quotation->fresh()->status);
    }

    public function test_full_bm_customer_finance_flow_publishes_only_after_explicit_submit_and_finance_approval(): void
    {
        $this->approveForCustomer();
        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
        $this->assertSame(0, DB::table('proformas')->count());
        $this->submitFinance($this->sales)->assertOk();
        $this->assertSame('pending_finance', $this->quotation->fresh()->status);
        $this->assertSame(0, DB::table('proformas')->count());
        $this->fakeProformaFiles();

        $this->review($this->financeDirector, 'approve', 0, 'Finance approved after customer acceptance')->assertRedirect();

        $this->assertSame('published', $this->quotation->fresh()->status);
        $this->assertSame(1, DB::table('proformas')->count());
        $this->assertSame(2, $this->quotation->currentReviews()->count());
        $this->assertSame(1, QuotationLog::where('action', 'submit_to_finance')->count());
        $this->assertSame(0, $this->quotation->currentReviews()->where('role', 'FIN')->sole()->revision_no);
    }
}
