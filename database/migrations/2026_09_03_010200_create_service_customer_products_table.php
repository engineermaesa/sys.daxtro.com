<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_customer_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('service_customers')->cascadeOnDelete();
            $table->string('machine_name');
            $table->string('serial_number')->nullable()->index();
            $table->string('model')->nullable();
            $table->foreignId('industry_id')->nullable()->constrained('ref_industries')->nullOnDelete();
            $table->string('warranty_period')->nullable();
            $table->date('warranty_start')->nullable();
            $table->date('warranty_end')->nullable();
            $table->enum('pm_contract_status', ['none', 'offered', 'active', 'expired'])->default('none');
            $table->string('pm_frequency')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_deleted')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_customer_products');
    }
};
