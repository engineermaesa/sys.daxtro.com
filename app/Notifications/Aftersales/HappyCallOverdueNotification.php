<?php

namespace App\Notifications\Aftersales;

use App\Models\Aftersales\Ticket;
use Carbon\Carbon;
use Illuminate\Notifications\Notification;

class HappyCallOverdueNotification extends Notification
{
    public function __construct(public readonly Ticket $ticket, public readonly int $cycleId, public readonly Carbon $dueAt) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return ['type' => 'happy_call_overdue', 'title' => 'Happy Call overdue',
            'message' => 'Happy Call for ticket '.$this->ticket->ticket_code.' is overdue.',
            'ticket_id' => $this->ticket->id, 'ticket_code' => $this->ticket->ticket_code,
            'cycle_id' => $this->cycleId, 'happy_call_due_at' => $this->dueAt->toIso8601String(),
            'url' => '/aftersales/tickets/'.$this->ticket->id];
    }
}
