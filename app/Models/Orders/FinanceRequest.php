<?php

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceRequest extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = [
        'meeting-expense',
        'proforma',
        'invoice',
        'payment-confirmation',
    ];

    protected $fillable = [
        'request_type',
        'reference_id',
        'requester_id',
        'status',
        'approver_id',
        'decided_at',
        'notes',
    ];

    protected $dates = ['decided_at'];

    public function requester()
    {
        return $this->belongsTo(\App\Models\User::class, 'requester_id');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approver_id');
    }
}
