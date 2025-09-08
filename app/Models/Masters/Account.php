<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_accounts';

    protected $fillable = [
        'company_id',
        'bank_id',
        'account_number',
        'holder_name',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }    
}
