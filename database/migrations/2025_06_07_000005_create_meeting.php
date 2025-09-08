<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('meeting_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('lead_meetings')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('sales_id')->constrained('users')->restrictOnUpdate()->restrictOnDelete();
            $table->double('amount', 15);
            $table->enum('status', ['draft','submitted','approved','rejected','canceled'])->default('draft');
            $table->timestamp('requested_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('meeting_expense_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_expense_id')->constrained('meeting_expenses')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('expense_type_id')->constrained('ref_expense_types')->restrictOnUpdate()->restrictOnDelete();
            $table->double('amount', 15);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_expense_details');
        Schema::dropIfExists('meeting_expenses');
    }
};
