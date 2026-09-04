<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TechnicianSkill extends Model
{
    use HasFactory, SoftDeletes;

    public const LEVEL_BASIC = 'basic';
    public const LEVEL_INTERMEDIATE = 'intermediate';
    public const LEVEL_EXPERT = 'expert';

    protected $fillable = [
        'technician_profile_id',
        'skill_name',
        'level',
    ];

    public function technicianProfile()
    {
        return $this->belongsTo(TechnicianProfile::class, 'technician_profile_id');
    }
}
