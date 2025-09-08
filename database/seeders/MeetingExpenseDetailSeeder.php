<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MeetingExpenseDetailSeeder extends Seeder
{
    public function run(): void
    {
        $expenseId = DB::table('meeting_expenses')->value('id');
        $expenseTypeId = DB::table('ref_expense_types')->value('id');

        if ($expenseId && $expenseTypeId) {
            DB::table('meeting_expense_details')->insert([
                'meeting_expense_id' => $expenseId,
                'expense_type_id'    => $expenseTypeId,
                'amount'             => 100,
                'notes'              => 'Transport',
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }
}
