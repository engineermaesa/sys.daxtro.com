@extends('layouts.app')

@section('content')
<section class="min-h-screen min-w-0 sm:text-xs lg:text-sm">
    <div class="pt-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div class="min-w-0">
                <h1 class="text-[#115640] font-semibold text-2xl">Technician</h1>
                <p class="text-[#757575] mt-1">Manage technician profiles, skills, and assignments.</p>
            </div>
            <a href="{{ route('aftersales.pages.technicians.create') }}" class="self-start sm:self-auto shrink-0 whitespace-nowrap bg-[#115640] text-white px-4 py-2 rounded-lg hover:bg-[#0d4633] transition-colors flex items-center gap-2">
                <i class="bi bi-plus-lg"></i> Add Technician
            </a>
        </div>

        {{--SEARCH--}}
        <div class="bg-white rounded-lg border border-[#D9D9D9] p-4 mt-4">
            <div class="flex flex-col lg:flex-row lg:items-stretch gap-3">
                <div class="flex-1 relative min-w-0">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-[#757575] text-base pointer-events-none"></i>
                    <input type="text" id="technician-search" placeholder="Search technician name"
                    class="w-full pl-10 pr-3 py-2 border border-[#D9D9D9] rounded-lg! text-left text-[#1E1E1E] focus:outline-none!">
                </div>
                <select id="technician-period" class="w-full lg:w-auto border border-[#D9D9D9] rounded-lg px-3 py-2 bg-white cursor-pointer text-[#1E1E1E]">
                    <option value="all">All time</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="365">Last 12 months</option>
                </select>
                <button type="button" id="btn-search-technician"
                class="w-full lg:w-auto bg-[#115640] text-white px-6 py-2 rounded-lg hover:bg-[#0d4633] transition-colors">
                    Search
                </button>
            </div>
        </div>

        {{--TABLE--}}
        <div class="table-cards-wrap bg-white rounded-lg border border-[#D9D9D9] mt-4 overflow-x-auto">
            <table class="table-cards w-full">
                <thead class="text-[#1E1E1E]">
                    <tr class="border-b border-b-[#D9D9D9]">
                        <th class="p-3 text-center uppercase text-xs">Technician Name</th>
                        <th class="p-3 text-center uppercase text-xs">Level</th>
                        <th class="p-3 text-center uppercase text-xs">Refrigeration</th>
                        <th class="p-3 text-center uppercase text-xs">Electrical</th>
                        <th class="p-3 text-center uppercase text-xs">PLC Control</th>
                        <th class="p-3 text-center uppercase text-xs">Water Treatment</th>
                        <th class="p-3 text-center uppercase text-xs">Cold Storage</th>
                        <th class="p-3 text-center uppercase text-xs">Area Coverage</th>
                        <th class="p-3 text-center uppercase text-xs">Certification</th>
                        <th class="p-3 text-center uppercase text-xs">Status</th>
                        <th class="p-3 text-center uppercase text-xs">Total Ticket</th>
                        @if (hasPermission(auth()->user(), 'aftersales.tickets.manage'))
                        <th class="p-3 text-center uppercase text-xs">Rating</th>
                        <th class="p-3 text-center uppercase text-xs">First Visit Completion</th>
                        @endif
                        <th class="p-3 text-center uppercase text-xs">Action</th>
                    </tr>
                </thead>
                <tbody id="technician-table-body"></tbody>
            </table>
        </div>

        {{--PAGINATION--}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-4">
            <div id="technician-showing" class="text-[#757575]">Showing 0 to 0 entries</div>
            <div id="technician-pagination" class="flex flex-wrap items-center gap-2"></div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    // Rating & First-Visit Completion hanya untuk After Sales (FR-E05)
    const CAN_SEE_SCORE = @json(hasPermission(auth()->user(), 'aftersales.tickets.manage'));
    const TECHNICIAN_COLSPAN = CAN_SEE_SCORE ? 14 : 12;

    const technicianState = {
        page: 1,
        lastPage: 1,
        perPage: 10,
        search: '',
        period: 'all',
    };

    const SKILL_COLUMNS = [
        { key: 'refrigeration', label: 'Refrigeration' },
        { key: 'electrical', label: 'Electrical' },
        { key: 'plc control', label: 'PLC Control' },
        { key: 'water treatment', label: 'Water Treatment' },
        { key: 'cold storage', label: 'Cold Storage' },
    ];

    const LEVEL_LABEL = {
        basic: 'Basic',
        intermediate: 'Intermediate',
        expert: 'Expert',
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function levelBadge(grade) {
        if (!grade) return '<span class="text-[#B0B0B0]">-</span>';
        return `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium bg-[#F0F0F0] text-[#1E1E1E]">${escapeHtml(grade)}</span>`;
    }

    function skillCell(skills, skillKey) {
        const found = (skills || []).find(s => String(s.skill_name || '').toLowerCase() === skillKey);
        if (!found) {
            return '<span class="text-[#B0B0B0]">None</span>';
        }
        return `<span class="text-[#1E1E1E]">${escapeHtml(LEVEL_LABEL[found.level] || found.level)}</span>`;
    }

    function statusBadge(status) {
        const isOnDuty = status === 'On Duty';
        const cls = isOnDuty ? 'bg-[#E6F4EF] text-[#115640]' : 'bg-[#F0F0F0] text-[#757575]';
        return `<span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium ${cls}">${escapeHtml(status || '-')}</span>`;
    }

    function scoreCells(row) {
        if (!CAN_SEE_SCORE) return '';
        // 3-level scale: the API sends rating_scale_max = 3, so ratings read as x/3
        const rating = row.rating === null || row.rating === undefined ? '—' : `${row.rating}/${row.rating_scale_max || 3} (${row.rating_count})`;
        const completion = row.completion === null || row.completion === undefined
            ? '—'
            : `${row.completion}% (${row.completion_closed}/${row.completion_total})`;
        return `<td class="p-3 text-center" data-label="Rating">${rating}</td><td class="p-3 text-center" data-label="First Visit Completion">${completion}</td>`;
    }

    function renderTechnicianRows(rows) {
        const $body = $('#technician-table-body');

        if (!rows || rows.length === 0) {
            $body.html(`<tr><td colspan="${TECHNICIAN_COLSPAN}" class="text-center p-4 text-[#757575]">No technicians found</td></tr>`);
            return;
        }

        const html = rows.map(row => {
            const user = row.user || {};
            const region = row.region || {};
            const skillCells = SKILL_COLUMNS.map(col => `<td class="p-3 text-center" data-label="${col.label}" data-secondary>${skillCell(row.skills, col.key)}</td>`).join('');

            return `
            <tr class="border-t border-t-[#D9D9D9] align-top" data-technician-id="${row.id}">
                <td class="p-3 font-semibold text-[#115640] text-left" data-primary data-label="Technician Name">${escapeHtml(user.name)}</td>
                <td class="p-3 text-center" data-label="Level">${levelBadge(user.grade)}</td>
                ${skillCells}
                <td class="p-3 text-center" data-label="Area Coverage" data-secondary>${escapeHtml(region.name || '-')}</td>
                <td class="p-3 text-center" data-label="Certification" data-secondary>${escapeHtml(row.certification || '—')}</td>
                <td class="p-3 text-center" data-label="Status">${statusBadge(row.status)}</td>
                <td class="p-3 text-center" data-label="Total Ticket">${row.total_tickets ?? 0}</td>
                ${scoreCells(row)}
                <td class="p-3 text-center" data-actions data-label="Action">
                    <div class="dropdown">
                        <button class="btn-technician-action w-8 h-8 rounded-lg border border-[#D9D9D9] bg-white cursor-pointer" type="button" data-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right">
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="/aftersales/technicians/${row.id}/details">
                                    <i class="bi bi-eye"></i> See Detail
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
                <td class="card-more-cell"><button type="button" class="card-more">Show more</button></td>
            </tr>
        `;
        }).join('');

        $body.html(html);
    }

    function renderTechnicianPagination(pagination) {
        pagination = pagination || {};
        technicianState.lastPage = pagination.last_page || 1;

        const from = pagination.total ? ((pagination.current_page - 1) * technicianState.perPage) + 1 : 0;
        const to = Math.min(pagination.current_page * technicianState.perPage, pagination.total || 0);
        $('#technician-showing').text(`Showing ${from} to ${to} of ${pagination.total || 0} entries`);

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

        $('#technician-pagination').html(buttons);
    }

    function loadTechnicians() {
        $('#technician-table-body').html(`<tr><td colspan="${TECHNICIAN_COLSPAN}" class="text-center p-4 text-[#757575]">Loading...</td></tr>`);

        $.ajax({
            url: '/api/aftersales/technicians',
            method: 'GET',
            data: {
                page: technicianState.page,
                per_page: technicianState.perPage,
                search: technicianState.search,
                period: technicianState.period,
            },
            success: function (response) {
                renderTechnicianRows(response.data);
                renderTechnicianPagination(response);
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Failed to load technicians';
                $('#technician-table-body').html(`<tr><td colspan="${TECHNICIAN_COLSPAN}" class="text-center p-4 text-[#900B09]">${escapeHtml(message)}</td></tr>`);
            }
        });
    }

    $(function () {
        loadTechnicians();

        let searchTimer = null;
        $('#technician-search').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                technicianState.search = $('#technician-search').val();
                technicianState.page = 1;
                loadTechnicians();
            }, 400);
        });

        $('#btn-search-technician').on('click', function () {
            technicianState.search = $('#technician-search').val();
            technicianState.page = 1;
            loadTechnicians();
        });

        $('#technician-period').on('change', function () {
            technicianState.period = this.value;
            technicianState.page = 1;
            loadTechnicians();
        });

        // mobile card view: expand / collapse the secondary fields of one technician
        $(document).on('click', '#technician-table-body .card-more', function () {
            const $row = $(this).closest('tr').toggleClass('is-open');
            $(this).text($row.hasClass('is-open') ? 'Show less' : 'Show more');
        });

        $(document).on('click', '#technician-pagination button', function () {
            const page = Number($(this).data('page'));
            if (!page || page < 1 || page > technicianState.lastPage) return;
            technicianState.page = page;
            loadTechnicians();
        });

    });
</script>
@endsection
