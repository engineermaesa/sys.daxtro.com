@extends('layouts.app')

@section('content')
    <section class="min-h-screen">
        <div class="pt-4">
            <div class="flex items-center gap-3">
                <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12ZM2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V4C0 3.45 0.195833 2.97917 0.5875 2.5875C0.979167 2.19583 1.45 2 2 2H3V1C3 0.716667 3.09583 0.479167 3.2875 0.2875C3.47917 0.0958333 3.71667 0 4 0C4.28333 0 4.52083 0.0958333 4.7125 0.2875C4.90417 0.479167 5 0.716667 5 1V2H13V1C13 0.716667 13.0958 0.479167 13.2875 0.2875C13.4792 0.0958333 13.7167 0 14 0C14.2833 0 14.5208 0.0958333 14.7125 0.2875C14.9042 0.479167 15 0.716667 15 1V2H16C16.55 2 17.0208 2.19583 17.4125 2.5875C17.8042 2.97917 18 3.45 18 4V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2Z"
                        fill="#115640" />
                </svg>
                <h1 class="text-[#115640] font-semibold text-2xl">Leads</h1>
            </div>
            <div class="flex items-center mt-2 gap-3">
                <a href="javascript:history.back()" class="text-[#757575] hover:no-underline">My Leads</a>
                <i class="fas fa-chevron-right text-[#757575]" style="font-size: 12px;"></i>
                <a href="/" class="text-[#083224] underline">
                    View Quotation
                </a>
            </div>
            @if ($quotation->status === 'rejected' && isset($rejection))
                <div class="w-full flex items-center p-3 bg-[#FDD3D0] border border-[#900B09] rounded-lg mt-3 text-[#900B09] gap-5">
                    <x-icon.info/>
                    <div>
                        <p>
                            Quotation rejected by 
                            <b>
                                {{ $rejection->reviewer->name ?? $rejection->role }}
                            </b>
                            <span>
                                on {{ $rejection->decided_at ? \Carbon\Carbon::parse($rejection->decided_at)->format('d M Y') : '' }}
                            </span>
                        </p>
                        <p>
                            <strong>Notes:</strong> {{ $rejection->notes }}
                        </p>
                    </div>
                </div>
            @elseif(in_array($quotation->status, ['review', 'pending_finance']))
                @php
                    $roleCode = auth()->user()->role?->code;
                    $bmReview = $quotation->reviews->where('role', 'BM')->sortByDesc('decided_at')->first();
                    $financeReview = $quotation->reviews->where('role', 'finance')->sortByDesc('decided_at')->first();
                @endphp
                <div class="w-full flex items-center p-3 bg-[#FFFBEB] border border-[#BF6A02] rounded-lg mt-3 text-[#522504] gap-5">
                    <x-icon.info/>
                    <div>
                        <strong>Quotation Under Review.</strong>
                        <p>
                            Branch Manager: 
                            <strong>
                                {{ $bmReview ? ucfirst                  ($bmReview->decision) : 'Pending' }}
                                @if ($quotation->status === 'review')
                                    (Waiting for approval)
                                @endif
                            </strong>
                        </p>
                        <p>
                            Finance: 
                            <strong>
                                {{ $financeReview ? ucfirst($financeReview->decision) : 'Pending' }}
                                @if ($quotation->status === 'pending_finance')
                                    (Waiting for approval)
                                @endif
                            </strong>
                        </p>

                        @if ($roleCode === 'branch_manager' && !$bmReview && $quotation->status === 'review')
                            You can <strong>approve</strong> or <strong>reject</strong> this quotation using the buttons at the bottom of the page.
                        @elseif ($roleCode === 'finance' && $bmReview && $bmReview->decision === 'approve' && !$financeReview && $quotation->status === 'pending_finance')
                            You can <strong>approve</strong> or <strong>reject</strong> this quotation using the buttons at the bottom of the page.
                        @elseif ($quotation->status === 'review')
                            Please wait for Branch Manager approval.
                        @elseif ($quotation->status === 'pending_finance')
                            Please wait for Finance approval.
                        @endif
                    </div>
                </div>
            @elseif($quotation && $quotation->status === 'published' && $quotation->reviews->count())
                @php
                    $approval = $quotation->reviews
                        ->where('decision', 'approve')
                        ->sortByDesc('decided_at')
                        ->first();
                @endphp
                @if ($approval)
                    <div class="w-full flex items-center p-3 bg-[#CFF7D3] border border-[#02542D] rounded-lg mt-3 text-[#02542D] gap-5">
                        <x-icon.info/>
                        <div>
                            <p>
                                Quotation published on
                                {{ $approval->decided_at ? \Carbon\Carbon::parse($approval->decided_at)->format('d M Y H:i:s') : '-' }}
                                WIB
                            </p>
                            <p>
                                <strong>Notes:</strong> 
                                <span>
                                    {{ $approval->notes }}
                                </span>
                            </p>
                        </div>
                    </div>
                @endif
            @endif

            {{-- QUOTATIONS ITEMS --}}
            <div class="bg-white border border-[#D9D9D9] rounded-lg mt-4">
                <h1 class="font-semibold uppercase text-[#1E1E1E] p-3 border-b border-b-[#D9D9D9]">Quotation Items</h1>
                <div class="p-3">
                    <div class="border border-[#D9D9D9] rounded-lg">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-b-[#D9D9D9]">
                                    <th class="p-3 font-semibold text-[#1E1E1E]">Product</th>
                                    <th class="p-3 font-semibold text-[#1E1E1E]">Qty</th>
                                    <th class="p-3 font-semibold text-[#1E1E1E]">Unit Price (Rp)</th>
                                    <th class="p-3 font-semibold text-[#1E1E1E]">Disc %</th>
                                    <th class="p-3 font-semibold text-[#1E1E1E]">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($quotation->items as $item)
                                    <tr class="border-b border-b-[#D9D9D9]">
                                        <td class="p-3 text-[#1E1E1E]">{{ $item->description }}</td>
                                        <td class="p-3 text-[#1E1E1E]">{{ $item->qty }}</td>
                                        <td class="p-3 text-[#1E1E1E]">Rp{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                        <td class="p-3 text-[#1E1E1E]">{{ $item->discount_pct }}</td>
                                        <td class="p-3 text-[#1E1E1E]">Rp{{ number_format($item->line_total, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-b border-b-[#D9D9D9]">
                                    <th class="text-[#1E1E1E] font-semibold p-3">
                                        Sub Total
                                    </th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th class="text-[#1E1E1E] font-semibold p-3">
                                        Rp{{ number_format($quotation->subtotal, 0, ',', '.') }}
                                    </th>
                                </tr>
                                <tr class="border-b border-b-[#D9D9D9]">
                                    <th class="p-3 text-[#1E1E1E] font-semibold">Tax ({{ $quotation->tax_pct }}%)</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th class="p-3 text-[#1E1E1E] font-semibold">Rp{{ number_format($quotation->tax_total, 0, ',', '.') }}</th>
                                </tr>
                                @if (!empty($quotation->discount))
                                    <tr class="border-b border-b-[#D9D9D9]">
                                        <th class="p-3 text-[#1E1E1E] font-semibold">Discount</th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th class="p-3 text-danger text-[#1E1E1E] font-semibold">- Rp{{ number_format($quotation->discount, 0, ',', '.') }}
                                        </th>
                                    </tr>
                                @endif
                                <tr>
                                    <th class="p-3 text-[#1E1E1E] font-semibold">Grand Total</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th class="p-3 text-[#1E1E1E] font-semibold">Rp{{ number_format($quotation->grand_total, 0, ',', '.') }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- FINANCIAL SUMMARY AND PAYMENT TERMS --}}
            <div class="grid grid-cols-2 gap-5 mt-4">
                
                {{-- FINANCIAL SUMMARY SECTION --}}
                @php
                    $statusLabel = [
                        'pending_finance' => 'Pending Finance',
                    ][$quotation->status] ?? ucfirst($quotation->status);
                @endphp
                <div class="bg-white border border-[#D9D9D9] rounded-lg">
                    <h1 class="uppercase text-[#1E1E1E] font-semibold p-3 border-b border-b-[#D9D9D9]">Financial Summary</h1>
                    <div class="p-3">
                        <table class="w-full">
                            <tr class="border-b border-b-white">
                                <th class="bg-[#115640] text-white py-1 px-5 rounded-tl-lg font-normal!">No</th>
                                <td class="bg-[#F5F5F5] text-[#1E1E1E] py-1 px-5 rounded-tr-lg font-normal!">{{ $quotation->quotation_no }}</td>
                            </tr>
                            <tr class="border-b border-b-white">
                                <th class="bg-[#115640] text-white py-1 px-5 font-normal!">Status</th>
                                <td class="bg-[#F5F5F5] text-[#1E1E1E] py-1 px-5 font-normal!">
                                    {{ $statusLabel }}
                                </td>
                            </tr>
                            <tr class="border-b border-b-white">
                                <th class="bg-[#115640] text-white py-1 px-5 font-normal!">Customer</th>
                                <td class="bg-[#F5F5F5] text-[#1E1E1E] py-1 px-5 font-normal!">
                                    {{ $quotation->lead->name ?? '-' }}
                                </td>
                            </tr>
                            <tr class="border-b border-b-white">
                                <th class="bg-[#115640] text-white py-1 px-5 font-normal!">Sub Total</th>
                                <td class="bg-[#F5F5F5] text-[#1E1E1E] py-1 px-5 font-normal!">
                                    Rp{{ number_format($quotation->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr class="border-b border-b-white">
                                <th class="bg-[#115640] text-white py-1 px-5 font-normal!">Tax ({{ $quotation->tax_pct }}%)</th>
                                <td class="bg-[#F5F5F5] text-[#1E1E1E] py-1 px-5 font-normal!">
                                    Rp{{ number_format($quotation->tax_total, 0, ',', '.') }}
                                </td>
                            </tr>
                            @if (!empty($quotation->total_discount))
                                <tr class="border-b border-b-white">
                                    <th class="bg-[#115640] text-white py-1 px-5 font-normal!">Total Discount</th>
                                    <td class="bg-[#F5F5F5] text-[#900B09] py-1 px-5 font-semibold!">
                                        Rp{{ number_format($quotation->total_discount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endif
                            <tr class="border-b border-b-white">
                                <th class="bg-[#115640] text-white py-1 px-5 font-normal!">Grand Total</th>
                                <td class="bg-[#F5F5F5] text-[#1E1E1E] py-1 px-5 font-semibold!">
                                    Rp{{ number_format($quotation->grand_total, 0, ',', '.') }}
                                </td>
                            </tr>
                            @if (!empty($quotation->booking_fee))
                                <tr class="border-b border-b-white">
                                    <th class="bg-[#115640] text-white py-1 px-5 font-normal!">Payment Type</th>
                                    <td class="bg-[#F5F5F5] text-[#1E1E1E] py-1 px-5 font-normal!">
                                        Booking Fee | Rp {{ number_format($quotation->booking_fee, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @else
                                <tr class="border-b border-b-white">
                                    <th class="bg-[#115640] text-white py-1 px-5 font-normal!">Payment Type</th>
                                    <td class="bg-[#F5F5F5] text-[#1E1E1E] py-1 px-5 font-normal!">
                                        Direct Down Payment
                                    </td>
                                </tr>
                            @endif
                            <tr class="border-b border-b-white">
                                <th class="bg-[#115640] text-white py-1 px-5 rounded-bl-lg font-normal!">Expiry Date</th>
                                <td class="bg-[#F5F5F5] text-[#1E1E1E] py-1 px-5 rounded-br-lg font-normal!">
                                    {{ $quotation->expiry_date ? date('d M Y', strtotime($quotation->expiry_date)) : '-' }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                {{-- PAYMENT TERMS SECTIONS --}}
                <div class="bg-white border border-[#D9D9D9] rounded-lg">
                    <h1 class="uppercase text-[#1E1E1E] font-semibold p-3 border-b border-b-[#D9D9D9]">Payment Terms</h1>
                    <div class="p-3">
                        <div class="border border-[#D9D9D9] rounded-lg">
                            <table class="w-full">
                                <thead>
                                    <tr>
                                        <th class="p-2 text-[#1E1E1E] font-semibold">Term</th>
                                        <th class="p-2 text-[#1E1E1E] font-semibold">Percentage</th>
                                        <th class="p-2 text-[#1E1E1E] font-semibold">Total (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($quotation->paymentTerms as $term)
                                        <tr class="border-t border-t-[#D9D9D9]">
                                            <td class="p-2 text-[#1E1E1E]">{{ $term->term_no }}</td>
                                            <td class="p-2 text-[#1E1E1E]">{{ $term->percentage }}%</td>
                                            @if($quotation->booking_fee)
                                                @if( $term->term_no === 1 )
                                                    <td class="p-2 text-[#1E1E1E]">
                                                        Rp{{ number_format(((($quotation->grand_total * $term->percentage)  / 100) - $quotation->booking_fee), 0, ',', '.') }}
                                                    </td>
                                                @else
                                                    <td class="p-2 text-[#1E1E1E]">
                                                        Rp{{ number_format(($quotation->grand_total * $term->percentage) / 100, 0, ',', '.') }}
                                                    </td>
                                                @endif
                                            @else
                                                <td class="p-2 text-[#1E1E1E]">
                                                    Rp{{ number_format(($quotation->grand_total * $term->percentage) / 100, 0, ',', '.') }}
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- PROFORMAS SECTION --}}
            @php
                $isSales = auth()->user()->role?->code === 'sales';
                $orderId = $quotation->order->id ?? null;
            @endphp
            
            @if ($quotation->proformas->count())
                <div class="bg-white border border-[#D9D9D9] rounded-lg mt-4">
                    <h1 class="font-semibold uppercase text-[#1E1E1E] p-3 border-b border-b-[#D9D9D9]">Proformas</h1>
                    <div class="p-3">
                        <div class="border border-[#D9D9D9] rounded-lg">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-b-[#D9D9D9]">
                                        <th class="p-3 font-semibold text-[#1E1E1E]">Term</th>
                                        <th class="p-3 font-semibold text-[#1E1E1E]">No</th>
                                        <th class="p-3 font-semibold text-[#1E1E1E]">Issued</th>
                                        <th class="p-3 font-semibold text-[#1E1E1E]">Amount</th>
                                        <th class="p-3 font-semibold text-[#1E1E1E]">Status</th>
                                        <th class="p-3 font-semibold text-[#1E1E1E]">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($quotation->proformas as $pf)
                                        <tr class="border-t border-t-[#D9D9D9]">
                                            <td class="p-3 text-[#1E1E1E] text-center">{{ $pf->term_no ?? 'Booking Fee' }}</td>
                                            <td class="p-3 text-[#1E1E1E]">{{ $pf->proforma_no ?? '-' }}</td>
                                            <td class="p-3 text-[#1E1E1E]">{{ $pf->issued_at ? date('d M Y', strtotime($pf->issued_at)) : '-' }}</td>
                                            <td class="p-3 text-[#1E1E1E]">Rp{{ number_format($pf->amount, 0, ',', '.') }} </td>
                                            @if ($pf->paymentConfirmation)
                                                <td class="p-3">
                                                    @php $fr = $pf->paymentConfirmation->financeRequest; @endphp

                                                    @if ($fr && in_array($fr->status, ['approved','rejected']))
                                                        <span class="{{ $fr->status === 'approved' ? 'span-deal' : 'span-hot' }} mt-1">
                                                            {{ ucfirst($fr->status) }}: {{ $fr->notes }}
                                                        </span>

                                                    @elseif($fr)
                                                        <span class="span-warm">Awaiting Finance</span>

                                                    @endif
                                                    @if ($fr && $fr->status !== 'rejected')
                                                        <div class="mt-2">
                                                            @if (isset($pf->paymentConfirmation->confirmedBy?->name))
                                                                <span class="text-[#02542D] block">
                                                                    Paid at: {{ $pf->paymentConfirmation->paid_at->format('d M Y') }}
                                                                </span>
                                                                <span class="text-[#02542D] block">
                                                                    Confirmed by: {{ $pf->paymentConfirmation->confirmedBy?->name ?? '-' }}
                                                                </span>
                                                            @else
                                                                <span class="text-[#682D03] block">
                                                                    Paid at: {{ $pf->paymentConfirmation->paid_at->format('d M Y') }}
                                                                </span>
                                                                <span class="text-[#682D03] block">
                                                                    Pending Finance Confirmation
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </td>
                                            @else
                                                <td class="p-3">
                                                    -
                                                </td>
                                            @endif
                                            </td>
                                            <td class="flex items-center gap-3 p-3">
                                                @if ($pf->attachment_id)
                                                    <a href="{{ route('attachments.download', $pf->attachment_id) }}"
                                                        class="flex items-center gap-2 text-[#1E1E1E] px-3 py-2 duration-300 border border-[#05261B] hover:bg-[#CFE7DE] hover:border hover:border-[#05261B] rounded-lg">
                                                        <x-icon.download/>
                                                        Proforma
                                                    </a>

                                                    @if ($pf->invoice && $pf->invoice->attachment_id)
                                                        <a href="{{ route('attachments.download', $pf->invoice->attachment_id) }}"
                                                            class="flex items-center gap-2 text-[#1E1E1E] px-3 py-2 duration-300 border border-[#05261B] hover:bg-[#CFE7DE] hover:border hover:border-[#05261B] rounded-lg">
                                                            <x-icon.download/>
                                                            Download Invoice
                                                        </a>
                                                    @endif

                                                    @if ($pf->status === 'confirmed')
                                                        @php $fr = $pf->paymentConfirmation?->financeRequest; @endphp
                                                        @if (!$pf->paymentConfirmation && $isSales)
                                                            <a href="{{ route('payment-confirmation.terms.payment.confirm.form', [$quotation->lead_id, $pf->term_no ?? 'bf']) }}"
                                                                class="flex items-center gap-2 text-white bg-[#115640] px-3 py-2 duration-300 border border-[#05261B] hover:bg-[#0D4433] hover:border hover:border-[#05261B] rounded-lg">
                                                                <x-icon.dollar/> 
                                                                Confirm Payment
                                                            </a>
                                                        @endif

                                                        @if ($pf->paymentConfirmation)
                                                            @if ( $fr && $fr->status === 'rejected' )
                                                                <a href="{{ route('payment-confirmation.terms.payment.confirm.form', [$quotation->lead_id, $pf->term_no ?? 'bf']) }}"
                                                                    class="flex items-center gap-2 text-[#1E1E1E] px-3 py-2 duration-300 border border-[#05261B] hover:bg-[#CFE7DE] hover:border hover:border-[#05261B] rounded-lg">
                                                                        <x-icon.edit/>
                                                                        <span>Edit Payment</span>
                                                                </a>
                                                            @else
                                                                <a href="{{ route('payment-confirmation.terms.payment.confirm.form', [$quotation->lead_id, $pf->term_no ?? 'bf']) }}"
                                                                    class="flex items-center gap-2 text-[#1E1E1E] px-3 py-2 duration-300 border border-[#05261B] hover:bg-[#CFE7DE] hover:border hover:border-[#05261B] rounded-lg">
                                                                        <x-icon.detail/>
                                                                        <span>View Payment</span>
                                                                </a>
                                                            @endif
                                                        @endif
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- PAYMENT LOGS SECTION --}}
            @if ($quotation->paymentLogs->count())
                <div class="bg-white border border-[#D9D9D9] rounded-lg mt-4">
                    <h1 class="uppercase text-[#1E1E1E] font-semibold p-3 border-b border-b-[#D9D9D9]">Payment Logs</h1>
                    <div class="p-3">
                            <div class="border border-[#D9D9D9] rounded-lg">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b border-b-[#D9D9D9]">
                                            <th class="p-3 font-semibold text-[#1E1E1E]">Date</th>
                                            <th class="p-3 font-semibold text-[#1E1E1E]">Type</th>
                                            <th class="p-3 font-semibold text-[#1E1E1E]">Detail</th>
                                            <th class="p-3 font-semibold text-[#1E1E1E]">User</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($quotation->paymentLogs as $log)
                                            <tr class="border-t border-t-[#D9D9D9] text-[#1E1E1E]">
                                                <td class="p-3">
                                                    {{ $log->logged_at ? \Carbon\Carbon::parse($log->logged_at)->format('d M Y H:i') : '-' }}
                                                </td>
                                                <td class="p-3">
                                                    {{ ucfirst($log->type) }}
                                                </td>
                                                <td class="p-3">
                                                    @if($log->type === 'proforma')
                                                        {{ $log->proforma?->proforma_no }}
                                                    @elseif($log->type === 'invoice')
                                                        {{ $log->invoice?->invoice_no }}
                                                    @elseif($log->type === 'confirmation')
                                                        {{ $log->proforma?->term_no ? 'Term ' . $log->proforma->term_no : 'Booking Fee' }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="p-3">{{ $log->user->name ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>


                </div>
            @endif

            {{-- SIGNED DOCUMENT SECTION --}}
            
            @if ($quotation->signedDocuments->count())
                <div class="bg-white border border-[#D9D9D9] rounded-lg my-4">
                    <h1 class="uppercase text-[#1E1E1E] font-semibold p-3 border-b border-b-[#D9D9D9]">Signed Document</h1>
                    <div class="flex items-center flex-wrap gap-3 text-[#1E1E1E] px-3 py-4">
                        @foreach ($quotation->signedDocuments as $doc)
                            <div class="p-3 border border-[#D9D9D9] rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1">
                                        <x-icon.user/>
                                        <p>{{ $doc->uploader?->name }}</p>
                                    </div>
                                    <div>
                                        <a href="{{ route('attachments.download', $doc->attachment_id) }}">
                                            <x-icon.download/>
                                        </a>
                                    </div>
                                </div>
                                <p class="font-semibold my-2">
                                    {{ basename($doc->attachment?->file_path ?? '') }}
                                </p>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p>Signed</p>
                                        <p>Description</p>
                                    </div>
                                    <div>
                                        <p>:</p>
                                        <p>:</p>
                                    </div>
                                    <div>
                                        <p>{{ $doc->signed_date ? date('d M Y', strtotime($doc->signed_date)) : '-' }}</p>
                                        <p>{{ $doc->description ? $doc->description : '-'}}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            
            {{-- FORM TO CREATE SIGNED DOCUMENT --}}
            @if(auth()->user()->role?->code === 'sales' && isset($claim))
                <div class="bg-white border border-[#D9D9D9] rounded-lg my-4">
                    <h1 class="uppercase text-[#1E1E1E] font-semibold px-3 py-2 border-b border-b-[#D9D9D9]">Upload Signed Document</h1>
                    <form action="{{ route('quotations.signed-documents.upload', $quotation->id) }}" method="POST" enctype="multipart/form-data">
                        <div class="flex justify-between items-end gap-3">
                            <div class="w-[30%] px-3 py-2">
                                <label for="signed_file" class="text-[#1E1E1E]!">
                                    Signed File
                                    <span class="text-[#900B09]">*</span>
                                </label>
                                <div class="flex items-center">
                                    <input type="file" class=" @error('file') is-invalid @enderror w-full
                                    cursor-pointer
                                    border border-[#D9D9D9]
                                    rounded-lg
                                    text-sm text-gray-500
                                    file:mr-4
                                    file:py-2
                                    file:px-3
                                    file:border-0
                                    file:text-sm
                                    file:font-medium
                                    file:bg-[#F5F5F5]
                                    file:text-[#1E1E1E]
                                    hover:file:bg-gray-200" id="signed_file" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
                                    @error('file')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="w-[30%] px-3 py-2">
                                <label for="signed_file" class="text-[#1E1E1E]!">
                                    Signed Time
                                    <span class="text-[#900B09]">*</span>
                                </label>
                                <input id="fpDate" type="text" name="signed_date" id="signed_date" class="text-[#1E1E1E] rounded-lg px-3 py-2 w-full border border-[#D9D9D9] cursor-pointer @error('signed_date') is-invalid @enderror" required value="{{ old('signed_date') }}">
                                @error('signed_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="w-[30%] px-3 py-2">
                                <label for="description" class="text-[#1E1E1E]!">
                                    Description
                                </label>
                                <input type="text" name="description" id="description" class="w-full text-[#1E1E1E] border border-[#D9D9D9] px-3 py-2 rounded-lg" placeholder="Type Description Here.. (Optional)" value="{{ old('description') }}">
                            </div>
                            <div class="w-[10%] px-3 py-2">
                                <button class="w-full px-3 py-2 cursor-pointer bg-[#115640] text-white rounded-lg flex items-center justify-center gap-2">
                                    <x-icon.upload/>
                                    Upload
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif

            {{-- BUTTONS TO DOWNLOAD / EDIT --}}
            <div class="flex justify-end py-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('quotations.download', $quotation->id) }}" class="px-5 py-2 bg-white border border-[#115640] rounded-lg text-[#115640] font-semibold flex items-center gap-2">
                        <x-icon.download/>
                        Download Quotation
                    </a>
                    @php
                        $userRole = auth()->user()->role?->code;
                        $bmApproved = $quotation->reviews->where('role', 'BM')->where('decision', 'approve')->isNotEmpty();
                        $financeApproved = $quotation->reviews->where('role', 'finance')->where('decision', 'approve')->isNotEmpty();
                        $allApproved = $bmApproved && $financeApproved;
                        $hasPayment = $quotation->proformas->contains(function ($p) {
                            return $p->paymentConfirmation !== null;
                        });

                        // Explicit editability rules - allow both sales and BM to edit before finance approval
                        $canEdit = false;
                        
                        if (in_array($userRole, ['sales', 'branch_manager']) && isset($claim)) {
                            // Sales OR BM can edit if quotation is not yet fully approved by finance
                            $editableStatuses = ['draft', 'review', 'pending_finance'];
                            $canEdit = in_array($quotation->status, $editableStatuses);
                        } elseif ($userRole === 'branch_manager' && $quotation->status === 'published' && !$hasPayment) {
                            // BM can edit published quotations if no payments exist
                            $canEdit = true;
                        }
                    @endphp
                    @if ($canEdit && isset($claim))
                        <a href="{{ route('leads.my.warm.quotation.create', $claim->id) }}" class="px-5 py-2 bg-[#115640] border border-[#115640] rounded-lg text-white font-semibold">Edit Quotation</a>
                    @endif
                </div>
            </div>

            {{-- APPROVAL SECTION BY FINANCE AND BRANCH MANAGER --}}
            @php
                $userRole = auth()->user()->role?->code;
                $bmReview = $quotation->reviews->where('role', 'BM')->first();
                $financeReview = $quotation->reviews->where('role', 'finance')->first();

                $canReview = false;
                        
                if ($quotation->status === 'review') {
                    // BM can review if they haven't reviewed yet
                    $canReview = ($userRole === 'branch_manager' && !$bmReview);
                } elseif ($quotation->status === 'pending_finance') {
                    // Finance can review if BM approved and finance hasn't reviewed yet
                    $canReview = ($userRole === 'finance' && $bmReview && $bmReview->decision === 'approve' && !$financeReview);
                }
            @endphp

            @if($canReview)
                <div class="bg-white border border-[#D9D9D9] rounded-lg my-4">
                    <h1 class="uppercase text-[#1E1E1E] font-semibold p-3 border-b border-b-[#D9D9D9]">Approval Section</h1>
                    <div class="grid grid-cols-2 divide-x divide-[#D9D9D9]">
                        {{-- REJECTING FORM --}}
                        <div class="p-3">
                            <h1 class="text-[#1E1E1E] font-semibold mb-2">Reject Quotation</h1>
                            <form method="POST" id="reject"
                                action="{{ route('quotations.reject', $quotation->id) }}" require-confirmation="true">
                                @csrf
                                <textarea name="notes" class="w-full! px-3! py-2! rounded-lg! border! border-[#D9D9D9]! focus:outline-none!" rows="5" placeholder="Enter notes..." required></textarea>
                                <div class="flex justify-end mt-3">
                                    <button class="cursor-pointer bg-[#900B09] text-white px-3 py-2 rounded-lg">
                                        Reject{{ $userRole === 'finance' ? ' (Finance)' : ($userRole === 'branch_manager' ? ' (BM)' : '') }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- APPROVING FORM --}}
                        <div class="p-3">
                            <h1 class="text-[#1E1E1E] font-semibold mb-2">Approve Quotation</h1>
                            <form method="POST" id="approve"
                                action="{{ route('quotations.approve', $quotation->id) }}" require-confirmation="true">
                                @csrf
                                <textarea name="notes" class="w-full! px-3! py-2! rounded-lg! border! border-[#D9D9D9]! focus:outline-none!" rows="5" placeholder="Enter notes..." required></textarea>
                                <div class="flex justify-end mt-3">
                                    <button class="cursor-pointer bg-[#115640] text-white px-3 py-2 rounded-lg">
                                        Approve{{ $userRole === 'finance' ? ' (Finance)' : ($userRole === 'branch_manager' ? ' (BM)' : '') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        const observer = new MutationObserver(() => {
            const confirmBtn = document.querySelector('.swal2-confirm');
            if (confirmBtn && !confirmBtn.dataset.bound) {
                confirmBtn.dataset.bound = 'true';
                confirmBtn.addEventListener('click', function() {
                    if (typeof loading === 'function') loading();
                });
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        const fileInput = document.getElementById('signed_file');
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const name = e.target.files[0] ? e.target.files[0].name : 'Browse';
                e.target.nextElementSibling.innerText = name;
            });
        }
        $(document).ready(function() {
            $("#fpDate").flatpickr({
                dateFormat: "d/m/Y",   // format tampil
                defaultDate: "today",  // otomatis isi tanggal sekarang
                allowInput: false
            });
        });
    </script>
@endsection
