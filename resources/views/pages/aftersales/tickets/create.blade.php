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
<script>
    const TOTAL_STEPS = 5;
    let currentStep = 1;

    const EDIT_TICKET_ID = @json($ticket->id ?? null);
    const EDIT_TICKET = @json(isset($ticket) ? $ticket->toArray() : null);
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

        $('#btn-wizard-back').toggle(currentStep > 1);
        const saveLabel = EDIT_TICKET_ID ? 'Update Ticket' : 'Save Ticket';
        $('#btn-wizard-next').html(currentStep === TOTAL_STEPS
            ? `${saveLabel} <i class="bi bi-check-lg"></i>`
            : 'Next <i class="bi bi-arrow-right"></i>');
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
        const selectors = REQUIRED_FIELDS_BY_STEP[currentStep] || [];
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
            if (itemId && qty && unit) {
                parts.push({ ref_sparepart_id: itemId, qty: qty, unit: unit });
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
                    remarks: $(this).find('.cost-remarks').val() || null,
                });
            }
        });

        const slaDueDate = $('#field-sla-due-date').val();
        let slaValue = 1;
        if (slaDueDate) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const due = new Date(slaDueDate + 'T00:00:00');
            const diffDays = Math.round((due - today) / (1000 * 60 * 60 * 24));
            slaValue = Math.max(diffDays, 1);
        }

        return {
            customer_id: $('#field-customer').val(),
            customer_product_id: $('#field-machine').val(),
            category: $('#field-category').val(),
            priority: $('#field-priority').val(),
            description: $('#field-description').val(),
            sla_value: slaValue,
            sla_unit: 'day',
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
                data: ajaxData,
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

                    if (payload.scheduled_at && (!isEdit || payload.scheduled_at !== originalVisitDate)) {
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
                                window.location.href = '{{ route('aftersales.pages.tickets.index') }}';
                            });
                        },
                        function (xhr) {
                            Swal.fire({
                                title: isEdit ? 'Ticket updated, but there was an additional issue' : 'Ticket created, but there was an additional issue',
                                text: formatApiErrors(xhr),
                                icon: 'warning',
                                confirmButtonColor: '#115640'
                            }).then(function () {
                                window.location.href = '{{ route('aftersales.pages.tickets.index') }}';
                            });
                        }
                    );
                },
                error: function (xhr) {
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

    $(function () {
        // TODO: fetch GET /api/aftersales/tickets/supervisors to populate #field-supervisor
        // (endpoint not yet available in backend)

        initSelect2();
        initTooltips();
        updateStepper();

        const customerPromise = loadCustomerOptions();
        const technicianPromise = loadTechnicianOptions();
        const sparepartPromise = loadSparepartOptions();

        if (EDIT_TICKET_ID) {
            $('#field-ticket-code').val(EDIT_TICKET.ticket_code || '-');

            $.when(customerPromise, technicianPromise, sparepartPromise).done(function () {
                $('#field-customer').val(String(EDIT_TICKET.customer_id)).trigger('change');
                $('#field-category').val(EDIT_TICKET.category || '');
                $('#field-priority').val(EDIT_TICKET.priority || 'medium');
                $('#field-description').val(EDIT_TICKET.description || '');

                if (EDIT_TICKET.assigned_technician_id) {
                    $('#field-technician').val(String(EDIT_TICKET.assigned_technician_id)).trigger('change.select2');
                }
                if (EDIT_TICKET.supervisor_id) {
                    $('#field-supervisor').val(String(EDIT_TICKET.supervisor_id));
                }
                if (EDIT_TICKET.sla_due_at) {
                    $('#field-sla-due-date').val(String(EDIT_TICKET.sla_due_at).slice(0, 10));
                }

                const latestVisit = (EDIT_TICKET.visits || []).slice().sort((a, b) => new Date(b.scheduled_at) - new Date(a.scheduled_at))[0];
                if (latestVisit) {
                    originalVisitDate = String(latestVisit.scheduled_at).slice(0, 16).replace(' ', 'T');
                    $('#field-visit-date').val(originalVisitDate);
                }

                prefillSparepartAndCostRows();
            });
        } else {
            const defaultSlaDate = new Date();
            defaultSlaDate.setDate(defaultSlaDate.getDate() + 3);
            $('#field-sla-due-date').val(defaultSlaDate.toISOString().slice(0, 10));
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