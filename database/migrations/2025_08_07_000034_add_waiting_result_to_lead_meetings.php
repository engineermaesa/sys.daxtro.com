<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lead_meetings', function (Blueprint $table) {
            $table->enum('result', ['yes', 'no', 'expired', 'waiting'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lead_meetings', function (Blueprint $table) {
            $table->enum('result', ['yes', 'no', 'expired'])->nullable()->change();
        });
    }
};
