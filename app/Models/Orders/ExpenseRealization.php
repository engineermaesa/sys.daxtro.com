<?php

namespace App\Models\Orders;

use App\Models\User;
use App\Models\Masters\ExpenseType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseRealization extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'meeting_expense_id',
        'sales_id',
        'realized_amount',
        'status',
        'notes',
        'submitted_at',
        'approved_at',
        'approved_by',
    ];

    protected $dates = ['submitted_at', 'approved_at'];

    public function meetingExpense()
    {
        return $this->belongsTo(MeetingExpense::class, 'meeting_expense_id');
    }

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function financeRequest()
    {
        return $this->hasOne(FinanceRequest::class, 'reference_id')
                    ->where('request_type', 'expense-realization');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function details()
    {
        return $this->hasMany(ExpenseRealizationDetail::class);
    }
}