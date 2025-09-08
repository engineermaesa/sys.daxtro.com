<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_regions';

    protected $fillable = [
        'regional_id',
        'province_id',
        'branch_id',
        'name',
        'code',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function regional()
    {
        return $this->belongsTo(Regional::class, 'regional_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function users()
    {
        return $this->hasMany(\App\Models\User::class, 'region_id');
    }

    public function leads()
    {
        return $this->hasMany(\App\Models\Leads\Lead::class, 'region_id');
    }
}
