<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPaymentTerm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'term_no',
        'percentage',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
