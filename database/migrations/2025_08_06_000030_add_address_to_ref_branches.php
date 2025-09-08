<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ref_branches', function (Blueprint $table) {
            $table->text('address')->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('ref_branches', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
