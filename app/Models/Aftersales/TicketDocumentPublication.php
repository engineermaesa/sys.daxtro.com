<?php

namespace App\Models\Aftersales;

use Illuminate\Database\Eloquent\Model;

class TicketDocumentPublication extends Model
{
    protected $fillable = ['ticket_id', 'cycle_id', 'ticket_log_id', 'satisfaction_link_id', 'published_at'];

    protected $casts = ['ticket_id' => 'integer', 'cycle_id' => 'integer', 'published_at' => 'datetime'];

    public function cycle()
    {
        return $this->belongsTo(TicketCycle::class, 'cycle_id');
    }

    public function surveyLink()
    {
        return $this->belongsTo(SatisfactionLink::class, 'satisfaction_link_id');
    }
}
