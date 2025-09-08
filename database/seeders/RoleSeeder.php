<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserRole;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'code' => 'super_admin'],
            ['name' => 'Sales', 'code' => 'sales'],
            ['name' => 'Sales Director', 'code' => 'sales_director'],
            ['name' => 'Branch Manager', 'code' => 'branch_manager'],
            ['name' => 'Finance', 'code' => 'finance'],
            ['name' => 'Finance Director', 'code' => 'finance_director'],
            ['name' => 'Accountant', 'code' => 'accountant'],
            ['name' => 'Accountant Director', 'code' => 'accountant_director'],
            ['name' => 'Purchasing', 'code' => 'purchasing'],
        ];

        foreach ($roles as $role) {
            UserRole::firstOrCreate(['code' => $role['code']], $role);
        }
    }
}
