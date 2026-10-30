<?php

namespace Tests\Feature\Aftersales;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DiagnosisCyclesMigrationTest extends TestCase
{
    private function migration()
    {
        return require database_path('migrations/2026_10_30_000300_scope_diagnosis_to_ticket_cycles.php');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', fn (Blueprint $t) => $t->id());
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->softDeletes();
        });
        Schema::create('ticket_service_forms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->softDeletes();
        });
        Schema::create('ticket_cycles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ticket_id');
            $t->unsignedInteger('cycle_no');
        });
        Schema::create('ticket_diagnosis_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_service_form_id')->constrained('ticket_service_forms');
            $t->string('field');
            $t->unsignedInteger('visit_no');
            $t->text('body');
            $t->unique(['ticket_service_form_id', 'field', 'visit_no'], 'diagnosis_form_field_visit');
        });
        DB::table('tickets')->insert(['id' => 1]);
        DB::table('ticket_service_forms')->insert(['id' => 1, 'ticket_id' => 1]);
        DB::table('ticket_cycles')->insert(['id' => 1, 'ticket_id' => 1, 'cycle_no' => 1]);
        foreach (['problem', 'cause', 'action'] as $field) {
            DB::table('ticket_diagnosis_entries')->insert(['ticket_service_form_id' => 1, 'field' => $field, 'visit_no' => 1, 'body' => 'Existing '.$field]);
        }
    }

    public function test_backfill_preserves_text_keys_and_deleted_ticket_history(): void
    {
        DB::table('tickets')->update(['deleted_at' => now()]);
        DB::table('ticket_service_forms')->update(['deleted_at' => now()]);
        $this->migration()->up();
        $this->assertSame(3, DB::table('ticket_diagnosis_entries')->where('cycle_id', 1)->count());
        $this->assertDatabaseHas('ticket_diagnosis_entries', ['field' => 'cause', 'body' => 'Existing cause']);
        $this->assertNull(DB::table('ticket_cycles')->value('service_form_first_edited_at'));
        $this->assertFalse(Schema::hasTable('recoming_findings'));
    }

    public function test_multiple_cycles_or_duplicate_field_groups_are_not_guessed(): void
    {
        DB::table('ticket_cycles')->insert(['id' => 2, 'ticket_id' => 1, 'cycle_no' => 2]);
        DB::table('tickets')->insert(['id' => 2]);
        DB::table('ticket_cycles')->insert(['id' => 3, 'ticket_id' => 2, 'cycle_no' => 1]);
        DB::table('ticket_service_forms')->insert(['id' => 2, 'ticket_id' => 2]);
        foreach ([1, 2] as $visit) {
            DB::table('ticket_diagnosis_entries')->insert(['ticket_service_form_id' => 2, 'field' => 'cause', 'visit_no' => $visit, 'body' => 'Ambiguous '.$visit]);
        }
        $this->migration()->up();
        $this->assertSame(5, DB::table('ticket_diagnosis_entries')->whereNull('cycle_id')->count());
    }

    public function test_unique_current_field_is_enforced_independently_of_legacy_visit_number(): void
    {
        $this->migration()->up();
        $this->expectException(QueryException::class);
        DB::table('ticket_diagnosis_entries')->insert(['ticket_service_form_id' => 1, 'cycle_id' => 1, 'field' => 'cause', 'visit_no' => 99, 'body' => 'Duplicate current field']);
    }

    public function test_roundtrip_preserves_legacy_diagnosis_rows(): void
    {
        $migration = $this->migration();
        $migration->up();
        $migration->down();
        $this->assertFalse(Schema::hasColumn('ticket_diagnosis_entries', 'cycle_id'));
        $this->assertDatabaseCount('ticket_diagnosis_entries', 3);
        $migration->up();
        $this->assertSame(3, DB::table('ticket_diagnosis_entries')->where('cycle_id', 1)->count());
        $this->assertDatabaseHas('ticket_diagnosis_entries', ['field' => 'cause', 'body' => 'Existing cause']);
    }

    public function test_rollback_protects_cycle_specific_recoming_findings(): void
    {
        $migration = $this->migration();
        $migration->up();
        DB::table('ticket_cycles')->insert(['id' => 2, 'ticket_id' => 1, 'cycle_no' => 2]);
        DB::table('ticket_diagnosis_entries')->insert(['ticket_service_form_id' => 1, 'cycle_id' => 2, 'field' => 'cause', 'visit_no' => 2, 'body' => 'Recoming finding']);
        try {
            $migration->down();
            $this->fail('Cycle ownership must not be discarded.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Cycle-specific Recoming findings exist', $e->getMessage());
        }
        $this->assertTrue(Schema::hasColumn('ticket_diagnosis_entries', 'cycle_id'));
        $this->assertDatabaseHas('ticket_diagnosis_entries', ['cycle_id' => 2, 'body' => 'Recoming finding']);
    }
}
