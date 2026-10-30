<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_roles') || ! Schema::hasTable('user_permissions') || ! Schema::hasTable('user_role_permissions')) {
            return;
        }

        (new \Database\Seeders\AftersalesV3RoleSeeder)->run();
    }

    // Roles and permissions are data; nothing to roll back.
    public function down(): void {}
};
