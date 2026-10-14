{{-- SERVICE AGREEMENT SHEET (partial; di-include dari tickets/index.blade.php, diisi oleh documents-script dari GET /api/aftersales/tickets/{id}/agreement) --}}
<div id="ticketAgreementView" class="hidden">
    <div class="pt-4">
        <p class="text-[#757575] flex items-center gap-2">
            <button type="button" class="btn-sa-back cursor-pointer">Ticket Detail</button>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="text-[#115640] font-semibold underline">Service / Repair Work Agreement Sheet</span>
        </p>

        <div class="flex items-start justify-between" style="margin-top: 16px; gap: 12px;">
            <div>
                <h1 id="saTitle" class="text-[#115640] font-bold text-2xl">Service / Repair Work Agreement Sheet</h1>
                <p id="saSubtitle" class="text-[#757575]" style="margin-top: 4px;"></p>
            </div>
            <span id="saStatusBadge" class="rounded-full bg-[#FFF3CD] text-[#D97706] font-semibold text-xs shrink-0" style="padding: 4px 12px;">Draft</span>
        </div>

        <div id="saLoading" class="text-[#757575]" style="margin-top: 20px;">Loading...</div>

        <div id="saBody" class="hidden">
            {{-- CUSTOMER NAME --}}
            <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 20px; padding: 20px;">
                <label for="saCustomerName" class="block font-medium" style="margin-bottom: 8px;">Customer representative name</label>
                <input type="text" id="saCustomerName" placeholder="Name of the customer who signs this agreement"
                    class="w-full border border-[#D9D9D9] rounded-md focus:outline-none focus:border-[#115640]" style="padding: 10px 12px;">
            </div>

            {{-- CHECKLIST (dirender dari template.sections) --}}
            <div class="bg-white rounded-lg border border-[#D9D9D9]" style="margin-top: 16px; overflow: hidden;">
                <label class="flex items-center cursor-pointer border-b border-[#EDEDED]" style="padding: 14px 16px; gap: 12px;">
                    <input type="checkbox" id="saCheckAll" style="width: 18px; height: 18px;">
                    <span class="font-semibold text-[#115640]">Check all</span>
                    <span id="saTemplateVersion" class="text-[#9CA3AF] text-xs"></span>
                </label>
                <div id="saSections"></div>
            </div>

            <p id="saClosing" class="text-[#4B5563] text-xs" style="margin-top: 12px;"></p>

            {{-- SIGNATURES --}}
            <div id="saSignatures" class="grid grid-cols-1 lg:grid-cols-3" style="margin-top: 20px; gap: 16px;"></div>

            {{-- COMPLETENESS --}}
            <div id="saCompleteness" class="rounded-md text-xs" style="margin-top: 16px; padding: 10px 14px;"></div>

            <div class="flex items-center justify-between flex-wrap" style="margin: 20px 0 24px; gap: 12px;">
                <button type="button" class="btn-sa-back bg-white border border-[#D9D9D9] rounded-lg cursor-pointer" style="padding: 8px 16px;">
                    <i class="bi bi-chevron-left text-[10px]"></i> Back
                </button>
                <div class="flex items-center" style="gap: 10px;">
                    <button type="button" id="saSaveDraft" class="bg-white border border-[#115640] text-[#115640] font-semibold rounded-lg cursor-pointer" style="padding: 8px 18px;">Save Draft</button>
                    <button type="button" id="saSave" class="bg-[#115640] text-white font-semibold rounded-lg cursor-pointer" style="padding: 8px 24px;">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>
