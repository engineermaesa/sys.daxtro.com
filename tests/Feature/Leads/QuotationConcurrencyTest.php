<?php

namespace Tests\Feature\Leads;

use App\Models\Leads\{Lead, LeadClaim, LeadStatus};
use App\Models\Orders\{Quotation, QuotationLog, QuotationReview, TempQuotation};
use App\Models\{User, UserRole};
use App\Services\Orders\{QuotationConcurrency, QuotationRevisions};
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\{DB, Notification};
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\{InputStream, Process};
use Tests\Support\BuildsQuotationSubmissionSchema;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('quotation-revision')]
class QuotationConcurrencyTest extends TestCase
{
    use BuildsQuotationSubmissionSchema;

    private string $originalConnection;
    private string $databasePath;
    private User $sales;
    private User $bm;
    private LeadClaim $claim;
    private TempQuotation $draft;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = DB::getDefaultConnection();
        $this->databasePath = tempnam(sys_get_temp_dir(), 'quotation-mutations-');
        config(['database.connections.quotation_concurrency_test' => [
            'driver' => 'sqlite', 'database' => $this->databasePath, 'prefix' => '',
            'busy_timeout' => 100, 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('quotation_concurrency_test');
        $this->buildQuotationSubmissionSchema();
        Notification::fake();
        $salesRole = UserRole::create(['code' => 'sales', 'name' => 'Sales']);
        $bmRole = UserRole::create(['code' => 'branch_manager', 'name' => 'BM']);
        $this->sales = User::factory()->create(['role_id' => $salesRole->id, 'branch_id' => 1]);
        $this->bm = User::factory()->create(['role_id' => $bmRole->id, 'branch_id' => 1]);
        $source = DB::table('lead_sources')->insertGetId(['name' => 'Test Source']);
        DB::table('lead_statuses')->insert(['id' => LeadStatus::WARM, 'name' => 'Warm']);
        $lead = Lead::create(['source_id' => $source, 'status_id' => LeadStatus::WARM, 'name' => 'Concurrent Customer']);
        $this->claim = LeadClaim::factory()->create(['lead_id' => $lead->id, 'sales_id' => $this->sales->id]);
        $this->draft = TempQuotation::factory()->create(['lead_id' => $lead->id, 'created_by' => $this->sales->id]);
        $this->draft->items()->create([
            'description' => 'Original machine', 'qty' => 1, 'unit_price' => 1000000,
            'discount_pct' => 0, 'line_total' => 1000000, 'is_visible_pdf' => true,
        ]);
        $this->draft->paymentTerms()->create(['term_no' => 1, 'percentage' => 100, 'description' => 'Original terms']);
        $this->actingAs($this->sales);
    }

    protected function tearDown(): void
    {
        DB::purge('quotation_concurrency_test');
        DB::setDefaultConnection($this->originalConnection);
        if (is_file($this->databasePath)) unlink($this->databasePath);
        parent::tearDown();
    }

    private function payload(int $version = 0): array
    {
        return [
            'draft_id' => $this->draft->id, 'draft_version' => $version,
            'product_id' => [null], 'description' => ['Saved machine'], 'qty' => [1],
            'unit_price' => [2000000], 'discount_pct' => [0], 'tax_pct' => 11,
            'payment_type' => 'down_payment', 'term_percentage' => [100], 'term_description' => ['Saved terms'],
        ];
    }

    public function test_stale_draft_cannot_overwrite_submit_or_cancel_newer_saved_contents(): void
    {
        $store = route('leads.my.warm.quotation.store', $this->claim->id);
        $submit = route('leads.my.warm.quotation.final-submit', $this->claim->id);
        $this->postJson($store, $this->payload())->assertOk()->assertJsonPath('data.draft_version', 1);
        $this->postJson($store, $this->payload())->assertStatus(409);
        $this->postJson($submit, ['draft_id' => $this->draft->id, 'draft_version' => 0])->assertStatus(409);
        $this->postJson(route('quotations.drafts.cancel', $this->draft->id), ['draft_version' => 0])->assertStatus(409);
        $this->assertSame(1, $this->draft->fresh()->lock_version);
        $this->assertSame('Saved machine', $this->draft->fresh()->items->sole()->description);
        $this->assertSame('Saved terms', $this->draft->fresh()->paymentTerms->sole()->description);
        $this->postJson($submit, ['draft_id' => $this->draft->id, 'draft_version' => 1])->assertOk();
        $this->postJson($store, $this->payload(1))->assertStatus(409);
        $this->postJson($submit, ['draft_id' => $this->draft->id, 'draft_version' => 1])->assertStatus(409);
        $this->assertSame(1, Quotation::count());
        $this->assertSame(0, Quotation::sole()->revision_no);
        $this->assertSame(1, Quotation::sole()->revisions()->count());
        $this->assertSame('Saved machine', Quotation::sole()->items->sole()->description);
        $this->assertSame(1, DB::table('document_counters')->where('type', 'quotation')->value('last_number'));
    }

    public function test_draft_failure_rolls_back_version_items_and_terms_and_returns_conflict(): void
    {
        DB::unprepared("CREATE TRIGGER fail_draft_terms BEFORE INSERT ON temp_quotation_payment_terms BEGIN SELECT RAISE(ABORT, 'database is locked'); END");
        $this->postJson(route('leads.my.warm.quotation.store', $this->claim->id), $this->payload())
            ->assertStatus(409)->assertJsonPath('message', QuotationConcurrency::MESSAGE);
        $this->assertSame(0, $this->draft->fresh()->lock_version);
        $this->assertSame('Original machine', $this->draft->fresh()->items->sole()->description);
        $this->assertSame('Original terms', $this->draft->fresh()->paymentTerms->sole()->description);
        $this->assertSame(0, Quotation::count());
        Notification::assertNothingSent();
    }

    public function test_review_database_conflict_rolls_back_decision_status_and_log(): void
    {
        $this->postJson(route('leads.my.warm.quotation.final-submit', $this->claim->id))->assertOk();
        Notification::fake();
        $quotation = Quotation::sole();
        DB::unprepared("CREATE TRIGGER fail_review_audit BEFORE INSERT ON quotation_logs WHEN NEW.action = 'approve' BEGIN SELECT RAISE(ABORT, 'database is locked'); END");
        $this->actingAs($this->bm)->postJson(route('quotations.approve', $quotation->id), ['revision_no' => 0, 'notes' => 'Approve'])
            ->assertStatus(409);
        $this->assertSame('review', $quotation->fresh()->status);
        $this->assertSame(0, QuotationReview::count());
        $this->assertSame(0, QuotationLog::where('action', 'approve')->count());
        Notification::assertNothingSent();
    }

    public function test_stale_snapshot_request_does_not_snapshot_a_newer_revision(): void
    {
        $this->postJson(route('leads.my.warm.quotation.final-submit', $this->claim->id))->assertOk();
        $stale = Quotation::sole();
        $stale->newQuery()->whereKey($stale->id)->update(['revision_no' => 1, 'revision_code' => $stale->quotation_no.'_R1']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        try {
            app(QuotationRevisions::class)->snapshotCurrentRevision($stale, $this->sales->id);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
            $this->assertSame(1, $stale->revisions()->count());
            throw $exception;
        }
    }

    public static function databaseErrors(): array
    {
        return [
            ['40001', 1213, 'Deadlock found', true], ['HY000', 1205, 'Lock wait timeout', true],
            ['HY000', 3572, 'NOWAIT lock', true], ['HY000', 5, 'database is locked', true],
            ['HY000', 517, 'database is locked', true], ['40P01', 0, 'deadlock', true],
            ['23000', 19, 'NOT NULL constraint failed', false], ['23000', 1452, 'Foreign key failure', false],
        ];
    }

    public function test_rejected_action_does_not_rollback_a_callers_existing_transaction(): void
    {
        $role = UserRole::create(['code' => 'purchasing', 'name' => 'Purchasing']);
        $actor = User::factory()->create(['role_id' => $role->id, 'branch_id' => 1]);
        DB::beginTransaction();
        try {
            DB::table('lead_sources')->insert(['name' => 'Outer transaction marker']);
            $this->actingAs($actor)->postJson(route('leads.my.warm.quotation.final-submit', $this->claim->id))->assertForbidden();
            $this->assertSame(1, DB::transactionLevel());
            $this->assertSame(1, DB::table('lead_sources')->where('name', 'Outer transaction marker')->count());
        } finally {
            DB::rollBack();
        }
    }

    #[DataProvider('databaseErrors')]
    public function test_only_database_concurrency_errors_are_mapped_to_conflicts(string $state, int $code, string $message, bool $expected): void
    {
        $previous = new \PDOException($message);
        $previous->errorInfo = [$state, $code, $message];
        $exception = new QueryException('test', 'update quotations', [], $previous);
        $this->assertSame($expected, QuotationConcurrency::isConflict($exception));
    }

    public static function concurrentActions(): array
    {
        return [['save', 'save'], ['save', 'submit'], ['save', 'cancel'], ['submit', 'submit'], ['cancel', 'submit'], ['approve', 'reject'], ['create', 'create'], ['finance', 'revise'], ['revise', 'finance']];
    }

    #[DataProvider('concurrentActions')]
    public function test_two_processes_cannot_commit_conflicting_mutations(string $firstAction, string $secondAction): void
    {
        $quotation = null;
        if ($firstAction === 'create') $this->draft->delete();
        if (in_array($firstAction, ['approve', 'finance', 'revise'], true)) {
            $this->postJson(route('leads.my.warm.quotation.final-submit', $this->claim->id))->assertOk();
            $quotation = Quotation::sole();
            if ($firstAction !== 'approve') {
                $this->actingAs($this->bm)->postJson(route('quotations.approve', $quotation->id), ['revision_no' => 0, 'notes' => 'Ready for customer'])->assertRedirect();
            }
        }
        $parameters = function (string $action) use ($quotation): array {
            $id = in_array($action, ['approve', 'reject', 'finance'], true) ? $quotation->id : ($action === 'cancel' ? $this->draft->id : $this->claim->id);
            $payload = $action === 'save' ? $this->payload() : ['draft_id' => $this->draft->id, 'draft_version' => 0, 'revision_no' => 0, 'notes' => 'Concurrent review'];
            return [PHP_BINARY, base_path('tests/Support/quotation-mutation-worker.php'), $this->databasePath,
                (string) (in_array($action, ['approve', 'reject'], true) ? $this->bm->id : $this->sales->id),
                $action, (string) $id, json_encode($payload)];
        };
        $input = new InputStream;
        $first = new Process([...$parameters($firstAction), 'hold']);
        $second = new Process($parameters($secondAction));
        $first->setInput($input);
        $first->setTimeout(30);
        $second->setTimeout(30);
        try {
            $first->start();
            $deadline = microtime(true) + 15;
            while (!str_contains($first->getOutput(), 'locked') && $first->isRunning() && microtime(true) < $deadline) usleep(10000);
            $this->assertStringContainsString('locked', $first->getOutput(), $first->getErrorOutput());
            $second->run();
            $this->assertSame(0, $second->getExitCode(), $second->getErrorOutput());
            $this->assertStringContainsString('status:409', $second->getOutput(), $second->getErrorOutput());
            $input->write("commit\n");
            $input->close();
            $this->assertSame(0, $first->wait(), $first->getErrorOutput());
            $this->assertMatchesRegularExpression('/status:(?:200|302)/', $first->getOutput());
            if ($firstAction === 'create') {
                $this->assertSame(1, TempQuotation::where('status', 'draft')->count());
                $this->assertSame(0, Quotation::count());
                $this->assertSame(1, QuotationLog::where('action', 'draft_created')->count());
            } elseif ($firstAction === 'finance') {
                $this->assertSame('pending_finance', $quotation->fresh()->status);
                $this->assertSame(0, TempQuotation::where('status', 'draft')->count());
                $this->assertSame(1, QuotationLog::where('action', 'submit_to_finance')->count());
            } elseif ($firstAction === 'revise') {
                $this->assertSame('approved_by_bm', $quotation->fresh()->status);
                $this->assertSame(1, TempQuotation::where('status', 'draft')->count());
                $this->assertSame(0, QuotationLog::where('action', 'submit_to_finance')->count());
            } elseif ($firstAction === 'save') {
                $this->assertSame(1, $this->draft->fresh()->lock_version);
                $this->assertSame('Saved machine', $this->draft->fresh()->items->sole()->description);
                $this->assertSame('Saved terms', $this->draft->fresh()->paymentTerms->sole()->description);
                $this->assertSame(0, Quotation::count());
            } elseif ($firstAction === 'submit') {
                $this->assertSame(1, Quotation::count());
                $this->assertSame(1, Quotation::sole()->revisions()->count());
                $this->assertSame(1, DB::table('document_counters')->where('type', 'quotation')->value('last_number'));
            } elseif ($firstAction === 'cancel') {
                $this->assertTrue(TempQuotation::withTrashed()->findOrFail($this->draft->id)->trashed());
                $this->assertSame(0, Quotation::count());
            } else {
                $this->assertSame('approved_by_bm', $quotation->fresh()->status);
                $this->assertSame(1, $quotation->currentReviews()->count());
                $this->assertSame('approve', $quotation->currentReviews()->sole()->decision);
            }
        } finally {
            $input->close();
            if ($first->isRunning()) $first->stop();
            if ($second->isRunning()) $second->stop();
        }
    }

    public function test_draft_version_migration_is_repeatable_and_does_not_reset_saved_versions(): void
    {
        $this->postJson(route('leads.my.warm.quotation.store', $this->claim->id), $this->payload())->assertOk();
        $migration = require database_path('migrations/2026_11_20_000500_add_quotation_draft_lock_version.php');
        $migration->up();
        $migration->up();
        $this->assertSame(1, $this->draft->fresh()->lock_version);
        $this->assertSame('Saved machine', $this->draft->fresh()->items->sole()->description);
    }
}
