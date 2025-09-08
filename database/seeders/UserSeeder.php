<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $companyId  = DB::table('ref_companies')->value('id');
        $branchIds  = DB::table('ref_branches')->pluck('id', 'code');

        $roles = DB::table('user_roles')->pluck('id', 'code');

        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@localhost.com',
                'nip' => 'NIP001',
                'password' => Hash::make('Password123!'),
                'role_id' => $roles['super_admin'],
                'branch_id' => null,
            ],
            [
                'name' => 'Sales Director',
                'email' => 'salesdirector@localhost.com',
                'nip' => 'NIP002',
                'password' => Hash::make('Password123!'),
                'role_id' => $roles['sales_director'],
                'branch_id' => null,
            ],
            [
                'name' => 'Finance Director',
                'email' => 'financedirector@localhost.com',
                'nip' => 'NIP003',
                'password' => Hash::make('Password123!'),
                'role_id' => $roles['finance_director'],
                'branch_id' => null,
            ],
            [
                'name' => 'Accountant Director',
                'email' => 'accountantdirector@localhost.com',
                'nip' => 'NIP004',
                'password' => Hash::make('Password123!'),
                'role_id' => $roles['accountant_director'],
                'branch_id' => null,
            ],
        ];

        $nipCounter = 5;
        foreach ($branchIds as $code => $id) {
            $users[] = [
                'name' => 'Sales User '.$code,
                'email' => strtolower($code).'_sales@localhost.com',
                'nip' => 'NIP'.sprintf('%03d', $nipCounter++),
                'password' => Hash::make('Password123!'),
                'role_id' => $roles['sales'],
                'company_id' => $companyId,
                'branch_id' => $id,
            ];
            $users[] = [
                'name' => 'Branch Manager '.$code,
                'email' => strtolower($code).'_manager@localhost.com',
                'nip' => 'NIP'.sprintf('%03d', $nipCounter++),
                'password' => Hash::make('Password123!'),
                'role_id' => $roles['branch_manager'],
                'branch_id' => $id,
            ];
            $users[] = [
                'name' => 'Finance '.$code,
                'email' => strtolower($code).'_finance@localhost.com',
                'nip' => 'NIP'.sprintf('%03d', $nipCounter++),
                'password' => Hash::make('Password123!'),
                'role_id' => $roles['finance'],
                'branch_id' => $id,
            ];
            $users[] = [
                'name' => 'Accountant '.$code,
                'email' => strtolower($code).'_accountant@localhost.com',
                'nip' => 'NIP'.sprintf('%03d', $nipCounter++),
                'password' => Hash::make('Password123!'),
                'role_id' => $roles['accountant'],
                'branch_id' => $id,
            ];
            $users[] = [
                'name' => 'Purchasing '.$code,
                'email' => strtolower($code).'_purchasing@localhost.com',
                'nip' => 'NIP'.sprintf('%03d', $nipCounter++),
                'password' => Hash::make('Password123!'),
                'role_id' => $roles['purchasing'],
                'branch_id' => $id,
            ];
        }

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(['email' => $user['email']], $user);
        }
    }
}
