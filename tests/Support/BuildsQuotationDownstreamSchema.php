<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait BuildsQuotationDownstreamSchema
{
    use BuildsQuotationSubmissionSchema;

    private function buildQuotationDownstreamSchema(): void
    {
        $this->buildQuotationSubmissionSchema();
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('first_sales_id')->nullable();
            $table->timestamp('deal_at')->nullable();
        });
        Schema::table('payment_confirmations', function (Blueprint $table) {
            foreach (['payer_name', 'payer_bank', 'payer_account_number'] as $column) $table->string($column)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('attachment_id')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
        });
        Schema::create('quotation_signed_documents', function (Blueprint $table) {
            $table->id();
            foreach (['quotation_id', 'attachment_id', 'uploader_id'] as $column) $table->unsignedBigInteger($column);
            $table->text('description')->nullable();
            $table->date('signed_date');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('lead_status_logs', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('lead_id'); $table->unsignedBigInteger('status_id');
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('finance_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_type');
            $table->unsignedBigInteger('reference_id');
            $table->unsignedBigInteger('requester_id');
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->string('status');
            $table->timestamp('decided_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('proforma_id'); $table->unsignedBigInteger('attachment_id')->nullable();
            $table->string('invoice_no'); $table->string('invoice_type'); $table->string('status'); $table->double('amount');
            $table->date('due_date')->nullable(); $table->timestamp('issued_at')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('quotation_id');
            $table->unsignedBigInteger('proforma_id')->nullable(); $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('type'); $table->unsignedBigInteger('user_id'); $table->timestamp('logged_at');
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('lead_id'); $table->string('order_no');
            $table->double('total_billing'); $table->string('order_status');
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('order_id'); $table->unsignedBigInteger('product_id')->nullable();
            $table->text('description')->nullable();
            foreach (['qty', 'unit_price', 'discount_pct', 'tax_pct', 'total_discount', 'line_total'] as $column) $table->double($column)->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('order_payment_terms', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('order_id'); $table->integer('term_no'); $table->double('percentage');
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('order_progress_logs', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('order_id'); $table->integer('progress_step');
            $table->text('note')->nullable(); $table->timestamp('logged_at'); $table->unsignedBigInteger('user_id');
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('user_balances', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->double('total_balance')->default(0);
        });
    }
}
