<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lead_pic_extensions', function (Blueprint $table) {
            $table->foreignId('jabatan_id')
                ->nullable()
                ->after('nama')
                ->constrained('ref_jabatans')
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lead_pic_extensions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jabatan_id');
        });
    }
};
