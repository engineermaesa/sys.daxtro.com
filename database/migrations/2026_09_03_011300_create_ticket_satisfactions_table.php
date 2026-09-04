<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_satisfactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained('tickets')->cascadeOnDelete();
            $table->unsignedTinyInteger('timeliness');
            $table->unsignedTinyInteger('technician_attitude');
            $table->unsignedTinyInteger('technical_knowledge');
            $table->unsignedTinyInteger('work_neatness');
            $table->unsignedTinyInteger('solution_quality');
            $table->foreignId('filled_by_user_id')->constrained('users');
            $table->timestamp('filled_at');
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
        Schema::dropIfExists('ticket_satisfactions');
    }
};
