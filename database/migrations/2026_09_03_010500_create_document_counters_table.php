<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pure numbering infrastructure (ticket_code / wo_number) — no soft-delete /
        // actor audit columns since rows here are never a business record to review.
        Schema::create('document_counters', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['ticket', 'work_order']);
            $table->date('date');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_counters');
    }
};
