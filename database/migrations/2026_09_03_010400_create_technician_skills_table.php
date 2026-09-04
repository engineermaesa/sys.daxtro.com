<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_profile_id')->constrained('technician_profiles')->cascadeOnDelete();
            $table->string('skill_name');
            $table->enum('level', ['basic', 'intermediate', 'expert']);
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_deleted')->default(false);

            $table->unique(['technician_profile_id', 'skill_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_skills');
    }
};
