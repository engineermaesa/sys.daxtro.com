<?php

namespace App\Notifications\Leads;

use App\Models\Leads\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class AvailableLeadNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public readonly Lead $lead) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'        => 'lead_available',
            'lead_id'     => $this->lead->id,
            'lead_name'   => $this->lead->name,
            'company'     => $this->lead->company,
            'branch_id'   => $this->lead->branch_id,
            'region_name' => $this->lead->region?->name,
        ];
    }

    public function toBroadcast($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastAs(): string
    {
        return 'lead.available';
    }
}
