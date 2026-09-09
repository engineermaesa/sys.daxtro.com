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

{{-- TICKET DETAIL MODAL --}}
<div class="modal fade" id="ticketDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl! overflow-hidden border-0!">
            <div class="modal-header border-0! items-start px-6! pt-6! pb-4!">
                <div>
                    <h5 id="ticketDetailCode" class="modal-title text-[#115640] text-xl font-bold mb-0">TKT-0000-000</h5>
                    <p id="ticketDetailSubtitle" class="text-[#757575] text-sm mb-0"></p>
                </div>
                <button type="button" class="close cursor-pointer opacity-100! text-[#1E1E1E]!" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="text-2xl font-light leading-none">&times;</span>
                </button>
            </div>
            <div class="modal-body px-6! pb-6! pt-0!">
                <hr class="border-[#D9D9D9] mb-4">
                <label class="uppercase text-[11px] text-[#757575] font-semibold tracking-wide">Problem Description</label>
                <p id="ticketDetailDescription" class="font-medium mt-1 mb-4"></p>

                <p class="mb-0">
                    <span class="text-[#1E1E1E] font-semibold">Problem:</span>
                    <span id="ticketDetailCategory" class="text-[#1E1E1E]"></span>
                    &nbsp;&middot;&nbsp;
                    <span id="ticketDetailPriority"></span>
                </p>
            </div>
        </div>
    </div>
</div>

{{-- TICKET UPDATE LOGS MODAL --}}
<div class="modal fade" id="ticketUpdateLogsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-2xl! overflow-hidden border-0!">
            <div class="modal-header border-0! items-start px-6! pt-6! pb-4!">
                <div>
                    <h5 id="ticketUpdateLogsCode" class="modal-title text-[#115640] text-xl font-bold mb-0">Update Logs</h5>
                    <p class="text-[#757575] text-sm mb-0">Ticket progress history</p>
                </div>
                <button type="button" class="close cursor-pointer opacity-100! text-[#1E1E1E]!" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="text-2xl font-light leading-none">&times;</span>
                </button>
            </div>
            <div class="modal-body px-6! pb-6! pt-0!">
                <hr class="border-[#D9D9D9] mb-4">

                <div class="overflow-x-auto mb-4">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#D9D9D9] text-left text-xs uppercase text-[#757575]">
                                <th class="p-2">Date</th>
                                <th class="p-2">Activity</th>
                                <th class="p-2">Note</th>
                                <th class="p-2">User</th>
                            </tr>
                        </thead>
                        <tbody id="ticketUpdateLogsRows"></tbody>
                    </table>
                </div>

                <div class="border-t border-[#D9D9D9] pt-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2">
                        <select id="ticketLogStep" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                            <option value="on_site">On Site</option>
                            <option value="repair">Repair</option>
                            <option value="waiting_sparepart">Waiting Sparepart</option>
                            <option value="documentation_published">Documentation Published</option>
                            <option value="satisfaction_submitted">Satisfaction Submitted</option>
                            <option value="closed">Closed</option>
                        </select>
                        <input type="text" id="ticketLogNote" placeholder="Note..."
                            class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!">
                    </div>

                    <div id="ticketLogSatisfactionFields" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2">
                        @foreach ([
                            'timeliness' => 'Timeliness',
                            'technician_attitude' => 'Technician Attitude',
                            'technical_knowledge' => 'Technical Knowledge',
                            'work_neatness' => 'Work Neatness',
                            'solution_quality' => 'Solution Quality',
                        ] as $key => $label)
                            <div>
                                <label class="block text-xs text-[#757575] mb-1">{{ $label }}</label>
                                <select class="ticket-log-satisfaction-field w-full border border-[#D9D9D9] rounded-lg px-3 py-2 focus:outline-none!" data-field="{{ $key }}">
                                    <option value="">Rate 1-5</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                            </div>
                        @endforeach
                    </div>

                    <div id="ticketLogPhotoFields" class="hidden mb-2">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs text-[#757575]">Attachments (optional)</label>
                            <button type="button" id="btn-add-ticket-log-photo" class="text-xs text-[#115640] underline cursor-pointer">
                                <i class="bi bi-plus-lg"></i> Add photo
                            </button>
                        </div>
                        <div id="ticketLogPhotoRows" class="space-y-2"></div>
                    </div>

                    <button type="button" id="btn-add-ticket-log"
                        class="bg-[#115640] text-white px-4 py-2 rounded-lg hover:bg-[#0d4633] transition-colors cursor-pointer">
                        <i class="bi bi-plus-lg"></i> Activity
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- TICKET LOG PREVIEW MODAL (Documentation photos / Satisfaction ratings) --}}
<div class="modal fade" id="ticketLogPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl! overflow-hidden border-0!">
            <div class="modal-header border-0! items-start px-6! pt-6! pb-4!">
                <h5 id="ticketLogPreviewTitle" class="modal-title text-[#115640] text-xl font-bold mb-0">Preview</h5>
                <button type="button" class="close cursor-pointer opacity-100! text-[#1E1E1E]!" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="text-2xl font-light leading-none">&times;</span>
                </button>
            </div>
            <div class="modal-body px-6! pb-6! pt-0!">
                <hr class="border-[#D9D9D9] mb-4">
                <div id="ticketLogPreviewBody"></div>
                <button type="button" data-dismiss="modal"
                    class="mt-4 border border-[#D9D9D9] text-[#1E1E1E] rounded-lg px-4 py-2 hover:bg-gray-50 cursor-pointer">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
            </div>
        </div>
    </div>
