@extends('layouts.app')

@section('content')
@php
    // Technician = may only see own tickets (view-own without manage)
    $isTechnician = ! hasPermission(auth()->user(), 'aftersales.tickets.manage') && hasPermission(auth()->user(), 'aftersales.tickets.view-own');
@endphp
<style>
    .ticket-filter-chip { padding: 6px 14px; border: 1px solid #D9D9D9; border-radius: 9999px; background: #fff; color: #1E1E1E; font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap; }
    .ticket-filter-chip.is-active { border-color: #115640; background: #E7F3EE; color: #115640; }
</style>
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div id="ticketListView">
    <div class="pt-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div class="min-w-0">
                <h1 class="text-[#115640] font-semibold text-2xl">{{ $isTechnician ? 'Ticket' : 'All Ticket' }}</h1>
                <p class="text-[#757575] mt-1">{{ $isTechnician ? 'Your assigned tickets.' : 'Track and manage all your service tickets in one place.' }}</p>
            </div>
            @if ($isTechnician)
            {{-- Overall Rating (IM-93): one read-only average number, scale 3. No score, comment or customer name per response (BR-S6). --}}
            <div class="self-start sm:self-auto shrink-0 bg-white rounded-lg border border-[#D9D9D9] px-4 py-2 text-right">
                <p class="uppercase font-semibold text-[#9CA3AF]" style="font-size: 10px;">Overall Rating</p>
                <p class="text-[#115640] font-bold text-xl mb-0" id="technician-overall-rating">&mdash;</p>
            </div>
            @endif
            @if (hasPermission(auth()->user(), 'aftersales.tickets.manage'))
            <a href="{{ route('aftersales.pages.tickets.create') }}" class="self-start sm:self-auto shrink-0 whitespace-nowrap bg-[#115640] text-white px-4 py-2 rounded-lg hover:bg-[#0d4633] transition-colors flex items-center gap-2">
                <i class="bi bi-plus-lg"></i> Add Ticket
            </a>
            @endif
        </div>

        {{--SEARCH--}}
        <div class="bg-white rounded-lg border border-[#D9D9D9] p-4 mt-4">
            <div class="flex flex-col lg:flex-row lg:items-stretch gap-3">
                <div class="flex-1 relative min-w-0">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-[#757575] text-base pointer-events-none"></i>
                    <input type="text" id="ticket-search" placeholder="Search ticket code, customer, or technician"
                    class="w-full pl-10 pr-3 py-2 border border-[#D9D9D9] rounded-lg! text-left text-[#1E1E1E] focus:outline-none!">
                </div>
                <div id="ticket-priority-chips" class="flex flex-wrap items-center gap-2">
                    <button type="button" class="ticket-filter-chip" data-priority="low">Low</button>
                    <button type="button" class="ticket-filter-chip" data-priority="medium">Medium</button>
                    <button type="button" class="ticket-filter-chip" data-priority="high">High</button>
                </div>
                <div class="flex items-center gap-2">
                    <label for="ticket-visit-date" class="text-[#757575] whitespace-nowrap">Visit date</label>
                    <input type="date" id="ticket-visit-date"
                        class="border border-[#D9D9D9] rounded-lg px-3 py-2 text-[#1E1E1E] focus:outline-none!">
                </div>
                <div class="grid grid-cols-2 lg:flex gap-3">
                    <button type="button" id="btn-search-ticket"
                    class="bg-[#115640] text-white px-6 py-2 rounded-lg hover:bg-[#0d4633] transition-colors">
                        Search
                    </button>
                    <button type="button" id="btn-reset-ticket"
                    class="bg-white border border-[#D9D9D9] text-[#1E1E1E] px-5 py-2 rounded-lg hover:bg-[#F5F5F5] transition-colors">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        {{--TABLE--}}
        <div class="table-cards-wrap bg-white rounded-lg border border-[#D9D9D9] mt-4 overflow-x-auto">
            <table class="table-cards w-full whitespace-nowrap text-xs">
                <thead class="text-[#1E1E1E]">
                    <tr class="border-b border-b-[#D9D9D9]">
                        <th class="p-3 text-left uppercase text-xs">Ticket ID</th>
                        <th class="p-3 text-center uppercase text-xs">Customer</th>
                        <th class="p-3 text-center uppercase text-xs">Machine</th>
                        <th class="p-3 text-center uppercase text-xs">Technician</th>
                        <th class="p-3 text-center uppercase text-xs">Province</th>
                        <th class="p-3 text-center uppercase text-xs">Region</th>
                        <th class="p-3 text-center uppercase text-xs">Problem</th>
                        <th class="p-3 text-center uppercase text-xs">Open Date</th>
                        <th class="p-3 text-center uppercase text-xs">Visit Date</th>
                        <th class="p-3 text-center uppercase text-xs">Close Date</th>
                        <th class="p-3 text-center uppercase text-xs">SLA</th>
                        <th class="p-3 text-center uppercase text-xs">Aging</th>
                        <th class="p-3 text-center uppercase text-xs">Steps</th>
                        <th class="p-3 text-center uppercase text-xs">Action</th>
                    </tr>
                </thead>
                <tbody id="ticket-table-body" class="text-left"></tbody>
            </table>
        </div>

        {{--PAGINATION--}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-4">
            <div id="ticket-showing" class="text-[#757575]">Showing 0 to 0 entries</div>
            <div id="ticket-pagination" class="flex flex-wrap items-center gap-2"></div>
        </div>
    </div>
    </div>

    @include('pages.aftersales.tickets.partials.details')
    @include('pages.aftersales.tickets.partials.work-order')
    @include('pages.aftersales.tickets.partials.service-agreement')
    @include('pages.aftersales.tickets.partials.service-form')
    @include('pages.aftersales.tickets.partials.supervisor-sign')
</section>

@include('pages.aftersales.tickets.partials.ticket-detail-modal')

@include('pages.aftersales.tickets.partials.ticket-update-logs-modal')

@include('pages.aftersales.tickets.partials.ticket-log-preview-modal')

@include('pages.aftersales.tickets.partials.ticket-delete-modal')

@include('pages.aftersales.tickets.partials.survey-on-behalf-modal')
@endsection

@section('scripts')
<script>
    let currentTicket = null;

    // hak akses (kontrak bagian 1): sembunyikan tombol yang tidak boleh, BE tetap menolak dengan 403
    const CAN_MANAGE = @json(hasPermission(auth()->user(), 'aftersales.tickets.manage'));

    const ticketState = {
        page: 1,
        lastPage: 1,
        perPage: 10,
        search: '',
        priority: [],      // multi-select: low | medium | high
        visitDate: '',     // YYYY-MM-DD, one specific Visit Date (IM-92)
    };

    // filter disimpan di URL query (FR-E24): ?search=..&priority=high,medium
    function syncTicketUrl() {
        const params = new URLSearchParams();
        if (ticketState.search) params.set('search', ticketState.search);
        if (ticketState.priority.length) params.set('priority', ticketState.priority.join(','));
        if (ticketState.visitDate) params.set('visit_date', ticketState.visitDate);
        const query = params.toString();
        history.replaceState(null, '', location.pathname + (query ? '?' + query : '') + location.hash);
    }

    function paintTicketFilters() {
        $('#ticket-search').val(ticketState.search);
        $('#ticket-visit-date').val(ticketState.visitDate);
        $('#ticket-priority-chips .ticket-filter-chip').each(function () {
            $(this).toggleClass('is-active', ticketState.priority.includes($(this).data('priority')));
        });
    }

    function readTicketUrl() {
        const params = new URLSearchParams(location.search);
        const allowed = ['low', 'medium', 'high'];
        ticketState.search = params.get('search') || '';
        ticketState.priority = (params.get('priority') || '').split(',').filter(v => allowed.includes(v));
        const visitDate = params.get('visit_date') || '';
        ticketState.visitDate = /^\d{4}-\d{2}-\d{2}$/.test(visitDate) ? visitDate : '';
        paintTicketFilters();
    }

    // Step name -> display percent. Backend hanya expose nama step (lihat Ticket::getProgressAttribute),
    // jadi mapping persen dibuat di frontend supaya cocok dengan tampilan mockup.
    const PROGRESS_PERCENT = {
        published: 10,
        assigned: 25,
        on_site: 40,
        repair: 60,
        waiting_sparepart: 70,
        documentation_published: 85,
        satisfaction_submitted: 95,
        closed: 100,
    };

    const PRIORITY_BADGE = {
        low: 'bg-[#F0F0F0] text-[#757575]',
        medium: 'bg-[#E7ECFB] text-[#3B4FA6]',
        high: 'bg-[#FFF3CD] text-[#8A6D00]',
    };

    const CATEGORY_LABEL = {
        electrical: 'Electrical',
        mechanical: 'Mechanical',
        refrigeration_system: 'Refrigeration System',
        production: 'Production',
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // tanggal selalu WIB (kontrak: jangan pakai toISOString, tanggal bisa mundur sehari)
    function formatDate(value) {
        if (!value) return '—';
        return new Date(value).toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' });
    }

    function priorityBadge(priority) {
        const cls = PRIORITY_BADGE[priority] || PRIORITY_BADGE.low;
        return `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium ${cls}">${escapeHtml(priority ? priority.charAt(0).toUpperCase() + priority.slice(1) : '-')}</span>`;
    }

    function slaBadge(slaStatus) {
        if (!slaStatus) return '<span class="text-[#B0B0B0]">-</span>';
        let cls = 'text-[#900B09]';
        if (slaStatus === 'On Time') cls = 'text-[#115640]';
        else if (slaStatus === 'Pending') cls = 'text-[#757575]';
        return `<span class="font-semibold ${cls}">${escapeHtml(slaStatus)}</span>`;
    }

    function stepBadge(step, currentRecoming) {
        const percent = PROGRESS_PERCENT[step] ?? 0;
        let cls = 'bg-[#FBEAEA] text-[#900B09]';
        if (percent >= 100) cls = 'bg-[#E6F4EF] text-[#115640]';
        else if (percent >= 50) cls = 'bg-[#FFF3CD] text-[#8A6D00]';
        let label = STEP_LABEL[step] || step || '-';
        // RC-09: "Recoming · visit {visit_no} · {tanggal}"
        if (step === 'recoming' && currentRecoming) {
            label = `Recoming · visit ${currentRecoming.visit_no} · ${formatDate(currentRecoming.planned_visit_at)}`;
        }
        return `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-semibold text-center ${cls}">${escapeHtml(label)}</span>`;
    }

    // Reminders under STEPS come straight from the API booleans (never computed from dates in the FE).
    function reminderBadges(reminders) {
        if (!reminders) return '';
        let html = '';
        if (reminders.survey_due) {
            html += '<span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium text-center bg-[#FFF3CD] text-[#8A6D00]">Survey due</span>';
        }
        if (reminders.happy_call_due) {
            html += '<span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium text-center bg-[#E7ECFB] text-[#3B4FA6]">Happy Call due</span>';
        }
        return html ? `<div class="flex flex-wrap justify-center gap-1 mt-1">${html}</div>` : '';
    }

    function renderTicketRows(rows) {
        const $body = $('#ticket-table-body');

        if (!rows || rows.length === 0) {
            $body.html('<tr><td colspan="14" class="text-center p-4 text-[#757575]">No tickets found</td></tr>');
            return;
        }

        const html = rows.map(row => {
            const customer = row.customer || {};
            const machine = row.customer_product || {};
            const technician = row.technician || {};

            return `
            <tr class="border-t border-t-[#D9D9D9] align-middle">
                <td class="p-3 font-semibold text-[#115640]" data-primary data-label="Ticket ID"><div class="flex flex-wrap items-center gap-2"><span>${escapeHtml(row.ticket_code)}</span>${priorityBadge(row.priority)}</div></td>
                <td class="p-3 text-center" data-label="Customer">${escapeHtml(customer.name)}</td>
                <td class="p-3 text-center" data-label="Machine">${escapeHtml(machine.machine_name || machine.product?.name)}</td>
                <td class="p-3 text-center" data-label="Technician" data-secondary>${escapeHtml(technician.name)}</td>
                <td class="p-3 text-center" data-label="Province" data-secondary>${escapeHtml(customer.province?.name)}</td>
                <td class="p-3 text-center" data-label="Region" data-secondary>${escapeHtml(customer.region?.name)}</td>
                <td class="p-3 text-center" data-label="Problem" data-secondary>${escapeHtml(CATEGORY_LABEL[row.category] || row.category)}</td>
                <td class="p-3 text-center" data-label="Open Date" data-secondary>${formatDate(row.created_at)}</td>
                <td class="p-3 text-center" data-label="Visit Date" data-secondary>${formatDate(row.visit_date)}</td>
                <td class="p-3 text-center" data-label="Close Date" data-secondary>${formatDate(row.closed_at)}</td>
                <td class="p-3 text-center" data-label="SLA">${slaBadge(row.sla_status)}</td>
                <td class="p-3 text-center" data-label="Aging" data-secondary>${row.aging_days ?? 0}</td>
                <td class="p-3 text-center" data-label="Steps">${stepBadge(row.progress, row.current_recoming)}${reminderBadges(row.reminders)}</td>
                <td class="p-3 text-center" data-actions data-label="Action">
                    <div class="dropdown">
                        <button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle"
                            type="button" id="ticketActionsDropdown${row.id}"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="ticketActionsDropdown${row.id}">
                            <a class="dropdown-item flex! items-center! gap-2! cursor-pointer btn-ticket-details">
                                <i class="bi bi-file-earmark-text"></i> Details
                            </a>
                            <a class="dropdown-item flex! items-center! gap-2! cursor-pointer" href="/aftersales/tickets/${row.id}/edit">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a class="dropdown-item flex! items-center! gap-2! cursor-pointer btn-ticket-update-logs">
                                <i class="bi bi-clock-history"></i> Update Logs
                            </a>
                            ${CAN_MANAGE ? `
                            <a class="dropdown-item flex! items-center! gap-2! cursor-pointer btn-ticket-delete text-[#900B09]">
                                <i class="bi bi-trash"></i> Delete
                            </a>` : ''}
                        </div>
                    </div>
                </td>
                <td class="card-more-cell"><button type="button" class="card-more">Show more</button></td>
            </tr>
        `;
        }).join('');

        $body.html(html);

        // simpan data mentah tiap row supaya modal detail tidak perlu request baru
        $body.find('.btn-ticket-detail').each(function (index) {
            $(this).data('ticket', rows[index]);
        });

        $body.find('.btn-ticket-update-logs').each(function (index) {
            $(this).data('ticket', rows[index]);
        });

        $body.find('.btn-ticket-details').each(function (index) {
            $(this).data('ticket', rows[index]);
        });

        $body.find('.btn-ticket-delete').each(function () {
            $(this).data('ticket', rows[$(this).closest('tr').index()]);
        });
    }

    function renderTicketPagination(pagination) {
        pagination = pagination || {};
        ticketState.lastPage = pagination.last_page || 1;

        const from = pagination.total ? ((pagination.current_page - 1) * ticketState.perPage) + 1 : 0;
        const to = Math.min(pagination.current_page * ticketState.perPage, pagination.total || 0);
        $('#ticket-showing').text(`Showing ${from} to ${to} of ${pagination.total || 0} entries`);

        const current = pagination.current_page || 1;
        const last = pagination.last_page || 1;
        let buttons = `<button class="w-8 h-8 rounded-lg border border-[#D9D9D9] bg-white" ${current <= 1 ? 'disabled' : ''} data-page="${current - 1}">
            <i class="bi bi-chevron-left"></i>
        </button>`;

        for (let i = 1; i <= last; i++) {
            buttons += `<button class="w-8 h-8 p-2 rounded-lg border border-[#D9D9D9] ${i === current ? 'bg-grey-300 text-primary' : 'bg-white'}" data-page="${i}">${i}</button>`;
        }

        buttons += `<button class="w-8 h-8 rounded-lg border border-[#D9D9D9] bg-white" ${current >= last ? 'disabled' : ''} data-page="${current + 1}">
            <i class="bi bi-chevron-right"></i>
        </button>`;

        $('#ticket-pagination').html(buttons);
    }

    function loadTickets() {
        syncTicketUrl();
        $('#ticket-table-body').html('<tr><td colspan="14" class="text-center p-4 text-[#757575]">Loading...</td></tr>');

        $.ajax({
            url: '/api/aftersales/tickets',
            method: 'GET',
            data: {
                page: ticketState.page,
                per_page: ticketState.perPage,
                search: ticketState.search,
                priority: ticketState.priority.length ? ticketState.priority : undefined, // jadi priority[]=..
                visit_date: ticketState.visitDate || undefined,
            },
            success: function (response) {
                renderTicketRows(response.data);
                renderTicketPagination(response);
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Failed to load tickets';
                $('#ticket-table-body').html(`<tr><td colspan="14" class="text-center p-4 text-[#900B09]">${escapeHtml(message)}</td></tr>`);
            }
        });
    }

    const STEP_LABEL = {
        published: 'Published',
        assigned: 'Assigned',
        on_site: 'On Site',
        repair: 'Repair',
        waiting_sparepart: 'Waiting Sparepart',
        documentation_published: 'Documentation Published',
        satisfaction_submitted: 'Satisfaction Submitted',
        happy_call_completed: 'Happy Call Completed',
        closed: 'Closed',
        recoming: 'Recoming',
    };

    function loadOverallRating() {
        const $el = $('#technician-overall-rating');
        if (!$el.length) return;
        $.getJSON('/api/aftersales/technicians/me/overall-rating').done(function (response) {
            const value = response.data?.overall_rating;
            const max = response.data?.rating_scale_max || 3;
            $el.text(value === null || value === undefined ? '—' : `${Number(value).toFixed(1)} / ${max}`);
        }).fail(function () {
            $el.text('—');
        });
    }

    $(function () {
        readTicketUrl();
        loadTickets();
        loadOverallRating();

        let searchTimer = null;
        $('#ticket-search').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                ticketState.search = $('#ticket-search').val();
                ticketState.page = 1;
                loadTickets();
            }, 400);
        });


        $('#ticket-priority-chips').on('click', '.ticket-filter-chip', function () {
            const value = $(this).data('priority');
            ticketState.priority = ticketState.priority.includes(value)
                ? ticketState.priority.filter(v => v !== value)
                : ticketState.priority.concat(value);
            paintTicketFilters();
            ticketState.page = 1;
            loadTickets();
        });

        $('#ticket-visit-date').on('change', function () {
            ticketState.visitDate = $(this).val();
            ticketState.page = 1;
            loadTickets();
        });

        // Reset: kosongkan search, priority, dan visit date (dan URL-nya)
        $('#btn-reset-ticket').on('click', function () {
            ticketState.search = '';
            ticketState.priority = [];
            ticketState.visitDate = '';
            ticketState.page = 1;
            paintTicketFilters();
            loadTickets();
        });

        $('#btn-search-ticket').on('click', function () {
            ticketState.search = $('#ticket-search').val();
            ticketState.page = 1;
            loadTickets();
        });

        // mobile card view: expand / collapse the secondary fields of one ticket
        $(document).on('click', '#ticket-table-body .card-more', function () {
            const $row = $(this).closest('tr').toggleClass('is-open');
            $(this).text($row.hasClass('is-open') ? 'Show less' : 'Show more');
        });

        $(document).on('click', '#ticket-pagination button', function () {
            const page = Number($(this).data('page'));
            if (!page || page < 1 || page > ticketState.lastPage) return;
            ticketState.page = page;
            loadTickets();
        });

        $(document).on('click', '.btn-ticket-detail', function () {
            openTicketDetail($(this).data('ticket'));
        });

        // SLA Target: tanggal + jam dari sla_due_at, fallback ke nilai + satuan
        function slaLabel(t, slaUnit) {
            if (t.sla_due_at) {
                const d = new Date(t.sla_due_at);
                if (!isNaN(d)) {
                    return d.toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false });
                }
            }
            return t.sla_value ? t.sla_value + ' ' + slaUnit : '';
        }

        // TICKET DETAILS (frontend only, diisi dari data row API)
        $(document).on('click', '.btn-ticket-details', function () {
            const t = $(this).data('ticket') || {};
            currentTicket = t;
            const slaUnit = t.sla_unit === 'hour' ? 'hour(s)' : 'business day(s)';
            const woUrl = '/aftersales/tickets/' + t.id + '/work-order-pdf';
            const text = function (id, value) { $(id).text(value || '-'); };

            text('#tdCode', t.ticket_code);
            text('#tdInfoCode', t.ticket_code);
            text('#tdProgress', STEP_LABEL[t.progress] || t.progress);
            text('#tdCustomer', t.customer?.name);
            text('#tdMachine', t.customer_product?.machine_name || t.customer_product?.product?.name);
            text('#tdProvinceRegion', [t.customer?.province?.name, t.customer?.region?.name].filter(Boolean).join(' · '));
            const warrantyActive = t.customer_product?.warranty_active;
            text('#tdWarranty', warrantyActive === null || warrantyActive === undefined ? '' : (warrantyActive ? 'Active' : 'Expired'));
            text('#tdTechnician', t.technician?.name);
            text('#tdSupervisor', t.supervisor?.name); // TODO: supervisor belum di-load oleh API index
            text('#tdCategory', CATEGORY_LABEL[t.category] || t.category);
            text('#tdVisit', t.visit_date ? formatDate(t.visit_date) : '');
            text('#tdSla', slaLabel(t, slaUnit));
            $('#tdPriority').html(t.priority ? priorityBadge(t.priority) : '');
            text('#tdDescription', t.description);
            loadTicketSurveyLink(t.id);
            $('#tdWoView, #tdWoDownload').attr('href', woUrl);

            $('#ticketListView').addClass('hidden');
            $('#ticketDetailsView').removeClass('hidden');
            window.scrollTo(0, 0);
        });

        $(document).on('click', '.btn-ticket-details-back', function () {
            $('#ticketDetailsView').addClass('hidden');
            $('#ticketListView').removeClass('hidden');
            loadTickets();
        });

        // WORK ORDER (frontend only)
        $(document).on('click', '#tdWoView', function () {
            const t = currentTicket || {};
            const slaUnit = t.sla_unit === 'hour' ? 'hour(s)' : 'business day(s)';
            const text = function (id, value) { $(id).text(value || '-'); };

            // TODO: WO number sebaiknya dari ticket_work_orders.wo_number; sementara diturunkan dari ticket_code
            text('#woNumber', String(t.ticket_code || '').replace('DAX-AF-', 'WO-'));
            text('#woTicketRef', t.ticket_code);
            text('#woCustomer', t.customer?.name);
            text('#woMachine', t.customer_product?.product?.name);
            text('#woProblem', CATEGORY_LABEL[t.category] || t.category);
            text('#woTechnician', t.technician?.name);
            text('#woVisit', t.visit_date ? formatDate(t.visit_date) : '');
            text('#woSla', slaLabel(t, slaUnit));
            $('#woExportPdf').attr('href', '/aftersales/tickets/' + t.id + '/work-order-pdf');

            $('#ticketDetailsView').addClass('hidden');
            $('#ticketWorkOrderView').removeClass('hidden');
            window.scrollTo(0, 0);
        });

        $(document).on('click', '.btn-wo-back', function () {
            $('#ticketWorkOrderView').addClass('hidden');
            $('#ticketDetailsView').removeClass('hidden');
        });

        // Service Agreement, Service Form, tanda tangan, dan Supervisor Sign: lihat partials/documents-script.blade.php

        function loadTicketSurveyLink(ticketId) {
            $('#tdSurveyUrl').removeAttr('href').text('Loading survey link...');
            $('#tdSurveyStatus').text('Loading...');
            $('#tdShareLink').text('Share Link').prop('disabled', true).data('url', '');
            $('#tdShareWhatsapp').prop('disabled', true).data('url', '');
            $.ajax({ url: '/api/aftersales/tickets/' + ticketId + '/survey-link', headers: { Accept: 'application/json' } })
                .done(function (res) {
                    if (currentTicket?.id !== ticketId) return;
                    const link = res.data || {};
                    const labels = { active: 'Active', inactive: 'Inactive', used: 'Completed', expired: 'Expired', replaced: 'Replaced' };
                    $('#tdSurveyStatus').text(labels[link.status] || 'Unavailable');
                    $(document).trigger('ticket:survey-link', [ticketId, link]); // banner Resubmission di documents-script
                    if (link.url && link.can_share) {
                        $('#tdSurveyUrl').attr('href', link.url).text(link.url);
                        $('#tdShareLink, #tdShareWhatsapp').prop('disabled', false).data('url', link.url);
                    } else {
                        $('#tdSurveyUrl').text(link.status === 'used' ? 'This survey has already been completed.' : 'No active survey link is available.');
                    }
                })
                .fail(function () {
                    if (currentTicket?.id !== ticketId) return;
                    $('#tdSurveyStatus').text('Unavailable');
                    $('#tdSurveyUrl').text('Unable to load the survey link. Please reopen the ticket to retry.');
                });
        }

        $(document).on('click', '#tdShareLink', function () {
            const $btn = $(this);
            const url = $btn.data('url') || '';
            if (!url) return;
            const done = function () {
                $btn.text('Copied');
                setTimeout(function () { $btn.text('Share Link'); }, 1500);
                logSurveyShare('copy');
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(done).catch(function () { showCopyLink(url); });
            } else {
                showCopyLink(url);
            }
        });

        $(document).on('click', '#tdShareWhatsapp', function () {
            const url = $(this).data('url') || '';
            if (!url) return;
            window.open('https://wa.me/?text=' + encodeURIComponent('Please complete our service satisfaction survey: ' + url), '_blank', 'noopener');
            logSurveyShare('whatsapp');
        });

        // catat pembagian link (kontrak 6.4): via = copy | whatsapp
        function logSurveyShare(via) {
            const id = currentTicket?.id;
            if (!id) return;
            $.ajax({
                url: '/api/aftersales/tickets/' + id + '/survey-link/share',
                method: 'POST',
                contentType: 'application/json',
                headers: { Accept: 'application/json' },
                data: JSON.stringify({ via: via }),
            });
        }

        function showCopyLink(url) {
            Swal.fire({ title: 'Copy survey link', input: 'text', inputValue: url, inputAttributes: { readonly: 'readonly' }, confirmButtonText: 'Close', confirmButtonColor: '#115640' });
        }

        $(document).on('click', '.btn-ticket-update-logs', function () {
            openTicketUpdateLogs($(this).data('ticket'));
        });

        $(document).on('click', '.btn-ticket-log-preview', function () {
            $('#ticketUpdateLogsModal').modal('hide');
            openTicketLogPreview($(this).data('step'));
        });

        $('#ticketLogPreviewModal').on('hidden.bs.modal', function () {
            $('#ticketUpdateLogsModal').modal('show');
        });
    });
</script>
@include('pages.aftersales.tickets.partials.documents-script')
@endsection
