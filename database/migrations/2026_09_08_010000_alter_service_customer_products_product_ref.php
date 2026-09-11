<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_customer_products', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('customer_id')->constrained('ref_products')->nullOnDelete();
            $table->foreignId('ref_product_type_id')->nullable()->after('serial_number')->constrained('ref_product_types')->nullOnDelete();
        });

        Schema::table('service_customer_products', function (Blueprint $table) {
            $table->dropColumn(['machine_name', 'model']);
        });
    }

    public function down(): void
    {
        Schema::table('service_customer_products', function (Blueprint $table) {
            $table->string('machine_name')->after('customer_id');
            $table->string('model')->nullable()->after('serial_number');
        });

        Schema::table('service_customer_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ref_product_type_id');
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
