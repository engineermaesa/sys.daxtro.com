<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Orders\FinanceRequest;
use App\Models\Orders\Proforma;
use App\Models\Orders\Order;
use App\Models\Orders\Quotation;
use App\Models\Orders\Invoice;
use App\Models\Orders\QuotationLog;
use App\Models\Orders\MeetingExpense;
use App\Models\Orders\PaymentConfirmation;
use App\Models\Orders\PaymentLog;
use App\Models\Leads\LeadStatus;
use App\Models\Leads\LeadStatusLog;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;


class FinanceRequestController extends Controller
{
    public function index()
    {
        $this->pageTitle = 'Finance Requests';
        return $this->render('pages.finance.requests.index');
    }

    public function list(Request $request)
    {
        $query = FinanceRequest::with('requester');
        if ($request->filled('type')) {
            $query->where('request_type', $request->input('type'));
        }

        return DataTables::of($query)
            ->addColumn('request_type_badge', function ($row) {
                $colors = [
                    'proforma'             => 'primary',
                    'invoice'              => 'success',
                    'payment-confirmation' => 'warning',
                    'meeting-expense'      => 'info',
                ];
                $color = $colors[$row->request_type] ?? 'secondary';
                $label = ucwords(str_replace('-', ' ', $row->request_type));

                return '<span class="badge bg-' . $color . '">' . $label . '</span>';
            })
            ->addColumn('status_badge', function ($row) {
                $color = $row->status === 'approved' ? 'success' : ($row->status === 'rejected' ? 'danger' : 'warning');
                $label = ucwords(str_replace('-', ' ', $row->status));
                return '<span class="badge bg-' . $color . '">' . $label . '</span>';
            })
            ->addColumn('requester_name', fn($row) => $row->requester->name ?? '-')
            ->addColumn('actions', function ($row) {
                $url = route('finance-requests.form', $row->id);
                return '<a href="'.$url.'" class="btn btn-sm btn-primary">View Detail</a>';
            })
            ->rawColumns(['actions', 'request_type_badge', 'status_badge'])
            ->make(true);
    }

    public function form($id)
    {
        $financeRequest = FinanceRequest::with(['requester', 'approver'])->findOrFail($id);
        $order = null;
        $termNo = null;
        $meetingExpense = null;
        $proforma = null;
        
        if ($financeRequest->request_type === 'payment-confirmation') {
            $paymentConfirmation = \App\Models\Orders\PaymentConfirmation::with('proforma.quotation.order.orderItems', 'proforma.quotation.order.paymentTerms', 'proforma.quotation.order.lead', 'attachment')
                ->find($financeRequest->reference_id);

            $proforma = $paymentConfirmation?->proforma;
            $order = $proforma?->quotation?->order;
        } else if ($financeRequest->request_type === 'proforma' || $financeRequest->request_type === 'payment-confirmation') {
            $proforma = Proforma::with('quotation.order.orderItems', 'quotation.order.paymentTerms', 'quotation.order.lead', 'paymentConfirmation.attachment')->find($financeRequest->reference_id);
            $order = $proforma?->quotation?->order;
        } elseif ($financeRequest->request_type === 'invoice') {
            [$orderId, $termNo] = explode('-', $financeRequest->reference_id);
            $order = Order::with('orderItems', 'paymentTerms', 'lead')->find($orderId);
        } elseif ($financeRequest->request_type === 'meeting-expense') {
            $meetingExpense = MeetingExpense::with(['details.expenseType', 'meeting.lead'])->find($financeRequest->reference_id);
        }

        return $this->render('pages.finance.requests.form', compact('financeRequest', 'order', 'termNo', 'meetingExpense', 'proforma'));
    }


