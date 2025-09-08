<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('user_permissions')->updateOrInsert(
            ['code' => 'masters.customer-types'],
            ['name' => 'Customer Types', 'code' => 'masters.customer-types', 'description' => 'Manage customer types']
        );

        $permissionId = DB::table('user_permissions')->where('code', 'masters.customer-types')->value('id');
        $roleId = DB::table('user_roles')->where('code', 'super_admin')->value('id');

        if ($permissionId && $roleId) {
            DB::table('user_role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['role_id' => $roleId, 'permission_id' => $permissionId]
            );
        }

        $types = [
            ['name' => 'Corporate'],
            ['name' => 'Government'],
            ['name' => 'Personal'],
            ['name' => 'Repeat Order'],
            ['name' => 'Distributor'],
            ['name' => 'Tender-based'],
            ['name' => 'Commanditaire Vennootschap'],
            ['name' => 'Institution'],
            ['name' => 'Hospital'],
            ['name' => 'Foundation'],
        ];

        foreach ($types as $type) {
            DB::table('ref_customer_types')->updateOrInsert(
                ['name' => $type['name']],
                $type
            );
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('user_permissions')->where('code', 'masters.customer-types')->value('id');

        if ($permissionId) {
            DB::table('user_role_permissions')->where('permission_id', $permissionId)->delete();
        }

        DB::table('user_permissions')->where('code', 'masters.customer-types')->delete();
    }
};