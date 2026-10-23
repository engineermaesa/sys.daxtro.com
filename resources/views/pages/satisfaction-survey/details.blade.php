{{-- DETAIL VIEW (partial; di-include dari index.blade.php, diisi oleh JS dari API) --}}
<div id="surveyDetailView" class="hidden">
    <div class="pt-4">
        <h1 class="font-bold text-xl lg:text-2xl text-[#115640]">Survey Response</h1>
        <p class="text-[#757575] mt-1 flex items-center gap-2">
            <button type="button" class="back-to-list cursor-pointer">Satisfaction Survey</button>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span id="dTicketCrumb" class="text-[#115640] font-semibold underline"></span>
        </p>
    </div>

    {{-- RESPONSE SUMMARY --}}
    <div class="mt-4 p-5 bg-white border border-[#D9D9D9] rounded-lg">
        <h2 class="font-bold tracking-wide">RESPONSE SUMMARY</h2>
        <hr class="my-3 border-[#EDEDED]">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Average Score</p>
                <p class="mt-1"><span id="dAvg" class="text-2xl font-bold text-[#115640]"></span> <span class="text-[#9CA3AF]">/ 3</span></p>
            </div>
            <div>
                <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Customer</p>
                <p id="dCustomer" class="mt-2 font-semibold"></p>
            </div>
            <div>
                <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Machine Status</p>
                <p id="dMachineTop" class="mt-2 font-semibold"></p>
            </div>
            <div>
                <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Submitted Via</p>
                <p id="dSource" class="mt-2 font-semibold"></p>
            </div>
        </div>
    </div>

    {{-- SURVEY FEEDBACK --}}
    <div class="mt-4 p-5 bg-white border border-[#D9D9D9] rounded-lg">
        <h2 class="font-bold tracking-wide">SURVEY FEEDBACK</h2>
        <ul id="dBreakdown" class="mt-4 space-y-3"></ul>
        <hr class="my-4 border-[#EDEDED]">
        <div class="space-y-4">
            <div>
                <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Machine Status</p>
                <p id="dMachine" class="mt-1 font-semibold"></p>
            </div>
            <div>
                <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Explanation</p>
                <p id="dExplanation" class="mt-1"></p>
            </div>
            <div>
                <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Comments &amp; Suggestions</p>
                <p id="dComments" class="mt-1"></p>
            </div>
            <div>
                <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Signer</p>
                <p id="dSigner" class="mt-1 font-semibold"></p>
                <img id="dSignature" alt="Customer signature" class="hidden mt-2 max-h-32 border border-[#EDEDED] rounded-lg bg-white">
            </div>
            <div id="dAdminReasonWrap" class="hidden">
                <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Admin Input Reason</p>
                <p id="dAdminReason" class="mt-1"></p>
            </div>
        </div>
    </div>

    {{-- REVIEW --}}
    <div class="mt-4 p-5 bg-white border border-[#D9D9D9] rounded-lg">
        <h2 class="font-bold tracking-wide">REVIEW</h2>
        <div class="mt-3 grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Status</p>
                <p id="dReviewStatus" class="mt-1 font-semibold"></p>
            </div>
            <div>
                <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Reviewed by</p>
                <p id="dReviewedBy" class="mt-1 font-semibold"></p>
            </div>
            <div>
                <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Note</p>
                <p id="dReviewNote" class="mt-1"></p>
            </div>
        </div>
    </div>

    {{-- SECURITY TRAIL --}}
    <div class="mt-4 p-5 bg-white border border-[#D9D9D9] rounded-lg">
        <h2 class="font-bold tracking-wide">SECURITY TRAIL</h2>
        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-x-8">
            @foreach ([['OTP Verified', 'dOtp'], ['Link Created By', 'dLinkBy'], ['Shared Via', 'dSharedVia'], ['IP and Device', 'dDevice'], ['Time to Complete', 'dDuration'], ['Review Reasons', 'dReason']] as [$label, $id])
                <div class="py-3 border-b border-[#EDEDED]">
                    <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">{{ $label }}</p>
                    <p id="{{ $id }}" class="mt-1 font-semibold"></p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- RESPONSES ON THIS TICKET --}}
    <div class="mt-4 p-5 bg-white border border-[#D9D9D9] rounded-lg">
        <h2 class="font-bold tracking-wide">RESPONSES ON THIS TICKET</h2>
        <div class="overflow-x-auto mt-3">
            <table class="w-full whitespace-nowrap">
                <thead>
                    <tr class="border-b border-[#D9D9D9] text-left text-xs text-[#757575]">
                        <th class="p-2">Response</th>
                        <th class="p-2">Submitted</th>
                        <th class="p-2 text-center">Average</th>
                        <th class="p-2">Review</th>
                        <th class="p-2">Reviewed by / note</th>
                    </tr>
                </thead>
                <tbody id="dResponsesBody"></tbody>
            </table>
        </div>
    </div>

    {{-- FOOTER ACTIONS --}}
    <div class="mt-4 pb-6 flex flex-wrap items-center justify-between gap-3">
        <button type="button" class="back-to-list px-4 py-2 bg-white border border-[#D9D9D9] rounded-lg cursor-pointer">
            <i class="fas fa-chevron-left text-[10px] mr-1"></i> Back
        </button>
        <div class="flex flex-wrap gap-2">
            <button type="button" id="btnViewForm" class="px-4 py-2 bg-white border border-[#D9D9D9] rounded-lg cursor-pointer">View form (read-only)</button>
            <button type="button" id="btnReview" class="hidden px-4 py-2 bg-[#115640] text-white font-semibold rounded-lg cursor-pointer">Review</button>
            <button type="button" id="btnScheduleRecoming" class="px-4 py-2 bg-[#FBF3D5] text-[#8A6D0B] font-semibold rounded-lg cursor-pointer">Schedule Recoming</button>
        </div>
    </div>
</div>
