<?php

namespace Tests\Feature\Leads;

use App\Models\Orders\Quotation;
use App\Models\Orders\QuotationReview;
use App\Models\Orders\TempQuotation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuotationRevisionSchemaTest extends TestCase
{
    private Migration $migration;

    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = DB::getDefaultConnection();
        config(['database.connections.quotation_revision_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('quotation_revision_test');

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_no')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('temp_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('quotation_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_id');
            $table->string('decision');
            $table->timestamps();
            $table->softDeletes();
        });

        $this->migration = require database_path('migrations/2026_11_20_000000_add_revision_fields_to_quotations.php');
    }

    protected function tearDown(): void
    {
        DB::purge('quotation_revision_test');
        DB::setDefaultConnection($this->originalConnection);

        parent::tearDown();
    }

    public function test_migration_preserves_legacy_records_and_allows_unassigned_drafts(): void
    {
        DB::table('quotations')->insert(['id' => 1, 'quotation_no' => 'QT_DEAL_042', 'status' => 'published']);
        DB::table('temp_quotations')->insert(['id' => 1, 'status' => 'draft']);
        DB::table('quotation_reviews')->insert(['quotation_id' => 1, 'decision' => 'approve']);

        $this->migration->up();
        $this->migration->up();

        $quotation = Quotation::findOrFail(1);
        $this->assertSame('QT_DEAL_042', $quotation->quotation_no);
        $this->assertSame('published', $quotation->status);
        $this->assertSame(0, $quotation->revision_no);
        $this->assertNull($quotation->revision_code);
        $this->assertSame(0, QuotationReview::firstOrFail()->revision_no);

        $draft = TempQuotation::findOrFail(1);
        $this->assertNull($draft->quotation_id);
        $this->assertNull($draft->revision_no);
        $this->assertNull($draft->revision_code);
        $this->assertSame('draft', $draft->status);

        $indexes = collect(Schema::getIndexes('temp_quotations'));
        $this->assertTrue($indexes->contains(fn ($index) => $index['columns'] === ['quotation_id'] && ! $index['unique']));
        $this->assertSame([], Schema::getForeignKeys('temp_quotations'));
    }

    public function test_models_persist_revision_fields_and_keep_history_scoped_to_its_quotation(): void
    {
        $this->migration->up();

        $quotation = Quotation::create([
            'quotation_no' => 'QT_DEAL_042',
            'revision_no' => '1',
            'revision_code' => 'QT_DEAL_042_R1',
        ]);
        $other = Quotation::create(['quotation_no' => 'QT_DEAL_043']);

        foreach ([0, 1] as $revisionNo) {
            $quotation->revisions()->create([
                'revision_no' => (string) $revisionNo,
                'revision_code' => 'QT_DEAL_042_R'.$revisionNo,
                'status' => 'submitted',
            ]);
        }
        $other->revisions()->create(['revision_no' => 9, 'status' => 'submitted']);
        $draft = TempQuotation::create(['quotation_id' => $quotation->id, 'status' => 'draft']);
        $draft->delete();

        $revisions = $quotation->fresh()->revisions;
        $this->assertSame([1, 0], $revisions->pluck('revision_no')->all());
        $this->assertSame('QT_DEAL_042_R1', $revisions->first()->revision_code);
        $this->assertTrue($revisions->first()->quotation->is($quotation));
        $this->assertSame(1, $quotation->fresh()->revision_no);
        $this->assertSame('QT_DEAL_042_R1', $quotation->fresh()->revision_code);

        $review = QuotationReview::create([
            'quotation_id' => $quotation->id,
            'revision_no' => '0',
            'decision' => 'reject',
        ]);
        $review->delete();

        $historicalReview = $quotation->reviews()->withTrashed()->firstOrFail();
        $this->assertSame(0, $historicalReview->revision_no);
        $this->assertTrue($historicalReview->quotation->is($quotation));
    }

    public function test_rollback_removes_only_revision_columns_and_preserves_original_data(): void
    {
        $this->migration->up();
        Quotation::create(['quotation_no' => 'QT_DEAL_042', 'status' => 'published']);

        $this->migration->down();
        $this->migration->down();

        foreach ([
            'quotations' => ['revision_no', 'revision_code'],
            'temp_quotations' => ['quotation_id', 'revision_no', 'revision_code'],
            'quotation_reviews' => ['revision_no'],
        ] as $table => $columns) {
            foreach ($columns as $column) {
                $this->assertFalse(Schema::hasColumn($table, $column));
            }
        }

        $this->assertSame('QT_DEAL_042', DB::table('quotations')->value('quotation_no'));
        $this->assertSame('published', DB::table('quotations')->value('status'));
        $this->assertTrue(Schema::hasColumn('quotation_reviews', 'quotation_id'));
    }
}
