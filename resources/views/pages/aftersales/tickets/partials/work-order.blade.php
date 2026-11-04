{{-- WORK ORDER (partial; di-include dari tickets/index.blade.php) --}}
<div id="ticketWorkOrderView" class="hidden">
    <div class="pt-4">
        <p class="text-[#757575] flex items-center gap-2">
            <button type="button" class="btn-wo-back cursor-pointer">Ticket Detail</button>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="text-[#115640] font-semibold underline">Work Order</span>
        </p>

        <div class="flex items-start justify-between" style="margin-top: 16px; gap: 12px;">
            <div>
                <h1 class="text-[#115640] font-bold text-2xl">Work Order</h1>
                <p class="text-[#757575]" style="margin-top: 4px;">Service assignment document</p>
            </div>
            <div class="flex items-center" style="gap: 10px;">
                <span class="rounded-full bg-[#FFF3CD] text-[#D97706] font-semibold text-xs" style="padding: 4px 12px;">Draft</span>
                <a id="woExportPdf" href="#" target="_blank"
                    class="bg-[#115640] text-white font-semibold rounded-md" style="padding: 8px 16px;">Export PDF</a>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 20px;">
            {{-- WO NUMBER + TICKET REFERENCE --}}
            <div class="flex items-start justify-between border-b border-[#EDEDED]" style="padding: 20px 24px;">
                <div>
                    <p class="uppercase font-semibold text-[#9CA3AF]" style="font-size: 10px;">WO Number</p>
                    <p id="woNumber" class="text-[#115640] font-bold text-lg" style="margin-top: 6px;"></p>
                </div>
                <div class="text-right">
                    <p class="uppercase font-semibold text-[#9CA3AF]" style="font-size: 10px;">Ticket Reference</p>
                    <p id="woTicketRef" class="font-semibold" style="margin-top: 6px;"></p>
                </div>
            </div>

            {{-- DETAIL GRID --}}
            <div class="grid grid-cols-1 lg:grid-cols-3" style="padding: 20px 24px; gap: 20px 24px;">
                @foreach ([
                    ['Customer', 'woCustomer'], ['Machine', 'woMachine'], ['Problem', 'woProblem'],
                    ['Technician', 'woTechnician'], ['Visit Schedule', 'woVisit'], ['SLA Target', 'woSla'],
                ] as [$label, $id])
                    <div>
                        <p class="uppercase font-semibold text-[#9CA3AF]" style="font-size: 10px;">{{ $label }}</p>
                        <p id="{{ $id }}" class="font-semibold" style="margin-top: 6px;"></p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-md bg-[#EEF4FF] text-[#2F5BD3]" style="margin-top: 16px; padding: 12px 16px;">
            Information: Work Order documents currently do not require signatures.
        </div>

        <div style="margin: 16px 0 24px;">
            <button type="button" class="btn-wo-back bg-white border border-[#D9D9D9] rounded-lg cursor-pointer" style="padding: 8px 16px;">
                <i class="bi bi-chevron-left text-[10px]"></i> Back
            </button>
        </div>
    </div>
</div>
