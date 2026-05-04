@extends('layouts.app')

@section('content')
@php
  $resolvedProforma = $proforma ?? null;
  $resolvedQuotation = $quotation ?? $resolvedProforma?->quotation;
  $resolvedOrder = $order ?? $resolvedQuotation?->order;
  $resolvedPayment = $paymentConfirmation ?? $resolvedProforma?->paymentConfirmation;
  $resolvedInvoice = $invoice ?? $resolvedProforma?->invoice;

  $formatDate = function ($value, $format = 'd M Y, H:i') {
      return $value ? \Carbon\Carbon::parse($value)->format($format) : '-';
  };

  $formatMoney = function ($value) {
      return $value !== null && $value !== '' ? 'Rp' . number_format((float) $value, 0, ',', '.') : '-';
  };

  $formatLabel = function ($value) {
      return $value ? ucwords(str_replace(['-', '_'], ' ', $value)) : '-';
  };

  $statusClasses = [
      'pending' => 'bg-[#FFF1C2] text-[#A87000]',
      'approved' => 'bg-[#E7F3EE] text-[#115640]',
      'rejected' => 'bg-[#FDECEC] text-[#900B09]',
  ];

  $typeClasses = [
      'proforma' => 'bg-[#E1EBFA] text-[#3F80EA]',
      'invoice' => 'bg-[#E7F3EE] text-[#115640]',
      'payment-confirmation' => 'bg-[#FFF1C2] text-[#A87000]',
      'meeting-expense' => 'bg-[#E1EBFA] text-[#1E1E1E]',
      'expense-realization' => 'bg-[#F5F5F5] text-[#1E1E1E]',
  ];

  $requestStatusClass = $statusClasses[$financeRequest->status] ?? 'bg-[#F5F5F5] text-[#1E1E1E]';
  $requestTypeClass = $typeClasses[$financeRequest->request_type] ?? 'bg-[#F5F5F5] text-[#1E1E1E]';

  $orderItems = collect($resolvedOrder?->orderItems ?? []);
  $displayItems = $orderItems->isNotEmpty()
      ? $orderItems
      : collect($resolvedQuotation?->items ?? []);

  $paymentTerms = collect($resolvedOrder?->paymentTerms ?? []);
  if ($paymentTerms->isEmpty()) {
      $paymentTerms = collect($resolvedQuotation?->paymentTerms ?? []);
  }

  $quotationProformas = collect($resolvedQuotation?->proformas ?? []);
  $termRows = $paymentTerms->map(function ($term) use ($quotationProformas, $resolvedOrder) {
      $termNo = $term->term_no ?? null;
      $percentage = $term->percentage ?? null;
      $termProforma = $quotationProformas->firstWhere('term_no', $termNo);
      $payment = $termProforma?->paymentConfirmation;
      $invoice = $termProforma?->invoice;
      $calculatedAmount = ($resolvedOrder && $percentage !== null)
          ? ((float) $resolvedOrder->total_billing * ((float) $percentage / 100))
          : null;

      return [
          'term_no' => $termNo,
          'percentage' => $percentage,
          'amount' => $payment?->amount ?? $termProforma?->amount ?? $invoice?->amount ?? $calculatedAmount,
          'paid_at' => $payment?->paid_at ?? $payment?->confirmed_at,
          'status' => $invoice?->status ?? ($payment?->confirmed_at ? 'paid' : ($termProforma?->status ?? '-')),
          'proforma' => $termProforma,
          'payment' => $payment,
          'invoice' => $invoice,
      ];
  });

  if ($termRows->isEmpty() && $resolvedProforma) {
      $termRows = collect([[
          'term_no' => $resolvedProforma->term_no ?? 'Booking Fee',
          'percentage' => null,
          'amount' => $resolvedPayment?->amount ?? $resolvedProforma->amount ?? $resolvedInvoice?->amount,
          'paid_at' => $resolvedPayment?->paid_at ?? $resolvedPayment?->confirmed_at,
          'status' => $resolvedInvoice?->status ?? ($resolvedPayment?->confirmed_at ? 'paid' : ($resolvedProforma->status ?? '-')),
          'proforma' => $resolvedProforma,
          'payment' => $resolvedPayment,
          'invoice' => $resolvedInvoice,
      ]]);
  }
