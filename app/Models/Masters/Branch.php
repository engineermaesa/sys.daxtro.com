<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_branches';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'address',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function regions()
    {
        return $this->hasMany(\App\Models\Masters\Region::class, 'region_id');
    }
}
