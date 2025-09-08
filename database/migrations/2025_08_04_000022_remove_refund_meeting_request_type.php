<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Delete existing refund-meeting finance requests
        DB::table('finance_requests')->where('request_type', 'refund-meeting')->delete();

        // Modify the enum to drop refund-meeting option
        Schema::table('finance_requests', function (Blueprint $table) {
            $table->enum('request_type', ['meeting-expense','proforma','invoice','payment-confirmation'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('finance_requests', function (Blueprint $table) {
            $table->enum('request_type', ['meeting-expense','proforma','invoice','refund-meeting','payment-confirmation'])->nullable()->change();
        });
    }
};
