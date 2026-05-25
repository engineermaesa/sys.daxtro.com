<?php

namespace App\Notifications\Orders;

use App\Models\Orders\Quotation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class QuotationPendingFinanceNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public readonly Quotation $quotation,
        public readonly User $sales,
        public readonly User $branchManager,
        public readonly ?string $notes = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'                => 'quotation_pending_finance',
            'quotation_id'        => $this->quotation->id,
            'quotation_no'        => $this->quotation->quotation_no,
            'lead_name'           => $this->quotation->lead?->name,
            'company'             => $this->quotation->lead?->company,
            'sales_name'          => $this->sales->name,
            'branch_manager_name' => $this->branchManager->name,
            'branch_id'           => $this->quotation->lead?->branch_id ?? $this->sales->branch_id,
            'notes'               => $this->notes,
            'url'                 => route('quotations.show', $this->quotation->id),
        ];
    }

    public function toBroadcast($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastAs(): string
    {
        return 'quotation.pending-finance';
    }
}
