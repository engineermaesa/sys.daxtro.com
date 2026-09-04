<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = DB::table('user_roles')->pluck('id', 'code');
        $permissions = DB::table('user_permissions')->pluck('id', 'code');

        $bodPermissions = [
            $permissions['dashboard'] ?? null,
            $permissions['leads.manage'] ?? null,
            $permissions['leads.available'] ?? null,
            $permissions['leads.trash'] ?? null,
            $permissions['orders'] ?? null,
            $permissions['finance.requests'] ?? null,
            $permissions['users.manage'] ?? null,
            $permissions['users.roles'] ?? null,
            $permissions['settings.permissions-settings'] ?? null,
            $permissions['settings.general-settings'] ?? null,
            $permissions['satisfaction-survey.view'] ?? null,
        ];

        $map = [
            'super_admin' => collect($permissions)
                ->reject(function ($id, $code) {
                    return $code === 'leads.my';
                })
                ->values()
                ->all(),
            'sales_director' => $bodPermissions,
            'finance_director' => $bodPermissions,
            'accountant_director' => $bodPermissions,
            'branch_manager' => [
                $permissions['dashboard'] ?? null,
                $permissions['leads.manage'] ?? null,
                $permissions['orders'] ?? null,
                $permissions['users.manage'] ?? null,
                $permissions['satisfaction-survey.view'] ?? null,
            ],
            'finance' => [
                $permissions['dashboard'] ?? null,
                $permissions['leads.manage'] ?? null,
                $permissions['orders'] ?? null,
                $permissions['finance.requests'] ?? null,
            ],
            'accountant' => [
                $permissions['dashboard'] ?? null,
                $permissions['leads.manage'] ?? null,
                $permissions['orders'] ?? null,
            ],
            'purchasing' => [
                $permissions['dashboard'] ?? null,
                $permissions['orders'] ?? null,
            ],
            'after_sales' => [
                $permissions['dashboard'] ?? null,
                $permissions['customers.view'] ?? null,
                $permissions['satisfaction-survey.view'] ?? null,
                $permissions['aftersales.tickets.manage'] ?? null,
                $permissions['aftersales.tickets.assign'] ?? null,
                $permissions['aftersales.tickets.close'] ?? null,
                $permissions['aftersales.satisfaction.submit'] ?? null,
                $permissions['aftersales.work-orders.issue'] ?? null,
                $permissions['masters.service-customers'] ?? null,
                $permissions['masters.technicians'] ?? null,
                $permissions['masters.spareparts'] ?? null,
                $permissions['aftersales.stock.manage'] ?? null,
                $permissions['aftersales.kpi.view'] ?? null,
            ],
            'technician' => [
                $permissions['dashboard'] ?? null,
                $permissions['aftersales.documentation.submit'] ?? null,
            ],
            'sales' => [
                $permissions['dashboard'] ?? null,
                $permissions['leads.available'] ?? null,
                $permissions['leads.my'] ?? null,
                $permissions['leads.trash'] ?? null,
                $permissions['orders'] ?? null,
                $permissions['incentives.view'] ?? null,
            ],
        ];

        foreach ($map as $roleCode => $permIds) {
            if (! isset($roles[$roleCode])) {
                continue;
            }
            $roleId = $roles[$roleCode];
            DB::table('user_role_permissions')->where('role_id', $roleId)->delete();
            foreach (array_filter($permIds) as $pid) {
                DB::table('user_role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $pid,
                ]);
            }
        }
    }
}
