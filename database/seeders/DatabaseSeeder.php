<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\BankSeeder;
use Database\Seeders\CompanySeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RegionSeeder;
use Database\Seeders\ExpenseTypeSeeder;
use Database\Seeders\CustomerTypeSeeder;
use Database\Seeders\ProductCategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\PartSeeder;
use Database\Seeders\ProductCategoryPivotSeeder;
use Database\Seeders\ProductPartSeeder;
use Database\Seeders\IndustrySeeder;
use Database\Seeders\JabatanSeeder;
use Database\Seeders\AccountSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\MeetingExpenseDetailSeeder;
use Database\Seeders\OrderSeeder;
use Database\Seeders\MeetingSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // RoleSeeder::class,
            // PermissionSeeder::class,
            // RolePermissionSeeder::class,
            // CompanySeeder::class,
            // BranchSeeder::class,
            // RegionSeeder::class,
            // UserSeeder::class,
            // BankSeeder::class,
            // ExpenseTypeSeeder::class,
            MeetingTypeSeeder::class,
            // CustomerTypeSeeder::class,
            // ProductCategorySeeder::class,
            // ProductTypeSeeder::class,
            // ProductSeeder::class,
            // PartSeeder::class,
            // ProductPartSeeder::class,
            // IndustrySeeder::class,
            // JabatanSeeder::class,
            // LeadActivityListSeeder::class,
            // AccountSeeder::class,
            // LeadSeeder::class,
            // OrderSeeder::class,
            // MeetingSeeder::class,
            // OrderPaymentStatusSeeder::class,
            // FinanceRequestSeeder::class,
            // MeetingExpenseDetailSeeder::class,
        ]);
    }
}
