<?php

use App\Models\UserPermission;
use App\Models\UserRole;
use App\Models\UserRolePermission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $roles = UserRole::where('code', 'technician')->get();
        if ($roles->isEmpty()) {
            return;
        }

        $permission = UserPermission::firstOrCreate(
            ['code' => 'dashboard'],
            ['name' => 'Dashboard', 'description' => 'Access dashboard'],
        );

        foreach ($roles as $role) {
            $grant = UserRolePermission::withTrashed()->firstOrNew([
                'role_id' => $role->id,
                'permission_id' => $permission->id,
            ]);
            $grant->deleted_at = null;
            $grant->save();
        }
    }

    public function down(): void {}
};
