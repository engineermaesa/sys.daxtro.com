<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartStockMovement extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'ref_sparepart_id',
        'type',
        'qty',
        'ticket_id',
        'note',
    ];

    public function sparepart()
    {
        return $this->belongsTo(RefSparepart::class, 'ref_sparepart_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
