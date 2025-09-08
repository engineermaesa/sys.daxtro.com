<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quotation_signed_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('attachment_id')->constrained('attachments')->restrictOnUpdate()->restrictOnDelete();
            $table->text('description')->nullable();
            $table->date('signed_date')->nullable();
            $table->foreignId('uploader_id')->constrained('users')->restrictOnUpdate()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_signed_documents');
    }
};
