<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Masters\Part;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_products';

    protected $fillable = [
        'sku',
        'name',
        'description',
        'vat',
        'corporate_price',
        'government_price',
        'personal_price',
        'fob_price',
        'bdi_price',
        'warranty_available',
        'warranty_time_month',
    ];

    public function categories()
    {
        return $this->belongsToMany(ProductCategory::class, 'ref_product_category', 'product_id', 'category_id');
    }

    public function parts()
    {
        return $this->belongsToMany(Part::class, 'ref_product_parts', 'product_id', 'part_id');
    }

    public function quotation_items()
    {
        return $this->hasMany(\App\Models\Orders\QuotationItems::class, 'product_id');
    }

    public function order_items()
    {
        return $this->hasMany(\App\Models\Orders\OrderItems::class, 'product_id');
    }

    public function type()
    {
        return $this->belongsTo(\App\Models\Masters\ProductType::class, 'product_type_id');
    }
}
