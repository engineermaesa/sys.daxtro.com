@extends('layouts.app')

@section('content')
    <section class="pt-4 text-xs lg:text-sm text-[#1E1E1E]">
        <h1 class="font-semibold text-lg lg:text-2xl text-[#115640]">Revision History</h1>
        <div class="flex items-center gap-3 mt-2">
            <a href="{{ in_array(auth()->user()->role?->code, ['sales', 'branch_manager']) ? route('leads.my') : route('quotations.index') }}" class="text-[#757575] underline">Quotations</a>
            <span aria-hidden="true">/</span>
            <span>{{ $quotation->quotation_no }}</span>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 bg-white border border-[#D9D9D9] rounded-lg p-4 mt-4">
            @foreach ([
                'Quotation No' => $quotation->quotation_no,
                'Customer' => $quotation->lead?->name ?? '-',
                'Sales' => $claim?->sales?->name ?? $quotation->createdBy?->name ?? '-',
                'Current Revision' => $quotation->revision_code ?? 'R'.$quotation->revision_no,
                'Status' => $quotation->status_label,
                'Grand Total' => 'Rp'.number_format($quotation->grand_total, 0, ',', '.'),
            ] as $label => $value)
                <div><dt class="text-[#757575]">{{ $label }}</dt><dd class="font-semibold mt-1">{{ $value }}</dd></div>
            @endforeach
        </dl>

        <div class="bg-white border border-[#D9D9D9] rounded-lg mt-4 overflow-x-auto">
            <table class="w-full text-left">
                <caption class="text-left p-4 font-semibold border-b border-[#D9D9D9]">Submitted Revisions</caption>
                <thead class="bg-[#F5F5F5]">
                    <tr>
                        @foreach (['Revision', 'Created', 'Grand Total', 'Outcome', 'Actions'] as $heading)
                            <th scope="col" class="p-3">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($revisions as $revision)
                        <tr class="border-t border-[#D9D9D9] align-top" data-revision="{{ $revision->revision_no }}">
                            <td class="p-3">
                                <p class="font-semibold">{{ $revision->revision_code }}</p>
                                @if ($revision->revision_no === $quotation->revision_no)
                                    <span class="inline-block bg-[#CFF7D3] text-[#02542D] rounded px-2 py-1 mt-2">Current</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <p>{{ $revision->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') ?? '-' }} WIB</p>
                                <p class="text-[#757575] mt-1">{{ $revision->createdBy?->name ?? 'Unknown creator' }}</p>
                            </td>
                            <td class="p-3 whitespace-nowrap">Rp{{ number_format($revision->grand_total, 0, ',', '.') }}</td>
                            <td class="p-3 min-w-[240px]">
                                @include('pages.orders.partials.quotation-revision-outcome', ['reviews' => $reviewsByRevision->get($revision->revision_no, collect())])
                            </td>
                            <td class="p-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('quotations.revisions.show', [$quotation->id, $revision->revision_no]) }}" class="px-3 py-2 bg-[#115640] text-white rounded-lg" aria-label="Open revision R{{ $revision->revision_no }}">Open</a>
                                    <a href="{{ route('quotations.revisions.download', [$quotation->id, $revision->revision_no]) }}" class="px-3 py-2 border border-[#115640] text-[#115640] rounded-lg whitespace-nowrap" aria-label="Download PDF for revision R{{ $revision->revision_no }}">Download PDF</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-5 text-center text-[#757575]">No submitted revisions available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
