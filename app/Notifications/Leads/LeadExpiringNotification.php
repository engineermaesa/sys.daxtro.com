<?php

namespace App\Notifications\Leads;

use App\Models\Leads\Lead;
use App\Models\Leads\LeadClaim;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class LeadExpiringNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public readonly Lead $lead,
        public readonly LeadClaim $claim,
        public readonly int $daysRemaining = 3,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'           => 'lead_expiring',
            'lead_id'        => $this->lead->id,
            'lead_name'      => $this->lead->name,
            'company'        => $this->lead->company,
            'sales_name'     => $this->claim->sales?->name,
            'claimed_at'     => $this->claim->claimed_at ? Carbon::parse($this->claim->claimed_at)->toDateString() : null,
            'days_remaining' => $this->daysRemaining,
            'lead_status'    => $this->lead->status?->name,
            'branch_id'      => $this->lead->branch_id,
        ];
    }

    public function toBroadcast($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastAs(): string
    {
        return 'lead.expiring';
    }
}
