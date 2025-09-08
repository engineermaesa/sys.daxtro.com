<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Regional extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_regionals';

    protected $fillable = [
        'name',
    ];

    public function provinces()
    {
        return $this->hasMany(Province::class, 'regional_id');
    }
}

