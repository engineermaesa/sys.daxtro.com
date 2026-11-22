<?php

namespace Tests\Feature\Leads;

use App\Models\Leads\Lead;
use App\Models\Leads\LeadClaim;
use App\Models\Leads\LeadStatus;
use App\Models\Orders\Proforma;
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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\BuildsQuotationSubmissionSchema;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('quotation-revision')]
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
        $actor->update(['branch_id' => 2]);

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
        $actor->update(['branch_id' => 2]);
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
            function ($notification, $channels) use ($actor) {
                $this->assertSame($this->bm->id, $notification->branchManager->id);
                $this->assertSame($this->sales->id, $notification->sales->id);
                $this->assertSame('BM approval notes', $notification->notes);
                $this->assertSame('pending_finance', $notification->quotation->status);
                $this->assertSame('QT_DEAL_001_R0', $notification->quotation->revision_code);
                $payload = $notification->toDatabase($this->finance);
                $this->assertSame(['database', 'broadcast'], $channels);
                $this->assertSame(0, $payload['revision_no']);
                $this->assertSame('QT_DEAL_001_R0', $payload['revision_code']);
                $this->assertSame('pending_finance', $payload['quotation_status']);
                $this->assertSame('Quotation Pending Finance Approval', $payload['title']);
                $this->assertStringContainsString('after customer acceptance', $payload['message']);
                $this->assertSame($actor->id, $payload['submitted_by']);
                $this->assertSame($actor->name, $payload['submitted_by_name']);
                $this->assertSame('BM approval notes', $payload['notes']);
                $approval = $this->quotation->currentReviews()->where('role', 'BM')->sole();
                $this->assertSame($approval->id, $payload['bm_approval_id']);
                $this->assertSame($approval->decided_at->toIso8601String(), $payload['bm_approved_at']);
                $this->assertSame(route('quotations.show', $this->quotation->id), $payload['url']);
                $this->assertSame($payload, $notification->toBroadcast($this->financeDirector));
                $this->assertSame('quotation.pending-finance', $notification->broadcastAs());

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

    #[\PHPUnit\Framework\Attributes\Group('quotation-uat')]
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

    public function test_finance_notification_uses_current_bm_notes_and_reviewer_instead_of_request_values(): void
    {
        $this->review($this->bm, 'approve', 0, 'Old R0 notes')->assertRedirect();
        $oldApproval = $this->quotation->currentReviews()->sole();
        $this->actingAs($this->sales)->getJson(route('leads.my.warm.quotation.create', $this->claim->id))->assertOk();
        $this->postJson(route('leads.my.warm.quotation.final-submit', $this->claim->id))->assertOk();
        $newBm = User::factory()->create(['role_id' => $this->bm->role_id, 'branch_id' => 1]);
        $this->review($newBm, 'approve', 1, 'R1 approved: 40% deposit, 60% delivery')->assertRedirect();
        $currentApproval = $this->quotation->currentReviews()->sole();
        Notification::fake();

        $this->submitFinance($this->bm, [
            'revision_no' => 1, 'notes' => 'Forged client notes', 'bm_approval_id' => $oldApproval->id,
        ])->assertOk();

        $notification = Notification::sent($this->finance, QuotationPendingFinanceNotification::class)->sole();
        $payload = $notification->toDatabase($this->finance);
        $this->assertSame('QT_DEAL_001_R1', $payload['revision_code']);
        $this->assertSame('R1 approved: 40% deposit, 60% delivery', $payload['notes']);
        $this->assertSame($newBm->name, $payload['branch_manager_name']);
        $this->assertSame($currentApproval->id, $payload['bm_approval_id']);
        $this->assertSame($this->bm->id, $payload['submitted_by']);
        $this->assertSame($this->bm->name, $payload['submitted_by_name']);
        $this->assertSame($payload, $notification->toBroadcast($this->financeDirector));
    }

    public function test_finance_notifications_wait_for_the_enclosing_transaction_to_commit(): void
    {
        $this->approveForCustomer();
        DB::beginTransaction();
        try {
            $this->submitFinance($this->sales)->assertOk();
            Notification::assertNothingSent();
            DB::commit();
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }

        Notification::assertSentTo([$this->finance, $this->financeDirector], QuotationPendingFinanceNotification::class);
        Notification::assertCount(2);
    }

    public function test_outer_transaction_rollback_discards_finance_notifications(): void
    {
        $this->approveForCustomer();
        DB::beginTransaction();
        try {
            $this->submitFinance($this->sales)->assertOk();
            Notification::assertNothingSent();
        } finally {
            DB::rollBack();
        }

        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
        $this->assertSame(0, QuotationLog::where('action', 'submit_to_finance')->count());
        Notification::assertNothingSent();
    }

    public function test_active_finance_recipients_are_not_restricted_to_the_lead_branch(): void
    {
        $this->approveForCustomer();
        $this->finance->delete();
        $this->financeDirector->update(['branch_id' => 2]);
        $otherFinance = User::factory()->create(['role_id' => $this->finance->role_id, 'branch_id' => 3]);

        $this->submitFinance($this->sales)->assertOk();

        Notification::assertSentTo([$this->financeDirector, $otherFinance], QuotationPendingFinanceNotification::class);
        Notification::assertNotSentTo([$this->finance, $this->sales, $this->bm], QuotationPendingFinanceNotification::class);
        Notification::assertCount(2);
    }

    public function test_finance_notification_payload_stays_fixed_when_its_models_change(): void
    {
        $this->approveForCustomer();
        $this->submitFinance($this->sales)->assertOk();
        $notification = Notification::sent($this->finance, QuotationPendingFinanceNotification::class)->sole();
        $before = $notification->toDatabase($this->finance);

        $notification->quotation->forceFill(['revision_no' => 99, 'revision_code' => 'QT_DEAL_001_R99', 'status' => 'review']);
        $notification->sales->name = 'Changed sales name';
        $notification->branchManager->name = 'Changed BM name';
        $notification->submittedBy->name = 'Changed submitter name';
        $notification->bmApproval->notes = 'Changed approval notes';

        $this->assertSame($before, $notification->toDatabase($this->finance));
        $this->assertSame($before, $notification->toBroadcast($this->financeDirector));
        $restored = unserialize(serialize($notification));
        $this->assertSame($before, $restored->toBroadcast($this->financeDirector));
        $this->assertSame('QT_DEAL_001_R0', $before['revision_code']);
        $this->assertSame('BM approval notes', $before['notes']);
    }

    public function test_database_channel_persists_the_same_finance_payload_as_broadcast(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        $this->approveForCustomer();
        $this->submitFinance($this->sales)->assertOk();
        $notification = Notification::sent($this->finance, QuotationPendingFinanceNotification::class)->sole();
        $notification->id = (string) Str::uuid();

        (new DatabaseChannel)->send($this->finance, $notification);

        $stored = $this->finance->notifications()->sole();
        $this->assertSame(QuotationPendingFinanceNotification::class, $stored->type);
        $this->assertSame($notification->toBroadcast($this->finance), $stored->data);
        $this->assertSame('BM approval notes', $stored->data['notes']);
        $this->assertSame('QT_DEAL_001_R0', $stored->data['revision_code']);
    }

    public function test_unapproved_roles_cannot_approve_or_reject_even_with_missing_payload(): void
    {
        $actors = [$this->sales, $this->user('super_admin'), $this->user('sales_director'),
            $this->user('accountant'), $this->user('purchasing')];
        foreach ($actors as $actor) {
            foreach (['approve', 'reject'] as $decision) {
                $this->review($actor, $decision, 0)->assertForbidden();
                $this->actingAs($actor)->postJson(route('quotations.'.$decision, $this->quotation->id), [])->assertForbidden();
            }
        }
        $this->assertSame('review', $this->quotation->fresh()->status);
        $this->assertSame(0, $this->quotation->reviewHistory()->count());
        Notification::assertNothingSent();
    }

    public function test_each_reviewer_role_is_restricted_to_its_own_approval_stage(): void
    {
        foreach ([$this->bm, $this->finance, $this->financeDirector] as $actor) {
            $allowedStatus = $actor->is($this->bm) ? 'review' : 'pending_finance';
            foreach (['draft', 'review', 'approved_by_bm', 'pending_finance', 'published', 'rejected', 'expired'] as $status) {
                if ($status === $allowedStatus) {
                    continue;
                }
                $this->quotation->update(['status' => $status]);
                foreach (['approve', 'reject'] as $decision) {
                    $this->review($actor, $decision, 0)->assertStatus(409);
                    $this->assertSame($status, $this->quotation->fresh()->status);
                }
            }
        }
        $this->assertSame(0, $this->quotation->reviewHistory()->count());
        $this->assertSame(0, QuotationLog::whereIn('action', ['approve', 'reject'])->count());
        Notification::assertNothingSent();
    }

    public function test_approve_and_reject_require_non_blank_text_notes_for_all_reviewer_roles(): void
    {
        $payloads = [
            ['revision_no' => 0],
            ['revision_no' => 0, 'notes' => null],
            ['revision_no' => 0, 'notes' => ''],
            ['revision_no' => 0, 'notes' => " \t\r\n "],
            ['revision_no' => 0, 'notes' => "\u{00A0}\u{200B}\u{FEFF}"],
            ['revision_no' => 0, 'notes' => ['not text']],
            ['revision_no' => 0, 'notes' => 123],
        ];
        foreach ([$this->bm, $this->finance, $this->financeDirector] as $actor) {
            $status = $actor->is($this->bm) ? 'review' : 'pending_finance';
            $this->quotation->update(['status' => $status]);
            if ($status === 'pending_finance' && ! $this->quotation->currentReviews()->where('role', 'BM')->exists()) {
                $this->historicApproval(0);
            }
            $reviewCount = $this->quotation->reviewHistory()->count();
            foreach (['approve', 'reject'] as $decision) {
                foreach ($payloads as $payload) {
                    $this->actingAs($actor)->postJson(route('quotations.'.$decision, $this->quotation->id), $payload)
                        ->assertUnprocessable()->assertJsonValidationErrors('notes');
                }
            }
            $this->assertSame($reviewCount, $this->quotation->reviewHistory()->count());
            $this->assertSame($status, $this->quotation->fresh()->status);
        }
        $this->assertSame(0, QuotationLog::whereIn('action', ['approve', 'reject'])->count());
        Notification::assertNothingSent();
    }

    public function test_approve_and_reject_require_an_explicit_integer_revision_number(): void
    {
        foreach (['approve', 'reject'] as $decision) {
            foreach ([[], ['revision_no' => null], ['revision_no' => -1], ['revision_no' => 0.5], ['revision_no' => 'invalid']] as $payload) {
                $this->actingAs($this->bm)->postJson(route('quotations.'.$decision, $this->quotation->id),
                    array_merge($payload, ['notes' => 'Valid review notes']))
                    ->assertUnprocessable()->assertJsonValidationErrors('revision_no');
            }
        }
        $this->assertSame('review', $this->quotation->fresh()->status);
        $this->assertSame(0, $this->quotation->reviewHistory()->count());
        Notification::assertNothingSent();
    }

    public function test_bm_branch_authorization_runs_before_item_validation(): void
    {
        $otherBm = User::factory()->create(['role_id' => $this->bm->role_id, 'branch_id' => 2]);
        $unassignedBm = User::factory()->create(['role_id' => $this->bm->role_id, 'branch_id' => 1]);
        $unassignedBm->branch_id = null;
        $this->quotation->items()->firstOrFail()->update(['discount_pct' => 150]);
        foreach ([$otherBm, $unassignedBm] as $actor) {
            foreach (['approve', 'reject'] as $decision) {
                $this->review($actor, $decision, 0)->assertForbidden();
            }
        }
        $this->assertSame('review', $this->quotation->fresh()->status);
        $this->assertSame(0, $this->quotation->reviewHistory()->count());
        Notification::assertNothingSent();
    }

    public function test_finance_decisions_require_bm_approval_for_the_same_revision(): void
    {
        $this->edit();
        $oldApproval = $this->historicApproval(0);
        $this->quotation->update(['status' => 'pending_finance']);
        Notification::fake();
        foreach ([$this->finance, $this->financeDirector] as $actor) {
            foreach (['approve', 'reject'] as $decision) {
                $this->review($actor, $decision, 1)->assertForbidden();
            }
        }
        $this->assertSame('pending_finance', $this->quotation->fresh()->status);
        $this->assertSame(0, $oldApproval->fresh()->revision_no);
        $this->assertSame(1, $this->quotation->reviewHistory()->count());
        Notification::assertNothingSent();
    }

    public function test_duplicate_role_decisions_are_blocked_even_if_status_was_reset(): void
    {
        foreach ([$this->bm, $this->finance, $this->financeDirector] as $actor) {
            $role = $actor->is($this->bm) ? 'BM' : 'FIN';
            $this->quotation->update(['status' => $role === 'BM' ? 'review' : 'pending_finance']);
            if (! $this->quotation->currentReviews()->where('role', $role)->exists()) {
                $this->historicApproval(0, $role);
            }
            $count = $this->quotation->reviewHistory()->count();
            foreach (['approve', 'reject'] as $decision) {
                $this->review($actor, $decision, 0)->assertStatus(409);
            }
            $this->assertSame($count, $this->quotation->reviewHistory()->count());
        }
        Notification::assertNothingSent();
    }

    public function test_review_stage_is_checked_before_item_validation(): void
    {
        $this->quotation->update(['status' => 'approved_by_bm']);
        $this->quotation->items()->firstOrFail()->update(['discount_pct' => 150]);
        foreach ([$this->bm, $this->finance, $this->financeDirector] as $actor) {
            foreach (['approve', 'reject'] as $decision) {
                $this->review($actor, $decision, 0)->assertStatus(409);
            }
        }
        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_my_leads_keeps_official_status_while_a_revision_is_open(): void
    {
        $controller = app(\App\Http\Controllers\Leads\WarmLeadController::class);
        $status = new \ReflectionMethod($controller, 'warmMeetingStatus');
        // A historical approval must not make a new BM review look like Finance review.
        $this->historicApproval(0);
        $this->quotation->update(['revision_no' => 1]);
        $this->assertStringContainsString('Pending BM Approval', $status->invoke($controller, $this->quotation));
        $this->quotation->update(['status' => 'pending_finance']);
        $this->assertStringContainsString('Pending Finance Approval', $status->invoke($controller, $this->quotation));

        $this->quotation->update(['status' => 'approved_by_bm']);
        $draft = app(\App\Services\Orders\QuotationRevisions::class)->startRevision($this->quotation, $this->sales->id);
        $html = $status->invoke($controller, $this->quotation, $draft);
        $this->assertStringContainsString('Approved by BM', $html);
        $this->assertStringContainsString('Revision in Progress (R2)', $html);
        $this->assertStringNotContainsString('>Draft<', $html);
        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
    }

    public function test_my_leads_menu_blocks_finance_submission_until_revision_is_cancelled(): void
    {
        $this->quotation->update(['status' => 'approved_by_bm']);
        $controller = app(\App\Http\Controllers\Leads\WarmLeadController::class);
        $actions = new \ReflectionMethod($controller, 'warmActions');
        $menu = fn () => $actions->invoke($controller, $this->claim->fresh(['lead.quotation', 'lead.tempQuotation']));
        $this->assertStringContainsString('Submit to Finance</button>', $menu());
        $this->assertStringContainsString('Revise Quotation</a>', $menu());

        $draft = app(\App\Services\Orders\QuotationRevisions::class)->startRevision($this->quotation, $this->sales->id);
        $html = $menu();
        $this->assertStringNotContainsString('Submit to Finance</button>', $html);
        $this->assertStringNotContainsString('Revise Quotation</a>', $html);
        foreach (['Continue Revision (R1)', 'Cancel Revision', 'View Quotation', 'Download Quotation', 'Quotation Log'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->actingAs($this->sales)->postJson(route('quotations.drafts.cancel', $draft->id))->assertOk();
        $this->assertStringContainsString('Submit to Finance</button>', $menu());
        $this->assertStringNotContainsString('Continue Revision', $menu());
    }

    public function test_my_leads_edit_actions_follow_existing_roles_stages_and_payment_lock(): void
    {
        $controller = app(\App\Http\Controllers\Leads\WarmLeadController::class);
        $actions = new \ReflectionMethod($controller, 'warmActions');
        $menu = fn () => $actions->invoke($controller, $this->claim->fresh(['lead.quotation', 'lead.tempQuotation']));
        foreach ([$this->sales, $this->bm] as $actor) {
            $this->actingAs($actor);
            foreach (['review', 'pending_finance'] as $stage) {
                $this->quotation->update(['status' => $stage]);
                $this->assertStringContainsString('Edit Quotation</a>', $menu());
                $this->assertStringNotContainsString('Submit to Finance</button>', $menu());
            }
        }
        foreach ([$this->finance, $this->financeDirector] as $actor) {
            $this->actingAs($actor);
            $this->assertStringNotContainsString('Edit Quotation</a>', $menu());
            $this->assertStringNotContainsString('Revise Quotation</a>', $menu());
        }
        $this->quotation->update(['status' => 'published']);
        $this->actingAs($this->sales);
        $this->assertStringNotContainsString('Edit Quotation</a>', $menu());
        $this->actingAs($this->bm);
        $this->assertStringContainsString('Edit Quotation</a>', $menu());
        $proforma = Proforma::create([
            'quotation_id' => $this->quotation->id, 'term_no' => 1, 'proforma_no' => 'PF_UI',
            'proforma_type' => 'down_payment', 'amount' => 1110000, 'status' => 'pending',
        ]);
        \App\Models\Orders\PaymentConfirmation::create(['proforma_id' => $proforma->id, 'amount' => 1110000]);
        $this->assertStringNotContainsString('Edit Quotation</a>', $menu());
    }

    public function test_review_notifications_wait_for_outer_commit_and_are_discarded_on_rollback(): void
    {
        DB::beginTransaction();
        $this->review($this->bm, 'approve', 0)->assertRedirect();
        Notification::assertNothingSent();
        DB::rollBack();
        $this->assertSame('review', $this->quotation->fresh()->status);
        $this->assertSame(0, $this->quotation->currentReviews()->count());
        Notification::assertNothingSent();

        DB::beginTransaction();
        $this->review($this->bm, 'approve', 0)->assertRedirect();
        Notification::assertNothingSent();
        DB::commit();
        Notification::assertSentTo($this->sales, QuotationReviewedNotification::class);
    }

    public function test_notification_delivery_failure_does_not_fail_a_committed_approval(): void
    {
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Notification provider unavailable'));
        $this->review($this->bm, 'approve', 0)->assertRedirect();
        $this->assertSame('approved_by_bm', $this->quotation->fresh()->status);
        $this->assertSame(1, $this->quotation->currentReviews()->count());
        $this->assertSame(1, QuotationLog::where('action', 'approve')->count());
    }

    public function test_finance_failure_restores_existing_proforma_and_removes_only_new_files(): void
    {
        $this->review($this->bm, 'approve', 0)->assertRedirect();
        $this->actingAs($this->sales)->postJson(route('quotations.submit-to-finance', $this->quotation->id), ['revision_no' => 0])->assertOk();
        $this->fakeProformaFiles();
        Storage::disk('public')->put('proformas/existing.pdf', 'Existing published document');
        $attachment = \App\Models\Attachment::create([
            'type' => 'proforma_pdf', 'file_path' => 'proformas/existing.pdf', 'mime_type' => 'application/pdf',
            'size' => 27, 'uploaded_by' => $this->finance->id,
        ]);
        $proforma = Proforma::create([
            'quotation_id' => $this->quotation->id, 'term_no' => 1, 'proforma_no' => 'PF_EXISTING',
            'proforma_type' => 'down_payment', 'amount' => 500000, 'status' => 'confirmed',
            'attachment_id' => $attachment->id,
        ]);
        $before = $proforma->fresh()->getRawOriginal();
        DB::unprepared("CREATE TRIGGER fail_finance_balance BEFORE INSERT ON user_balance_logs BEGIN SELECT RAISE(ABORT, 'forced finance failure'); END");
        Notification::fake();
        $this->review($this->finance, 'approve', 0)->assertStatus(500);
        $this->assertSame($before, $proforma->fresh()->getRawOriginal());
        $this->assertSame('pending_finance', $this->quotation->fresh()->status);
        $this->assertSame(0, $this->quotation->currentReviews()->where('role', 'FIN')->count());
        $this->assertSame(1, \App\Models\Attachment::count());
        $this->assertSame(['proformas/existing.pdf'], Storage::disk('public')->allFiles('proformas'));
        $this->assertSame('Existing published document', Storage::disk('public')->get('proformas/existing.pdf'));
        Notification::assertNothingSent();
    }

    public function test_both_review_actions_require_the_current_revision_snapshot(): void
    {
        $this->edit();
        $this->quotation->revisions()->where('revision_no', 1)->delete();
        Notification::fake();
        foreach (['approve', 'reject'] as $decision) {
            $this->review($this->bm, $decision, 1)->assertStatus(409)
                ->assertJsonPath('message', 'The current revision snapshot is unavailable. Reload the quotation.');
        }
        $this->assertSame('review', $this->quotation->fresh()->status);
        $this->assertSame(0, $this->quotation->reviewHistory()->count());
        Notification::assertNothingSent();
    }
}
