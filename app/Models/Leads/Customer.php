<?php

namespace App\Models\Leads;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_link',
        'electricity',
        'building_area',
        'access_road_width',
        'file_cad',
        'leads_id',
    ];

    protected $casts = [
        'file_cad' => 'array',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'leads_id');
    }
}
