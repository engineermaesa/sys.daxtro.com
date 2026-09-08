@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
    <div class="pt-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-[#115640] font-semibold text-2xl">Technician</h1>
                <p class="text-[#757575] mt-1">Manage technician profiles, skills, and assignments.</p>
            </div>
            <a href="{{ route('aftersales.pages.technicians.create') }}" class="bg-[#115640] text-white px-4 py-2 rounded-lg hover:bg-[#0d4633] transition-colors flex items-center gap-2">
                <i class="bi bi-plus-lg"></i> Add Technician
            </a>
        </div>

        {{--SEARCH--}}
        <div class="bg-white rounded-lg border border-[#D9D9D9] p-4 mt-4">
            <div class="flex items-stretch gap-3">
                <div class="flex-1 relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-[#757575] text-base pointer-events-none"></i>
                    <input type="text" id="technician-search" placeholder="Search technician name"
                    class="w-full pl-10 pr-3 py-2 border border-[#D9D9D9] rounded-lg! text-left text-[#1E1E1E] focus:outline-none!">
                </div>
                <button type="button" id="btn-search-technician"
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
                        <th class="p-3 text-left uppercase text-xs">Technician Name</th>
                        <th class="p-3 text-left uppercase text-xs">Level</th>
                        <th class="p-3 text-left uppercase text-xs">Refrigeration</th>
                        <th class="p-3 text-left uppercase text-xs">Electrical</th>
                        <th class="p-3 text-left uppercase text-xs">PLC Control</th>
                        <th class="p-3 text-left uppercase text-xs">Water Treatment</th>
                        <th class="p-3 text-left uppercase text-xs">Cold Storage</th>
                        <th class="p-3 text-left uppercase text-xs">Area Coverage</th>
                        <th class="p-3 text-left uppercase text-xs">Certification</th>
                        <th class="p-3 text-left uppercase text-xs">Status</th>
                        <th class="p-3 text-left uppercase text-xs">Total Ticket</th>
                        <th class="p-3 text-left uppercase text-xs">Action</th>
                    </tr>
                </thead>
                <tbody id="technician-table-body" class="text-left"></tbody>
            </table>
        </div>

        {{--PAGINATION--}}
        <div class="flex items-center justify-between mt-4">
            <div id="technician-showing" class="text-[#757575]">Showing 0 to 0 entries</div>
            <div id="technician-pagination" class="flex items-center gap-2"></div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    const technicianState = {
        page: 1,
        lastPage: 1,
        perPage: 10,
        search: '',
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

    function renderTechnicianRows(rows) {
        const $body = $('#technician-table-body');

        if (!rows || rows.length === 0) {
            $body.html('<tr><td colspan="12" class="text-center p-4 text-[#757575]">No technicians found</td></tr>');
            return;
        }

        const html = rows.map(row => {
            const user = row.user || {};
            const region = row.region || {};
            const skillCells = SKILL_COLUMNS.map(col => `<td class="p-3">${skillCell(row.skills, col.key)}</td>`).join('');

            return `
            <tr class="border-t border-t-[#D9D9D9] align-top" data-technician-id="${row.id}">
                <td class="p-3 font-semibold text-[#115640]">${escapeHtml(user.name)}</td>
                <td class="p-3">${levelBadge(user.grade)}</td>
                ${skillCells}
                <td class="p-3">${escapeHtml(region.name || '-')}</td>
                <td class="p-3">${escapeHtml(row.certification || '—')}</td>
                <td class="p-3">${statusBadge(row.status)}</td>
                <td class="p-3">${row.total_tickets ?? 0}</td>
                <td class="p-3">
                    <div class="dropdown">
                        <button class="btn-technician-action w-8 h-8 rounded-lg border border-[#D9D9D9] bg-white cursor-pointer" type="button" data-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right">
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="/aftersales/technicians/${row.id}/work-orders">
                                    <i class="bi bi-copy"></i> Work Order
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
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
        $('#technician-table-body').html('<tr><td colspan="12" class="text-center p-4 text-[#757575]">Loading...</td></tr>');

        $.ajax({
            url: '/api/aftersales/technicians',
            method: 'GET',
            data: {
                page: technicianState.page,
                per_page: technicianState.perPage,
                search: technicianState.search,
            },
            success: function (response) {
                renderTechnicianRows(response.data);
                renderTechnicianPagination(response);
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Failed to load technicians';
                $('#technician-table-body').html(`<tr><td colspan="12" class="text-center p-4 text-[#900B09]">${escapeHtml(message)}</td></tr>`);
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

        $(document).on('click', '#technician-pagination button', function () {
            const page = Number($(this).data('page'));
            if (!page || page < 1 || page > technicianState.lastPage) return;
            technicianState.page = page;
            loadTechnicians();
        });

    });
</script>
@endsection
