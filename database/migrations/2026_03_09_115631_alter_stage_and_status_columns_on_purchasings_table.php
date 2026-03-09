<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchasings', function (Blueprint $table) {
            // Ubah tipe kolom stage dan status dari tinyInteger menjadi string
            // supaya bisa menyimpan nama stage dan nama status langsung.
            $table->string('stage', 100)->change();
            $table->string('status', 100)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchasings', function (Blueprint $table) {
            // Kembalikan ke tinyInteger jika di-rollback.
            $table->tinyInteger('stage')->change();
            $table->tinyInteger('status')->change();
        });
    }
};
