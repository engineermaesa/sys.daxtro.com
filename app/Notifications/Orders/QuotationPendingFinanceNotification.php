<?php

namespace App\Notifications\Orders;

use App\Models\Orders\Quotation;
use App\Models\Orders\QuotationReview;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class QuotationPendingFinanceNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    private readonly array $payload;

    public function __construct(
        public readonly Quotation $quotation,
        public readonly User $sales,
        public readonly User $branchManager,
        public readonly ?string $notes = null,
        public readonly ?User $submittedBy = null,
        public readonly ?QuotationReview $bmApproval = null,
    ) {
        $code = $quotation->revision_code ?? $quotation->quotation_no;
        $this->payload = [
            'type' => 'quotation_pending_finance',
            'quotation_id' => $quotation->id,
            'quotation_no' => $quotation->quotation_no,
            'revision_no' => $quotation->revision_no,
            'revision_code' => $quotation->revision_code,
            'quotation_status' => $quotation->status,
            'title' => 'Quotation Pending Finance Approval',
            'message' => 'Quotation '.$code.' was submitted to Finance after customer acceptance and is awaiting your review.',
            'lead_name' => $quotation->lead?->name,
            'company' => $quotation->lead?->company,
            'sales_name' => $sales->name,
            'branch_manager_name' => $branchManager->name,
            'branch_id' => $quotation->lead?->branch_id ?? $sales->branch_id,
            'submitted_by' => $submittedBy?->id,
            'submitted_by_name' => $submittedBy?->name,
            'bm_approval_id' => $bmApproval?->id,
            'bm_approved_at' => $bmApproval?->decided_at?->toIso8601String(),
            'notes' => $notes,
            'url' => route('quotations.show', $quotation->id),
        ];
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return $this->payload;
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
