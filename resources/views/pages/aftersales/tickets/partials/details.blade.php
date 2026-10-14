{{-- TICKET DETAILS (partial; di-include dari tickets/index.blade.php, diisi oleh JS dari data row API) --}}
<div id="ticketDetailsView" class="hidden">
    <div class="pt-4">
        <p class="text-[#757575] flex items-center gap-2">
            <button type="button" class="btn-ticket-details-back cursor-pointer">All Ticket</button>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="text-[#115640] font-semibold underline">Detail</span>
        </p>

        <h1 id="tdCode" class="text-[#115640] font-semibold text-2xl" style="margin-top: 20px;"></h1>
        <p class="text-[#757575]" style="margin-top: 4px;">Service ticket overview and supporting documents</p>

        {{-- TICKET INFORMATION --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 20px; padding: 24px;">
            <h2 class="font-bold tracking-wide">TICKET INFORMATION</h2>
            <hr class="border-[#EDEDED]" style="margin: 12px 0 20px;">

            <div class="grid grid-cols-2 lg:grid-cols-4" style="gap: 20px 24px;">
                @foreach ([
                    ['Customer', 'tdCustomer'], ['Machine', 'tdMachine'], ['Technician', 'tdTechnician'], ['Supervisor', 'tdSupervisor'],
                    ['Complaint Category', 'tdCategory'], ['Visit Schedule', 'tdVisit'], ['SLA Target', 'tdSla'], ['Priority', 'tdPriority'],
                ] as [$label, $id])
                    <div>
                        <p class="uppercase font-semibold text-[#9CA3AF]" style="font-size: 10px;">{{ $label }}</p>
                        <p id="{{ $id }}" class="font-semibold" style="margin-top: 6px;"></p>
                    </div>
                @endforeach
            </div>

            <hr class="border-[#EDEDED]" style="margin: 20px 0;">
            <p class="uppercase font-semibold text-[#9CA3AF]" style="font-size: 10px;">Problem Description</p>
            <p id="tdDescription" style="margin-top: 6px;"></p>
        </div>

        {{-- SUPPORTING DOCUMENTS --}}
        {{-- TODO: status dokumen, Service Agreement & Service Form menunggu backend --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 20px; padding: 24px;">
            <h2 class="font-bold tracking-wide">SUPPORTING DOCUMENTS</h2>

            @php
                $docs = [
                    ['Work Order', 'Document ready', false, true],
                    ['Service Agreement', 'Document ready', false, false],
                    ['Service Form', 'Awaiting supervisor signature', true, false],
                ];
            @endphp

            <div style="margin-top: 12px;">
                @foreach ($docs as [$title, $status, $needsSign, $isWorkOrder])
                    <div class="flex items-center justify-between flex-wrap border-b border-[#EDEDED] last:border-b-0" style="padding: 14px 0; gap: 12px;">
                        <div>
                            <p class="font-semibold">{{ $title }}</p>
                            <p @if ($needsSign) id="tdSfStatus" @endif class="text-[#9CA3AF] text-xs" style="margin-top: 2px;">{{ $status }}</p>
                        </div>
                        <div class="flex items-center" style="gap: 8px;">
                            @if ($needsSign)
                                <button type="button" id="tdSupervisorSign" class="bg-[#E7F3EE] text-[#115640] font-semibold rounded-md cursor-pointer" style="padding: 8px 14px;">Supervisor Sign</button>
                            @endif
                            @if ($isWorkOrder)
                                <button type="button" id="tdWoView" class="bg-white border border-[#D9D9D9] rounded-md cursor-pointer" style="padding: 8px 14px;">View</button>
                                <a id="tdWoDownload" href="#" target="_blank" class="bg-white border border-[#D9D9D9] text-[#115640] font-semibold rounded-md" style="padding: 8px 14px;">Download PDF</a>
                            @else
                                <button type="button" @if ($title === 'Service Agreement') id="tdSaView" @elseif ($title === 'Service Form') id="tdSfView" @endif class="bg-white border border-[#D9D9D9] rounded-md cursor-pointer" style="padding: 8px 14px;">View</button>
                                <button type="button" class="bg-white border border-[#D9D9D9] text-[#115640] font-semibold rounded-md cursor-pointer" style="padding: 8px 14px;">Download PDF</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- SATISFACTION SURVEY --}}
        {{-- TODO: link survey dibuat otomatis oleh backend saat ticket dibuat --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 20px; padding: 24px;">
            <div class="flex items-start justify-between" style="gap: 12px;">
                <div>
                    <h2 class="font-bold text-base">Satisfaction Survey</h2>
                    <p class="text-[#9CA3AF] text-xs" style="margin-top: 2px;">Secure customer feedback for this service visit</p>
                </div>
                <span class="rounded-full bg-[#E7F3EE] text-[#115640] font-semibold text-xs" style="padding: 3px 12px;">Active</span>
            </div>

            <div class="flex items-center justify-between rounded-lg bg-[#F7F7F7]" style="margin-top: 16px; padding: 14px 16px; gap: 12px;">
                <span id="tdSurveyUrl" class="text-[#4B5563] truncate"></span>
                <button type="button" id="tdShareLink" class="bg-[#115640] text-white font-semibold rounded-md cursor-pointer shrink-0" style="padding: 8px 16px;">Share Link</button>
            </div>
        </div>

        <div style="margin: 20px 0 24px;">
            <button type="button" class="btn-ticket-details-back bg-white border border-[#D9D9D9] rounded-lg cursor-pointer" style="padding: 8px 16px;">
                <i class="bi bi-chevron-left text-[10px]"></i> Back
            </button>
        </div>
    </div>
</div>
