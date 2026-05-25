<?php

namespace App\Notifications\Orders;

use App\Models\Orders\FinanceRequest;
use App\Models\Orders\Order;
use App\Models\Orders\PaymentConfirmation;
use App\Models\Orders\Proforma;
use App\Models\Orders\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class FinanceRequestReviewedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public readonly FinanceRequest $financeRequest,
        public readonly string $decision,
        public readonly ?string $notes = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'               => 'finance_request_reviewed',
            'finance_request_id' => $this->financeRequest->id,
            'request_type'       => $this->financeRequest->request_type,
            'decision'           => $this->decision,
            'notes'              => $this->notes,
            'approver_name'      => auth()->user()?->name,
            'url'                => $this->resolveUrl(),
        ];
    }

    public function toBroadcast($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastAs(): string
    {
        return 'finance-request.reviewed';
    }

    private function resolveUrl(): string
    {
        try {
            $referenceId = $this->financeRequest->reference_id;

            switch ($this->financeRequest->request_type) {
                case 'proforma':
                    $proforma = Proforma::find($referenceId);
                    if ($proforma?->quotation_id) {
                        return route('quotations.show', $proforma->quotation_id);
                    }
                    break;

                case 'payment-confirmation':
                    $payment = PaymentConfirmation::with('proforma')->find($referenceId);
                    if ($payment?->proforma?->quotation_id) {
                        return route('quotations.show', $payment->proforma->quotation_id);
                    }
                    break;

                case 'invoice':
                    // reference_id format: "{order_id}-{term_no}"
                    [$orderId] = explode('-', $referenceId);
                    $order = Order::find($orderId);
                    if ($order?->lead_id) {
                        $quotation = Quotation::where('lead_id', $order->lead_id)
                            ->where('status', 'approved')
                            ->latest()
                            ->first();
                        if ($quotation) {
                            return route('quotations.show', $quotation->id);
                        }
                    }
                    break;
            }
        } catch (\Throwable) {
            // fallback
        }

        return route('finance-requests.index');
    }
}
