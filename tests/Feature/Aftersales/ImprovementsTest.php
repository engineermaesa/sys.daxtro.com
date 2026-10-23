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
    }

    private function file(array $rows): UploadedFile
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Import Customer');
        $sheet->fromArray(CustomerImports::HEADERS, null, 'A1');
        foreach ($rows as $i => $row) {
            $input = array_replace(['Customer Name*' => 'Mr Rusman', 'Phone' => '0811440149', 'Province' => 'Jawa Timur', 'City / Region' => 'Ponorogo', 'Machine Category*' => 'Tube', 'Machine Name*' => 'TD 50', 'Serial Number' => 'SN-'.$i, 'Warranty Start' => '27-Des-25'], $row);
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
        $this->assertSame(0, ServiceCustomer::count());
        $this->assertSame(0, ServiceCustomerProduct::count());
        $this->assertSame(['new', 'exact', 'exact'], $batch->rows->pluck('review.status')->all());
        app(CustomerImports::class)->apply($batch, 0, 1);
        $this->assertSame(1, ServiceCustomer::count());
        $this->assertSame(3, ServiceCustomerProduct::count());
        $machine = ServiceCustomerProduct::first();
        $this->assertSame('2025-12-27', $machine->warranty_start->toDateString());
        $this->assertSame('TD 50', $machine->machine_code);
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
            $rows[] = ['Serial Number' => 'LEGACY-'.$i];
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
