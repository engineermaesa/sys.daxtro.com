<?php

use App\Models\UserPermission;
use App\Models\UserRole;
use App\Models\UserRolePermission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $grants = [
            'supervisor_after_sales' => ['aftersales.tickets.view-own', 'aftersales.documents.fill', 'aftersales.survey-link.share'],
            'head_after_sales' => ['aftersales.tickets.view-all', 'aftersales.documents.sign'],
        ];

        foreach ($grants as $code => $permissions) {
            $role = UserRole::where('code', $code)->first();
            if (! $role) {
                continue;
            }

            foreach (UserPermission::whereIn('code', $permissions)->pluck('id') as $permissionId) {
                $grant = UserRolePermission::withTrashed()->firstOrNew([
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                ]);
                $grant->deleted_at = null;
                $grant->save();
            }
        }
    }

    public function down(): void {}
};
