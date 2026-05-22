<?php

namespace App\Notifications\Leads;

use App\Models\Leads\Lead;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class LeadTrashedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public readonly Lead $lead,
        public readonly ?User $sales,
        public readonly string $trashNote,
        public readonly bool $isAutoTrash = false
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'          => 'lead_trashed',
            'lead_id'       => $this->lead->id,
            'lead_name'     => $this->lead->name,
            'company'       => $this->lead->company,
            'sales_name'    => $this->sales?->name ?? 'System (Auto)',
            'trash_note'    => $this->trashNote,
            'is_auto_trash' => $this->isAutoTrash,
            'branch_id'     => $this->lead->branch_id,
        ];
    }

    public function toBroadcast($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('branch.' . $this->lead->branch_id)];
    }

    public function broadcastAs(): string
    {
        return 'lead.trashed';
    }
}