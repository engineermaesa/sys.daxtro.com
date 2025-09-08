<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('user_permissions')->updateOrInsert(
            ['code' => 'masters.provinces'],
            ['name' => 'Provinces', 'code' => 'masters.provinces', 'description' => 'Manage provinces']
        );

        $permissionId = DB::table('user_permissions')->where('code', 'masters.provinces')->value('id');
        $roleId = DB::table('user_roles')->where('code', 'super_admin')->value('id');

        if ($permissionId && $roleId) {
            DB::table('user_role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['role_id' => $roleId, 'permission_id' => $permissionId]
            );
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('user_permissions')->where('code', 'masters.provinces')->value('id');

        if ($permissionId) {
            DB::table('user_role_permissions')->where('permission_id', $permissionId)->delete();
        }

        DB::table('user_permissions')->where('code', 'masters.provinces')->delete();
    }
};
