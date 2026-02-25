@extends('layouts.app')

@section('content')
    <section class="min-h-screen">
        {{-- MAIN ROW --}}
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
                    {{ $quotation ? 'Edit Quotation' : 'Generate Quotation' }}
                </a>
            </div>
            <form method="POST" action="{{ route('leads.my.warm.quotation.store', $claim->id) }}" id="form"
            require-confirmation="true">
                @csrf
                {{-- QUOTATIONS ITEMS --}}
                <div class="bg-white rounded-lg mt-4">
                    <h1 class="text-black uppercase border-b border-b-[#D9D9D9] p-3 font-semibold">Quotations Items</h1>
                    @php
                        $disabled = $isEditable ? '' : 'disabled';
                        $defaultSegment = strtolower($defaultSegment ?? '');
                        $segmentOptions = $segments ?? collect();
                    @endphp

                    @if (!$isEditable && $quotation)
                        <div class="alert alert-warning">
                            This quotation is already <strong>{{ ucfirst($quotation->status) }}</strong> and cannot be
                            edited.
                        </div>
                    @endif
                    @if ($quotation && $quotation->status === 'rejected' && isset($rejection))
                        <div class="alert alert-danger">
                            Quotation rejected by <b>{{ $rejection->reviewer->name ?? $rejection->role }}</b> on
                            {{ $rejection->decided_at ? \Carbon\Carbon::parse($rejection->decided_at)->format('d M Y') : '' }}
                            <strong>Notes:</strong> {{ $rejection->notes }}
                        </div>
                    @elseif($quotation && isset($approval))
                        <div class="alert alert-success">
                            Quotation approved by <b>{{ $approval->reviewer->name ?? $approval->role }}</b> on
                            {{ $approval->decided_at ? \Carbon\Carbon::parse($approval->decided_at)->format('d M Y') : '' }}
                            <strong>Notes:</strong> {{ $approval->notes }}
                        </div>
                    @endif
                    {{-- PRODUCTS --}}
                    <div class="p-3">
                        <div class="border border-[#D9D9D9] rounded-lg">
                            <table class="w-full" id="items-table">
                                <thead class="text-[#1E1E1E] font-semibold">
                                    <tr class="border-b border-b-[#D9D9D9]">
                                        <td class="p-3">
                                            Product
                                        </td>
                                        <td class="p-3">
                                            Description
                                        </td>
                                        <td class="p-3">
                                            Qty
                                        </td>
                                        <td class="w-[200px] p-3">
                                            Unit Price (Rp)
                                        </td>
                                        <td class="p-3">
                                            Disc (%)
                                        </td>
                                        <td class="w-[200px] p-3">
                                            Line Total
                                        </td>
                                        <td class="p-3">
                                            PDF Visibility
                                        </td>
                                        <td class="p-3">
                                            Action
                                        </td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($quotation)
                                        @foreach ($quotation->items as $item)
                                            <tr data-item-id="{{ $item->id }}" class="border-b border-b-[#D9D9D9]">
                                                <td class="p-3">
                                                    <select name="product_id[]" class="w-full item-product select2" {{ $disabled }} required>
                                                        <option value="">Select Product</option>
                                                        <option value="add_on" {{ is_null($item->product_id) ? 'selected' : '' }}>Add On Product</option>
                                                        @foreach ($products as $p)
                                                            <option value="{{ $p->id }}"
                                                                {{ $item->product_id == $p->id ? 'selected' : '' }}
                                                                data-name="{{ $p->name }}"
                                                                data-sku="{{ $p->sku }}"
                                                                data-price="{{ $p->price }}"
                                                                data-gov="{{ $p->government_price }}"
                                                                data-corp="{{ $p->corporate_price }}"
                                                                data-pers="{{ $p->personal_price }}"
                                                                data-fob="{{ $p->fob_price }}">
                                                                {{ $p->name }} ({{ $p->sku }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="p-3">
                                                    <input type="text" name="description[]"
                                                    class="form-control item-desc text-start" value="{{ $item->description }}"
                                                    {{ $item->product_id ? 'readonly' : '' }} {{ $disabled }}
                                                    required>
                                                </td>
                                                <td class="p-3 max-w-20!">
                                                    <input type="number" name="qty[]" class="form-control item-qty"
                                                        value="{{ $item->qty }}" {{ $disabled }}>
                                                </td>
                                                <td>
                                                    <div class="w-[200px] flex items-center p-3">
                                                        <span class="bg-[#F5F5F5] text-[#B3B3B3] p-2 font-semibold border border-[#D9D9D9] border-r-0 rounded-tl-lg rounded-bl-lg">Rp</span>
                                                        <input type="text" name="unit_price[]"
                                                            class="p-2 border border-[#D9D9D9] w-full rounded-tr-lg rounded-br-lg item-price number-input"
                                                            value="{{ number_format($item->unit_price, 0, ',', '.') }}"
                                                            {{ $disabled }} required>
                                                    </div>
                                                    <div class="px-3">
                                                        <select class="p-2 border border-[#D9D9D9] rounded-lg item-segment mb-1 {{ $item->product ? '' : 'd-none' }}" {{ $disabled }}>
                                                            @foreach($segmentOptions as $seg)
                                                                <option value="{{ strtolower($seg->name) }}" {{ strtolower($seg->name) == $defaultSegment ? 'selected' : '' }}>{{ $seg->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    @if ($item->product)
                                                    <div class="segment-price-info px-3 pt-1 text-[#1E1E1E]">
                                                        <p class="text-sm block w-full">
                                                            Gov:
                                                            <span>
                                                                Rp{{ number_format($item->product->government_price, 0, ',', '.') }}
                                                            </span>
                                                        </p>
                                                        <p class="text-sm block">
                                                            Corp:
                                                            <span>
                                                                Rp{{ number_format($item->product->corporate_price, 0, ',', '.') }}
                                                            </span>
                                                        </p>
                                                        <p class="text-sm block">
                                                            Personal:
                                                            <span>
                                                                Rp{{ number_format($item->product->personal_price, 0, ',', '.') }}
                                                            </span>
                                                        </p>
                                                        <p class="text-sm block">
                                                            FOB:
                                                            <span>
                                                                Rp{{ number_format($item->product->fob_price, 0, ',', '.') }}
                                                            </span>
                                                        </p>
                                                    </div>
                                                    @endif
                                                </td>
                                                <td class="p-3 w-[100px]">
                                                    <input type="number" 
                                                    name="discount_pct[]"
                                                        class="form-control item-disc"
                                                        step='0.01'
                                                        min="0"
                                                        max="100"
                                                        value="{{ $item->discount_pct }}" {{ $disabled }}>
                                                </td>
                                                <td class="p-3">
                                                    <div class="w-[200px] flex items-center">
                                                        <span class="bg-[#F5F5F5] text-[#B3B3B3] p-2 font-semibold border border-[#D9D9D9] border-r-0 rounded-tl-lg rounded-bl-lg">Rp</span>
                                                        <input type="text" class="p-2 border border-[#D9D9D9] w-full rounded-tr-lg rounded-br-lg bg-[#F5F5F5] border-l-0 item-total number-input"
                                                        value="{{ number_format($item->line_total, 0, ',', '.') }}"
                                                        readonly>
                                                    </div>
                                                </td>
                                                @if ($isEditable)
                                                <td class="p-3">
                                                    {{-- Visibility Toggle Button --}}
                                                    <button type="button" class="visibility-toggle border border-[#D9D9D9] rounded-lg cursor-pointer p-2 text-[#1E1E1E]"
                                                            data-visible="{{ $item->is_visible_pdf ? 'true' : 'false' }}">
                                                        <i class="bi {{ $item->is_visible_pdf ? 'bi-eye' : 'bi-eye-slash' }}"></i>
                                                    </button>
                                                    <input type="hidden" name="is_visible_pdf[]" 
                                                        value="{{ $item->is_visible_pdf ? '1' : '0' }}"
                                                        class="visibility-input">

                                                    {{-- Merge Into Dropdown --}}
                                                    <select name="merge_into_item_id[]"
                                                        class="p-2 border border-[#D9D9D9] rounded-lg focus:outline-none form-select-sm mt-2 merge-dropdown d-none block {{ $item->is_visible_pdf ? 'd-none' : '' }}"
                                                        {{ $disabled }}>
                                                        <option value="">Select visible item...</option>
                                                        @foreach ($quotation->items->where('is_visible_pdf', true) as $visibleItem)
                                                            <option value="{{ $loop->index }}"
                                                                {{ $item->merge_into_item_id == $visibleItem->id ? 'selected' : '' }}>
                                                                {{ $visibleItem->description }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="p-3">
                                                    <button type="button" class="remove-item flex items-center cursor-pointer text-[#900B09] px-3 py-1 border border-[#D9D9D9] rounded-lg gap-2">
                                                        <svg width="16" height="13" viewBox="0 0 14 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M1 3.66667H2.33333M2.33333 3.66667H13M2.33333 3.66667L2.33333 13C2.33333 13.3536 2.47381 13.6928 2.72386 13.9428C2.97391 14.1929 3.31304 14.3333 3.66667 14.3333H10.3333C10.687 14.3333 11.0261 14.1929 11.2761 13.9428C11.5262 13.6928 11.6667 13.3536 11.6667 13V3.66667M4.33333 3.66667V2.33333C4.33333 1.97971 4.47381 1.64057 4.72386 1.39052C4.97391 1.14048 5.31304 1 5.66667 1H8.33333C8.68696 1 9.02609 1.14048 9.27614 1.39052C9.52619 1.64057 9.66667 1.97971 9.66667 2.33333V3.66667M5.66667 7V11M8.33333 7V11" stroke="#900B09" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        Delete
                                                    </button>
                                                </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @else
                                    {{-- New quotation - single empty row --}}
                                    <tr class="border-b border-b-[#D9D9D9]" data-item-id="new">
                                        <td class="p-3">
                                            <select name="product_id[]" class="w-full item-product select2"
                                                {{ $disabled }} required>
                                                <option value="">Select Product</option>
                                                <option value="add_on">Add On Product</option>
                                                @foreach ($products as $p)
                                                    <option value="{{ $p->id }}"
                                                        data-name="{{ $p->name }}"
                                                        data-sku="{{ $p->sku }}"
                                                        data-price="{{ $p->price }}"
                                                        data-gov="{{ $p->government_price }}"
                                                        data-corp="{{ $p->corporate_price }}"
                                                        data-pers="{{ $p->personal_price }}"
                                                        data-fob="{{ $p->fob_price }}">
                                                        {{ $p->name }} ({{ $p->sku }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="p-3">
                                            <input type="text" name="description[]"
                                                class="form-control item-desc text-start" readonly {{ $disabled }}>
                                        </td>
                                        <td class="p-3 max-w-20!">
                                            <input type="number" name="qty[]" class="form-control item-qty"
                                                value="1" {{ $disabled }} required>
                                        </td>
                                        <td>
                                            <div class="w-[200px] flex items-center p-3">
                                                <span class="bg-[#F5F5F5] text-[#B3B3B3] p-2 font-semibold border border-[#D9D9D9] border-r-0 rounded-tl-lg rounded-bl-lg">Rp</span>
                                                <input type="text" name="unit_price[]"
                                                    class="p-2 border border-[#D9D9D9] w-full rounded-tr-lg rounded-br-lg item-price number-input" {{ $disabled }}
                                                    required>
                                            </div>
                                            <div class="px-3">
                                                <select class="p-2 border border-[#D9D9D9] rounded-lg item-segment mb-1 d-none" {{ $disabled }}>
                                                    @foreach($segmentOptions as $seg)
                                                        <option value="{{ strtolower($seg->name) }}" {{ strtolower($seg->name) == $defaultSegment ? 'selected' : '' }}>{{ $seg->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-text segment-price-info px-3 text-[#1E1E1E]"></div>
                                        </td>
                                        <td class="p-3 w-[100px]">
                                            <input type="number" 
                                                name="discount_pct[]"
                                                class="form-control item-disc"
                                                step='0.01'
                                                min="0"
                                                max="100" {{ $disabled }}>
                                        </td>
                                        <td class="p-3">
                                            <div class="w-[200px] flex items-center">
                                                <span class="bg-[#F5F5F5] text-[#B3B3B3] p-2 font-semibold border border-[#D9D9D9] border-r-0 rounded-tl-lg rounded-bl-lg">Rp</span>
                                                <input type="text" class="p-2 border border-[#D9D9D9] w-full rounded-tr-lg rounded-br-lg bg-[#F5F5F5] border-l-0 item-total number-input"
                                                    readonly>
                                            </div>
                                        </td>
                                        @if ($isEditable)
                                            <td class="p-3">
                                                <button type="button" class="visibility-toggle border border-[#D9D9D9] rounded-lg cursor-pointer p-2 text-[#1E1E1E]" data-visible="true">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <input type="hidden" name="is_visible_pdf[]" value="1" class="visibility-input block">
                                                <select name="merge_into_item_id[]" class="p-2 border border-[#D9D9D9] rounded-lg focus:outline-none form-select-sm mt-2 merge-dropdown d-none block">
                                                    <option value="">Select visible item...</option>
                                                </select>
                                            </td>
                                            <td class="p-3">
                                                <button type="button" class="remove-item flex items-center cursor-pointer text-[#900B09] px-3 py-1 border border-[#D9D9D9] rounded-lg gap-2">
                                                    <svg width="16" height="13" viewBox="0 0 14 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M1 3.66667H2.33333M2.33333 3.66667H13M2.33333 3.66667L2.33333 13C2.33333 13.3536 2.47381 13.6928 2.72386 13.9428C2.97391 14.1929 3.31304 14.3333 3.66667 14.3333H10.3333C10.687 14.3333 11.0261 14.1929 11.2761 13.9428C11.5262 13.6928 11.6667 13.3536 11.6667 13V3.66667M4.33333 3.66667V2.33333C4.33333 1.97971 4.47381 1.64057 4.72386 1.39052C4.97391 1.14048 5.31304 1 5.66667 1H8.33333C8.68696 1 9.02609 1.14048 9.27614 1.39052C9.52619 1.64057 9.66667 1.97971 9.66667 2.33333V3.66667M5.66667 7V11M8.33333 7V11" stroke="#900B09" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    Delete
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                    @endif
                                </tbody>
                                @if ($isEditable)
                                    <tfoot>
                                        <tr class="border-t border-t-[#D9D9D9]">
                                            <td class="p-3">
                                                <button type="button" id="add-item" class="cursor-pointer text-[#083224] flex items-center gap-1">
                                                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M7.46647 4.7998V10.1331M4.7998 7.46647H10.1331M14.1331 7.46647C14.1331 11.1484 11.1484 14.1331 7.46647 14.1331C3.78457 14.1331 0.799805 11.1484 0.799805 7.46647C0.799805 3.78457 3.78457 0.799805 7.46647 0.799805C11.1484 0.799805 14.1331 3.78457 14.1331 7.46647Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    <p class="font-semibold">More Product</p>
                                                </button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>

                {{-- FINANCIAL SUMMARY & PAYMENT TERMS --}}
                <div class="grid grid-cols-2 gap-5 mt-4">

                    {{-- FINANCIAL SUMMARY SECTION --}}
                    <div class="bg-white rounded-lg">
                        <h1 class="text-black uppercase border-b border-b-[#D9D9D9] p-3 font-semibold">Financial Summary</h1>
                        {{-- TAX SECTION --}}
                        <div class="px-3 py-2 text-[#1E1E1E]">
                            <p class="mb-1 font-medium">
                                Tax (%)
                                <span class="text-[#EC221F]">*</span>
                            </p>
                            <input type="number" step="0.01" name="tax_pct" id="tax_pct" class="w-full px-3 py-2 border border-[#D9D9D9] rounded-lg"
                                value="{{ old('tax_pct', $quotation->tax_pct ?? 11) }}" {{ $disabled }} required>
                        </div>
                        <div class="px-3 py-2 text-[#1E1E1E]">
                            <p class="mb-1 font-medium">
                                Total Discount
                            </p>
                            <input type="text" id="total_discount_display" class="w-full px-3 py-2 border border-[#D9D9D9] rounded-lg bg-[#F5F5F5]"
                                value="{{ 'Rp' . number_format(($quotation && $quotation->total_discount) ? $quotation->total_discount : 0, 0, ',', '.') }}" readonly>
                            <input type="hidden" name="total_discount" id="total_discount"
                                value="{{ optional($quotation)->total_discount ?? 0 }}">
                        </div>
                        <div class="px-3 py-2 text-[#1E1E1E]">
                            <p class="mb-1 font-medium">
                                Subtotal
                            </p>
                            <input type="text" id="subtotal_display" class="w-full px-3 py-2 border border-[#D9D9D9] rounded-lg bg-[#F5F5F5]"
                                value="{{ 'Rp' . number_format($quotation->subtotal ?? 0, 0, ',', '.') }}" readonly>
                            <input type="hidden" name="subtotal" id="subtotal"
                                value="{{ $quotation->subtotal ?? 0 }}">
                        </div>
                        <div class="px-3 py-2 text-[#1E1E1E]">
                            <p class="mb-1 font-medium">
                                Tax Amount
                            </p>
                            <input type="text" id="tax_total_display" class="w-full px-3 py-2 border border-[#D9D9D9] rounded-lg bg-[#F5F5F5]"
                                value="{{ 'Rp' . number_format($quotation->tax_total ?? 0, 0, ',', '.') }}" readonly>
                            <input type="hidden" name="tax_total" id="tax_total"
                                value="{{ $quotation->tax_total ?? 0 }}">
                        </div>
                        <div class="px-3 py-2 text-[#1E1E1E]">
                            <p class="mb-1 font-medium">
                                Grand Total
                            </p>
                            <input type="text" id="grand_total_display" class="w-full px-3 py-2 border border-[#D9D9D9] rounded-lg bg-[#F5F5F5]"
                                value="{{ 'Rp' . number_format($quotation->grand_total ?? 0, 0, ',', '.') }}"
                                readonly>
                            <input type="hidden" name="grand_total" id="grand_total"
                                value="{{ $quotation->grand_total ?? 0 }}">
                        </div>

                        {{-- PAYMENT TYPE --}}
                        <div class="px-3 py-2 text-[#1E1E1E]">
                            <p class="mb-1 font-medium">
                                Payment Type
                                <span class="text-[#EC221F]">*</span>
                            </p>
                            @php
                                $paymentType = old(
                                    'payment_type',
                                    $quotation?->booking_fee ? 'booking_fee' : 'down_payment',
                                );
                            @endphp
                            <select name="payment_type" id="payment_type" class="w-full px-3 py-2 border border-[#D9D9D9] rounded-lg"
                                {{ $disabled }}>
                                <option value="booking_fee" {{ $paymentType === 'booking_fee' ? 'selected' : '' }}>
                                    Booking Fee First</option>
                                <option value="down_payment" {{ $paymentType === 'down_payment' ? 'selected' : '' }}>
                                    Direct Down Payment</option>
                            </select>
                        </div>

                        <div class="px-3 py-2 text-[#1E1E1E]" id="booking_fee_field" style="display:none;">
                            <p class="mb-1 font-medium">
                                Payment Type
                            </p>
                            <input type="text" name="booking_fee" id="booking_fee"
                                class="w-full px-3 py-2 border border-[#D9D9D9] rounded-lg bg-white number-input" value="{{ number_format(old('booking_fee', $quotation->booking_fee ?? 0), 0, ',', '.') }}"
                                {{ $disabled }}>
                        </div>
                    </div>

                    {{-- PAYMENT TERMS SECTION --}}
                    <div class="bg-white rounded-lg">
                        <h1 class="text-black uppercase border-b border-b-[#D9D9D9] p-3 font-semibold">Payment Terms Configuration</h1>
                        {{-- Tooltip --}}
                        @if ($isEditable)
                            <div class="px-3 py-2 flex items-center gap-2">
                                <button type="button" id="autofill-downpayment" class="cursor-pointer bg-white border border-[#083224] text-[#083224] px-3 py-2 rounded-lg font-semibold flex items-center gap-1">
                                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M8.63333 6.72803L7.2 9.02803C7.07778 9.21692 6.90833 9.2947 6.69167 9.26136C6.475 9.22803 6.33889 9.10025 6.28333 8.87803L5.81667 7.01136L1.26667 11.5614C1.14444 11.6836 0.991667 11.7475 0.808333 11.753C0.625 11.7586 0.466667 11.6947 0.333333 11.5614C0.211111 11.4391 0.15 11.2836 0.15 11.0947C0.15 10.9058 0.211111 10.7503 0.333333 10.628L4.88333 6.06136L3.01667 5.5947C2.79444 5.53914 2.66667 5.40303 2.63333 5.18636C2.6 4.9697 2.67778 4.80025 2.86667 4.67803L5.16667 3.26136L4.96667 0.544697C4.94445 0.322475 5.03333 0.161364 5.23333 0.0613636C5.43333 -0.0386364 5.61667 -0.0164141 5.78333 0.12803L7.86667 1.87803L10.3833 0.861364C10.5944 0.772475 10.7778 0.805808 10.9333 0.961364C11.0889 1.11692 11.1222 1.30025 11.0333 1.51136L10.0167 4.02803L11.7667 6.0947C11.9111 6.26136 11.9333 6.4447 11.8333 6.6447C11.7333 6.8447 11.5722 6.93359 11.35 6.91136L8.63333 6.72803ZM0.1 2.26136C0.0333333 2.1947 0 2.11692 0 2.02803C0 1.93914 0.0333333 1.86136 0.1 1.7947L0.966667 0.92803C1.03333 0.861364 1.11111 0.82803 1.2 0.82803C1.28889 0.82803 1.36667 0.861364 1.43333 0.92803L2.3 1.7947C2.36667 1.86136 2.4 1.93914 2.4 2.02803C2.4 2.11692 2.36667 2.1947 2.3 2.26136L1.43333 3.12803C1.36667 3.1947 1.28889 3.22803 1.2 3.22803C1.11111 3.22803 1.03333 3.1947 0.966667 3.12803L0.1 2.26136ZM7.11667 6.6447L7.91667 5.32803L9.46667 5.4447L8.46667 4.26136L9.05 2.82803L7.61667 3.41136L6.43333 2.42803L6.55 3.96136L5.23333 4.77803L6.73333 5.1447L7.11667 6.6447ZM9.63334 11.7947L8.76667 10.928C8.7 10.8614 8.66667 10.7836 8.66667 10.6947C8.66667 10.6058 8.7 10.528 8.76667 10.4614L9.63334 9.5947C9.7 9.52803 9.77778 9.4947 9.86667 9.4947C9.95556 9.4947 10.0333 9.52803 10.1 9.5947L10.9667 10.4614C11.0333 10.528 11.0667 10.6058 11.0667 10.6947C11.0667 10.7836 11.0333 10.8614 10.9667 10.928L10.1 11.7947C10.0333 11.8614 9.95556 11.8947 9.86667 11.8947C9.77778 11.8947 9.7 11.8614 9.63334 11.7947Z" fill="#083224"/>
                                    </svg>
                                    <p>
                                        Auto Fill
                                    </p>
                                </button>
                                <div class="flex items-center gap-2 relative group w-fit">
                                    {{-- ICON --}}
                                    <div class="w-5 h-5 flex items-center justify-center 
                                                bg-white border-2 border-[#083224] text-[#083224] text-xs rounded-full cursor-pointer font-semibold">
                                        i
                                    </div>
                                    {{-- Tooltip --}}
                                    <div class="absolute left-full top-1/2 -translate-y-1/2 ml-3
                                                opacity-0 group-hover:opacity-100
                                                transition-all duration-200
                                                bg-[#083224] text-white text-sm
                                                px-3 py-2 rounded-md shadow-lg
                                                whitespace-nowrap z-50">
                                            Autofill Down Payment Terms
                                            <p>
                                                Will populate standard down payment terms
                                            </p>
                                        {{-- ARROW --}}
                                        <div class="absolute right-full top-1/2 -translate-y-1/2
                                                    border-8 border-transparent border-r-[#083224]">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        <div class="p-3">
                            <div class="border border-[#D9D9D9] rounded-lg">
                                <table class="w-full text-[#1E1E1E]">
                                    <thead>
                                        <tr class="border-b-[#D9D9D9] border-b">
                                            <td class="p-3 font-semibold">
                                                <p>Term of Payments (%)</p>
                                            </td>
                                            <td class="p-3 font-semibold">
                                                <p>Description</p>
                                            </td>
                                            <td class="p-3 font-semibold">
                                                <p>Actions</p>
                                            </td>
                                        </tr>
                                    </thead>
                                    <tbody id="terms-table-container">
                                        @php
                                            $terms = $quotation
                                                ? $quotation->paymentTerms->pluck('percentage')
                                                : collect([null]);
                                        @endphp
                                        @if ($quotation)
                                            @foreach ($terms as $i => $term)
                                                <tr class="term-row border-b border-b-[#D9D9D9]">
                                                    <td class="flex items-center p-3">
                                                        <span class="bg-[#F5F5F5] text-[#B3B3B3] p-2 font-semibold border border-[#D9D9D9] border-r-0 rounded-tl-lg w-1/4 rounded-bl-lg">Term {{ $i + 1 }}</span>
                                                        <input
                                                            type="number" step="0.01"
                                                            name="term_percentage[]"
                                                            class="p-2 border border-[#D9D9D9] w-full rounded-tr-lg rounded-br-lg item-price"
                                                            value="{{ old("term_percentage.$i", $term) }}"
                                                            {{ $disabled }}
                                                            placeholder="Input Here... (%)"
                                                            required
                                                        >
                                                    </td>
                                                    <td class="p-3">
                                                        <input
                                                            type="text"
                                                            name="term_description[]"
                                                            class="p-2 border border-[#D9D9D9] w-full rounded-lg item-price"
                                                            placeholder="Type Description Here... (Opsional)"
                                                            value="{{ old("term_description.$i", $quotation->paymentTerms[$i]->description ?? '') }}"
                                                            {{ $disabled }}
                                                        >
                                                    </td>
                                                    <td class="p-3">
                                                        <button type="button" class="remove-term-table flex items-center cursor-pointer text-[#900B09] px-3 py-1 border border-[#D9D9D9] rounded-lg gap-2">
                                                            <svg width="16" height="13" viewBox="0 0 14 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M1 3.66667H2.33333M2.33333 3.66667H13M2.33333 3.66667L2.33333 13C2.33333 13.3536 2.47381 13.6928 2.72386 13.9428C2.97391 14.1929 3.31304 14.3333 3.66667 14.3333H10.3333C10.687 14.3333 11.0261 14.1929 11.2761 13.9428C11.5262 13.6928 11.6667 13.3536 11.6667 13V3.66667M4.33333 3.66667V2.33333C4.33333 1.97971 4.47381 1.64057 4.72386 1.39052C4.97391 1.14048 5.31304 1 5.66667 1H8.33333C8.68696 1 9.02609 1.14048 9.27614 1.39052C9.52619 1.64057 9.66667 1.97971 9.66667 2.33333V3.66667M5.66667 7V11M8.33333 7V11" stroke="#900B09" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t border-t-[#D9D9D9]">
                                            <td class="p-3">
                                                @if ($isEditable)
                                                    <button type="button" id="add-term-table" class="cursor-pointer text-[#083224] flex items-center gap-1">
                                                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M7.46647 4.7998V10.1331M4.7998 7.46647H10.1331M14.1331 7.46647C14.1331 11.1484 11.1484 14.1331 7.46647 14.1331C3.78457 14.1331 0.799805 11.1484 0.799805 7.46647C0.799805 3.78457 3.78457 0.799805 7.46647 0.799805C11.1484 0.799805 14.1331 3.78457 14.1331 7.46647Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                        <p class="font-semibold">More Term</p>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-5 pb-5">
                    @if (!$quotation)
                    <a href="{{ route('leads.my') }}" class="cursor-pointer px-5 py-2 bg-white border border-[#115640] rounded-lg text-[#083224] font-semibold">Cancel</a>
                    @endif
                    @if ($quotation)
                        <a href="{{ route('quotations.download', $quotation->id) }}" class="cursor-pointer px-5 py-2 bg-white border border-[#115640] rounded-lg text-[#083224] font-semibold flex items-center gap-1">
                            <x-icon.download/>
                            Download Quotation
                        </a>
                    @endif
                    @if ($isEditable)
                        <button type="submit" class="cursor-pointer px-10 py-2 bg-[#115640] border border-[#115640] rounded-lg text-white font-semibold">Save</button>
                    @endif
                </div>
            </form>
        </div>
    </section>
@endsection

@section('scripts')
    @if ($isEditable)
        <script>
            //— Helpers to parse/format numbers
            function parseNumber(val) {
                if (!val) return 0;
                return parseFloat(val.toString().replace(/\./g, '').replace(',', '.')) || 0;
            }

            function formatNumber(val) {
                return new Intl.NumberFormat('id-ID').format(val);
            }

            function formatCurrency(val) {
                return 'Rp' + formatNumber(val);
            }

            const defaultSegment = '{{ $defaultSegment }}';

            //— Render the four price tiers as a neat inline list
            function renderPriceTiers(gov, corp, pers, fob) {
                return `
                    <ul class="list-inline mb-0 small text-muted">
                      <li class="list-inline-item me-3">
                        <span class="fw-semibold">Gov:</span> Rp${formatNumber(gov)}
                      </li>
                      <li class="list-inline-item me-3">
                        <span class="fw-semibold">Corp:</span> Rp${formatNumber(corp)}
                      </li>
                      <li class="list-inline-item me-3">
                        <span class="fw-semibold">Personal:</span> Rp${formatNumber(pers)}
                      </li>
                      <li class="list-inline-item me-3">
                        <span class="fw-semibold">FOB:</span> Rp${formatNumber(fob)}
                      </li>
                    </ul>
                `;
            }

            function updatePriceForRow(row) {
                let seg = row.find('.item-segment').val() || defaultSegment;
                let opt = row.find('.item-product option:selected');
                let price = 0;

                if (!opt.length) return;

                switch (seg) {
                    case 'government':
                        price = opt.data('gov');
                        break;
                    case 'corporate':
                        price = opt.data('corp');
                        break;
                    case 'fob':
                        price = opt.data('fob');
                        break;
                    default:
                        price = opt.data('pers');
                }

                row.find('.item-price').val(formatNumber(price || 0));
            }

            //— Calculate a single row’s line total
            function calcRow(row) {
                let price = parseNumber(row.find('.item-price').val());
                let qty   = parseFloat(row.find('.item-qty').val()) || 0;
                let disc  = parseFloat(row.find('.item-disc').val()) || 0;
                let line  = (price - (price * disc / 100)) * qty;
                let discountAmount = (price * disc / 100) * qty;
                row.find('.item-total').val(formatNumber(line));
                return {lineTotal: line, discountAmount: discountAmount};
            }

            //— Sum up all rows, compute tax & grand total
            function calcTotal() {
                let subtotal = 0;
                let totalDiscount = 0;
                $('#items-table tbody tr').each(function() {
                    let result = calcRow($(this));
                    subtotal += result.lineTotal;
                    totalDiscount += result.discountAmount;
                });

                let pct   = parseFloat($('#tax_pct').val()) || 0;
                let tax   = subtotal * pct / 100;
                let grand = subtotal + tax;

                $('#total_discount_display').val(formatCurrency(totalDiscount));
                $('#total_discount').val(totalDiscount.toFixed(2));

                $('#subtotal_display').val(formatCurrency(subtotal));
                $('#tax_total_display').val(formatCurrency(tax));
                $('#grand_total_display').val(formatCurrency(grand));

                $('#subtotal').val(subtotal.toFixed(2));
                $('#tax_total').val(tax.toFixed(2));
                $('#grand_total').val(grand.toFixed(2));
            }

            $(document).on('click', '.visibility-toggle', function() {
                const $btn = $(this);
                const $row = $btn.closest('tr');
                const $icon = $btn.find('i');
                const $input = $row.find('.visibility-input');
                const $dropdown = $row.find('.merge-dropdown');
                const isVisible = $btn.data('visible') === true || $btn.data('visible') === 'true';
                
                if (isVisible) {
                    $icon.removeClass('bi-eye').addClass('bi-eye-slash');
                    $input.val('0');
                    $btn.data('visible', false);
                    $dropdown.removeClass('d-none');
                    updateMergeDropdowns();
                } else {
                    $icon.removeClass('bi-eye-slash').addClass('bi-eye');
                    $input.val('1');
                    $btn.data('visible', true);
                    $dropdown.addClass('d-none').val('');
                    updateMergeDropdowns();
                }
            });

            function updateMergeDropdowns() {
                const visibleItems = [];

                $('#items-table tbody tr').each(function(index) {
                    const $row = $(this);
                    const $toggle = $row.find('.visibility-toggle');
                    const isVisible = $toggle.data('visible') === true || $toggle.data('visible') === 'true';
                    
                    if (isVisible) {
                        const description = $row.find('.item-desc').val();
                        if (description) {
                            visibleItems.push({ id: index, description: description }); // Use index as ID
                        }
                    }
                });

                $('.merge-dropdown').each(function() {
                    const $dropdown = $(this);
                    const currentValue = $dropdown.val();
                    
                    $dropdown.empty().append('<option value="">Select visible item...</option>');
                    
                    visibleItems.forEach(item => {
                        $dropdown.append(`<option value="${item.id}">${item.description}</option>`);
                    });

                    if (currentValue && visibleItems.find(item => item.id == currentValue)) {
                        $dropdown.val(currentValue);
                    }
                });
            }

            $(document).on('input', '.item-desc', function() {
                updateMergeDropdowns();
            });

            $(function() {
                // — Initialize existing rows
                $('#items-table tbody tr').each(function() {
                    let row = $(this);
                    let sel = row.find('.item-product');
                    if (sel.val() && sel.val() !== 'add_on') {
                        let opt = sel.find('option:selected');
                        row.find('.item-segment').removeClass('d-none').val(defaultSegment);
                        updatePriceForRow(row);
                        row.find('.segment-price-info').html(
                            renderPriceTiers(
                                opt.data('gov')  || 0,
                                opt.data('corp') || 0,
                                opt.data('pers') || 0,
                                opt.data('fob')  || 0
                            )
                        );
                    } else {
                        row.find('.item-segment').addClass('d-none');
                    }
                    row.find('.item-total').val(formatNumber(parseNumber(row.find('.item-total').val())));
                });

                $('#payment_type').on('change', function() {
                    if ($(this).val() === 'down_payment' && $('#terms-table-container tr.term-row').length <= 1) {
                        // Only autofill if terms are empty or only have one term
                        $('#autofill-downpayment').trigger('click');
                    }
                });

                $(function() {
                    // Check if this is a new quotation (not editing existing) and payment type is down_payment
                    const isNewQuotation = {{ $quotation ? 'false' : 'true' }};
                    const paymentType = $('#payment_type').val();
                    
                    if (isNewQuotation && paymentType === 'down_payment' && $('#terms-table-container tr.term-row').length <= 1) {
                        $('#autofill-downpayment').trigger('click');
                    }
                });

                $(document).on('change', '.item-product', function() {
                    let row       = $(this).closest('tr');
                    let descInput = row.find('.item-desc');
                    let sel       = $(this);

                    if (sel.val() === 'add_on') {
                        descInput
                            .prop('readonly', false)
                            .val('')
                            .attr('required', true);
                        row.find('.item-price').val(formatNumber(0));
                        row.find('.segment-price-info').html('');
                        row.find('.item-segment').addClass('d-none');
                    } else {
                        let opt  = sel.find('option:selected');
                        let name = opt.data('name');
                        let sku  = opt.data('sku');

                        descInput
                            .prop('readonly', true)
                            .val(`${name} (${sku})`)     // otomatis terisi Name (SKU)
                            .removeAttr('required');

                        row.find('.item-segment').removeClass('d-none').val(defaultSegment);
                        updatePriceForRow(row);
                        row.find('.segment-price-info').html(
                            renderPriceTiers(
                                opt.data('gov')  || 0,
                                opt.data('corp') || 0,
                                opt.data('pers') || 0,
                                opt.data('fob')  || 0
                            )
                        );
                    }

                    calcTotal();
                });

                $(document).on('change', '.item-segment', function() {
                    let row = $(this).closest('tr');
                    updatePriceForRow(row);
                    calcTotal();
                });


                // — Recalc when qty, price, discount, or tax pct change
                $(document).on('input', '.item-qty, .item-price, .item-disc, #tax_pct', calcTotal);

                // — Format number inputs on keyup
                $(document).on('keyup', '.number-input', function() {
                    $(this).val(formatNumber(parseNumber($(this).val())));
                });

                // — Add new item row
                $('#add-item').on('click', function() {
                    $('select.select2').select2('destroy');
                    let newRow = $('#items-table tbody tr:first').clone();

                    // Clear all inputs
                    newRow.find('input').val('');
                    newRow.find('select').val('').trigger('change');
                    newRow.find('.item-desc').prop('readonly', true);
                    newRow.find('.item-qty').val(1);
                    newRow.find('.item-price').val(formatNumber(0));
                    newRow.find('.segment-price-info').html('');
                    newRow.find('.item-segment').val(defaultSegment).addClass('d-none');
                    newRow.find('.item-total').val('');

                    // Reset visibility controls
                    newRow.find('.visibility-toggle').data('visible', true).find('i')
                        .removeClass('bi-eye-slash').addClass('bi-eye');
                    newRow.find('.visibility-input').val('1');
                    newRow.find('.merge-dropdown').addClass('d-none').val('');
                    
                    // Update data-item-id for new row
                    newRow.attr('data-item-id', 'new-' + Date.now());
                    
                    $('#items-table tbody').append(newRow);
                    $('.select2').select2({ width: '100%' });
                    updateMergeDropdowns();
                });

                // — Remove item row
                $(document).on('click', '.remove-item', function() {
                    if ($('#items-table tbody tr').length > 1) {
                        $(this).closest('tr').remove();
                        calcTotal();
                    }
                });

                // — Payment terms: add / remove
                $('#add-term').on('click', function() {
                    let idx = $('#terms-table-container tr.term-row').length + 1;
                    let html = `
                        <div class="input-group mb-2 term-row">
                        <span class="input-group-text">Term ${idx}</span>
                        <input type="number" step="0.01" name="term_percentage[]" class="form-control" required>
                        <input type="text" name="term_description[]" class="form-control ms-2" placeholder="Description (optional)">
                        <button type="button" class="btn btn-outline-danger remove-term">&times;</button>
                        </div>
                    `;
                    $('#terms-container').append(html);
                });

                $(document).on('click', '.remove-term', function() {
                    $(this).closest('.term-row').remove();
                    $('#terms-table-container tr.term-row').each(function(i) {
                        $(this).find('.input-group-text').text('Term ' + (i + 1));
                    });
                });

                // — Payment terms (table): add / remove / renumber
                function updateTermTableLabels() {
                    $('#terms-table-container tr.term-row').each(function(i) {
                        // find the term label span inside the first td
                        $(this).find('td').first().find('span').first().text('Term ' + (i + 1));
                    });
                }

                $('#add-term-table').on('click', function() {
                    let idx = $('#terms-table-container tr.term-row').length + 1;

                    let html = `
                        <tr class="term-row border-b border-b-[#D9D9D9]">
                            <td class="flex items-center p-3">
                                <span class="bg-[#F5F5F5] text-[#B3B3B3] p-2 font-semibold border border-[#D9D9D9] border-r-0 rounded-tl-lg w-1/4 rounded-bl-lg">Term ${idx}</span>
                                <input
                                    type="number" step="0.01"
                                    name="term_percentage[]"
                                    class="p-2 border border-[#D9D9D9] w-full rounded-tr-lg rounded-br-lg item-price"
                                    placeholder="Input Here... (%)"
                                    required
                                >
                            </td>
                            <td class="p-3">
                                <input
                                    type="text"
                                    name="term_description[]"
                                    class="p-2 border border-[#D9D9D9] w-full rounded-lg item-price"
                                    placeholder="Type Description Here... (Opsional)"
                                >
                            </td>
                            <td class="p-3">
                                <button type="button" class="remove-term-table flex items-center cursor-pointer text-[#900B09] px-3 py-1 border border-[#D9D9D9] rounded-lg gap-2">
                                    <svg width="16" height="13" viewBox="0 0 14 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1 3.66667H2.33333M2.33333 3.66667H13M2.33333 3.66667L2.33333 13C2.33333 13.3536 2.47381 13.6928 2.72386 13.9428C2.97391 14.1929 3.31304 14.3333 3.66667 14.3333H10.3333C10.687 14.3333 11.0261 14.1929 11.2761 13.9428C11.5262 13.6928 11.6667 13.3536 11.6667 13V3.66667M4.33333 3.66667V2.33333C4.33333 1.97971 4.47381 1.64057 4.72386 1.39052C4.97391 1.14048 5.31304 1 5.66667 1H8.33333C8.68696 1 9.02609 1.14048 9.27614 1.39052C9.52619 1.64057 9.66667 1.97971 9.66667 2.33333V3.66667M5.66667 7V11M8.33333 7V11" stroke="#900B09" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Delete
                                </button>
                            </td>
                        </tr>
                    `;
                    $('#terms-table-container').append(html);
                    updateTermTableLabels();
                });

                $(document).on('click', '.remove-term-table', function() {
                    $(this).closest('tr').remove();
                    updateTermTableLabels();
                });

                // ensure table labels correct on load
                updateTermTableLabels();

                $('#autofill-downpayment').on('click', function() {
                    $('#terms-container').empty();
                    
                    const terms = [
                        { percentage: 50, description: 'Down Payment' },
                        { percentage: 50, description: 'Before Shipment' },
                        // { percentage: 50, description: 'AFTER RUNNING TEST OR BEFORE DELIVERY TO INDONESIA' }
                    ];
                    
                    terms.forEach((term, index) => {
                        let html = `
                            <tr class="term-row border-b border-b-[#D9D9D9]">
                                <td class="flex items-center p-3">
                                    <span class="bg-[#F5F5F5] text-[#B3B3B3] p-2 font-semibold border border-[#D9D9D9] border-r-0 rounded-tl-lg w-1/4 rounded-bl-lg">Term ${index + 1}</span>
                                    <input
                                        type="number" step="0.01"
                                        name="term_percentage[]"
                                        class="p-2 border border-[#D9D9D9] w-full rounded-tr-lg rounded-br-lg item-price"
                                        value="${term.percentage}"
                                        placeholder="Input Here... (%)"
                                        required
                                    >
                                </td>
                                <td class="p-3">
                                    <input
                                        type="text"
                                        name="term_description[]"
                                        class="p-2 border border-[#D9D9D9] w-full rounded-lg item-price"
                                        placeholder="Type Description Here... (Opsional)"
                                        value="${term.description}"
                                    >
                                </td>
                                <td class="p-3">
                                    <button type="button" class="remove-term-table flex items-center cursor-pointer text-[#900B09] px-3 py-1 border border-[#D9D9D9] rounded-lg gap-2">
                                        <svg width="16" height="13" viewBox="0 0 14 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M1 3.66667H2.33333M2.33333 3.66667H13M2.33333 3.66667L2.33333 13C2.33333 13.3536 2.47381 13.6928 2.72386 13.9428C2.97391 14.1929 3.31304 14.3333 3.66667 14.3333H10.3333C10.687 14.3333 11.0261 14.1929 11.2761 13.9428C11.5262 13.6928 11.6667 13.3536 11.6667 13V3.66667M4.33333 3.66667V2.33333C4.33333 1.97971 4.47381 1.64057 4.72386 1.39052C4.97391 1.14048 5.31304 1 5.66667 1H8.33333C8.68696 1 9.02609 1.14048 9.27614 1.39052C9.52619 1.64057 9.66667 1.97971 9.66667 2.33333V3.66667M5.66667 7V11M8.33333 7V11" stroke="#900B09" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        `;
                        $('#terms-table-container').append(html);
                    });
                });

                // — Show/hide booking fee field
                function toggleBookingFee() {
                    if ($('#payment_type').val() === 'booking_fee') {
                        $('#booking_fee_field').show()
                            .find('input, select, textarea').attr('required', true);
                    } else {
                        $('#booking_fee_field').hide()
                            .find('input, select, textarea').removeAttr('required');
                    }
                }
                $('#payment_type').on('change', toggleBookingFee);
                toggleBookingFee();

                // — Initial total calculation
                calcTotal();
            });
        </script>
    @endif
@endsection
