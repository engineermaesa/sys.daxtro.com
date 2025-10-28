<?php

namespace App\Models\Orders;

use App\Models\Masters\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuotationItems extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'quotation_items';

    protected $fillable = [
        'quotation_id',
        'product_id',
        'qty',
        'description',
        'unit_price',
        'discount_pct',
        'line_total',
        'is_visible_pdf',
        'merge_into_item_id',
    ];

    protected $casts = [
        'is_visible_pdf' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function mergeIntoItem()
    {
        return $this->belongsTo(QuotationItems::class, 'merge_into_item_id');
    }

    public function mergedItems()
    {
        return $this->hasMany(QuotationItems::class, 'merge_into_item_id');
    }

}
