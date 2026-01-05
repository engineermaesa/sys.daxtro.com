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
        // Add target column to ref_companies table
        Schema::table('ref_companies', function (Blueprint $table) {
            $table->decimal('target', 15, 2)->nullable()->after('phone');
        });

        // Add target column to ref_branches table
        Schema::table('ref_branches', function (Blueprint $table) {
            $table->decimal('target', 15, 2)->nullable()->after('code');
        });

        // Add target column to users table
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('target', 15, 2)->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove target column from ref_companies table
        Schema::table('ref_companies', function (Blueprint $table) {
            $table->dropColumn('target');
        });

        // Remove target column from ref_branches table
        Schema::table('ref_branches', function (Blueprint $table) {
            $table->dropColumn('target');
        });

        // Remove target column from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('target');
        });
    }
};
