<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Orders\ExpenseRealization;
use App\Models\Orders\MeetingExpense;
use App\Models\User;

class ExpenseRealizationSeeder extends Seeder
{
    public function run(): void
    {
        // Get an approved meeting expense
        $meetingExpense = MeetingExpense::where('status', 'approved')->first();
        
        if (!$meetingExpense) {
            // Create one if it doesn't exist
            $meetingExpense = MeetingExpense::first();
            if ($meetingExpense) {
                $meetingExpense->update(['status' => 'approved']);
            }
        }
        
        if ($meetingExpense) {
            // Create some expense realizations with different statuses
            ExpenseRealization::create([
                'meeting_expense_id' => $meetingExpense->id,
                'sales_id' => $meetingExpense->sales_id,
                'realized_amount' => 50000,
                'status' => 'draft',
                'notes' => 'Draft expense realization',
            ]);
            
            ExpenseRealization::create([
                'meeting_expense_id' => $meetingExpense->id,
                'sales_id' => $meetingExpense->sales_id,
                'realized_amount' => 75000,
                'status' => 'submitted',
                'notes' => 'Submitted expense realization',
                'submitted_at' => now(),
            ]);
        }
    }
}