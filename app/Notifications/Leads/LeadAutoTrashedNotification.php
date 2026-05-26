<?php

namespace App\Notifications\Leads;

use App\Models\Leads\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class LeadAutoTrashedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public readonly Lead $lead,
        public readonly string $trashNote,
        public readonly string $previousStatus,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'            => 'lead_auto_trashed',
            'lead_id'         => $this->lead->id,
            'lead_name'       => $this->lead->name,
            'company'         => $this->lead->company,
            'previous_status' => $this->previousStatus,
            'trash_note'      => $this->trashNote,
            'branch_id'       => $this->lead->branch_id,
        ];
    }

    public function toBroadcast($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastAs(): string
    {
        return 'lead.auto-trashed';
    }

    // Tidak perlu broadcastOn() — default ke private channel per-user (App.Models.User.{id})
    // konsisten dengan AvailableLeadNotification dan LeadExpiringNotification
}
