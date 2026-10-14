{{-- VIEW FORM (partial; di-include dari index.blade.php, diisi oleh JS dari API) --}}
<div id="surveyFormView" class="hidden">
    <div class="pt-4 flex items-start justify-between gap-3">
        <div>
            <h1 class="font-semibold text-xl lg:text-2xl text-[#115640]">Submitted Survey Form</h1>
            <p class="text-[#757575] mt-1 flex items-center gap-2">
                <button type="button" class="back-to-response cursor-pointer">Response Detail</button>
                <i class="fas fa-chevron-right text-[9px]"></i>
                <span class="text-[#115640] font-semibold underline">View form</span>
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="px-3 py-1 rounded-full bg-[#F0F0F0] text-[#4B5563] font-semibold text-xs">View only</span>
            <span id="vfMachineBadge" class="px-3 py-1 rounded-full bg-[#E7F3EE] text-[#115640] font-semibold text-xs"></span>
        </div>
    </div>

    {{-- JOB INFORMATION --}}
    <div class="mt-4 p-5 bg-white border border-[#D9D9D9] rounded-lg">
        <h2 class="font-bold tracking-wide">JOB INFORMATION</h2>
        <hr class="my-3 border-[#EDEDED]">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Ticket ID</p>
                <p id="vfTicket" class="mt-1 font-semibold"></p>
            </div>
            <div>
                <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Customer</p>
                <p id="vfCustomer" class="mt-1 font-semibold"></p>
            </div>
            <div>
                <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Technician</p>
                <p id="vfTechnician" class="mt-1 font-semibold"></p>
            </div>
            <div>
                <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Service Date</p>
                <p id="vfServiceDate" class="mt-1 font-semibold"></p>
            </div>
        </div>
    </div>

    {{-- TECHNICIAN SERVICE RATING --}}
    <div class="mt-4 p-5 bg-white border border-[#D9D9D9] rounded-lg">
        <h2 class="font-bold tracking-wide">TECHNICIAN SERVICE RATING</h2>
        <hr class="my-3 border-[#EDEDED]">
        <div id="vfRatings"></div>
    </div>

    {{-- MACHINE WORKING STATUS --}}
    <div class="mt-4 p-5 bg-white border border-[#D9D9D9] rounded-lg">
        <h2 class="font-bold tracking-wide">MACHINE WORKING STATUS</h2>
        <hr class="my-3 border-[#EDEDED]">
        <p id="vfMachine" class="font-semibold"></p>
        <div id="vfExplanationWrap" class="hidden mt-3">
            <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Explanation</p>
            <p id="vfExplanation" class="mt-1 text-[#4B5563]"></p>
        </div>
    </div>

    {{-- COMMENTS & SUGGESTIONS --}}
    <div class="mt-4 p-5 bg-white border border-[#D9D9D9] rounded-lg">
        <h2 class="font-bold tracking-wide">COMMENTS &amp; SUGGESTIONS</h2>
        <hr class="my-3 border-[#EDEDED]">
        <p id="vfComments" class="text-[#4B5563]"></p>
    </div>

    {{-- SIGNER --}}
    <div class="mt-4 p-5 bg-white border border-[#D9D9D9] rounded-lg">
        <h2 class="font-bold tracking-wide">SIGNER</h2>
        <hr class="my-3 border-[#EDEDED]">
        <p id="vfSigner" class="font-semibold"></p>
        <img id="vfSignature" alt="Customer signature" class="hidden mt-2 max-h-32 border border-[#EDEDED] rounded-lg bg-white">
    </div>

    <div class="mt-4 pb-6">
        <button type="button" class="back-to-response px-4 py-2 bg-white border border-[#D9D9D9] rounded-lg cursor-pointer">
            <i class="fas fa-chevron-left text-[10px] mr-1"></i> Back to response
        </button>
    </div>
</div>
