<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {        
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->restrictOnUpdate()->restrictOnDelete();                        
            $table->string('quotation_no', 255)->nullable();
            $table->enum('status', ['draft', 'review', 'published',` 'rejected', 'expired'])->nullable();
            $table->double('subtotal')->nullable();
            $table->double('tax_pct')->default(11);
            $table->double('tax_total')->nullable();
            $table->double('grand_total')->nullable();
            $table->double('booking_fee')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnUpdate()->restrictOnDelete();                        
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->restrictOnUpdate()->restrictOnDelete();                        
            $table->foreignId('product_id')->nullable()->constrained('ref_products')->restrictOnUpdate()->restrictOnDelete();                        
            $table->double('qty')->default(1);
            $table->text('description')->nullable();
            $table->double('unit_price')->nullable();
            $table->double('discount_pct')->nullable();
            $table->double('line_total')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnUpdate()->restrictOnDelete();
            $table->enum('role', ['BM'])->nullable();
            $table->enum('decision', ['approve', 'reject'])->nullable();
            $table->text('notes')->nullable();
            $table->decimal('incentive_nominal')->default(0);
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_payment_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->restrictOnUpdate()->restrictOnDelete();
            $table->tinyInteger('term_no');
            $table->double('percentage');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnUpdate()->restrictOnDelete();
            $table->double('total_balance')->default(0);
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('user_balance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnUpdate()->restrictOnDelete();
            $table->double('amount');            
            $table->foreignId('quotation_id')->constrained('quotations')->restrictOnUpdate()->restrictOnDelete();
            $table->string('description')->nullable();
            $table->enum('status', ['pending', 'received', 'expired']);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        $tables = [
            'user_balance_logs',
            'user_balances',
            'quotation_payment_terms',
            'quotation_reviews',
            'quotation_items',
            'quotations',       
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
