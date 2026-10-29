@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <div class="flex items-center justify-between mb-1">
            <div>
                <h1 class="text-[#115640] font-bold text-lg uppercase">Tickets Input Form</h1>
                <p class="text-[#757575] text-sm mt-1">
                    <a href="{{ route('aftersales.pages.tickets.index') }}" class="hover:underline">All Ticket</a>
                    <span class="mx-1">&gt;</span>
                    <span class="font-semibold text-[#1E1E1E]">{{ isset($ticket) ? 'Edit Ticket' : 'New Ticket' }}</span>
                </p>
            </div>
        </div>

        {{-- STEPPER --}}
        <div class="bg-white rounded-lg border border-[#D9D9D9] p-4 mt-3">
            <div id="ticket-stepper" class="flex items-center">
                @php
                    $steps = [
                        1 => 'Customer & Machine Data',
                        2 => 'Complaint Details',
                        3 => 'Assignment',
                        4 => 'Sparepart',
                        5 => 'Final Cost',
                    ];
                @endphp
                @foreach ($steps as $number => $label)
                    <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                        <div class="flex items-center gap-2 step-indicator" data-step-indicator="{{ $number }}">
                            <span class="step-circle shrink-0 w-7 h-7 min-w-[1.75rem] leading-none aspect-square rounded-full inline-flex items-center justify-center text-xs font-semibold border-2 border-[#D9D9D9] text-[#757575]">{{ $number }}</span>
                            <span class="step-label text-sm font-medium text-[#757575] whitespace-nowrap">{{ $label }}</span>
                        </div>
                        @if (!$loop->last)
                            <div class="step-connector flex-1 h-[2px] bg-[#D9D9D9] mx-3"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- FORM --}}
        <form id="ticket-wizard-form" class="mt-4">
            @include('pages.aftersales.tickets.partials.step-1-customer-machine')

            @include('pages.aftersales.tickets.partials.step-2-complaint')

            @include('pages.aftersales.tickets.partials.step-3-assignment')

            @include('pages.aftersales.tickets.partials.step-4-sparepart')

            @include('pages.aftersales.tickets.partials.step-5-cost')

            {{-- NAVIGATION --}}
            <div class="flex items-center justify-between mt-4">
                <button type="button" id="btn-wizard-back"
                    class="border border-[#D9D9D9] text-[#1E1E1E] rounded-lg px-4 py-2 hover:bg-gray-50 cursor-pointer">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
                <button type="button" id="btn-wizard-next"
                    class="ml-auto bg-[#115640] text-white rounded-lg px-4 py-2 hover:bg-[#0d4633] transition-colors cursor-pointer">
                    Next <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
</section>
@endsection

@section('scripts')
{{-- data Blade dibawa lewat atribut HTML supaya kode JS di bawah tetap JS murni --}}
<div id="ticketBoot" class="hidden"
    data-ticket-id="{{ $ticket->id ?? '' }}"
    data-ticket="{{ json_encode(isset($ticket) ? $ticket->toArray() : null) }}"
    data-index-url="{{ route('aftersales.pages.tickets.index') }}"></div>
