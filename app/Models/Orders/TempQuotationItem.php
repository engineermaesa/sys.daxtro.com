<?php

namespace App\Models\Orders;

use App\Models\Masters\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempQuotationItem extends Model
{
    use HasFactory;

    protected $table = 'temp_quotation_items';

    protected $fillable = [
        'temp_quotation_id',
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
        return $this->belongsTo(TempQuotationItem::class, 'merge_into_item_id');
    }

    public function mergedItems()
    {
        return $this->hasMany(TempQuotationItem::class, 'merge_into_item_id');
    }
}
