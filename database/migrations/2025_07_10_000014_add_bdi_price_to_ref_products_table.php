<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('ref_products', function (Blueprint $table) {
            $table->double('bdi_price', 15)->nullable()->after('fob_price');
        });
    }

    public function down(): void
    {
        Schema::table('ref_products', function (Blueprint $table) {
            $table->dropColumn('bdi_price');
        });
    }
};
