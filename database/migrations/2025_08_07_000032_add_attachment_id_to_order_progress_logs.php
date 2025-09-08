<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_progress_logs', function (Blueprint $table) {
            $table->foreignId('attachment_id')->nullable()->after('note')->constrained('attachments')->restrictOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_progress_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attachment_id');
        });
    }
};
