<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_customer_types';

    protected $fillable = [
        'name',
    ];
}
