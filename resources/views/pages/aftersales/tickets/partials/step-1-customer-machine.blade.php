{{-- STEP 1: DATA CUSTOMER & MESIN --}}
<div class="wizard-step" data-step="1">
{{-- Gate (IM-89): Phone and Email of the Customer must be complete before a NEW ticket can continue --}}
<div id="customer-gate-alert" class="hidden mb-4 rounded-lg border border-[#F0B8B3] bg-[#FBEAEA] text-[#900B09] px-4 py-3" role="alert">
    <i class="bi bi-exclamation-triangle"></i>
    <span id="customer-gate-message"></span>
    <a id="customer-gate-link" href="#" target="_blank" class="font-semibold underline ml-1">Edit Customer</a>
</div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-lg border border-[#D9D9D9] p-5">
        <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">1. Customer Data</h6>

        <div class="mb-3">
            <label class="block text-sm font-medium mb-1">Customer Name<span class="text-red-600">*</span></label>
            <select id="field-customer" class="select2 w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!" required>
                <option value="">Choose customer</option>
                {{-- TODO: fetch GET /api/aftersales/customers lalu isi <option value="{id}" data-*="...">{name}</option> --}}
            </select>
        </div>
        <div class="mb-3">
            <label class="block text-sm font-medium mb-1">PIC Contact<span class="text-red-600">*</span></label>
            <input type="text" id="field-pic-name" readonly placeholder="PIC Name"
                class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
        </div>
        <div class="mb-3">
            <label class="block text-sm font-medium mb-1">Email<span class="text-red-600">*</span></label>
            <input type="text" id="field-pic-email" readonly placeholder="pic@company.com"
                class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
        </div>
        <div class="mb-3">
            <label class="block text-sm font-medium mb-1">Phone Numbers / WA<span class="text-red-600">*</span></label>
            <input type="text" id="field-phone" readonly placeholder="08xxxxxxxxxx"
                class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Location / Area<span class="text-red-600">*</span></label>
            <input type="text" id="field-location" readonly placeholder="City, Province"
                class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
        </div>
    </div>

    <div class="bg-white rounded-lg border border-[#D9D9D9] p-5">
        <h6 class="text-[#1E1E1E] font-bold uppercase text-sm tracking-wide border-b border-[#D9D9D9] pb-3 mb-4">2. Machine Data & Warranty</h6>

        <div class="mb-3">
            <label class="block text-sm font-medium mb-1">Machine Name<span class="text-red-600">*</span></label>
            <select id="field-machine" class="select2 w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!" required disabled>
                <option value="">Please choose the customer first.</option>
                {{-- TODO: after customer is selected, fetch GET /api/aftersales/customers/{id} then populate <option value="{product.id}">{machine_name}</option> --}}
            </select>
        </div>
        <div class="mb-3">
            <label class="block text-sm font-medium mb-1">Model / Type</label>
            <input type="text" id="field-machine-model" readonly placeholder="Model or type"
                class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
        </div>
        <div class="mb-3">
            <label class="block text-sm font-medium mb-1">Serial Number<span class="text-red-600">*</span></label>
            <input type="text" id="field-serial-number" readonly placeholder="SN-XXXXXX"
                class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
        </div>
        <div class="mb-3">
            <label class="block text-sm font-medium mb-1">Installation Year</label>
            <input type="text" id="field-install-year" readonly placeholder="e.g. 2022"
                class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Warranty Status</label>
            <input type="text" id="field-warranty-status" readonly placeholder="Active / Expired"
                class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 bg-[#F5F5F5] focus:outline-none!">
        </div>
    </div>
</div>
</div>

<script>
    let customersCache = [];
    let currentProducts = [];

    function selectedCustomer() {
        const customerId = $('#field-customer').val();
        return customerId ? customersCache.find(c => String(c.id) === String(customerId)) : null;
    }

    function customerGateBlocked() {
        if (typeof EDIT_TICKET_ID !== 'undefined' && EDIT_TICKET_ID) return false;
        return selectedCustomer()?.ticket_creation?.can_create === false;
    }

    function setTicketCreation(customerId, ticketCreation) {
        const customer = customersCache.find(c => String(c.id) === String(customerId));
        if (customer && ticketCreation) customer.ticket_creation = ticketCreation;
        if (String(customerId) === String($('#field-customer').val())) applyCustomerGate();
    }

    function applyCustomerGate() {
        const blocked = customerGateBlocked();
        const gate = selectedCustomer()?.ticket_creation || {};
        $('#customer-gate-alert').toggleClass('hidden', !blocked);
        $('#customer-gate-message').text(gate.message || '');
        $('#customer-gate-link').attr('href', gate.edit_customer_url || '#');
        $('#btn-wizard-next').prop('disabled', blocked && typeof currentStep !== 'undefined' && currentStep === 1)
            .toggleClass('opacity-50 cursor-not-allowed', blocked && currentStep === 1);
    }

    function refreshCustomerGate() {
        const customerId = $('#field-customer').val();
        if (!customerGateBlocked()) return;
        $.getJSON(`/api/aftersales/customers/${customerId}`).done(function (response) {
            const c = response.data || {};
            $('#field-pic-email').val(c.email || '');
            $('#field-phone').val(c.phone || '');
            setTicketCreation(customerId, c.ticket_creation);
        });
    }

    function resetCustomerFields() {
        $('#field-pic-name').val('');
        $('#field-pic-email').val('');
        $('#field-phone').val('');
        $('#field-location').val('');
    }

    function resetMachineFields() {
        $('#field-machine-model').val('');
        $('#field-serial-number').val('');
        $('#field-install-year').val('');
        $('#field-warranty-status').val('');
    }

    function warrantyStatusLabel(product) {
        if (product.warranty_active === null || product.warranty_active === undefined) return '-';
        return product.warranty_active ? 'Active' : 'Expired';
    }

    function loadCustomerOptions() {
        return $.ajax({
            url: '/api/aftersales/customers',
            method: 'GET',
            data: { per_page: 100 },
            success: function (response) {
                customersCache = (response.data || []).slice().sort((a, b) =>
                    String(a.name || '').trim().localeCompare(String(b.name || '').trim(), 'id', { sensitivity: 'base' })
                );

                const options = customersCache.map(customer =>
                    `<option value="${customer.id}">${escapeHtml(customer.name)}</option>`
                ).join('');

                $('#field-customer').html('<option value="">Select customer</option>' + options).trigger('change.select2');
            },
            error: function (xhr) {
                console.error('Failed to load customers', xhr.responseJSON?.message || xhr.statusText);
            }
        });
    }

    function loadMachineOptions(customerId, onLoaded) {
        $('#field-machine').prop('disabled', true).html('<option value="">Loading...</option>').trigger('change.select2');
        resetMachineFields();

        $.ajax({
            url: `/api/aftersales/customers/${customerId}`,
            method: 'GET',
            success: function (response) {
                currentProducts = response.data?.products || [];
                setTicketCreation(customerId, response.data?.ticket_creation);

                if (currentProducts.length === 0) {
                    $('#field-machine').html('<option value="">No machines registered yet</option>').trigger('change.select2');
                    return;
                }

                const options = currentProducts.map(product => {
                    const name = product.machine_name || product.product?.name || `Machine #${product.id}`;
                    const label = product.serial_number ? `${name} — ${product.serial_number}` : name;
                    return `<option value="${product.id}">${escapeHtml(label)}</option>`;
                }).join('');

                $('#field-machine').prop('disabled', false).html('<option value="">Select machine</option>' + options).trigger('change.select2');
            },
            error: function (xhr) {
                console.error('Failed to load customer machines', xhr.responseJSON?.message || xhr.statusText);
                $('#field-machine').html('<option value="">Failed to load machine data</option>').trigger('change.select2');
            }
        }).always(function () {
            if (typeof onLoaded === 'function') onLoaded();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        $('#field-customer').on('change', function () {
            const selected = $(this).val();
            resetCustomerFields();
            applyCustomerGate();

            if (!selected) {
                currentProducts = [];
                $('#field-machine').prop('disabled', true).html('<option value="">Select customer first</option>').trigger('change.select2');
                return;
            }

            const customer = customersCache.find(c => String(c.id) === String(selected));
            if (customer) {
                $('#field-pic-name').val(customer.pic_name || '');
                $('#field-pic-email').val(customer.email || '');
                $('#field-phone').val(customer.phone || '');
                $('#field-location').val([customer.region?.name, customer.province?.name].filter(Boolean).join(', '));
            }
            applyCustomerGate();

            loadMachineOptions(selected, function () {
                if (EDIT_TICKET && EDIT_TICKET.customer_product_id && String(selected) === String(EDIT_TICKET.customer_id)) {
                    $('#field-machine').val(String(EDIT_TICKET.customer_product_id)).trigger('change');
                }
            });
        });

        $('#field-machine').on('change', function () {
            const selected = $(this).val();
            resetMachineFields();

            const product = currentProducts.find(p => String(p.id) === String(selected));
            if (product) {
                $('#field-machine-model').val(product.product_type?.name || '');
                $('#field-serial-number').val(product.serial_number || '');
                $('#field-install-year').val(product.warranty_start ? new Date(product.warranty_start).getFullYear() : '');
                $('#field-warranty-status').val(warrantyStatusLabel(product));
            }
        });

        window.addEventListener('focus', refreshCustomerGate);
    });
</script>
