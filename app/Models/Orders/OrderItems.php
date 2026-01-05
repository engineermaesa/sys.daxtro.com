<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItems extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id', 'product_id', 'description', 'qty', 'unit_price', 'discount_pct', 
        'tax_pct', 'line_total', 'total_discount'
    ];

    public function product()
    {
        return $this->belongsTo(App\Models\Masters\Product::class, 'product_id');
    }
}
