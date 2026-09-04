<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketVisit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'scheduled_at',
        'actual_start',
        'actual_end',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function workOrders()
    {
        return $this->hasMany(TicketWorkOrder::class, 'ticket_visit_id');
    }

    public function getWorkHoursAttribute(): ?float
    {
        if (! $this->actual_start || ! $this->actual_end) {
            return null;
        }

        return round($this->actual_start->diffInMinutes($this->actual_end) / 60, 2);
    }
}
