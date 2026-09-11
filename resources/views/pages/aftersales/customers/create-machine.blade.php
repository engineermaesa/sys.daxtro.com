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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {{-- MACHINE INFORMATION --}}
                <div class="bg-white rounded-lg border border-[#D9D9D9] p-5">
                    <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">Machine Information</h6>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Machine Name<span class="text-red-600">*</span></label>
                        <select id="machine-product-id" required
                            class="select2 w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                            <option value="">Select Machine</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}" {{ ($product->product_id ?? null) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Serial Number<span class="text-red-600">*</span></label>
                        <input type="text" id="machine-serial-number" placeholder="e.g. SN-XXXXXX" required
                            value="{{ $product->serial_number ?? '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Model</label>
                        <select id="machine-product-type-id"
                            class="select2 w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                            <option value="">Select Model</option>
                            @foreach ($productTypes as $type)
                                <option value="{{ $type->id }}" {{ ($product->ref_product_type_id ?? null) == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Service Frequency</label>
                        <select id="machine-pm-frequency" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                            <option value="">Select Frequency</option>
                            <option value="Monthly" {{ ($product->pm_frequency ?? '') === 'Monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="Yearly" {{ ($product->pm_frequency ?? '') === 'Yearly' ? 'selected' : '' }}>Yearly</option>
                        </select>
                    </div>
                </div>

                {{-- WARRANTY & CONTRACT --}}
                <div class="bg-white rounded-lg border border-[#D9D9D9] p-5">
                    <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">Warranty & Contract</h6>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Warranty Period</label>
                        <input type="text" id="machine-warranty-period" placeholder="Auto-calculated from dates below" disabled
                            value="{{ $product->warranty_period ?? '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] text-[#757575] focus:outline-none!">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Start Date</label>
                        <input type="text" id="machine-warranty-start" placeholder="Select date" readonly
                            value="{{ $product?->warranty_start ? \Illuminate\Support\Carbon::parse($product?->warranty_start)->format('Y-m-d') : '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none! bg-white">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Finish Date</label>
                        <input type="text" id="machine-warranty-end" placeholder="Select date" readonly
                            value="{{ $product?->warranty_end ? \Illuminate\Support\Carbon::parse($product->warranty_end)->format('Y-m-d') : '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none! bg-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">PM Contract</label>
                        <select id="machine-pm-contract-status" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                            <option value="none" {{ ($product->pm_contract_status ?? 'none') === 'none' ? 'selected' : '' }}>None</option>
                            <option value="offered" {{ ($product->pm_contract_status ?? '') === 'offered' ? 'selected' : '' }}>Offered</option>
                            <option value="active" {{ ($product->pm_contract_status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="expired" {{ ($product->pm_contract_status ?? '') === 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- ACTIONS --}}
            <div class="flex items-center justify-between mt-4">
                <a href="{{ route('aftersales.pages.customers.machines', $customer->id) }}"
                    class="bg-white border border-[#D9D9D9] text-[#1E1E1E] rounded-lg px-4 py-2 hover:bg-gray-50 transition-colors inline-flex items-center gap-2">
                    <i class="bi bi-chevron-left"></i> Cancel
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
<script>
    const customerId = {{ $customer->id }};
    const machineId = {{ $product->id ?? 'null' }};
    const apiBaseUrl = '/api/aftersales/customers';
    const machinesPageUrl = '{{ route('aftersales.pages.customers.machines', $customer->id) }}';

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

        $('#machine-product-id, #machine-product-type-id').select2({
            width: '100%',
            dropdownCssClass: 'select2-dropdown-modern'
        });
    }

    function initDatePickers() {
        if (typeof flatpickr === 'undefined') {
            return;
        }

        flatpickr('#machine-warranty-start', {
            dateFormat: 'Y-m-d',
            allowInput: false,
            onChange: calculateWarrantyPeriod,
        });

        flatpickr('#machine-warranty-end', {
            dateFormat: 'Y-m-d',
            allowInput: false,
            onChange: calculateWarrantyPeriod,
        });
    }

    $(function () {
        initSelect2();
        initDatePickers();
        calculateWarrantyPeriod();

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
                    product_id: $('#machine-product-id').val(),
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
