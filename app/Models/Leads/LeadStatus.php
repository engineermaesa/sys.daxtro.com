<?php

namespace App\Models\Leads;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public const PUBLISHED  = 1;
    public const COLD       = 2;
    public const WARM       = 3;
    public const HOT        = 4;
    public const DEAL       = 5;
    public const TRASH_COLD = 6;
    public const TRASH_WARM = 7;
}
