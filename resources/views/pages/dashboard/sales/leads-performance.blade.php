<h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">My Leads Performance</h1>
<div class="mt-2 bg-white rounded-lg border-r border-l border-t border-[#D9D9D9]">
    {{-- NAVIGATION TABLES --}}
    <div class="bg-white lg:grid lg:grid-cols-[1fr_3fr] border-b border-[#D9D9D9] p-3 gap-4 rounded-tr-lg rounded-tl-lg sm:gap-3 grid grid-cols-1">
        {{-- SEARCH TABLES --}}
        <div class="w-full border border-gray-300 rounded-lg flex items-center p-2">
            <div class="px-2">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.5 13C4.68333 13 3.14583 12.3708 1.8875 11.1125C0.629167 9.85417 0 8.31667 0 6.5C0 4.68333 0.629167 3.14583 1.8875 1.8875C3.14583 0.629167 4.68333 0 6.5 0C8.31667 0 9.85417 0.629167 11.1125 1.8875C12.3708 3.14583 13 4.68333 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.8125 10.5625 9.6875 9.6875C10.5625 8.8125 11 7.75 11 6.5C11 5.25 10.5625 4.1875 9.6875 3.3125C8.8125 2.4375 7.75 2 6.5 2C5.25 2 4.1875 2.4375 3.3125 3.3125C2.4375 4.1875 2 5.25 2 6.5C2 7.75 2.4375 8.8125 3.3125 9.6875C4.1875 10.5625 5.25 11 6.5 11Z" fill="#6B7786"/>
                </svg>
            </div>
            <input id="searchInputLeadSales" type="text" placeholder="Search" class="w-full px-3 py-1 border-none focus:outline-[#115640] "/>
        </div>

        <div class="w-full grid grid-cols-4  border border-gray-300 rounded-lg p-2">
            
            {{-- FILTERS BY --}}
            <div class="flex items-center justify-center gap-2 border-r border-r-[#CFD5DC] cursor-pointer h-full text-[#1E1E1E]">                        
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7.02059 16C6.73725 16 6.49975 15.9042 6.30809 15.7125C6.11642 15.5208 6.02059 15.2833 6.02059 15V9L0.220588 1.6C-0.0294118 1.26667 -0.0669118 0.916667 0.108088 0.55C0.283088 0.183333 0.587255 0 1.02059 0H15.0206C15.4539 0 15.7581 0.183333 15.9331 0.55C16.1081 0.916667 16.0706 1.26667 15.8206 1.6L10.0206 9V15C10.0206 15.2833 9.92476 15.5208 9.73309 15.7125C9.54142 15.9042 9.30392 16 9.02059 16H7.02059ZM8.02059 8.3L12.9706 2H3.07059L8.02059 8.3Z" fill="#0D0F11"/>
                </svg>
                <p class="font-medium">Filter By</p>
            </div>

            {{-- SOURCES --}}
            <div class="flex items-center justify-center gap-2 border-r border-r-[#CFD5DC] cursor-pointer h-full px-2 text-[#1E1E1E]">
                <select id="salesLeadPerformanceSource"
                class="w-full font-semibold text-center focus:outline-none cursor-pointer">
                <option value="">All Source</option>
                @foreach($leadSources as $source)
                    <option value="{{ $source->id }}">{{ $source->name }}</option>
                @endforeach
                </select>
            </div>

            {{-- DATES --}}
            <div
            class="border-r border-r-[#CFD5DC] cursor-pointer w-full relative grid grid-cols-1 items-center h-full">

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

            {{-- RESET FILTER --}}
            <div id="salesActivityReset" class="flex items-center justify-center gap-2 cursor-pointer h-full">
                <i id="chevronFiltersReset" class="fa fa-redo transition-transform duration-300 text-[#900B09] -scale-x-100   " style="font-size: 12px;"></i>
                <p class="font-medium text-[#900B09]">Reset Filter</p>
            </div>
        </div>
    </div>

    {{-- CONTENTS TABLES --}}
    <div class="max-md:overflow-x-scroll">
        <table class="w-full bg-white rounded-br-lg rounded-bl-lg">

            {{-- HEADER TABLE --}}
            <thead class="text-[#1E1E1E]">
                <tr class="border-b border-b-[#CFD5DC]">
                    <th class="hidden">ID (hidden)</th>
                    <th class="p-1 md:p-2 lg:p-3">
                        Source
                    </th>
                    <th class="p-1 md:p-2 lg:p-3">  
                        Segment
                    </th>
                    <th class="p-1 md:p-2 lg:p-3">
                        Cum
                    </th>
                    <th class="p-1 md:p-2 lg:p-3">
                        Cold
                    </th>
                    <th class="p-1 md:p-2 lg:p-3">
                        Warm
                    </th>
                    <th class="p-1 md:p-2 lg:p-3">
                        Hot
                    </th>
                    <th class="p-1 md:p-2 lg:p-3">
                        Deal
                    </th>
                </tr>
            </thead>
            <tbody id="salesBodyTableLeadsPerformance" class="text-[#1E1E1E]"></tbody>
            <tfoot id="salesFootTableLeadsPerformance"></tfoot>
        </table>
    </div>

    {{-- NAVIGATION ROWS --}}
    <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! bg-transparent border-t border-t-[#D9D9D9]">
        <div class="flex items-center gap-3">
            <p class="font-semibold">Show Rows</p>
            <select id="tabPageSizeSelect" class="w-auto bg-white font-semibold p-2 rounded-md"
                onchange="loadLead('size', this.value)">
                <option value="5" selected>5</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>
</div>

<script>
    // LOAD LEAD
    async function loadLead(action = 'init', value = null) {
        const API_URL = '/api/leads/leads-performance';

        if (action === 'size' && value) {
            leadsPerformancePageSize = parseInt(value, 10) || DEFAULT_PAGE_SIZE;
            applyLeadRowLimit();
            return;
        }

        const params = new URLSearchParams({
            search: typeof getSearchQuery === 'function' ? getSearchQuery() : ''
        });

        if (filterSource) params.append('source_id', filterSource);
        if (filterStartDate) params.append('start_date', filterStartDate);
        if (filterEndDate) params.append('end_date', filterEndDate);

        const tbody = document.getElementById('salesBodyTableLeadsPerformance');
        const tfoot = document.getElementById('salesFootTableLeadsPerformance');
        
        try {
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-3 text-[#1E1E1E]">
                            Loading data...
                        </td>
                    </tr>
                `;
            }

            const response = await fetch(`${API_URL}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (!result || result.status !== 'success') return;

            const leadData = result.data || [];
            const summary = result.summary || {};

            tbody.innerHTML = '';
            tfoot.innerHTML = '';

            if (leadData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-3 text-[#1E1E1E]">
                            No data found
                        </td>
                    </tr>
                `;
                return;
            }

            let rowsHtml = '';

            leadData.forEach((item, index) => {
                rowsHtml += `
                    <tr data-row-index="${index}" class="border-t border-t-[#D9D9D9] text-[#1E1E1E]">
                        <td class="p-1 lg:p-3">${item.source ?? '-'}</td>
                        <td class="p-1 lg:p-3">${item.segment ?? '-'}</td>
                        <td class="p-1 lg:p-3">
                            ${item.cum ?? '-'}
                            <span class="opacity-50 text-xs">(${item.persen_cum ?? '-'}%)</span>
                        </td>
                        <td class="p-1 lg:p-3">
                            ${item.cold ?? '-'}
                            <span class="opacity-50 text-xs">(${item.persen_cold ?? '-'}%)</span>
                        </td>
                        <td class="p-1 lg:p-3">
                            ${item.warm ?? '-'}
                            <span class="opacity-50 text-xs">(${item.persen_warm ?? '-'}%)</span>
                        </td>
                        <td class="p-1 lg:p-3">
                            ${item.hot ?? '-'}
                            <span class="opacity-50 text-xs">(${item.persen_hot ?? '-'}%)</span>
                        </td>
                        <td class="p-1 lg:p-3">
                            ${item.deal ?? '-'}
                            <span class="opacity-50 text-xs">(${item.persen_deal ?? '-'}%)</span>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = rowsHtml;

            tfoot.innerHTML = `
                <tr class="font-semibold border-t border-t-[#D9D9D9] text-[#1E1E1E]">
                    <td class="p-2 lg:p-3">Total</td>
                    <td class="p-2 lg:p-3"></td>
                    <td class="p-2 lg:p-3">${summary.total_all ?? 0}</td>
                    <td class="p-2 lg:p-3">${summary.total_cold ?? 0}</td>
                    <td class="p-2 lg:p-3">${summary.total_warm ?? 0}</td>
                    <td class="p-2 lg:p-3">${summary.total_hot ?? 0}</td>
                    <td class="p-2 lg:p-3">${summary.total_deal ?? 0}</td>
                </tr>
            `;

            applyLeadRowLimit();

        } catch (error) {
            console.error('Load Lead Error:', error);
        }
    }

    const leadSourceSelect = document.getElementById('salesLeadPerformanceSource');

    if (leadSourceSelect) {
        leadSourceSelect.addEventListener('change', function () {

            filterSource = this.value || null;

            loadLead('filter');
        });
    }

    const searchInputLead = document.getElementById('searchInputLeadSales');

    if (searchInputLead) {
        searchInputLead.addEventListener('keyup', function () {

            clearTimeout(searchTimeout);

            searchTimeout = setTimeout(() => {

                searchQuery = this.value.trim();

                loadLead('search');

            }, 500);

        });
    }
    
    function applyLeadRowLimit() {
        const tbody = document.getElementById('salesBodyTableLeadsPerformance');
        if (!tbody) return;

        const rows = tbody.querySelectorAll('tr[data-row-index]');

        rows.forEach((row, index) => {
            if (index < leadsPerformancePageSize) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
    }

</script>