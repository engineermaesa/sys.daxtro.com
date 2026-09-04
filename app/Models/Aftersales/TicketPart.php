<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketPart extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_ESTIMATED = 'estimated';
    public const TYPE_ACTUAL = 'actual';

    public const UNITS = ['pieces', 'set', 'lot', 'kilogram'];

    protected $fillable = [
        'ticket_id',
        'ref_sparepart_id',
        'qty',
        'unit',
        'unit_price',
        'type',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function sparepart()
    {
        return $this->belongsTo(RefSparepart::class, 'ref_sparepart_id');
    }

    public function getSubtotalAttribute(): float
    {
        return round((float) $this->qty * (float) $this->unit_price, 2);
    }
}
