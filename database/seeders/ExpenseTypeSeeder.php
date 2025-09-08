<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $expenseTypes = [
            ['name' => 'Travel'],
            ['name' => 'Meals'],
            ['name' => 'Accommodation'],
        ];

        foreach ($expenseTypes as $type) {
            DB::table('ref_expense_types')->updateOrInsert(['name' => $type['name']], $type);
        }
    }
}