@endphp

<section class="min-h-screen text-xs lg:text-sm">
  <div class="pt-4">
    <div class="flex items-center gap-2">
      <svg width="20" height="20" viewBox="0 0 22 27" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path opacity="0.8" d="M2.66667 26.6667C1.93333 26.6667 1.30556 26.4056 0.783333 25.8833C0.261111 25.3611 0 24.7333 0 24V2.66667C0 1.93333 0.261111 1.30556 0.783333 0.783333C1.30556 0.261111 1.93333 0 2.66667 0H13.3333L21.3333 8V24C21.3333 24.7333 21.0722 25.3611 20.55 25.8833C20.0278 26.4056 19.4 26.6667 18.6667 26.6667H2.66667ZM9.33333 22.6667H12V21.3333H13.3333C13.7111 21.3333 14.0278 21.2056 14.2833 20.95C14.5389 20.6944 14.6667 20.3778 14.6667 20V16C14.6667 15.6222 14.5389 15.3056 14.2833 15.05C14.0278 14.7944 13.7111 14.6667 13.3333 14.6667H9.33333V13.3333H14.6667V10.6667H12V9.33333H9.33333V10.6667H8C7.62222 10.6667 7.30556 10.7944 7.05 11.05C6.79444 11.3056 6.66667 11.6222 6.66667 12V16C6.66667 16.3778 6.79444 16.6944 7.05 16.95C7.30556 17.2056 7.62222 17.3333 8 17.3333H12V18.6667H6.66667V21.3333H9.33333V22.6667ZM12.2333 8H17.5667L12.2333 2.66667V8Z" fill="#115640"/>
      </svg>
      <h1 class="text-[#115640] font-semibold lg:text-2xl text-lg">Finance</h1>
    </div>
    <div class="mt-2 flex items-center gap-3">
      <a href="{{ route('finance-requests.index') }}" class="text-[#757575] hover:no-underline">Finance Requests</a>
      <i class="fas fa-chevron-right text-[#757575]" style="font-size: 12px;"></i>
      <a href="{{ route('finance-requests.form', $financeRequest->id) }}" class="text-[#083224] underline">
        View Finance Request
      </a>
    </div>
  </div>

  @if(session('error'))
    <div class="mt-4 rounded-lg border border-[#FDD3D0] bg-[#FDECEC] p-3 text-[#900B09]">
      {{ session('error') }}
    </div>
  @endif

  @if(session('status') || session('success'))
    <div class="mt-4 rounded-lg border border-[#CFF7D3] bg-[#E7F3EE] p-3 text-[#115640]">
      {{ session('status') ?? session('success') }}
    </div>
  @endif

  {{-- FINANCE REQUEST INFO --}}
  <div class="bg-white border border-[#D9D9D9] rounded-lg my-4">
    <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] bg-[#115640] text-white rounded-tl-lg rounded-tr-lg">Finance Request Info</h1>
    <div class="p-3">
      <div class="border border-[#D9D9D9] rounded-lg overflow-hidden">
        <table class="w-full text-[#1E1E1E]">
          <tr class="border-b border-b-[#D9D9D9]">
            <th class="p-3 w-[28%]">Request ID</th>
            <td class="p-3">#{{ $financeRequest->id }}</td>
          </tr>
          <tr class="border-b border-b-[#D9D9D9]">
            <th class="p-3">Type</th>
            <td class="p-3">
              <span class="inline-flex rounded-sm px-3 py-1 font-semibold {{ $requestTypeClass }}">
                {{ $formatLabel($financeRequest->request_type) }}
              </span>
            </td>
          </tr>
          <tr class="border-b border-b-[#D9D9D9]">
            <th class="p-3">Status</th>
            <td class="p-3">
              <span class="inline-flex rounded-sm px-3 py-1 font-semibold {{ $requestStatusClass }}">
                {{ $formatLabel($financeRequest->status) }}
              </span>
            </td>
          </tr>
          <tr class="border-b border-b-[#D9D9D9]">
            <th class="p-3">Requester</th>
            <td class="p-3">{{ $financeRequest->requester->name ?? '-' }}</td>
          </tr>
          <tr class="border-b border-b-[#D9D9D9]">
            <th class="p-3">Approver</th>
            <td class="p-3">{{ $financeRequest->approver->name ?? '-' }}</td>
          </tr>
          <tr class="border-b border-b-[#D9D9D9]">
            <th class="p-3">Reference ID</th>
            <td class="p-3">{{ $financeRequest->reference_id ?? '-' }}</td>
          </tr>
          <tr class="border-b border-b-[#D9D9D9]">
            <th class="p-3">Requested At</th>
            <td class="p-3">{{ $formatDate($financeRequest->created_at) }}</td>
          </tr>
          <tr class="border-b border-b-[#D9D9D9]">
            <th class="p-3">Decided At</th>
            <td class="p-3">{{ $formatDate($financeRequest->decided_at) }}</td>
          </tr>
          <tr>
            <th class="p-3">Decision Notes</th>
            <td class="p-3">{{ $financeRequest->notes ?? '-' }}</td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  @if($resolvedOrder)
    {{-- ORDER INFO --}}
    <div class="bg-white border border-[#D9D9D9] rounded-lg my-4">
      <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] bg-[#115640] text-white rounded-tl-lg rounded-tr-lg">Order Info</h1>
      <div class="p-3">
        <div class="border border-[#D9D9D9] rounded-lg overflow-hidden">
          <table class="w-full text-[#1E1E1E]">
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3 w-[28%]">Order No</th>
              <td class="p-3">{{ $resolvedOrder->order_no }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Customer</th>
              <td class="p-3">{{ $resolvedOrder->lead->name ?? '-' }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Quotation No</th>
              <td class="p-3">{{ $resolvedQuotation->quotation_no ?? '-' }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Total Billing</th>
              <td class="p-3">{{ $formatMoney($resolvedOrder->total_billing) }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Status</th>
              <td class="p-3">{{ $formatLabel($resolvedOrder->order_status) }}</td>
            </tr>
            <tr>
              <th class="p-3">Created At</th>
              <td class="p-3">{{ $formatDate($resolvedOrder->created_at) }}</td>
            </tr>
          </table>
        </div>
      </div>
    </div>
  @endif

  @if($displayItems->isNotEmpty())
    {{-- ORDER ITEMS --}}
    <div class="bg-white border border-[#D9D9D9] rounded-lg mt-4">
      <h1 class="font-semibold uppercase p-3 border-b border-b-[#D9D9D9] bg-[#115640] text-white rounded-tl-lg rounded-tr-lg">Order Items</h1>
      <div class="p-3">
        <div class="border border-[#D9D9D9] rounded-lg overflow-x-auto">
          <table class="w-full min-w-[760px]">
            <thead>
              <tr class="border-b border-b-[#D9D9D9]">
                <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">#</th>
                <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">Description</th>
                <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">Qty</th>
                <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">Unit Price</th>
                <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">Disc (%)</th>
                <th class="lg:p-3 p-2 font-semibold text-[#1E1E1E]">Total</th>
              </tr>
            </thead>
            <tbody>
              @foreach($displayItems as $index => $item)
                @php
                  $lineTotal = $item->line_total ?? $item->total_price ?? ((float) ($item->qty ?? 0) * (float) ($item->unit_price ?? 0));
                @endphp
                <tr class="border-b border-b-[#D9D9D9]">
                  <td class="lg:p-3 p-2 text-[#1E1E1E]">{{ $index + 1 }}</td>
                  <td class="lg:p-3 p-2 text-[#1E1E1E]">{{ $item->description ?? '-' }}</td>
                  <td class="lg:p-3 p-2 text-[#1E1E1E]">{{ $item->qty ?? '-' }}</td>
                  <td class="lg:p-3 p-2 text-[#1E1E1E]">{{ $formatMoney($item->unit_price ?? null) }}</td>
                  <td class="lg:p-3 p-2 text-[#1E1E1E]">{{ $item->discount_pct ?? 0 }}</td>
                  <td class="lg:p-3 p-2 text-[#1E1E1E]">{{ $formatMoney($lineTotal) }}</td>
                </tr>
              @endforeach
            </tbody>
            @if($resolvedQuotation)
              <tfoot>
                <tr class="border-b border-b-[#D9D9D9]">
                  <th class="text-[#1E1E1E] font-semibold lg:p-3 p-2">Sub Total</th>
                  <th></th><th></th><th></th><th></th>
                  <th class="text-[#1E1E1E] font-semibold lg:p-3 p-2">{{ $formatMoney($resolvedQuotation->subtotal) }}</th>
                </tr>
                <tr class="border-b border-b-[#D9D9D9]">
                  <th class="lg:p-3 p-2 text-[#1E1E1E] font-semibold">Tax ({{ $resolvedQuotation->tax_pct ?? 0 }}%)</th>
                  <th></th><th></th><th></th><th></th>
                  <th class="lg:p-3 p-2 text-[#1E1E1E] font-semibold">{{ $formatMoney($resolvedQuotation->tax_total) }}</th>
                </tr>
                <tr class="border-b border-b-[#D9D9D9]">
                  <th class="lg:p-3 p-2 text-[#1E1E1E] font-semibold">Discount</th>
                  <th></th><th></th><th></th><th></th>
                  <th class="lg:p-3 p-2 text-[#900B09] font-semibold">- {{ $formatMoney($resolvedQuotation->total_discount ?? 0) }}</th>
                </tr>
                <tr>
                  <th class="lg:p-3 p-2 text-[#1E1E1E] font-semibold">Grand Total</th>
                  <th></th><th></th><th></th><th></th>
                  <th class="lg:p-3 p-2 text-[#1E1E1E] font-semibold">{{ $formatMoney($resolvedQuotation->grand_total) }}</th>
                </tr>
              </tfoot>
            @endif
          </table>
        </div>
      </div>
    </div>
  @endif

  @if($resolvedProforma)
    {{-- PROFORMA INFO --}}
    <div class="bg-white border border-[#D9D9D9] rounded-lg my-4">
      <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] bg-[#115640] text-white rounded-tl-lg rounded-tr-lg">Proforma Info</h1>
      <div class="p-3">
        <div class="border border-[#D9D9D9] rounded-lg overflow-hidden">
          <table class="w-full text-[#1E1E1E]">
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3 w-[28%]">Proforma No</th>
              <td class="p-3">{{ $resolvedProforma->proforma_no ?? '-' }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Term</th>
              <td class="p-3">{{ $resolvedProforma->term_no ?? 'Booking Fee' }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Type</th>
              <td class="p-3">{{ $formatLabel($resolvedProforma->proforma_type) }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Status</th>
              <td class="p-3">{{ $formatLabel($resolvedProforma->status) }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Amount</th>
              <td class="p-3">{{ $formatMoney($resolvedProforma->amount) }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Issued At</th>
              <td class="p-3">{{ $formatDate($resolvedProforma->issued_at, 'd M Y') }}</td>
            </tr>
            <tr>
              <th class="p-3">Download</th>
              <td class="p-3">
                @if($resolvedProforma->attachment_id)
                  <a href="{{ route('attachments.download', $resolvedProforma->attachment_id) }}" class="inline-flex items-center gap-2 rounded-lg bg-[#115640] px-3 py-2 text-white">
                    <x-icon.download/>
                    Download Proforma
                  </a>
                @else
                  -
                @endif
              </td>
            </tr>
          </table>
        </div>
      </div>
    </div>
  @endif

  @if($termRows->isNotEmpty())
    {{-- TERM SUMMARY --}}
    <div class="bg-white border border-[#D9D9D9] rounded-lg mt-4">
      <h1 class="font-semibold uppercase p-3 border-b border-b-[#D9D9D9] bg-[#115640] text-white rounded-tl-lg rounded-tr-lg">Term Summary</h1>
      <div class="p-3">
        <div class="border border-[#D9D9D9] rounded-lg overflow-x-auto">
          <table class="w-full min-w-[680px]">
            <thead>
              <tr class="border-b border-b-[#D9D9D9]">
                <th class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">Term</th>
                <th class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">Percentage</th>
                <th class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">Amount</th>
                <th class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">Paid At</th>
                <th class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach($termRows as $row)
                <tr class="border-t border-t-[#D9D9D9]">
                  <td class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">Term {{ $row['term_no'] ?? '-' }}</td>
                  <td class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">{{ $row['percentage'] !== null ? $row['percentage'] . '%' : '-' }}</td>
                  <td class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">{{ $formatMoney($row['amount']) }}</td>
                  <td class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">{{ $formatDate($row['paid_at'], 'd M Y') }}</td>
                  <td class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">{{ $formatLabel($row['status']) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif

  @if($resolvedPayment)
    {{-- PAYMENT CONFIRMATION DETAILS --}}
    <div class="bg-white border border-[#D9D9D9] rounded-lg my-4">
      <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] bg-[#115640] text-white rounded-tl-lg rounded-tr-lg">Payment Confirmation Details</h1>
      <div class="p-3">
        <div class="border border-[#D9D9D9] rounded-lg overflow-hidden">
          <table class="w-full text-[#1E1E1E]">
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3 w-[28%]">Payer Name</th>
              <td class="p-3">{{ $resolvedPayment->payer_name ?? '-' }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Payer Bank</th>
              <td class="p-3">{{ $resolvedPayment->payer_bank ?? '-' }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Account Number</th>
              <td class="p-3">{{ $resolvedPayment->payer_account_number ?? '-' }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Paid At</th>
              <td class="p-3">{{ $formatDate($resolvedPayment->paid_at, 'd M Y') }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Confirmed At</th>
              <td class="p-3">{{ $formatDate($resolvedPayment->confirmed_at) }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Amount</th>
              <td class="p-3">{{ $formatMoney($resolvedPayment->amount) }}</td>
            </tr>
            <tr>
              <th class="p-3">Evidence</th>
              <td class="p-3">
                @if($resolvedPayment->attachment_id)
                  <a href="{{ route('attachments.download', $resolvedPayment->attachment_id) }}" class="inline-flex items-center gap-2 rounded-lg bg-[#115640] px-3 py-2 text-white">
                    <x-icon.download/>
                    Download Proof
                  </a>
                @else
                  -
                @endif
              </td>
            </tr>
          </table>
        </div>
      </div>
    </div>
  @endif

  @if($resolvedInvoice)
    {{-- INVOICE --}}
    <div class="bg-white border border-[#D9D9D9] rounded-lg my-4">
      <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] bg-[#115640] text-white rounded-tl-lg rounded-tr-lg">Invoice</h1>
      <div class="p-3">
        <div class="border border-[#D9D9D9] rounded-lg overflow-hidden">
          <table class="w-full text-[#1E1E1E]">
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3 w-[28%]">Invoice No</th>
              <td class="p-3">{{ $resolvedInvoice->invoice_no ?? '-' }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Type</th>
              <td class="p-3">{{ $formatLabel($resolvedInvoice->invoice_type ?? null) }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Status</th>
              <td class="p-3">{{ $formatLabel($resolvedInvoice->status ?? null) }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Issued At</th>
              <td class="p-3">{{ $formatDate($resolvedInvoice->issued_at, 'd M Y') }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Amount</th>
              <td class="p-3">{{ $formatMoney($resolvedInvoice->amount) }}</td>
            </tr>
            <tr>
              <th class="p-3">Download</th>
              <td class="p-3">
                @if($resolvedInvoice->attachment_id)
                  <a href="{{ route('attachments.download', $resolvedInvoice->attachment_id) }}" class="inline-flex items-center gap-2 rounded-lg bg-[#115640] px-3 py-2 text-white">
                    <x-icon.download/>
                    Download Invoice
                  </a>
                @else
                  -
                @endif
              </td>
            </tr>
          </table>
        </div>
      </div>
    </div>
  @endif

  @if($meetingExpense)
    {{-- MEETING EXPENSE DETAILS --}}
    <div class="bg-white border border-[#D9D9D9] rounded-lg my-4">
      <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] bg-[#115640] text-white rounded-tl-lg rounded-tr-lg">Meeting Expense Details</h1>
      <div class="p-3">
        <div class="border border-[#D9D9D9] rounded-lg overflow-hidden mb-4">
          <table class="w-full text-[#1E1E1E]">
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3 w-[28%]">Lead</th>
              <td class="p-3">{{ $meetingExpense->meeting->lead->name ?? '-' }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Sales</th>
              <td class="p-3">{{ $meetingExpense->sales->name ?? '-' }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Meeting Date</th>
              <td class="p-3">{{ $formatDate($meetingExpense->meeting->scheduled_start_at ?? null) }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Total Amount</th>
              <td class="p-3">{{ $formatMoney($meetingExpense->amount) }}</td>
            </tr>
            <tr>
              <th class="p-3">Status</th>
              <td class="p-3">{{ $formatLabel($meetingExpense->status) }}</td>
            </tr>
          </table>
        </div>

        <div class="border border-[#D9D9D9] rounded-lg overflow-x-auto">
          <table class="w-full min-w-[620px]">
            <thead>
              <tr class="border-b border-b-[#D9D9D9]">
                <th class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">Type</th>
                <th class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">Notes</th>
                <th class="p-2 lg:p-3 font-semibold text-[#1E1E1E]">Amount</th>
              </tr>
            </thead>
            <tbody>
              @forelse($meetingExpense->details as $detail)
                <tr class="border-t border-t-[#D9D9D9]">
                  <td class="p-2 lg:p-3 text-[#1E1E1E]">{{ $detail->expenseType->name ?? '-' }}</td>
                  <td class="p-2 lg:p-3 text-[#1E1E1E]">{{ $detail->notes ?? '-' }}</td>
                  <td class="p-2 lg:p-3 text-[#1E1E1E]">{{ $formatMoney($detail->amount) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="p-3 text-center text-[#757575]">No expense breakdown found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif

  @if($financeRequest->status === 'pending')
    @if(auth()->user()?->role?->code === 'finance')
      {{-- APPROVAL SECTION --}}
      <div class="bg-white border border-[#D9D9D9] rounded-lg my-4">
        <h1 class="uppercase text-[#1E1E1E] font-semibold p-3 border-b border-b-[#D9D9D9]">Approval Section</h1>
        <div class="grid grid-cols-1 p-3">
          <textarea id="main_notes" name="notes" class="w-full! px-3! py-2! rounded-lg! border! border-[#D9D9D9]! focus:outline-none!" rows="8" placeholder="Enter notes..." required></textarea>

          <div class="px-3 py-1 flex items-center justify-end gap-3">
            <form method="POST" id="reject" action="{{ route('finance-requests.reject', $financeRequest->id) }}" require-confirmation="true">
              @csrf
              <input type="hidden" name="notes" class="hidden_notes">
              <div class="flex justify-end mt-3">
                <button class="cursor-pointer bg-[#900B09] text-white px-3 py-2 rounded-lg">
                  Reject
                </button>
              </div>
            </form>
            <form method="POST" id="approve" action="{{ route('finance-requests.approve', $financeRequest->id) }}" require-confirmation="true">
              @csrf
              <input type="hidden" name="notes" class="hidden_notes">
              <div class="flex justify-end mt-3">
                <button class="cursor-pointer bg-[#115640] text-white px-3 py-2 rounded-lg">
                  Approve
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    @endif
  @endif

</section>
@endsection

@section('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const mainNotes = document.getElementById('main_notes');
    const hiddenNotes = document.querySelectorAll('.hidden_notes');
    const forms = document.querySelectorAll('form[require-confirmation="true"]');

    if (!mainNotes) {
      return;
    }

    mainNotes.addEventListener('input', function () {
      hiddenNotes.forEach(function (input) {
        input.value = mainNotes.value;
      });
    });

    forms.forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (mainNotes.value.trim() === '') {
          event.preventDefault();
          alert('Notes wajib diisi!');
          mainNotes.focus();
          return;
        }

        hiddenNotes.forEach(function (input) {
          input.value = mainNotes.value;
        });
      });
    });
  });
</script>
@endsection
