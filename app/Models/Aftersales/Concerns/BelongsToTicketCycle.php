<?php

namespace App\Models\Aftersales\Concerns;

use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketCycle;
use App\Services\Aftersales\TicketCycles;

trait BelongsToTicketCycle
{
    public function initializeBelongsToTicketCycle(): void
    {
        $this->mergeCasts(['cycle_id' => 'integer']);
    }

    public static function bootBelongsToTicketCycle(): void
    {
        static::creating(function ($model) {
            if ($model->cycle_id !== null) {
                return;
            }
            $ticket = Ticket::withTrashed()->find($model->ticket_id);
            if ($ticket?->workflow_version !== null) {
                $model->cycle_id = $ticket->currentCycle?->id ?? TicketCycles::initial($ticket)->id;
            }
        });
    }

    public function cycle()
    {
        return $this->belongsTo(TicketCycle::class, 'cycle_id');
    }
}
