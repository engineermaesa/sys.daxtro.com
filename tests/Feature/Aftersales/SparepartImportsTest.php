<?php

namespace Tests\Feature\Aftersales;

use App\Models\Aftersales\CustomerImport;
use App\Models\Aftersales\PartStockMovement;
use App\Models\Aftersales\RefSparepart;
use App\Models\User;
use App\Services\Aftersales\SparepartImports;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SparepartImportsTest extends TestCase
{
    private const API = '/api/aftersales/spareparts/imports';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locked' => false]);
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Uploader']);
        Schema::create('customer_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('status')->default('review');
            $table->unsignedBigInteger('created_by_user_id');
            foreach (['lock_version', 'created_count', 'matched_count', 'error_count'] as $field) {
                $table->integer($field)->default(0);
            }
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
        Schema::create('customer_import_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_import_id');
            $table->integer('row_number');
            foreach (['input', 'review', 'decision', 'result'] as $field) {
                $table->json($field)->nullable();
            }
            $table->timestamps();
            $table->unique(['customer_import_id', 'row_number']);
        });
        Schema::create('aftersales_mutation_locks', function (Blueprint $table) {
            $table->string('lock_key', 64)->primary();
        });
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
        });
        (require database_path('migrations/2026_10_28_000000_add_type_to_customer_imports.php'))->up();
        (require database_path('migrations/2026_09_03_010000_create_ref_spareparts_table.php'))->up();
        (require database_path('migrations/2026_09_03_011400_create_part_stock_movements_table.php'))->up();
    }

    private function login(int $id = 1, array $permissions = ['masters.spareparts']): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => $id]);
        $user->shouldReceive('hasPermission')->andReturnUsing(fn ($code) => in_array($code, $permissions, true));
        $this->actingAs($user);
    }

    private function file(array $rows, ?array $headers = null, string $title = 'Import Sparepart'): UploadedFile
    {
        $headers ??= SparepartImports::HEADERS;
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle($title);
        $sheet->fromArray($headers, null, 'A1');
        $book->createSheet()->setTitle('Guide');
        foreach ($rows as $i => $row) {
            $values = array_replace(['Part Number*' => 'PN-'.$i, 'Name*' => 'Part '.$i, 'Price' => '100', 'Stock' => '10', 'Min Stock' => '3'], $row);
            foreach ($headers as $j => $header) {
                $sheet->setCellValueExplicit([$j + 1, $i + 2], (string) ($values[$header] ?? ''), DataType::TYPE_STRING);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'sparepart-import');
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        $this->beforeApplicationDestroyed(fn () => @unlink($path));

        return new UploadedFile($path, 'spareparts.xlsx', null, null, true);
    }

    private function upload(array $rows): array
    {
        return $this->post(self::API, ['file' => $this->file($rows)], ['Accept' => 'application/json'])->assertCreated()->json('data');
    }

    public function test_apply_creates_parts_and_exactly_one_opening_movement_for_positive_stock(): void
    {
        $this->login();
        $data = $this->upload([
            ['Part Number*' => ' pn-open ', 'Brand' => 'Brand', 'Supplier' => 'Supplier', 'Rack Location' => 'A-01'],
            ['Part Number*' => 'PN-ZERO', 'Stock' => '0'],
            ['Part Number*' => 'PN-EMPTY', 'Price' => '', 'Stock' => '', 'Min Stock' => ''],
        ]);
        $response = $this->postJson(self::API.'/'.$data['import']['id'].'/apply', ['lock_version' => 0])->assertOk()
            ->assertJsonPath('data.import.status', 'applied')->assertJsonPath('data.import.lock_version', 1)
            ->assertJsonPath('data.import.created_count', 3)->assertJsonPath('data.import.matched_count', 0)
            ->assertJsonPath('data.import.error_count', 0)->assertJsonPath('data.rows.0.result.status', 'applied')
            ->assertJsonPath('data.rows.1.result.stock_movement_id', null)->assertJsonPath('data.rows.2.result.opening_stock', 0);
        $this->assertNotNull($response->json('data.import.applied_at'));
        $this->assertSame(3, RefSparepart::count());
        $part = RefSparepart::where('part_number', 'PN-OPEN')->firstOrFail();
        $this->assertSame('Brand', $part->brand);
        $this->assertSame('Supplier', $part->supplier);
        $this->assertSame('A-01', $part->rack_location);
        $this->assertSame(1, (int) $part->created_by);
        $this->assertSame(10, $part->stock);
        $this->assertSame('0.00', RefSparepart::where('part_number', 'PN-EMPTY')->firstOrFail()->price);
        $this->assertSame(1, PartStockMovement::count());
        $movement = PartStockMovement::firstOrFail();
        $this->assertSame($part->id, (int) $movement->ref_sparepart_id);
        $this->assertSame(10, (int) $movement->qty);
        $this->assertSame('in', $movement->type);
        $this->assertSame('Opening stock (import)', $movement->note);
        $this->assertSame(1, (int) $movement->created_by);
        $this->assertNull($movement->ticket_id);
    }

    public function test_apply_skips_active_deleted_duplicate_and_error_rows_without_overwriting_master(): void
    {
        $active = RefSparepart::create(['part_number' => 'PN-EXIST', 'name' => 'Keep name', 'price' => 400, 'stock' => 50]);
        $deleted = RefSparepart::create(['part_number' => 'PN-DELETED', 'name' => 'Archived', 'stock' => 3]);
        $deleted->delete();
        $this->login();
        $data = $this->upload([
            ['Part Number*' => 'PN-NEW'], ['Part Number*' => 'PN-EXIST', 'Name*' => 'Do not update'],
            ['Part Number*' => 'PN-DELETED'], ['Part Number*' => ' pn-new '], ['Part Number*' => 'PN-BAD', 'Stock' => '-1'],
        ]);
        $this->postJson(self::API.'/'.$data['import']['id'].'/apply', ['lock_version' => 0])->assertOk()
            ->assertJsonPath('data.import.created_count', 1)->assertJsonPath('data.import.matched_count', 2)
            ->assertJsonPath('data.import.error_count', 2)->assertJsonPath('data.rows.1.result.reason', 'already_exists')
            ->assertJsonPath('data.rows.2.result.reason', 'soft_deleted')->assertJsonPath('data.rows.3.result.reason', 'duplicate_part_number')
            ->assertJsonPath('data.rows.4.result.reason', 'validation_error');
        $this->assertSame('Keep name', $active->fresh()->name);
        $this->assertSame('400.00', $active->fresh()->price);
        $this->assertSame(50, $active->fresh()->stock);
        $this->assertNotNull(RefSparepart::withTrashed()->findOrFail($deleted->id)->deleted_at);
        $this->assertSame(3, RefSparepart::withTrashed()->count());
        $this->assertSame(1, PartStockMovement::count());
        $response = $this->get(self::API.'/'.$data['import']['id'].'/export')->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'applied-errors');
        $this->beforeApplicationDestroyed(fn () => @unlink($path));
        file_put_contents($path, $response->streamedContent());
        $book = IOFactory::load($path);
        $this->assertSame(3, $book->getActiveSheet()->getHighestDataRow());
        $this->assertSame('Result', $book->getActiveSheet()->getCell('M1')->getValue());
        $this->assertSame('skipped', json_decode($book->getActiveSheet()->getCell('M2')->getValue(), true)['status']);
        $book->disconnectWorksheets();
    }

    public function test_apply_is_idempotent_even_when_retrying_original_version(): void
    {
        $this->login();
        $data = $this->upload([[]]);
        $url = self::API.'/'.$data['import']['id'].'/apply';
        $first = $this->postJson($url, ['lock_version' => 0])->assertOk()->json('data');
        $second = $this->postJson($url, ['lock_version' => 0])->assertOk()->json('data');
        $this->assertSame($first['rows'][0]['result'], $second['rows'][0]['result']);
        $this->assertSame($first['import']['applied_at'], $second['import']['applied_at']);
        $this->assertSame(1, $second['import']['lock_version']);
        $this->assertSame(1, RefSparepart::count());
        $this->assertSame(1, PartStockMovement::count());
        $this->assertSame(10, RefSparepart::firstOrFail()->stock);
    }

    public function test_two_batches_with_the_same_part_number_only_create_one_part_and_movement(): void
    {
        $this->login();
        $first = $this->upload([['Part Number*' => 'PN-SHARED']]);
        $second = $this->upload([['Part Number*' => ' pn-shared ', 'Stock' => '20']]);
        $this->postJson(self::API.'/'.$first['import']['id'].'/apply', ['lock_version' => 0])->assertOk();
        $this->postJson(self::API.'/'.$second['import']['id'].'/apply', ['lock_version' => 0])->assertOk()
            ->assertJsonPath('data.import.created_count', 0)->assertJsonPath('data.import.matched_count', 1)
            ->assertJsonPath('data.rows.0.review.status', 'already_exists')->assertJsonPath('data.rows.0.result.reason', 'already_exists');
        $this->assertSame(1, RefSparepart::count());
        $this->assertSame(10, RefSparepart::firstOrFail()->stock);
        $this->assertSame(1, PartStockMovement::count());
        $this->assertSame(1, DB::table('aftersales_mutation_locks')->count());
    }

    public function test_apply_rechecks_catalogue_after_manual_creation_or_soft_delete(): void
    {
        $this->login();
        $data = $this->upload([['Part Number*' => 'PN-MANUAL'], ['Part Number*' => 'PN-ARCHIVED']]);
        $this->postJson('/api/aftersales/spareparts', ['part_number' => ' pn-manual ', 'name' => 'Manual', 'stock' => 4])->assertCreated();
        $archived = RefSparepart::create(['part_number' => 'PN-ARCHIVED', 'name' => 'Archived']);
        $archived->delete();
        $this->postJson(self::API.'/'.$data['import']['id'].'/apply', ['lock_version' => 0])->assertOk()
            ->assertJsonPath('data.import.created_count', 0)->assertJsonPath('data.import.matched_count', 2)
            ->assertJsonPath('data.rows.0.result.reason', 'already_exists')->assertJsonPath('data.rows.1.result.reason', 'soft_deleted');
        $this->assertSame(2, RefSparepart::withTrashed()->count());
        $this->assertSame(0, PartStockMovement::count());
        $this->assertSame(4, RefSparepart::firstOrFail()->stock);
    }

    public function test_failed_movement_rolls_back_the_whole_batch_and_allows_a_clean_retry(): void
    {
        $this->login();
        $data = $this->upload([[], []]);
        $attempts = 0;
        PartStockMovement::creating(function () use (&$attempts) {
            if (++$attempts === 2) {
                throw new \RuntimeException('Simulated movement failure');
            }
        });
        try {
            app(SparepartImports::class)->apply(CustomerImport::findOrFail($data['import']['id']), 0, 1);
            $this->fail('A failed movement must roll back the batch.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated movement failure', $exception->getMessage());
        } finally {
            PartStockMovement::flushEventListeners();
        }
        $batch = CustomerImport::findOrFail($data['import']['id']);
        $this->assertSame('review', $batch->status);
        $this->assertSame(0, $batch->lock_version);
        $this->assertNull($batch->applied_at);
        $this->assertSame(0, RefSparepart::count());
        $this->assertSame(0, PartStockMovement::count());
        $this->assertSame(0, DB::table('aftersales_mutation_locks')->count());
        foreach ($batch->rows as $row) {
            $this->assertNull($row->result);
        }
        $this->postJson(self::API.'/'.$batch->id.'/apply', ['lock_version' => 0])->assertOk()->assertJsonPath('data.import.created_count', 2);
        $this->assertSame(2, RefSparepart::count());
        $this->assertSame(2, PartStockMovement::count());
    }

    public function test_apply_enforces_permission_owner_type_version_and_current_review(): void
    {
        $this->login();
        $data = $this->upload([[]]);
        $url = self::API.'/'.$data['import']['id'].'/apply';
        $this->postJson($url, [])->assertUnprocessable()->assertJsonValidationErrors('lock_version');
        $this->postJson($url, ['lock_version' => -1])->assertUnprocessable();
        $this->postJson($url, ['lock_version' => 1])->assertConflict();
        $this->login(1, ['masters.service-customers']);
        $this->postJson($url, ['lock_version' => 0])->assertForbidden();
        $this->login(2);
        $this->postJson($url, ['lock_version' => 0])->assertForbidden();
        $this->login(1, ['masters.spareparts', 'masters.service-customers']);
        $customer = CustomerImport::create(['file_name' => 'customer.xlsx', 'created_by_user_id' => 1]);
        $this->postJson(self::API.'/'.$customer->id.'/apply', ['lock_version' => 0])->assertNotFound();
        $this->putJson(self::API.'/'.$data['import']['id'].'/review', ['lock_version' => 0, 'rows' => [['id' => $data['rows'][0]['id'], 'input' => ['Stock' => '5']]]])->assertOk();
        $this->postJson($url, ['lock_version' => 0])->assertConflict();
        $this->postJson($url, ['lock_version' => 1])->assertOk();
        $this->login(2);
        $this->postJson($url, ['lock_version' => 0])->assertForbidden();
        $this->assertSame(1, RefSparepart::count());
        $this->assertSame(5, RefSparepart::firstOrFail()->stock);
    }

    public function test_apply_never_promotes_previously_skipped_rows_without_a_new_review(): void
    {
        $part = RefSparepart::create(['part_number' => 'PN-REMOVED', 'name' => 'Removed']);
        $this->login();
        $data = $this->upload([['Part Number*' => 'PN-REMOVED']]);
        $part->forceDelete();
        $url = self::API.'/'.$data['import']['id'];
        $this->postJson($url.'/apply', ['lock_version' => 0])->assertConflict();
        $this->assertSame(0, RefSparepart::count());
        $this->assertSame('review', CustomerImport::findOrFail($data['import']['id'])->status);
        $this->putJson($url.'/review', ['lock_version' => 0, 'rows' => [['id' => $data['rows'][0]['id'], 'input' => ['Name*' => 'Reviewed new part']]]])->assertOk();
        $this->postJson($url.'/apply', ['lock_version' => 1])->assertOk()->assertJsonPath('data.import.created_count', 1);
    }

    public function test_apply_revalidates_input_instead_of_trusting_a_forged_review_flag(): void
    {
        $this->login();
        $data = $this->upload([['Stock' => '-1']]);
        $row = CustomerImport::findOrFail($data['import']['id'])->rows()->firstOrFail();
        $review = $row->review;
        $review['can_apply'] = true;
        $review['part']['stock'] = 10;
        $row->update(['review' => $review]);
        $this->postJson(self::API.'/'.$data['import']['id'].'/apply', ['lock_version' => 0])->assertOk()
            ->assertJsonPath('data.import.created_count', 0)->assertJsonPath('data.import.error_count', 1)
            ->assertJsonPath('data.rows.0.result.reason', 'validation_error');
        $this->assertSame(0, RefSparepart::count());
        $this->assertSame(0, PartStockMovement::count());
    }

    public function test_manual_sparepart_writers_share_locks_and_reject_new_duplicate_numbers(): void
    {
        $this->login();
        $data = $this->upload([['Part Number*' => 'PN-SHARED']]);
        $this->postJson(self::API.'/'.$data['import']['id'].'/apply', ['lock_version' => 0])->assertOk();
        $this->postJson('/api/aftersales/spareparts', ['part_number' => ' pn-shared ', 'name' => 'Duplicate'])->assertUnprocessable()->assertJsonValidationErrors('part_number');
        $other = RefSparepart::create(['part_number' => 'PN-OTHER', 'name' => 'Other']);
        $this->putJson('/api/aftersales/spareparts/'.$other->id, ['part_number' => 'PN-SHARED'])->assertUnprocessable()->assertJsonValidationErrors('part_number');
        $existing = RefSparepart::where('part_number', 'PN-SHARED')->firstOrFail();
        $existing->delete();
        $this->postJson('/api/aftersales/spareparts', ['part_number' => 'PN-SHARED', 'name' => 'Do not recreate'])->assertUnprocessable();
        $this->putJson('/api/aftersales/spareparts/'.$other->id, ['part_number' => ' pn-unique ', 'name' => 'Updated'])->assertOk()
            ->assertJsonPath('data.data.part_number', 'PN-UNIQUE');
        $this->assertSame(1, (int) $other->fresh()->updated_by);
        $this->assertSame(2, RefSparepart::withTrashed()->count());
        $this->assertSame(1, PartStockMovement::count());
    }

    public function test_upload_reviews_all_statuses_and_does_not_change_master_parts_or_stock(): void
    {
        $existing = RefSparepart::create(['part_number' => 'PN-EXISTS', 'name' => 'Existing name', 'price' => 100, 'stock' => 50, 'min_stock' => 5]);
        $deleted = RefSparepart::create(['part_number' => 'PN-ARCHIVE', 'name' => 'Archived', 'stock' => 6]);
        $deleted->delete();
        $this->login();
        $data = $this->upload([
            ['Part Number*' => ' pn-new '],
            ['Part Number*' => 'pn-exists', 'Name*' => 'Do not overwrite', 'Price' => '1', 'Stock' => '0', 'Min Stock' => '0'],
            ['Part Number*' => 'PN-WARNING', 'Price' => '', 'Stock' => '', 'Min Stock' => ''],
            ['Part Number*' => 'PN-ARCHIVE'],
            ['Part Number*' => 'PN-NEW'],
        ]);
        $this->assertSame('sparepart', $data['import']['type']);
        $this->assertSame('review', $data['import']['status']);
        $this->assertSame(['new', 'already_exists', 'warning', 'warning', 'error'], array_column(array_column($data['rows'], 'review'), 'status'));
        $this->assertSame(['new' => 1, 'already_exists' => 1, 'warning' => 2, 'error' => 1, 'reorder' => 2, 'applicable' => 2], $data['summary']);
        $this->assertSame('PN-NEW', $data['rows'][0]['review']['part']['part_number']);
        $this->assertSame('0.00', $data['rows'][2]['review']['part']['price']);
        $this->assertSame(0, $data['rows'][2]['review']['part']['stock']);
        $this->assertTrue($data['rows'][2]['review']['can_apply']);
        $this->assertFalse($data['rows'][3]['review']['can_apply']);
        $this->assertSame('soft_deleted', $data['rows'][3]['review']['skip_reason']);
        $this->assertSame(2, $data['rows'][4]['review']['duplicate_of_row']);
        $this->assertSame(1, RefSparepart::count());
        $this->assertSame(2, RefSparepart::withTrashed()->count());
        $this->assertSame('Existing name', $existing->fresh()->name);
        $this->assertSame(50, $existing->fresh()->stock);
        $this->assertSame('100.00', $existing->fresh()->price);
        $this->assertSame(0, DB::table('part_stock_movements')->count());
        $this->getJson(self::API.'/'.$data['import']['id'])->assertOk()->assertJsonPath('data.summary.error', 1);
    }

    public function test_validation_errors_cover_required_fields_numbers_precision_ranges_and_formulas(): void
    {
        $this->login();
        $rows = [
            ['Part Number*' => ''], ['Name*' => ''], ['Price' => '-1'], ['Price' => 'abc'], ['Price' => '1.234'],
            ['Price' => '10000000000000'], ['Stock' => '-1'], ['Stock' => '1.5'], ['Stock' => '2147483648'],
            ['Min Stock' => '-1'], ['Min Stock' => 'abc'], ['Brand' => str_repeat('X', 256)],
            ['Supplier' => str_repeat('X', 256)], ['Rack Location' => str_repeat('X', 256)], ['Name*' => '=1+1'],
        ];
        $data = $this->upload($rows);
        $this->assertSame(count($rows), $data['summary']['error']);
        $this->assertSame(0, $data['summary']['applicable']);
        foreach ($data['rows'] as $row) {
            $this->assertNotEmpty($row['review']['errors']);
            $this->assertFalse($row['review']['can_apply']);
        }
        $this->assertSame(0, RefSparepart::count());
    }

    public function test_review_renormalizes_all_rows_and_duplicate_decisions_with_optimistic_locking(): void
    {
        $this->login();
        $data = $this->upload([['Part Number*' => 'pn-a'], ['Part Number*' => ' PN-A ']]);
        $url = self::API.'/'.$data['import']['id'].'/review';
        $change = ['lock_version' => 0, 'rows' => [['id' => $data['rows'][0]['id'], 'input' => ['Part Number*' => 'pn-b', 'Price' => '12.50']]]];
        $this->putJson($url, $change)->assertOk()->assertJsonPath('data.import.lock_version', 1)
            ->assertJsonPath('data.summary.error', 0)->assertJsonPath('data.summary.new', 2)
            ->assertJsonPath('data.rows.0.review.part.part_number', 'PN-B')->assertJsonPath('data.rows.0.review.part.price', '12.50');
        $this->putJson($url, $change)->assertConflict();
        RefSparepart::create(['part_number' => 'PN-B', 'name' => 'Added after upload', 'stock' => 4]);
        $change['lock_version'] = 1;
        $this->putJson($url, $change)->assertOk()->assertJsonPath('data.rows.0.review.status', 'already_exists')
            ->assertJsonPath('data.rows.0.review.can_apply', false)->assertJsonPath('data.import.lock_version', 2);
        $this->assertSame(1, RefSparepart::count());
        $this->assertSame(4, RefSparepart::first()->stock);
    }

    public function test_review_rejects_foreign_rows_unknown_columns_non_scalar_cells_and_applied_batches(): void
    {
        $this->login();
        $first = $this->upload([[]]);
        $other = $this->upload([[]]);
        $url = self::API.'/'.$first['import']['id'].'/review';
        $this->putJson($url, ['lock_version' => 0, 'rows' => [
            ['id' => $first['rows'][0]['id'], 'input' => ['Name*' => 'Must roll back']],
            ['id' => $other['rows'][0]['id'], 'input' => ['Name*' => 'Foreign']],
        ]])->assertNotFound();
        $this->assertSame('Part 0', CustomerImport::findOrFail($first['import']['id'])->rows->first()->input['Name*']);
        $this->putJson($url, ['lock_version' => 0, 'rows' => [['id' => $first['rows'][0]['id'], 'input' => ['unknown' => 'value']]]])->assertUnprocessable();
        $this->putJson($url, ['lock_version' => 0, 'rows' => [['id' => $first['rows'][0]['id'], 'input' => ['Stock' => ['nested']]]]])->assertUnprocessable();
        CustomerImport::findOrFail($first['import']['id'])->update(['status' => 'applied']);
        $this->putJson($url, ['lock_version' => 0, 'rows' => [['id' => $first['rows'][0]['id'], 'input' => ['Price' => '10']]]])->assertConflict();
    }

    public function test_matching_uses_normalized_legacy_keys_and_prefers_skipping_active_records(): void
    {
        DB::table('ref_spareparts')->insert([
            ['part_number' => "\t legacy-pn \t", 'name' => 'Legacy', 'deleted_at' => null],
            ['part_number' => 'LEGACY-PN', 'name' => 'Deleted duplicate', 'deleted_at' => now()],
        ]);
        $this->login();
        $data = $this->upload([['Part Number*' => ' legacy-pn ']]);
        $this->assertSame('already_exists', $data['rows'][0]['review']['status']);
        $this->assertFalse($data['rows'][0]['review']['can_apply']);
        $this->assertSame(2, RefSparepart::withTrashed()->count());
    }

    public function test_invalid_headers_sheet_empty_corrupt_and_oversized_uploads_create_no_batch(): void
    {
        $this->login();
        $wrong = SparepartImports::HEADERS;
        $wrong[0] = 'Part Number';
        $files = [$this->file([[]], $wrong), $this->file([[]], array_merge(SparepartImports::HEADERS, ['Extra'])), $this->file([[]], null, 'Wrong Sheet'), $this->file([]), UploadedFile::fake()->createWithContent('broken.xlsx', 'invalid xlsx')];
        foreach ($files as $file) {
            $this->post(self::API, ['file' => $file], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
        }
        $file = $this->file([]);
        $book = IOFactory::load($file->getRealPath());
        $book->getActiveSheet()->setCellValue('A5002', 'TOO-MANY');
        (new Xlsx($book))->save($file->getRealPath());
        $book->disconnectWorksheets();
        $this->post(self::API, ['file' => $file], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->post(self::API, ['file' => UploadedFile::fake()->create('large.xlsx', 20481)], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->postJson(self::API, [])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->assertSame(0, CustomerImport::count());
        $this->assertSame(0, DB::table('customer_import_rows')->count());
    }

    public function test_download_template_matches_parser_and_supplied_guide(): void
    {
        $this->login();
        $response = $this->get(self::API.'/template')->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);
        $this->assertSame(['Import Sparepart', 'Guide'], $book->getSheetNames());
        $this->assertSame(SparepartImports::HEADERS, $book->getSheetByName('Import Sparepart')->rangeToArray('A1:H1', null, false, false)[0]);
        $this->assertStringContainsString('Opening stock (import)', $book->getSheetByName('Guide')->getCell('C7')->getValue());
        $book->disconnectWorksheets();
        $this->post(self::API, ['file' => new UploadedFile($path, 'template.xlsx', null, null, true), 'type' => 'customer', 'created_by_user_id' => 99], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.import.type', 'sparepart')->assertJsonPath('data.import.created_by_user_id', 1)->assertJsonPath('data.summary.error', 0);
        $this->assertSame(0, RefSparepart::count());
    }

    public function test_error_export_filters_rows_and_keeps_formula_like_input_as_literal_text(): void
    {
        $this->login();
        $data = $this->upload([[], ['Name*' => '=1+1']]);
        $url = self::API.'/'.$data['import']['id'].'/export';
        foreach (['' => 2, '?errors_only=0' => 3] as $query => $lastRow) {
            $response = $this->get($url.$query)->assertOk();
            $path = tempnam(sys_get_temp_dir(), 'sparepart-review');
            $this->beforeApplicationDestroyed(fn () => @unlink($path));
            file_put_contents($path, $response->streamedContent());
            $book = IOFactory::load($path);
            $sheet = $book->getActiveSheet();
            $this->assertSame($lastRow, $sheet->getHighestDataRow());
            $errorRow = $lastRow;
            $this->assertSame('=1+1', $sheet->getCell('B'.$errorRow)->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B'.$errorRow)->getDataType());
            $this->assertStringContainsString('literal value', $sheet->getCell('K'.$errorRow)->getValue());
            $book->disconnectWorksheets();
        }
        $this->assertSame(0, RefSparepart::count());
        $this->assertSame(0, DB::table('part_stock_movements')->count());
    }

    public function test_permission_owner_and_type_checks_cover_all_import_operations(): void
    {
        $this->getJson(self::API.'/template')->assertUnauthorized();
        $this->login(1, ['masters.service-customers']);
        $this->getJson(self::API.'/template')->assertForbidden();
        $this->postJson(self::API, [])->assertForbidden();
        $this->login();
        $data = $this->upload([[]]);
        $url = self::API.'/'.$data['import']['id'];
        $this->login(2);
        $this->getJson($url)->assertForbidden();
        $this->putJson($url.'/review', [])->assertForbidden();
        $this->getJson($url.'/export')->assertForbidden();
        $this->login(1, ['masters.spareparts', 'masters.service-customers']);
        $customer = CustomerImport::create(['file_name' => 'customer.xlsx', 'created_by_user_id' => 1]);
        $this->getJson(self::API.'/'.$customer->id)->assertNotFound();
        $this->putJson(self::API.'/'.$customer->id.'/review', [])->assertNotFound();
        $this->getJson(self::API.'/'.$customer->id.'/export')->assertNotFound();
        $this->getJson('/api/aftersales/customers/imports/'.$data['import']['id'])->assertNotFound();
    }

    public function test_actual_excel_formulas_are_not_evaluated_as_import_values(): void
    {
        $this->login();
        $file = $this->file([[]]);
        $book = IOFactory::load($file->getRealPath());
        $book->getActiveSheet()->setCellValue('E2', '=10+20');
        (new Xlsx($book))->save($file->getRealPath());
        $book->disconnectWorksheets();
        $response = $this->post(self::API, ['file' => $file], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('data.summary.error', 1)->assertJsonPath('data.rows.0.review.can_apply', false);
        $this->assertStringContainsString('literal value', implode(' ', $response->json('data.rows.0.review.errors')));
        $this->assertSame(0, RefSparepart::count());
    }

    public function test_second_duplicate_is_error_even_if_first_row_is_invalid_and_zero_text_is_preserved(): void
    {
        $this->login();
        $data = $this->upload([
            ['Part Number*' => 'pn-dup', 'Name*' => ''],
            ['Part Number*' => ' PN-DUP '],
            ['Part Number*' => '0', 'Name*' => '0', 'Brand' => '0', 'Supplier' => '0', 'Rack Location' => '0'],
        ]);
        $this->assertSame(2, $data['summary']['error']);
        $this->assertSame(2, $data['rows'][1]['review']['duplicate_of_row']);
        foreach (['part_number', 'name', 'brand', 'supplier', 'rack_location'] as $field) {
            $this->assertSame('0', $data['rows'][2]['review']['part'][$field]);
        }
    }

    public function test_bulk_review_reads_catalogue_once_and_does_not_mutate_stock(): void
    {
        $this->login();
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $data = $this->upload(array_fill(0, 250, []));
            $queries = DB::getQueryLog();
            $reads = array_filter($queries, fn ($query) => str_starts_with(strtolower($query['query']), 'select') && str_contains($query['query'], 'ref_spareparts'));
            $this->assertCount(1, $reads);
            $this->assertSame(250, $data['summary']['new']);
            $this->assertSame(0, RefSparepart::count());
            $this->assertSame(0, DB::table('part_stock_movements')->count());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }
}
