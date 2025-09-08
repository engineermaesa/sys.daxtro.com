<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_expense_types';

    protected $fillable = [
        'name',
    ];

    public function meetingExpenseDetails()
    {
        return $this->hasMany(\App\Models\Orders\MeetingExpenseDetail::class, 'expense_type_id');
    }
}
