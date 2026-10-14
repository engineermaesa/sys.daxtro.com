<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_service_forms')) {
            return;
        }

        Schema::create('ref_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->char('group_code', 1);
            $table->unsignedTinyInteger('number')->unique();
            $table->string('name');
            $table->string('standard')->nullable();
            $table->json('only_for_types')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ref_inspection_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('ref_inspection_items')->cascadeOnDelete();
            $table->string('key', 40);
            $table->string('label');
            $table->enum('type', ['number', 'checklist', 'text']);
            $table->string('unit', 20)->nullable();
            $table->json('options')->nullable();
            $table->string('required_when')->nullable(); 
            $table->unsignedTinyInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['item_id', 'key']);
        });

        Schema::create('ticket_service_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained('tickets')->cascadeOnDelete();
            $table->enum('status', ['draft', 'saved'])->default('draft');
            $table->timestamp('first_draft_at')->nullable();
            $table->timestamp('saved_at')->nullable();
            $table->foreignId('saved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('unlocked_at')->nullable();
            $table->foreignId('unlocked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ticket_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_service_form_id')->constrained('ticket_service_forms')->cascadeOnDelete();
            $table->foreignId('ref_inspection_item_id')->constrained('ref_inspection_items');
            $table->enum('status', ['ok', 'rep', 'na'])->nullable();
            $table->timestamps();
            $table->unique(['ticket_service_form_id', 'ref_inspection_item_id'], 'ticket_inspections_form_item_unique');
        });

        Schema::create('ticket_inspection_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_inspection_id')->constrained('ticket_inspections')->cascadeOnDelete();
            $table->foreignId('ref_inspection_field_id')->constrained('ref_inspection_fields');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['ticket_inspection_id', 'ref_inspection_field_id'], 'ticket_inspection_values_unique');
        });

        Schema::create('ref_agreement_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('version')->unique();
            $table->string('title');
            $table->json('sections'); 
            $table->text('closing_statement')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ticket_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('ref_agreement_template_id')->constrained('ref_agreement_templates');
            $table->unsignedInteger('template_version');
            $table->json('accepted_clauses')->nullable();
            $table->string('customer_name')->nullable();
            $table->enum('status', ['draft', 'saved'])->default('draft');
            $table->timestamp('saved_at')->nullable();
            $table->foreignId('saved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('unlocked_at')->nullable();
            $table->foreignId('unlocked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ticket_signatures', function (Blueprint $table) {
            $table->id();
            $table->morphs('signable');
            $table->enum('role', ['technician', 'supervisor', 'customer']);
            $table->string('signer_name');
            $table->foreignId('signed_by_user_id')->nullable()->constrained('users')->nullOnDelete(); // null for customer
            $table->foreignId('captured_by_user_id')->nullable()->constrained('users')->nullOnDelete(); // technician's device
            $table->string('file_path');
            $table->timestamp('signed_at');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->unique(['signable_type', 'signable_id', 'role'], 'ticket_signatures_unique_role');
        });

        Schema::create('ticket_document_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->enum('document_type', ['agreement', 'service_form']);
            $table->enum('recipient_role', ['customer', 'aftersales', 'technician']);
            $table->string('recipient_email')->nullable();
            $table->string('copy_label', 40);
            $table->enum('status', ['queued', 'sent', 'failed', 'skipped'])->default('queued');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_photo_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('ticket_inspection_value_id')->constrained('ticket_inspection_values')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE ticket_photos MODIFY category ENUM('problem','analysis','repair','inspection') NOT NULL");
        Schema::table('ticket_photos', function (Blueprint $table) {
            $table->foreignId('ticket_inspection_value_id')->nullable()->after('ticket_id')->constrained('ticket_inspection_values')->nullOnDelete();
            $table->timestamp('captured_at')->nullable()->after('file_name');
            $table->string('thumbnail_path')->nullable()->after('captured_at');
        });

        Schema::table('service_customer_products', function (Blueprint $table) {
            $table->string('capacity')->nullable();
            $table->string('refrigerant')->nullable();
            $table->string('condenser_type')->nullable();
            $table->string('voltage')->nullable();
            $table->string('phase')->nullable();
            $table->string('hz')->nullable();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('service_type', ['preventive', 'repair'])->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', fn (Blueprint $t) => $t->dropColumn('service_type'));
        Schema::table('service_customer_products', fn (Blueprint $t) => $t->dropColumn(['capacity', 'refrigerant', 'condenser_type', 'voltage', 'phase', 'hz']));
        Schema::table('ticket_photos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_inspection_value_id');
            $table->dropColumn(['captured_at', 'thumbnail_path']);
        });
        DB::statement("DELETE FROM ticket_photos WHERE category = 'inspection'");
        DB::statement("ALTER TABLE ticket_photos MODIFY category ENUM('problem','analysis','repair') NOT NULL");
        Schema::dropIfExists('ticket_photo_tokens');
        Schema::dropIfExists('ticket_document_dispatches');
        Schema::dropIfExists('ticket_signatures');
        Schema::dropIfExists('ticket_agreements');
        Schema::dropIfExists('ref_agreement_templates');
        Schema::dropIfExists('ticket_inspection_values');
        Schema::dropIfExists('ticket_inspections');
        Schema::dropIfExists('ticket_service_forms');
        Schema::dropIfExists('ref_inspection_fields');
        Schema::dropIfExists('ref_inspection_items');
    }
};
