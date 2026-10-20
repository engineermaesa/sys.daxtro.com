{{-- TICKETS (isi work order lama): data asli dari GET /api/aftersales/tickets --}}
<div class="bg-white rounded-lg border border-[#D9D9D9] p-6 mt-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h2 class="font-semibold text-lg">Tickets</h2>
        <div class="flex items-center gap-2">
            <input id="work-order-search" type="text" placeholder="Search ticket code, customer"
                class="border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs w-56">
            <select id="work-order-priority" class="border border-[#D9D9D9] rounded-lg px-3 py-2 text-xs bg-white cursor-pointer">
                <option value="">All priorities</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto mt-4">
        <table class="w-full">
            <thead class="text-[#1E1E1E]">
                <tr>
                    <th style="background-color: #F3F4F6;" class="p-3 text-left font-semibold">Ticket</th>
                    <th style="background-color: #F3F4F6;" class="p-3 text-center font-semibold">Customer</th>
                    <th style="background-color: #F3F4F6;" class="p-3 text-center font-semibold">Priority</th>
                    <th style="background-color: #F3F4F6;" class="p-3 text-center font-semibold">Steps</th>
                    <th style="background-color: #F3F4F6;" class="p-3 text-center font-semibold">Visit Date</th>
                    <th style="background-color: #F3F4F6;" class="p-3 text-center font-semibold">Action</th>
                </tr>
            </thead>
            <tbody id="work-order-table-body" class="text-left"></tbody>
        </table>
    </div>

    <div class="flex items-center justify-between mt-4">
        <div id="work-order-showing" class="text-[#757575]">Showing 0 to 0 entries</div>
        <div id="work-order-pagination" class="flex items-center gap-2"></div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const state = {
            page: 1,
            lastPage: 1,
            perPage: 10,
            technicianUserId: Number('{{ $technician->user_id }}'),
            search: '',
            priority: '',
        };

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

        const STEP_LABEL = {
            published: 'Published',
            assigned: 'Assigned',
            on_site: 'On Site',
            repair: 'Repair',
            waiting_sparepart: 'Waiting Sparepart',
            documentation_published: 'Documentation Published',
            satisfaction_submitted: 'Satisfaction Submitted',
            closed: 'Closed',
            recoming: 'Recoming',
        };

        const PRIORITY_BADGE = {
            low: 'bg-[#F0F0F0] text-[#757575]',
            medium: 'bg-[#E7ECFB] text-[#3B4FA6]',
            high: 'bg-[#FFF3CD] text-[#8A6D00]',
        };

        // tanggal selalu WIB (kontrak: jangan pakai toISOString)
        function formatDate(value) {
            if (!value) return '—';
            return new Date(value).toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' });
        }

        function priorityBadge(priority) {
            const cls = PRIORITY_BADGE[priority] || PRIORITY_BADGE.low;
            return `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium ${cls}">${escapeHtml(priority ? priority.charAt(0).toUpperCase() + priority.slice(1) : '-')}</span>`;
        }

        function stepBadge(step, currentRecoming) {
            const percent = PROGRESS_PERCENT[step] ?? 0;
            let cls = 'bg-[#FBEAEA] text-[#900B09]';
            if (percent >= 100) cls = 'bg-[#E6F4EF] text-[#115640]';
            else if (percent >= 50) cls = 'bg-[#FFF3CD] text-[#8A6D00]';
            let label = STEP_LABEL[step] || step || '-';
            if (step === 'recoming' && currentRecoming) {
                label = `Recoming · visit ${currentRecoming.visit_no} · ${formatDate(currentRecoming.planned_visit_at)}`;
            }
            return `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-semibold ${cls}">${escapeHtml(label)}</span>`;
        }

        function renderRows(rows) {
            const $body = $('#work-order-table-body');

            if (!rows || rows.length === 0) {
                $body.html('<tr><td colspan="6" class="text-center p-4 text-[#757575]">No tickets found</td></tr>');
                return;
            }

            $body.html(rows.map(row => `
                <tr class="border-t border-t-[#D9D9D9]">
                    <td class="p-3 font-semibold text-[#115640]">${escapeHtml(row.ticket_code)}</td>
                    <td class="p-3 text-center">${escapeHtml((row.customer || {}).name)}</td>
                    <td class="p-3 text-center">${priorityBadge(row.priority)}</td>
                    <td class="p-3 text-center">${stepBadge(row.progress, row.current_recoming)}</td>
                    <td class="p-3 text-center">${formatDate(row.visit_date)}</td>
                    <td class="p-3 text-center">
                        <a href="/aftersales/tickets/${row.id}/work-order-pdf" target="_blank"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-[#115640] text-[#115640] text-xs font-semibold hover:bg-[#115640] hover:text-white transition-colors">
                            <i class="bi bi-file-earmark-pdf"></i> Export PDF
                        </a>
                    </td>
                </tr>
            `).join(''));
        }

        function renderPagination(pagination) {
            pagination = pagination || {};
            state.lastPage = pagination.last_page || 1;

            const from = pagination.total ? ((pagination.current_page - 1) * state.perPage) + 1 : 0;
            const to = Math.min(pagination.current_page * state.perPage, pagination.total || 0);
            $('#work-order-showing').text(`Showing ${from} to ${to} of ${pagination.total || 0} entries`);

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

            $('#work-order-pagination').html(buttons);
        }

        function load() {
            $('#work-order-table-body').html('<tr><td colspan="6" class="text-center p-4 text-[#757575]">Loading...</td></tr>');

            $.ajax({
                url: '/api/aftersales/tickets',
                method: 'GET',
                data: {
                    page: state.page,
                    per_page: state.perPage,
                    assigned_technician_id: state.technicianUserId,
                    search: state.search || undefined,
                    priority: state.priority || undefined,
                },
                success: function (response) {
                    renderRows(response.data);
                    renderPagination(response);
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to load tickets';
                    $('#work-order-table-body').html(`<tr><td colspan="6" class="text-center p-4 text-[#900B09]">${escapeHtml(message)}</td></tr>`);
                }
            });
        }

        $(function () {
            let searchTimer;
            $('#work-order-search').on('input', function () {
                clearTimeout(searchTimer);
                const value = this.value.trim();
                searchTimer = setTimeout(function () {
                    state.search = value;
                    state.page = 1;
                    load();
                }, 400);
            });
            $('#work-order-priority').on('change', function () {
                state.priority = this.value;
                state.page = 1;
                load();
            });
            $(document).on('click', '#work-order-pagination button', function () {
                const page = Number($(this).data('page'));
                if (!page || page < 1 || page > state.lastPage) return;
                state.page = page;
                load();
            });

            load();
        });
    })();
</script>
@endpush
