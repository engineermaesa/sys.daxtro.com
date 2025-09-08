<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Part extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_parts';

    protected $fillable = [
        'name',
        'price',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'ref_product_parts', 'part_id', 'product_id');
    }
}
