<?php

namespace App\Notifications\Orders;

use App\Models\Orders\Quotation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class QuotationSubmittedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public readonly Quotation $quotation,
        public readonly User $sales,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'         => 'quotation_submitted',
            'quotation_id' => $this->quotation->id,
            'quotation_no' => $this->quotation->quotation_no,
            'lead_name'    => $this->quotation->lead?->name,
            'company'      => $this->quotation->lead?->company,
            'sales_name'   => $this->sales->name,
            'branch_id'    => $this->quotation->lead?->branch_id,
            'url'          => route('quotations.view', $this->quotation->id),
        ];
    }

    public function toBroadcast($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastAs(): string
    {
        return 'quotation.submitted';
    }
}
