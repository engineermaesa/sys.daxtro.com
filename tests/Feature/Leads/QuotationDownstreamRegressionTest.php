<?php

namespace Tests\Feature\Leads;

use App\Models\{Attachment, User, UserBalance, UserBalanceLog, UserRole};
use App\Models\Leads\{Lead, LeadClaim, LeadStatus, LeadStatusLog};
use App\Models\Orders\{FinanceRequest, Invoice, Order, PaymentConfirmation, PaymentLog, Proforma, Quotation, QuotationItems, QuotationLog, QuotationReview, QuotationSignedDocument, TempQuotation};
use App\Notifications\Orders\PaymentConfirmationSubmittedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\{Request, UploadedFile};
use Illuminate\Support\Facades\{DB, File, Notification, Storage, View};
use PHPUnit\Framework\Attributes\{DataProvider, Group};
use Tests\Support\BuildsQuotationDownstreamSchema;
use Tests\TestCase;

#[Group('quotation-revision')]
class QuotationDownstreamRegressionTest extends TestCase
{
    use BuildsQuotationDownstreamSchema;

    private string $originalConnection;
    private User $sales;
    private User $bm;
    private User $finance;
    private User $director;
    private LeadClaim $claim;
    private Quotation $quotation;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-22 10:00:00', 'Asia/Jakarta'));
        $this->originalConnection = DB::getDefaultConnection();
        config(['database.connections.quotation_downstream_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('quotation_downstream_test');
        $this->buildQuotationDownstreamSchema();
        Notification::fake();
        Storage::fake('local');
        Storage::fake('public');
        $this->sales = $this->user('sales');
        $this->bm = $this->user('branch_manager');
        $this->finance = $this->user('finance');
        $this->director = $this->user('finance_director');
        $source = DB::table('lead_sources')->insertGetId(['name' => 'Regression Source']);
        foreach ([LeadStatus::WARM => 'Warm', LeadStatus::HOT => 'Hot', LeadStatus::DEAL => 'Deal', LeadStatus::TRASH_WARM => 'Trash Warm'] as $id => $name) {
            DB::table('lead_statuses')->insert(compact('id', 'name'));
        }
        $lead = Lead::create(['source_id' => $source, 'status_id' => LeadStatus::WARM, 'name' => 'Downstream Customer']);
        $this->claim = LeadClaim::factory()->create(['lead_id' => $lead->id, 'sales_id' => $this->sales->id]);
        $draft = TempQuotation::factory()->create([
            'lead_id' => $lead->id, 'created_by' => $this->sales->id,
            'subtotal' => 1000000, 'tax_pct' => 11, 'tax_total' => 110000, 'grand_total' => 1110000,
        ]);
        $draft->items()->create(['description' => 'Regression machine', 'qty' => 1, 'unit_price' => 1000000,
            'discount_pct' => 0, 'line_total' => 1000000, 'is_visible_pdf' => true]);
        $draft->paymentTerms()->createMany([
            ['term_no' => 1, 'percentage' => 40, 'description' => 'Deposit'],
            ['term_no' => 2, 'percentage' => 60, 'description' => 'Delivery'],
        ]);
        $this->actingAs($this->sales)->postJson(route('leads.my.warm.quotation.final-submit', $this->claim->id))->assertOk();
        $this->quotation = Quotation::sole();
        Notification::fake();
        $this->fakeBillingPdfs();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('quotation_downstream_test');
        DB::setDefaultConnection($this->originalConnection);
        parent::tearDown();
    }

    private function user(string $role): User
    {
        $record = UserRole::create(['code' => $role, 'name' => $role]);
        return User::factory()->create(['role_id' => $record->id, 'branch_id' => 1]);
    }

    private function fakeBillingPdfs(): void
    {
        File::partialMock()->shouldReceive('exists')->with(storage_path('app/public/proformas'))->andReturnTrue();
        File::shouldReceive('exists')->with(storage_path('app/public/invoices'))->andReturnTrue();
        $factory = app('view');
        $views = \Mockery::mock($factory);
        $views->shouldReceive('make')->withAnyArgs()->andReturnUsing(fn (...$arguments) => $factory->make(...$arguments))->byDefault();
        foreach (['pdfs.proforma', 'pdfs.invoice'] as $template) {
            $view = \Mockery::mock(\Illuminate\Contracts\View\View::class);
            $view->shouldReceive('render')->andReturn('<p>Billing PDF</p>');
            $views->shouldReceive('make')->with($template, \Mockery::type('array'), [])->andReturn($view);
        }
        View::swap($views);
        Pdf::shouldReceive('loadHTML')->andReturnSelf();
        Pdf::shouldReceive('setPaper')->with('A4', 'portrait')->andReturnSelf();
        Pdf::shouldReceive('output')->andReturn('%PDF-1.4 billing test');
        Pdf::shouldReceive('save')->andReturnSelf();
    }

    private function publish(?User $actor = null, int $bookingFee = 0): void
    {
        if ($bookingFee) {
            $this->actingAs($this->sales)->postJson(route('leads.my.warm.quotation.store', $this->claim->id), [
                'revision_no' => $this->quotation->fresh()->revision_no, 'product_id' => [null],
                'description' => ['Regression machine'], 'qty' => [1], 'unit_price' => [1000000],
                'discount_pct' => [0], 'tax_pct' => 11, 'payment_type' => 'booking_fee', 'booking_fee' => $bookingFee,
                'term_percentage' => [40, 60], 'term_description' => ['Deposit', 'Delivery'],
            ])->assertOk();
        }
        $revision = $this->quotation->fresh()->revision_no;
        $this->actingAs($this->bm)->postJson(route('quotations.approve', $this->quotation->id), ['revision_no' => $revision, 'notes' => 'BM approved'])->assertRedirect();
        $this->assertSame(0, $this->quotation->proformas()->count());
        $this->assertSame(0, UserBalanceLog::where('quotation_id', $this->quotation->id)->count());
        $this->actingAs($this->sales)->postJson(route('quotations.submit-to-finance', $this->quotation->id), ['revision_no' => $revision])->assertOk();
        $this->actingAs($actor ?? $this->finance)->postJson(route('quotations.approve', $this->quotation->id), ['revision_no' => $revision, 'notes' => 'Finance approved'])->assertRedirect();
        $this->quotation->refresh();
    }

    public static function financePublishers(): array
    {
        return [['finance', 0], ['finance_director', 100000]];
    }

    #[DataProvider('financePublishers')]
    public function test_publication_preserves_proforma_amounts_pending_incentive_and_expiry(string $role, int $bookingFee): void
    {
        $expiry = $this->quotation->expiry_date->toDateString();
        $this->publish($role === 'finance' ? $this->finance : $this->director, $bookingFee);
        $this->assertSame('2026-12-07', $expiry);
        $this->assertSame('published', $this->quotation->status);
        $this->assertSame('2026-12-22', $this->quotation->expiry_date->toDateString());
        $proformas = $this->quotation->proformas()->orderBy('term_no')->get();
        $this->assertCount($bookingFee ? 3 : 2, $proformas);
        $this->assertEquals(444000 - $bookingFee, $proformas->firstWhere('term_no', 1)->amount);
        $this->assertEquals(666000, $proformas->firstWhere('term_no', 2)->amount);
        if ($bookingFee) $this->assertEquals($bookingFee, $proformas->firstWhere('proforma_type', 'booking_fee')->amount);
        foreach ($proformas as $proforma) {
            $this->assertSame('confirmed', $proforma->status);
            $this->assertSame($this->quotation->id, $proforma->quotation_id);
            Storage::disk('public')->assertExists($proforma->attachment->file_path);
        }
        $incentive = UserBalanceLog::where('quotation_id', $this->quotation->id)->sole();
        $this->assertSame($this->sales->id, $incentive->user_id);
        $this->assertSame('pending', $incentive->status);
        $this->assertEquals((float) env('INCENTIVE_AMOUNT_RATE', 100000), $incentive->amount);
        $this->actingAs($this->finance)->postJson(route('quotations.approve', $this->quotation->id), ['revision_no' => 0, 'notes' => 'Duplicate'])->assertStatus(409);
        $this->assertSame(1, UserBalanceLog::where('quotation_id', $this->quotation->id)->count());
    }

    public function test_signed_document_and_existing_billing_records_survive_a_bm_published_revision(): void
    {
        $this->publish();
        $this->actingAs($this->sales)->post(route('quotations.signed-documents.upload', $this->quotation->id), [
            'file' => UploadedFile::fake()->create('signed-quotation.pdf', 10, 'application/pdf'),
            'signed_date' => '22/11/2026', 'description' => 'Customer acceptance',
        ])->assertRedirect();
        $document = $this->quotation->signedDocuments()->sole();
        $this->assertSame('2026-11-22', $document->signed_date);
        $storedPath = substr($document->attachment->file_path, strlen('storage/'));
        Storage::disk('local')->assertExists($storedPath);
        $before = [];
        foreach (['proformas', 'attachments', 'quotation_signed_documents', 'user_balance_logs'] as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        }
        $this->actingAs($this->bm)->postJson(route('leads.my.warm.quotation.store', $this->claim->id), [
            'revision_no' => 0, 'product_id' => [null], 'description' => ['BM revised machine'], 'qty' => [1],
            'unit_price' => [2000000], 'discount_pct' => [0], 'tax_pct' => 11, 'payment_type' => 'down_payment',
            'term_percentage' => [100], 'term_description' => ['Revised terms'],
        ])->assertOk();
        foreach ($before as $table => $rows) $this->assertSame($rows, DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
        Storage::disk('local')->assertExists($storedPath);
        foreach ($this->quotation->proformas as $proforma) Storage::disk('public')->assertExists($proforma->attachment->file_path);
        $this->assertSame(1, $this->quotation->fresh()->revision_no);
        $this->assertSame('review', $this->quotation->fresh()->status);
        $this->assertSame(1, $this->quotation->signedDocuments()->count());
        $this->get(route('quotations.signed-documents', $this->quotation->id))->assertRedirect(route('quotations.show', $this->quotation->id));
    }

    public function test_signed_upload_keeps_existing_role_and_validation_rules(): void
    {
        foreach ([$this->bm, $this->finance, $this->director] as $actor) {
            $this->actingAs($actor)->postJson(route('quotations.signed-documents.upload', $this->quotation->id))->assertForbidden();
        }
        $this->actingAs($this->sales)->postJson(route('quotations.signed-documents.upload', $this->quotation->id), [
            'file' => UploadedFile::fake()->create('invalid.txt', 1, 'text/plain'), 'signed_date' => '2026-11-22',
        ])->assertUnprocessable();
        $this->assertSame(0, QuotationSignedDocument::count());
        $this->assertSame(0, Attachment::count());
    }

    public function test_issued_at_editor_still_regenerates_the_archived_proforma_pdf(): void
    {
        $this->publish();
        $proforma = $this->quotation->proformas()->where('term_no', 1)->sole();
        $previousAttachment = $proforma->attachment;
        $previousContent = Storage::disk('public')->get($previousAttachment->file_path);
        $request = Request::create('/proforma-date-test', 'POST', ['issued_at' => '2026-11-23']);
        $request->setUserResolver(fn () => $this->sales);
        $response = app(\App\Http\Controllers\Orders\QuotationController::class)->updateProformaIssuedAt($request, $proforma->id);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('2026-11-23', $proforma->fresh()->issued_at->toDateString());
        $this->assertNotSame($previousAttachment->id, $proforma->fresh()->attachment_id);
        $this->assertSame($previousContent, Storage::disk('public')->get($previousAttachment->file_path));
        Storage::disk('public')->assertExists($proforma->fresh()->attachment->file_path);
    }

    public function test_payment_submission_and_resubmission_keep_proforma_amount_and_original_evidence(): void
    {
        $this->publish();
        $proforma = $this->quotation->proformas()->where('term_no', 1)->sole();
        $url = route('payment-confirmation.terms.payment.confirm', [$this->quotation->lead_id, 1]);
        $this->actingAs($this->sales)->post($url, [
            'attachment_id' => UploadedFile::fake()->create('payment.pdf', 10, 'application/pdf'),
            'paid_at' => '2026-11-22', 'payer_name' => 'Customer', 'amount' => 1,
        ])->assertRedirect(route('quotations.show', $this->quotation->id));
        $payment = $proforma->paymentConfirmation()->sole();
        $attachment = $payment->attachment_id;
        $this->assertEquals($proforma->amount, $payment->amount);
        $request = $payment->financeRequest;
        $request->update(['status' => 'rejected', 'approver_id' => $this->finance->id, 'notes' => 'Check the date', 'decided_at' => Carbon::now('Asia/Jakarta')]);
        $this->post($url, ['paid_at' => '2026-11-23', 'payer_name' => 'Updated payer'])->assertRedirect();
        $this->assertSame(1, PaymentConfirmation::count());
        $this->assertSame($attachment, $payment->fresh()->attachment_id);
        $this->assertEquals($proforma->amount, $payment->fresh()->amount);
        $this->assertSame('pending', $request->fresh()->status);
        $this->assertNull($request->fresh()->notes);
        $this->assertNull($request->fresh()->approver_id);
        $this->assertSame(2, PaymentLog::where('type', 'confirmation')->count());
        Notification::assertSentTo([$this->finance, $this->director], PaymentConfirmationSubmittedNotification::class);
        $this->actingAs($this->bm)->postJson(route('leads.my.warm.quotation.store', $this->claim->id), [
            'revision_no' => 0, 'product_id' => [null], 'description' => ['Blocked edit'], 'qty' => [1],
            'unit_price' => [2000000], 'discount_pct' => [0], 'tax_pct' => 11, 'payment_type' => 'down_payment',
            'term_percentage' => [100], 'term_description' => ['Full payment'],
        ])->assertForbidden();
        $this->assertSame('published', $this->quotation->fresh()->status);
    }

    public function test_booking_dp_and_final_payment_approval_preserve_invoices_order_and_incentive_lifecycle(): void
    {
        $this->publish($this->director, 100000);
        $snapshot = $this->quotation->revisions()->where('revision_no', 1)->sole()->getRawOriginal();
        $financeReview = $this->quotation->currentReviews()->where('role', 'FIN')->sole();
        $pendingIncentive = UserBalanceLog::where('quotation_id', $this->quotation->id)->sole();
        $this->assertGreaterThan(0, $pendingIncentive->amount);
        // Characterize the existing payout source; changing incentive rules is outside this PRD.
        $purchasing = \Mockery::mock(\App\Http\Controllers\Purchasing\PurchaseController::class);
        $purchasing->shouldReceive('handleLeadDeal')->once()->with($this->quotation->lead_id);
        $this->app->instance(\App\Http\Controllers\Purchasing\PurchaseController::class, $purchasing);
        $conversion = \Mockery::mock(\App\Services\Aftersales\DealConversion::class);
        $conversion->shouldReceive('sync')->once()->with(\Mockery::on(fn ($lead) => $lead->id === $this->quotation->lead_id && $lead->status_id === LeadStatus::DEAL));
        $this->app->instance(\App\Services\Aftersales\DealConversion::class, $conversion);

        foreach (['bf', 1, 2] as $term) {
            $proforma = $term === 'bf'
                ? $this->quotation->proformas()->where('proforma_type', 'booking_fee')->sole()
                : $this->quotation->proformas()->where('term_no', $term)->sole();
            $this->actingAs($this->sales)->post(route('payment-confirmation.terms.payment.confirm', [$this->quotation->lead_id, $term]), [
                'attachment_id' => UploadedFile::fake()->create('evidence-'.$term.'.pdf', 10, 'application/pdf'),
                'paid_at' => '2026-11-22', 'payer_name' => 'Customer',
            ])->assertRedirect();
            $payment = $proforma->paymentConfirmation()->sole();
            $response = $this->actingAs($this->finance)->postJson(route('finance-requests.approve', $payment->financeRequest->id), ['notes' => 'Payment verified']);
            $this->assertSame(200, $response->status(), $response->getContent());
            $this->assertSame($this->finance->id, $payment->fresh()->confirmed_by);
            $this->assertNotNull($payment->fresh()->confirmed_at);
            $this->assertSame('approved', $payment->financeRequest->fresh()->status);
            $invoice = $proforma->invoice()->sole();
            $this->assertSame('paid', $invoice->status);
            $this->assertEquals($proforma->amount, $invoice->amount);
            if ($term === 'bf') {
                $this->assertSame(LeadStatus::HOT, $this->quotation->lead->fresh()->status_id);
                $this->assertSame(0, Order::count());
                $this->assertSame('pending', $pendingIncentive->fresh()->status);
            } else {
                $this->assertSame(LeadStatus::DEAL, $this->quotation->lead->fresh()->status_id);
                $this->assertNotNull($this->quotation->lead->fresh()->deal_at);
                $this->assertSame(1, Order::count());
                $this->assertSame($term === 1 ? 'publish' : 'done', Order::sole()->order_status);
                $this->assertSame('received', $pendingIncentive->fresh()->status);
            }
        }
        $this->assertSame(3, Invoice::count());
        $this->assertSame(3, PaymentConfirmation::count());
        $this->assertSame(1, Order::sole()->orderItems()->count());
        $this->assertSame(2, Order::sole()->paymentTerms()->count());
        $this->assertEquals($this->quotation->grand_total, Order::sole()->total_billing);
        $this->assertEquals($financeReview->incentive_nominal, UserBalance::where('user_id', $this->sales->id)->sole()->total_balance);
        $this->assertSame($snapshot, $this->quotation->revisions()->where('revision_no', 1)->sole()->getRawOriginal());
        $this->quotation->update(['expiry_date' => '2026-11-21']);
        $this->artisan('quotations:expire')->assertSuccessful();
        $this->assertSame('received', $pendingIncentive->fresh()->status);
        $this->assertSame(LeadStatus::DEAL, $this->quotation->lead->fresh()->status_id);
        $this->assertSame('done', Order::sole()->order_status);
        $this->assertSame(3, Invoice::where('status', 'paid')->count());
    }

    public static function incentiveCases(): array
    {
        return [[0, 140000], [0.5, 145000], [1, 140000], [4.99, 140000], [5, 0], [100, 0]];
    }

    #[DataProvider('incentiveCases')]
    public function test_existing_incentive_formula_boundaries_are_preserved(float $discount, int $additional): void
    {
        $item = new QuotationItems(['qty' => 2, 'discount_pct' => $discount]);
        $item->setAttribute('price', 1000000);
        $item->setAttribute('base_price', 800000);
        $quotation = clone $this->quotation;
        $quotation->setRelation('items', new \Illuminate\Database\Eloquent\Collection([$item]));
        $controller = app(\App\Http\Controllers\Orders\QuotationController::class);
        $method = new \ReflectionMethod($controller, 'calculateQuotationIncentive');
        $this->assertEquals((float) env('INCENTIVE_AMOUNT_RATE', 100000) + $additional, $method->invoke($controller, $quotation));
    }

    public static function ignoredExpiryCases(): array
    {
        return [['2026-11-23', 'review', false], [null, 'review', false], ['2026-11-21', 'expired', false], ['2026-11-21', 'review', true]];
    }

    #[DataProvider('ignoredExpiryCases')]
    public function test_expiry_ignores_future_null_already_expired_and_deleted_quotations(?string $date, string $status, bool $deleted): void
    {
        $this->quotation->update(['expiry_date' => $date, 'status' => $status]);
        if ($deleted) $this->quotation->delete();
        $before = Quotation::withTrashed()->findOrFail($this->quotation->id)->getRawOriginal();
        $this->artisan('quotations:expire')->assertSuccessful();
        $this->assertSame($before, Quotation::withTrashed()->findOrFail($this->quotation->id)->getRawOriginal());
        $this->assertSame(LeadStatus::WARM, $this->quotation->lead->fresh()->status_id);
        $this->assertSame(0, LeadStatusLog::count());
    }

    public static function expirableStatuses(): array
    {
        return [['review'], ['approved_by_bm'], ['pending_finance'], ['published'], ['rejected']];
    }

    #[DataProvider('expirableStatuses')]
    public function test_expiry_keeps_published_leads_and_expires_only_pending_incentive(string $status): void
    {
        $this->quotation->update(['status' => $status, 'expiry_date' => '2026-11-22']);
        $pending = UserBalanceLog::create(['quotation_id' => $this->quotation->id, 'user_id' => $this->sales->id,
            'amount' => 100000, 'status' => 'pending', 'created_at' => Carbon::now('Asia/Jakarta')]);
        $received = UserBalanceLog::create(['quotation_id' => $this->quotation->id, 'user_id' => $this->sales->id,
            'amount' => 25000, 'status' => 'received', 'created_at' => Carbon::now('Asia/Jakarta')]);
        $snapshot = $this->quotation->revisions()->sole()->getRawOriginal();
        $this->artisan('quotations:expire')->assertSuccessful();
        $this->assertSame('expired', $this->quotation->fresh()->status);
        $this->assertSame('expired', $pending->fresh()->status);
        $this->assertSame('received', $received->fresh()->status);
        $this->assertSame($snapshot, $this->quotation->revisions()->sole()->getRawOriginal());
        $this->assertSame($status === 'published' ? LeadStatus::WARM : LeadStatus::TRASH_WARM, $this->quotation->lead->fresh()->status_id);
        $count = LeadStatusLog::count();
        $this->artisan('quotations:expire')->assertSuccessful();
        $this->assertSame($count, LeadStatusLog::count());
    }

    public function test_expiry_does_not_expire_a_revision_whose_validity_was_extended_after_selection(): void
    {
        $this->quotation->update(['expiry_date' => '2026-11-21']);
        $changed = false;
        DB::listen(function ($event) use (&$changed) {
            if (!$changed && str_starts_with($event->sql, 'select * from "quotations"') && str_contains($event->sql, '"expiry_date"')) {
                $changed = true;
                DB::table('quotations')->where('id', $this->quotation->id)->update(['expiry_date' => '2026-12-22']);
            }
        });
        $this->artisan('quotations:expire')->assertSuccessful();
        $this->assertTrue($changed);
        $this->assertSame('review', $this->quotation->fresh()->status);
        $this->assertSame(LeadStatus::WARM, $this->quotation->lead->fresh()->status_id);
        $this->assertSame(0, LeadStatusLog::count());
    }

    public function test_expiry_failure_rolls_back_quotation_lead_and_pending_incentive(): void
    {
        $this->quotation->update(['expiry_date' => '2026-11-21']);
        $balance = UserBalanceLog::create(['quotation_id' => $this->quotation->id, 'user_id' => $this->sales->id,
            'amount' => 100000, 'status' => 'pending', 'created_at' => Carbon::now('Asia/Jakarta')]);
        DB::unprepared("CREATE TRIGGER fail_expiry_log BEFORE INSERT ON lead_status_logs BEGIN SELECT RAISE(ABORT, 'forced expiry failure'); END");
        try {
            $this->artisan('quotations:expire')->run();
            $this->fail('Expected the expiry transaction to fail.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('forced expiry failure', $exception->getMessage());
        }
        $this->assertSame('review', $this->quotation->fresh()->status);
        $this->assertSame(LeadStatus::WARM, $this->quotation->lead->fresh()->status_id);
        $this->assertSame('pending', $balance->fresh()->status);
    }
}
