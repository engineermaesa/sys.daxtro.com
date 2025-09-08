<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {                
        Schema::create('proformas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->restrictOnUpdate()->restrictOnDelete();
            $table->tinyInteger('term_no')->nullable();
            $table->enum('proforma_type', ['booking_fee', 'down_payment', 'term_payment'])->nullable();
            $table->string('proforma_no', 100)->nullable();
            $table->double('amount')->nullable();
            $table->enum('status', ['pending', 'confirmed'])->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->restrictOnUpdate()->restrictOnDelete();                        
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('attachment_id')->nullable()->constrained('attachments')->restrictOnUpdate()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payment_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_id')->constrained('proformas')->restrictOnUpdate()->restrictOnDelete();
            $table->string('payer_name', 100)->nullable();
            $table->string('payer_bank', 100)->nullable();
            $table->string('payer_account_number', 100)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->double('amount')->nullable();
            $table->foreignId('attachment_id')->constrained('attachments')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnUpdate()->restrictOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        $tables = [
            'payment_confirmations',
            'proformas',          
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
