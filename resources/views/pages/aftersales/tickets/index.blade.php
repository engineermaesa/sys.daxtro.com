@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-[#115640] font-semibold text-2xl">All Ticket</h1>
                <p class="text-[#757575] mt-1">Track and manage all your service tickets in one place.</p>
            </div>
            <a href="{{ route('aftersales.pages.tickets.create') }}" class="bg-[#115640] text-white px-4 py-2 rounded-lg hover:bg-[#0d4633] transition-colors flex items-center gap-2">
                <i class="bi bi-plus-lg"></i> Add Ticket
            </a>
        </div>

        {{--SEARCH--}}
        <div class="bg-white rounded-lg border border-[#D9D9D9] p-4 mt-4">
            <div class="flex items-stretch gap-3">
                <div class="flex-1 relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-[#757575] text-base pointer-events-none"></i>
                    <input type="text" id="ticket-search" placeholder="Search ticket code, customer, or machine"
                    class="w-full pl-10 pr-3 py-2 border border-[#D9D9D9] rounded-lg! text-left text-[#1E1E1E] focus:outline-none!">
                </div>
                <button type="button" id="btn-search-ticket"
                class="bg-[#115640] text-white px-6 rounded-lg hover:bg-[#0d4633] transition-colors">
                    Search
                </button>
            </div>
        </div>

        {{--TABLE--}}
        <div class="bg-white rounded-lg border border-[#D9D9D9] mt-4 overflow-x-auto">
            <table class="w-full">
                <thead class="text-[#1E1E1E]">
                    <tr class="border-b border-b-[#D9D9D9]">
                        <th class="p-3 text-left uppercase text-xs">Ticket ID</th>
                        <th class="p-3 text-left uppercase text-xs">Customer</th>
                        <th class="p-3 text-left uppercase text-xs">Machine</th>
                        <th class="p-3 text-left uppercase text-xs">Technician</th>
                        <th class="p-3 text-left uppercase text-xs">Province</th>
                        <th class="p-3 text-left uppercase text-xs">Region</th>
                        <th class="p-3 text-left uppercase text-xs">Desc.</th>
                        <th class="p-3 text-left uppercase text-xs">Problem</th>
                        <th class="p-3 text-left uppercase text-xs">Open Date</th>
                        <th class="p-3 text-left uppercase text-xs">Visit Date</th>
                        <th class="p-3 text-left uppercase text-xs">Close Date</th>
                        <th class="p-3 text-left uppercase text-xs">SLA</th>
                        <th class="p-3 text-left uppercase text-xs">Aging</th>
                        <th class="p-3 text-left uppercase text-xs">Priority</th>
                        <th class="p-3 text-left uppercase text-xs">Steps</th>
                        <th class="p-3 text-center uppercase text-xs">Action</th>
                    </tr>
                </thead>
                <tbody id="ticket-table-body" class="text-left"></tbody>
            </table>
        </div>

        {{--PAGINATION--}}
        <div class="flex items-center justify-between mt-4">
            <div id="ticket-showing" class="text-[#757575]">Showing 0 to 0 entries</div>
            <div id="ticket-pagination" class="flex items-center gap-2"></div>
        </div>
    </div>
</section>

@include('pages.aftersales.tickets.partials.ticket-detail-modal')

@include('pages.aftersales.tickets.partials.ticket-update-logs-modal')

@include('pages.aftersales.tickets.partials.ticket-log-preview-modal')
@endsection

@section('scripts')
<script>
    const ticketState = {
        page: 1,
        lastPage: 1,
        perPage: 10,
        search: '',
    };

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
        critical: 'bg-[#FBEAEA] text-[#900B09]',
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

    function formatDate(value) {
        if (!value) return '—';
        return new Date(value).toISOString().slice(0, 10);
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

    function stepBadge(step) {
        const percent = PROGRESS_PERCENT[step] ?? 0;
        let cls = 'bg-[#FBEAEA] text-[#900B09]';
        if (percent >= 100) cls = 'bg-[#E6F4EF] text-[#115640]';
        else if (percent >= 50) cls = 'bg-[#FFF3CD] text-[#8A6D00]';
        const label = STEP_LABEL[step] || step || '-';
        return `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-semibold ${cls}">${escapeHtml(label)}</span>`;
    }

    function renderTicketRows(rows) {
        const $body = $('#ticket-table-body');

        if (!rows || rows.length === 0) {
            $body.html('<tr><td colspan="16" class="text-center p-4 text-[#757575]">No tickets found</td></tr>');
            return;
        }

        const html = rows.map(row => {
            const customer = row.customer || {};
            const machine = row.customer_product || {};
            const technician = row.technician || {};

            return `
            <tr class="border-t border-t-[#D9D9D9] align-top">
                <td class="p-3 font-semibold text-[#115640]">${escapeHtml(row.ticket_code)}</td>
                <td class="p-3">${escapeHtml(customer.name)}</td>
                <td class="p-3">${escapeHtml(machine.product?.name)}</td>
                <td class="p-3">${escapeHtml(technician.name)}</td>
                <td class="p-3">${escapeHtml(customer.province?.name)}</td>
                <td class="p-3">${escapeHtml(customer.region?.name)}</td>
                <td class="p-3 max-w-[160px]">
                    <span class="line-clamp-2">${escapeHtml(row.description)}</span>
                    <button type="button" class="btn-ticket-detail text-[#115640] text-xs underline cursor-pointer"
                        data-ticket='@json([])'>- View Detail</button>
                </td>
                <td class="p-3">${escapeHtml(CATEGORY_LABEL[row.category] || row.category)}</td>
                <td class="p-3">${formatDate(row.created_at)}</td>
                <td class="p-3">${formatDate(row.visit_date)}</td>
                <td class="p-3">${formatDate(row.closed_at)}</td>
                <td class="p-3">${slaBadge(row.sla_status)}</td>
                <td class="p-3">${row.aging_days ?? 0}</td>
                <td class="p-3">${priorityBadge(row.priority)}</td>
                <td class="p-3">${stepBadge(row.progress)}</td>
                <td class="p-3 text-center">
                    <div class="dropdown">
                        <button class="bg-white px-1! py-px! cursor-pointer border border-[#D5D5D5] rounded-md duration-300 ease-in-out hover:bg-[#115640]! transition-all! hover:text-white! dropdown-toggle"
                            type="button" id="ticketActionsDropdown${row.id}"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="ticketActionsDropdown${row.id}">
                            <a class="dropdown-item flex! items-center! gap-2! cursor-pointer" href="/aftersales/tickets/${row.id}/edit">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a class="dropdown-item flex! items-center! gap-2! cursor-pointer btn-ticket-update-logs">
                                <i class="bi bi-clock-history"></i> Update Logs
                            </a>
                        </div>
                    </div>
                </td>
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
        $('#ticket-table-body').html('<tr><td colspan="16" class="text-center p-4 text-[#757575]">Loading...</td></tr>');

        $.ajax({
            url: '/api/aftersales/tickets',
            method: 'GET',
            data: {
                page: ticketState.page,
                per_page: ticketState.perPage,
                search: ticketState.search,
            },
            success: function (response) {
                renderTicketRows(response.data);
                renderTicketPagination(response);
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Failed to load tickets';
                $('#ticket-table-body').html(`<tr><td colspan="16" class="text-center p-4 text-[#900B09]">${escapeHtml(message)}</td></tr>`);
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
        closed: 'Closed',
    };

    $(function () {
        loadTickets();

        let searchTimer = null;
        $('#ticket-search').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                ticketState.search = $('#ticket-search').val();
                ticketState.page = 1;
                loadTickets();
            }, 400);
        });

        $('#btn-search-ticket').on('click', function () {
            ticketState.search = $('#ticket-search').val();
            ticketState.page = 1;
            loadTickets();
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
@endsection