{{-- RECOMING MODAL (partial; di-include dari index.blade.php) --}}
{{-- Jarak memakai inline style agar tidak bergantung pada Tailwind yang perlu di-build ulang --}}
<div id="recomingModal" class="hidden fixed inset-0 bg-black/40 z-[9999] flex items-center justify-center p-4">
    <div class="bg-white rounded-lg w-full max-w-md max-h-[92vh] overflow-y-auto text-xs">
        <div class="border-b border-[#EDEDED]" style="padding: 16px 20px;">
            <h3 class="font-bold text-base text-[#303030]">Job not finished (Recoming)</h3>
            <p id="rcTicket" class="text-[11px] font-semibold text-[#115640]" style="margin-top: 4px;"></p>
        </div>

        <div style="padding: 24px 20px; display: flex; flex-direction: column; gap: 20px;">
            <div>
                <p class="font-medium" style="margin-bottom: 10px;">Reason</p>
                <div class="flex flex-wrap" style="gap: 10px;">
                    @foreach (['Spare part not available yet', 'Further work needed', 'Site access / conditions', 'Other'] as $reason)
                        <button type="button"
                            class="rc-reason rounded-full border border-[#D9D9D9] bg-white font-medium cursor-pointer"
                            style="padding: 6px 14px;">
                            {{ $reason }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="rcDate" class="block font-medium" style="margin-bottom: 10px;">Follow-up visit date <span class="text-[#EC221F]">*</span></label>
                <input type="date" id="rcDate" class="w-full border border-[#D9D9D9] rounded-md focus:outline-none focus:border-[#115640]"
                    style="padding: 8px 12px;">
                <p id="rcDateError" class="hidden text-[11px] text-[#EC221F]" style="margin-top: 6px;">Follow-up visit date is required.</p>
            </div>

            <div>
                <label for="rcNotes" class="block font-medium" style="margin-bottom: 10px;">Notes</label>
                <textarea id="rcNotes" rows="3" placeholder="Details of the remaining work..."
                    class="w-full border border-[#D9D9D9] rounded-md focus:outline-none focus:border-[#115640]"
                    style="padding: 8px 12px;"></textarea>
            </div>

            <div class="rounded-md bg-[#EEF4FF] text-[#2F5BD3] text-[11px] leading-relaxed" style="padding: 14px 16px;">
                <p class="font-bold tracking-wide" style="margin-bottom: 8px;">AFTER SAVING</p>
                <ul style="display: flex; flex-direction: column; gap: 4px;">
                    <li>• Ticket progress changes to Recoming (second visit scheduled).</li>
                    <li>• Service Form remains in Draft status.</li>
                    <li>• The satisfaction survey link is temporarily disabled.</li>
                    <li>• Ticket status returns to On Site on the follow-up visit date.</li>
                </ul>
            </div>
        </div>

        <div class="border-t border-[#EDEDED] flex justify-end" style="padding: 12px 20px; gap: 8px;">
            <button type="button" id="rcCancel" class="bg-white border border-[#D9D9D9] rounded-md cursor-pointer" style="padding: 6px 14px;">Cancel</button>
            <button type="button" id="rcSave" class="bg-[#115640] text-white font-semibold rounded-md cursor-pointer" style="padding: 6px 14px;">Save Recoming</button>
        </div>
    </div>
</div>
