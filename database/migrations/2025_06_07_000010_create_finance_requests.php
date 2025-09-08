<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {                
        Schema::create('finance_requests', function (Blueprint $table) {
            $table->id();
            $table->enum('request_type', ['meeting-expense','proforma','invoice','refund-meeting','payment-confirmation'])->nullable();
            $table->string('reference_id', 255)->nullable();
            $table->foreignId('requester_id')->constrained('users')->restrictOnUpdate()->restrictOnDelete();
            $table->enum('status', ['pending','approved','rejected'])->nullable();            
            $table->foreignId('approver_id')->nullable()->constrained('users')->restrictOnUpdate()->restrictOnDelete();                        
            $table->timestamp('decided_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {       
        Schema::dropIfExists('finance_requests');
    }
};
