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
        $readyForCustomer = $this->decision === 'approve' && $this->reviewerRole === 'BM'
            && $this->quotation->status === 'approved_by_bm';
        $code = $this->quotation->revision_code ?? $this->quotation->quotation_no;

        return [
            'type'          => 'quotation_reviewed',
            'quotation_id'  => $this->quotation->id,
            'quotation_no'  => $this->quotation->quotation_no,
            'revision_no'   => $this->quotation->revision_no,
            'revision_code' => $this->quotation->revision_code,
            'quotation_status' => $this->quotation->status,
            'ready_for_customer' => $readyForCustomer,
            'title' => $readyForCustomer ? 'Quotation Approved by BM' : 'Quotation Update',
            'message' => $readyForCustomer
                ? 'Quotation '.$code.' has been approved by BM and is ready to share with the customer.'
                : 'Quotation '.$code.' was '.($this->decision === 'approve' ? 'approved' : 'rejected').' by '.$this->reviewerRole.'.',
            'lead_name'     => $this->quotation->lead?->name,
            'company'       => $this->quotation->lead?->company,
            'decision'      => $this->decision,
            'reviewer_role' => $this->reviewerRole,
            'notes'         => $this->notes,
            'url'           => route('quotations.show', $this->quotation->id),
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
