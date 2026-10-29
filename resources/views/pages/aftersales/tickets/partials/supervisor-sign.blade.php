{{-- REVIEW AND SIGN - SPV Technician (v3.0 W4; partial di-include dari tickets/index.blade.php).
     Dua tab: Service Inspection & Report dan Service Agreement. "Save and sign" aktif setelah SPV menandatangani;
     saat kedua dokumen tertanda tangan, BE menerbitkan Documentation Published. Tanda tangan SPV yang sudah ada tetap berlaku saat Recoming. --}}
<div id="ticketSupervisorSignView" class="hidden">
    <div class="pt-4">
        <div class="flex items-start justify-between" style="gap: 12px;">
            <p class="text-[#757575] flex items-center gap-2">
                <button type="button" class="btn-ss-list text-[#115640] font-semibold underline cursor-pointer">All Ticket</button>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <button type="button" class="btn-ss-back cursor-pointer">Detail</button>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span id="ssCrumb" class="text-[#115640] font-semibold underline">Service Form</span>
            </p>
            <p class="text-[#757575] text-xs">Logged in as SPV Technician</p>
        </div>

        <h1 id="ssTitle" class="text-[#115640] text-2xl" style="margin-top: 20px;">Review and Sign</h1>
        <p id="ssTicket" class="text-[#757575] text-xs" style="margin-top: 6px;"></p>

        {{-- TABS: satu dokumen per tab --}}
        <div class="flex items-center gap-1 border-b border-b-[#D9D9D9]" style="margin-top: 16px;" role="tablist">
            <button type="button" class="ss-tab px-4 py-2 font-semibold cursor-pointer border-b-2 -mb-px" data-doc="service_form" role="tab">Service Inspection &amp; Report</button>
            <button type="button" class="ss-tab px-4 py-2 font-semibold cursor-pointer border-b-2 -mb-px" data-doc="agreement" role="tab">Service Agreement</button>
        </div>

        {{-- SUMMARY + SIGNATURE STATUS --}}
        <div class="grid grid-cols-1 lg:grid-cols-2" style="margin-top: 20px; gap: 16px;">
            <div class="bg-white rounded-lg border border-[#D9D9D9]" style="padding: 24px;">
                <h2 class="font-bold tracking-wide">DOCUMENT SUMMARY</h2>
                <hr class="border-[#EDEDED]" style="margin: 12px 0 16px;">
                <p><span class="font-semibold">Customer:</span> <span id="ssCustomer"></span></p>
                <p style="margin-top: 8px;"><span class="font-semibold">Machine:</span> <span id="ssMachine"></span></p>
                <p style="margin-top: 8px;"><span class="font-semibold">Technician:</span> <span id="ssTechnician"></span></p>
            </div>

            <div class="bg-white rounded-lg border border-[#D9D9D9]" style="padding: 24px;">
                <h2 class="font-bold tracking-wide">DOCUMENT STATUS</h2>
                <hr class="border-[#EDEDED]" style="margin: 12px 0 16px;">
                <span id="ssStatusBadge" class="inline-block rounded-full bg-[#FFF3CD] text-[#D97706] font-semibold text-xs" style="padding: 4px 12px;">Awaiting SPV signature</span>
                <p id="ssCompleteness" class="text-[#9CA3AF] text-xs" style="margin-top: 14px;"></p>
            </div>
        </div>

        {{-- RECOMING FINDINGS (read-only; hanya ada bila ticket punya siklus Recoming). [API] t.recoming_cycles[].findings --}}
        <div id="ssFindingsCard" class="hidden bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 16px; padding: 24px;">
            <h2 class="font-bold tracking-wide" id="ssFindingsTitle">RECOMING FINDINGS</h2>
            <hr class="border-[#EDEDED]" style="margin: 12px 0 16px;">
            <div class="grid" style="grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px;">
                <div><p class="uppercase font-semibold text-[#9CA3AF]" style="font-size: 10px;">Problem</p><p id="ssFindProblem" class="text-xs whitespace-pre-wrap" style="margin-top: 4px;"></p></div>
                <div><p class="uppercase font-semibold text-[#9CA3AF]" style="font-size: 10px;">Root Cause</p><p id="ssFindRoot" class="text-xs whitespace-pre-wrap" style="margin-top: 4px;"></p></div>
                <div><p class="uppercase font-semibold text-[#9CA3AF]" style="font-size: 10px;">Action</p><p id="ssFindAction" class="text-xs whitespace-pre-wrap" style="margin-top: 4px;"></p></div>
            </div>
        </div>

        {{-- TECHNICIAN SIGNATURE (read-only) --}}
        <div id="ssTechCard" class="hidden bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 16px; padding: 24px;">
            <h2 class="font-bold tracking-wide">TECHNICIAN SIGNATURE</h2>
            <hr class="border-[#EDEDED]" style="margin: 12px 0 16px;">
            <img id="ssTechImage" alt="Technician signature" class="border border-[#EDEDED] rounded-md bg-white" style="max-height: 120px;">
            <p id="ssTechMeta" class="text-[#757575] text-xs" style="margin-top: 8px;"></p>
        </div>

        {{-- SUPERVISOR APPROVAL --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 16px; padding: 24px;">
            <h2 class="font-bold tracking-wide">SUPERVISOR APPROVAL</h2>
            <hr class="border-[#EDEDED]" style="margin: 12px 0 20px;">

            <label class="block font-medium" style="margin-bottom: 8px;">Checked By (Supervisor) Name</label>
            <input type="text" id="ssName" readonly class="w-full border border-[#D9D9D9] rounded-md" style="padding: 10px 12px; background: #F3F4F6;">

            <div id="ssSignArea">
                <label class="block font-medium" style="margin: 20px 0 8px;">Signature</label>
                <div class="relative rounded-md" style="border: 1px dashed #B0B0B0; background: #FAFAFA; height: 170px;">
                    <canvas id="ssCanvas" style="width: 100%; height: 100%; display: block; touch-action: none; cursor: crosshair;"></canvas>
                    <span id="ssPlaceholder" class="absolute text-[#B0B0B0] pointer-events-none" style="inset: 0; display: flex; align-items: center; justify-content: center;">Sign here</span>
                </div>
                <p id="ssError" class="hidden text-[#EC221F] text-xs" style="margin-top: 6px;">Please sign before submitting.</p>

                <button type="button" id="ssClear" class="bg-white border border-[#D9D9D9] rounded-md cursor-pointer" style="margin-top: 12px; padding: 8px 16px;">Clear</button>
            </div>
            <div id="ssSignedArea" class="hidden" style="margin-top: 20px;">
                <p class="font-medium" style="margin-bottom: 8px;">Signature</p>
                <p id="ssSignedNote" class="text-[#757575] text-xs" style="margin-bottom: 8px;">Your existing signature stays valid. It does not need to be signed again.</p>
                <img id="ssSignedImage" alt="Supervisor signature" class="border border-[#EDEDED] rounded-md bg-white" style="max-height: 140px;">
            </div>
        </div>

        <div class="flex items-center justify-between flex-wrap" style="margin: 16px 0 24px; gap: 12px;">
            <button type="button" class="btn-ss-back bg-white border border-[#D9D9D9] rounded-lg cursor-pointer" style="padding: 8px 16px;">
                <i class="bi bi-chevron-left text-[10px]"></i> Back
            </button>
            <div class="flex items-center" style="gap: 12px;">
                <span class="text-[#757575] text-xs">Signing confirms that the document has been checked.</span>
                <button type="button" id="ssSign" disabled class="bg-[#115640] text-white font-semibold rounded-lg cursor-pointer disabled:opacity-50" style="padding: 8px 22px;">Save and sign</button>
            </div>
        </div>
    </div>
</div>
