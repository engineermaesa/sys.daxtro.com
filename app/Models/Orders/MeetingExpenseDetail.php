<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeetingExpenseDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'meeting_expense_id',
        'expense_type_id',
        'amount',
        'notes',
    ];

    public function meetingExpense()
    {
        return $this->belongsTo(MeetingExpense::class, 'meeting_expense_id');
    }

    public function expenseType()
    {
        return $this->belongsTo(\App\Models\Masters\ExpenseType::class, 'expense_type_id');
    }
}
