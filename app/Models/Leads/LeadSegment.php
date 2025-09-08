<?php

namespace App\Models\Leads;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadSegment extends Model
{
    use HasFactory;

    protected $table = 'lead_segments';

    protected $fillable = ['name'];
}
