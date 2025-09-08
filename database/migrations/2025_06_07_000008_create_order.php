<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {                
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->restrictOnUpdate()->restrictOnDelete();                        
            $table->string('order_no', 100)->nullable();
            $table->double('total_billing')->nullable();
            $table->enum('order_status', ['publish','in_progress','delivered','confirmed','done','canceled'])->nullable();            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('ref_products')->restrictOnUpdate()->restrictOnDelete();
            $table->text('description')->nullable();
            $table->double('qty')->nullable();
            $table->double('unit_price')->nullable();
            $table->double('discount_pct')->nullable();
            $table->double('tax_pct')->nullable();
            $table->double('line_total')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_progress_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnUpdate()->restrictOnDelete();
            $table->enum('progress_step', ['1','2','3','4','5','6','7','8'])->nullable();            
            $table->text('note')->nullable();
            $table->timestamp('logged_at')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnUpdate()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_payment_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnUpdate()->restrictOnDelete();
            $table->tinyInteger('term_no');
            $table->double('percentage');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        $tables = [
            'order_payment_terms',
            'order_progress_logs',
            'order_items',
            'orders',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
