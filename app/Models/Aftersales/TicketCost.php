<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketCost extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORIES = ['labor', 'accommodation', 'transportation', 'other'];
    public const CURRENCIES = ['idr', 'usd'];

    protected $fillable = [
        'ticket_id',
        'category',
        'amount',
        'currency',
        'exchange_rate',
        'remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function getAmountIdrAttribute(): float
    {
        return $this->currency === 'usd'
            ? round((float) $this->amount * (float) ($this->exchange_rate ?? 1), 2)
            : (float) $this->amount;
    }
}
