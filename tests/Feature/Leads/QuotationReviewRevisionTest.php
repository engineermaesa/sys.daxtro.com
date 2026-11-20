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
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
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
        $this->assertSame('pending_finance', $this->quotation->fresh()->status);
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
        Storage::fake('public');
        File::partialMock()->shouldReceive('exists')->with(storage_path('app/public/proformas'))->andReturnTrue();
        $view = \Mockery::mock(\Illuminate\Contracts\View\View::class);
        $view->shouldReceive('render')->andReturn('<p>Proforma</p>');
        View::partialMock()->shouldReceive('make')->with('pdfs.proforma', \Mockery::type('array'), [])->andReturn($view);
        Pdf::shouldReceive('loadHTML')->andReturnSelf();
        Pdf::shouldReceive('setPaper')->with('A4', 'portrait')->andReturnSelf();
        Pdf::shouldReceive('output')->andReturn('%PDF-1.4 test');

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
}
