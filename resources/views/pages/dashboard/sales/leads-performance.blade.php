<h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">My Leads Performance</h1>
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mt-2">
    {{-- SOURCES SECTION --}}
    <div class="bg-white rounded-lg border-r border-l border-t border-[#D9D9D9]">
        <h1 class="text-lg font-semibold p-3 text-[#1E1E1E] border-b border-b-[#D9D9D9]">
            Source Performance
        </h1>
        {{-- NAVIGATION TABLES --}}
        <div class="bg-white lg:grid lg:grid-cols-[1fr_3fr] border-b border-[#D9D9D9] p-3 gap-4 rounded-tr-lg rounded-tl-lg sm:gap-3 grid grid-cols-1">
            {{-- SEARCH TABLES --}}
            <div class="w-full border border-gray-300 rounded-lg flex items-center p-2">
                <div class="px-2">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6.5 13C4.68333 13 3.14583 12.3708 1.8875 11.1125C0.629167 9.85417 0 8.31667 0 6.5C0 4.68333 0.629167 3.14583 1.8875 1.8875C3.14583 0.629167 4.68333 0 6.5 0C8.31667 0 9.85417 0.629167 11.1125 1.8875C12.3708 3.14583 13 4.68333 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.8125 10.5625 9.6875 9.6875C10.5625 8.8125 11 7.75 11 6.5C11 5.25 10.5625 4.1875 9.6875 3.3125C8.8125 2.4375 7.75 2 6.5 2C5.25 2 4.1875 2.4375 3.3125 3.3125C2.4375 4.1875 2 5.25 2 6.5C2 7.75 2.4375 8.8125 3.3125 9.6875C4.1875 10.5625 5.25 11 6.5 11Z" fill="#6B7786"/>
                    </svg>
                </div>
                <input id="searchInputLeadSourceSales" type="text" placeholder="Search" class="w-full px-3 py-1 border-none focus:outline-[#115640] "/>
            </div>
    
            <div class="w-full grid grid-cols-3 border border-gray-300 rounded-lg p-2">
    
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
                <div id="lpSourceOpenDateDropdown" class="flex justify-center items-center gap-2">
                    <p id="lpSourceDateLabel" class="font-medium text-black">Date</p>
                    <i id="lpSourceIconDate" class="fas fa-chevron-down transition-transform duration-300 text-black" style="font-size: 12px;"></i>
                </div>
    
                {{-- DATE DROPDOWN --}}
                <div id="lpSourceDateDropdown"
                    class="absolute top-full left-0 mt-2 bg-white rounded-lg shadow-xl w-[350px] p-4 z-50 opacity-0 scale-95 pointer-events-none transition-all duration-200 ease-out origin-top overflow-visible">
    
                    <h3 class="font-semibold mb-2">Select Date Range</h3>
    
                        <div class="flex justify-center items-center">
                            <input type="text" id="lpSourceDateRange" class="shadow-none w-full" placeholder="Select date range">
                        </div>
    
                    <div class="flex justify-end gap-2 mt-3">
    
                        <button id="lpSourceCancelDate" class="px-3 py-1 text-[#303030]">
                            Cancel
                        </button>
    
                        <button id="lpSourceApplyDate"
                            class="px-3 py-1 bg-[#115640] text-white rounded-lg cursor-pointer">
                            Apply
                        </button>
    
                    </div>
                </div>
                </div>  
    
                {{-- RESET FILTER --}}
                <div id="leadsSourceReset" class="flex items-center justify-center gap-2 cursor-pointer h-full">
                    <i id="chevronFiltersReset" class="fa fa-redo transition-transform duration-300 text-[#900B09] -scale-x-100   " style="font-size: 12px;"></i>
                    <p class="font-medium text-[#900B09]">Reset Filter</p>
                </div>
            </div>
        </div>
    
        {{-- CONTENTS TABLES --}}
        <div class="overflow-x-scroll">
            <table class="w-full whitespace-nowrap bg-white rounded-br-lg rounded-bl-lg">
    
                {{-- HEADER TABLE --}}
                <thead class="text-[#1E1E1E]">
                    <tr class="border-b border-b-[#CFD5DC]">
                        <th class="hidden">ID (hidden)</th>
                        <th class="p-1 md:p-2 lg:p-3">
                            Source
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
                <tbody id="salesBodyTableLeadsPerformanceSource" class="text-[#1E1E1E]"></tbody>
                <tfoot id="salesFootTableLeadsPerformanceSource"></tfoot>
            </table>
        </div>
    
        {{-- NAVIGATION ROWS --}}
        <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! bg-transparent border-t border-t-[#D9D9D9]">
            <div class="flex items-center gap-3">
                <p class="font-semibold">Show Rows</p>
                <select id="tabPageSizeSelect" class="w-auto bg-white font-semibold p-2 rounded-md"
                    onchange="loadSource('size', this.value)">
                    <option value="5" selected>5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>
    
    {{-- SEGMENTS SECTION --}}
    <div class="bg-white rounded-lg border-r border-l border-t border-[#D9D9D9]">
        <h1 class="text-lg font-semibold p-3 text-[#1E1E1E] border-b border-b-[#D9D9D9]">
            Segment Performance
        </h1>
        {{-- NAVIGATION TABLES --}}
        <div class="bg-white lg:grid lg:grid-cols-[1fr_3fr] border-b border-[#D9D9D9] p-3 gap-4 rounded-tr-lg rounded-tl-lg sm:gap-3 grid grid-cols-1">
            {{-- SEARCH TABLES --}}
            <div class="w-full border border-gray-300 rounded-lg flex items-center p-2">
                <div class="px-2">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6.5 13C4.68333 13 3.14583 12.3708 1.8875 11.1125C0.629167 9.85417 0 8.31667 0 6.5C0 4.68333 0.629167 3.14583 1.8875 1.8875C3.14583 0.629167 4.68333 0 6.5 0C8.31667 0 9.85417 0.629167 11.1125 1.8875C12.3708 3.14583 13 4.68333 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.8125 10.5625 9.6875 9.6875C10.5625 8.8125 11 7.75 11 6.5C11 5.25 10.5625 4.1875 9.6875 3.3125C8.8125 2.4375 7.75 2 6.5 2C5.25 2 4.1875 2.4375 3.3125 3.3125C2.4375 4.1875 2 5.25 2 6.5C2 7.75 2.4375 8.8125 3.3125 9.6875C4.1875 10.5625 5.25 11 6.5 11Z" fill="#6B7786"/>
                    </svg>
                </div>
                <input id="searchInputLeadSegmentSales" type="text" placeholder="Search" class="w-full px-3 py-1 border-none focus:outline-[#115640] "/>
            </div>
    
            <div class="w-full grid grid-cols-3  border border-gray-300 rounded-lg p-2">
    
                {{-- SOURCES --}}
                <div class="flex items-center justify-center gap-2 border-r border-r-[#CFD5DC] cursor-pointer h-full px-2 text-[#1E1E1E]">
                    <select id="salesLeadPerformanceSegments"
                    class="w-full font-semibold text-center focus:outline-none cursor-pointer">
                    <option value="">All Segment</option>
                    @foreach($segments as $segment)
                        <option value="{{ $segment->id }}">{{ $segment->name }}</option>
                    @endforeach
                    </select>
                </div>
                {{-- DATES --}}
                <div
                class="border-r border-r-[#CFD5DC] cursor-pointer w-full relative grid grid-cols-1 items-center h-full">
    
                {{-- TOGGLE --}}
                <div id="lpSegmentOpenDateDropdown" class="flex justify-center items-center gap-2">
                    <p id="lpSegmentDateLabel" class="font-medium text-black">Date</p>
                    <i id="lpSegmentIconDate" class="fas fa-chevron-down transition-transform duration-300 text-black" style="font-size: 12px;"></i>
                </div>
    
                {{-- DATE DROPDOWN --}}
                <div id="lpSegmentDateDropdown"
                    class="absolute top-full left-0 mt-2 bg-white rounded-lg shadow-xl w-[350px] p-4 z-50 opacity-0 scale-95 pointer-events-none transition-all duration-200 ease-out origin-top overflow-visible">
    
                    <h3 class="font-semibold mb-2">Select Date Range</h3>
    
                        <div class="flex justify-center items-center">
                            <input type="text" id="lpSegmentDateRange" class="shadow-none w-full" placeholder="Select date range">
                        </div>
    
                    <div class="flex justify-end gap-2 mt-3">
    
                        <button id="lpSegmentCancelDate" class="px-3 py-1 text-[#303030]">
                            Cancel
                        </button>
    
                        <button id="lpSegmentApplyDate"
                            class="px-3 py-1 bg-[#115640] text-white rounded-lg cursor-pointer">
                            Apply
                        </button>
    
                    </div>
                </div>
                </div>  
    
                {{-- RESET FILTER --}}
                <div id="leadsSegmentReset" class="flex items-center justify-center gap-2 cursor-pointer h-full">
                    <i id="chevronFiltersReset" class="fa fa-redo transition-transform duration-300 text-[#900B09] -scale-x-100   " style="font-size: 12px;"></i>
                    <p class="font-medium text-[#900B09]">Reset Filter</p>
                </div>
            </div>
        </div>
    
        {{-- CONTENTS TABLES --}}
        <div class="overflow-x-scroll">
            <table class="w-full whitespace-nowrap bg-white rounded-br-lg rounded-bl-lg">
    
                {{-- HEADER TABLE --}}
                <thead class="text-[#1E1E1E]">
                    <tr class="border-b border-b-[#CFD5DC]">
                        <th class="hidden">ID (hidden)</th>
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
                <tbody id="salesBodyTableLeadsPerformanceSegments" class="text-[#1E1E1E]"></tbody>
                <tfoot id="salesFootTableLeadsPerformanceSegments"></tfoot>
            </table>
        </div>
    
        {{-- NAVIGATION ROWS --}}
        <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! bg-transparent border-t border-t-[#D9D9D9]">
            <div class="flex items-center gap-3">
                <p class="font-semibold">Show Rows</p>
                <select id="tabPageSizeSelect" class="w-auto bg-white font-semibold p-2 rounded-md"
                    onchange="loadSegment('size', this.value)">
                    <option value="5" selected>5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>
</div>

<script>
    let lpSourceFp = null;
    let lpSegmentFp = null;
    let leadSourceFilter = '';
    let leadSegmentFilter = '';
    let leadSourceSearchQuery = '';
    let leadSegmentSearchQuery = '';
    let leadSourceSearchTimeout = null;
    let leadSegmentSearchTimeout = null;

    function applySourceDateFilter(startDate, endDate) {
        filterStartDateSource = startDate;
        filterEndDateSource = endDate;

        const sourceLabel = document.getElementById('lpSourceDateLabel');
        if (sourceLabel) sourceLabel.innerText = `${startDate} -> ${endDate}`;

        loadSource('filter');
    }

    function applySegmentDateFilter(startDate, endDate) {
        filterStartDateSegment = startDate;
        filterEndDateSegment = endDate;

        const segmentLabel = document.getElementById('lpSegmentDateLabel');
        if (segmentLabel) segmentLabel.innerText = `${startDate} -> ${endDate}`;

        loadSegment('filter');
    }

    function setupLeadPerformanceDatePicker(config) {
        const openBtn = document.getElementById(config.openBtnId);
        const dropdown = document.getElementById(config.dropdownId);
        const icon = document.getElementById(config.iconId);
        const input = document.getElementById(config.inputId);
        const cancelBtn = document.getElementById(config.cancelBtnId);
        const applyBtn = document.getElementById(config.applyBtnId);

        if (!openBtn || !dropdown || !icon || !input || !cancelBtn || !applyBtn || typeof flatpickr === 'undefined') {
            return null;
        }

        const instance = flatpickr(input, {
            mode: 'range',
            inline: true,
            dateFormat: 'Y-m-d',
        });

        openBtn.addEventListener('click', () => {
            dropdown.classList.toggle('opacity-0');
            dropdown.classList.toggle('scale-95');
            dropdown.classList.toggle('pointer-events-none');
            icon.classList.toggle('rotate-180');
            instance.open();
        });

        cancelBtn.addEventListener('click', () => {
            dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
            icon.classList.remove('rotate-180');
        });

        applyBtn.addEventListener('click', () => {
            const dates = instance.selectedDates || [];
            if (dates.length !== 2) return;

            const startDate = instance.formatDate(dates[0], 'Y-m-d');
            const endDate = instance.formatDate(dates[1], 'Y-m-d');

            if (typeof config.onApply === 'function') {
                config.onApply(startDate, endDate);
            }

            dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
            icon.classList.remove('rotate-180');
        });

        return instance;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            lpSourceFp = setupLeadPerformanceDatePicker({
                openBtnId: 'lpSourceOpenDateDropdown',
                dropdownId: 'lpSourceDateDropdown',
                iconId: 'lpSourceIconDate',
                inputId: 'lpSourceDateRange',
                cancelBtnId: 'lpSourceCancelDate',
                applyBtnId: 'lpSourceApplyDate',
                onApply: applySourceDateFilter,
            });

            lpSegmentFp = setupLeadPerformanceDatePicker({
                openBtnId: 'lpSegmentOpenDateDropdown',
                dropdownId: 'lpSegmentDateDropdown',
                iconId: 'lpSegmentIconDate',
                inputId: 'lpSegmentDateRange',
                cancelBtnId: 'lpSegmentCancelDate',
                applyBtnId: 'lpSegmentApplyDate',
                onApply: applySegmentDateFilter,
            });
        });
    } else {
        lpSourceFp = setupLeadPerformanceDatePicker({
            openBtnId: 'lpSourceOpenDateDropdown',
            dropdownId: 'lpSourceDateDropdown',
            iconId: 'lpSourceIconDate',
            inputId: 'lpSourceDateRange',
            cancelBtnId: 'lpSourceCancelDate',
            applyBtnId: 'lpSourceApplyDate',
            onApply: applySourceDateFilter,
        });

        lpSegmentFp = setupLeadPerformanceDatePicker({
            openBtnId: 'lpSegmentOpenDateDropdown',
            dropdownId: 'lpSegmentDateDropdown',
            iconId: 'lpSegmentIconDate',
            inputId: 'lpSegmentDateRange',
            cancelBtnId: 'lpSegmentCancelDate',
            applyBtnId: 'lpSegmentApplyDate',
            onApply: applySegmentDateFilter,
        });
    }

    // LOAD SOURCE
    async function loadSource(action = 'init', value = null) {
        const API_URL = '/api/leads/leads-performance';

        if (action === 'size' && value) {
            leadsPerformancePageSize = parseInt(value, 10) || DEFAULT_PAGE_SIZE;
            applyLeadRowLimit();
            return;
        }

        const params = new URLSearchParams({
            search: leadSourceSearchQuery
        });

        if (leadSourceFilter) params.append('source_id', leadSourceFilter);
        if (filterStartDateSource) params.append('start_date_source', filterStartDateSource);
        if (filterEndDateSource) params.append('end_date_source', filterEndDateSource);

        const tbody = document.getElementById('salesBodyTableLeadsPerformanceSource');
        const tfoot = document.getElementById('salesFootTableLeadsPerformanceSource');
        
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

            const leadData = result.by_source?.data || [];
            const summary = result.by_source?.summary || {};

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
                const rupiahNominalCum = formatRupiah(item.amount_cum);
                const rupiahNominalWarm = formatRupiah(item.nominal_warm);
                const rupiahNominalHot = formatRupiah(item.nominal_hot);
                const rupiahNominalDeal = formatRupiah(item.nominal_deal);

                rowsHtml += `
                    <tr data-row-index="${index}" class="border-t border-t-[#D9D9D9] text-[#1E1E1E]">
                        <td class="p-1 lg:p-3">${item.source ?? '-'}</td>
                        <td class="p-1 lg:p-3">
                            ${item.total ?? '-'}
                            <p class="  font-semibold text-xs lg:text-sm">${rupiahNominalCum}</p>
                        </td>
                        <td class="p-1 lg:p-3">
                            ${item.cold ?? '-'}
                        </td>
                        <td class="p-1 lg:p-3">
                            ${item.warm ?? '-'}
                            <p class="text-[#FCB53B] font-semibold text-xs lg:text-sm">${rupiahNominalWarm}</p>
                        </td>
                        <td class="p-1 lg:p-3">
                            ${item.hot ?? '-'}
                            <p class="text-[#F93827] font-semibold text-xs lg:text-sm">${rupiahNominalHot}</p>
                        </td>
                        <td class="p-1 lg:p-3">
                            ${item.deal ?? '-'}
                            <p class="text-[#5CB338] font-semibold text-xs lg:text-sm">${rupiahNominalDeal}</p>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = rowsHtml;

            const rupiahNominalTotalWarm = formatRupiah(summary.nominal_total_warm);
            const rupiahNominalTotalHot = formatRupiah(summary.nominal_total_hot);
            const rupiahNominalTotalDeal = formatRupiah(summary.nominal_total_deal);
            const rupiahNominalTotal = formatRupiah(summary.nominal_total);

            tfoot.innerHTML = `
                <tr class="font-semibold border-t border-t-[#D9D9D9] text-[#1E1E1E]">
                    <td class="p-2 lg:p-3">Total</td>
                    <td class="p-2 lg:p-3">${rupiahNominalTotal ?? 0}</td>
                    <td class="p-2 lg:p-3">${summary.total_cold ?? 0}</td>
                    <td class="p-2 lg:p-3">${summary.total_warm ?? 0} <span class="text-[#FCB53B] font-semibold text-xs lg:text-sm">(${rupiahNominalTotalWarm ?? 0})</span></td>
                    <td class="p-2 lg:p-3">${summary.total_hot ?? 0} <span class="text-[#F93827] font-semibold text-xs lg:text-sm">(${rupiahNominalTotalHot ?? 0})</span></td>
                    <td class="p-2 lg:p-3">${summary.total_deal ?? 0} <span class="text-[#5CB338] font-semibold text-xs lg:text-sm">(${rupiahNominalTotalDeal ?? 0})</span></td>
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

            leadSourceFilter = this.value || '';

            loadSource('filter');
        });
    }

    // LOAD SEGMENTS
    async function loadSegment(action = 'init', value = null) {
        const API_URL = '/api/leads/leads-performance';

        if (action === 'size' && value) {
            leadsPerformancePageSize = parseInt(value, 10) || DEFAULT_PAGE_SIZE;
            applySegmentRowLimit();
            return;
        }

        const params = new URLSearchParams({
            search: leadSegmentSearchQuery
        });

        if (leadSegmentFilter) params.append('segment_id', leadSegmentFilter);
        if (filterStartDateSegment) params.append('start_date_segment', filterStartDateSegment);
        if (filterEndDateSegment) params.append('end_date_segment', filterEndDateSegment);

        const tbody = document.getElementById('salesBodyTableLeadsPerformanceSegments');
        const tfoot = document.getElementById('salesFootTableLeadsPerformanceSegments');
        
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

            const leadData = result.by_segment?.data || [];
            const summary = result.by_segment?.summary || {};

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
                const rupiahNominalCum = formatRupiah(item.amount_cum);
                const rupiahNominalWarm = formatRupiah(item.nominal_warm);
                const rupiahNominalHot = formatRupiah(item.nominal_hot);
                const rupiahNominalDeal = formatRupiah(item.nominal_deal);

                rowsHtml += `
                    <tr data-row-index="${index}" class="border-t border-t-[#D9D9D9] text-[#1E1E1E]">
                        <td class="p-1 lg:p-3">${item.segment ?? '-'}</td>
                        <td class="p-1 lg:p-3">
                            ${item.total ?? '-'}
                            <p class="  font-semibold text-xs lg:text-sm">${rupiahNominalCum}</p>
                        </td>
                        <td class="p-1 lg:p-3">
                            ${item.cold ?? '-'}
                        </td>
                        <td class="p-1 lg:p-3">
                            ${item.warm ?? '-'}
                            <p class="text-[#FCB53B] font-semibold text-xs lg:text-sm">${rupiahNominalWarm}</p>
                        </td>
                        <td class="p-1 lg:p-3">
                            ${item.hot ?? '-'}
                            <p class="text-[#F93827] font-semibold text-xs lg:text-sm">${rupiahNominalHot}</p>
                        </td>
                        <td class="p-1 lg:p-3">
                            ${item.deal ?? '-'}
                            <p class="text-[#5CB338] font-semibold text-xs lg:text-sm">${rupiahNominalDeal}</p>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = rowsHtml;

            const rupiahNominalTotalWarm = formatRupiah(summary.nominal_total_warm);
            const rupiahNominalTotalHot = formatRupiah(summary.nominal_total_hot);
            const rupiahNominalTotalDeal = formatRupiah(summary.nominal_total_deal);
            const rupiahNominalTotal = formatRupiah(summary.nominal_total);

            tfoot.innerHTML = `
                <tr class="font-semibold border-t border-t-[#D9D9D9] text-[#1E1E1E]">
                    <td class="p-2 lg:p-3">Total</td>
                    <td class="p-2 lg:p-3">${rupiahNominalTotal ?? 0}</td>
                    <td class="p-2 lg:p-3">${summary.total_cold ?? 0}</td>
                    <td class="p-2 lg:p-3">${summary.total_warm ?? 0} <span class="text-[#FCB53B] font-semibold text-xs lg:text-sm">(${rupiahNominalTotalWarm ?? 0})</span></td>
                    <td class="p-2 lg:p-3">${summary.total_hot ?? 0} <span class="text-[#F93827] font-semibold text-xs lg:text-sm">(${rupiahNominalTotalHot ?? 0})</span></td>
                    <td class="p-2 lg:p-3">${summary.total_deal ?? 0} <span class="text-[#5CB338] font-semibold text-xs lg:text-sm">(${rupiahNominalTotalDeal ?? 0})</span></td>
                </tr>
            `;

            applySegmentRowLimit();

        } catch (error) {
            console.error('Load Lead Error:', error);
        }
    }

    const leadSegmentSelect = document.getElementById('salesLeadPerformanceSegments');

    if (leadSegmentSelect) {
        leadSegmentSelect.addEventListener('change', function () {

            leadSegmentFilter = this.value || '';

            loadSegment('filter');
        });
    }


    const searchInputLeadSource = document.getElementById('searchInputLeadSourceSales');

    if (searchInputLeadSource) {
        searchInputLeadSource.addEventListener('keyup', function () {

            clearTimeout(leadSourceSearchTimeout);

            leadSourceSearchTimeout = setTimeout(() => {

                leadSourceSearchQuery = this.value.trim();

                loadSource('search');

            }, 500);

        });
    }

    const searchInputLeadSegment = document.getElementById('searchInputLeadSegmentSales');

    if (searchInputLeadSegment) {
        searchInputLeadSegment.addEventListener('keyup', function () {

            clearTimeout(leadSegmentSearchTimeout);

            leadSegmentSearchTimeout = setTimeout(() => {

                leadSegmentSearchQuery = this.value.trim();

                loadSegment('search');

            }, 500);

        });
    }

    const leadsSourceResetBtn = document.getElementById('leadsSourceReset');

    if (leadsSourceResetBtn) {
        leadsSourceResetBtn.addEventListener('click', function () {
            leadSourceFilter = '';
            leadSourceSearchQuery = '';
            filterStartDateSource = '';
            filterEndDateSource = '';

            const sourceSelect = document.getElementById('salesLeadPerformanceSource');
            if (sourceSelect) sourceSelect.value = '';

            const sourceSearchInput = document.getElementById('searchInputLeadSourceSales');
            if (sourceSearchInput) sourceSearchInput.value = '';

            const sourceLabel = document.getElementById('lpSourceDateLabel');
            if (sourceLabel) sourceLabel.innerText = 'Date';

            const sourceDropdown = document.getElementById('lpSourceDateDropdown');
            if (sourceDropdown) sourceDropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');

            const sourceIcon = document.getElementById('lpSourceIconDate');
            if (sourceIcon) sourceIcon.classList.remove('rotate-180');

            if (lpSourceFp && typeof lpSourceFp.clear === 'function') {
                lpSourceFp.clear();
            }

            loadSource('filter');
        });
    }

    const leadsSegmentResetBtn = document.getElementById('leadsSegmentReset');

    if (leadsSegmentResetBtn) {
        leadsSegmentResetBtn.addEventListener('click', function () {
            leadSegmentFilter = '';
            leadSegmentSearchQuery = '';
            filterStartDateSegment = '';
            filterEndDateSegment = '';

            const segmentSelect = document.getElementById('salesLeadPerformanceSegments');
            if (segmentSelect) segmentSelect.value = '';

            const segmentSearchInput = document.getElementById('searchInputLeadSegmentSales');
            if (segmentSearchInput) segmentSearchInput.value = '';

            const segmentLabel = document.getElementById('lpSegmentDateLabel');
            if (segmentLabel) segmentLabel.innerText = 'Date';

            const segmentDropdown = document.getElementById('lpSegmentDateDropdown');
            if (segmentDropdown) segmentDropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');

            const segmentIcon = document.getElementById('lpSegmentIconDate');
            if (segmentIcon) segmentIcon.classList.remove('rotate-180');

            if (lpSegmentFp && typeof lpSegmentFp.clear === 'function') {
                lpSegmentFp.clear();
            }

            loadSegment('filter');
        });
    }
    
    function applyRowsLimit(tbodyId) {
        const tbody = document.getElementById(tbodyId);
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

    function applyLeadRowLimit() {
        applyRowsLimit('salesBodyTableLeadsPerformanceSource');
    }

    function applySegmentRowLimit() {
        applyRowsLimit('salesBodyTableLeadsPerformanceSegments');
    }

</script>
