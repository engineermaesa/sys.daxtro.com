<?php

namespace App\Models\Orders;

use App\Models\Masters\ExpenseType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseRealizationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_realization_id',
        'expense_type_id',
        'amount',
        'receipt_attachment_id',
        'notes',
    ];

    public function expenseRealization()
    {
        return $this->belongsTo(ExpenseRealization::class);
    }

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class);
    }

    public function receiptAttachment()
    {
        return $this->belongsTo(\App\Models\Attachment::class, 'receipt_attachment_id');
    }
}