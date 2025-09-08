<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_confirmations', function (Blueprint $table) {
            $table->string('payer_name', 100)->nullable()->change();
            $table->string('payer_bank', 100)->nullable()->change();
            $table->string('payer_account_number', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_confirmations', function (Blueprint $table) {
            $table->string('payer_name', 100)->nullable(false)->change();
            $table->string('payer_bank', 100)->nullable(false)->change();
            $table->string('payer_account_number', 100)->nullable(false)->change();
        });
    }
};