<script>
    const TOTAL_STEPS = 5;
    let currentStep = 1;

    const ticketBoot = document.getElementById('ticketBoot');
    const EDIT_TICKET_ID = ticketBoot.dataset.ticketId ? Number(ticketBoot.dataset.ticketId) : null;
    const EDIT_TICKET = JSON.parse(ticketBoot.dataset.ticket || 'null');
    const TICKETS_INDEX_URL = ticketBoot.dataset.indexUrl;
    let originalTechnicianId = EDIT_TICKET?.assigned_technician_id ? String(EDIT_TICKET.assigned_technician_id) : null;
    let originalVisitDate = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function updateStepper() {
        $('.step-indicator').each(function () {
            const step = Number($(this).data('step-indicator'));
            const $circle = $(this).find('.step-circle');
            const $label = $(this).find('.step-label');

            $circle.removeClass('bg-[#115640] border-[#115640] text-white border-[#D9D9D9] text-[#757575]');
            $label.removeClass('text-[#115640] text-[#757575]');

            if (step < currentStep) {
                $circle.addClass('bg-[#115640] border-[#115640] text-white').html('<i class="bi bi-check-lg"></i>');
                $label.addClass('text-[#115640]');
            } else if (step === currentStep) {
                $circle.addClass('bg-[#115640] border-[#115640] text-white').text(step);
                $label.addClass('text-[#115640]');
            } else {
                $circle.addClass('border-[#D9D9D9] text-[#757575]').text(step);
                $label.addClass('text-[#757575]');
            }
        });

        $('.wizard-step').addClass('hidden');
        $(`.wizard-step[data-step="${currentStep}"]`).removeClass('hidden');
        if (currentStep === 5 && typeof recalculateCostTotal === 'function') recalculateCostTotal();

        $('#btn-wizard-back').toggle(currentStep > 1);
        const saveLabel = EDIT_TICKET_ID ? 'Update Ticket' : 'Save Ticket';
        $('#btn-wizard-next').html(currentStep === TOTAL_STEPS
            ? `${saveLabel} <i class="bi bi-check-lg"></i>`
            : 'Next <i class="bi bi-arrow-right"></i>');

        // IM-89: Next stays disabled on step 1 while the selected customer has no Phone or Email
        if (typeof applyCustomerGate === 'function') applyCustomerGate();
    }

    const REQUIRED_FIELDS_BY_STEP = {
        1: ['#field-customer', '#field-pic-name', '#field-phone', '#field-location', '#field-machine', '#field-serial-number'],
        2: ['#field-category', '#field-description'],
    };

    function markFieldInvalid(selector) {
        $(selector).addClass('border-red-500');
        if (selector === '#field-customer' || selector === '#field-machine' || selector === '#field-technician') {
            $(selector).next('.select2-container').find('.select2-selection').addClass('border-red-500');
        }
    }

    function clearFieldInvalid(selector) {
        $(selector).removeClass('border-red-500');
        if (selector === '#field-customer' || selector === '#field-machine' || selector === '#field-technician') {
            $(selector).next('.select2-container').find('.select2-selection').removeClass('border-red-500');
        }
    }

    function validateCurrentStep() {
        const selectors = (REQUIRED_FIELDS_BY_STEP[currentStep] || []).filter(selector =>
            selector !== '#field-serial-number' || serialNumberRequired()
        );
        const missing = selectors.filter(selector => !$(selector).val());

        selectors.forEach(clearFieldInvalid);
        missing.forEach(markFieldInvalid);

        if (missing.length > 0) {
            Swal.fire({
                title: 'Data incomplete',
                text: 'Please fill in all required fields before proceeding.',
                icon: 'warning',
                confirmButtonColor: '#115640'
            });
            return false;
        }

        // Every sparepart row requires a unit before continuing, including newly added rows.
        if (currentStep === 4 && (!checkSparepartUnits(true) || !checkSparepartStock())) {
            return false;
        }

        if (currentStep === 5) {
            let totalCost = 0;
            $('#cost-rows tr[data-cost-category] .cost-amount').each(function () {
                totalCost += Number($(this).val() || 0);
            });

            if (totalCost <= 0) {
                Swal.fire({
                    title: 'Data incomplete',
                    text: 'Please fill in at least one cost amount greater than 0.',
                    icon: 'warning',
                    confirmButtonColor: '#115640'
                });
                return false;
            }
        }

        return true;
    }

    function collectPayload() {
        const parts = [];
        $('#sparepart-rows tr').each(function () {
            const itemId = $(this).find('.sparepart-item').val();
            const qty = $(this).find('.sparepart-qty').val();
            const unit = $(this).find('.sparepart-unit').val();
            if (itemId && Number(qty) > 0 && unit) {
                parts.push({ ref_sparepart_id: itemId, qty: Number(qty), unit: unit });
            }
        });

        const costs = [];
        $('#cost-rows tr[data-cost-category]').each(function () {
            const amount = Number($(this).find('.cost-amount').val() || 0);
            if (amount > 0) {
                costs.push({
                    category: $(this).data('cost-category'),
                    amount: amount,
                    currency: $(this).find('.cost-currency').val(),
                    exchange_rate: $(this).find('.cost-currency').val() === 'idr' ? null : costRateFor($(this).find('.cost-currency').val()),
                    remarks: $(this).find('.cost-remarks').val() || null,
                });
            }
        });

        const slaDueAt = $('#field-sla-due-date').val();
        let slaValue = 1;
        if (slaDueAt) {
            const diffHours = Math.ceil((new Date(slaDueAt) - new Date()) / (1000 * 60 * 60));
            slaValue = Math.max(diffHours, 1);
        }

        return {
            customer_id: $('#field-customer').val(),
            customer_product_id: $('#field-machine').val(),
            category: $('#field-category').val(),
            priority: $('#field-priority').val(),
            description: $('#field-description').val(),
            sla_value: slaValue,
            sla_unit: 'hour',
            sla_due_at: slaDueAt || null,
            cost_display_currency: $('#cost-total-currency').val() || 'idr',
            supervisor_id: $('#field-supervisor').val() || null,
            assigned_technician_id: $('#field-technician').val() || null,
            scheduled_at: $('#field-visit-date').val() || null,
            parts: parts,
            costs: costs,
        };
    }

    function runSequential(tasks, onDone, onError) {
        if (tasks.length === 0) {
            onDone();
            return;
        }

        const [first, ...rest] = tasks;
        first(
            () => runSequential(rest, onDone, onError),
            onError
        );
    }

    function formatApiErrors(xhr) {
        const errors = xhr.responseJSON?.errors;
        if (errors) {
            return Object.values(errors).flat().join('\n');
        }
        return xhr.responseJSON?.message || 'An error occurred, please try again.';
    }

    function submitTicket() {
        const payload = collectPayload();
        const isEdit = !!EDIT_TICKET_ID;

        if (!payload.customer_id || !payload.customer_product_id) {
            Swal.fire({
                title: 'Data incomplete',
                text: 'Please select a customer and machine in Step 1 first.',
                icon: 'warning',
                confirmButtonColor: '#115640'
            });
            currentStep = 1;
            updateStepper();
            return;
        }

        if (customerGateBlocked()) {
            Swal.fire({
                title: 'Customer data is incomplete',
                text: selectedCustomer()?.ticket_creation?.message || 'Phone and Email are required. Please complete the customer data first.',
                icon: 'warning',
                confirmButtonColor: '#115640'
            });
            currentStep = 1;
            updateStepper();
            return;
        }

        if (payload.scheduled_at && payload.sla_due_at && new Date(payload.sla_due_at) < new Date(payload.scheduled_at)) {
            Swal.fire({
                title: 'Invalid SLA Target',
                text: 'SLA Target cannot be earlier than the Visit Schedule.',
                icon: 'warning',
                confirmButtonColor: '#115640'
            });
            currentStep = 3;
            updateStepper();
            return;
        }

        Swal.fire({
            title: isEdit ? 'Update this ticket?' : 'Save this ticket?',
            text: 'Make sure all the data entered is correct.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#115640',
            cancelButtonColor: '#d33',
            confirmButtonText: isEdit ? 'Yes, update it!' : 'Yes, save it!',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            const ajaxData = {
                customer_id: payload.customer_id,
                customer_product_id: payload.customer_product_id,
                category: payload.category,
                priority: payload.priority,
                description: payload.description,
                sla_value: payload.sla_value,
                sla_unit: payload.sla_unit,
                sla_due_at: payload.sla_due_at,
                cost_display_currency: payload.cost_display_currency,
                supervisor_id: payload.supervisor_id,
                parts: payload.parts,
                costs: payload.costs,
            };

            if (isEdit) {
                ajaxData.assigned_technician_id = payload.assigned_technician_id;
            }

            $.ajax({
                url: isEdit ? `/api/aftersales/tickets/${EDIT_TICKET_ID}` : '/api/aftersales/tickets',
                method: isEdit ? 'PUT' : 'POST',
                // JSON: jQuery tidak mengirim array kosong, padahal parts: [] berarti semua part dihapus
                contentType: 'application/json',
                data: JSON.stringify(ajaxData),
                success: function (response) {
                    const ticketId = isEdit ? EDIT_TICKET_ID : response.data?.data?.id;
                    const ticketCode = response.data?.data?.ticket_code ?? EDIT_TICKET?.ticket_code;
                    const followUpTasks = [];

                    if (payload.assigned_technician_id && (!isEdit || payload.assigned_technician_id !== originalTechnicianId)) {
                        followUpTasks.push((next, fail) => {
                            $.ajax({
                                url: `/api/aftersales/tickets/${ticketId}/assign`,
                                method: 'POST',
                                data: { assigned_technician_id: payload.assigned_technician_id },
                            }).done(next).fail(fail);
                        });
                    }

                    if (payload.scheduled_at && (!isEdit || (!originalVisitDate && payload.scheduled_at))) {
                        followUpTasks.push((next, fail) => {
                            $.ajax({
                                url: `/api/aftersales/tickets/${ticketId}/visits`,
                                method: 'POST',
                                data: { scheduled_at: payload.scheduled_at },
                            }).done(next).fail(fail);
                        });
                    }

                    runSequential(
                        followUpTasks,
                        function () {
                            Swal.fire({
                                title: isEdit ? 'Updated!' : 'Saved!',
                                text: `Ticket ${ticketCode ?? ''} has been ${isEdit ? 'updated' : 'created'} successfully.`,
                                icon: 'success',
                                confirmButtonColor: '#115640'
                            }).then(function () {
                                window.location.href = TICKETS_INDEX_URL;
                            });
                        },
                        function (xhr) {
                            Swal.fire({
                                title: isEdit ? 'Ticket updated, but there was an additional issue' : 'Ticket created, but there was an additional issue',
                                text: formatApiErrors(xhr),
                                icon: 'warning',
                                confirmButtonColor: '#115640'
                            }).then(function () {
                                window.location.href = TICKETS_INDEX_URL;
                            });
                        }
                    );
                },
                error: function (xhr) {
                    // stok kurang: 422 { message, errors: { parts: ["Not enough stock: ..."] } }, tiket tidak tersimpan
                    const stockError = xhr.status === 422 ? xhr.responseJSON?.errors?.parts?.[0] : null;
                    if (stockError) {
                        currentStep = 4;
                        updateStepper();
                        showSparepartStockAlert('Ticket was not saved. ' + stockError);
                        return;
                    }

                    if (xhr.responseJSON?.code === 'customer_contact_incomplete') {
                        setTicketCreation(xhr.responseJSON.customer_id, xhr.responseJSON.ticket_creation);
                        currentStep = 1;
                        updateStepper();
                    }

                    Swal.fire({
                        title: isEdit ? 'Failed to update ticket' : 'Failed to save ticket',
                        text: formatApiErrors(xhr),
                        icon: 'error',
                        confirmButtonColor: '#115640'
                    });
                }
            });
        });
    }

    function initSelect2() {
        if (!window.jQuery || !jQuery.fn?.select2) {
            return;
        }

        $('#field-customer, #field-machine, #field-technician').select2({
            width: '100%',
            dropdownCssClass: 'select2-dropdown-modern',
        });
    }

    function initTooltips() {
        if (window.jQuery && jQuery.fn?.tooltip) {
            $('[data-toggle="tooltip"]').tooltip();
        }
    }

    function loadSupervisorOptions() {
        return $.ajax({
            url: '/api/aftersales/tickets/supervisors',
            method: 'GET',
            headers: { Accept: 'application/json' },
            success: function (response) {
                const options = (response.data || []).map(function (u) {
                    return '<option value="' + u.id + '">' + escapeHtml(u.name) + '</option>';
                }).join('');
                $('#field-supervisor').html('<option value="">Select Supervisor</option>' + options);
            },
            error: function (xhr) {
                console.error('Failed to load supervisors', xhr.responseJSON?.message || xhr.statusText);
            }
        });
    }

    $(function () {
        initSelect2();
        initTooltips();
        updateStepper();

        const customerPromise = loadCustomerOptions();
        const technicianPromise = loadTechnicianOptions();
        const sparepartPromise = loadSparepartOptions();
        const supervisorPromise = loadSupervisorOptions();

        if (EDIT_TICKET_ID) {
            $('#field-ticket-code').val(EDIT_TICKET.ticket_code || '-');

            $.when(customerPromise, technicianPromise, sparepartPromise, supervisorPromise).done(function () {
                $('#field-customer').val(String(EDIT_TICKET.customer_id)).trigger('change');
                $('#field-category').val(EDIT_TICKET.category || '');
                $('#field-priority').val(EDIT_TICKET.priority || 'medium');
                $('#field-description').val(EDIT_TICKET.description || '');

                if (EDIT_TICKET.assigned_technician_id) {
                    $('#field-technician').val(String(EDIT_TICKET.assigned_technician_id)).trigger('change.select2');
                }
                if (EDIT_TICKET.supervisor_id) {
                    // Older tickets may have a supervisor who is not in the new dropdown (role supervisor_after_sales):
                    // keep showing the existing one instead of clearing it. Keeping the same ID is allowed by the API.
                    if (!$('#field-supervisor option[value="' + EDIT_TICKET.supervisor_id + '"]').length) {
                        $('#field-supervisor').append('<option value="' + EDIT_TICKET.supervisor_id + '">' +
                            escapeHtml(EDIT_TICKET.supervisor?.name || ('User #' + EDIT_TICKET.supervisor_id)) + '</option>');
                    }
                    $('#field-supervisor').val(String(EDIT_TICKET.supervisor_id));
                }
                if (EDIT_TICKET.sla_due_at) {
                    $('#field-sla-due-date').val(String(EDIT_TICKET.sla_due_at).slice(0, 16).replace(' ', 'T'));
                    slaTouched = true;
                }

                const latestVisit = (EDIT_TICKET.visits || []).slice().sort((a, b) => b.id - a.id)[0];
                if (latestVisit) {
                    originalVisitDate = String(latestVisit.scheduled_at).slice(0, 16).replace(' ', 'T');
                    $('#field-visit-date').val(originalVisitDate);
                }

                prefillSparepartAndCostRows();
            });
        } else {
            addSparepartRow();

            $.ajax({
                url: '/api/aftersales/tickets/next-code',
                method: 'GET',
                success: function (response) {
                    $('#field-ticket-code').val(response.data?.ticket_code || 'Will be generated automatically upon saving');
                },
                error: function () {
                    $('#field-ticket-code').val('Will be generated automatically upon saving');
                }
            });
        }

        // SLA Target default: 8 jam setelah Visit Schedule, selama belum diubah manual
        var slaTouched = false;
        function pad(n) { return String(n).padStart(2, '0'); }
        $(document).on('input change', '#field-sla-due-date', function () { slaTouched = true; });
        $(document).on('input change', '#field-visit-date', function () {
            const v = $(this).val();
            if (slaTouched || !v) return;
            const d = new Date(v);
            if (isNaN(d)) return;
            d.setHours(d.getHours() + 8);
            $('#field-sla-due-date').val(d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes()));
        });

        $(document).on('input change', '#field-customer, #field-pic-name, #field-phone, #field-location, #field-machine, #field-serial-number, #field-category, #field-description, #field-technician, #field-visit-date', function () {
            if ($(this).val()) {
                clearFieldInvalid('#' + $(this).attr('id'));
            }
        });

        $('#btn-wizard-back').on('click', function () {
            if (currentStep > 1) {
                currentStep -= 1;
                updateStepper();
            }
        });

        $('#btn-wizard-next').on('click', function () {
            if (!validateCurrentStep()) return;

            if (currentStep < TOTAL_STEPS) {
                currentStep += 1;
                updateStepper();
            } else {
                submitTicket();
            }
        });
    });
</script>
@endsection
