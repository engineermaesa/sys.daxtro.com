<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {                
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('type', 100)->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('mime_type', 255)->nullable();
            $table->integer('size')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnUpdate()->restrictOnDelete();                        
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        $tables = [
            'attachments',          
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
