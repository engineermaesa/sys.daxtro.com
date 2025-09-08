<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {                
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_id')->constrained('proformas')->restrictOnUpdate()->restrictOnDelete();
            $table->string('invoice_no', 100)->nullable();
            $table->enum('invoice_type', ['booking_fee','down_payment','final'])->nullable();            
            $table->double('amount')->nullable();
            $table->date('due_date', 100)->nullable();
            $table->enum('status', ['open','paid','partial','void'])->nullable();            
            $table->foreignId('attachment_id')->nullable()->constrained('attachments')->restrictOnUpdate()->restrictOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnUpdate()->restrictOnDelete();
            $table->text('description')->nullable();
            $table->double('amount')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnUpdate()->restrictOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->double('amount')->nullable();
            $table->foreignId('attachment_id')->constrained('attachments')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('confirmed_by')->constrained('users')->restrictOnUpdate()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        $tables = [
            'invoice_payments',
            'invoice_items',
            'invoices',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
