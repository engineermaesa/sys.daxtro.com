@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <h1 class="text-[#115640] font-bold text-2xl">{{ $customer->name }} — Machine List</h1>
        <p class="text-[#757575] text-sm mt-1">
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
                            @foreach ($productTypes as $type)
                                <option value="{{ $type->id }}" {{ ($product->ref_product_type_id ?? null) == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Machine Name<span class="text-red-600">*</span></label>
                        <input type="text" id="machine-name" required placeholder="e.g. CD 50 – Cube Ice Machine"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Capacity (ton)</label>
                        <input type="number" min="0" step="any" id="machine-capacity" placeholder="e.g. 50"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Machine Code</label>
                        <input type="text" id="machine-code" placeholder="Auto-generated from model" disabled
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs bg-[#F5F5F5] text-[#757575] focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Brand</label>
                        <input type="text" id="machine-brand" placeholder="e.g. Refcomp"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Refrigerant Type</label>
                        <select id="machine-refrigerant" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">
                            <option value="">Select Refrigerant Type</option>
                            @foreach (['R404A', 'R134a', 'R290', 'R507A', 'R717', 'R22'] as $refrigerant)
                                <option value="{{ $refrigerant }}">{{ $refrigerant }}</option>
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
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!"></textarea>
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
                        <input type="text" id="machine-warranty-start" readonly
                            value="{{ $product?->warranty_start ? \Illuminate\Support\Carbon::parse($product?->warranty_start)->format('Y-m-d') : '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none! bg-white">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Finish Date</label>
                        <input type="text" id="machine-warranty-end" placeholder="Start Date + product warranty" readonly
                            value="{{ $product?->warranty_end ? \Illuminate\Support\Carbon::parse($product->warranty_end)->format('Y-m-d') : '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none! bg-white">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">PM Contract</label>
                        <select id="machine-pm-contract-status" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">
                            <option value="none" {{ ($product->pm_contract_status ?? 'none') === 'none' ? 'selected' : '' }}>None</option>
                            <option value="basic" {{ ($product->pm_contract_status ?? '') === 'basic' ? 'selected' : '' }}>Basic</option>
                            <option value="premium" {{ ($product->pm_contract_status ?? '') === 'premium' ? 'selected' : '' }}>Premium</option>
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

    function initDatePickers() {
        if (typeof flatpickr === 'undefined') {
            return;
        }

        const endPicker = flatpickr('#machine-warranty-end', {
            dateFormat: 'Y-m-d',
            allowInput: false,
            onChange: calculateWarrantyPeriod,
        });

        // Warranty defaults to 1 year after the start date; Finish Date can still be changed manually.
        flatpickr('#machine-warranty-start', {
            dateFormat: 'Y-m-d',
            allowInput: false,
            onChange: function (selectedDates) {
                if (selectedDates.length) {
                    const finish = new Date(selectedDates[0]);
                    finish.setFullYear(finish.getFullYear() + 1);
                    endPicker.setDate(finish, true);
                } else {
                    calculateWarrantyPeriod();
                }
            },
        });
    }

    // Service Frequency is read-only and follows the PM Contract: Basic = 6 months, Premium = 3 months.
    const PM_FREQUENCY = { basic: '6 months', premium: '3 months' };

    function syncServiceFrequency() {
        $('#machine-pm-frequency').val(PM_FREQUENCY[$('#machine-pm-contract-status').val()] || '');
    }

    // Machine Code = Model (Machine Category) prefix + Capacity, e.g. Cube + 50 = "CD 50". Read-only, not stored.
    const MACHINE_CODE_PREFIX = {
        'tube': 'TD',
        'cube': 'CD',
        'block': 'BD',
        'flake': 'FD',
        'slurry': 'SD',
        'cold room': 'RD',
        'reverse osmosis': 'RO',
    };

    function syncMachineCode() {
        const typeName = $('#machine-product-type-id').val()
            ? $('#machine-product-type-id option:selected').text().trim().toLowerCase()
            : '';
        const prefix = Object.keys(MACHINE_CODE_PREFIX).find(key => typeName.includes(key));
        const capacity = $('#machine-capacity').val();

        $('#machine-code').val(prefix ? `${MACHINE_CODE_PREFIX[prefix]}${capacity !== '' ? ' ' + capacity : ''}` : '');
    }

    $(function () {
        initSelect2();
        initDatePickers();
        calculateWarrantyPeriod();
        syncServiceFrequency();
        syncMachineCode();

        $('#machine-pm-contract-status').on('change', syncServiceFrequency);
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

                const payload = {
                    product_id: null, // imported/manual machines are not tied to a Product (PRD IM-11)
                    serial_number: $('#machine-serial-number').val(),
                    ref_product_type_id: $('#machine-product-type-id').val() || null,
                    warranty_period: $('#machine-warranty-period').val(),
                    warranty_start: $('#machine-warranty-start').val(),
                    warranty_end: $('#machine-warranty-end').val(),
                    pm_contract_status: $('#machine-pm-contract-status').val(),
                    pm_frequency: $('#machine-pm-frequency').val(),
                };

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
                        Swal.fire({
                            title: 'Failed',
                            text: xhr.responseJSON?.message || 'Failed to save machine',
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
