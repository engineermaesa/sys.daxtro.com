<?php

namespace App\Notifications\Leads;

use App\Models\Leads\Lead;
use App\Models\Leads\LeadActivityLog;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class LeadActivityNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public readonly LeadActivityLog $activityLog,
        public readonly Lead $lead,
        public readonly User $sales
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'          => 'lead_activity',
            'lead_id'       => $this->lead->id,
            'lead_name'     => $this->lead->name,
            'company'       => $this->lead->company,
            'activity_name' => $this->activityLog->activity?->name ?? '-',
            'note'          => $this->activityLog->note,
            'sales_name'    => $this->sales->name,
            'logged_at'     => $this->activityLog->logged_at?->toISOString(),
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
        return 'lead.activity';
    }
}