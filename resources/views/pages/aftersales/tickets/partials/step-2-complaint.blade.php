{{-- STEP 2: COMPLAINT DETAILS --}}
<div class="wizard-step hidden bg-white rounded-lg border border-[#D9D9D9] p-5" data-step="2">
    <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">3. Complaint & Ticket Details</h6>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div>
            <label class="flex items-center gap-1 text-sm font-medium mb-1">
                Ticket Number
                <i class="bi bi-info-circle text-[#757575] cursor-pointer" data-toggle="tooltip" data-placement="top"
                    title="{{ isset($ticket) ? 'Auto generated upon saving' : 'Preview of the number that will be assigned on saving' }}"></i>
            </label>
            <input type="text" id="field-ticket-code" disabled value="Generating..."
                class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F0FAF5] text-[#115640] font-semibold focus:outline-none!">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Complaint Category<span class="text-red-600">*</span></label>
            <select id="field-category" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!" required>
                <option value="">Select</option>
                <option value="electrical">Electrical</option>
                <option value="mechanical">Mechanical</option>
                <option value="refrigeration_system">Refrigeration System</option>
                <option value="production">Production</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Priority Level</label>
            <select id="field-priority" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
            </select>
        </div>
    </div>

    <div class="mt-4">
        <label class="block text-sm font-medium mb-1">Problem Description<span class="text-red-600">*</span></label>
        <textarea id="field-description" rows="4" placeholder="Describe the complaint or problem encountered..."
            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!" required></textarea>
    </div>
</div>
