<?php

namespace App\Notifications\Orders;

use App\Models\Orders\PaymentConfirmation;
use App\Models\Orders\Proforma;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class PaymentConfirmationSubmittedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public readonly PaymentConfirmation $paymentConfirmation,
        public readonly Proforma $proforma,
        public readonly User $submitter,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        $quotation = $this->proforma->quotation;

        return [
            'type'                    => 'payment_confirmation_submitted',
            'payment_confirmation_id' => $this->paymentConfirmation->id,
            'proforma_id'             => $this->proforma->id,
            'quotation_id'            => $quotation?->id,
            'quotation_no'            => $quotation?->quotation_no,
            'lead_name'               => $quotation?->lead?->name,
            'company'                 => $quotation?->lead?->company,
            'submitter_name'          => $this->submitter->name,
            'amount'                  => $this->proforma->amount,
            'term_no'                 => $this->proforma->term_no,
            'url'                     => $quotation ? route('quotations.show', $quotation->id) : null,
        ];
    }

    public function toBroadcast($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastAs(): string
    {
        return 'payment.confirmation.submitted';
    }
}
