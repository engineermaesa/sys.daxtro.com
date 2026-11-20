<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait BuildsQuotationSubmissionSchema
{
    private function buildQuotationSubmissionSchema(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('user_roles');
            $table->unsignedBigInteger('branch_id')->default(1);
            foreach (['name', 'email', 'nip', 'password', 'remember_token'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        foreach (['lead_sources', 'lead_statuses'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });
        }
        Schema::create('ref_products', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('lead_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('lead_sources');
            $table->foreignId('status_id')->constrained('lead_statuses');
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->unsignedBigInteger('segment_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('lead_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads');
            $table->foreignId('sales_id')->constrained('users');
            $table->timestamp('claimed_at');
            $table->timestamp('released_at')->nullable();
            $table->text('trash_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads');
            $table->foreignId('created_by')->constrained('users');
            $table->string('quotation_no')->nullable();
            $table->string('status')->default('draft');
            foreach (['subtotal', 'tax_pct', 'tax_total', 'total_discount', 'grand_total', 'booking_fee'] as $column) {
                $table->double($column)->nullable();
            }
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('proformas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations');
            $table->integer('term_no')->nullable();
            $table->string('proforma_type')->nullable();
            $table->string('proforma_no')->nullable();
            $table->double('amount')->nullable();
            $table->string('status')->default('confirmed');
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->unsignedBigInteger('attachment_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('payment_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_id')->constrained('proformas');
            $table->double('amount');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations');
            $table->foreignId('product_id')->nullable()->constrained('ref_products');
            $table->double('qty');
            $table->text('description')->nullable();
            $table->double('unit_price')->nullable();
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->double('line_total')->nullable();
            $table->boolean('is_visible_pdf')->nullable();
            $table->unsignedBigInteger('merge_into_item_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('quotation_payment_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations');
            $table->integer('term_no');
            $table->double('percentage');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('quotation_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations');
            $table->string('role')->nullable();
            $table->string('decision')->nullable();
            $table->unsignedBigInteger('reviewer_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->double('incentive_nominal')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        foreach ([
            '2026_08_20_100000_create_temp_quotations_table.php',
            '2026_08_20_100001_create_temp_quotation_items_table.php',
            '2026_08_20_100002_create_temp_quotation_payment_terms_table.php',
            '2026_11_20_000000_add_revision_fields_to_quotations.php',
            '2026_09_03_010500_create_document_counters_table.php',
            '2026_11_20_000200_add_quotation_document_counter.php',
            '2026_11_20_000300_add_bm_approved_quotation_status.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }

        Schema::create('quotation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations');
            $table->foreignId('temp_quotation_id')->nullable()->constrained('temp_quotations');
            $table->foreignId('user_id')->constrained('users');
            $table->string('action');
            $table->timestamp('logged_at');
            $table->timestamps();
            $table->softDeletes();
        });
        (require database_path('migrations/2026_11_20_000400_add_revision_metadata_to_quotation_logs.php'))->up();
        (require database_path('migrations/2026_11_20_000500_add_quotation_draft_lock_version.php'))->up();
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            foreach (['type', 'file_path', 'mime_type'] as $column) {
                $table->string($column);
            }
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('user_balance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('quotation_id')->constrained('quotations');
            $table->double('amount');
            $table->string('description')->nullable();
            $table->string('status');
            $table->timestamp('created_at')->nullable();
        });
    }
}
