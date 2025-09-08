<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'Dashboard', 'code' => 'dashboard', 'description' => 'Access dashboard'],
            ['name' => 'Banks', 'code' => 'masters.banks', 'description' => 'Manage banks'],
            ['name' => 'Accounts', 'code' => 'masters.accounts', 'description' => 'Manage accounts'],
            ['name' => 'Product Categories', 'code' => 'masters.product-categories', 'description' => 'Manage product categories'],
            ['name' => 'Products', 'code' => 'masters.products', 'description' => 'Manage products'],
            ['name' => 'Parts', 'code' => 'masters.parts', 'description' => 'Manage parts'],
            ['name' => 'Companies', 'code' => 'masters.companies', 'description' => 'Manage companies'],
            ['name' => 'Provinces', 'code' => 'masters.provinces', 'description' => 'Manage provinces'],
            ['name' => 'Ref Provinces', 'code' => 'masters.provinces', 'description' => 'Manage ref provinces'],
            ['name' => 'Regions', 'code' => 'masters.regions', 'description' => 'Manage regions'],
            ['name' => 'Branches', 'code' => 'masters.branches', 'description' => 'Manage branches'],
            ['name' => 'Expense Types', 'code' => 'masters.expense-types', 'description' => 'Manage expense types'],
            ['name' => 'Customer Types', 'code' => 'masters.customer-types', 'description' => 'Manage customer types'],
            ['name' => 'Manage Leads', 'code' => 'leads.manage', 'description' => 'Manage leads'],
            ['name' => 'Available Leads', 'code' => 'leads.available', 'description' => 'View available leads'],
            ['name' => 'My Leads', 'code' => 'leads.my', 'description' => 'View my leads'],
            ['name' => 'Trash Leads', 'code' => 'leads.trash', 'description' => 'View trash leads'],
            ['name' => 'Orders', 'code' => 'orders', 'description' => 'Manage orders'],
            ['name' => 'Incentive Dashboard', 'code' => 'incentives.view', 'description' => 'View incentive balance and logs'],
            ['name' => 'Finance Requests', 'code' => 'finance.requests', 'description' => 'Handle finance approvals'],
            ['name' => 'Manage Users', 'code' => 'users.manage', 'description' => 'Manage users'],
            ['name' => 'Roles', 'code' => 'users.roles', 'description' => 'Manage roles'],
            ['name' => 'Permissions Settings', 'code' => 'settings.permissions-settings', 'description' => 'Manage permissions settings'],
            ['name' => 'Settings', 'code' => 'settings.general-settings', 'description' => 'Manage general settings'],
        ];

        foreach ($permissions as $perm) {
            DB::table('user_permissions')->updateOrInsert(
                ['code' => $perm['code']],
                $perm
            );
        }
    }
}
