<?php

namespace App\Notifications\Orders;

use App\Models\Orders\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class QuotationReviewedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public readonly Quotation $quotation,
        public readonly string $decision,
        public readonly string $reviewerRole,
        public readonly ?string $notes = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'          => 'quotation_reviewed',
            'quotation_id'  => $this->quotation->id,
            'quotation_no'  => $this->quotation->quotation_no,
            'lead_name'     => $this->quotation->lead?->name,
            'company'       => $this->quotation->lead?->company,
            'decision'      => $this->decision,
            'reviewer_role' => $this->reviewerRole,
            'notes'         => $this->notes,
            'url'           => route('quotations.view', $this->quotation->id),
        ];
    }

    public function toBroadcast($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastAs(): string
    {
        return 'quotation.reviewed';
    }
}
