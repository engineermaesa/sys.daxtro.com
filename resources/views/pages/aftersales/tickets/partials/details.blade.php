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

            {{-- IM-90: two-column grid (one column on narrow screens). Row order follows wireframe W8. --}}
            <div class="grid grid-cols-1 sm:grid-cols-2" style="gap: 20px 24px;">
                <div>
                    <p class="uppercase font-semibold text-[#9CA3AF]" style="font-size: 10px;">Ticket ID</p>
                    <p class="font-semibold flex items-center flex-wrap" style="margin-top: 6px; gap: 8px;">
                        <span id="tdInfoCode"></span><span id="tdPriority"></span>
                    </p>
                </div>
                @foreach ([
                    ['Progress', 'tdProgress'], ['Customer', 'tdCustomer'], ['Machine', 'tdMachine'],
                    ['Province · Region', 'tdProvinceRegion'], ['Technician', 'tdTechnician'],
                    ['Supervisor', 'tdSupervisor'], ['SLA Target', 'tdSla'],
                    ['Visit Schedule', 'tdVisit'], ['Warranty', 'tdWarranty'], ['Complaint Category', 'tdCategory'],
                ] as [$label, $id])
                    <div>
                        <p class="uppercase font-semibold text-[#9CA3AF]" style="font-size: 10px;">{{ $label }}</p>
                        <p id="{{ $id }}" class="font-semibold" style="margin-top: 6px;"></p>
                    </div>
                @endforeach
                <div class="sm:col-span-2">
                    <p class="uppercase font-semibold text-[#9CA3AF]" style="font-size: 10px;">Complaint</p>
                    <p id="tdDescription" style="margin-top: 6px;"></p>
                </div>
            </div>
        </div>

        {{-- FINAL COST (rincian sparepart + category, dirender oleh documents-script) --}}
        <div id="tdFinalCostCard" class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 20px; padding: 24px;">
            <div class="flex items-start justify-between flex-wrap" style="gap: 12px;">
                <div>
                    <h2 class="font-bold tracking-wide">FINAL COST</h2>
                    <p class="text-[#9CA3AF] text-xs" style="margin-top: 2px;">Sparepart and service charges for this ticket</p>
                </div>
                <div id="tdFinalCostChips" class="flex" style="gap: 8px;"></div>
            </div>

            <div style="margin-top: 16px;">
                <p class="uppercase font-semibold text-[#9CA3AF]" style="font-size: 10px;">Used Materials &middot; Sparepart</p>
                <div class="overflow-x-auto" style="margin-top: 6px;">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#D9D9D9] text-left text-xs text-[#757575]">
                                <th class="p-2">Item Name</th>
                                <th class="p-2">Part Code</th>
                                <th class="p-2 text-right w-20">Qty</th>
                                <th class="p-2 w-28">Unit</th>
                                <th class="p-2 text-right w-36">Unit Price</th>
                                <th class="p-2 text-right w-40">Total</th>
                            </tr>
                        </thead>
                        <tbody id="tdFcParts"></tbody>
                        <tfoot>
                            <tr class="border-t border-[#D9D9D9] bg-[#F5FAF8]">
                                <td class="p-2 font-semibold text-[#115640]" colspan="5">Total Sparepart</td>
                                <td id="tdFcPartsTotal" class="p-2 text-right font-semibold text-[#115640]">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <p class="uppercase font-semibold text-[#9CA3AF]" style="font-size: 10px;">Service Charges &middot; Cost</p>
                <div class="overflow-x-auto" style="margin-top: 6px;">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#D9D9D9] text-left text-xs text-[#757575]">
                                <th class="p-2">Category</th>
                                <th class="p-2 text-right w-48">Amount</th>
                                <th class="p-2 w-28">Currency</th>
                                <th class="p-2">Remarks</th>
                            </tr>
                        </thead>
                        <tbody id="tdFcCosts"></tbody>
                        <tfoot>
                            <tr class="border-t border-[#D9D9D9] bg-[#F5FAF8]">
                                <td class="p-2 font-semibold text-[#115640]">Total Category</td>
                                <td id="tdFcCostsTotal" class="p-2 text-right font-semibold text-[#115640]">-</td>
                                <td colspan="2"></td>
                            </tr>
                            <tr class="border-t-2 border-[#115640] bg-[#E7F3EE]">
                                <td class="p-2 font-bold text-[#115640] uppercase">Final Cost</td>
                                <td id="tdFcFinal" class="p-2 text-right font-bold text-[#115640]" style="font-size: 1.1em;">-</td>
                                <td id="tdFcFinalCurrency" class="p-2 font-semibold text-[#115640]"></td>
                                <td class="p-2 text-xs text-[#4B5563]">Total category + total sparepart</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- VISIT HISTORY (di-render renderVisitHistory() di documents-script dari GET /tickets/{id} -> visit_history) --}}
        <div id="tdVisitHistoryCard" class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 20px; padding: 24px;">
            <div class="flex items-center justify-between flex-wrap" style="gap: 12px;">
                <h2 class="font-bold tracking-wide">VISIT HISTORY</h2>
                <button type="button" id="tdRescheduleBtn" class="hidden border border-[#115640] text-[#115640] rounded-lg cursor-pointer" style="padding: 6px 12px;">
                    <i class="bi bi-calendar-event"></i> Reschedule
                </button>
            </div>
            <div class="overflow-x-auto" style="margin-top: 12px;">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-[#D9D9D9] text-left text-xs text-[#757575]">
                            <th class="p-2" style="width: 15%;">Visit</th>
                            <th class="p-2" style="width: 25%;">Date</th>
                            <th class="p-2">Note</th>
                        </tr>
                    </thead>
                    <tbody id="tdVisitHistoryBody">
                        <tr class="border-t border-[#EDEDED]"><td class="p-2 text-[#9CA3AF]" colspan="3">No visit yet.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- SUPPORTING DOCUMENTS (status dokumen dari GET /api/aftersales/tickets/{id}/documents, dirender oleh documents-script) --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 20px; padding: 24px;">
            <h2 class="font-bold tracking-wide">SUPPORTING DOCUMENTS</h2>

            <div id="tdDocsWarning" class="hidden rounded-md bg-[#FFF3CD] text-[#8A6D00] text-xs" style="margin-top: 12px; padding: 10px 14px;"></div>

            <div style="margin-top: 12px;">
                {{-- WORK ORDER --}}
                <div class="flex items-center justify-between flex-wrap border-b border-[#EDEDED]" style="padding: 14px 0; gap: 12px;">
                    <div>
                        <p class="font-semibold">Work Order</p>
                        <p class="text-[#9CA3AF] text-xs" style="margin-top: 2px;">Document ready</p>
                    </div>
                    <div class="flex items-center" style="gap: 8px;">
                        <button type="button" id="tdWoView" class="bg-white border border-[#D9D9D9] rounded-md cursor-pointer" style="padding: 8px 14px;">View</button>
                        <a id="tdWoDownload" href="#" target="_blank" class="bg-white border border-[#D9D9D9] text-[#115640] font-semibold rounded-md" style="padding: 8px 14px;">Download PDF</a>
                    </div>
                </div>

                {{-- SERVICE AGREEMENT & SERVICE FORM --}}
                <div id="tdDocsDynamic">
                    <p class="text-[#9CA3AF] text-xs" style="padding: 14px 0;">Loading documents...</p>
                </div>
            </div>
        </div>

        {{-- SATISFACTION SURVEY --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 20px; padding: 24px;">
            <div class="flex items-start justify-between" style="gap: 12px;">
                <div>
                    <h2 class="font-bold text-base">Satisfaction Survey</h2>
                    <p class="text-[#9CA3AF] text-xs" style="margin-top: 2px;">Secure customer feedback for this service visit</p>
                </div>
                <span id="tdSurveyStatus" class="rounded-full bg-[#E7F3EE] text-[#115640] font-semibold text-xs" style="padding: 3px 12px;">Loading...</span>
            </div>

            {{-- Resubmission link (FR-E32): respons current ditolak dan link baru Active --}}
            <div id="tdResubmitBanner" class="hidden rounded-md text-xs" style="margin-top: 16px; padding: 10px 14px; background:#FFF3CD; color:#8A6D00;"></div>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between rounded-lg bg-[#F7F7F7]" style="margin-top: 16px; padding: 14px 16px; gap: 12px;">
                <a id="tdSurveyUrl" class="block min-w-0 text-[#4B5563] break-all md:truncate underline" target="_blank" rel="noopener noreferrer"></a>
                <div class="flex flex-wrap items-center md:shrink-0" style="gap: 8px;">
                    <button type="button" id="tdFillOnBehalf" class="hidden bg-white border border-[#D9D9D9] text-[#1E1E1E] font-semibold rounded-md cursor-pointer" style="padding: 8px 16px;">Fill on Behalf</button>
                    <button type="button" id="tdShareLink" disabled class="bg-[#115640] text-white font-semibold rounded-md cursor-pointer disabled:opacity-50" style="padding: 8px 16px;">Share Link</button>
                    <button type="button" id="tdShareWhatsapp" disabled class="bg-white border border-[#115640] text-[#115640] font-semibold rounded-md cursor-pointer disabled:opacity-50" style="padding: 8px 16px;">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </button>
                </div>
            </div>

            {{-- Teknisi: hanya status Filled (BR-S6, tanpa skor) --}}
            <p id="tdSurveyFilled" class="hidden text-xs" style="margin-top: 14px;"></p>

            {{-- After Sales: respons current + History (kontrak 3.2 survey_responses) --}}
            <div id="tdSurveyResponsesWrap" class="hidden" style="margin-top: 16px;">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#D9D9D9] text-left text-xs text-[#757575]">
                                <th class="p-2">Response</th>
                                <th class="p-2">Submitted</th>
                                <th class="p-2 text-center">Average</th>
                                <th class="p-2 text-center">Machine OK</th>
                                <th class="p-2">Review</th>
                            </tr>
                        </thead>
                        <tbody id="tdSurveyResponsesBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div style="margin: 20px 0 24px;">
            <button type="button" class="btn-ticket-details-back bg-white border border-[#D9D9D9] rounded-lg cursor-pointer" style="padding: 8px 16px;">
                <i class="bi bi-chevron-left text-[10px]"></i> Back
            </button>
        </div>
    </div>
</div>
