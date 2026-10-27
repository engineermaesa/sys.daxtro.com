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

                    {{-- TODO(API): is_daxtro (bool) and compressor (string|null) are sent on POST/PUT products; see the API notes above the script. --}}
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Daxtro Machine<span class="text-red-600">*</span></label>
                        <div class="flex items-center gap-4">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="machine-is-daxtro" value="1" {{ ($product->is_daxtro ?? true) ? 'checked' : '' }}> Yes
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="machine-is-daxtro" value="0" {{ ($product->is_daxtro ?? true) ? '' : 'checked' }}> No
                            </label>
                        </div>
                    </div>
                    <div class="mb-3" id="machine-brand-group" hidden>
                        <label class="block text-xs font-medium mb-1">Brand<span class="text-red-600">*</span></label>
                        <input type="text" id="machine-brand" placeholder="e.g. [brand]"
                            value="{{ $product->brand ?? '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Model (Machine Category)<span class="text-red-600">*</span></label>
                        <select id="machine-product-type-id" required
                            class="select2 w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                            <option value="">Select Model</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Capacity (ton)</label>
                        <input type="number" min="0" step="any" id="machine-capacity" placeholder="e.g. 15"
                            value="{{ $product->capacity ?? '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Compressor</label>
                        <input type="text" id="machine-compressor" maxlength="255" placeholder="Optional (e.g. Bitzer)"
                            value="{{ $product->compressor ?? '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Refrigerant Type</label>
                        <input type="text" id="machine-refrigerant" maxlength="255"
                            value="{{ $product->refrigerant ?? '' }}"
                            placeholder="Optional (e.g. R404A)"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium mb-1">Machine Name<span class="text-red-600">*</span></label>
                        <input type="text" id="machine-name" required placeholder="e.g. TD150 | Bitzer R404A"
                            value="{{ $product->machine_name ?? '' }}"
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs focus:outline-none!">
                        <p id="machine-name-hint" class="text-[#757575] mt-1" style="font-size:11px"></p>
                    </div>
                    <div class="mb-3" id="machine-serial-group">
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

                {{-- WARRANTY & CONTRACT (hidden for non-Daxtro machines) --}}
                <div class="bg-white rounded-lg border border-[#D9D9D9] p-5" id="machine-warranty-card">
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
        'is_daxtro' => $product->is_daxtro ?? true,
    ] : null,
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) !!}
</script>
<script>
    /*
     * TODO(API) — backend contract still pending (PRD v2.2 IM-74..83). Placement of each endpoint call:
     *  1. GET  /api/aftersales/customers/machine-references  -> loadMachineReferences(): categories[].code_prefix (exists).
     *  2. POST /api/aftersales/customers/{customer}/products -> submit handler (create).
     *  3. PUT  /api/aftersales/customers/products/{id}       -> submit handler (edit).
     *     Both must accept & store: is_daxtro (bool, default true), compressor (string|null),
     *     brand (required only when is_daxtro = false), serial_number (required only when is_daxtro = true),
     *     machine_name (required); warranty_start, warranty_end and pm_contract_status ignored when is_daxtro = false.
     *  Until then the extra fields are sent but ignored by the server; records read back default to is_daxtro = true.
     */
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

    const isDaxtro = () => $('input[name="machine-is-daxtro"]:checked').val() !== '0';
    let nameTouched = !!pageConfig.machineId; // saved names are never overwritten until one of the four source fields changes

    // Series: RD (Cold Room) = ton / 3 x 10, every other prefix = ton x 10 (TD 15 ton -> TD150, RD 6 ton -> RD20).
    function machineSeries(prefix, ton) {
        return Math.round(prefix === 'RD' ? ton / 3 * 10 : ton * 10);
    }

    // Machine Name (Daxtro only): "[Prefix][series] | [Compressor] [Refrigerant]", e.g. "TD150 | Bitzer R404A".
    function buildMachineName() {
        const prefix = (categoryPrefixes[$('#machine-product-type-id').val()] || '').replace(/\s+/g, '');
        const ton = parseFloat($('#machine-capacity').val());
        if (!prefix || isNaN(ton)) {
            return '';
        }
        const detail = [$('#machine-compressor').val(), $('#machine-refrigerant').val()]
            .map(v => (v || '').trim()).filter(Boolean).join(' ');
        return `${prefix}${machineSeries(prefix, ton)}${detail ? ' | ' + detail : ''}`;
    }

    // Called when Model, Capacity, Compressor or Refrigerant Type changes.
    function syncMachineName() {
        if (!isDaxtro()) {
            return;
        }
        nameTouched = false;
        const name = buildMachineName();
        if (name) {
            $('#machine-name').val(name);
        }
    }

    // Daxtro Machine = No: Brand required, no Serial Number, no Warranty & Contract, free Machine Name.
    function applyDaxtroMode() {
        const daxtro = isDaxtro();
        $('#machine-brand-group').prop('hidden', daxtro);
        $('#machine-brand').prop('required', !daxtro);
        $('#machine-serial-group').prop('hidden', !daxtro);
        $('#machine-serial-number').prop('required', daxtro);
        $('#machine-warranty-card').prop('hidden', !daxtro);
        $('#machine-name-hint').text(daxtro
            ? 'Auto-filled from Model, Capacity, Compressor, Refrigerant Type. Still editable.'
            : '');
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
        applyDaxtroMode();
        $('input[name="machine-is-daxtro"]').on('change', function () {
            applyDaxtroMode();
            syncMachineName();
        });
        $('#machine-product-type-id').on('change', syncMachineName);
        $('#machine-capacity, #machine-compressor, #machine-refrigerant').on('input', syncMachineName);
        $('#machine-name').on('input', function () { nameTouched = true; });

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
                const daxtro = isDaxtro();
                const payload = {
                    is_daxtro: daxtro ? 1 : 0, // TODO(API): new field
                    machine_name: $('#machine-name').val(),
                    serial_number: daxtro ? $('#machine-serial-number').val() : null,
                    ref_product_type_id: $('#machine-product-type-id').val() || null,
                    brand: daxtro ? null : ($('#machine-brand').val() || null),
                    capacity: $('#machine-capacity').val() || null,
                    compressor: $('#machine-compressor').val() || null, // TODO(API): new field
                    refrigerant: $('#machine-refrigerant').val() || null,
                    note: $('#machine-note').val() || null,
                    warranty_start: daxtro ? ($('#machine-warranty-start').val() || null) : null,
                    warranty_end: daxtro ? ($('#machine-warranty-end').val() || null) : null,
                };

                // Create always sends PM; Edit only sends it once the user picked a (new) value. Non-Daxtro has no PM contract.
                if (!daxtro) {
                    payload.pm_contract_status = 'none';
                } else if (!machineId || pmTouched) {
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
