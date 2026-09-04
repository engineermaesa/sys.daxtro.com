<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code')->unique();
            $table->foreignId('customer_id')->constrained('service_customers');
            $table->foreignId('customer_product_id')->constrained('service_customer_products');
            $table->enum('category', ['electrical', 'mechanical', 'refrigeration_system', 'production']);
            $table->enum('priority', ['low', 'medium', 'high']);
            $table->text('description');
            $table->unsignedInteger('sla_value');
            $table->enum('sla_unit', ['hour', 'day']);
            $table->timestamp('sla_due_at')->nullable();
            $table->foreignId('assigned_technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
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
        Schema::dropIfExists('tickets');
    }
};
