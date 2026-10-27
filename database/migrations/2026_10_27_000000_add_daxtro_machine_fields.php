<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_customer_products', function (Blueprint $table) {
            $table->boolean('is_daxtro')->default(true);
            $table->string('compressor')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('service_customer_products', function (Blueprint $table) {
            $table->dropColumn(['is_daxtro', 'compressor']);
        });
    }
};
