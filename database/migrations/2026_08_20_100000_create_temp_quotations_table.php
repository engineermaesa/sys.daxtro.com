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
        Schema::create('temp_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads');
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->double('subtotal')->nullable();
            $table->double('tax_pct')->default(11);
            $table->double('tax_total')->nullable();
            $table->double('total_discount')->nullable();
            $table->double('grand_total')->nullable();
            $table->double('booking_fee')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_quotations');
    }
};
