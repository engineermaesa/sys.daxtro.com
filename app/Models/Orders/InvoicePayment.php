<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoicePayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'invoice_payments';

    protected $fillable = [
        'invoice_id',
        'paid_at',
        'amount',
        'attachment_id',
        'confirmed_by',
    ];

    protected $dates = ['paid_at'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
