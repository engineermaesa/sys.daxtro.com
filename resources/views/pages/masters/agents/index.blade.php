@extends('layouts.app')

@section('styles')
<style>
    .agent-filter-select {
        border: 0 !important;
        box-shadow: none !important;
        outline: none !important;
        background: transparent !important;
    }

    .agent-filter-select + .select2-container .select2-selection {
        border: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
    }

    .agent-filter-select + .select2-container .select2-selection__rendered {
        color: #1E1E1E !important;
        font-weight: 600;
        text-align: center;
    }

    .agent-filter-select + .select2-container .select2-selection__arrow {
        right: 2px;
    }

    #source-date-range {
        border: 0 !important;
        box-shadow: none !important;
        outline: none !important;
        background: transparent !important;
        color: #1E1E1E;
        font-weight: 600;
        text-align: center;
        cursor: pointer;
    }

    .nav-leads {
        border-bottom: 2px solid transparent;
    }

    .nav-leads.active-nav {
        background-color: #E7F3EE;
    }

    .nav-leads.active-nav p {
        color: #115640 !important;
        font-weight: 600;
    }
</style>
@endsection

@section('content')

    <section class="min-h-screen text-xs! lg:text-sm! text-[#1E1E1E]">
        {{-- HEADER PAGES --}}
        <div class="pt-4">
            <div class="flex items-center gap-3">
                <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12ZM2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V4C0 3.45 0.195833 2.97917 0.5875 2.5875C0.979167 2.19583 1.45 2 2 2H3V1C3 0.716667 3.09583 0.479167 3.2875 0.2875C3.47917 0.0958333 3.71667 0 4 0C4.28333 0 4.52083 0.0958333 4.7125 0.2875C4.90417 0.479167 5 0.716667 5 1V2H13V1C13 0.716667 13.0958 0.479167 13.2875 0.2875C13.4792 0.0958333 13.7167 0 14 0C14.2833 0 14.5208 0.0958333 14.7125 0.2875C14.9042 0.479167 15 0.716667 15 1V2H16C16.55 2 17.0208 2.19583 17.4125 2.5875C17.8042 2.97917 18 3.45 18 4V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2Z"
                        fill="#115640" />
                </svg>
                <h1 class="text-[#115640] font-semibold text-lg lg:text-2xl">Agents</h1>
            </div>
            <p class="mt-1 text-[#115640] text-sm lg:text-lg">All Agents</p>
        </div>

        {{-- TABLES CONTENTS --}}
        <div class="mt-4 rounded-lg">
            {{-- NAVIGATION TABLES --}}
            <div
                class="bg-white lg:flex justify-between items-center border-b border-[#D9D9D9] p-3 gap-3 rounded-tr-lg rounded-tl-lg sm:gap-3 grid grid-cols-1">

                {{-- SEARCH TABLES --}}
                <div class="xl:w-[10%]! border border-gray-300 rounded-lg lg:flex! items-center p-2 hidden">
                    <div class="px-2">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M6.5 13C4.68333 13 3.14583 12.3708 1.8875 11.1125C0.629167 9.85417 0 8.31667 0 6.5C0 4.68333 0.629167 3.14583 1.8875 1.8875C3.14583 0.629167 4.68333 0 6.5 0C8.31667 0 9.85417 0.629167 11.1125 1.8875C12.3708 3.14583 13 4.68333 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.8125 10.5625 9.6875 9.6875C10.5625 8.8125 11 7.75 11 6.5C11 5.25 10.5625 4.1875 9.6875 3.3125C8.8125 2.4375 7.75 2 6.5 2C5.25 2 4.1875 2.4375 3.3125 3.3125C2.4375 4.1875 2 5.25 2 6.5C2 7.75 2.4375 8.8125 3.3125 9.6875C4.1875 10.5625 5.25 11 6.5 11Z"
                                fill="#6B7786" />
                        </svg>
                    </div>
                    <input id="searchInput" type="text" placeholder="Search"
                        class="w-full px-3 border-none focus:outline-none" />
                </div>

                {{-- NAVIGATION STATUS TABLES --}}
                <div class="xl:w-[80%]! gap-3 flex items-center">
                    {{-- FILTER BRANCH AGENTS DATE --}}
                    <div class="w-full grid grid-cols-5 bg-white border border-[#D9D9D9] rounded-lg">

                        {{-- BRANCH --}}
                        <div class="flex items-center justify-center gap-2 border-r border-r-[#CFD5DC] cursor-pointer h-full px-2 text-[#1E1E1E]">
                            <select id="branchesQuery"
                            class="agent-filter-select w-full font-semibold text-center focus:outline-none cursor-pointer">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- REGIONS --}}
                        <div class="flex items-center justify-center gap-2 border-r border-r-[#CFD5DC] cursor-pointer h-full px-2 text-[#1E1E1E]">
                            <select id="regionsQuery"
                            class="agent-filter-select w-full font-semibold text-center focus:outline-none cursor-pointer">
                                <option value="">All Regions</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}">{{ $region->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- PROVINCES --}}
                        <div class="flex items-center justify-center gap-2 border-r border-r-[#CFD5DC] cursor-pointer h-full px-2 text-[#1E1E1E]">
                            <select id="provincesQuery"
                            class="agent-filter-select w-full font-semibold text-center focus:outline-none cursor-pointer">
                                <option value="">All Provinces</option>
                                @foreach($provinces as $province)
                                    <option value="{{ $province->name }}">{{ $province->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- DATES --}}
                        <div class="border-r border-r-[#CFD5DC] cursor-pointer w-full relative grid grid-cols-1 items-center h-full">
                            {{-- TOGGLE --}}
                            <div id="openDateDropdown" class="flex justify-center items-center gap-2">
                                <p id="dateLabel" class="font-medium text-black">Date</p>
                                <i id="iconDate" class="fas fa-chevron-down transition-transform duration-300 text-black" style="font-size: 12px;"></i>
                            </div>

                            {{-- DATE DROPDOWN --}}
                            <div id="dateDropdown"
                                class="absolute top-full left-0 mt-2 bg-white rounded-lg shadow-xl w-[350px] p-4 z-50 opacity-0 scale-95 pointer-events-none transition-all duration-200 ease-out origin-top overflow-visible">

                                <h3 class="font-semibold mb-2">Select Date Range</h3>

                                <div class="flex justify-center items-center">
                                    <input type="text" id="source-date-range" class="shadow-none w-full" placeholder="Select date range">
                                </div>

                                <div class="flex justify-end gap-2 mt-3">
                                    <button id="cancelDate" class="px-3 py-1 text-[#303030]">
                                        Cancel
                                    </button>

                                    <button id="applyDate"
                                        class="px-3 py-1 bg-[#115640] text-white rounded-lg cursor-pointer">
                                        Apply
                                    </button>
                                </div>
                            </div>
                        </div>  

                        {{-- RESET FILTERS --}}
                        <div id="generalFilterReset" class="flex items-center justify-center gap-2 cursor-pointer h-full">
                            <i id="resetQuery" class="fa fa-redo transition-transform duration-300 text-[#900B09] -scale-x-100   " style="font-size: 12px;"></i>
                            <p class="font-medium text-[#900B09]">Reset Filter</p>
                        </div>
                    </div>
                </div>

                {{-- ADD AGENTS --}}
                <div class="bg-[#115640] rounded-lg hidden lg:flex! h-full">
                    <a href="{{ route('masters.agents.form') }}"
                        class="w-full h-full flex justify-center items-center gap-1 p-2 xl:gap-3 xl:px-3 xl:py-2">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M6 8H1C0.716667 8 0.479167 7.90417 0.2875 7.7125C0.0958333 7.52083 0 7.28333 0 7C0 6.71667 0.0958333 6.47917 0.2875 6.2875C0.479167 6.09583 0.716667 6 1 6H6V1C6 0.716667 6.09583 0.479167 6.2875 0.2875C6.47917 0.0958333 6.71667 0 7 0C7.28333 0 7.52083 0.0958333 7.7125 0.2875C7.90417 0.479167 8 0.716667 8 1V6H13C13.2833 6 13.5208 6.09583 13.7125 6.2875C13.9042 6.47917 14 6.71667 14 7C14 7.28333 13.9042 7.52083 13.7125 7.7125C13.5208 7.90417 13.2833 8 13 8H8V13C8 13.2833 7.90417 13.5208 7.7125 13.7125C7.52083 13.9042 7.28333 14 7 14C6.71667 14 6.47917 13.9042 6.2875 13.7125C6.09583 13.5208 6 13.2833 6 13V8Z"
                                fill="#E7F3EE" />
                        </svg>
                        <p class="text-white font-medium">Add Agents</p>
                    </a>
                </div>
            </div>

            <div class="w-full border-b border-b-[#D9D9D9] bg-white rounded-lg grid grid-cols-3">
                @foreach (['all', 'active', 'inactive'] as $tab)
                {{-- NAVIGATION STATUS --}}
                <div data-tab="{{ $tab }}"
                    class="text-center cursor-pointer py-2 h-full w-full border-r border-r-[#D5D5D5] nav-leads">
                    <p class="text-[#083224]">
                        {{ $loop->first ? 'All Status' : ucfirst($tab) }}
                        <span data-manage-tab-count="{{ $tab }}" class="{{ 
                            $tab === 'all' 
                                ? 'span-all' 
                                : ($tab === 'active' 
                                    ? 'span-deal' 
                                    : 'span-warm'
                                )
                            }}">
                        </span>
                    </p>
                </div>
                @endforeach
            </div>

            {{-- CONTENTS TABLE --}}
            @foreach(['all', 'active', 'inactive'] as $tab)
                <div data-status-wrapper="{{ $tab }}" class="leads-table-container {{ $loop->first ? '' : 'hidden' }}">
                    <div class="max-xl:overflow-x-scroll">
                        <table id="{{ $tab }}LeadsTableNew" class="w-full bg-white rounded-br-lg rounded-bl-lg">
                            {{-- HEADER TABLE --}}
                            <thead class="text-[#1E1E1E]">
                                <tr class="border-b border-b-[#D9D9D9]">
                                    <th class="p-1 lg:p-3">Name</th>
                                    <th class="p-1 lg:p-3">Phone</th>
                                    <th class="p-1 lg:p-3">Email</th>
                                    <th class="p-1 lg:p-3">Branch</th>
                                    <th class="p-1 lg:p-3">Region</th>
                                    <th class="p-1 lg:p-3">Province</th>
                                    <th class="p-1 lg:p-3">Company Name</th>
                                    <th class="p-1 lg:p-3">Company Address</th>
                                    <th class="p-1 lg:p-3">Source</th>
                                    <th class="p-1 lg:p-3">Created At</th>
    
                                    @if ($tab === 'all')
                                    <th class="p-1 lg:p-3">Status</th>
                                    @endif
                                    
                                    <th class="p-1 lg:p-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="{{ $tab }}BodyTable"></tbody>
                        </table>
                    </div>
    
                    {{-- NAVIGATION ROWS --}}
                    <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! bg-transparent">
                        <div class="flex items-center gap-3">
                            <p class="font-semibold">Show Rows</p>
                            <select id="{{ $tab }}PageSizeSelect" class="w-auto bg-white font-semibold p-2 rounded-md"
                                onchange="changePageSize('{{ $tab }}', this.value)">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
    
                        <div class="flex items-center gap-2">
                            <div id="{{ $tab }}Showing" class="font-semibold">Showing 0-0 of 0</div>
                            <div>
                                <button id="{{ $tab }}PrevBtn"
                                    class="btn btn bg-white border! border-[#D9D9D9]! cursor-pointer!"
                                    onclick="goPrev('{{ $tab }}')">
                                    <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
                                </button>
                                <button id="{{ $tab }}NextBtn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!"
                                    onclick="goNext('{{ $tab }}')">
                                    <i class="fas fa-chevron-right text-black" style="font-size: 12px;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

