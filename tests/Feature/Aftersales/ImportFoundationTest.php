<?php

namespace Tests\Feature\Aftersales;

use App\Models\Aftersales\CustomerImport;
use App\Models\Aftersales\CustomerImportRow;
use App\Models\User;
use App\Services\Aftersales\CustomerImports;
use App\Services\Aftersales\ImportAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ImportFoundationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locked' => false]);
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('customer_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('status')->default('review');
            $table->integer('lock_version')->default(0);
            $table->integer('created_count')->default(0);
            $table->integer('matched_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->unsignedBigInteger('created_by_user_id');
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
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Uploader']);
        (require database_path('migrations/2026_10_28_000000_add_type_to_customer_imports.php'))->up();
    }

    private function user(array $permissions = ['masters.service-customers'], int $id = 1): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => $id]);
        $user->shouldReceive('hasPermission')->andReturnUsing(fn ($code) => in_array($code, $permissions, true));

        return $user;
    }

    private function batch(string $type = CustomerImport::TYPE_CUSTOMER, int $owner = 1): CustomerImport
    {
        return CustomerImport::create(['type' => $type, 'file_name' => $type.'.xlsx', 'created_by_user_id' => $owner]);
    }

    private function denied(callable $action, int $status): void
    {
        try {
            $action();
            $this->fail('Import access should have been rejected.');
        } catch (HttpException $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }

    public function test_type_migration_preserves_legacy_logs_rows_and_application_metadata(): void
    {
        $migration = require database_path('migrations/2026_10_28_000000_add_type_to_customer_imports.php');
        $migration->down();
        $id = DB::table('customer_imports')->insertGetId([
            'file_name' => 'legacy.xlsx', 'status' => 'applied', 'lock_version' => 4,
            'created_count' => 2, 'matched_count' => 3, 'error_count' => 1,
            'created_by_user_id' => 1, 'applied_at' => '2026-10-27 10:00:00',
        ]);
        DB::table('customer_import_rows')->insert(['customer_import_id' => $id, 'row_number' => 2, 'input' => '{"Customer Name*":"Legacy"}', 'review' => '{"status":"new"}', 'result' => '{"status":"applied"}']);
        $migration->up();
        $batch = CustomerImport::findOrFail($id);
        $this->assertSame('customer', $batch->type);
        $this->assertSame('applied', $batch->status);
        $this->assertSame(4, $batch->lock_version);
        $this->assertSame(2, $batch->created_count);
        $this->assertSame(3, $batch->matched_count);
        $this->assertSame(1, $batch->error_count);
        $this->assertSame('2026-10-27 10:00:00', $batch->applied_at->format('Y-m-d H:i:s'));
        $this->assertSame(['Customer Name*' => 'Legacy'], $batch->rows->first()->input);
        $this->assertSame(['status' => 'applied'], $batch->rows->first()->result);
    }

    public function test_shared_batches_support_type_scopes_uploader_and_row_relations(): void
    {
        $customer = CustomerImport::create(['file_name' => 'default.xlsx', 'created_by_user_id' => 1]);
        $sparepart = $this->batch('sparepart');
        $row = $sparepart->rows()->create(['row_number' => 2, 'input' => ['Part Number*' => 'PN-1'], 'review' => ['status' => 'new']]);
        $this->assertSame('customer', $customer->fresh()->type);
        $this->assertSame([$customer->id], CustomerImport::ofType('customer')->pluck('id')->all());
        $this->assertSame([$sparepart->id], CustomerImport::ofType('sparepart')->pluck('id')->all());
        $this->assertSame($sparepart->id, $row->batch->id);
        $this->assertSame('sparepart', $row->batch->type);
        $this->assertSame('Uploader', $sparepart->createdBy->name);
        $this->assertSame(1, CustomerImportRow::count());
    }

    public function test_invalid_or_changed_import_types_are_rejected_without_persisting_changes(): void
    {
        foreach ([null, '', 'other', 'Customer'] as $type) {
            try {
                CustomerImport::create(['type' => $type, 'file_name' => 'invalid.xlsx', 'created_by_user_id' => 1]);
                $this->fail('Unknown import types must not be saved.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('type', $exception->errors());
            }
        }
        $batch = $this->batch();
        try {
            $batch->update(['type' => 'sparepart', 'status' => 'applied']);
            $this->fail('A batch must not change type.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('type', $exception->errors());
        }
        $this->assertSame('customer', $batch->fresh()->type);
        $this->assertSame('review', $batch->fresh()->status);
        $this->assertSame(1, CustomerImport::count());
    }

    public function test_shared_access_requires_the_type_permission_and_batch_uploader(): void
    {
        $customer = $this->batch();
        $sparepart = $this->batch('sparepart');
        ImportAccess::authorize($this->user(), 'customer', $customer);
        ImportAccess::authorize($this->user(['masters.spareparts']), 'sparepart', $sparepart);
        $this->denied(fn () => ImportAccess::authorize($this->user(), 'sparepart', $sparepart), 403);
        $this->denied(fn () => ImportAccess::authorize($this->user(['masters.spareparts']), 'customer', $customer), 403);
        $this->denied(fn () => ImportAccess::authorize($this->user(['masters.service-customers', 'masters.spareparts']), 'customer', $sparepart), 404);
        $this->denied(fn () => ImportAccess::authorize($this->user(['masters.service-customers', 'masters.spareparts']), 'sparepart', $customer), 404);
        $this->denied(fn () => ImportAccess::authorize($this->user(['masters.spareparts'], 2), 'sparepart', $sparepart), 403);
        $this->denied(fn () => ImportAccess::authorize(null, 'customer', $customer), 403);
        $this->denied(fn () => ImportAccess::authorize($this->user(), 'unknown'), 403);
    }

    public function test_customer_endpoints_reject_sparepart_batches_before_parsing_their_rows(): void
    {
        $batch = $this->batch('sparepart');
        $row = $batch->rows()->create(['row_number' => 2, 'input' => ['Part Number*' => 'PN-1'], 'review' => ['status' => 'new']]);
        $this->actingAs($this->user(['masters.service-customers', 'masters.spareparts']));
        $url = '/api/aftersales/customers/imports/'.$batch->id;
        $this->getJson($url)->assertNotFound();
        $this->putJson($url.'/review', ['lock_version' => 0, 'rows' => [['id' => $row->id, 'input' => ['Customer Name*' => 'Injected']]]])->assertNotFound();
        $this->postJson($url.'/apply', ['lock_version' => 0])->assertNotFound();
        $this->getJson($url.'/export')->assertNotFound();
        $this->assertSame(['Part Number*' => 'PN-1'], $row->fresh()->input);
        $this->assertSame('review', $batch->fresh()->status);
        $this->assertSame(0, $batch->fresh()->lock_version);
    }

    public function test_direct_customer_service_calls_reject_sparepart_batches_even_when_already_applied(): void
    {
        $batch = $this->batch('sparepart');
        $service = app(CustomerImports::class);
        $this->denied(fn () => $service->review([], $batch), 404);
        $this->denied(fn () => $service->payload($batch), 404);
        $this->denied(fn () => $service->apply($batch, 0, 1), 404);
        $batch->update(['status' => 'applied']);
        $this->denied(fn () => $service->apply($batch, 0, 1), 404);
        $this->assertSame(0, CustomerImportRow::count());
    }

    public function test_customer_batch_access_remains_restricted_to_uploader_in_http_and_service(): void
    {
        $batch = $this->batch('customer', 2);
        $this->actingAs($this->user());
        $url = '/api/aftersales/customers/imports/'.$batch->id;
        $this->getJson($url)->assertForbidden();
        $this->putJson($url.'/review', ['lock_version' => 0, 'rows' => []])->assertForbidden();
        $this->postJson($url.'/apply', ['lock_version' => 0])->assertForbidden();
        $this->getJson($url.'/export')->assertForbidden();
        $this->denied(fn () => app(CustomerImports::class)->apply($batch, 0, 1), 403);
        $batch->update(['status' => 'applied']);
        $this->denied(fn () => app(CustomerImports::class)->apply($batch, 0, 1), 403);
        $this->assertSame(0, $batch->fresh()->lock_version);
    }
}
