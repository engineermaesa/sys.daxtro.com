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
        Schema::create('temp_quotation_payment_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('temp_quotation_id')->constrained('temp_quotations')->onDelete('cascade');
            $table->tinyInteger('term_no');
            $table->double('percentage');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_quotation_payment_terms');
    }
};