    public function approve($id, Request $request)
    {
        $financeRequest = FinanceRequest::findOrFail($id);

        $request->validate([
            'notes' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            match ($financeRequest->request_type) {
                'proforma'             => $this->approveProforma($financeRequest),
                'invoice'              => $this->approveInvoice($financeRequest),
                'payment-confirmation' => $this->approvePaymentConfirmation($financeRequest, $request),
                'meeting-expense'      => $this->approveMeetingExpense($financeRequest),
            };
    
            $financeRequest->update([
                'status' => 'approved',
                'approver_id' => auth()->id(),
                'decided_at' => now(),
                'notes' => $request->input('notes'),
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', 'An error occurred, please try again.');
        }

        return back()->with('status', 'Request approved');
    }

    private function approveProforma(FinanceRequest $request)
    {
        $proforma = Proforma::findOrFail($request->reference_id);
        $proforma->update([
            'proforma_no' => 'PROFORMA_' . $proforma->id,
            'status'      => 'confirmed',
            'issued_at'   => now(),
        ]);

        Attachment::create([
            'type'        => 'proforma_pdf',
            'file_path'   => 'storage/proformas/PROFORMA_' . $proforma->id . '.pdf',
            'mime_type'   => 'application/pdf',
            'size'        => 0,
            'uploaded_by' => auth()->id(),
        ]);

        PaymentLog::create([
            'quotation_id' => $proforma->quotation_id,
            'proforma_id'  => $proforma->id,
            'type'         => 'proforma',
            'user_id'      => auth()->id(),
            'logged_at'    => now(),
        ]);
    }

    private function approveInvoice(FinanceRequest $request)
    {
        [$orderId, $term] = explode('-', $request->reference_id);
        $order = Order::with('paymentTerms')->findOrFail($orderId);
        $quotation = Quotation::where('lead_id', $order->lead_id)->firstOrFail();

        $percentage = $order->paymentTerms->firstWhere('term_no', (int)$term)?->percentage ?? 0;
        $amount = $order->total_billing * ($percentage / 100);

        $proforma = $quotation->proformas()->firstOrCreate(
            ['term_no' => (int)$term],
            ['proforma_type' => $term == 1 ? 'down_payment' : 'term_payment', 'amount' => $amount]
        );

        if (!$proforma->proforma_no) {
            $proforma->update([
                'proforma_no' => 'PROFORMA_' . $proforma->id,
                'status'      => 'confirmed',
                'issued_at'   => now(),
            ]);

            Attachment::create([
                'type'        => 'proforma_pdf',
                'file_path'   => 'storage/proformas/PROFORMA_' . $proforma->id . '.pdf',
                'mime_type'   => 'application/pdf',
                'size'        => 0,
                'uploaded_by' => auth()->id(),
            ]);
        }

        $invoice = Invoice::create([
            'proforma_id'  => $proforma->id,
            'invoice_no'   => 'INVOICE_' . $proforma->id,
            'invoice_type' => $term == 3 ? 'final' : 'down_payment',
            'amount'       => $amount,
            'due_date'     => now()->addWeeks(4),
            'status'       => 'open',
            'issued_at'    => now(),
        ]);

        Attachment::create([
            'type'        => 'invoice_pdf',
            'file_path'   => 'storage/invoices/' . $invoice->invoice_no . '.pdf',
            'mime_type'   => 'application/pdf',
            'size'        => 0,
            'uploaded_by' => auth()->id(),
        ]);

        PaymentLog::create([
            'quotation_id' => $quotation->id,
            'invoice_id'   => $invoice->id,
            'type'         => 'invoice',
            'user_id'      => auth()->id(),
            'logged_at'    => now(),
        ]);

        QuotationLog::create([
            'quotation_id' => $quotation->id,
            'action'       => 'invoice_created',
            'user_id'      => auth()->id(),
            'logged_at'    => now(),
        ]);
    }

    private function approvePaymentConfirmation(FinanceRequest $request, Request $httpRequest)
    {
        $payment = PaymentConfirmation::with('proforma.quotation.lead')->findOrFail($request->reference_id);
        $quotation = $payment->proforma->quotation->load('items', 'paymentTerms');

        $invoiceType = match ($payment->proforma->proforma_type) {
            'booking_fee'  => 'booking_fee',
            'down_payment' => 'down_payment',
            default        => 'final',
        };

        $invoice = Invoice::create([
            'proforma_id'  => $payment->proforma->id,
            'invoice_no'   => 'INV-' . str_pad(Invoice::max('id') + 1, 5, '0', STR_PAD_LEFT),
            'invoice_type' => $invoiceType,
            'amount'       => $payment->amount,
            'due_date'     => now()->addDays(30),
            'status'       => 'paid',
            'issued_at'    => now(),
        ]);

        $html = view('pdfs.invoice', [
            'invoice'   => $invoice,
            'quotation' => $quotation,
            'proforma'  => $payment->proforma,
        ])->render();

        $pdf = PDF::loadHTML($html)->setPaper('A4', 'portrait');
        $fileName = $invoice->invoice_no . '.pdf';

        $storagePath = storage_path('app/public/invoices');
        if (!File::exists($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }

        $filePath = 'invoices/' . $fileName;
        $pdf->save(storage_path('app/public/' . $filePath));

        $attachment = Attachment::create([
            'type'        => 'invoice_pdf',
            'file_path'   => 'storage/' . $filePath,
            'mime_type'   => 'application/pdf',
            'size'        => strlen($pdf->output()),
            'uploaded_by' => auth()->id(),
        ]);

        $invoice->update(['attachment_id' => $attachment->id]);

        PaymentLog::create([
            'quotation_id' => $payment->proforma->quotation->id,
            'proforma_id'  => $payment->proforma->id,
            'invoice_id'   => $invoice->id,
            'type'         => 'invoice',
            'user_id'      => auth()->id(),
            'logged_at'    => now(),
        ]);

        QuotationLog::create([
            'quotation_id' => $payment->proforma->quotation->id,
            'action'       => 'invoice_created',
            'user_id'      => auth()->id(),
            'logged_at'    => now(),
        ]);

        $payment->update([
            'confirmed_by' => $httpRequest->user()->id,
            'confirmed_at' => now(),
        ]);

        // UPDATE LEAD STATUS
        $this->updateLeadStatusAfterPayment($payment);

        if ($payment->proforma->proforma_type === 'down_payment') {
            $this->createOrderFromQuotation($payment->proforma->quotation);
            $this->releaseIncentiveOnDownPayment($payment);
        }

        // CHECK IF ALL PROFORMAS ARE PAID
        $payment = PaymentConfirmation::with('proforma.quotation.lead')->findOrFail($request->reference_id);
        $this->checkOrderFinalization($payment);
    }

    private function createOrderFromQuotation(Quotation $quotation): void
    {
        // If order already exists, skip
        if ($quotation->order) {
            return;
        }

        $userId = Auth::user()->id;

        // Create the order
        $order = $quotation->order()->create([
            'lead_id'       => $quotation->lead_id,
            'order_no'      => 'ORDER_' . str_pad(\App\Models\Orders\Order::max('id') + 1, 5, '0', STR_PAD_LEFT),
            'total_billing' => $quotation->grand_total,
            'order_status'  => 'publish',
        ]);
        
        // Copy items to order_items
        foreach ($quotation->items as $item) {
            $order->orderItems()->create([
                'product_id'   => $item->product_id,
                'description'  => $item->description,
                'qty'          => $item->qty,
                'unit_price'   => $item->unit_price,
                'discount_pct' => $item->discount_pct,
                'tax_pct'      => $quotation->tax_pct,
                'line_total'   => $item->line_total,
            ]);
        }

        // Copy payment terms
        foreach ($quotation->paymentTerms as $term) {
            $order->paymentTerms()->create([
                'term_no'    => $term->term_no,
                'percentage' => $term->percentage,
            ]);
        }

        // Create initial order progress log
        $order->progressLogs()->create([
            'progress_step' => 1,
            'note'          => "Order created",
            'logged_at'     => now(),
            'user_id'       => $userId,
        ]);
    }

    private function updateLeadStatusAfterPayment(PaymentConfirmation $payment)
    {
        $lead = $payment->proforma->quotation->lead ?? null;

        if (!$lead) return;

        $type = $payment->proforma->proforma_type;
        
        if ($type === 'booking_fee' && $lead->status_id != LeadStatus::DEAL) {
            $lead->update(['status_id' => LeadStatus::HOT]);
            LeadStatusLog::create(['lead_id' => $lead->id, 'status_id' => LeadStatus::HOT]);
        } elseif ($type === 'down_payment') {
            $lead->update(['status_id' => LeadStatus::DEAL]);
            LeadStatusLog::create(['lead_id' => $lead->id, 'status_id' => LeadStatus::DEAL]);
        }
    }

    private function checkOrderFinalization(PaymentConfirmation $payment)
    {
        $order = $payment->proforma->quotation->order;
        if (!$order) return;

        $quotation = $payment->proforma->quotation->load('proformas.paymentConfirmation', 'reviews');

        $allPaid = $quotation->proformas->every(fn($p) =>
            $p->paymentConfirmation && $p->paymentConfirmation->confirmed_at
        );

        if ($allPaid) {
            $order->update(['order_status' => 'done']);

            // Incentive already released on first down payment
            // so no further balance increment is required here.
        }
    }

    private function releaseIncentiveOnDownPayment(PaymentConfirmation $payment)
    {
        $proforma = $payment->proforma;

        if ($proforma->proforma_type !== 'down_payment' || $proforma->term_no !== 1) {
            return;
        }

        $quotation = $proforma->quotation->load('reviews');

        $review = $quotation->reviews->where('decision', 'approve')->last();
        if (! $review) return;

        $salesId = $quotation->created_by;

        if ($salesId) {
            $balance = \App\Models\UserBalance::firstOrCreate(
                ['user_id' => $salesId],
                ['total_balance' => 0]
            );

            $balance->increment('total_balance', $review->incentive_nominal);

            \App\Models\UserBalanceLog::where('user_id', $salesId)
                ->where('quotation_id', $quotation->id)
                ->update(['status' => 'received']);
        }
    }

    private function approveMeetingExpense(FinanceRequest $request)
    {
        $expense = MeetingExpense::find($request->reference_id);
        if ($expense) {
            $expense->update(['status' => 'approved']);
        }
    }


    public function reject($id, Request $request)
    {
        $financeRequest = FinanceRequest::findOrFail($id);

        if ($financeRequest->request_type === 'meeting-expense') {
            $expense = MeetingExpense::find($financeRequest->reference_id);
            if ($expense) {
                $expense->status = 'rejected';
                $expense->save();
            }
        }

        $financeRequest->update([
            'status' => 'rejected',
            'approver_id' => auth()->id(),
            'decided_at' => now(),
            'notes' => $request->input('notes'),
        ]);

        return back()->with('status', 'Request rejected');
    }
}
