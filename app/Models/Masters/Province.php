<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Province extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_provinces';

    protected $fillable = [
        'regional_id',
        'name',
    ];

    public function regional()
    {
        return $this->belongsTo(Regional::class, 'regional_id');
    }

    public function regions()
    {
        return $this->hasMany(Region::class, 'province_id');
    }
}

