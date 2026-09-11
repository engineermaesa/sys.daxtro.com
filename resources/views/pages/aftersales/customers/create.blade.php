@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <h1 class="text-[#115640] font-bold text-2xl">{{ $customer ? 'Edit Customer' : 'Add Customer' }}</h1>
        <p class="text-[#757575] text-sm mt-1">
            <a href="{{ route('aftersales.pages.customers.index') }}" class="hover:underline">Customer</a>
            <span class="mx-1">&gt;</span>
            <span class="font-semibold text-[#115640] underline">{{ $customer ? 'Edit Customer' : 'Add Customer' }}</span>
        </p>

        <form id="customer-form" class="mt-4">
            <input type="hidden" id="customer-id" value="{{ $customer->id ?? '' }}">

            <div class="bg-white rounded-lg border border-[#D9D9D9] p-5">
                <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">Customer Information</h6>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium mb-1">Customer Name<span class="text-red-600">*</span></label>
                        <input type="text" id="customer-name" required value="{{ $customer->name ?? '' }}"
                            placeholder="e.g. Global Dynamics Inc."
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium mb-1">Address</label>
                        <textarea id="customer-address" rows="2" placeholder="e.g. Jl. Sudirman No. 123, Jakarta"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">{{ $customer->address ?? '' }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Province</label>
                        <select id="customer-province" class="select2 w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                            <option value="">Select Province</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">City</label>
                        <select id="customer-region" class="select2 w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!" disabled>
                            <option value="">Select Province first</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">PIC Name</label>
                        <input type="text" id="customer-pic-name" value="{{ $customer->pic_name ?? '' }}"
                            placeholder="e.g. John Doe"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" id="customer-email" value="{{ $customer->email ?? '' }}"
                            placeholder="e.g. john.doe@company.com"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Phone</label>
                        <input type="number" id="customer-phone" value="{{ $customer->phone ?? '' }}"
                            placeholder="e.g. 081234567890"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between mt-4">
                <a href="{{ route('aftersales.pages.customers.index') }}"
                    class="bg-white border border-[#D9D9D9] text-[#1E1E1E] rounded-lg px-4 py-2 hover:bg-gray-50 transition-colors inline-flex items-center gap-2">
                    <i class="bi bi-chevron-left"></i> Cancel
                </a>
                <button type="submit"
                    class="bg-[#115640] text-white rounded-lg px-4 py-2 hover:bg-[#0d4633] transition-colors cursor-pointer">
                    Save Customer
                </button>
            </div>
        </form>
    </div>
</section>
@endsection

@section('scripts')
<script>
    const customerId = {{ $customer->id ?? 'null' }};
    const customerRecord = @json($customer ? [
        'ref_province_id' => $customer->ref_province_id,
        'ref_region_id' => $customer->ref_region_id,
    ] : null);

    function initSelect2() {
        if (!window.jQuery || !jQuery.fn?.select2) {
            return;
        }

        $('#customer-province, #customer-region').select2({
            width: '100%',
            dropdownCssClass: 'select2-dropdown-modern'
        });
    }

    function loadProvinces(selectedId) {
        $.ajax({
            url: '/api/aftersales/customers/provinces',
            method: 'GET',
            success: function (response) {
                const options = (response.data || []).map(p =>
                    `<option value="${p.id}" ${selectedId && String(p.id) === String(selectedId) ? 'selected' : ''}>${p.name}</option>`
                ).join('');
                $('#customer-province').html('<option value="">Select Province</option>' + options).trigger('change.select2');

                if (selectedId) {
                    loadRegions(selectedId, customerRecord?.ref_region_id);
                }
            }
        });
    }

    function loadRegions(provinceId, selectedId) {
        if (!provinceId) {
            $('#customer-region').prop('disabled', true).html('<option value="">Select Province first</option>').trigger('change.select2');
            return;
        }

        $('#customer-region').prop('disabled', true).html('<option value="">Loading...</option>').trigger('change.select2');

        $.ajax({
            url: '/api/aftersales/customers/regions',
            method: 'GET',
            data: { province_id: provinceId },
            success: function (response) {
                const rows = response.data || [];
                if (rows.length === 0) {
                    $('#customer-region').html('<option value="">No city data available</option>').trigger('change.select2');
                    return;
                }
                const options = rows.map(r =>
                    `<option value="${r.id}" ${selectedId && String(r.id) === String(selectedId) ? 'selected' : ''}>${r.name}</option>`
                ).join('');
                $('#customer-region').prop('disabled', false).html('<option value="">Select City</option>' + options).trigger('change.select2');
            },
            error: function () {
                $('#customer-region').html('<option value="">Failed to load city data</option>').trigger('change.select2');
            }
        });
    }

    $(function () {
        initSelect2();
        loadProvinces(customerRecord?.ref_province_id);

        $('#customer-province').on('change', function () {
            loadRegions($(this).val(), null);
        });

        $('#customer-form').on('submit', function (e) {
            e.preventDefault();

            Swal.fire({
                title: 'Save this customer?',
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
                    name: $('#customer-name').val(),
                    address: $('#customer-address').val(),
                    ref_province_id: $('#customer-province').val() || null,
                    ref_region_id: $('#customer-region').val() || null,
                    pic_name: $('#customer-pic-name').val(),
                    email: $('#customer-email').val(),
                    phone: $('#customer-phone').val(),
                };

                const isEdit = !!customerId;
                const url = isEdit ? `/api/aftersales/customers/${customerId}` : '/api/aftersales/customers';
                const method = isEdit ? 'PUT' : 'POST';

                $.ajax({
                    url,
                    method,
                    data: payload,
                    success: function () {
                        Swal.fire({
                            title: 'Saved!',
                            text: 'Customer data has been saved successfully.',
                            icon: 'success',
                            confirmButtonColor: '#115640'
                        }).then(function () {
                            window.location.href = '{{ route('aftersales.pages.customers.index') }}';
                        });
                    },
                    error: function (xhr) {
                        Swal.fire({
                            title: 'Failed',
                            text: xhr.responseJSON?.message || 'Failed to save customer',
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
