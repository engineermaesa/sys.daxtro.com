{{-- STEP 3: ASSIGNMENT & APPROVAL --}}
<div class="wizard-step hidden bg-white rounded-lg border border-[#D9D9D9] p-5" data-step="3">
    <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">4. Assignment & Approval</h6>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Assigned Technician</label>
            <select id="field-technician" class="select2 w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!" required>
                <option value="">Select Technician</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Visit Schedule</label>
            <input type="datetime-local" id="field-visit-date"
                class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!" required>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">SLA Target</label>
            <input type="date" id="field-sla-due-date"
                class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Supervisor Approval</label>
            <select id="field-supervisor" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                <option value="">Select Supervisor</option>
                {{-- TODO: fetch GET /api/aftersales/tickets/supervisors (new endpoint, after_sales role) then populate <option value="{id}">{name}</option> --}}
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Ticket Status</label>
            <select disabled class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
                <option selected>Open</option>
            </select>
        </div>
    </div>
</div>

<script>
    function loadTechnicianOptions() {
        return $.ajax({
            url: '/api/aftersales/technicians',
            method: 'GET',
            data: { per_page: 100 },
            success: function (response) {
                const technicians = response.data || [];
                const options = technicians.map(t =>
                    `<option value="${t.user?.id}">${escapeHtml(t.user?.name)}</option>`
                ).join('');
                $('#field-technician').html('<option value="">Select Technician</option>' + options).trigger('change.select2');
            },
            error: function (xhr) {
                console.error('Failed to load technicians', xhr.responseJSON?.message || xhr.statusText);
            }
        });
    }
</script>
