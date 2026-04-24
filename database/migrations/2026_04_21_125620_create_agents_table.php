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
        Schema::create('ref_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('ref_branches');
            $table->foreignId('jabatan_id')->constrained('ref_jabatans');
            $table->foreignId('source_id')->constrained('lead_sources');
            $table->foreignId('customer_type_id')->constrained('ref_customer_types');
            $table->foreignId('region_id')->constrained('ref_regions');
            $table->string('province');
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('company_name');
            $table->text('company_address');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_agents');
    }
};
