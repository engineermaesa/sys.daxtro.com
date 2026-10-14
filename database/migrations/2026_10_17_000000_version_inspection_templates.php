<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ref_inspection_templates', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('version')->unique();
            $t->string('status', 16)->index();
            $t->string('title');
            $t->json('groups');
            $t->string('diagnosis_section_title');
            $t->json('diagnosis_fields');
            $t->unsignedInteger('lock_version')->default(0);
            foreach (['created_by_user_id', 'updated_by_user_id', 'activated_by_user_id'] as $column) {
                $t->foreignId($column)->nullable()->constrained('users')->nullOnDelete();
            }
            $t->timestamp('activated_at')->nullable();
            $t->timestamps();
        });
        Schema::create('inspection_template_state', function (Blueprint $t) {
            $t->unsignedTinyInteger('id')->primary();
            $t->foreignId('active_template_id')->nullable()->constrained('ref_inspection_templates');
            $t->foreignId('draft_template_id')->nullable()->constrained('ref_inspection_templates');
        });
        DB::table('inspection_template_state')->insert(['id' => 1]);
        Schema::create('ref_inspection_groups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ref_inspection_template_id')->constrained('ref_inspection_templates');
            $t->string('group_key', 40);
            $t->string('code', 8);
            $t->string('title');
            $t->unsignedInteger('sort_order');
            $t->unique(['ref_inspection_template_id', 'group_key'], 'inspection_group_template_key');
        });
        Schema::table('ref_inspection_items', function (Blueprint $t) {
            $t->dropUnique(['number']);
            $t->unsignedInteger('number')->change();
            $t->string('group_code', 8)->change();
            $t->text('standard')->nullable()->change();
            $t->foreignId('ref_inspection_template_id')->nullable()->constrained('ref_inspection_templates');
            $t->foreignId('ref_inspection_group_id')->nullable()->constrained('ref_inspection_groups');
            $t->string('item_key', 40)->nullable();
            $t->json('machine_types')->nullable();
            $t->boolean('types_restricted')->default(false);
            $t->unique(['ref_inspection_template_id', 'number'], 'inspection_template_number');
        });
        Schema::table('ref_inspection_fields', function (Blueprint $t) {
            $t->string('field_key', 40)->nullable();
            $t->json('units')->nullable();
            $t->boolean('is_multiple')->default(false);
            $t->decimal('min_value', 16, 5)->nullable();
            $t->decimal('max_value', 16, 5)->nullable();
        });
        Schema::table('ticket_service_forms', function (Blueprint $t) {
            $t->foreignId('ref_inspection_template_id')->nullable()->constrained('ref_inspection_templates');
            $t->unsignedInteger('template_version')->nullable();
        });
        Schema::table('ticket_inspection_values', fn (Blueprint $t) => $t->string('unit', 20)->nullable());
        Schema::create('inspection_template_audits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ref_inspection_template_id')->constrained('ref_inspection_templates');
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('actor_name');
            $t->string('action');
            $t->json('changes')->nullable();
            $t->timestamp('created_at');
        });
        if (DB::table('ref_inspection_items')->exists()) {
            app(\App\Services\Aftersales\InspectionTemplates::class)->importLegacy();
        }
    }

    public function down(): void
    {
        if (DB::table('ref_inspection_templates')->where('version', '>', 1)->exists()) {
            throw new RuntimeException('Inspection template versions exist; rollback would invalidate document history.');
        }
        Schema::dropIfExists('inspection_template_audits');
        Schema::dropIfExists('inspection_template_state');
        Schema::table('ticket_inspection_values', fn (Blueprint $t) => $t->dropColumn('unit'));
        Schema::table('ticket_service_forms', function (Blueprint $t) {
            $t->dropConstrainedForeignId('ref_inspection_template_id');
            $t->dropColumn('template_version');
        });
        Schema::table('ref_inspection_fields', fn (Blueprint $t) => $t->dropColumn(['field_key', 'units', 'is_multiple', 'min_value', 'max_value']));
        Schema::table('ref_inspection_items', function (Blueprint $t) {
            $t->dropUnique('inspection_template_number');
            $t->dropConstrainedForeignId('ref_inspection_template_id');
            $t->dropConstrainedForeignId('ref_inspection_group_id');
            $t->dropColumn(['item_key', 'machine_types', 'types_restricted']);
            $t->unique('number');
        });
        Schema::dropIfExists('ref_inspection_groups');
        Schema::dropIfExists('ref_inspection_templates');
    }
};
