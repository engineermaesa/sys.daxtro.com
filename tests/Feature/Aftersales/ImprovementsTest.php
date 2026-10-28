<?php

namespace Tests\Feature\Aftersales;

use App\Models\Aftersales\CustomerDeal;
use App\Models\Aftersales\CustomerImport;
use App\Models\Aftersales\ServiceCustomer;
use App\Models\Aftersales\ServiceCustomerProduct;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketSatisfaction;
use App\Models\Leads\Lead;
use App\Models\User;
use App\Services\Aftersales\CustomerImports;
use App\Services\Aftersales\CustomerMatching;
use App\Services\Aftersales\DealConversion;
use App\Services\Aftersales\MachineTransfer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImprovementsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locked' => false]);
        foreach (['users', 'user_roles', 'user_permissions', 'ref_provinces', 'ref_regions', 'ref_industries', 'ref_product_types', 'ref_products', 'leads', 'quotations', 'quotation_items'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->string('name')->nullable();
                $t->timestamps();
                $t->softDeletes();
                if (in_array($table, ['users', 'user_roles', 'user_permissions'])) {
                    $t->string('code')->nullable();
                }
                if ($table === 'users') {
                    $t->unsignedBigInteger('role_id')->nullable();
                }
                if ($table === 'ref_regions') {
                    $t->unsignedBigInteger('province_id');
                }
                if ($table === 'ref_products') {
                    $t->unsignedBigInteger('product_type_id')->nullable();
                    $t->boolean('warranty_available')->default(true);
                    $t->integer('warranty_time_month')->default(12);
                }
                if ($table === 'leads') {
                    $t->integer('status_id');
                    $t->timestamp('deal_at')->nullable();
                    foreach (['phone', 'email', 'company_address', 'factory_province', 'tonase'] as $key) {
                        $t->string($key)->nullable();
                    }
                    $t->unsignedBigInteger('factory_city_id')->nullable();
                    $t->unsignedBigInteger('industry_id')->nullable();
                }
                if ($table === 'quotations') {
                    $t->unsignedBigInteger('lead_id');
                    $t->string('quotation_no')->nullable();
                }
                if ($table === 'quotation_items') {
                    $t->unsignedBigInteger('quotation_id');
                    $t->unsignedBigInteger('product_id');
                    $t->unsignedBigInteger('merge_into_item_id')->nullable();
                    $t->integer('qty');
                }
            });
        }
        Schema::create('user_role_permissions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('permission_id');
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'After Sales']);
        DB::table('user_roles')->insert(['id' => 1, 'code' => 'after_sales', 'name' => 'After Sales']);
        DB::table('user_permissions')->insert(['id' => 1, 'code' => 'masters.service-customers']);
        DB::table('user_role_permissions')->insert(['role_id' => 1, 'permission_id' => 1]);
        DB::table('ref_provinces')->insert(['id' => 1, 'name' => 'Jawa Timur']);
        DB::table('ref_regions')->insert(['id' => 1, 'name' => 'Ponorogo', 'province_id' => 1]);
        foreach (['2026_09_03_010100_create_service_customers_table.php', '2026_09_08_000000_alter_service_customers_province_region.php', '2026_09_03_010200_create_service_customer_products_table.php', '2026_09_08_010000_alter_service_customer_products_product_ref.php', '2026_10_22_000100_add_source_to_service_customers.php', '2026_10_22_000200_add_maps_link_to_service_customers.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::table('service_customer_products', function (Blueprint $t) {
            $t->string('capacity')->nullable();
            $t->string('refrigerant')->nullable();
        });
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->string('ticket_code')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('customer_product_id')->nullable();
            $t->unsignedBigInteger('assigned_technician_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->string('step');
            $t->unsignedBigInteger('actor_user_id')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_visits', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->timestamp('scheduled_at')->nullable();
            $t->softDeletes();
        });
        Schema::create('ticket_satisfactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            foreach (array_keys(TicketSatisfaction::ASPECTS) as $key) {
                $t->integer($key)->nullable();
            } $t->string('review_status')->default('normal');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('ticket_satisfaction_answers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_satisfaction_id');
            $t->string('type');
            $t->integer('value_number')->nullable();
        });
        Schema::create('ref_survey_templates', function (Blueprint $t) {
            $t->id();
            $t->json('content');
        });
        DB::table('tickets')->insert(['id' => 1, 'ticket_code' => 'legacy']);
        DB::table('ticket_satisfactions')->insert(['id' => 1, 'ticket_id' => 1, 'timeliness' => 2, 'grooming' => 5]);
        DB::table('ticket_satisfaction_answers')->insert(['ticket_satisfaction_id' => 1, 'type' => 'rating', 'value_number' => 4]);
        (require database_path('migrations/2026_10_22_100000_aftersales_improvements.php'))->up();
        (require database_path('migrations/2026_10_23_000000_optimize_aftersales_matching.php'))->up();
        (require database_path('migrations/2026_10_27_000000_add_daxtro_machine_fields.php'))->up();
        (require database_path('migrations/2026_10_27_000100_add_machine_name_mode.php'))->up();
        (require database_path('migrations/2026_10_27_000200_add_customer_deal_review_status.php'))->up();
        (require database_path('migrations/2026_10_28_000000_add_type_to_customer_imports.php'))->up();
    }

    public function test_daxtro_migration_preserves_existing_machine_data_and_backfills_both_active_and_deleted_units(): void
    {
        $migration = require database_path('migrations/2026_10_27_000000_add_daxtro_machine_fields.php');
        $migration->down();
        $customer = ServiceCustomer::create(['name' => 'Legacy owner']);
        foreach ([null, now()] as $deletedAt) {
            DB::table('service_customer_products')->insert([
                'customer_id' => $customer->id, 'machine_name' => 'Existing TD 50',
                'capacity' => '50', 'brand' => 'Legacy brand', 'serial_number' => 'LEGACY',
                'warranty_start' => '2026-01-01', 'warranty_end' => '2027-01-01', 'deleted_at' => $deletedAt,
            ]);
        }
        $migration->up();
        foreach (ServiceCustomerProduct::withTrashed()->get() as $machine) {
            $this->assertTrue($machine->is_daxtro);
            $this->assertNull($machine->compressor);
            $this->assertSame('Existing TD 50', $machine->machine_name);
            $this->assertSame('50', $machine->capacity);
            $this->assertSame('Legacy brand', $machine->brand);
            $this->assertSame('LEGACY', $machine->serial_number);
            $this->assertSame('2027-01-01', $machine->warranty_end->toDateString());
        }
    }

    public function test_manual_daxtro_create_defaults_type_and_returns_compressor_and_refrigerant(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Machine owner']);
        $this->actingAs($this->user());
        $response = $this->postJson('/api/aftersales/customers/'.$customer->id.'/products', [
            'machine_name' => 'Existing custom name', 'serial_number' => ' sn - 22 ',
            'brand' => 'Ignored', 'compressor' => 'Bitzer', 'refrigerant' => 'R404A', 'capacity' => 7.5,
            'warranty_start' => '2026-10-27', 'pm_contract_status' => 'basic',
        ])->assertCreated()->assertJsonPath('data.data.is_daxtro', true)
            ->assertJsonPath('data.data.brand', null)->assertJsonPath('data.data.compressor', 'Bitzer')
            ->assertJsonPath('data.data.refrigerant', 'R404A')->assertJsonPath('data.data.serial_number', 'SN-22');
        $machine = $customer->products()->firstOrFail();
        $this->assertSame('Existing custom name', $machine->machine_name);
        $this->assertSame('2027-10-27', $machine->warranty_end->toDateString());
        $this->assertSame('6 months', $machine->pm_frequency);
        $this->getJson('/api/aftersales/customers/'.$customer->id)->assertOk()
            ->assertJsonPath('data.products.0.is_daxtro', true)->assertJsonPath('data.products.0.compressor', 'Bitzer');
    }

    public function test_non_daxtro_create_ignores_hidden_fields_and_requires_no_category_capacity_or_serial(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Machine owner']);
        $this->actingAs($this->user());
        $this->postJson('/api/aftersales/customers/'.$customer->id.'/products', [
            'is_daxtro' => false, 'machine_name' => 'Other chiller', 'brand' => 'Other Brand',
            'serial_number' => ['invalid hidden value'], 'warranty_start' => 'invalid', 'warranty_end' => 'invalid',
            'warranty_period' => '99 Years', 'pm_contract_status' => 'invalid', 'pm_frequency' => 'monthly',
        ])->assertCreated()->assertJsonPath('data.data.is_daxtro', false)
            ->assertJsonPath('data.data.brand', 'Other Brand')->assertJsonPath('data.data.serial_number', null)
            ->assertJsonPath('data.data.warranty_active', null)->assertJsonPath('data.data.warranty_end', null)
            ->assertJsonPath('data.data.pm_contract_status', 'none')->assertJsonPath('data.data.pm_frequency', null);
        $machine = $customer->products()->firstOrFail();
        $this->assertNull($machine->warranty_period);
        $this->assertNull($machine->warranty_start);
        $this->assertNull($machine->getRawOriginal('normalized_serial'));
        $this->getJson('/api/aftersales/customers/'.$customer->id)->assertOk()
            ->assertJsonPath('data.products.0.is_daxtro', false)->assertJsonPath('data.products.0.warranty_active', null);
    }

    public function test_machine_type_and_conditional_required_fields_are_validated(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Machine owner']);
        $this->actingAs($this->user());
        $url = '/api/aftersales/customers/'.$customer->id.'/products';
        $this->postJson($url, ['is_daxtro' => false])->assertUnprocessable()->assertJsonValidationErrors(['brand', 'machine_name']);
        foreach ([[], ['is_daxtro' => true], ['is_daxtro' => '1']] as $input) {
            $this->postJson($url, $input + ['machine_name' => 'Daxtro'])->assertUnprocessable()->assertJsonValidationErrors('serial_number');
        }
        foreach (['yes', null, 2] as $invalid) {
            $this->postJson($url, ['is_daxtro' => $invalid, 'machine_name' => 'Machine', 'brand' => 'Brand', 'serial_number' => 'SN'])
                ->assertUnprocessable()->assertJsonValidationErrors('is_daxtro');
        }
        $this->postJson($url, ['is_daxtro' => false, 'machine_name' => 'Other', 'brand' => 'Brand', 'capacity' => -1, 'compressor' => str_repeat('x', 256)])
            ->assertUnprocessable()->assertJsonValidationErrors(['capacity', 'compressor']);
        $this->assertSame(0, ServiceCustomerProduct::count());
    }

    public function test_switching_machine_type_clears_inapplicable_data_and_requires_serial_when_switching_back(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Machine owner']);
        $machine = $customer->products()->create([
            'machine_name' => 'Keep name', 'serial_number' => 'SWITCH-1', 'capacity' => '50',
            'warranty_start' => '2026-01-01', 'pm_contract_status' => 'premium',
        ]);
        $this->actingAs($this->user());
        $url = '/api/aftersales/customers/products/'.$machine->id;
        $this->putJson($url, ['is_daxtro' => false, 'machine_name' => 'Keep name', 'brand' => 'Other'])
            ->assertOk()->assertJsonPath('data.data.serial_number', null)->assertJsonPath('data.data.warranty_period', null)
            ->assertJsonPath('data.data.warranty_start', null)->assertJsonPath('data.data.warranty_end', null)
            ->assertJsonPath('data.data.pm_contract_status', 'none')->assertJsonPath('data.data.pm_frequency', null);
        $this->assertSame('50', $machine->fresh()->capacity);
        // Omitted type preserves a non-Daxtro unit, rather than reverting it to the create default.
        $this->putJson($url, ['machine_name' => 'Keep name', 'brand' => 'Other', 'compressor' => 'Copeland'])
            ->assertOk()->assertJsonPath('data.data.is_daxtro', false)->assertJsonPath('data.data.compressor', 'Copeland');
        $this->putJson($url, ['is_daxtro' => true, 'machine_name' => 'Keep name'])
            ->assertUnprocessable()->assertJsonValidationErrors('serial_number');
        $this->assertFalse($machine->fresh()->is_daxtro);
        $this->putJson($url, ['is_daxtro' => true, 'machine_name' => 'Keep name', 'serial_number' => 'SWITCH-1', 'brand' => 'Ignored'])
            ->assertOk()->assertJsonPath('data.data.is_daxtro', true)->assertJsonPath('data.data.brand', null);
    }

    public function test_daxtro_serials_remain_unique_including_soft_deleted_units(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Machine owner']);
        $machine = $customer->products()->create(['machine_name' => 'First', 'serial_number' => 'UNIQUE-1']);
        $this->actingAs($this->user());
        $url = '/api/aftersales/customers/'.$customer->id.'/products';
        $input = ['machine_name' => 'Duplicate', 'serial_number' => ' unique - 1 '];
        $this->postJson($url, $input)->assertUnprocessable()->assertJsonValidationErrors('serial_number');
        $machine->delete();
        $this->postJson($url, $input)->assertUnprocessable()->assertJsonValidationErrors('serial_number');
    }

    public function test_serial_matching_excludes_non_daxtro_rows_even_if_legacy_serial_data_remains(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Machine owner']);
        DB::table('service_customer_products')->insert([
            'customer_id' => $customer->id, 'is_daxtro' => false, 'machine_name' => 'Other', 'brand' => 'Brand',
            'serial_number' => 'STALE', 'normalized_serial' => 'STALE',
        ]);
        $this->assertFalse(\App\Services\Aftersales\MachineSerials::exists('STALE'));
        $this->assertSame([], \App\Services\Aftersales\MachineSerials::existing(['STALE']));
        $this->assertSame(['existing' => [], 'archived' => []], \App\Services\Aftersales\MachineSerials::importSnapshot(['STALE']));
        $this->actingAs($this->user());
        $this->postJson('/api/aftersales/customers/'.$customer->id.'/products', ['machine_name' => 'Daxtro', 'serial_number' => 'STALE'])->assertCreated();
    }

    public function test_non_daxtro_rules_apply_to_direct_model_saves_and_daxtro_deal_units_may_have_no_serial(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Machine owner']);
        $machine = $customer->products()->create([
            'is_daxtro' => false, 'machine_name' => 'Other', 'brand' => 'Brand', 'serial_number' => 'NO-SERIAL',
            'warranty_start' => '2026-01-01', 'warranty_end' => '2025-01-01', 'pm_contract_status' => 'premium',
        ]);
        $this->assertNull($machine->serial_number);
        $this->assertNull($machine->warranty_active);
        $this->assertNull($machine->warranty_end);
        $this->assertNull($machine->pm_frequency);
        $dealMachine = $customer->products()->create(['machine_name' => 'From Deal']);
        $this->assertTrue($dealMachine->fresh()->is_daxtro);
        $this->assertNull($dealMachine->serial_number);
    }

    public function test_machine_mutations_still_require_customer_permission(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Machine owner']);
        $machine = $customer->products()->create(['machine_name' => 'Existing']);
        $this->actingAs($this->user(false));
        $input = ['is_daxtro' => false, 'machine_name' => 'Other', 'brand' => 'Brand'];
        $this->postJson('/api/aftersales/customers/'.$customer->id.'/products', $input)->assertForbidden();
        $this->putJson('/api/aftersales/customers/products/'.$machine->id, $input)->assertForbidden();
        $this->assertTrue($machine->fresh()->is_daxtro);
        $this->assertSame(1, ServiceCustomerProduct::count());
    }

    public function test_auto_machine_creation_generates_name_and_keeps_machine_code_formula(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Generator owner']);
        $type = DB::table('ref_product_types')->where('code_prefix', 'TD')->value('id');
        DB::table('ref_products')->insert(['id' => 1, 'name' => 'Tube product', 'product_type_id' => $type, 'warranty_available' => true, 'warranty_time_month' => 24]);
        $this->actingAs($this->user());
        $this->postJson('/api/aftersales/customers/'.$customer->id.'/products', [
            'serial_number' => 'AUTO-1', 'ref_product_type_id' => $type, 'capacity' => 15,
            'compressor' => 'Bitzer', 'refrigerant' => 'R404A',
            'product_id' => 1, 'warranty_start' => '2026-10-27',
        ])->assertCreated()->assertJsonPath('data.data.machine_name', 'TD150 | Bitzer R404A')
            ->assertJsonPath('data.data.machine_name_mode', 'auto')->assertJsonPath('data.data.machine_code', 'TD 15');
        $machine = $customer->products()->firstOrFail();
        $this->assertSame('2028-10-27', $machine->warranty_end->toDateString());
        $this->getJson('/api/aftersales/customers/'.$customer->id)->assertOk()
            ->assertJsonPath('data.products.0.machine_name_mode', 'auto');
        // Older FE clients resend the unchanged name; auto mode must still regenerate on field changes.
        $input = ['machine_name' => $machine->machine_name, 'serial_number' => 'AUTO-1', 'capacity' => 7];
        $url = '/api/aftersales/customers/products/'.$machine->id;
        $this->putJson($url, $input)->assertOk()->assertJsonPath('data.data.machine_name', 'TD70 | Bitzer R404A');
        $this->putJson($url, ['serial_number' => 'AUTO-1', 'compressor' => null, 'refrigerant' => null])
            ->assertOk()->assertJsonPath('data.data.machine_name', 'TD70');
        $coldRoom = DB::table('ref_product_types')->where('code_prefix', 'RD')->value('id');
        $this->putJson($url, ['serial_number' => 'AUTO-1', 'ref_product_type_id' => $coldRoom, 'capacity' => 6])
            ->assertOk()->assertJsonPath('data.data.machine_name', 'RD20')->assertJsonPath('data.data.machine_code', 'RD 6');
    }

    public function test_manually_edited_names_survive_driver_changes_until_auto_is_explicitly_requested(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Manual owner']);
        $type = DB::table('ref_product_types')->where('code_prefix', 'TD')->value('id');
        $this->actingAs($this->user());
        $this->postJson('/api/aftersales/customers/'.$customer->id.'/products', [
            'serial_number' => 'MANUAL-1', 'ref_product_type_id' => $type, 'capacity' => 15,
        ])->assertCreated();
        $machine = $customer->products()->firstOrFail();
        $url = '/api/aftersales/customers/products/'.$machine->id;
        $this->putJson($url, ['serial_number' => 'MANUAL-1', 'machine_name' => 'Customer custom name', 'capacity' => 7])
            ->assertOk()->assertJsonPath('data.data.machine_name_mode', 'manual')->assertJsonPath('data.data.machine_name', 'Customer custom name');
        $this->putJson($url, ['serial_number' => 'MANUAL-1', 'capacity' => 15, 'compressor' => 'Bitzer', 'refrigerant' => 'R404A'])
            ->assertOk()->assertJsonPath('data.data.machine_name', 'Customer custom name');
        $this->putJson($url, ['serial_number' => 'MANUAL-1', 'machine_name_mode' => 'auto', 'machine_name' => 'Stale browser name'])
            ->assertOk()->assertJsonPath('data.data.machine_name_mode', 'auto')->assertJsonPath('data.data.machine_name', 'TD150 | Bitzer R404A');
    }

    public function test_existing_names_and_capacities_are_preserved_until_a_driver_changes(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Legacy owner']);
        $type = DB::table('ref_product_types')->where('code_prefix', 'TD')->value('id');
        $machine = $customer->products()->create([
            'machine_name' => 'Existing TD 50', 'serial_number' => 'LEGACY-NAME', 'capacity' => '50', 'ref_product_type_id' => $type,
        ]);
        $migration = require database_path('migrations/2026_10_27_000100_add_machine_name_mode.php');
        $machine->delete();
        $migration->down();
        $migration->up();
        $this->assertSame('Existing TD 50', $machine->fresh()->machine_name);
        $this->assertSame('auto', $machine->fresh()->machine_name_mode);
        $this->assertSame('50', $machine->fresh()->capacity);
        $machine->restore();
        $this->actingAs($this->user());
        $url = '/api/aftersales/customers/products/'.$machine->id;
        $this->putJson($url, ['serial_number' => 'LEGACY-NAME', 'note' => 'Only change note'])
            ->assertOk()->assertJsonPath('data.data.machine_name', 'Existing TD 50');
        $this->putJson($url, ['serial_number' => 'LEGACY-NAME', 'capacity' => 5])
            ->assertOk()->assertJsonPath('data.data.machine_name', 'TD50');
    }

    public function test_manual_create_is_backward_compatible_and_non_daxtro_never_generates(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Manual owner']);
        $type = DB::table('ref_product_types')->where('code_prefix', 'TD')->value('id');
        $this->actingAs($this->user());
        $url = '/api/aftersales/customers/'.$customer->id.'/products';
        $this->postJson($url, ['machine_name' => 'Free name', 'serial_number' => 'FREE-1'])
            ->assertCreated()->assertJsonPath('data.data.machine_name_mode', 'manual')->assertJsonPath('data.data.machine_name', 'Free name');
        $this->postJson($url, [
            'is_daxtro' => false, 'brand' => 'Other', 'machine_name' => 'Other chiller', 'machine_name_mode' => 'auto',
            'ref_product_type_id' => $type, 'capacity' => 15, 'compressor' => 'Bitzer',
        ])->assertCreated()->assertJsonPath('data.data.machine_name', 'Other chiller')->assertJsonPath('data.data.machine_name_mode', 'manual');
        $this->postJson($url, ['is_daxtro' => false, 'brand' => 'Other', 'ref_product_type_id' => $type, 'capacity' => 15])
            ->assertUnprocessable()->assertJsonValidationErrors('machine_name');
    }

    public function test_auto_name_requires_generator_inputs_and_failed_update_preserves_saved_state(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Validation owner']);
        $type = DB::table('ref_product_types')->where('code_prefix', 'TD')->value('id');
        $noPrefix = DB::table('ref_product_types')->insertGetId(['name' => 'No prefix']);
        $this->actingAs($this->user());
        $url = '/api/aftersales/customers/'.$customer->id.'/products';
        $this->postJson($url, ['serial_number' => 'INVALID-1', 'capacity' => 15])->assertUnprocessable()->assertJsonValidationErrors('ref_product_type_id');
        $this->postJson($url, ['serial_number' => 'INVALID-1', 'ref_product_type_id' => $type])->assertUnprocessable()->assertJsonValidationErrors('capacity');
        $this->postJson($url, ['serial_number' => 'INVALID-1', 'ref_product_type_id' => $noPrefix, 'capacity' => 15])->assertUnprocessable()->assertJsonValidationErrors('ref_product_type_id');
        $this->postJson($url, ['serial_number' => 'INVALID-1', 'machine_name_mode' => 'manual'])->assertUnprocessable()->assertJsonValidationErrors('machine_name');
        $this->postJson($url, ['serial_number' => 'INVALID-1', 'machine_name_mode' => 'unknown'])->assertUnprocessable()->assertJsonValidationErrors('machine_name_mode');
        $this->assertSame(0, ServiceCustomerProduct::count());
        $this->postJson($url, ['serial_number' => 'VALID-1', 'ref_product_type_id' => $type, 'capacity' => 15])->assertCreated();
        $machine = $customer->products()->firstOrFail();
        $this->putJson('/api/aftersales/customers/products/'.$machine->id, ['serial_number' => 'VALID-1', 'capacity' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('capacity');
        $this->assertSame('15', $machine->fresh()->capacity);
        $this->assertSame('TD150', $machine->fresh()->machine_name);
        $this->assertSame('auto', $machine->fresh()->machine_name_mode);
    }

    public function test_preview_uses_the_same_generator_without_persisting_data_and_checks_permission(): void
    {
        $type = DB::table('ref_product_types')->where('code_prefix', 'RD')->value('id');
        $input = ['ref_product_type_id' => $type, 'capacity' => 6, 'compressor' => 'Copeland', 'refrigerant' => 'R22'];
        $this->actingAs($this->user());
        $this->postJson('/api/aftersales/customers/machine-name-preview', $input)->assertOk()
            ->assertJsonPath('data.machine_name', 'RD20 | Copeland R22')->assertJsonPath('data.machine_name_mode', 'auto');
        $this->assertSame(0, ServiceCustomerProduct::count());
        $this->postJson('/api/aftersales/customers/machine-name-preview', ['ref_product_type_id' => $type, 'capacity' => -1])
            ->assertUnprocessable()->assertJsonValidationErrors('capacity');
        $this->actingAs($this->user(false));
        $this->postJson('/api/aftersales/customers/machine-name-preview', $input)->assertForbidden();
    }

    public function test_import_v22_generates_daxtro_names_and_persists_manual_or_auto_modes(): void
    {
        $batch = $this->upload([
            ['Daxtro Machine' => '', 'Machine Name' => '', 'Capacity' => '15', 'Compressor' => 'Bitzer', 'Refrigerant Type' => 'R404A', 'Brand' => 'Ignored'],
            ['Daxtro Machine' => 'yEs', 'Machine Category*' => 'Cold Room', 'Machine Name' => '', 'Capacity' => '6'],
            ['Machine Name' => 'Custom Tube unit', 'Capacity' => '7.25'],
        ]);
        $rows = $batch->rows()->orderBy('row_number')->get();
        $this->assertSame(['TD150 | Bitzer R404A', 'RD20', 'Custom Tube unit'], $rows->pluck('review.machine.machine_name')->all());
        $this->assertSame(['auto', 'auto', 'manual'], $rows->pluck('review.machine.machine_name_mode')->all());
        $this->assertNull($rows[0]->review['machine']['brand']);
        $this->assertSame(CustomerImports::TEMPLATE_VERSION, $rows[0]->review['template_version']);
        $this->assertSame(0, ServiceCustomerProduct::count());
        $this->assertSame(0, ServiceCustomer::count());
        app(CustomerImports::class)->apply($batch, 0, 1);
        $machines = ServiceCustomerProduct::orderBy('id')->get();
        $this->assertTrue($machines[0]->is_daxtro);
        $this->assertSame('TD150 | Bitzer R404A', $machines[0]->machine_name);
        $this->assertSame('auto', $machines[0]->machine_name_mode);
        $this->assertSame('Bitzer', $machines[0]->compressor);
        $this->assertSame('R404A', $machines[0]->refrigerant);
        $this->assertSame('RD20', $machines[1]->machine_name);
        $this->assertSame('manual', $machines[2]->machine_name_mode);
        $this->assertSame('7.25', $machines[2]->capacity);
        $this->assertNull($machines[0]->product_id);
    }

    public function test_import_non_daxtro_ignores_hidden_values_and_never_matches_or_merges_serials(): void
    {
        $owner = ServiceCustomer::create(['name' => 'Other owner']);
        $owner->products()->create(['machine_name' => 'Existing Daxtro', 'serial_number' => 'SAME']);
        $input = [
            'Daxtro Machine' => 'No', 'Brand' => 'Brand X', 'Machine Name' => 'Other chiller',
            'Machine Category*' => '', 'Capacity' => '', 'Serial Number' => 'SAME',
            'Warranty Start' => 'invalid date', 'Warranty End' => 'not a date', 'PM Contract' => 'bad value',
            'Compressor' => 'Copeland', 'Refrigerant Type' => 'R22',
        ];
        $batch = $this->upload([$input, $input]);
        foreach ($batch->rows as $row) {
            $this->assertNotSame('error', $row->review['status']);
            $this->assertNotSame('already_exists', $row->review['status']);
            $this->assertSame([], $row->review['errors']);
            $this->assertFalse($row->review['machine']['is_daxtro']);
            $this->assertSame('manual', $row->review['machine']['machine_name_mode']);
            foreach (['serial_number', 'warranty_start', 'warranty_end', 'ref_product_type_id', 'capacity'] as $key) {
                $this->assertNull($row->review['machine'][$key]);
            }
            $warnings = implode(' ', $row->review['warnings']);
            foreach (['Serial Number', 'Warranty Start', 'Warranty End', 'PM Contract'] as $field) {
                $this->assertStringContainsString($field.' is ignored for non-Daxtro machines.', $warnings);
            }
            $this->assertStringNotContainsString('Serial repeated', $warnings);
        }
        app(CustomerImports::class)->apply($batch, 0, 1);
        $this->assertSame(3, ServiceCustomerProduct::count());
        foreach (ServiceCustomerProduct::where('is_daxtro', false)->get() as $machine) {
            $this->assertSame('Brand X', $machine->brand);
            $this->assertSame('Other chiller', $machine->machine_name);
            $this->assertSame('Copeland', $machine->compressor);
            $this->assertSame('R22', $machine->refrigerant);
            $this->assertNull($machine->serial_number);
            $this->assertNull($machine->warranty_active);
            $this->assertSame('none', $machine->pm_contract_status);
        }
    }

    public function test_import_v22_conditional_requirements_and_numeric_validation_become_row_errors(): void
    {
        $batch = $this->upload([
            ['Capacity' => '', 'Machine Name' => 'TD50'],
            ['Machine Category*' => ''],
            ['Daxtro Machine' => 'No', 'Machine Name' => '', 'Brand' => 'Brand X'],
            ['Daxtro Machine' => 'No', 'Brand' => ''],
            ['Daxtro Machine' => 'Maybe'],
            ['Capacity' => 'not a number'],
            ['Capacity' => '-1'],
            ['Capacity' => '1e309'],
            ['Compressor' => str_repeat('X', 256)],
            ['Refrigerant Type' => str_repeat('X', 256)],
            ['Machine Name' => '', 'Compressor' => str_repeat('X', 250), 'Refrigerant Type' => 'R404A'],
        ]);
        $this->assertSame(11, $batch->rows()->where('review->status', 'error')->count());
        $rows = $batch->rows()->orderBy('row_number')->get();
        $this->assertContains('Capacity is required.', $rows[0]->review['errors']);
        $this->assertNull($rows[0]->review['machine']['capacity']);
        $this->assertContains('Machine Category* is required.', $rows[1]->review['errors']);
        $this->assertContains('Machine Name is required.', $rows[2]->review['errors']);
        $this->assertContains('Brand is required.', $rows[3]->review['errors']);
        app(CustomerImports::class)->apply($batch, 0, 1);
        $this->assertSame(0, ServiceCustomer::count());
        $this->assertSame(0, ServiceCustomerProduct::count());
        $this->assertSame(11, $batch->fresh()->error_count);
    }

    public function test_missing_contact_is_warning_and_daxtro_serial_can_be_empty_during_import(): void
    {
        $batch = $this->upload([['Phone' => '', 'Email' => '', 'Serial Number' => '', 'Machine Name' => '', 'Capacity' => '7']]);
        $review = $batch->rows->first()->review;
        $this->assertSame('new', $review['status']);
        $this->assertSame([], $review['errors']);
        $this->assertStringContainsString('Phone is empty.', implode(' ', $review['warnings']));
        $this->assertStringContainsString('Email is empty.', implode(' ', $review['warnings']));
        $this->assertStringContainsString('before creating a new ticket', implode(' ', $review['warnings']));
        $this->assertSame(1, app(CustomerImports::class)->payload($batch)['summary']['warning']);
        app(CustomerImports::class)->apply($batch, 0, 1);
        $this->assertSame('TD70', ServiceCustomerProduct::firstOrFail()->machine_name);
        $this->assertNull(ServiceCustomerProduct::firstOrFail()->serial_number);
        $this->assertNull(ServiceCustomer::firstOrFail()->phone);
        $this->assertNull(ServiceCustomer::firstOrFail()->email);
    }

    public function test_v22_review_can_change_machine_type_and_regenerates_preview_from_corrected_input(): void
    {
        $this->actingAs($this->user());
        $batch = $this->upload([['Machine Name' => '', 'Capacity' => '15']]);
        $row = $batch->rows()->first();
        $url = '/api/aftersales/customers/imports/'.$batch->id;
        $this->putJson($url.'/review', ['lock_version' => 0, 'rows' => [['id' => $row->id, 'input' => ['Capacity' => '7', 'Compressor' => 'Bitzer', 'Refrigerant Type' => 'R404A']]]])
            ->assertOk()->assertJsonPath('data.rows.0.review.machine.machine_name', 'TD70 | Bitzer R404A');
        $this->putJson($url.'/review', ['lock_version' => 1, 'rows' => [['id' => $row->id, 'input' => ['Daxtro Machine' => 'No', 'Brand' => 'Brand X', 'Machine Name' => 'Manual chiller']]]])
            ->assertOk()->assertJsonPath('data.rows.0.review.machine.is_daxtro', false)
            ->assertJsonPath('data.rows.0.review.machine.serial_number', null)->assertJsonPath('data.rows.0.review.machine.machine_name_mode', 'manual');
        $this->assertSame(0, ServiceCustomerProduct::count());
        $this->postJson($url.'/apply', ['lock_version' => 2])->assertOk();
        $machine = ServiceCustomerProduct::firstOrFail();
        $this->assertFalse($machine->is_daxtro);
        $this->assertSame('Manual chiller', $machine->machine_name);
    }

    public function test_downloaded_v22_template_matches_parser_and_documents_legacy_capacity_conversion(): void
    {
        $this->actingAs($this->user());
        $response = $this->get('/api/aftersales/customers/imports/template')->assertOk();
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($response->baseResponse->getFile()->getPathname());
        $this->assertSame(['Import Customer', 'Guide', 'Reference'], $book->getSheetNames());
        $sheet = $book->getSheetByName('Import Customer');
        $this->assertSame(CustomerImports::HEADERS, $sheet->rangeToArray('A1:U1', null, false, false)[0]);
        $this->assertSame('@', $sheet->getStyle('C2')->getNumberFormat()->getFormatCode());
        $this->assertSame('@', $sheet->getStyle('Q2')->getNumberFormat()->getFormatCode());
        $this->assertSame('"Yes,No"', $sheet->getDataValidation('J2')->getFormula1());
        $guide = implode(' ', array_column($book->getSheetByName('Guide')->toArray(), 0));
        $this->assertStringContainsString('dibagi 10', $guide);
        $this->assertStringContainsString('dikali 3', $guide);
        $this->assertStringContainsString('TD50', $guide);
        $this->assertStringContainsString('VCD20', $guide);
        $this->assertStringNotContainsString('Machine Code dibuat otomatis', $guide);
        $book->disconnectWorksheets();
        $path = $response->baseResponse->getFile()->getPathname();
        $batch = app(CustomerImports::class)->upload(new UploadedFile($path, 'template.xlsx', null, null, true), 1);
        $this->assertSame(2, $batch->rows()->count());
        $this->assertSame(0, app(CustomerImports::class)->payload($batch)['summary']['error']);
    }

    public function test_legacy_headers_and_unapplied_legacy_reviews_cannot_bypass_v22_rules(): void
    {
        $file = $this->file([[]]);
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
        $sheet = $book->getActiveSheet();
        $sheet->removeColumn('J');
        $sheet->removeColumn('N', 2);
        $sheet->setCellValue('K1', 'Machine Name*');
        (new Xlsx($book))->save($file->getRealPath());
        try {
            app(CustomerImports::class)->upload($file, 1);
            $this->fail('Old templates must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }
        $batch = $this->upload([[]]);
        $row = $batch->rows()->first();
        $review = $row->review;
        unset($review['template_version']);
        $row->update(['review' => $review]);
        $this->actingAs($this->user());
        $this->postJson('/api/aftersales/customers/imports/'.$batch->id.'/apply', ['lock_version' => 0])->assertConflict();
        $this->assertSame(0, ServiceCustomer::count());
        $this->assertSame(0, ServiceCustomerProduct::count());
        $this->assertSame('review', $batch->fresh()->status);
    }

    public function test_v22_export_contains_new_input_columns_and_errors_without_creating_machines(): void
    {
        $batch = $this->upload([['Daxtro Machine' => 'No', 'Brand' => '']]);
        $this->actingAs($this->user());
        $response = $this->get('/api/aftersales/customers/imports/'.$batch->id.'/export')->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'v22-review');
        $this->beforeApplicationDestroyed(fn () => @unlink($path));
        file_put_contents($path, $response->streamedContent());
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet = $book->getActiveSheet();
        $this->assertSame('Daxtro Machine', $sheet->getCell('J1')->getValue());
        $this->assertSame('Compressor', $sheet->getCell('O1')->getValue());
        $this->assertSame('Refrigerant Type', $sheet->getCell('P1')->getValue());
        $this->assertStringContainsString('Brand is required.', $sheet->getCell('X2')->getValue());
        $this->assertSame(0, ServiceCustomerProduct::count());
        $book->disconnectWorksheets();
    }

    private function pendingDeal(): array
    {
        $customer = ServiceCustomer::firstOrCreate(['name' => 'Pak Rusman', 'phone' => '0811440148'], ['ref_region_id' => 1]);
        $lead = Lead::withoutEvents(fn () => Lead::create(['name' => 'Mr Rusman', 'phone' => '0811440149', 'status_id' => 5, 'deal_at' => now(), 'factory_city_id' => 1]));
        $deal = app(DealConversion::class)->sync($lead);

        return [$deal, $lead, $customer];
    }

    public function test_deal_review_migration_backfills_pending_and_preserves_linked_data(): void
    {
        [$pending, $lead, $customer] = $this->pendingDeal();
        $other = Lead::withoutEvents(fn () => Lead::create(['name' => 'Different', 'status_id' => 5, 'deal_at' => now()]));
        $resolved = DB::transaction(fn () => app(DealConversion::class)->link($other, $customer));
        $resolved->update(['pending_changes' => ['name' => 'Changed']]);
        $migration = require database_path('migrations/2026_10_27_000200_add_customer_deal_review_status.php');
        $migration->down();
        $migration->up();
        $this->assertSame('pending_review', $pending->fresh()->review_status);
        $this->assertNull($pending->fresh()->service_customer_id);
        $this->assertSame('resolved', $resolved->fresh()->review_status);
        $this->assertSame($customer->id, $resolved->fresh()->service_customer_id);
        $this->assertSame(['name' => 'Changed'], $resolved->fresh()->pending_changes);
        $this->assertSame(2, CustomerDeal::count());
    }

    public function test_pending_deal_list_and_badge_exclude_lead_updates_and_inactive_deals(): void
    {
        [$pending] = $this->pendingDeal();
        [$left, $leftLead] = $this->pendingDeal();
        $leftLead->update(['status_id' => 1]);
        [$unrecorded, $unrecordedLead] = $this->pendingDeal();
        Lead::withoutEvents(fn () => $unrecordedLead->update(['deal_at' => null]));
        [$update, $updateLead, $customer] = $this->pendingDeal();
        DB::transaction(fn () => app(DealConversion::class)->link($updateLead, $customer));
        $update->refresh()->update(['pending_changes' => ['name' => 'New name']]);
        $this->actingAs($this->user());
        $url = '/api/aftersales/customers/deal-reviews';
        $this->getJson($url.'?queue=pending&per_page=1')->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $pending->id)->assertJsonPath('data.0.review_status', 'pending_review')
            ->assertJsonPath('pending_count', 1)->assertJsonPath('badge_label', '1')->assertJsonPath('has_pending', true);
        $this->getJson($url.'?queue=lead_updates')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $update->id)->assertJsonPath('pending_count', 1);
        $this->getJson($url)->assertOk()->assertJsonPath('total', 2);
        $this->getJson($url.'/pending-count')->assertOk()->assertJsonPath('data.pending_count', 1);
        $this->getJson($url.'?queue=invalid')->assertUnprocessable()->assertJsonValidationErrors('queue');
        $this->getJson($url.'?per_page=101')->assertUnprocessable()->assertJsonValidationErrors('per_page');
    }

    public function test_deal_pending_badge_caps_display_at_99_and_hides_at_zero(): void
    {
        $this->actingAs($this->user());
        $url = '/api/aftersales/customers/deal-reviews/pending-count';
        $this->getJson($url)->assertOk()->assertJsonPath('data.pending_count', 0)->assertJsonPath('data.badge_label', null)->assertJsonPath('data.has_pending', false);
        for ($i = 0; $i < 100; $i++) {
            $id = DB::table('leads')->insertGetId(['name' => 'Pending '.$i, 'status_id' => 5, 'deal_at' => now()]);
            DB::table('customer_deals')->insert(['lead_id' => $id, 'status' => 'needs_review', 'review_status' => 'pending_review']);
        }
        $this->getJson($url)->assertOk()->assertJsonPath('data.pending_count', 100)->assertJsonPath('data.badge_label', '99+')->assertJsonPath('data.has_pending', true);
        DB::table('customer_deals')->where('id', 1)->update(['review_status' => 'resolved']);
        $this->getJson($url)->assertOk()->assertJsonPath('data.pending_count', 99)->assertJsonPath('data.badge_label', '99');
    }

    public function test_deal_review_detail_compares_live_customers_and_eligible_quotation_machine_counts(): void
    {
        [$deal, $lead, $customer] = $this->pendingDeal();
        $type = DB::table('ref_product_types')->where('code_prefix', 'TD')->value('id');
        DB::table('ref_products')->insert([['id' => 1, 'name' => 'Tube product', 'product_type_id' => $type], ['id' => 2, 'name' => 'Accessory', 'product_type_id' => null]]);
        $quote = DB::table('quotations')->insertGetId(['lead_id' => $lead->id, 'quotation_no' => 'QT_DEAL_001']);
        DB::table('quotation_items')->insert([
            ['quotation_id' => $quote, 'product_id' => 1, 'qty' => 2, 'merge_into_item_id' => null],
            ['quotation_id' => $quote, 'product_id' => 2, 'qty' => 5, 'merge_into_item_id' => null],
            ['quotation_id' => $quote, 'product_id' => 1, 'qty' => 3, 'merge_into_item_id' => 1],
        ]);
        for ($i = 0; $i < 3; $i++) {
            $customer->products()->create(['machine_name' => 'Existing '.$i]);
        }
        $deleted = ServiceCustomer::create(['name' => 'Rusman', 'phone' => '0811440149']);
        $deleted->delete();
        $this->actingAs($this->user());
        $this->getJson('/api/aftersales/customers/deal-reviews/'.$deal->id)->assertOk()
            ->assertJsonPath('data.deal.id', $deal->id)->assertJsonPath('data.from_deal.quotation_no', 'QT_DEAL_001')
            ->assertJsonPath('data.from_deal.machine_count', 2)->assertJsonCount(1, 'data.from_deal.machines')
            ->assertJsonPath('data.from_deal.region.name', 'Ponorogo')->assertJsonPath('data.from_deal.province.name', 'Jawa Timur')
            ->assertJsonCount(1, 'data.candidates')->assertJsonPath('data.candidates.0.customer.id', $customer->id)
            ->assertJsonPath('data.candidates.0.customer.products_count', 3)->assertJsonPath('data.candidates.0.match_status', 'possible')
            ->assertJsonPath('data.allowed_actions', ['link', 'create_new']);
        $this->assertSame(3, ServiceCustomerProduct::count());
    }

    public function test_resolving_deals_decrements_badge_and_rejects_repeat_decisions_without_duplicate_units(): void
    {
        [$deal, $lead, $customer] = $this->pendingDeal();
        [$second] = $this->pendingDeal();
        $type = DB::table('ref_product_types')->where('code_prefix', 'TD')->value('id');
        DB::table('ref_products')->insert(['id' => 1, 'name' => 'Tube product', 'product_type_id' => $type]);
        $quote = DB::table('quotations')->insertGetId(['lead_id' => $lead->id]);
        DB::table('quotation_items')->insert(['quotation_id' => $quote, 'product_id' => 1, 'qty' => 2]);
        $this->actingAs($this->user());
        $url = '/api/aftersales/customers/deal-reviews/'.$deal->id.'/resolve';
        $input = ['action' => 'link', 'customer_id' => $customer->id, 'updated_at' => $deal->updated_at->toJSON()];
        $this->postJson($url, $input)->assertOk()->assertJsonPath('data.review_status', 'resolved')
            ->assertJsonPath('data.service_customer_id', $customer->id)->assertJsonPath('pending_count', 1)->assertJsonPath('badge_label', '1');
        $this->assertSame(2, ServiceCustomerProduct::count());
        $this->postJson($url, $input)->assertConflict();
        $this->assertSame(2, ServiceCustomerProduct::count());
        $this->postJson('/api/aftersales/customers/deal-reviews/'.$second->id.'/resolve', ['action' => 'create_new', 'updated_at' => $second->updated_at->toJSON()])
            ->assertOk()->assertJsonPath('data.review_status', 'resolved')->assertJsonPath('pending_count', 0)->assertJsonPath('badge_label', null)->assertJsonPath('has_pending', false);
        $this->assertSame(2, ServiceCustomer::count());
        $this->assertSame(2, DB::table('aftersales_customer_audits')->whereIn('action', ['lead_link', 'lead_create_new'])->count());
    }

    public function test_deal_review_disallows_skip_stale_decisions_deleted_customers_and_inactive_deals(): void
    {
        [$deal, $lead, $customer] = $this->pendingDeal();
        $this->actingAs($this->user());
        $url = '/api/aftersales/customers/deal-reviews/'.$deal->id.'/resolve';
        $this->postJson($url, ['action' => 'skip', 'updated_at' => $deal->updated_at->toJSON()])->assertUnprocessable()->assertJsonValidationErrors('action');
        $this->postJson($url, ['action' => 'ignore', 'updated_at' => $deal->updated_at->toJSON()])->assertConflict();
        $this->postJson($url, ['action' => 'create_new', 'updated_at' => 'stale'])->assertConflict();
        $customer->delete();
        $this->postJson($url, ['action' => 'link', 'customer_id' => $customer->id, 'updated_at' => $deal->updated_at->toJSON()])->assertNotFound();
        $lead->update(['status_id' => 1]);
        $deal->refresh();
        $this->postJson($url, ['action' => 'create_new', 'updated_at' => $deal->updated_at->toJSON()])->assertConflict();
        $this->assertNull($deal->fresh()->service_customer_id);
        $this->assertSame(0, ServiceCustomer::count());
        $this->assertSame(0, ServiceCustomerProduct::count());
    }

    public function test_pending_deals_refresh_matching_without_becoming_lead_updates_and_reenter_the_queue(): void
    {
        [$deal, $lead] = $this->pendingDeal();
        $lead->update(['phone' => '0811440147']);
        $deal->refresh();
        $this->assertSame('pending_review', $deal->review_status);
        $this->assertNull($deal->pending_changes);
        $this->assertSame('62811440147', $deal->lead_snapshot['phone']);
        $this->assertNotEmpty($deal->candidates);
        $lead->update(['status_id' => 1]);
        $this->assertSame(0, CustomerDeal::pendingReview()->count());
        $lead->update(['status_id' => 5]);
        $this->assertSame(1, CustomerDeal::pendingReview()->count());
        $this->assertSame(1, CustomerDeal::count());
        $this->assertSame(1, ServiceCustomer::count());
        $this->assertSame(0, ServiceCustomerProduct::count());
    }

    public function test_lead_updates_remain_separate_and_ignore_does_not_affect_pending_badge(): void
    {
        [$pending] = $this->pendingDeal();
        [$linked, $lead, $customer] = $this->pendingDeal();
        DB::transaction(fn () => app(DealConversion::class)->link($lead, $customer));
        $lead->update(['name' => 'Updated name']);
        $linked->refresh();
        $this->actingAs($this->user());
        $url = '/api/aftersales/customers/deal-reviews/'.$linked->id;
        $this->getJson($url)->assertOk()->assertJsonPath('data.allowed_actions', ['apply', 'ignore']);
        $this->postJson($url.'/resolve', ['action' => 'ignore', 'updated_at' => $linked->updated_at->toJSON()])
            ->assertOk()->assertJsonPath('pending_count', 1)->assertJsonPath('data.pending_changes', null);
        $this->assertSame('Pak Rusman', $customer->fresh()->name);
        $this->assertSame(1, CustomerDeal::pendingReview()->count());
    }

    public function test_deal_review_apis_require_customer_permission(): void
    {
        [$deal] = $this->pendingDeal();
        $this->actingAs($this->user(false));
        $url = '/api/aftersales/customers/deal-reviews';
        $this->getJson($url)->assertForbidden();
        $this->getJson($url.'/pending-count')->assertForbidden();
        $this->getJson($url.'/'.$deal->id)->assertForbidden();
        $this->postJson($url.'/'.$deal->id.'/resolve', ['action' => 'create_new', 'updated_at' => $deal->updated_at->toJSON()])->assertForbidden();
        $this->assertSame(1, CustomerDeal::pendingReview()->count());
    }

    private function ticketContactTables(): void
    {
        $this->ticketAuxiliaryTables(true);
        Schema::table('tickets', function (Blueprint $table) {
            foreach (['category', 'priority', 'description', 'sla_value', 'sla_unit', 'cost_display_currency'] as $field) {
                $table->string($field)->nullable();
            }
            $table->timestamp('sla_due_at')->nullable();
            foreach (['supervisor_id', 'created_by', 'updated_by'] as $field) {
                $table->unsignedBigInteger($field)->nullable();
            }
        });
        Schema::table('ticket_logs', fn (Blueprint $table) => $table->unsignedBigInteger('created_by')->nullable());
        (require database_path('migrations/2026_09_03_010500_create_document_counters_table.php'))->up();
        (require database_path('migrations/2026_09_03_010000_create_ref_spareparts_table.php'))->up();
    }

    private function ticketContactInput(ServiceCustomer $customer, ServiceCustomerProduct $machine): array
    {
        return ['customer_id' => $customer->id, 'customer_product_id' => $machine->id, 'category' => 'electrical', 'priority' => 'high', 'description' => 'Check machine', 'sla_value' => 1, 'sla_unit' => 'day'];
    }

    public function test_customer_list_and_detail_expose_ticket_contact_readiness_without_extra_reads(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Incomplete', 'phone' => '   ', 'email' => null]);
        $complete = ServiceCustomer::create(['name' => 'Complete', 'phone' => '08123456789', 'email' => 'customer@example.com']);
        $this->actingAs($this->user());
        $url = '/api/aftersales/customers/'.$customer->id;
        $this->getJson($url)->assertOk()->assertJsonPath('data.ticket_creation.can_create', false)
            ->assertJsonPath('data.ticket_creation.missing_fields', ['phone', 'email'])
            ->assertJsonPath('data.ticket_creation.edit_customer_url', '/aftersales/customers/'.$customer->id.'/edit');
        $response = $this->getJson('/api/aftersales/customers')->assertOk()->json('data');
        $this->assertFalse(collect($response)->firstWhere('id', $customer->id)['ticket_creation']['can_create']);
        $this->assertTrue(collect($response)->firstWhere('id', $complete->id)['ticket_creation']['can_create']);
        [, $queries] = $this->measureQueries(fn () => $this->getJson('/api/aftersales/customers/'.$complete->id)->assertOk()
            ->assertJsonPath('data.ticket_creation.missing_fields', [])->assertJsonPath('data.ticket_creation.message', null));
        $this->assertSame(1, $this->reads($queries, 'service_customers'));
    }

    public function test_new_ticket_rejects_each_missing_contact_without_number_logs_stock_or_cost_mutations(): void
    {
        $this->ticketContactTables();
        $customer = ServiceCustomer::create(['name' => 'Incomplete']);
        $machine = $customer->products()->create(['machine_name' => 'Machine']);
        $part = \App\Models\Aftersales\RefSparepart::create(['name' => 'Contactor', 'stock' => 10, 'price' => 100]);
        $input = $this->ticketContactInput($customer, $machine) + [
            'parts' => [['ref_sparepart_id' => $part->id, 'qty' => 2, 'unit' => 'pieces']],
            'costs' => [['category' => 'labor', 'amount' => 100, 'currency' => 'idr']],
            'ticket_creation' => ['can_create' => true],
        ];
        $this->actingAs($this->user());
        foreach ([[null, null, ['phone', 'email']], ['08123456789', ' ', ['email']], ['---', 'customer@example.com', ['phone']]] as [$phone, $email, $missing]) {
            $customer->update(['phone' => $phone, 'email' => $email]);
            $this->postJson('/api/aftersales/tickets', $input)->assertUnprocessable()
                ->assertJsonPath('code', 'customer_contact_incomplete')->assertJsonPath('customer_id', $customer->id)
                ->assertJsonPath('ticket_creation.can_create', false)->assertJsonPath('ticket_creation.missing_fields', $missing)
                ->assertJsonValidationErrors(array_map(fn ($field) => 'customer.'.$field, $missing));
        }
        $this->assertSame(1, Ticket::count()); // Only the legacy fixture ticket remains.
        foreach (['document_counters', 'ticket_logs', 'ticket_parts', 'ticket_costs', 'part_stock_movements', 'ticket_service_forms', 'ticket_agreements'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), $table.' must not change when the contact gate fails.');
        }
        $this->assertSame(10, $part->fresh()->stock);
    }

    public function test_ticket_gate_reads_latest_customer_and_allows_creation_after_email_is_completed(): void
    {
        $this->ticketContactTables();
        $customer = ServiceCustomer::create(['name' => 'Customer', 'phone' => '08123456789', 'email' => 'customer@example.com']);
        $machine = $customer->products()->create(['machine_name' => 'Machine']);
        $this->actingAs($this->user());
        $input = $this->ticketContactInput($customer, $machine);
        $this->getJson('/api/aftersales/customers/'.$customer->id)->assertOk()->assertJsonPath('data.ticket_creation.can_create', true);
        DB::table('service_customers')->where('id', $customer->id)->update(['email' => null]);
        $this->postJson('/api/aftersales/tickets', $input)->assertUnprocessable()->assertJsonValidationErrors('customer.email');
        $this->putJson('/api/aftersales/customers/'.$customer->id, ['email' => 'fixed@example.com'])->assertOk();
        $this->getJson('/api/aftersales/customers/'.$customer->id)->assertOk()->assertJsonPath('data.ticket_creation.can_create', true);
        $this->postJson('/api/aftersales/tickets', $input)->assertCreated()->assertJsonPath('data.data.customer.email', 'fixed@example.com');
        $this->assertSame(2, Ticket::count());
        $this->assertSame(1, DB::table('document_counters')->value('last_number'));
        $this->assertSame(1, DB::table('ticket_logs')->where('step', 'published')->count());
    }

    public function test_complete_contacts_allow_daxtro_and_non_daxtro_tickets_without_serial_or_warranty(): void
    {
        $this->ticketContactTables();
        $customer = ServiceCustomer::create(['name' => 'Customer', 'phone' => '08123456789', 'email' => 'customer@example.com']);
        $this->actingAs($this->user());
        foreach ([true, false] as $isDaxtro) {
            $machine = $customer->products()->create(['is_daxtro' => $isDaxtro, 'machine_name' => 'Machine', 'brand' => $isDaxtro ? null : 'Other Brand']);
            $this->assertNull($machine->serial_number);
            $this->assertNull($machine->warranty_start);
            $this->postJson('/api/aftersales/tickets', $this->ticketContactInput($customer, $machine))
                ->assertCreated()->assertJsonPath('data.data.customer_product.serial_number', null);
        }
        $this->assertSame(3, Ticket::count());
    }

    public function test_existing_ticket_update_does_not_require_customer_phone_or_email(): void
    {
        $this->ticketContactTables();
        $customer = ServiceCustomer::create(['name' => 'Legacy customer']);
        $machine = $customer->products()->create(['machine_name' => 'Legacy machine']);
        $ticket = Ticket::create(['ticket_code' => 'LEGACY-CONTACT', 'customer_id' => $customer->id, 'customer_product_id' => $machine->id]);
        $this->actingAs($this->user());
        $input = $this->ticketContactInput($customer, $machine);
        $input['description'] = 'Updated legacy complaint';
        $this->putJson('/api/aftersales/tickets/'.$ticket->id, $input)->assertOk()->assertJsonPath('data.data.description', 'Updated legacy complaint');
        $this->assertSame('Updated legacy complaint', $ticket->fresh()->description);
        $this->assertSame(0, DB::table('document_counters')->count());
    }

    public function test_new_ticket_contact_gate_preserves_permission_ownership_and_deleted_customer_checks(): void
    {
        $this->ticketContactTables();
        $customer = ServiceCustomer::create(['name' => 'Customer', 'phone' => '08123456789', 'email' => 'customer@example.com']);
        $machine = $customer->products()->create(['machine_name' => 'Machine']);
        $input = $this->ticketContactInput($customer, $machine);
        $this->actingAs($this->user(false));
        $this->postJson('/api/aftersales/tickets', $input)->assertForbidden();
        $this->actingAs($this->user());
        $other = ServiceCustomer::create(['name' => 'Other']);
        $this->postJson('/api/aftersales/tickets', array_replace($input, ['customer_id' => $other->id]))
            ->assertUnprocessable()->assertJsonValidationErrors('customer_product_id');
        $customer->delete();
        $this->postJson('/api/aftersales/tickets', $input)->assertNotFound();
        $this->assertSame(1, Ticket::count());
        $this->assertSame(0, DB::table('document_counters')->count());
    }

    private function visitDateTicket(string $code, array $dates, array $attributes = []): Ticket
    {
        $ticket = Ticket::create($attributes + ['ticket_code' => $code]);
        foreach ($dates as $date) {
            DB::table('ticket_visits')->insert(['ticket_id' => $ticket->id, 'scheduled_at' => $date]);
        }

        return $ticket;
    }

    public function test_visit_date_filter_uses_latest_active_visit_including_recoming_and_rescheduled_dates(): void
    {
        $this->ticketAuxiliaryTables();
        Schema::table('ticket_recomings', function (Blueprint $table) {
            $table->integer('visit_no')->nullable();
            $table->timestamp('planned_visit_at')->nullable();
            $table->string('reason')->nullable();
            $table->string('from_step')->nullable();
        });
        $ticket = $this->visitDateTicket('RECOMING-DATE', ['2026-10-20 10:00:00', '2026-10-27 09:00:00']);
        DB::table('ticket_recomings')->insert(['ticket_id' => $ticket->id, 'visit_no' => 2, 'planned_visit_at' => '2026-10-27 09:00:00']);
        \App\Models\Aftersales\TicketLog::create(['ticket_id' => $ticket->id, 'step' => 'recoming']);
        $this->actingAs($this->user());
        $this->getJson('/api/aftersales/tickets?visit_date=2026-10-20')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/aftersales/tickets?visit_date=2026-10-27')->assertOk()
            ->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $ticket->id)->assertJsonPath('data.0.current_recoming.visit_no', 2)
            ->assertJsonPath('data.0.visit_date', '2026-10-27T02:00:00.000000Z');
        DB::table('ticket_visits')->where('ticket_id', $ticket->id)->where('scheduled_at', '2026-10-27 09:00:00')->update(['scheduled_at' => '2026-10-28 14:00:00']);
        $this->getJson('/api/aftersales/tickets?visit_date=2026-10-27')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/aftersales/tickets?visit_date=2026-10-28')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $ticket->id);
    }

    public function test_visit_date_filter_handles_day_boundaries_missing_visits_and_soft_deletes(): void
    {
        $this->ticketAuxiliaryTables();
        $start = $this->visitDateTicket('START', ['2026-10-27 00:00:00']);
        $end = $this->visitDateTicket('END', ['2026-10-27 23:59:59']);
        $this->visitDateTicket('PREVIOUS', ['2026-10-26 23:59:59']);
        $this->visitDateTicket('NEXT', ['2026-10-28 00:00:00']);
        $this->visitDateTicket('NO-DATE', [null]);
        $this->visitDateTicket('NO-VISITS', []);
        $deletedTicket = $this->visitDateTicket('DELETED-TICKET', ['2026-10-27 12:00:00']);
        $deletedTicket->delete();
        $active = $this->visitDateTicket('DELETED-LATER-VISIT', ['2026-10-27 12:00:00']);
        DB::table('ticket_visits')->insert(['ticket_id' => $active->id, 'scheduled_at' => '2026-10-28 12:00:00', 'deleted_at' => now()]);
        $onlyDeleted = $this->visitDateTicket('ONLY-DELETED-VISIT', ['2026-10-27 12:00:00']);
        DB::table('ticket_visits')->where('ticket_id', $onlyDeleted->id)->update(['deleted_at' => now()]);
        $this->actingAs($this->user());
        $response = $this->getJson('/api/aftersales/tickets?visit_date=2026-10-27')->assertOk()->assertJsonPath('total', 3);
        $this->assertSame([$active->id, $end->id, $start->id], array_column($response->json('data'), 'id'));
    }

    public function test_visit_date_combines_with_search_priority_and_supervisor_before_pagination(): void
    {
        $this->ticketContactTables();
        $customer = ServiceCustomer::create(['name' => 'Search customer']);
        $attributes = ['customer_id' => $customer->id, 'priority' => 'high', 'supervisor_id' => 1];
        $first = $this->visitDateTicket('MATCH-1', ['2026-10-27 08:00:00', '2026-10-27 10:00:00'], $attributes);
        $second = $this->visitDateTicket('MATCH-2', ['2026-10-27 11:00:00'], array_replace($attributes, ['priority' => 'medium']));
        $this->visitDateTicket('WRONG-PRIORITY', ['2026-10-27 11:00:00'], array_replace($attributes, ['priority' => 'low']));
        $this->visitDateTicket('WRONG-SUPERVISOR', ['2026-10-27 11:00:00'], array_replace($attributes, ['supervisor_id' => null]));
        $this->visitDateTicket('WRONG-DATE', ['2026-10-28 11:00:00'], $attributes);
        $this->visitDateTicket('WRONG-SEARCH', ['2026-10-27 11:00:00'], array_replace($attributes, ['customer_id' => null]));
        $this->actingAs($this->user());
        $url = '/api/aftersales/tickets?'.http_build_query(['visit_date' => '2026-10-27', 'search' => 'Search customer', 'priority' => ['high', 'medium'], 'supervisor_id' => 1, 'per_page' => 1]);
        $this->getJson($url)->assertOk()->assertJsonPath('total', 2)->assertJsonPath('last_page', 2)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $second->id);
        $this->getJson($url.'&page=2')->assertOk()->assertJsonPath('total', 2)->assertJsonPath('data.0.id', $first->id);
        $this->getJson('/api/aftersales/tickets?visit_date=2026-10-27&search=MATCH&supervisor=me')->assertOk()->assertJsonPath('total', 2);
    }

    public function test_visit_date_filter_validates_single_iso_date_and_empty_value_resets_it(): void
    {
        $this->ticketAuxiliaryTables();
        $this->visitDateTicket('DATED', ['2026-10-27 10:00:00']);
        $this->visitDateTicket('UNDATED', []);
        $this->actingAs($this->user());
        foreach (['2026-02-30', '27/10/2026', '2026-10-27T00:00:00Z', '2026-10', ['2026-10-27', '2026-10-28']] as $invalid) {
            $this->getJson('/api/aftersales/tickets?'.http_build_query(['visit_date' => $invalid]))
                ->assertUnprocessable()->assertJsonValidationErrors('visit_date');
        }
        $this->getJson('/api/aftersales/tickets?search=DATE')->assertOk()->assertJsonPath('total', 2);
        $this->getJson('/api/aftersales/tickets?search=DATE&visit_date=')->assertOk()->assertJsonPath('total', 2);
    }

    public function test_visit_date_filter_keeps_technician_ownership_and_permission_boundaries(): void
    {
        $this->ticketAuxiliaryTables();
        $mine = $this->visitDateTicket('MY-VISIT', ['2026-10-27 10:00:00'], ['assigned_technician_id' => 1]);
        $this->visitDateTicket('OTHER-VISIT', ['2026-10-27 10:00:00'], ['assigned_technician_id' => 2]);
        $technician = Mockery::mock(User::class)->makePartial();
        $technician->setRawAttributes(['id' => 1]);
        $technician->shouldReceive('hasPermission')->andReturnUsing(fn ($code) => $code === 'aftersales.tickets.view-own');
        $this->actingAs($technician);
        $this->getJson('/api/aftersales/tickets?visit_date=2026-10-27')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $mine->id);
        $this->getJson('/api/aftersales/tickets?visit_date=2026-10-27&assigned_technician_id=2')->assertOk()->assertJsonPath('total', 0);
        $this->actingAs($this->user(false));
        $this->getJson('/api/aftersales/tickets?visit_date=2026-10-27')->assertForbidden();
    }

    public function test_customer_upload_always_sets_customer_type_and_authenticated_uploader(): void
    {
        $this->actingAs($this->user());
        $this->post('/api/aftersales/customers/imports', [
            'file' => $this->file([[]]), 'type' => 'sparepart', 'created_by_user_id' => 2,
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('data.import.type', CustomerImport::TYPE_CUSTOMER)->assertJsonPath('data.import.created_by_user_id', 1);
        $this->assertSame(0, CustomerImport::ofType(CustomerImport::TYPE_SPAREPART)->count());
        $this->assertSame(0, ServiceCustomer::count());
    }

    private function file(array $rows): UploadedFile
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Import Customer');
        $sheet->fromArray(CustomerImports::HEADERS, null, 'A1');
        foreach ($rows as $i => $row) {
            $input = array_replace(['Customer Name*' => 'Mr Rusman', 'Phone' => '0811440149', 'Province' => 'Jawa Timur', 'City / Region' => 'Ponorogo', 'Machine Category*' => 'Tube', 'Machine Name' => 'TD 50', 'Capacity' => '5', 'Serial Number' => 'SN-'.$i, 'Warranty Start' => '27-Des-25'], $row);
            foreach (CustomerImports::HEADERS as $j => $key) {
                $sheet->setCellValueExplicit([$j + 1, $i + 2], (string) ($input[$key] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'customer-import');
        (new Xlsx($book))->save($path);
        $this->beforeApplicationDestroyed(fn () => @unlink($path));

        return new UploadedFile($path, 'customers.xlsx', null, null, true);
    }

    private function upload(array $rows): CustomerImport
    {
        return app(CustomerImports::class)->upload($this->file($rows), 1);
    }

    private function user(bool $allowed = true): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => 1, 'name' => 'After Sales']);
        $user->shouldReceive('hasPermission')->andReturn($allowed);

        return $user;
    }

    private function measureQueries(callable $work): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $result = $work();

            return [$result, DB::getQueryLog()];
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    private function reads(array $queries, string $table): int
    {
        return count(array_filter($queries, fn ($q) => preg_match('/^select\b/i', $q['query']) && preg_match('/\bfrom\s+["`]?'.preg_quote($table, '/').'["`]?\b/i', $q['query'])));
    }

    public function test_migration_preserves_original_scores_and_grandfathers_existing_tickets(): void
    {
        $response = TicketSatisfaction::first();
        $this->assertSame(1, $response->timeliness);
        $this->assertSame(3, $response->grooming);
        $this->assertSame(5, $response->score_original['grooming']);
        $answer = DB::table('ticket_satisfaction_answers')->first();
        $this->assertSame(4, $answer->score_original);
        $this->assertSame(3, $answer->value_number);
        $this->assertFalse(Ticket::find(1)->happy_call_required);
        $this->assertTrue(Ticket::create(['ticket_code' => 'new'])->fresh()->happy_call_required);
        $this->assertSame(1, DB::table('user_role_permissions')->where('role_id', DB::table('user_roles')->where('code', 'supervisor_after_sales')->value('id'))->count());
    }

    public function test_import_preview_writes_no_customers_and_apply_groups_many_machines_idempotently(): void
    {
        $batch = $this->upload([[], ['Serial Number' => 'SN-SECOND'], ['Serial Number' => 'SN-THIRD']]);
        $this->assertSame(CustomerImport::TYPE_CUSTOMER, $batch->type);
        $this->assertSame(0, ServiceCustomer::count());
        $this->assertSame(0, ServiceCustomerProduct::count());
        $this->assertSame(['new', 'exact', 'exact'], $batch->rows->pluck('review.status')->all());
        app(CustomerImports::class)->apply($batch, 0, 1);
        $this->assertSame(1, ServiceCustomer::count());
        $this->assertSame(3, ServiceCustomerProduct::count());
        $machine = ServiceCustomerProduct::first();
        $this->assertSame('2025-12-27', $machine->warranty_start->toDateString());
        $this->assertSame('TD 5', $machine->machine_code);
        app(CustomerImports::class)->apply($batch, 0, 1);
        $this->assertSame(3, ServiceCustomerProduct::count());
        $again = $this->upload([[]]);
        $this->assertSame('already_exists', $again->rows->first()->review['status']);
    }

    public function test_possible_matches_require_explicit_resolution_and_errors_are_skipped(): void
    {
        ServiceCustomer::create(['name' => 'Pak Rusman', 'phone' => '62811440148', 'ref_region_id' => 1]);
        $batch = $this->upload([[], ['Phone' => '6.28139E+24']]);
        $this->assertSame(['possible', 'error'], $batch->rows->pluck('review.status')->all());
        try {
            app(CustomerImports::class)->apply($batch, 0, 1);
            $this->fail('Unresolved possible match must fail');
        } catch (ValidationException) {
        }
        $batch->rows()->first()->update(['decision' => ['action' => 'link', 'customer_id' => ServiceCustomer::first()->id]]);
        app(CustomerImports::class)->apply($batch, 0, 1);
        $this->assertSame(1, ServiceCustomer::count());
        $this->assertSame(1, ServiceCustomerProduct::count());
    }

    public function test_duplicate_serial_in_file_uses_last_owner_and_earliest_warranty(): void
    {
        $batch = $this->upload([['Serial Number' => ' SAME ', 'Warranty Start' => '01/01/2024'], ['Customer Name*' => 'Another Owner', 'Phone' => '089999999999', 'Serial Number' => 'same', 'Warranty Start' => '01/01/2025']]);
        app(CustomerImports::class)->apply($batch, 0, 1);
        $this->assertSame(1, ServiceCustomerProduct::count());
        $machine = ServiceCustomerProduct::first();
        $this->assertSame('Another Owner', $machine->customer->name);
        $this->assertSame('2024-01-01', $machine->warranty_start->toDateString());
        $this->assertStringContainsString('Previously owned by Mr Rusman', $machine->note);
    }

    public function test_deal_conversion_creates_units_once_and_does_not_overwrite_customer(): void
    {
        $type = DB::table('ref_product_types')->where('code_prefix', 'TD')->value('id');
        DB::table('ref_products')->insert(['id' => 1, 'name' => 'Tube Machine', 'product_type_id' => $type, 'warranty_available' => true, 'warranty_time_month' => 24]);
        $lead = Lead::withoutEvents(fn () => Lead::create(['name' => 'Pak Deal', 'phone' => '08123456789', 'status_id' => 5, 'deal_at' => now(), 'factory_city_id' => 1]));
        DB::table('quotations')->insert(['id' => 1, 'lead_id' => $lead->id]);
        DB::table('quotation_items')->insert(['quotation_id' => 1, 'product_id' => 1, 'qty' => 2]);
        $link = app(DealConversion::class)->sync($lead);
        $this->assertSame(2, $link->customer->products()->count());
        $this->assertNull($link->customer->products()->first()->warranty_start);
        $lead->update(['phone' => '089999999999']);
        $this->assertSame('628123456789', $link->customer->fresh()->phone);
        $this->assertNotNull($link->fresh()->pending_changes);
        $lead->update(['status_id' => 1]);
        $this->assertNotNull($link->fresh()->left_deal_at);
        $lead->update(['status_id' => 5]);
        $this->assertNull($link->fresh()->left_deal_at);
        $this->assertSame(1, CustomerDeal::count());
        $this->assertSame(2, ServiceCustomerProduct::count());
    }

    public function test_machine_warranty_defaults_and_product_period_survive_manual_edits(): void
    {
        $this->actingAs($this->user());
        $customer = ServiceCustomer::create(['name' => 'Warranty Customer']);
        $this->postJson('/api/aftersales/customers/'.$customer->id.'/products', [
            'machine_name' => 'Manual Machine', 'serial_number' => 'WARRANTY-DEFAULT',
            'warranty_start' => '2026-10-23',
        ])->assertCreated();
        $manual = $customer->products()->first();
        $this->assertSame('2027-10-23', $manual->warranty_end->toDateString());
        $this->assertSame('1 Year', $manual->warranty_period);

        DB::table('ref_products')->insert(['id' => 1, 'name' => '24 Month Machine', 'warranty_available' => true, 'warranty_time_month' => 24]);
        $machine = $customer->products()->create([
            'product_id' => 1, 'machine_name' => 'Product Machine', 'serial_number' => 'WARRANTY-PRODUCT',
        ]);
        $url = '/api/aftersales/customers/products/'.$machine->id;
        $input = ['machine_name' => 'Product Machine', 'serial_number' => 'WARRANTY-PRODUCT', 'warranty_start' => '2026-10-23'];
        $this->putJson($url, $input)->assertOk();
        $machine->refresh();
        $this->assertEquals(1, $machine->product_id);
        $this->assertSame('2028-10-23', $machine->warranty_end->toDateString());
        $this->assertSame('2 Years', $machine->warranty_period);

        $this->putJson($url, array_replace($input, ['warranty_end' => '2027-10-01']))->assertOk();
        $machine->refresh();
        $this->assertSame('2027-10-01', $machine->warranty_end->toDateString());
        $this->assertSame('11 Months', $machine->warranty_period);
    }

    public function test_standalone_machine_and_note_are_available_after_create_and_edit(): void
    {
        $this->actingAs($this->user());
        $customer = ServiceCustomer::create(['name' => 'Machine Note Customer']);
        $input = [
            'machine_name' => 'Custom Machine', 'serial_number' => 'NOTE-MACHINE',
            'note' => "Previously owned by A\nCheck valve <B> & pump",
        ];
        $this->postJson('/api/aftersales/customers/'.$customer->id.'/products', $input)->assertCreated();
        $machine = $customer->products()->firstOrFail();
        $this->assertNull($machine->product_id);
        $this->getJson('/api/aftersales/customers/'.$customer->id)->assertOk()
            ->assertJsonPath('data.products.0.id', $machine->id)
            ->assertJsonPath('data.products.0.machine_name', 'Custom Machine')
            ->assertJsonPath('data.products.0.note', $input['note']);

        $input['note'] = "Updated after inspection\nKeep this note.";
        $this->putJson('/api/aftersales/customers/products/'.$machine->id, $input)->assertOk();
        $this->getJson('/api/aftersales/customers/'.$customer->id)->assertOk()
            ->assertJsonPath('data.products.0.note', $input['note']);
        $this->assertSame($input['note'], $machine->fresh()->note);
    }

    public function test_customer_delete_soft_deletes_and_preserves_machine_and_ticket_records(): void
    {
        $this->actingAs($this->user());
        $customer = ServiceCustomer::create(['name' => 'Archived Customer', 'phone' => '08123456789']);
        $machine = $customer->products()->create(['machine_name' => 'Archived Machine', 'serial_number' => 'ARCHIVE-1']);
        $ticket = Ticket::create(['customer_id' => $customer->id, 'customer_product_id' => $machine->id]);

        $this->deleteJson('/api/aftersales/customers/'.$customer->id)->assertOk();
        $this->assertNull(ServiceCustomer::find($customer->id));
        $archived = ServiceCustomer::withTrashed()->findOrFail($customer->id);
        $this->assertNotNull($archived->deleted_at);
        $this->assertEquals(1, $archived->deleted_by);
        $this->assertTrue((bool) $archived->is_deleted);
        $this->assertSame($customer->id, $machine->fresh()->customer_id);
        $this->assertSame($customer->id, $ticket->fresh()->customer_id);
        $this->getJson('/api/aftersales/customers')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/aftersales/customers/'.$customer->id)->assertNotFound();
        $this->deleteJson('/api/aftersales/customers/'.$customer->id)->assertNotFound();
    }

    public function test_customer_delete_requires_customer_management_permission(): void
    {
        $this->actingAs($this->user(false));
        $customer = ServiceCustomer::create(['name' => 'Protected Customer']);
        $this->deleteJson('/api/aftersales/customers/'.$customer->id)->assertForbidden();
        $this->assertNotNull(ServiceCustomer::find($customer->id));
    }

    public function test_reimport_deleted_customer_creates_new_customer_and_keeps_old_machines_and_deal_archived(): void
    {
        $this->actingAs($this->user());
        $owner = ServiceCustomer::create(['name' => 'Mr Rusman', 'phone' => '62811440149', 'ref_region_id' => 1]);
        $first = $owner->products()->create(['machine_name' => 'Original Machine', 'serial_number' => 'ARCHIVED-A', 'note' => 'Keep original note', 'warranty_start' => '2024-01-01']);
        $second = $owner->products()->create(['machine_name' => 'Second Machine', 'serial_number' => 'ARCHIVED-B']);
        $ticket = Ticket::create(['customer_id' => $owner->id, 'customer_product_id' => $first->id]);
        $lead = Lead::withoutEvents(fn () => Lead::create(['name' => 'Mr Rusman', 'phone' => '0811440149', 'status_id' => 5, 'deal_at' => now(), 'factory_city_id' => 1]));
        $deal = app(DealConversion::class)->link($lead, $owner);
        $this->deleteJson('/api/aftersales/customers/'.$owner->id)->assertOk();

        $batch = $this->upload([['Serial Number' => 'ARCHIVED-A'], ['Serial Number' => 'ARCHIVED-B']]);
        $this->assertSame(['new', 'exact'], $batch->rows->pluck('review.status')->all());
        foreach ($batch->rows as $row) {
            $this->assertTrue($row->review['keep_machine_archived']);
            $this->assertStringContainsString('stays archived', implode(' ', $row->review['warnings']));
        }
        $this->assertSame(0, ServiceCustomer::count());
        app(CustomerImports::class)->apply($batch, 0, 1);
        $customer = ServiceCustomer::firstOrFail();
        $this->assertNotSame($owner->id, $customer->id);
        $this->assertSame('import', $customer->source);
        $this->assertSame(0, $customer->products()->count());
        $this->assertSame(2, ServiceCustomerProduct::count());
        $this->assertSame($owner->id, $first->fresh()->customer_id);
        $this->assertSame($owner->id, $second->fresh()->customer_id);
        $this->assertSame('Keep original note', $first->fresh()->note);
        $this->assertSame('2024-01-01', $first->fresh()->warranty_start->toDateString());
        $this->assertSame($owner->id, $ticket->fresh()->customer_id);
        $this->assertEquals($owner->id, $deal->fresh()->service_customer_id);
        $this->assertTrue(ServiceCustomer::withTrashed()->find($owner->id)->trashed());
        foreach ($batch->rows()->get() as $row) {
            $this->assertSame($customer->id, $row->result['customer_id']);
            $this->assertNull($row->result['machine_id']);
            $this->assertSame('kept_archived', $row->result['machine_status']);
        }
        app(CustomerImports::class)->apply($batch, 0, 1);
        $again = $this->upload([['Serial Number' => 'ARCHIVED-A']]);
        app(CustomerImports::class)->apply($again, 0, 1);
        $this->assertSame(1, ServiceCustomer::count());
        $this->assertSame(2, ServiceCustomerProduct::count());
    }

    public function test_reimport_deleted_customer_with_new_serial_creates_new_machine_without_restoring_old_owner(): void
    {
        $owner = ServiceCustomer::create(['name' => 'Mr Rusman', 'phone' => '62811440149', 'ref_region_id' => 1]);
        $oldMachine = $owner->products()->create(['machine_name' => 'Old Machine', 'serial_number' => 'OLD-SERIAL']);
        $owner->delete();
        $batch = $this->upload([['Serial Number' => 'NEW-SERIAL']]);
        $this->assertSame('new', $batch->rows->first()->review['status']);
        app(CustomerImports::class)->apply($batch, 0, 1);
        $customer = ServiceCustomer::firstOrFail();
        $this->assertNotSame($owner->id, $customer->id);
        $this->assertSame('NEW-SERIAL', $customer->products()->firstOrFail()->serial_number);
        $this->assertSame($owner->id, $oldMachine->fresh()->customer_id);
        $this->assertTrue($owner->fresh()->trashed());
    }

    public function test_import_refuses_stale_archive_decision_if_old_owner_is_restored_before_apply(): void
    {
        $owner = ServiceCustomer::create(['name' => 'Mr Rusman', 'phone' => '62811440149']);
        $owner->products()->create(['machine_name' => 'Old Machine', 'serial_number' => 'ARCHIVED-A']);
        $owner->delete();
        $batch = $this->upload([['Serial Number' => 'ARCHIVED-A']]);
        $owner->restore();
        try {
            app(CustomerImports::class)->apply($batch, 0, 1);
            $this->fail('Changed archive ownership must require a new review.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
        $this->assertSame(1, ServiceCustomer::count());
        $this->assertSame(1, ServiceCustomerProduct::count());
        $this->assertNotSame('applied', $batch->fresh()->status);
    }

    public function test_move_api_returns_open_ticket_warning_without_changing_machine_or_note(): void
    {
        $this->actingAs($this->user());
        $owner = ServiceCustomer::create(['name' => 'Owner']);
        $destination = ServiceCustomer::create(['name' => 'Destination']);
        $machine = $owner->products()->create(['machine_name' => 'Machine', 'serial_number' => 'OPEN-TICKET', 'note' => 'Original note']);
        $ticket = Ticket::create(['customer_id' => $owner->id, 'customer_product_id' => $machine->id]);
        $ticket->logs()->create(['step' => 'assigned']);

        $this->postJson('/api/aftersales/customers/products/'.$machine->id.'/move', [
            'customer_id' => $destination->id, 'note' => 'Transfer note',
        ])->assertStatus(422)->assertJsonPath('message', 'This machine has open tickets. Close them first.');

        $machine->refresh();
        $this->assertSame($owner->id, $machine->customer_id);
        $this->assertSame('Original note', $machine->note);
        $this->assertSame(0, DB::table('aftersales_customer_audits')->where('action', 'machine_moved')->count());
    }

    public function test_move_blocks_open_tickets_then_preserves_history_and_contract(): void
    {
        $a = ServiceCustomer::create(['name' => 'Owner A']);
        $b = ServiceCustomer::create(['name' => 'Owner B']);
        $machine = $a->products()->create(['machine_name' => 'Machine', 'serial_number' => 'ABC', 'warranty_start' => '2026-01-01', 'pm_contract_status' => 'premium']);
        $ticket = Ticket::create(['customer_id' => $a->id, 'customer_product_id' => $machine->id]);
        try {
            app(MachineTransfer::class)->move($machine, $b->id, null, 1);
            $this->fail('Open tickets must block transfer');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
        $ticket->logs()->create(['step' => 'closed']);
        $moved = app(MachineTransfer::class)->move($machine, $b->id, null, 1);
        $this->assertSame($b->id, $moved->customer_id);
        $this->assertSame($a->id, $ticket->fresh()->customer_id);
        $this->assertSame('2026-01-01', $moved->warranty_start->toDateString());
        $this->assertSame('3 months', $moved->pm_frequency);
        $this->assertSame('machine_moved', DB::table('aftersales_customer_audits')->first()->action);
    }

    public function test_happy_call_gate_and_existing_review_gate_are_both_enforced(): void
    {
        $this->actingAs($this->user());
        $ticket = Ticket::create(['ticket_code' => 'NEW']);
        $ticket->logs()->create(['step' => 'documentation_published']);
        $url = '/api/aftersales/tickets/'.$ticket->id;
        $this->postJson($url.'/close')->assertStatus(422)->assertSee('Happy Call Completed');
        $this->postJson($url.'/happy-call', ['note' => 'Customer contacted'])->assertCreated();
        TicketSatisfaction::create(['ticket_id' => $ticket->id, 'review_status' => 'needs_review']);
        $this->postJson($url.'/close')->assertStatus(422)->assertSee('Needs review');
        TicketSatisfaction::where('ticket_id', $ticket->id)->update(['review_status' => 'reviewed']);
        $this->postJson($url.'/close')->assertCreated();
        $this->assertSame('closed', $ticket->fresh()->progress);
    }

    public function test_maps_validation_and_import_permissions(): void
    {
        $this->actingAs($this->user());
        $this->postJson('/api/aftersales/customers', ['name' => 'Test', 'address_maps_url' => 'https://google.com.evil.example/maps'])->assertUnprocessable();
        $this->postJson('/api/aftersales/customers', ['name' => 'Test', 'address_maps_url' => 'https://maps.app.goo.gl/abcdef'])->assertCreated();
        $this->actingAs($this->user(false));
        $this->getJson('/api/aftersales/customers/imports/template')->assertForbidden();
    }

    public function test_date_parser_and_phone_matching(): void
    {
        $service = app(CustomerImports::class);
        $this->assertSame('2026-08-30', $service->date('30-Agt-2026'));
        $this->assertSame('2026-09-28', $service->date('28-sept-26'));
        $this->assertNull($service->date('30-Dec-00'));
        $this->assertSame('62811440149', CustomerMatching::phone('811440149'));
        $this->assertSame('rusman', CustomerMatching::name('Mr Pak Rusman'));
        $this->expectException(\InvalidArgumentException::class);
        $service->date('31/02/2026');
    }

    public function test_api_review_corrections_lock_version_and_apply(): void
    {
        $this->actingAs($this->user());
        $upload = $this->postJson('/api/aftersales/customers/imports', ['file' => $this->file([['Phone' => '6.28139E+24']])])->assertCreated()->json('data');
        $this->assertSame(1, $upload['summary']['error']);
        $this->assertSame(0, $upload['import']['lock_version']);
        $url = '/api/aftersales/customers/imports/'.$upload['import']['id'];
        $review = $this->putJson($url.'/review', ['lock_version' => 0, 'rows' => [['id' => $upload['rows'][0]['id'], 'input' => ['Phone' => '08123456789']]]])->assertOk()->json('data');
        $this->assertSame(0, $review['summary']['error']);
        $this->assertSame(1, $review['import']['lock_version']);
        $this->postJson($url.'/apply', ['lock_version' => 0])->assertConflict();
        $this->postJson($url.'/apply', ['lock_version' => 1])->assertOk();
        $this->assertSame(1, ServiceCustomerProduct::count());
        $this->get($url.'/export')->assertOk();
    }

    public function test_template_header_mismatch_and_foreign_import_are_rejected(): void
    {
        $file = $this->file([[]]);
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
        $book->getActiveSheet()->setCellValue('A1', 'Wrong header');
        (new Xlsx($book))->save($file->getRealPath());
        try {
            app(CustomerImports::class)->upload($file, 1);
            $this->fail('Invalid headers must fail');
        } catch (ValidationException) {
        }
        $this->assertSame(0, CustomerImport::count());
        $batch = $this->upload([[]]);
        DB::table('users')->insert(['id' => 2, 'name' => 'Another uploader']);
        $batch->update(['created_by_user_id' => 2]);
        $this->actingAs($this->user());
        $this->getJson('/api/aftersales/customers/imports/'.$batch->id)->assertForbidden();
    }

    public function test_import_links_existing_sales_deal_without_duplicate_customer(): void
    {
        $lead = Lead::withoutEvents(fn () => Lead::create(['name' => 'Pak Rusman', 'phone' => '0811440149', 'status_id' => 5, 'deal_at' => now(), 'factory_city_id' => 1]));
        $batch = $this->upload([[]]);
        $this->assertSame('exact', $batch->rows->first()->review['status']);
        app(CustomerImports::class)->apply($batch, 0, 1);
        $this->assertSame(1, ServiceCustomer::count());
        $this->assertSame(ServiceCustomer::first()->id, $lead->customerDeal->service_customer_id);
        app(DealConversion::class)->sync($lead);
        $this->assertSame(1, ServiceCustomer::count());
    }

    public function test_import_handles_276_machine_rows(): void
    {
        $rows = [];
        for ($i = 0; $i < 276; $i++) {
            $rows[] = ['Serial Number' => 'LEGACY-'.$i, 'Machine Name' => ''];
        }
        [$batch, $uploadQueries] = $this->measureQueries(fn () => $this->upload($rows));
        foreach (['ref_provinces', 'ref_regions', 'ref_industries', 'ref_product_types', 'service_customers', 'leads', 'service_customer_products', 'customer_import_rows'] as $table) {
            $this->assertLessThanOrEqual(1, $this->reads($uploadQueries, $table), 'Repeated upload reads: '.$table);
        }
        $this->assertLessThanOrEqual(30, count($uploadQueries));
        $payload = app(CustomerImports::class)->payload($batch);
        $this->assertSame(276, $payload['rows']->count());
        $this->assertSame(275, $payload['summary']['exact']);
        $this->assertSame(0, ServiceCustomer::count());
        $this->actingAs($this->user());
        [, $reviewQueries] = $this->measureQueries(fn () => $this->putJson('/api/aftersales/customers/imports/'.$batch->id.'/review', [
            'lock_version' => 0, 'rows' => [['id' => $payload['rows']->first()->id, 'input' => ['Note' => 'Correct one row']]],
        ])->assertOk());
        foreach (['ref_provinces', 'ref_regions', 'ref_industries', 'ref_product_types', 'service_customers', 'leads', 'service_customer_products'] as $table) {
            $this->assertLessThanOrEqual(1, $this->reads($reviewQueries, $table), 'Repeated review reads: '.$table);
        }
        $this->assertLessThanOrEqual(25, count($reviewQueries));
        [, $applyQueries] = $this->measureQueries(fn () => app(CustomerImports::class)->apply($batch, 1, 1));
        $this->assertLessThanOrEqual(1, $this->reads($applyQueries, 'service_customers'));
        $this->assertLessThanOrEqual(1, $this->reads($applyQueries, 'service_customer_products'));
        $this->assertLessThanOrEqual(300, count($applyQueries));
        $this->assertSame(0, $this->reads($applyQueries, 'ref_product_types'));
        $this->assertSame(1, ServiceCustomer::count());
        $this->assertSame(276, ServiceCustomerProduct::count());
    }

    public function test_indexed_matching_preserves_exact_possible_and_missing_phone_behavior(): void
    {
        $matching = app(CustomerMatching::class);
        $index = new \App\Services\Aftersales\CustomerMatchIndex;
        $records = [
            ['name' => 'Pak Rusman', 'phone' => '0811440149', 'ref_region_id' => 1],
            ['name' => 'Mrs Rusman', 'phone' => null, 'ref_region_id' => 2],
            ['name' => 'Different customer', 'phone' => '62811440149', 'ref_region_id' => 2],
            ['name' => 'Rusman', 'phone' => '6.28139E+24', 'ref_region_id' => 1],
            ['name' => 'Roy', 'phone' => '089999999999', 'ref_region_id' => 1],
        ];
        foreach ($records as $i => $record) {
            $index->add('customer', $i + 1, $record);
        }
        $probes = $records;
        foreach (['62811440149', '628114401490', '6281144014', '62811440148', '6281440149', '628999999999', null, '-', 'bad phone'] as $phone) {
            $probes[] = ['name' => 'Mr Rusman', 'phone' => $phone, 'ref_region_id' => 1];
            $probes[] = ['name' => 'Another name', 'phone' => $phone, 'ref_region_id' => 3];
        }
        foreach ($probes as $probe) {
            $expected = [];
            foreach ($records as $i => $record) {
                if ($status = $matching->classify($probe, $record)) {
                    $expected[$i + 1] = $status;
                }
            }
            $actual = collect($index->candidates($probe))->pluck('status', 'id')->all();
            $this->assertSame($expected, $actual);
        }
    }

    public function test_apply_rechecks_customers_created_since_preview(): void
    {
        $batch = $this->upload([[]]);
        ServiceCustomer::create(['name' => 'Pak Rusman', 'phone' => '0811440148', 'ref_region_id' => 1]);
        try {
            app(CustomerImports::class)->apply($batch, 0, 1);
            $this->fail('A new ambiguous match must block stale Apply.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
        $this->assertSame('review', $batch->fresh()->status);
        $this->assertSame(0, ServiceCustomerProduct::count());
    }

    public function test_apply_rechecks_serials_inserted_since_preview(): void
    {
        $batch = $this->upload([[]]);
        $other = ServiceCustomer::create(['name' => 'Another owner']);
        $other->products()->create(['machine_name' => 'Existing unit', 'serial_number' => ' s n - 0 ']);
        $result = app(CustomerImports::class)->apply($batch, 0, 1);
        $this->assertSame('already_exists', $result['rows']->first()->result['status']);
        $this->assertSame(1, ServiceCustomerProduct::count());
        $this->assertSame(1, ServiceCustomer::count());
    }

    public function test_partial_review_preserves_unaffected_decisions_and_batches_target_validation(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Pak Rusman', 'phone' => '62811440149', 'ref_region_id' => 1]);
        $batch = $this->upload(array_fill(0, 10, []));
        $rows = $batch->rows()->orderBy('row_number')->get();
        $decisions = $rows->map(fn ($row) => ['id' => $row->id, 'decision' => ['action' => 'link', 'customer_id' => $customer->id]])->all();
        $this->actingAs($this->user());
        [, $queries] = $this->measureQueries(fn () => $this->putJson('/api/aftersales/customers/imports/'.$batch->id.'/review', ['lock_version' => 0, 'rows' => $decisions])->assertOk());
        $this->assertSame(2, $this->reads($queries, 'service_customers'));
        $result = $this->putJson('/api/aftersales/customers/imports/'.$batch->id.'/review', [
            'lock_version' => 1, 'rows' => [['id' => $rows->first()->id, 'input' => ['Note' => 'Change note only']]],
        ])->assertOk()->json('data');
        $this->assertSame('link', $result['rows'][1]['decision']['action']);
        $this->assertSame(2, $result['import']['lock_version']);
    }

    public function test_serial_migration_backfills_legacy_duplicates_without_rewriting_original_values(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Legacy owner']);
        $one = $customer->products()->create(['machine_name' => 'Legacy one', 'serial_number' => ' a B c ']);
        $two = $customer->products()->create(['machine_name' => 'Legacy two', 'serial_number' => 'ABC']);
        $two->delete();
        $migration = require database_path('migrations/2026_10_23_000000_optimize_aftersales_matching.php');
        $migration->down();
        $migration->up();
        $this->assertSame('ABC', $one->fresh()->getRawOriginal('normalized_serial'));
        $this->assertSame(' a B c ', $one->fresh()->serial_number);
        $this->assertSame(2, ServiceCustomerProduct::withTrashed()->where('normalized_serial', 'ABC')->count());
    }

    public function test_serial_lookup_is_indexed_and_respects_soft_deleted_units(): void
    {
        $customer = ServiceCustomer::create(['name' => 'Serial owner']);
        $machine = $customer->products()->create(['machine_name' => 'Machine', 'serial_number' => ' a B c ']);
        $this->assertSame('ABC', $machine->getRawOriginal('normalized_serial'));
        $this->assertArrayNotHasKey('normalized_serial', $machine->toArray());
        $machine->delete();
        $this->actingAs($this->user());
        [, $queries] = $this->measureQueries(fn () => $this->postJson('/api/aftersales/customers/'.$customer->id.'/products', [
            'machine_name' => 'Duplicate', 'serial_number' => 'a b c',
        ])->assertUnprocessable()->assertJsonValidationErrors('serial_number'));
        $serialQueries = array_values(array_filter($queries, fn ($q) => str_contains($q['query'], 'normalized_serial')));
        $this->assertCount(1, $serialQueries);
        $this->assertStringContainsString('exists', strtolower($serialQueries[0]['query']));
        $this->assertStringContainsString('where', strtolower($serialQueries[0]['query']));
    }

    public function test_deal_conversion_runs_after_commit_and_never_for_rolled_back_lead(): void
    {
        DB::beginTransaction();
        Lead::create(['name' => 'Rolled back', 'phone' => '08123456789', 'status_id' => 5, 'deal_at' => now()]);
        $this->assertSame(0, CustomerDeal::count());
        DB::rollBack();
        $this->assertSame(0, ServiceCustomer::count());
        DB::beginTransaction();
        $lead = Lead::create(['name' => 'Committed', 'phone' => '08123456789', 'status_id' => 5, 'deal_at' => now()]);
        $this->assertSame(0, CustomerDeal::count());
        DB::commit();
        $this->assertSame(1, CustomerDeal::count());
        DB::transaction(function () use ($lead) {
            $lead->update(['status_id' => 1]);
            $lead->update(['name' => 'Changed before commit']);
        });
        $this->assertNotNull($lead->customerDeal->left_deal_at);
        $this->assertSame(1, ServiceCustomer::count());
    }

    public function test_deal_review_uses_consistent_lock_order_for_link_and_apply(): void
    {
        Schema::create('service_customer_phone_changes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id');
            $t->string('old_phone')->nullable();
            $t->string('new_phone')->nullable();
            $t->unsignedBigInteger('changed_by_user_id');
            $t->timestamp('changed_at');
            $t->timestamps();
        });
        $customer = ServiceCustomer::create(['name' => 'Pak Rusman', 'phone' => '0811440148', 'ref_region_id' => 1]);
        $lead = Lead::withoutEvents(fn () => Lead::create(['name' => 'Mr Rusman', 'phone' => '0811440149', 'status_id' => 5, 'deal_at' => now(), 'factory_city_id' => 1]));
        $link = app(DealConversion::class)->sync($lead);
        $this->assertSame('needs_review', $link->status);
        $this->actingAs($this->user());
        $url = '/api/aftersales/customers/deal-reviews/'.$link->id.'/resolve';
        $this->postJson($url, ['action' => 'link', 'customer_id' => $customer->id, 'updated_at' => $link->updated_at->toJSON()])
            ->assertOk()->assertJsonPath('data.service_customer_id', $customer->id);
        $lead->update(['phone' => '089999999999']);
        $link->refresh();
        $this->postJson($url, ['action' => 'apply', 'updated_at' => $link->updated_at->toJSON()])->assertOk();
        $this->assertSame('6289999999999', $customer->fresh()->phone);
        $this->assertNull($link->fresh()->pending_changes);
        $this->assertSame(1, DB::table('service_customer_phone_changes')->count());
    }

    public function test_identity_locks_overlap_for_possible_matches_but_not_unrelated_customers(): void
    {
        $keys = fn ($name, $phone) => \App\Services\Aftersales\CustomerMutationLocks::customerKeys(['name' => $name, 'phone' => $phone]);
        $a = $keys('Pak Rusman', '0811440149');
        $this->assertNotEmpty(array_intersect($a, $keys('Different name', '0811440148')));
        $this->assertNotEmpty(array_intersect($a, $keys('Another name', '08114401490')));
        $this->assertNotEmpty(array_intersect($a, $keys('Mr Rusman', null)));
        $this->assertEmpty(array_intersect($a, $keys('Completely unrelated', '089999999999')));
    }

    private function ticketAuxiliaryTables(bool $detail = false): void
    {
        $tables = ['ticket_recomings', 'ticket_service_forms', 'satisfaction_links'];
        if ($detail) {
            $tables = array_merge($tables, ['ticket_work_orders', 'ticket_parts', 'ticket_costs', 'ticket_photos', 'ticket_agreements']);
        }
        foreach ($tables as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('ticket_id');
                $t->timestamp('saved_at')->nullable();
                $t->softDeletes();
            });
        }
        if ($detail) {
            Schema::create('part_stock_movements', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('ticket_id');
                $t->string('type');
                $t->integer('qty');
                $t->softDeletes();
            });
            Schema::table('ticket_satisfactions', fn (Blueprint $t) => $t->unsignedBigInteger('ref_survey_template_id')->nullable());
        }
    }

    public function test_ticket_list_eager_loads_machine_codes_once_for_many_units(): void
    {
        $this->ticketAuxiliaryTables();
        $customer = ServiceCustomer::create(['name' => 'Customer']);
        $type = DB::table('ref_product_types')->where('code_prefix', 'TD')->value('id');
        for ($i = 0; $i < 10; $i++) {
            $machine = $customer->products()->create(['machine_name' => 'Machine '.$i, 'serial_number' => 'LIST-'.$i, 'capacity' => '5', 'ref_product_type_id' => $type]);
            Ticket::create(['ticket_code' => 'LIST-'.$i, 'customer_id' => $customer->id, 'customer_product_id' => $machine->id]);
        }
        $this->actingAs($this->user());
        [$response, $queries] = $this->measureQueries(fn () => $this->getJson('/api/aftersales/tickets?per_page=10')->assertOk());
        $this->assertCount(10, $response->json('data'));
        $this->assertSame('TD 5', $response->json('data.0.customer_product.machine_code'));
        $this->assertSame(1, $this->reads($queries, 'ref_product_types'));
        $this->assertArrayNotHasKey('satisfaction', $response->json('data.0'));
    }

    public function test_ticket_detail_fetches_survey_answers_once_for_history(): void
    {
        $this->ticketAuxiliaryTables(true);
        $ticket = Ticket::create(['ticket_code' => 'DETAIL']);
        for ($i = 0; $i < 10; $i++) {
            $response = TicketSatisfaction::create(['ticket_id' => $ticket->id, 'ref_survey_template_id' => 1]);
            DB::table('ticket_satisfaction_answers')->insert(['ticket_satisfaction_id' => $response->id, 'type' => 'rating', 'value_number' => 3]);
        }
        $this->actingAs($this->user());
        [$response, $queries] = $this->measureQueries(fn () => $this->getJson('/api/aftersales/tickets/'.$ticket->id)->assertOk());
        $this->assertCount(10, $response->json('data.survey_responses'));
        $this->assertEquals(3, $response->json('data.survey_responses.0.average_score'));
        $this->assertSame(1, $this->reads($queries, 'ticket_satisfaction_answers'));
    }

    public function test_frontend_maps_alias_uses_backend_storage_and_validation(): void
    {
        $this->actingAs($this->user());
        $created = $this->postJson('/api/aftersales/customers', [
            'name' => 'FE Customer', 'maps_link' => 'https://maps.app.goo.gl/frontend',
        ])->assertCreated()->json('data.data');
        $this->assertSame('https://maps.app.goo.gl/frontend', $created['address_maps_url']);
        $this->assertSame($created['address_maps_url'], $created['maps_link']);
        $customer = ServiceCustomer::findOrFail($created['id']);
        $this->assertSame($customer->address_maps_url, $customer->maps_link);
        $this->postJson('/api/aftersales/customers', [
            'name' => 'Invalid Maps', 'maps_link' => 'https://example.com/maps',
        ])->assertUnprocessable()->assertJsonValidationErrors('address_maps_url');
        $this->putJson('/api/aftersales/customers/'.$customer->id, [
            'maps_link' => 'https://maps.app.goo.gl/changed',
        ])->assertOk()->assertJsonPath('data.data.address_maps_url', 'https://maps.app.goo.gl/changed');
        $this->putJson('/api/aftersales/customers/'.$customer->id, [
            'maps_link' => 'https://maps.app.goo.gl/a', 'address_maps_url' => 'https://maps.app.goo.gl/b',
        ])->assertUnprocessable()->assertJsonValidationErrors('maps_link');
        DB::table('service_customers')->where('id', $customer->id)->update(['maps_link' => 'https://maps.app.goo.gl/old']);
        $this->putJson('/api/aftersales/customers/'.$customer->id, ['maps_link' => null])
            ->assertOk()->assertJsonPath('data.data.maps_link', null);
        $this->assertNull($customer->fresh()->address_maps_url);
    }

    public function test_maps_migration_preserves_both_existing_frontend_and_backend_values(): void
    {
        $legacy = ServiceCustomer::create(['name' => 'FE legacy']);
        $backend = ServiceCustomer::create(['name' => 'Backend customer', 'address_maps_url' => 'https://maps.app.goo.gl/backend']);
        DB::table('service_customers')->whereIn('id', [$legacy->id, $backend->id])->update(['maps_link' => 'https://maps.app.goo.gl/legacy']);
        (require database_path('migrations/2026_10_22_110000_reconcile_customer_maps_fields.php'))->up();
        $this->assertSame('https://maps.app.goo.gl/legacy', $legacy->fresh()->address_maps_url);
        $this->assertSame('https://maps.app.goo.gl/backend', $backend->fresh()->address_maps_url);
        $this->assertSame('https://maps.app.goo.gl/backend', $backend->fresh()->maps_link);
    }

    public function test_ticket_reminders_do_not_expose_survey_answers_to_technician(): void
    {
        foreach (['ticket_recomings', 'ticket_service_forms', 'satisfaction_links'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('ticket_id');
                $t->timestamp('saved_at')->nullable();
                $t->softDeletes();
            });
        }
        $user = Mockery::mock(User::class)->makePartial();
        $user->setRawAttributes(['id' => 1]);
        $user->shouldReceive('hasPermission')->andReturnUsing(fn ($code) => $code === 'aftersales.tickets.view-own');
        $this->actingAs($user);
        $ticket = Ticket::create(['ticket_code' => 'PRIVACY', 'assigned_technician_id' => 1]);
        TicketSatisfaction::create(['ticket_id' => $ticket->id, 'timeliness' => 1, 'score_original' => ['timeliness' => 2]]);
        $row = $this->getJson('/api/aftersales/tickets')->assertOk()->json('data.0');
        $this->assertSame('PRIVACY', $row['ticket_code']);
        $this->assertArrayHasKey('reminders', $row);
        $this->assertArrayNotHasKey('satisfactions', $row);
        $this->assertArrayNotHasKey('satisfaction', $row);
        $this->assertArrayNotHasKey('score_original', $row);
    }
}
