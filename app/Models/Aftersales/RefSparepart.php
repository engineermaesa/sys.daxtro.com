<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefSparepart extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_spareparts';

    protected $fillable = [
        'part_number',
        'name',
        'brand',
        'supplier',
        'price',
        'stock',
        'min_stock',
        'rack_location',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'min_stock' => 'integer',
    ];

    public function ticketParts()
    {
        return $this->hasMany(TicketPart::class, 'ref_sparepart_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(PartStockMovement::class, 'ref_sparepart_id');
    }

    public function getStockStatusAttribute(): string
    {
        return $this->stock <= $this->min_stock ? 'REORDER' : 'SAFE';
    }
}
