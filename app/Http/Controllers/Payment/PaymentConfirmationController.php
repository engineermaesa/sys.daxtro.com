<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Models\Orders\Quotation;
use App\Models\Orders\Proforma;
use App\Models\Orders\FinanceRequest;
use App\Models\Orders\PaymentConfirmation;
use App\Models\Orders\PaymentLog;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PaymentConfirmationController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Orders';
        return $this->render('pages.orders.index');
    }

    public function paymentConfirmationForm($leadId, $term)
    {
        $quotation = Quotation::where('lead_id', $leadId)->firstOrFail();

        if ($term == 'bf') {
            $proforma = $quotation->proformas()->whereNull('term_no')->firstOrFail();
        } else {
            $proforma = $quotation->proformas()->where('term_no', $term)->firstOrFail();
        }

        return $this->render('pages.orders.payment-confirmation-form', compact('leadId', 'term', 'proforma'));
    }

    public function confirmPayment(Request $request, $leadId, $term)
    {
        $quotation = Quotation::where('lead_id', $leadId)->firstOrFail();

        if ($term == 'bf') {
            $proforma = $quotation->proformas()->whereNull('term_no')->firstOrFail();
        } else {
            $proforma = $quotation->proformas()->where('term_no', $term)->firstOrFail();
        }

        $payment = $proforma->paymentConfirmation;

        $rules = [
            'payer_name'            => 'nullable|string|max:255',
            'payer_bank'            => 'nullable|string|max:255',
            'payer_account_number'  => 'nullable|string|max:100',
            'paid_at'               => 'required|date',
        ];

        $rules['attachment_id'] = $payment
            ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120'
            : 'required|file|mimes:jpg,jpeg,png,pdf|max:5120';

        $data = $request->validate($rules);

        $attachmentId = $payment?->attachment_id;

        if ($request->hasFile('attachment_id')) {
            $file = $request->file('attachment_id');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('payment-confirmation', $filename, 'local');

            $attachment = Attachment::create([
                'type'        => 'payment_evidence',
                'file_path'   => 'storage/' . $path,
                'mime_type'   => $file->getClientMimeType(),
                'size'        => $file->getSize(),
                'uploaded_by' => $request->user()->id,
            ]);

            $attachmentId = $attachment->id;
        }

        if ($payment) {
            $payment->update([
                'payer_name'           => $data['payer_name'] ?? null,
                'payer_bank'           => $data['payer_bank'] ?? null,
                'payer_account_number' => $data['payer_account_number'] ?? null,
                'paid_at'              => $data['paid_at'],
                'amount'               => $proforma->amount,
                'attachment_id'        => $attachmentId,
            ]);

            $financeRequest = $payment->financeRequest;
            if ($financeRequest) {
                $financeRequest->update([
                    'status'      => 'pending',
                    'approver_id' => null,
                    'decided_at'  => null,
                    'notes'       => null,
                ]);
            } else {
                FinanceRequest::create([
                    'request_type' => 'payment-confirmation',
                    'reference_id' => $payment->id,
                    'requester_id' => $request->user()->id,
                    'status'       => 'pending',
                ]);
            }

            PaymentLog::create([
                'quotation_id' => $quotation->id,
                'proforma_id'  => $proforma->id,
                'type'         => 'confirmation',
                'user_id'      => $request->user()->id,
                'logged_at'    => now(),
            ]);
        } else {
            $payment = $proforma->paymentConfirmation()->create([
                'payer_name'           => $data['payer_name'] ?? null,
                'payer_bank'           => $data['payer_bank'] ?? null,
                'payer_account_number' => $data['payer_account_number'] ?? null,
                'paid_at'              => $data['paid_at'],
                'amount'               => $proforma->amount,
                'attachment_id'        => $attachmentId,
            ]);

            FinanceRequest::create([
                'request_type' => 'payment-confirmation',
                'reference_id' => $payment->id,
                'requester_id' => $request->user()->id,
                'status'       => 'pending',
            ]);

            PaymentLog::create([
                'quotation_id' => $quotation->id,
                'proforma_id'  => $proforma->id,
                'type'         => 'confirmation',
                'user_id'      => $request->user()->id,
                'logged_at'    => now(),
            ]);
        }

        return redirect()
            ->route('quotations.show', $quotation->id)
            ->with('status', 'Payment confirmation submitted for term ' . $term);
    }

}
