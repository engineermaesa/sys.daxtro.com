@extends('layouts.app')

@section('content')
    <section class="min-h-screen text-[#1E1E1E]">
        <div class="flex items-center gap-2 text-[#115640] pt-4">
            <x-icon.production/>
            <h1 class="font-semibold lg:text-2xl text-lg">Purchasing Log</h1>
        </div>
        <div class="flex items-center mt-2 gap-3">
            <a href="javascript:history.back()" class="text-[#757575] hover:no-underline">Purchasing Log</a>
            <i class="fas fa-chevron-right text-[#757575]" style="font-size: 12px;"></i>
            <a href="/" class="text-[#083224] underline">
                View Purchasing
            </a>
        </div>

        {{-- PURCHASING DETAIL --}}
        <div class="bg-white border border-[#D9D9D9] rounded-lg mt-4">
            <h1 class="font-semibold uppercase text-[#1E1E1E] p-3 border-b border-b-[#D9D9D9]">Purchasing Detail</h1>
            <div class="p-3">
                <div class="border border-[#D9D9D9] rounded-lg">
                    <table class="w-full">
                        <tr>
                            <th class="p-3">Created At</th>
                            <td class="p-3">{{ $purchasing->created_at }}</td>
                        </tr>
                        <tr class="border-t border-t-[#D9D9D9]">
                            <th class="p-3">Stage</th>
                            <td class="p-3">
                                @php
                                    $stageClass = match($purchasing->stage) {
                                        'Invoice Received' => 'bg-[#F5F5F5]',
                                        'Vendor Processing' => 'text-[#682D03] bg-[#FFF1C2]',
                                        'Ready for Handover' => 'text-[#183057] bg-[#E1EBFA]',
                                        'Completed' => 'text-[#02542D] bg-[#CFE7DE]',
                                        'Pending' => 'text-[#682D03] bg-[#FFF1C2]',
                                        'Canceled' => 'text-[#900B09] bg-[#FDD3D0]',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp

                                <span class="p-2 rounded-lg {{ $stageClass }}">
                                    {{ $purchasing->stage }}
                                </span>
                            </td>
                        </tr>
                        <tr class="border-t border-t-[#D9D9D9]">
                            <th class="p-3">Status</th>
                            <td class="p-3">
                                {{ $purchasing->status }}
                            </td>
                        </tr>
                        <tr class="border-t border-t-[#D9D9D9]">
                            <th class="p-3">Notes</th>
                            <td class="p-3">{{ $purchasing->notes ?? '-' }}</td>
                        </tr>
                        {{-- GANTI JADI DOWNLOAD BTN AMBIL DARI API --}}
                        <tr class="border-t border-t-[#D9D9D9]">
                            <th class="p-3">Files</th>
                            <td class="p-3">{{ $purchasing->files ?? '-' }}</td>
                        </tr>
                        <tr class="border-t border-t-[#D9D9D9]">
                            <th class="p-3">Latest Update</th>
                            <td class="p-3">{{ $purchasing->updated_at ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            

        </div>

        {{-- LEAD & QUOTATION --}}
        <div class="grid grid-cols-2 gap-5 my-4">

            {{-- LEAD --}}
            <div class="bg-white border border-[#D9D9D9] rounded-lg">
                <h1 class="font-semibold uppercase text-[#1E1E1E] p-3 border-b border-b-[#D9D9D9]">Lead Detail</h1>
                <div class="p-3">
                    <div class="border border-[#D9D9D9] rounded-lg">
                        <table class="w-full">
                            <tr>
                                <th class="p-3">Lead Name</th>
                                <td class="p-3">{{ $lead->name ?? '-' }}</td>
                            </tr>
                            <tr class="border-t border-t-[#D9D9D9]">
                                <th class="p-3">Company</th>
                                <td class="p-3">{{ $lead->company ?? '-' }}</td>
                            </tr>
                            <tr class="border-t border-t-[#D9D9D9]">
                                <th class="p-3">Phone</th>
                                <td class="p-3">{{ $lead->phone ?? '-' }}</td>
                            </tr>
                            <tr class="border-t border-t-[#D9D9D9]">
                                <th class="p-3">Email</th>
                                <td class="p-3">{{ $lead->email ?? '-' }}</td>
                            </tr>
                            <tr class="border-t border-t-[#D9D9D9]">
                                <th class="p-3">Provinsi</th>
                                <td class="p-3">{{ $lead->province ?? '-' }}</td>
                            </tr>
                            <tr class="border-t border-t-[#D9D9D9]">
                                <th class="p-3">City</th>
                                <td class="p-3">{{ $lead->region->name ?? '-' }}</td>
                            </tr>
                            <tr class="border-t border-t-[#D9D9D9]">
                                <th class="p-3">Customer Type</th>
                                <td class="p-3">{{ $lead->customer_type ?? '-' }}</td>
                            </tr>
                            <tr class="border-t border-t-[#D9D9D9]">
                                <th class="p-3">Needs</th>
                                <td class="p-3">{{ $lead->needs ?? '-' }}</td>
                            </tr>
                            <tr class="border-t border-t-[#D9D9D9]">
                                <th class="p-3">Leads Status</th>
                                <td class="p-3">{{ $lead->status->name ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            {{-- QUOTATION --}}
            <div class="bg-white border border-[#D9D9D9] rounded-lg">
                <h1 class="font-semibold uppercase text-[#1E1E1E] p-3 border-b border-b-[#D9D9D9]">Quotation Detail</h1>
                <div class="p-3">
                    <div class="border border-[#D9D9D9] rounded-lg">
                        <table class="w-full">
                            <tr>
                                <th class="p-3">Quotation Number</th>
                                <td class="p-3 font-semibold">{{ $lead->quotation->quotation_no ?? '-' }}</td>
                            </tr>
                            <tr class="border-t border-t-[#D9D9D9]">
                                <th class="p-3">Status</th>
                                <td class="p-3 uppercase">{{ $lead->quotation->status ?? '-' }}</td>
                            </tr>
                            <tr class="border-t border-t-[#D9D9D9]">
                                <th class="p-3">Items</th>
                                <td class="p-3">{{ $lead->quotation?->items?->first()?->description ?? '-' }}</td>
                            </tr>
                            <tr class="border-t border-t-[#D9D9D9]">
                                <th class="p-3">Qty</th>
                                <td class="p-3">{{ count($lead->quotation?->items) ?? '-' }}</td>
                            </tr>
                            <tr class="border-t border-t-[#D9D9D9]">
                                <th class="p-3">Unit Price (Rp)</th>
                                <td class="p-3">Rp {{ number_format($lead->quotation?->items?->first()?->unit_price, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="border-t border-t-[#D9D9D9]">
                                <th class="p-3">Total Discount (%)</th>
                                <td class="p-3">{{ $lead->quotation?->items?->first()?->discount_pct }}%</td>
                            </tr>
                            <tr class="border-t border-t-[#D9D9D9]">
                                <th class="p-3">Sub Total</th>
                                <td class="p-3">Rp {{ number_format($lead->quotation?->items?->first()?->line_total, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="border-t border-t-[#D9D9D9]">
                                <th class="p-3">Tax ({{ $lead->quotation->tax_pct }}%)</th>
                                <td class="p-3">Rp {{ number_format($lead->quotation?->tax_total, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="border-t border-t-[#D9D9D9]">
                                <th class="p-3">Grand Total</th>
                                <td class="p-3 font-semibold">Rp {{ number_format($lead->quotation?->grand_total, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection