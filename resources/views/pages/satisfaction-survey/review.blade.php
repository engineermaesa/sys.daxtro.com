{{-- D12 REVIEW RESPONSE (partial; di-include dari index.blade.php, diisi oleh JS dari API) --}}
<div id="surveyReviewView" class="hidden">
    <div class="pt-4 flex items-start justify-between gap-3">
        <div>
            <h1 class="font-bold text-xl lg:text-2xl text-[#115640]">Review Response</h1>
            <p class="text-[#757575] mt-1 flex items-center gap-2">
                <button type="button" class="back-to-list cursor-pointer">Satisfaction Survey</button>
                <i class="fas fa-chevron-right text-[9px]"></i>
                <span id="rvTicketCrumb" class="text-[#115640] font-semibold underline"></span>
            </p>
        </div>
        <button type="button" id="rvStartReview" class="hidden px-4 py-2 bg-[#115640] text-white font-semibold rounded-lg cursor-pointer shrink-0" style="margin-top: 14px;">Review Response</button>
    </div>

    <div class="mt-4 rounded-lg" style="padding: 12px 16px; background:#FFF3CD; color:#8A6D00;">
        This ticket cannot be set to Closed until this response is approved or the customer submits a new response.
    </div>

    {{-- A. JOB INFORMATION --}}
    <div class="mt-4 p-5 bg-white border border-[#D9D9D9] rounded-lg">
        <h2 class="font-bold tracking-wide">A. JOB INFORMATION</h2>
        <div class="mt-3 grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ([['Ticket', 'rvTicket'], ['Customer', 'rvCustomer'], ['Technician', 'rvTechnician'], ['Service Date', 'rvServiceDate']] as [$label, $id])
                <div>
                    <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">{{ $label }}</p>
                    <p id="{{ $id }}" class="mt-1 font-semibold"></p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- B. RATINGS --}}
    <div class="mt-4 p-5 bg-white border border-[#D9D9D9] rounded-lg">
        <div class="flex items-center justify-between">
            <h2 class="font-bold tracking-wide">B. RATINGS</h2>
            <p><span id="rvAvg" class="text-xl font-bold text-[#115640]"></span> <span class="text-[#9CA3AF]">/ 3</span></p>
        </div>
        <ul id="rvRatings" class="mt-4 space-y-3"></ul>
    </div>

    {{-- C. MACHINE --}}
    <div class="mt-4 p-5 bg-white border border-[#D9D9D9] rounded-lg">
        <h2 class="font-bold tracking-wide">C. MACHINE WORKING PROPERLY?</h2>
        <p id="rvMachine" class="mt-3 font-semibold"></p>
        <div id="rvMachineNoteWrap" class="hidden mt-2">
            <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">Explanation</p>
            <p id="rvMachineNote" class="mt-1"></p>
        </div>
    </div>

    {{-- D. COMMENTS --}}
    <div class="mt-4 p-5 bg-white border border-[#D9D9D9] rounded-lg">
        <h2 class="font-bold tracking-wide">D. COMMENTS &amp; SUGGESTIONS</h2>
        <p id="rvComments" class="mt-3"></p>
    </div>

    {{-- WHY THIS WAS FLAGGED --}}
    <div class="mt-4 p-5 rounded-lg" style="background:#FFF8EC; border:1px solid #F5DDB3;">
        <h2 class="font-bold tracking-wide" style="color:#8A6D00;">WHY THIS WAS FLAGGED</h2>
        <ul id="rvFlagged" class="mt-3 space-y-1" style="list-style: disc; padding-left: 18px; color:#8A6D00;"></ul>
    </div>

    {{-- SECURITY TRAIL --}}
    <div class="mt-4 p-5 bg-white border border-[#D9D9D9] rounded-lg">
        <h2 class="font-bold tracking-wide">SECURITY TRAIL</h2>
        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-x-8">
            @foreach ([['OTP Verified', 'rvOtp'], ['Link Created By', 'rvLinkBy'], ['Shared Via', 'rvSharedVia'], ['IP and Device', 'rvDevice'], ['Time to Complete', 'rvDuration']] as [$label, $id])
                <div class="py-3 border-b border-[#EDEDED]">
                    <p class="text-[10px] uppercase font-semibold text-[#9CA3AF]">{{ $label }}</p>
                    <p id="{{ $id }}" class="mt-1 font-semibold"></p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- DECISION --}}
    <div id="rvDecisionCard" class="hidden mt-4 p-5 bg-white border border-[#D9D9D9] rounded-lg">
        <h2 class="font-bold tracking-wide">DECISION</h2>
        <p id="rvDecisionLocked" class="hidden mt-3 text-[#757575]"></p>
        <div id="rvDecisionForm" class="mt-3">
            <div class="flex flex-wrap gap-6">
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="rvDecision" value="approve"> <span class="font-semibold">Approve</span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="rvDecision" value="reject"> <span class="font-semibold">Reject</span></label>
            </div>
            <p class="text-[#757575] text-xs mt-2">Reject discards this response and issues a new survey link for the customer. Approve counts it toward the technician rating (unless entered by admin).</p>
            <label for="rvNote" class="block font-semibold mt-4 mb-1">Note <span id="rvNoteRequired" class="hidden" style="color:#EC221F;">*</span></label>
            <textarea id="rvNote" rows="3" maxlength="2000" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2" placeholder="Reason for the decision"></textarea>
            <p id="rvFeedback" class="text-xs mt-2 mb-0" style="color:#EC221F;"></p>
        </div>
    </div>

    <div class="mt-4 pb-6 flex flex-wrap items-center justify-between gap-3">
        <button type="button" class="back-to-response px-4 py-2 bg-white border border-[#D9D9D9] rounded-lg cursor-pointer">
            <i class="fas fa-chevron-left text-[10px] mr-1"></i> Back
        </button>
        <button type="button" id="rvSubmit" class="px-5 py-2 bg-[#115640] text-white font-semibold rounded-lg cursor-pointer">Submit decision</button>
    </div>
</div>
