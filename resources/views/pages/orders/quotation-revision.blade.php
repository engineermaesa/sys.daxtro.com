@extends('layouts.app')

@section('content')
    <section class="pt-4 text-xs lg:text-sm text-[#1E1E1E]">
        <h1 class="text-lg lg:text-2xl font-semibold text-[#115640]">{{ $revision->revision_code }}</h1>
        <a href="{{ route('quotations.revisions.index', $quotation->id) }}" class="inline-block mt-2 text-[#115640] underline">Back to revision history</a>
        <div class="mt-4 p-3 border border-[#BF6A02] bg-[#FFFBEB] rounded-lg">Viewing revision R{{ $revision->revision_no }} (read-only)</div>
        <div class="bg-white border border-[#D9D9D9] rounded-lg mt-4 p-4">
            <p><strong>Customer:</strong> {{ $quotation->lead?->name ?? '-' }}</p>
            <p><strong>Created:</strong> {{ $revision->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') ?? '-' }} WIB</p>
            <p><strong>Created by:</strong> {{ $revision->createdBy?->name ?? 'Unknown creator' }}</p>
            <div class="overflow-x-auto mt-4">
                <table class="w-full text-left">
                    <thead><tr>@foreach (['Description', 'Qty', 'Unit Price', 'Discount %', 'Total'] as $label)<th class="p-2" scope="col">{{ $label }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($revision->items as $item)
                            <tr class="border-t border-[#D9D9D9]"><td class="p-2">{{ $item->description }}</td><td class="p-2">{{ $item->qty }}</td><td class="p-2">Rp{{ number_format($item->unit_price, 0, ',', '.') }}</td><td class="p-2">{{ $item->discount_pct }}</td><td class="p-2">Rp{{ number_format($item->line_total, 0, ',', '.') }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <dl class="mt-4">
                @foreach (['Subtotal' => $revision->subtotal, 'Tax ('.$revision->tax_pct.'%)' => $revision->tax_total, 'Discount' => $revision->total_discount, 'Grand Total' => $revision->grand_total, 'Booking Fee' => $revision->booking_fee] as $label => $amount)
                    <div class="flex justify-between gap-4 py-1"><dt>{{ $label }}</dt><dd>Rp{{ number_format($amount ?? 0, 0, ',', '.') }}</dd></div>
                @endforeach
            </dl>
            <h2 class="font-semibold mt-4">Payment Terms</h2>
            @foreach ($revision->paymentTerms as $term)
                <p class="mt-1">{{ $term->term_no }}. {{ $term->percentage }}% {{ $term->description }}</p>
            @endforeach
            <h2 class="font-semibold mt-4 mb-2">Review Outcome</h2>
            @include('pages.orders.partials.quotation-revision-outcome')
        </div>
        <a href="{{ route('quotations.revisions.download', [$quotation->id, $revision->revision_no]) }}" class="inline-block my-4 px-4 py-2 rounded-lg bg-[#115640] text-white">Download PDF</a>
    </section>
@endsection
