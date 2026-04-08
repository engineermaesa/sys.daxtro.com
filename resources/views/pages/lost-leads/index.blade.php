@extends('layouts.app')

@section('content')
    <section class="min-h-screen sm:text-xs! lg:text-sm!">
        {{-- HEADER PAGES --}}
        <div class="pt-4">
            <div class="flex items-center gap-3">        
                <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12ZM2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V4C0 3.45 0.195833 2.97917 0.5875 2.5875C0.979167 2.19583 1.45 2 2 2H3V1C3 0.716667 3.09583 0.479167 3.2875 0.2875C3.47917 0.0958333 3.71667 0 4 0C4.28333 0 4.52083 0.0958333 4.7125 0.2875C4.90417 0.479167 5 0.716667 5 1V2H13V1C13 0.716667 13.0958 0.479167 13.2875 0.2875C13.4792 0.0958333 13.7167 0 14 0C14.2833 0 14.5208 0.0958333 14.7125 0.2875C14.9042 0.479167 15 0.716667 15 1V2H16C16.55 2 17.0208 2.19583 17.4125 2.5875C17.8042 2.97917 18 3.45 18 4V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2Z" fill="#115640"/>
                </svg>
                <h1 class="text-[#115640] font-semibold lg:text-2xl text-lg">Leads</h1>
            </div>
            <p class="mt-1 text-[#115640] lg:text-lg text-sm">Lost Leads</p>
        </div>

        <div class="mt-4 bg-white rounded-lg border-r border-l border-t border-[#D9D9D9]">
            {{-- NAVIGATION TABLES --}}
            <div class="bg-white lg:grid-cols-[1fr_6fr] justify-between items-center border-b border-[#D9D9D9] p-3 gap-4 rounded-tr-lg rounded-tl-lg sm:gap-3 grid grid-cols-1">
                {{-- SEARCH TABLES --}}
                <div class="w-full border border-[#D5D5D5] rounded-lg flex items-center p-2">
                    <div class="px-2">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.5 13C4.68333 13 3.14583 12.3708 1.8875 11.1125C0.629167 9.85417 0 8.31667 0 6.5C0 4.68333 0.629167 3.14583 1.8875 1.8875C3.14583 0.629167 4.68333 0 6.5 0C8.31667 0 9.85417 0.629167 11.1125 1.8875C12.3708 3.14583 13 4.68333 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.8125 10.5625 9.6875 9.6875C10.5625 8.8125 11 7.75 11 6.5C11 5.25 10.5625 4.1875 9.6875 3.3125C8.8125 2.4375 7.75 2 6.5 2C5.25 2 4.1875 2.4375 3.3125 3.3125C2.4375 4.1875 2 5.25 2 6.5C2 7.75 2.4375 8.8125 3.3125 9.6875C4.1875 10.5625 5.25 11 6.5 11Z" fill="#6B7786"/>
                        </svg>
                    </div>
                    <input id="searchInput" type="text" placeholder="Search" class="w-full px-3 py-1 border-none focus:outline-[#115640] "/>
                </div>
                {{-- FILTERING TRASH LEADS AND NAVIGATION STATUS TABLES --}}
                <div class="grid grid-cols-[6fr_1fr] gap-4">
                    {{-- FILTERING TRASH LEADS --}}
                    <div class="w-full border border-[#D5D5D5] rounded-lg grid grid-cols-4">
                        <div class="flex items-center justify-center gap-2 border-b lg:border-b-0 lg:border-r border-[#CFD5DC] px-2 py-2 h-full">
                        <select id="filterSales" class="w-full text-sm font-semibold text-center focus:outline-none cursor-pointer">
                            <option value="">All Sales</option>
                            @foreach($salesFilters as $salesFilter)
                                <option value="{{ $salesFilter->id }}">
                                    {{ $salesFilter->name }} - {{ $salesFilter->branch->name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                        </div>

                        <div class="cursor-pointer w-full relative grid grid-cols-1 items-center h-full border-b lg:border-b-0 lg:border-r border-[#D9D9D9]">
                        <div id="openClaimedDateDropdown" class="flex justify-center items-center gap-2 py-2">
                            <p id="claimedDateLabel" class="font-medium text-black text-sm">Claimed At</p>
                            <i id="claimedIconDate" class="fas fa-chevron-down transition-transform duration-300 text-black" style="font-size: 12px;"></i>
                        </div>

                        <div id="claimedDateDropdown" class="absolute top-full left-0 mt-2 bg-white rounded-lg shadow-xl w-[350px] p-4 z-50 opacity-0 scale-95 pointer-events-none transition-all duration-200 ease-out origin-top overflow-visible">
                            <h3 class="font-semibold mb-2">Select Date Range</h3>

                            <div class="flex justify-center items-center">
                            <input type="text" id="claimed-source-date-range" class="shadow-none w-full" placeholder="Select date range">
                            </div>

                            <div class="flex justify-end gap-2 mt-3">
                            <button id="cancelClaimedDate" class="px-3 py-1 text-[#303030]">Cancel</button>
                            <button id="applyClaimedDate" class="px-3 py-1 bg-[#115640] text-white rounded-lg cursor-pointer">Apply</button>
                            </div>
                        </div>
                        </div>

                        <div class="cursor-pointer w-full relative grid grid-cols-1 items-center h-full border-b lg:border-b-0 lg:border-r border-[#D9D9D9]">
                        <div id="openToTrashDateDropdown" class="flex justify-center items-center gap-2 py-2">
                            <p id="toTrashDateLabel" class="font-medium text-black text-sm">To Trash At</p>
                            <i id="toTrashIconDate" class="fas fa-chevron-down transition-transform duration-300 text-black" style="font-size: 12px;"></i>
                        </div>

                        <div id="toTrashDateDropdown" class="absolute top-full left-0 mt-2 bg-white rounded-lg shadow-xl w-[350px] p-4 z-50 opacity-0 scale-95 pointer-events-none transition-all duration-200 ease-out origin-top overflow-visible">
                            <h3 class="font-semibold mb-2">Select Date Range</h3>

                            <div class="flex justify-center items-center">
                            <input type="text" id="to-trash-source-date-range" class="shadow-none w-full" placeholder="Select date range">
                            </div>

                            <div class="flex justify-end gap-2 mt-3">
                            <button id="cancelToTrashDate" class="px-3 py-1 text-[#303030]">Cancel</button>
                            <button id="applyToTrashDate" class="px-3 py-1 bg-[#115640] text-white rounded-lg cursor-pointer">Apply</button>
                            </div>
                        </div>
                        </div>

                        <div id="resetTrashLeadFilter" class="flex items-center justify-center gap-2 py-2 cursor-pointer h-full">
                        <i class="fa fa-redo transition-transform duration-300 text-[#900B09] -scale-x-100" style="font-size: 12px;"></i>
                        <p class="font-medium text-[#900B09]">Reset Filter</p>
                        </div>
                    </div>

                    {{-- NAVIGATION STATUS TABLES --}}
                    <div class="w-full border border-[#D5D5D5] rounded-lg grid grid-cols-1">
                        {{-- NAVIGATION STATUS --}}
                            <div class="text-center cursor-pointer h-full border-r border-r-[#D5D5D5] py-3 nav-leads-active" data-status="hot">
                            <p class="text-[#083224]">
                                Hot
                                <span 
                                    class="span-hot">
                                        {{ $leadCounts['hot'] }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="bulkRestoreBar" class="hidden items-center justify-between gap-3 px-3 py-2 border-b border-[#D9D9D9] bg-white">
            <p class="text-[#115640] font-semibold"><span id="bulkRestoreCount">0</span> lead selected</p>
            <button id="bulkRestoreBtn" type="button" class="px-3 py-2 rounded-md bg-[#115640] text-white font-semibold cursor-pointer">
                Restore Selected
            </button>
            </div>

            {{-- CONTENTS TABLES --}}
            <div class="max-md:overflow-x-scroll">
                <div data-status-wrapper="hot">
                    <table id="hotTrashLeadsTableTailwind" class="w-full bg-white rounded-br-lg rounded-bl-lg">
                        {{-- HEADER TABLE --}}
                        <thead class="text-[#1E1E1E]">
                            <tr class="border-b border-b-[#CFD5DC]">
                                <th class="hidden">ID (hidden)</th>
                                <th class="p-1 md:p-2 lg:p-3">
                                Claimed At
                                </th>
                                <th class="p-1 md:p-2 lg:p-3">
                                To Trash At
                                </th>
                                <th class="p-1 md:p-2 lg:p-3">  
                                    Lead Name
                                </th>
                                <th class="p-1 md:p-2 lg:p-3">
                                    Segment
                                </th>
                                <th class="p-1 md:p-2 lg:p-3">
                                    Source
                                </th>
                                <th class="p-1 md:p-2 lg:p-3">
                                    First Sales
                                </th>
                                <th class="p-1 md:p-2 lg:p-3">
                                Status
                                </th>
                                <th class="text-center p-1 md:p-2 lg:p-3">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody id="hotBodyTable"></tbody>
                    </table>
                    {{-- NAVIGATION ROWS --}}
                    <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! bg-transparent">
                        <div class="flex items-center gap-3">
                        <p class="font-semibold">Show Rows</p>
                        <select id="hotPageSizeSelect" class="w-auto bg-white font-semibold p-2 rounded-md"
                            onchange="changePageSize('hot', this.value)">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <div id="hotShowing" class="font-semibold">Showing 0-0 of 0</div>
                            <div>
                            <button id="hotPrevBtn"
                                class="btn btn bg-white border! border-[#D9D9D9]! cursor-pointer!"
                                onclick="goPrev('hot')">
                                <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
                            </button>
                            <button id="hotNextBtn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!"
                                onclick="goNext('hot')">
                                <i class="fas fa-chevron-right text-black" style="font-size: 12px;"></i>
                            </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Assign Lead Modal -->
    <div class="modal fade" id="assignLeadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="assignLeadForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Lead</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assign_claim_id">
                <div class="mb-3">
                <label for="assign_branch_id" class="form-label">Branch</label>
                <select id="assign_branch_id" class="form-select select2">
                    <option value="">-- Select Branch --</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                </div>
                <div class="mb-3">
                <label for="assign_sales_id" class="form-label">Sales</label>
                <select id="assign_sales_id" class="form-select select2">
                    <option value="">-- Select Sales --</option>
                </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Assign</button>
            </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    @php
        $hotRoute = \Illuminate\Support\Facades\Route::has('lost-leads.hot.list')
            ? route('lost-leads.hot.list')
            : null;
    @endphp

    const hotRoute = @json($hotRoute);
    const DEFAULT_PAGE_SIZE = 10;

    let currentPage = 1;
    let pageSize = DEFAULT_PAGE_SIZE;
    let totalItems = {{ $leadCounts['hot'] ?? 0 }};

    const filterState = {
        sales: '',
        filter_by_claimed_at: { start_at: '', end_at: '' },
        filter_by_to_trash_at: { start_at: '', end_at: '' }
    };

    function getSearchQuery() {
        const el = document.getElementById('searchInput');
        return (el?.value || '').trim();
    }

    function closeDateDropdown(dropdownSelector, iconSelector) {
        const $dropdown = $(dropdownSelector);
        const $icon = $(iconSelector);
        $dropdown.addClass('opacity-0 scale-95 pointer-events-none');
        $icon.removeClass('rotate-180');
    }

    function updatePagerUI(total) {
        const totalPages = Math.max(1, Math.ceil((total || 0) / pageSize));

        const prev = document.getElementById('hotPrevBtn');
        const next = document.getElementById('hotNextBtn');
        const showing = document.getElementById('hotShowing');

        if (prev) prev.disabled = currentPage <= 1;
        if (next) next.disabled = currentPage >= totalPages;

        const startIdx = total === 0 ? 0 : (currentPage - 1) * pageSize + 1;
        const endIdx = Math.min(total, (currentPage - 1) * pageSize + pageSize);

        if (showing) showing.innerText = `Showing ${startIdx}-${endIdx} of ${total}`;
    }

    function changePageSize(_tab, value) {
        pageSize = parseInt(value, 10) || DEFAULT_PAGE_SIZE;
        currentPage = 1;
        initHotTable();
    }

    function goPrev(_tab) {
        if (currentPage > 1) {
            currentPage -= 1;
            initHotTable();
        }
    }

    function goNext(_tab) {
        const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
        if (currentPage < totalPages) {
            currentPage += 1;
            initHotTable();
        }
    }

    window.changePageSize = changePageSize;
    window.goPrev = goPrev;
    window.goNext = goNext;

    async function initHotTable() {
        const tbody = document.getElementById('hotBodyTable');
        if (!tbody) return;

        if (!hotRoute) {
            tbody.innerHTML = `<tr><td colspan="10" class="text-center p-3 text-[#1E1E1E]">Route lost-leads hot list tidak ditemukan</td></tr>`;
            return;
        }

        const params = new URLSearchParams({
            page: currentPage,
            per_page: pageSize,
        });

        const search = getSearchQuery();
        if (search) params.set('search', search);

        if (filterState.sales) params.set('sales', filterState.sales);

        if (filterState.filter_by_claimed_at.start_at) params.set('filter_by_claimed_at[start_at]', filterState.filter_by_claimed_at.start_at);
        if (filterState.filter_by_claimed_at.end_at) params.set('filter_by_claimed_at[end_at]', filterState.filter_by_claimed_at.end_at);

        if (filterState.filter_by_to_trash_at.start_at) params.set('filter_by_to_trash_at[start_at]', filterState.filter_by_to_trash_at.start_at);
        if (filterState.filter_by_to_trash_at.end_at) params.set('filter_by_to_trash_at[end_at]', filterState.filter_by_to_trash_at.end_at);

        try {
            const response = await fetch(`${hotRoute}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load lost leads hot list');
            }

            const result = await response.json();
            totalItems = result.total || 0;
            updatePagerUI(totalItems);

            if (result.data && result.data.length > 0) {
                let rowsHtml = '';
                result.data.forEach(row => {
                    rowsHtml += `
                        <tr class="border-b border-b-[#D9D9D9] text-[#1E1E1E]">
                            <td class="hidden">${row.id}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.claimed_at ?? '-'}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.trashed_at ?? '-'}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.name ?? '-'}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.segment_name ?? '-'}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.source ?? '-'}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.sales_name ?? '-'}</td>
                            <td class="p-1 md:p-2 lg:p-3">${row.status_lead ?? '-'}</td>
                            <td class="text-center p-1 md:p-2 lg:p-3">${row.actions ?? '-'}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = rowsHtml;
            } else {
                tbody.innerHTML = `<tr><td colspan="10" class="text-center p-3 text-[#1E1E1E] border-b border-b-[#D9D9D9]">No data available</td></tr>`;
            }
        } catch (error) {
            console.error('Load Lost Leads Hot Error:', error);
            tbody.innerHTML = `<tr><td colspan="10" class="text-center p-3 text-[#1E1E1E]">Failed to load data</td></tr>`;
        }
    }

    $(function () {
        const claimedPicker = (typeof flatpickr !== 'undefined')
            ? flatpickr('#claimed-source-date-range', {
                mode: 'range',
                inline: true,
                dateFormat: 'Y-m-d'
            })
            : null;

        const toTrashPicker = (typeof flatpickr !== 'undefined')
            ? flatpickr('#to-trash-source-date-range', {
                mode: 'range',
                inline: true,
                dateFormat: 'Y-m-d'
            })
            : null;

        function applyRangeToFilter(picker, labelSelector, filterKey) {
            if (!picker || picker.selectedDates.length !== 2) return false;

            const startAt = flatpickr.formatDate(picker.selectedDates[0], 'Y-m-d');
            const endAt = flatpickr.formatDate(picker.selectedDates[1], 'Y-m-d');

            filterState[filterKey].start_at = startAt;
            filterState[filterKey].end_at = endAt;
            $(labelSelector).text(`${startAt} - ${endAt}`);
            return true;
        }

        function clearRangeFilter(picker, labelSelector, defaultLabel, filterKey) {
            if (picker) picker.clear();
            filterState[filterKey].start_at = '';
            filterState[filterKey].end_at = '';
            $(labelSelector).text(defaultLabel);
        }

        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let searchTimeout = null;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentPage = 1;
                    initHotTable();
                }, 500);
            });
        }

        $('#filterSales').on('change', function () {
            filterState.sales = ($(this).val() || '').trim();
            currentPage = 1;
            initHotTable();
        });

        $('#openClaimedDateDropdown').on('click', function (e) {
            e.stopPropagation();
            $('#claimedDateDropdown').toggleClass('opacity-0 scale-95 pointer-events-none');
            $('#claimedIconDate').toggleClass('rotate-180');
        });

        $('#openToTrashDateDropdown').on('click', function (e) {
            e.stopPropagation();
            $('#toTrashDateDropdown').toggleClass('opacity-0 scale-95 pointer-events-none');
            $('#toTrashIconDate').toggleClass('rotate-180');
        });

        $('#claimedDateDropdown, #toTrashDateDropdown').on('click', function (e) {
            e.stopPropagation();
        });

        $('#cancelClaimedDate').on('click', function () {
            closeDateDropdown('#claimedDateDropdown', '#claimedIconDate');
        });

        $('#cancelToTrashDate').on('click', function () {
            closeDateDropdown('#toTrashDateDropdown', '#toTrashIconDate');
        });

        $('#applyClaimedDate').on('click', function () {
            const ok = applyRangeToFilter(claimedPicker, '#claimedDateLabel', 'filter_by_claimed_at');
            if (!ok) return;

            closeDateDropdown('#claimedDateDropdown', '#claimedIconDate');
            currentPage = 1;
            initHotTable();
        });

        $('#applyToTrashDate').on('click', function () {
            const ok = applyRangeToFilter(toTrashPicker, '#toTrashDateLabel', 'filter_by_to_trash_at');
            if (!ok) return;

            closeDateDropdown('#toTrashDateDropdown', '#toTrashIconDate');
            currentPage = 1;
            initHotTable();
        });

        $('#resetTrashLeadFilter').on('click', function () {
            filterState.sales = '';
            $('#filterSales').val('');

            clearRangeFilter(claimedPicker, '#claimedDateLabel', 'Claimed At', 'filter_by_claimed_at');
            clearRangeFilter(toTrashPicker, '#toTrashDateLabel', 'To Trash At', 'filter_by_to_trash_at');

            closeDateDropdown('#claimedDateDropdown', '#claimedIconDate');
            closeDateDropdown('#toTrashDateDropdown', '#toTrashIconDate');

            currentPage = 1;
            initHotTable();
        });

        $(document).on('click', function () {
            closeDateDropdown('#claimedDateDropdown', '#claimedIconDate');
            closeDateDropdown('#toTrashDateDropdown', '#toTrashIconDate');
        });

        initHotTable();
    });
</script>
@endsection
