<?php

namespace App\Models\Aftersales;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketWorkOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'ticket_visit_id',
        'wo_number',
        'technician_id',
        'issued_at',
        'technician_signed_at',
        'note',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'technician_signed_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function visit()
    {
        return $this->belongsTo(TicketVisit::class, 'ticket_visit_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
