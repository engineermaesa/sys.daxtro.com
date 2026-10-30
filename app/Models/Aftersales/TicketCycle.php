<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class TicketCycle extends Model
{
    protected $fillable = [
        'ticket_id', 'cycle_no', 'started_at', 'ended_at',
        'sla_value', 'sla_unit', 'sla_due_at', 'sla_stopped_at', 'happy_call_due_at',
    ];

    protected $casts = [
        'ticket_id' => 'integer', 'cycle_no' => 'integer', 'sla_value' => 'integer',
        'started_at' => 'datetime', 'ended_at' => 'datetime', 'sla_due_at' => 'datetime',
        'sla_stopped_at' => 'datetime', 'happy_call_due_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function recoming()
    {
        return $this->hasOne(TicketRecoming::class, 'cycle_id');
    }

    public function visits()
    {
        return $this->hasMany(TicketVisit::class, 'cycle_id');
    }

    public function logs()
    {
        return $this->hasMany(TicketLog::class, 'cycle_id');
    }

    public function surveyResponses()
    {
        return $this->hasMany(TicketSatisfaction::class, 'cycle_id');
    }

    public function surveyLinks()
    {
        return $this->hasMany(SatisfactionLink::class, 'cycle_id');
    }
}
