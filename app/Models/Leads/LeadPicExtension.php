<?php

namespace App\Models\Leads;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadPicExtension extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_id',
        'nama',
        'jabatan_id',
        'email',
        'phone',
        'title',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
