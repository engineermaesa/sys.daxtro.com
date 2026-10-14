<?php

namespace App\Notifications\Aftersales;

use App\Models\Aftersales\TicketSatisfaction;
use App\Models\User;
use Illuminate\Notifications\Notification;

class MachineNotWorkingNotification extends Notification
{
    public function __construct(public readonly TicketSatisfaction $satisfaction) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $ticket = $this->satisfaction->ticket;

        return [
            'type' => 'machine_not_working',
            'satisfaction_id' => $this->satisfaction->id,
            'ticket_id' => $ticket->id,
            'ticket_code' => $ticket->ticket_code,
            'customer' => $ticket->customer?->name,
            'note' => $this->satisfaction->machine_issue_note,
        ];
    }

    public static function sendFor(TicketSatisfaction $satisfaction): void
    {
        if ($satisfaction->machine_ok !== false) {
            return;
        }

        $satisfaction->loadMissing('ticket.customer');

        User::whereHas('role.permissions', fn ($q) => $q->where('code', 'satisfaction-survey.review'))
            ->each(fn (User $u) => $u->notify(new self($satisfaction)));
    }
}
