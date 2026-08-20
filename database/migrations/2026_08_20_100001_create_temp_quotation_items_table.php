<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('temp_quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('temp_quotation_id')->constrained('temp_quotations')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('ref_products');
            $table->double('qty')->default(1);
            $table->text('description')->nullable();
            $table->double('unit_price')->nullable();
            $table->decimal('discount_pct', 5, 2)->default(0.00);
            $table->double('line_total')->nullable();
            $table->boolean('is_visible_pdf')->nullable();
            $table->unsignedBigInteger('merge_into_item_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_quotation_items');
    }
};
