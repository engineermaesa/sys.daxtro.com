<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bank extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_banks';

    protected $fillable = [
        'name',
    ];

    public function accounts()
    {
        return $this->hasMany(\App\Models\Masters\Account::class, 'bank_id');
    }
}
