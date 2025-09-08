<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('activity_id')->constrained('lead_activity_lists')->restrictOnUpdate()->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('logged_at')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnUpdate()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activity_logs');
    }
};
