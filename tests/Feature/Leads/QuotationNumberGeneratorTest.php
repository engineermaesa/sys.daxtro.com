<?php

namespace Tests\Feature\Leads;

use App\Models\Aftersales\DocumentCounter;
use App\Services\Orders\QuotationNumberGenerator;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use RuntimeException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class QuotationNumberGeneratorTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = DB::getDefaultConnection();
        config(['database.connections.quotation_number_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true, 'busy_timeout' => 10000,
        ]]);
        DB::setDefaultConnection('quotation_number_test');
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('quotation_number_test');
        DB::setDefaultConnection($this->originalConnection);
        parent::tearDown();
    }

    private function createSchema(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_no')->nullable();
            $table->softDeletes();
        });
        (require database_path('migrations/2026_09_03_010500_create_document_counters_table.php'))->up();
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_20_000200_add_quotation_document_counter.php');
    }

    public function test_sequence_starts_at_one_and_does_not_reset_on_a_new_day(): void
    {
        $this->migration()->up();
        $generator = app(QuotationNumberGenerator::class);
        Carbon::setTestNow(Carbon::parse('2026-11-20 10:00:00', 'Asia/Jakarta'));
        $this->assertSame('QT_DEAL_001', $generator->next());
        Carbon::setTestNow(Carbon::parse('2026-11-21 10:00:00', 'Asia/Jakarta'));
        $this->assertSame('QT_DEAL_002', $generator->next());
        $this->assertSame(1, DB::table('document_counters')->where('type', 'quotation')->count());
    }

    public function test_seed_uses_highest_numeric_number_including_soft_deleted_rows_instead_of_latest_id(): void
    {
        DB::table('quotations')->insert([
            ['quotation_no' => 'QT_DEAL_1002', 'deleted_at' => '2026-11-01 00:00:00'],
            ['quotation_no' => 'QT_DEAL_099', 'deleted_at' => null],
            ['quotation_no' => 'QT_DEAL_9999_R1', 'deleted_at' => null],
            ['quotation_no' => 'QT001', 'deleted_at' => null],
            ['quotation_no' => null, 'deleted_at' => null],
        ]);
        $this->migration()->up();

        $this->assertSame('QT_DEAL_1003', app(QuotationNumberGenerator::class)->next());
        $this->assertSame(5, DB::table('quotations')->count());
    }

    public function test_later_imported_soft_deleted_number_is_skipped(): void
    {
        $this->migration()->up();
        DB::table('quotations')->insert(['quotation_no' => 'QT_DEAL_001', 'deleted_at' => '2026-11-01 00:00:00']);

        $this->assertSame('QT_DEAL_002', app(QuotationNumberGenerator::class)->next());
    }

    public function test_counter_survives_removal_of_a_previously_issued_quotation(): void
    {
        $this->migration()->up();
        $generator = app(QuotationNumberGenerator::class);
        DB::table('quotations')->insert(['quotation_no' => $generator->next()]);
        DB::table('quotations')->delete();

        $this->assertSame('QT_DEAL_002', $generator->next());
    }

    public function test_counter_allocation_rolls_back_with_the_enclosing_quotation_transaction(): void
    {
        $this->migration()->up();
        DB::beginTransaction();
        $number = app(QuotationNumberGenerator::class)->next();
        DB::table('quotations')->insert(['quotation_no' => $number]);
        DB::rollBack();

        $this->assertSame(0, DB::table('quotations')->count());
        $this->assertSame(0, DB::table('document_counters')->value('last_number'));
        $this->assertSame('QT_DEAL_001', app(QuotationNumberGenerator::class)->next());
    }

    public function test_migration_does_not_reset_counter_or_change_ticket_and_work_order_numbering(): void
    {
        $date = Carbon::parse('2026-11-20', 'Asia/Jakarta');
        $this->assertSame(1, DocumentCounter::nextNumber('ticket', $date));
        $this->assertSame(1, DocumentCounter::nextNumber('work_order', $date));
        $this->migration()->up();
        $generator = app(QuotationNumberGenerator::class);
        $this->assertSame('QT_DEAL_001', $generator->next());
        $this->migration()->up();
        $this->assertSame('QT_DEAL_002', $generator->next());
        $this->assertSame(2, DocumentCounter::nextNumber('ticket', $date));
        $this->assertSame(2, DocumentCounter::nextNumber('work_order', $date));
    }

    public function test_migration_can_roll_back_an_unused_counter(): void
    {
        $this->migration()->up();
        $this->migration()->down();
        $this->assertSame(0, DB::table('document_counters')->count());
        $this->assertSame(1, DocumentCounter::nextNumber('ticket'));
    }

    public function test_rollback_refuses_to_discard_an_allocated_sequence(): void
    {
        $this->migration()->up();
        app(QuotationNumberGenerator::class)->next();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('numbering history');
        $this->migration()->down();
    }

    public function test_missing_counter_fails_without_falling_back_to_the_unsafe_generator(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('migration first');
        app(QuotationNumberGenerator::class)->next();
    }

    public function test_two_processes_wait_for_the_same_counter_and_commit_distinct_numbers(): void
    {
        $databasePath = tempnam(sys_get_temp_dir(), 'quotation-counter-');
        $this->assertNotFalse($databasePath);
        DB::purge('quotation_number_test');
        config(['database.connections.quotation_number_test.database' => $databasePath]);
        $input = new InputStream;
        $first = new Process([PHP_BINARY, base_path('tests/Support/quotation-number-worker.php'), $databasePath, 'hold']);
        $second = new Process([PHP_BINARY, base_path('tests/Support/quotation-number-worker.php'), $databasePath]);
        $first->setInput($input);
        $first->setTimeout(30);
        $second->setTimeout(30);

        try {
            $this->createSchema();
            $this->migration()->up();
            $first->start();
            $this->waitForWorkerOutput($first, 'reserved:QT_DEAL_001');
            $second->start();
            $this->waitForWorkerOutput($second, 'ready');
            // Keep the first transaction open while the second attempts allocation.
            usleep(100000);
            $this->assertTrue($second->isRunning());
            $this->assertStringNotContainsString('reserved:', $second->getOutput());
            $input->write("commit\n");
            $input->close();

            $this->assertSame(0, $first->wait(), $first->getErrorOutput());
            $this->assertSame(0, $second->wait(), $second->getErrorOutput());
            $this->assertSame(['QT_DEAL_001', 'QT_DEAL_002'], DB::table('quotations')->orderBy('id')->pluck('quotation_no')->all());
            $this->assertSame(2, DB::table('document_counters')->value('last_number'));
        } finally {
            $input->close();
            if ($first->isRunning()) {
                $first->stop();
            }
            if ($second->isRunning()) {
                $second->stop();
            }
            DB::purge('quotation_number_test');
            unlink($databasePath);
        }
    }

    private function waitForWorkerOutput(Process $process, string $expected): void
    {
        $deadline = microtime(true) + 10;
        do {
            // start() may already have buffered the signal before a waitUntil callback is installed.
            if (str_contains($process->getOutput(), $expected)) {
                $this->assertStringContainsString($expected, $process->getOutput());

                return;
            }
            if (! $process->isRunning()) {
                break;
            }
            $process->checkTimeout();
            usleep(10000);
        } while (microtime(true) < $deadline);

        $this->fail('Worker did not emit '.$expected.': '.$process->getOutput().' '.$process->getErrorOutput());
    }
}
