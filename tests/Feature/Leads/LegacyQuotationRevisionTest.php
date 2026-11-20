<?php

namespace Tests\Feature\Leads;

use App\Models\Orders\Quotation;
use App\Models\Orders\QuotationItems;
use App\Models\Orders\TempQuotation;
use App\Services\Orders\QuotationRevisions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyQuotationRevisionTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = DB::getDefaultConnection();
        config(['database.connections.legacy_quotation_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('legacy_quotation_test');

        foreach (['leads', 'users', 'ref_products'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->softDeletes();
            });
            DB::table($tableName)->insert(['id' => 1]);
        }
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('created_by');
            $table->string('quotation_no')->nullable();
            $table->string('status')->default('review');
            foreach (['subtotal', 'tax_pct', 'tax_total', 'total_discount', 'grand_total', 'booking_fee'] as $column) {
                $table->double($column)->nullable();
            }
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->double('qty');
            $table->text('description');
            $table->double('unit_price');
            $table->double('discount_pct');
            $table->double('line_total');
            $table->boolean('is_visible_pdf')->default(true);
            $table->unsignedBigInteger('merge_into_item_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('quotation_payment_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_id');
            $table->integer('term_no');
            $table->double('percentage');
            $table->text('description')->nullable();
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

        foreach ([
            '2026_08_20_100000_create_temp_quotations_table.php',
            '2026_08_20_100001_create_temp_quotation_items_table.php',
            '2026_08_20_100002_create_temp_quotation_payment_terms_table.php',
            '2026_11_20_000000_add_revision_fields_to_quotations.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
    }

    protected function tearDown(): void
    {
        DB::purge('legacy_quotation_test');
        DB::setDefaultConnection($this->originalConnection);

        parent::tearDown();
    }

    private function legacyQuotation(array $attributes = []): Quotation
    {
        return Quotation::create(array_merge([
            'lead_id' => 1, 'created_by' => 1, 'quotation_no' => 'QT_DEAL_042',
            'status' => 'published', 'subtotal' => 150, 'tax_pct' => 11,
            'tax_total' => 16.5, 'total_discount' => 5, 'grand_total' => 166.5,
            'booking_fee' => 20, 'expiry_date' => '2026-12-01',
        ], $attributes));
    }

    public function test_backfill_assigns_r0_including_deleted_quotations_without_creating_snapshots(): void
    {
        $legacy = $this->legacyQuotation();
        $deleted = $this->legacyQuotation(['quotation_no' => 'QT_DEAL_043', 'revision_code' => '']);
        $deleted->delete();
        $versioned = $this->legacyQuotation(['revision_no' => 2, 'revision_code' => 'QT_DEAL_042_R2']);
        $unnumbered = $this->legacyQuotation(['quotation_no' => null]);
        $before = DB::table('quotations')->where('id', $legacy->id)->first();

        $migration = require database_path('migrations/2026_11_20_000100_backfill_legacy_quotation_revision_codes.php');
        $migration->up();
        $migration->up();

        $this->assertSame('QT_DEAL_042_R0', $legacy->fresh()->revision_code);
        $this->assertSame('QT_DEAL_043_R0', Quotation::withTrashed()->findOrFail($deleted->id)->revision_code);
        $this->assertSame('QT_DEAL_042_R2', $versioned->fresh()->revision_code);
        $this->assertNull($unnumbered->fresh()->revision_code);
        $after = DB::table('quotations')->where('id', $legacy->id)->first();
        unset($after->revision_code, $before->revision_code);
        $this->assertEquals($before, $after);
        $this->assertSame(0, TempQuotation::count());
    }

    public function test_preview_routes_create_r0_before_rendering_and_reuse_it_on_the_next_opening(): void
    {
        Schema::create('lead_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->timestamp('released_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->softDeletes();
        });
        $quotation = $this->legacyQuotation();
        $this->withoutMiddleware();
        Pdf::shouldReceive('loadView')->twice()
            ->with('pdfs.quotation_body', \Mockery::on(function ($data) use ($quotation) {
                return $data['quotation']->id === $quotation->id
                    && $data['quotation']->revision_code === 'QT_DEAL_042_R0'
                    && $quotation->revisions()->where('status', 'submitted')->count() === 1;
            }))->andReturnSelf();
        Pdf::shouldReceive('setPaper')->twice()->with('A4', 'portrait')->andReturnSelf();
        Pdf::shouldReceive('stream')->twice()->with('QT_DEAL_042.pdf')
            ->andReturn(response('quotation-preview'));

        $this->assertSame(0, TempQuotation::count());
        $this->get(route('quotations.preview', $quotation->id))->assertOk()->assertSee('quotation-preview');
        $this->get(route('quotations.view', $quotation->id))->assertOk()->assertSee('quotation-preview');
        $this->assertSame(1, $quotation->revisions()->count());
    }

    public function test_lazy_snapshot_copies_active_data_and_remaps_merged_items_without_changing_the_quotation(): void
    {
        $quotation = $this->legacyQuotation();
        DB::table('quotations')->where('id', $quotation->id)->update([
            'created_at' => '2025-10-01 08:00:00', 'updated_at' => '2025-10-02 09:00:00',
        ]);
        $parent = $quotation->items()->make([
            'product_id' => 1, 'qty' => 1, 'description' => 'Machine',
            'unit_price' => 100, 'discount_pct' => 5, 'line_total' => 95, 'is_visible_pdf' => true,
        ]);
        $parent->id = 10;
        $parent->save();
        $child = $quotation->items()->create([
            'product_id' => null, 'qty' => 1, 'description' => 'Hidden addon',
            'unit_price' => 55, 'discount_pct' => 0, 'line_total' => 55,
            'is_visible_pdf' => false, 'merge_into_item_id' => $parent->id,
        ]);
        $removed = $quotation->items()->create([
            'qty' => 1, 'description' => 'Removed', 'unit_price' => 10, 'discount_pct' => 0, 'line_total' => 10,
        ]);
        $removed->delete();
        $quotation->paymentTerms()->create(['term_no' => 2, 'percentage' => 60, 'description' => 'Delivery']);
        $quotation->paymentTerms()->create(['term_no' => 1, 'percentage' => 40, 'description' => 'Deposit']);
        $removedTerm = $quotation->paymentTerms()->create(['term_no' => 3, 'percentage' => 10]);
        $removedTerm->delete();
        $reviewId = DB::table('quotation_reviews')->insertGetId([
            'quotation_id' => $quotation->id, 'decision' => 'approve', 'deleted_at' => '2025-10-03 09:00:00',
        ]);
        $before = DB::table('quotations')->where('id', $quotation->id)->first();

        $snapshot = app(QuotationRevisions::class)->ensureInitialSnapshot($quotation);

        $this->assertSame('QT_DEAL_042_R0', $snapshot->revision_code);
        $this->assertSame('QT_DEAL_042_R0', $quotation->revision_code);
        $this->assertSame(0, $snapshot->revision_no);
        $this->assertSame('submitted', $snapshot->status);
        $this->assertEquals($quotation->only([
            'lead_id', 'created_by', 'subtotal', 'tax_pct', 'tax_total', 'total_discount', 'grand_total', 'booking_fee',
        ]), $snapshot->only([
            'lead_id', 'created_by', 'subtotal', 'tax_pct', 'tax_total', 'total_discount', 'grand_total', 'booking_fee',
        ]));
        $this->assertSame('2025-10-01 08:00:00', $snapshot->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2025-10-02 09:00:00', $snapshot->updated_at->format('Y-m-d H:i:s'));
        $copied = $snapshot->items()->orderBy('id')->get();
        $this->assertCount(2, $copied);
        $this->assertSame(1, $copied[0]->product_id);
        $this->assertNull($copied[1]->product_id);
        $this->assertSame('Hidden addon', $copied[1]->description);
        $this->assertFalse($copied[1]->is_visible_pdf);
        $this->assertSame($copied[0]->id, $copied[1]->merge_into_item_id);
        $this->assertNotEquals($parent->id, $copied[1]->merge_into_item_id);
        $this->assertSame([1, 2], $snapshot->paymentTerms->pluck('term_no')->all());
        $this->assertSame(['Deposit', 'Delivery'], $snapshot->paymentTerms->pluck('description')->all());
        $this->assertEquals([40, 60], $snapshot->paymentTerms->pluck('percentage')->all());
        $after = DB::table('quotations')->where('id', $quotation->id)->first();
        unset($after->revision_code, $before->revision_code);
        $this->assertEquals($before, $after);
        $this->assertSame($parent->id, $child->fresh()->merge_into_item_id);
        $this->assertSame(3, QuotationItems::withTrashed()->count());
        $this->assertSame(0, DB::table('quotation_reviews')->where('id', $reviewId)->value('revision_no'));
    }

    public function test_repeated_opening_keeps_the_snapshot_immutable_and_does_not_consume_existing_drafts(): void
    {
        $quotation = $this->legacyQuotation();
        $draft = TempQuotation::create(['lead_id' => 1, 'created_by' => 1, 'status' => 'draft', 'grand_total' => 999]);
        $oldSubmitted = TempQuotation::create(['lead_id' => 1, 'created_by' => 1, 'status' => 'submitted', 'grand_total' => 111]);
        $service = app(QuotationRevisions::class);
        $first = $service->ensureInitialSnapshot($quotation);
        $quotation->update(['grand_total' => 888]);

        $again = $service->ensureInitialSnapshot($quotation->fresh());

        $this->assertSame($first->id, $again->id);
        $this->assertEquals(166.5, $again->grand_total);
        $this->assertSame(1, $quotation->revisions()->where('status', 'submitted')->count());
        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertNull($draft->fresh()->quotation_id);
        $this->assertNull($oldSubmitted->fresh()->quotation_id);
        $this->assertEquals(999, $draft->fresh()->grand_total);
    }

    public function test_initialization_uses_fresh_database_data_instead_of_stale_loaded_relations(): void
    {
        $quotation = $this->legacyQuotation();
        $quotation->load('items', 'paymentTerms');
        $quotation->items()->create([
            'qty' => 1, 'description' => 'Latest item', 'unit_price' => 150, 'discount_pct' => 0, 'line_total' => 150,
        ]);
        $quotation->paymentTerms()->create(['term_no' => 1, 'percentage' => 100]);

        $snapshot = app(QuotationRevisions::class)->ensureInitialSnapshot($quotation);

        $this->assertSame('Latest item', $snapshot->items->sole()->description);
        $this->assertCount(1, $snapshot->paymentTerms);
    }

    public function test_partial_snapshot_failure_rolls_back_header_and_children(): void
    {
        $quotation = $this->legacyQuotation();
        $quotation->items()->create([
            'qty' => 1, 'description' => 'Machine', 'unit_price' => 150, 'discount_pct' => 0, 'line_total' => 150,
        ]);
        $quotation->paymentTerms()->create(['term_no' => 1, 'percentage' => 100]);
        DB::unprepared("CREATE TRIGGER fail_snapshot_term BEFORE INSERT ON temp_quotation_payment_terms BEGIN SELECT RAISE(ABORT, 'forced snapshot failure'); END");

        try {
            app(QuotationRevisions::class)->ensureInitialSnapshot($quotation);
            $this->fail('Expected snapshot insertion to fail.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('forced snapshot failure', $exception->getMessage());
        }

        $this->assertNull($quotation->fresh()->revision_code);
        $this->assertNull($quotation->revision_code);
        $this->assertSame(0, TempQuotation::count());
        $this->assertSame(0, DB::table('temp_quotation_items')->count());
        $this->assertSame(0, DB::table('temp_quotation_payment_terms')->count());
        $this->assertSame(1, $quotation->items()->count());
        $this->assertSame('published', $quotation->fresh()->status);
    }

    public function test_versioned_and_unnumbered_quotations_do_not_get_a_fabricated_r0(): void
    {
        $versioned = $this->legacyQuotation(['revision_no' => 2, 'revision_code' => 'QT_DEAL_042_R2']);
        $unnumbered = $this->legacyQuotation(['quotation_no' => null]);
        $service = app(QuotationRevisions::class);

        $this->assertNull($service->ensureInitialSnapshot($versioned));
        $this->assertNull($service->ensureInitialSnapshot($unnumbered));
        $this->assertSame('QT_DEAL_042_R2', $versioned->fresh()->revision_code);
        $this->assertSame(0, TempQuotation::count());
    }
}
