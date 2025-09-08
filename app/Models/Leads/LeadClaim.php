<?php

namespace App\Models\Leads;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadClaim extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_id',
        'sales_id',
        'claimed_at',
        'released_at',
        'trash_note',
    ];

    protected $dates = [
        'claimed_at',
        'released_at',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function sales()
    {
        return $this->belongsTo(\App\Models\User::class, 'sales_id');
    }
}