@endsection

@section('scripts')
<script>
    const agentState = {
        tab: 'all',
        page: {
            all: 1,
            active: 1,
            inactive: 1
        },
        lastPage: {
            all: 1,
            active: 1,
            inactive: 1
        },
        perPage: {
            all: 10,
            active: 10,
            inactive: 10
        },
        search: '',
        branch_id: '',
        region_id: '',
        province: '',
        start_date: '',
        end_date: ''
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function resetAgentPages() {
        agentState.page = {
            all: 1,
            active: 1,
            inactive: 1
        };
    }

    function statusBadge(row) {
        const isActive = Number(row.is_active) === 1;
        const classes = isActive
            ? 'status-deal'
            : 'status-warm';

        return `<span class="inline-block px-2 py-1 rounded ${classes}">${escapeHtml(row.status_name || '-')}</span>`;
    }

    function renderAgents(tab, rows) {
        const withStatus = tab === 'all';
        const colspan = withStatus ? 12 : 11;
        const $body = $(`#${tab}BodyTable`);

        if (!rows || rows.length === 0) {
            $body.html(`<tr><td colspan="${colspan}" class="text-center p-3">No agents found</td></tr>`);
            return;
        }

        const html = rows.map(row => `
            <tr class="border-t border-t-[#D9D9D9]">
                <td class="p-1 lg:p-3">${escapeHtml(row.name || '-')}</td>
                <td class="p-1 lg:p-3">${escapeHtml(row.phone || '-')}</td>
                <td class="p-1 lg:p-3">${escapeHtml(row.email || '-')}</td>
                <td class="p-1 lg:p-3">${escapeHtml(row.branch_name || '-')}</td>
                <td class="p-1 lg:p-3">${escapeHtml(row.region_name || '-')}</td>
                <td class="p-1 lg:p-3">${escapeHtml(row.province || '-')}</td>
                <td class="p-1 lg:p-3">${escapeHtml(row.company_name || '-')}</td>
                <td class="p-1 lg:p-3">${escapeHtml(row.company_address || '-')}</td>
                <td class="p-1 lg:p-3">${escapeHtml(row.source_name || '-')}</td>
                <td class="p-1 lg:p-3">${escapeHtml(row.created_at || '-')}</td>
                ${withStatus ? `<td class="p-1 lg:p-3">${statusBadge(row)}</td>` : ''}
                <td class="p-1 lg:p-3 text-center">${row.actions || ''}</td>
            </tr>
        `).join('');

        $body.html(html);
    }

    function renderTableLoading(tab) {
        const colspan = tab === 'all' ? 12 : 11;

        $(`#${tab}BodyTable`).html(`
            <tr>
                <td colspan="${colspan}" class="text-center p-4 text-[#6B7786] font-medium">
                    Loading data...
                </td>
            </tr>
        `);
    }

    function renderTableError(tab, message) {
        const colspan = tab === 'all' ? 12 : 11;

        $(`#${tab}BodyTable`).html(`
            <tr>
                <td colspan="${colspan}" class="text-center p-4 text-[#900B09] font-medium">
                    ${escapeHtml(message)}
                </td>
            </tr>
        `);
    }

    function renderCounts(counts) {
        counts = counts || {};
        $('[data-manage-tab-count="all"]').text(`(${counts.all || 0})`);
        $('[data-manage-tab-count="active"]').text(`(${counts.active || 0})`);
        $('[data-manage-tab-count="inactive"]').text(`(${counts.inactive || 0})`);
    }

    function renderPagination(tab, pagination) {
        pagination = pagination || {};
        agentState.lastPage[tab] = pagination.last_page || 1;

        $(`#${tab}Showing`).text(`Showing ${pagination.from || 0}-${pagination.to || 0} of ${pagination.total || 0}`);
        $(`#${tab}PrevBtn`).prop('disabled', (pagination.current_page || 1) <= 1);
        $(`#${tab}NextBtn`).prop('disabled', (pagination.current_page || 1) >= (pagination.last_page || 1));
    }

    function loadAgents(tab = agentState.tab) {
        $.ajax({
            url: '{{ url('/api/masters/agents/list') }}',
            method: 'GET',
            data: {
                status: tab,
                page: agentState.page[tab],
                per_page: agentState.perPage[tab],
                search: agentState.search,
                branch_id: agentState.branch_id,
                region_id: agentState.region_id,
                province: agentState.province,
                start_date: agentState.start_date,
                end_date: agentState.end_date
            },
            beforeSend: function () {
                renderTableLoading(tab);
            },
            success: function (response) {
                renderAgents(tab, response.data || []);
                renderCounts(response.counts || {});
                renderPagination(tab, response.pagination || {});
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Failed to load agents';

                renderTableError(tab, message);
                notif(message, 'error');
            }
        });
    }

    function changePageSize(tab, value) {
        agentState.perPage[tab] = Number(value || 10);
        agentState.page[tab] = 1;
        loadAgents(tab);
    }

    function goPrev(tab) {
        if (agentState.page[tab] > 1) {
            agentState.page[tab]--;
            loadAgents(tab);
        }
    }

    function goNext(tab) {
        if (agentState.page[tab] < agentState.lastPage[tab]) {
            agentState.page[tab]++;
            loadAgents(tab);
        }
    }

    function closeDateDropdown() {
        $('#dateDropdown').addClass('opacity-0 scale-95 pointer-events-none');
        $('#iconDate').removeClass('rotate-180');
    }

    function toggleDateDropdown() {
        $('#dateDropdown').toggleClass('opacity-0 scale-95 pointer-events-none');
        $('#iconDate').toggleClass('rotate-180');
    }

    $(function () {
        let searchTimer = null;
        const datePicker = flatpickr('#source-date-range', {
            mode: 'range',
            inline: true,
            dateFormat: 'Y-m-d',
            onClose: function () {}
        });

        $('#branchesQuery, #regionsQuery, #provincesQuery').select2({
            width: '100%',
            minimumResultsForSearch: 5
        });

        $(document).on('click', '.nav-leads', function () {
            const tab = $(this).data('tab');

            agentState.tab = tab;
            $('[data-status-wrapper]').addClass('hidden');
            $(`[data-status-wrapper="${tab}"]`).removeClass('hidden');
            $('.nav-leads').removeClass('active-nav');
            $(this).addClass('active-nav');

            loadAgents(tab);
        });

        $('#searchInput').on('input', function () {
            clearTimeout(searchTimer);

            searchTimer = setTimeout(() => {
                agentState.search = $(this).val();
                resetAgentPages();
                loadAgents();
            }, 400);
        });

        $('#branchesQuery').on('change', function () {
            agentState.branch_id = $(this).val();
            resetAgentPages();
            loadAgents();
        });

        $('#regionsQuery').on('change', function () {
            agentState.region_id = $(this).val();
            resetAgentPages();
            loadAgents();
        });

        $('#provincesQuery').on('change', function () {
            agentState.province = $(this).val();
            resetAgentPages();
            loadAgents();
        });

        $('#openDateDropdown').on('click', function () {
            toggleDateDropdown();
        });

        $('#cancelDate').on('click', function (e) {
            e.preventDefault();
            closeDateDropdown();
        });

        $('#applyDate').on('click', function (e) {
            e.preventDefault();

            const dates = datePicker.selectedDates || [];
            if (dates.length !== 2) {
                return;
            }

            agentState.start_date = datePicker.formatDate(dates[0], 'Y-m-d');
            agentState.end_date = datePicker.formatDate(dates[1], 'Y-m-d');

            $('#dateLabel').text(`${agentState.start_date} -> ${agentState.end_date}`);
            resetAgentPages();
            loadAgents();
            closeDateDropdown();
        });

        $('#generalFilterReset').on('click', function () {
            agentState.search = '';
            agentState.branch_id = '';
            agentState.region_id = '';
            agentState.province = '';
            agentState.start_date = '';
            agentState.end_date = '';
            resetAgentPages();

            $('#searchInput').val('');
            $('#branchesQuery, #regionsQuery, #provincesQuery').val('').trigger('change.select2');
            datePicker.clear();
            $('#dateLabel').text('Date');
            closeDateDropdown();

            loadAgents();
        });

        $('.nav-leads[data-tab="all"]').addClass('active-nav');
        loadAgents();
    });
</script>
@endsection
