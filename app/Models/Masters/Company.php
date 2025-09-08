<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_companies';

    protected $fillable = [
        'name',
        'address',
        'phone',
    ];

    public function branches()
    {
        return $this->hasMany(\App\Models\Masters\Branch::class, 'company_id');
    }

    public function accounts()
    {
        return $this->hasMany(\App\Models\Masters\Account::class, 'company_id');
    }
}
