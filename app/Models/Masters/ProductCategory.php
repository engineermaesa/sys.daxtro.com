<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_product_categories';

    protected $fillable = [
        'name',
        'code',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'ref_product_category', 'category_id', 'product_id');
    }
}
