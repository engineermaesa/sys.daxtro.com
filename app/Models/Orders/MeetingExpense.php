<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeetingExpense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'meeting_id',
        'sales_id',
        'amount',
        'status',
        'requested_at',
    ];

    protected $dates = ['requested_at'];

    public function details()
    {
        return $this->hasMany(MeetingExpenseDetail::class, 'meeting_expense_id');
    }

    public function meeting()
    {
        return $this->belongsTo(\App\Models\Leads\LeadMeeting::class, 'meeting_id');
    }

    public function financeRequest()
    {
        return $this->hasOne(FinanceRequest::class, 'reference_id')
                    ->where('request_type', 'meeting-expense');
    }

    public function expenseRealizations()
    {
        return $this->hasMany(ExpenseRealization::class, 'meeting_expense_id');
    }

    public function sales()
    {
        return $this->belongsTo(\App\Models\User::class, 'sales_id');
    }
}