</div>
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

    function formatDateTime(value) {
        if (!value) return '—';
        return new Date(value).toLocaleString('id-ID');
    }

    // Maps each loggable step to the existing, already-built ticket endpoint
    // that actually persists it (repair/on-site/waiting-sparepart/close just
    // take a note; documentation/satisfaction have their own real payloads).
    const STEP_ENDPOINT = {
        on_site: 'on-site',
        repair: 'repair',
        waiting_sparepart: 'waiting-sparepart',
        documentation_published: 'documentation',
        satisfaction_submitted: 'satisfaction',
        closed: 'close',
    };

    let currentUpdateLogsTicketId = null;
    let ticketLogPhotoRowCount = 0;
    let currentTicketPhotos = [];
    let currentTicketSatisfaction = null;

    const PHOTO_CATEGORY_LABEL = {
        problem: 'Problem',
        analysis: 'Analysis',
        repair: 'Repair',
    };

    const SATISFACTION_LABEL = {
        timeliness: 'Timeliness',
        technician_attitude: 'Technician Attitude',
        technical_knowledge: 'Technical Knowledge',
        work_neatness: 'Work Neatness',
        solution_quality: 'Solution Quality',
    };

    function openTicketLogPreview(step) {
        if (step === 'documentation_published') {
            $('#ticketLogPreviewTitle').text('Documentation Attachments');

            if (!currentTicketPhotos || currentTicketPhotos.length === 0) {
                $('#ticketLogPreviewBody').html('<p class="text-[#757575] text-sm mb-0">No attachments.</p>');
            } else {
                const groups = {};
                currentTicketPhotos.forEach(photo => {
                    groups[photo.category] = groups[photo.category] || [];
                    groups[photo.category].push(photo);
                });

                const html = Object.keys(groups).map(category => `
                    <div class="mb-3">
                        <label class="block text-xs uppercase text-[#757575] font-semibold mb-2">${escapeHtml(PHOTO_CATEGORY_LABEL[category] || category)}</label>
                        <div class="flex flex-wrap gap-2">
                            ${groups[category].map(photo => `
                                <a href="/storage/${photo.file_path}" target="_blank" rel="noopener">
                                    <img src="/storage/${photo.file_path}" alt="${escapeHtml(photo.file_name || '')}" class="w-20 h-20 object-cover rounded-lg border border-[#D9D9D9]">
                                </a>
                            `).join('')}
                        </div>
                    </div>
                `).join('');

                $('#ticketLogPreviewBody').html(html);
            }
        } else if (step === 'satisfaction_submitted') {
            $('#ticketLogPreviewTitle').text('Satisfaction Result');

            if (!currentTicketSatisfaction) {
                $('#ticketLogPreviewBody').html('<p class="text-[#757575] text-sm mb-0">No satisfaction data.</p>');
            } else {
                const html = Object.keys(SATISFACTION_LABEL).map(key => `
                    <div class="flex items-center justify-between border-b border-[#F0F0F0] py-2">
                        <span class="text-sm text-[#1E1E1E]">${escapeHtml(SATISFACTION_LABEL[key])}</span>
                        <span class="font-semibold text-[#115640]">${escapeHtml(currentTicketSatisfaction[key] ?? '-')} / 5</span>
                    </div>
                `).join('');

                $('#ticketLogPreviewBody').html(html);
            }
        }

        $('#ticketLogPreviewModal').modal('show');
    }

    function addTicketLogPhotoRow() {
        ticketLogPhotoRowCount += 1;
        const rowId = ticketLogPhotoRowCount;

        const html = `
            <div class="flex items-center gap-2" data-photo-row="${rowId}">
                <select class="ticket-log-photo-category border border-[#D9D9D9] rounded-lg px-2 py-1.5 text-sm focus:outline-none!">
                    <option value="problem">Problem</option>
                    <option value="analysis">Analysis</option>
                    <option value="repair">Repair</option>
                </select>
                <input type="file" accept="image/*" class="ticket-log-photo-file flex-1 border border-[#D9D9D9] rounded-lg px-2 py-1.5 text-sm focus:outline-none!">
                <button type="button" class="btn-remove-ticket-log-photo text-[#900B09] cursor-pointer" title="Remove">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;

        $('#ticketLogPhotoRows').append(html);
    }

    const PREVIEWABLE_STEPS = ['documentation_published', 'satisfaction_submitted'];

    function renderTicketUpdateLogsRows(logs) {
        logs = (logs || []).slice().sort((a, b) => a.id - b.id);

        const html = logs.length > 0
            ? logs.map(log => `
                <tr class="border-t border-[#D9D9D9]">
                    <td class="p-2 whitespace-nowrap">${formatDateTime(log.created_at)}</td>
                    <td class="p-2 font-semibold text-[#1E1E1E]">
                        ${escapeHtml(STEP_LABEL[log.step] || log.step)}
                        ${PREVIEWABLE_STEPS.includes(log.step)
                            ? `<button type="button" class="btn-ticket-log-preview text-xs text-[#115640] underline cursor-pointer ml-2" data-step="${log.step}">Preview</button>`
                            : ''}
                    </td>
                    <td class="p-2 text-[#757575]">${escapeHtml(log.note || '-')}</td>
                    <td class="p-2">${escapeHtml(log.actor?.name || '-')}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="4" class="p-2 text-center text-[#757575]">No update logs yet.</td></tr>';

        $('#ticketUpdateLogsRows').html(html);
    }

    function resetTicketLogForm() {
        $('#ticketLogStep').val('on_site');
        $('#ticketLogNote').val('');
        $('.ticket-log-satisfaction-field').val('');
        $('#ticketLogSatisfactionFields').addClass('hidden');
        $('#ticketLogPhotoFields').addClass('hidden');
        $('#ticketLogPhotoRows').empty();
        $('#ticketLogNote').removeClass('hidden');
    }

    function openTicketUpdateLogs(ticket) {
        currentUpdateLogsTicketId = ticket.id;
        currentTicketPhotos = [];
        currentTicketSatisfaction = null;
        $('#ticketUpdateLogsCode').text(ticket.ticket_code || 'Update Logs');
        renderTicketUpdateLogsRows(ticket.logs);
        resetTicketLogForm();
        $('#ticketUpdateLogsModal').modal('show');

        $.ajax({
            url: `/api/aftersales/tickets/${ticket.id}`,
            method: 'GET',
            success: function (response) {
                currentTicketPhotos = response.data?.photos || [];
                currentTicketSatisfaction = response.data?.satisfaction || null;
                renderTicketUpdateLogsRows(response.data?.logs || ticket.logs);
            }
        });
    }

    function submitTicketLog() {
        if (!currentUpdateLogsTicketId) return;

        const step = $('#ticketLogStep').val();
        const endpoint = STEP_ENDPOINT[step];
        let data = { note: $('#ticketLogNote').val() || null };
        let ajaxOptions = { data: data };

        if (step === 'satisfaction_submitted') {
            data = {};
            let missing = false;
            $('.ticket-log-satisfaction-field').each(function () {
                const value = $(this).val();
                if (!value) missing = true;
                data[$(this).data('field')] = value;
            });

            if (missing) {
                Swal.fire({
                    title: 'Data incomplete',
                    text: 'Please rate all five satisfaction criteria.',
                    icon: 'warning',
                    confirmButtonColor: '#115640'
                });
                return;
            }

            ajaxOptions = { data: data };
        }

        if (step === 'documentation_published') {
            const formData = new FormData();
            formData.append('note', $('#ticketLogNote').val() || '');

            let photoIndex = 0;
            $('#ticketLogPhotoRows [data-photo-row]').each(function () {
                const file = $(this).find('.ticket-log-photo-file')[0].files[0];
                if (!file) return;
                formData.append(`photos[${photoIndex}][category]`, $(this).find('.ticket-log-photo-category').val());
                formData.append(`photos[${photoIndex}][file]`, file);
                photoIndex++;
            });

            ajaxOptions = { data: formData, processData: false, contentType: false };
        }

        $.ajax({
            url: `/api/aftersales/tickets/${currentUpdateLogsTicketId}/${endpoint}`,
            method: 'POST',
            ...ajaxOptions,
            success: function () {
                $.ajax({
                    url: `/api/aftersales/tickets/${currentUpdateLogsTicketId}`,
                    method: 'GET',
                    success: function (response) {
                        currentTicketPhotos = response.data?.photos || [];
                        currentTicketSatisfaction = response.data?.satisfaction || null;
                        renderTicketUpdateLogsRows(response.data?.logs);
                    }
                });
                resetTicketLogForm();
                loadTickets();
            },
            error: function (xhr) {
                Swal.fire({
                    title: 'Failed to add activity',
                    text: xhr.responseJSON?.message || 'An error occurred, please try again.',
                    icon: 'error',
                    confirmButtonColor: '#115640'
                });
            }
        });
    }

    function openTicketDetail(ticket) {
        const customer = ticket.customer || {};
        const machine = ticket.customer_product || {};

        $('#ticketDetailCode').text(ticket.ticket_code || '-');
        $('#ticketDetailSubtitle').text(`${customer.name || '-'} · ${machine.product?.name || '-'}`);
        $('#ticketDetailDescription').text(ticket.description || '-');
        $('#ticketDetailCategory').text(CATEGORY_LABEL[ticket.category] || ticket.category || '-');
        $('#ticketDetailPriority').html(priorityBadge(ticket.priority));

        $('#ticketDetailModal').modal('show');
    }

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

        $('#ticketLogStep').on('change', function () {
            const step = $(this).val();
            const isSatisfaction = step === 'satisfaction_submitted';
            const isDocumentation = step === 'documentation_published';

            $('#ticketLogSatisfactionFields').toggleClass('hidden', !isSatisfaction);
            $('#ticketLogNote').toggleClass('hidden', isSatisfaction);
            $('#ticketLogPhotoFields').toggleClass('hidden', !isDocumentation);

            if (isDocumentation && $('#ticketLogPhotoRows [data-photo-row]').length === 0) {
                addTicketLogPhotoRow();
            }
        });

        $('#btn-add-ticket-log-photo').on('click', addTicketLogPhotoRow);

        $(document).on('click', '.btn-remove-ticket-log-photo', function () {
            $(this).closest('[data-photo-row]').remove();
        });

        $('#btn-add-ticket-log').on('click', submitTicketLog);

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