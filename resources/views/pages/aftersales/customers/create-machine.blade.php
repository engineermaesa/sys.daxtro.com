@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <a href="{{ route('aftersales.pages.customers.machines', $customer->id) }}" class="sm:hidden inline-flex items-center gap-2 text-[#115640] font-semibold mb-3">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <h1 class="text-[#115640] font-bold text-2xl">{{ $customer->name }} — Machine List</h1>
        <p class="hidden sm:block text-[#757575] text-sm mt-1">
            <a href="{{ route('aftersales.pages.customers.index') }}" class="hover:underline">Customer</a>
            <span class="mx-1">&gt;</span>
            <a href="{{ route('aftersales.pages.customers.machines', $customer->id) }}" class="hover:underline">{{ $customer->name }} — Machine List</a>
            <span class="mx-1">&gt;</span>
            <span class="font-semibold text-[#115640] underline">{{ $product ? 'Edit Machine' : 'Add Machine' }}</span>
        </p>

        <form id="machine-form" class="mt-4">
            <input type="hidden" id="machine-id" value="{{ $product->id ?? '' }}">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
                {{-- MACHINE INFORMATION --}}
                <div class="bg-white rounded-lg border border-[#D9D9D9] p-5">
                    <h6 class="text-[#1E1E1E] font-bold uppercase text-xs tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">Machine Information</h6>

                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Model<span class="text-red-600">*</span></label>
                        <select id="machine-product-type-id" required
                            class="select2 w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                            <option value="">Select Model</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Machine Name<span class="text-red-600">*</span></label>
                        <input type="text" id="machine-name" required placeholder="e.g. CD 50 – Cube Ice Machine"
                            value="{{ $product->machine_name ?? '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Capacity (ton)</label>
                        <input type="number" min="0" step="any" id="machine-capacity" placeholder="e.g. 50"
                            value="{{ $product->capacity ?? '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Machine Code</label>
                        <input type="text" id="machine-code" placeholder="Auto-generated from model" disabled
                            value="{{ $product->machine_code ?? '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs bg-[#F5F5F5] text-[#757575] focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Brand</label>
                        <input type="text" id="machine-brand" placeholder="e.g. Refcomp"
                            value="{{ $product->brand ?? '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Refrigerant Type</label>
                        {{-- Refrigerant is free text in the API; this list only suggests common values and keeps any saved value. --}}
                        <select id="machine-refrigerant" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">
                            <option value="">Select Refrigerant Type</option>
                            @php($refrigerants = ['R404A', 'R134a', 'R290', 'R507A', 'R717', 'R22'])
                            @if (!empty($product->refrigerant) && !in_array($product->refrigerant, $refrigerants, true))
                                <option value="{{ $product->refrigerant }}" selected>{{ $product->refrigerant }}</option>
                            @endif
                            @foreach ($refrigerants as $refrigerant)
                                <option value="{{ $refrigerant }}" {{ ($product->refrigerant ?? '') === $refrigerant ? 'selected' : '' }}>{{ $refrigerant }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Serial Number<span class="text-red-600">*</span></label>
                        <input type="text" id="machine-serial-number" placeholder="e.g. SN-XXXXXX" required
                            value="{{ $product->serial_number ?? '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Note</label>
                        <textarea id="machine-note" rows="3" placeholder="Optional"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">{{ $product->note ?? '' }}</textarea>
                    </div>
                </div>

                {{-- WARRANTY & CONTRACT --}}
                <div class="bg-white rounded-lg border border-[#D9D9D9] p-5">
                    <h6 class="text-[#1E1E1E] font-bold uppercase text-xs tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">Warranty & Contract</h6>

                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Warranty Period</label>
                        <input type="text" id="machine-warranty-period" placeholder="Auto-calculated from dates below" disabled
                            value="{{ $product->warranty_period ?? '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs bg-[#F5F5F5] text-[#757575] focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Start Date</label>
                        <input type="date" id="machine-warranty-start"
                            value="{{ $product?->warranty_start ? \Illuminate\Support\Carbon::parse($product?->warranty_start)->format('Y-m-d') : '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none! bg-white">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Finish Date</label>
                        <input type="date" id="machine-warranty-end" placeholder="Start Date + product warranty"
                            value="{{ $product?->warranty_end ? \Illuminate\Support\Carbon::parse($product->warranty_end)->format('Y-m-d') : '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none! bg-white">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">PM Contract</label>
                        {{-- Options are filled from the machine-references API (pm_contracts). --}}
                        <select id="machine-pm-contract-status" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">
                            <option value="none">None</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Service Frequency</label>
                        <input type="text" id="machine-pm-frequency" placeholder="Set by PM Contract" readonly
                            value="{{ $product->pm_frequency ?? '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs bg-[#F5F5F5] text-[#757575] focus:outline-none!">
                    </div>
                </div>
            </div>

            {{-- ACTIONS --}}
            <div class="flex items-center justify-between mt-4">
                <a href="{{ route('aftersales.pages.customers.machines', $customer->id) }}"
                    class="bg-white border border-[#D9D9D9] text-[#1E1E1E] rounded-lg px-4 py-2 hover:bg-gray-50 transition-colors inline-flex items-center gap-2">
                    <i class="bi bi-chevron-left"></i> Back
                </a>
                <button type="submit"
                    class="bg-[#115640] text-white rounded-lg px-4 py-2 hover:bg-[#0d4633] transition-colors cursor-pointer">
                    Save Machine
                </button>
            </div>
        </form>
    </div>
</section>
@endsection

@section('scripts')
<script type="application/json" id="machine-page-config">
{!! json_encode([
    'customerId' => $customer->id,
    'machineId' => $product->id ?? null,
    'machinesPageUrl' => route('aftersales.pages.customers.machines', $customer->id),
    'warrantyMonths' => $product?->product?->warranty_available && $product->product->warranty_time_month
        ? (int) $product->product->warranty_time_month : 12,
    'record' => $product ? [
        'ref_product_type_id' => $product->ref_product_type_id,
        'pm_contract_status' => $product->pm_contract_status,
    ] : null,
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) !!}
</script>
<script>
    const pageConfig = JSON.parse(document.getElementById('machine-page-config').textContent);
    const customerId = pageConfig.customerId;
    const machineId = pageConfig.machineId;
    const apiBaseUrl = '/api/aftersales/customers';
    const machinesPageUrl = pageConfig.machinesPageUrl;

    function calculateWarrantyPeriod() {
        const start = $('#machine-warranty-start').val();
        const end = $('#machine-warranty-end').val();

        if (!start || !end) {
            $('#machine-warranty-period').val('');
            return;
        }

        const startDate = new Date(start);
        const endDate = new Date(end);

        if (endDate <= startDate) {
            $('#machine-warranty-period').val('');
            return;
        }

        let totalMonths = (endDate.getFullYear() - startDate.getFullYear()) * 12
            + (endDate.getMonth() - startDate.getMonth());
        if (endDate.getDate() < startDate.getDate()) {
            totalMonths -= 1;
        }
        totalMonths = Math.max(totalMonths, 1);

        let label;
        if (totalMonths % 12 === 0) {
            const years = totalMonths / 12;
            label = years === 1 ? '1 Year' : `${years} Years`;
        } else {
            label = totalMonths === 1 ? '1 Month' : `${totalMonths} Months`;
        }

        $('#machine-warranty-period').val(label);
    }

    function initSelect2() {
        if (!window.jQuery || !jQuery.fn?.select2) {
            return;
        }

        $('#machine-product-type-id').select2({
            width: '100%',
            dropdownCssClass: 'select2-dropdown-modern'
        });
    }

    function syncWarrantyEnd() {
        const start = $('#machine-warranty-start').val();
        const endInput = document.getElementById('machine-warranty-end');
        let finish = '';

        if (start) {
            const [year, month, day] = start.split('-').map(Number);
            
            const target = new Date(year, month - 1 + pageConfig.warrantyMonths, 1);
            const lastDay = new Date(target.getFullYear(), target.getMonth() + 1, 0).getDate();
            target.setDate(Math.min(day, lastDay));
            finish = `${target.getFullYear()}-${String(target.getMonth() + 1).padStart(2, '0')}-${String(target.getDate()).padStart(2, '0')}`;
        }

        if (endInput._flatpickr) {
            endInput._flatpickr.setDate(finish, false);
        } else {
            endInput.value = finish;
        }
        calculateWarrantyPeriod();
    }

    function initDatePickers() {
        $('#machine-warranty-start').on('change', syncWarrantyEnd);
        $('#machine-warranty-end').on('change', calculateWarrantyPeriod);

        if (typeof flatpickr === 'undefined') {
            return;
        }

        flatpickr('#machine-warranty-end', {
            dateFormat: 'Y-m-d',
            allowInput: false,
            disableMobile: true,
            onChange: calculateWarrantyPeriod,
        });

        flatpickr('#machine-warranty-start', {
            dateFormat: 'Y-m-d',
            allowInput: false,
            disableMobile: true,
            onChange: syncWarrantyEnd,
        });
    }

    // Filled from GET /machine-references: category prefixes and PM contract frequencies.
    let categoryPrefixes = {};   // { [categoryId]: code_prefix }
    let pmFrequencies = {};      // { none: null, basic: '6 months', premium: '3 months' }
    let pmTouched = false;       // legacy PM values are only replaced after the user picks a new one

    // Service Frequency is read-only and follows the PM Contract (values come from the API).
    function syncServiceFrequency() {
        $('#machine-pm-frequency').val(pmFrequencies[$('#machine-pm-contract-status').val()] || '');
    }

    // Machine Code preview = category code_prefix + Capacity, e.g. Cube + 50 = "CD 50". The saved value comes from the API.
    function syncMachineCode() {
        const prefix = categoryPrefixes[$('#machine-product-type-id').val()];
        const capacity = $('#machine-capacity').val();

        $('#machine-code').val(prefix ? `${prefix}${capacity !== '' ? ' ' + capacity : ''}` : '');
    }

    function loadMachineReferences() {
        return $.ajax({
            url: `${apiBaseUrl}/machine-references`,
            method: 'GET',
            dataType: 'json',
        }).done(function (response) {
            const refs = response.data || {};
            const record = pageConfig.record;

            const $type = $('#machine-product-type-id');
            $type.html('<option value="">Select Model</option>' + (refs.categories || []).map(c => {
                categoryPrefixes[c.id] = c.code_prefix;
                return `<option value="${c.id}">${$('<div>').text(c.name).html()}</option>`;
            }).join(''));
            if (record?.ref_product_type_id) {
                $type.val(String(record.ref_product_type_id));
            }
            $type.trigger('change.select2');

            pmFrequencies = refs.pm_contracts || {};
            const $pm = $('#machine-pm-contract-status');
            const labels = { none: 'None', basic: 'Basic', premium: 'Premium' };
            $pm.html(Object.keys(pmFrequencies).map(key => `<option value="${key}">${labels[key] || key}</option>`).join(''));

            const current = record?.pm_contract_status;
            if (!record) {
                $pm.val('none');
            } else if (current && Object.prototype.hasOwnProperty.call(pmFrequencies, current)) {
                $pm.val(current);
            } else {
                // Legacy PM value outside the list: show as not chosen and keep the old value until a new one is picked.
                $pm.prepend('<option value="" disabled>Select PM Contract</option>').val('');
            }

            syncServiceFrequency();
            syncMachineCode();
        }).fail(function (xhr) {
            Swal.fire({
                title: 'Failed',
                text: xhr.responseJSON?.message || 'Failed to load machine options',
                icon: 'error',
                confirmButtonColor: '#115640'
            });
        });
    }

    $(function () {
        initSelect2();
        initDatePickers();
        if ($('#machine-warranty-start').val() && !$('#machine-warranty-end').val()) {
            syncWarrantyEnd();
        }
        calculateWarrantyPeriod();
        loadMachineReferences();

        $('#machine-pm-contract-status').on('change', function () {
            pmTouched = true;
            syncServiceFrequency();
        });
        $('#machine-product-type-id').on('change', syncMachineCode);
        $('#machine-capacity').on('input', syncMachineCode);

        $('#machine-form').on('submit', function (e) {
            e.preventDefault();

            Swal.fire({
                title: machineId ? 'Save changes to this machine?' : 'Add this machine?',
                text: 'Please make sure the data you entered is correct.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#115640',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, save it!',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                // machine_code, warranty_period and pm_frequency are read-only: never sent (API contract 5.2).
                const payload = {
                    machine_name: $('#machine-name').val(),
                    serial_number: $('#machine-serial-number').val(),
                    ref_product_type_id: $('#machine-product-type-id').val() || null,
                    brand: $('#machine-brand').val() || null,
                    capacity: $('#machine-capacity').val() || null,
                    refrigerant: $('#machine-refrigerant').val() || null,
                    note: $('#machine-note').val() || null,
                    warranty_start: $('#machine-warranty-start').val() || null,
                    warranty_end: $('#machine-warranty-end').val() || null,
                };

                // Create always sends PM; Edit only sends it once the user picked a (new) value.
                if (!machineId || pmTouched) {
                    payload.pm_contract_status = $('#machine-pm-contract-status').val() || 'none';
                }

                const isEdit = !!machineId;
                const url = isEdit ? `${apiBaseUrl}/products/${machineId}` : `${apiBaseUrl}/${customerId}/products`;
                const method = isEdit ? 'PUT' : 'POST';

                $.ajax({
                    url,
                    method,
                    data: payload,
                    success: function () {
                        Swal.fire({
                            title: 'Saved!',
                            text: 'Machine data has been saved successfully.',
                            icon: 'success',
                            confirmButtonColor: '#115640'
                        }).then(function () {
                            window.location.href = machinesPageUrl;
                        });
                    },
                    error: function (xhr) {
                        // 422: list each field error (e.g. serial_number, warranty_end).
                        const fieldErrors = Object.values(xhr.responseJSON?.errors || {}).flat();
                        Swal.fire({
                            title: 'Failed',
                            text: fieldErrors.length ? fieldErrors.join('\n') : (xhr.responseJSON?.message || 'Failed to save machine'),
                            icon: 'error',
                            confirmButtonColor: '#115640'
                        });
                    }
                });
            });
        });
    });
</script>
@endsection
