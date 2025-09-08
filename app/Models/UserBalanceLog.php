<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBalanceLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'amount',
        'quotation_id',
        'description',
        'status',
        'created_at',
    ];

    protected $dates = ['created_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
