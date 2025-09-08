<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ref_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ref_banks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ref_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('ref_companies')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('bank_id')->constrained('ref_banks')->restrictOnUpdate()->restrictOnDelete();
            $table->string('account_number', 50)->nullable();            
            $table->string('holder_name', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ref_product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable();
            $table->string('code', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ref_products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 255)->nullable();
            $table->string('name', 100)->nullable();
            $table->text('description')->nullable();
            $table->integer('vat')->nullable();
            $table->double('corporate_price', 15)->nullable();
            $table->double('government_price', 15)->nullable();
            $table->double('personal_price', 15)->nullable();
            $table->boolean('warranty_available')->default(false);
            $table->integer('warranty_time_month')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ref_parts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->double('price', 15);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ref_product_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('ref_products')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('part_id')->constrained('ref_parts')->restrictOnUpdate()->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('ref_product_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('ref_products')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('category_id')->constrained('ref_product_categories')->restrictOnUpdate()->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('ref_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('ref_companies')->restrictOnUpdate()->restrictOnDelete();
            $table->string('name', 100)->nullable();
            $table->string('code', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ref_regionals', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ref_provinces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regional_id')->constrained('ref_regionals')->restrictOnUpdate()->restrictOnDelete();
            $table->string('name', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ref_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regional_id')->constrained('ref_regionals')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('province_id')->constrained('ref_provinces')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('ref_branches')->restrictOnUpdate()->restrictOnDelete();
            $table->string('name', 100)->nullable();
            $table->string('code', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ref_expense_types', function (Blueprint $table) {
            $table->id();            
            $table->string('name', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        $tables = [
            'ref_expense_types',
            'ref_regions',
            'ref_provinces',
            'ref_regionals',
            'ref_branches',
            'ref_product_parts',
            'ref_product_category',
            'ref_parts',
            'ref_products',
            'ref_product_categories',
            'ref_accounts',
            'ref_banks',
            'ref_companies',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
