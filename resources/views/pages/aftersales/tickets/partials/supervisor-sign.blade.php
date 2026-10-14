{{-- SUPERVISOR SIGNATURE · SERVICE FORM (partial; di-include dari tickets/index.blade.php, frontend only) --}}
<div id="ticketSupervisorSignView" class="hidden">
    <div class="pt-4">
        <div class="flex items-start justify-between" style="gap: 12px;">
            <p class="text-[#757575] flex items-center gap-2">
                <button type="button" class="btn-ss-list text-[#115640] font-semibold underline cursor-pointer">All Ticket</button>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <button type="button" class="btn-ss-back cursor-pointer">Detail</button>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-[#115640] font-semibold underline">Service Form</span>
            </p>
            <p class="text-[#757575] text-xs">Logged in as supervisor</p>
        </div>

        <h1 class="text-[#115640] text-2xl" style="margin-top: 20px;">Supervisor Signature · Service Form</h1>
        <p id="ssTicket" class="text-[#757575] text-xs" style="margin-top: 6px;"></p>

        {{-- FORM SUMMARY + SIGNATURE STATUS --}}
        <div class="grid grid-cols-1 lg:grid-cols-2" style="margin-top: 20px; gap: 16px;">
            <div class="bg-white rounded-lg border border-[#D9D9D9]" style="padding: 24px;">
                <h2 class="font-bold tracking-wide">FORM SUMMARY</h2>
                <hr class="border-[#EDEDED]" style="margin: 12px 0 16px;">
                <p><span class="font-semibold">Customer:</span> <span id="ssCustomer"></span></p>
                <p style="margin-top: 8px;"><span class="font-semibold">Machine:</span> <span id="ssMachine"></span></p>
                <p style="margin-top: 8px;"><span class="font-semibold">Technician:</span> <span id="ssTechnician"></span></p>
            </div>

            <div class="bg-white rounded-lg border border-[#D9D9D9]" style="padding: 24px;">
                <h2 class="font-bold tracking-wide">SIGNATURE STATUS</h2>
                <hr class="border-[#EDEDED]" style="margin: 12px 0 16px;">
                <span id="ssStatusBadge" class="inline-block rounded-full bg-[#FFF3CD] text-[#D97706] font-semibold text-xs" style="padding: 4px 12px;">Awaiting signature</span>
                <p class="text-[#9CA3AF] text-xs" style="margin-top: 14px;">Customer signature has already been recorded.</p>
            </div>
        </div>

        {{-- SUPERVISOR APPROVAL --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 16px; padding: 24px;">
            <h2 class="font-bold tracking-wide">SUPERVISOR APPROVAL</h2>
            <hr class="border-[#EDEDED]" style="margin: 12px 0 20px;">

            <label class="block font-medium" style="margin-bottom: 8px;">Checked By (Supervisor) Name</label>
            <input type="text" id="ssName" readonly class="w-full border border-[#D9D9D9] rounded-md" style="padding: 10px 12px; background: #F3F4F6;">

            <label class="block font-medium" style="margin: 20px 0 8px;">Signature</label>
            <div class="relative rounded-md" style="border: 1px dashed #B0B0B0; background: #FAFAFA; height: 170px;">
                <canvas id="ssCanvas" style="width: 100%; height: 100%; display: block; touch-action: none; cursor: crosshair;"></canvas>
                <span id="ssPlaceholder" class="absolute text-[#B0B0B0] pointer-events-none" style="inset: 0; display: flex; align-items: center; justify-content: center;">Sign here</span>
            </div>
            <p id="ssError" class="hidden text-[#EC221F] text-xs" style="margin-top: 6px;">Please sign before submitting.</p>

            <button type="button" id="ssClear" class="bg-white border border-[#D9D9D9] rounded-md cursor-pointer" style="margin-top: 12px; padding: 8px 16px;">Clear</button>
        </div>

        <div class="flex items-center justify-between flex-wrap" style="margin: 16px 0 24px; gap: 12px;">
            <button type="button" class="btn-ss-back bg-white border border-[#D9D9D9] rounded-lg cursor-pointer" style="padding: 8px 16px;">
                <i class="bi bi-chevron-left text-[10px]"></i> Back
            </button>
            <div class="flex items-center" style="gap: 12px;">
                <span class="text-[#757575] text-xs">Signing confirms that the service form has been checked.</span>
                <button type="button" id="ssSign" class="bg-[#115640] text-white font-semibold rounded-lg cursor-pointer" style="padding: 8px 22px;">Sign</button>
            </div>
        </div>
    </div>
</div>
