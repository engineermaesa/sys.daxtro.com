<?php

namespace App\Models\Leads;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadSegment extends Model
{
    use HasFactory;

    // Use the legacy reference table for customer types
    protected $table = 'ref_customer_types';

    protected $fillable = ['name'];
}
