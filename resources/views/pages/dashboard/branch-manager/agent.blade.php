{{-- AGENTS TRENDS & KPI --}}

<h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">Agent Trends & KPI</h1>

<div class="grid grid-cols-[3fr_1fr] gap-3">
    {{-- AGENTS TRENDS ACHIEVEMENT --}}
    <div class="w-full bg-white p-3 rounded-lg border border-[#D9D9D9] mt-2 mb-4">
        <div class="flex items-center justify-between gap-4 mb-4">
            <div class="grid grid-cols-4 gap-5 flex-1">
                <select id="agentFilterYear" class="border border-[#D9D9D9] rounded-lg px-3 py-2" onchange="handleAgentTrendFilterChange()">
                    <option value="2026">2026</option>
                    <option value="2025">2025</option>
                </select>
            
                <select id="agentFilterMonth" class="border border-[#D9D9D9] rounded-lg px-3 py-2" onchange="handleAgentTrendFilterChange()">
                    <option value="">Pilih Month</option>
                    <option value="1">Januari</option>
                    <option value="2">Februari</option>
                    <option value="3">Maret</option>
                    <option value="4">April</option>
                    <option value="5">Mei</option>
                    <option value="6">Juni</option>
                    <option value="7">Juli</option>
                    <option value="8">Agustus</option>
                    <option value="9">September</option>
                    <option value="10">Oktober</option>
                    <option value="11">November</option>
                    <option value="12">Desember</option>
                </select>
            
                <select id="agentFilterMonthFrom" class="border border-[#D9D9D9] rounded-lg px-3 py-2" onchange="handleAgentTrendFilterChange()">
                    <option value="">Month From</option>
                    <option value="1">Januari</option>
                    <option value="2">Februari</option>
                    <option value="3">Maret</option>
                    <option value="4">April</option>
                    <option value="5">Mei</option>
                    <option value="6">Juni</option>
                    <option value="7">Juli</option>
                    <option value="8">Agustus</option>
                    <option value="9">September</option>
                    <option value="10">Oktober</option>
                    <option value="11">November</option>
                    <option value="12">Desember</option>
                </select>
            
                <select id="agentFilterMonthTo" class="border border-[#D9D9D9] rounded-lg px-3 py-2" onchange="handleAgentTrendFilterChange()">
                    <option value="">Month To</option>
                    <option value="1">Januari</option>
                    <option value="2">Februari</option>
                    <option value="3">Maret</option>
                    <option value="4">April</option>
                    <option value="5">Mei</option>
                    <option value="6">Juni</option>
                    <option value="7">Juli</option>
                    <option value="8">Agustus</option>
                    <option value="9">September</option>
                    <option value="10">Oktober</option>
                    <option value="11">November</option>
                    <option value="12">Desember</option>
                </select>
            </div>

            <button
                id="downloadAgentGeneralTrendsPng"
                type="button"
                class="shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-[#115640] text-white rounded-lg cursor-pointer"
            >
                <x-icon.download/>
                Download PNG
            </button>
        </div>

        <div id="agentTrendsChart" class="w-full">
        </div>
    </div>

    {{-- KPI --}}
    <div class="w-full grid grid-cols-1 gap-3">

        {{-- ACHIEVEMENT VS TARGET SALE AMOUNT SECTION--}}
        <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg">

            <div class="flex justify-between items-center">
                
                <h1 class="text-[#757575] font-semibold">Agent Achievement (MTD)</h1>
                
                <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                    <x-icon.crosshair/>
                </div>
            </div>

            <div>
                <div class="mt-3 text-[#757575]">
                    <p id="achievementAgent">0</p>
                </div>

                <p id="compareAchievementAgent" class="hidden"></p>
            </div>

        </div>
        
        {{-- TOTAL ACTIVE AGENTS SECTION--}}
        <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg">

            <div class="flex justify-between items-center">
                
                <h1 class="text-[#757575] font-semibold">Total Active Agents</h1>
                
                <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                    <x-icon.users/>
                </div>
            </div>

            <div>
                <div class="mt-3 text-[#757575]">
                    <p id="totalAgent">0</p>
                    <div class="flex gap-2 mt-1">
                        <p id="totalAgentActive">Active Agents: 0</p>
                        <p id="totalAgentInactive">Inactive Agents: 0</p>
                    </div>
                </div>

                <p id="compareTotalAgent" class="hidden"></p>
            </div>

        </div>

        {{-- TOTAL LEADS AGENTS SECTION --}}
        <div class="p-3 bg-white border border-[#D9D9D9] rounded-lg">

            <div class="flex justify-between items-center">
                
                <h1 class="text-[#757575] font-semibold">Total Leads Agents</h1>
                
                <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                    <x-icon.leads/>
                </div>
            </div>

            <div>
                <div class="mt-3 text-[#757575]">
                    <p id="totalAgentLeads">0</p>
                    <p id="totalAgentAvailableLeads">Available Leads: 0</p>
                </div>

                <div class="flex items-center justify-start gap-3 mt-3">
                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full block bg-[#3F80EA]"></span>
                        <p id="agentColdLeads">0 Cold</p>
                    </div>

                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full block bg-[#E8B931]"></span>
                        <p id="agentWarmLeads">0 Warm</p>
                    </div>

                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full block bg-[#EC221F]"></span>
                        <p id="agentHotLeads">0 Hot</p>
                    </div>

                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full block bg-[#115640]"></span>
                        <p id="agentDealLeads">0 Deal</p>
                    </div>
                </div>

                <p id="compareTotalActiveAgentLeads" class="hidden"></p>
            </div>

        </div>
    </div>
</div>

{{-- AGENTS DASH INFORMATION --}}

<style>
    #agentDetailProvince + .select2-container,
    #agentDetailCity + .select2-container {
        width: 100% !important;
    }

    #agentDetailProvince + .select2-container .select2-selection,
    #agentDetailCity + .select2-container .select2-selection,
    #agentDetailProvince + .select2-container .select2-selection--single,
    #agentDetailCity + .select2-container .select2-selection--single,
    #agentDetailProvince + .select2-container.select2-container--default .select2-selection--single,
    #agentDetailCity + .select2-container.select2-container--default .select2-selection--single {
        border: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
    }
</style>

<h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">Agents Detail</h1>

{{-- TABLES AGENTS DETAIL --}}
<div class="mt-2 mb-4 bg-white rounded-lg border-r border-l border-t border-[#D9D9D9]">
    {{-- NAVIGATION TABLES --}}
    <div class="bg-white lg:grid lg:grid-cols-[1fr_3fr] border-b border-[#D9D9D9] p-2 lg:p-3 gap-4 rounded-tr-lg rounded-tl-lg sm:gap-3 grid grid-cols-1">
        {{-- SEARCH TABLES --}}
        <div class="w-full border border-gray-300 rounded-lg flex items-center p-1 lg:p-2">
            <div class="px-2">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.5 13C4.68333 13 3.14583 12.3708 1.8875 11.1125C0.629167 9.85417 0 8.31667 0 6.5C0 4.68333 0.629167 3.14583 1.8875 1.8875C3.14583 0.629167 4.68333 0 6.5 0C8.31667 0 9.85417 0.629167 11.1125 1.8875C12.3708 3.14583 13 4.68333 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.8125 10.5625 9.6875 9.6875C10.5625 8.8125 11 7.75 11 6.5C11 5.25 10.5625 4.1875 9.6875 3.3125C8.8125 2.4375 7.75 2 6.5 2C5.25 2 4.1875 2.4375 3.3125 3.3125C2.4375 4.1875 2 5.25 2 6.5C2 7.75 2.4375 8.8125 3.3125 9.6875C4.1875 10.5625 5.25 11 6.5 11Z" fill="#6B7786"/>
                </svg>
            </div>
            <input id="agentDetailSearch" type="text" placeholder="Search" class="w-full px-3 py-1 border-none focus:outline-[#115640] "/>
        </div>
        <div class="w-full grid grid-cols-5 border border-gray-300 rounded-lg p-1 lg:p-2">

            {{-- PROVINCES --}}
            <div class="flex items-center justify-center gap-2 border-r border-r-[#CFD5DC] cursor-pointer h-full px-2 text-[#1E1E1E]">
                <select id="agentDetailProvince"
                    class="select2 w-full font-semibold text-center focus:outline-none cursor-pointer">
                    <option value="">All Provinces</option>
                    @foreach ($provinces as $province)
                        <option value="{{ $province->id }}">{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- CITY --}}
            <div class="flex items-center justify-center gap-2 border-r border-r-[#CFD5DC] cursor-pointer h-full px-2 text-[#1E1E1E]">
                <select id="agentDetailCity"
                    class="select2 w-full font-semibold text-center focus:outline-none cursor-pointer">
                    <option value="">All City</option>
                    @foreach ($regions as $region)
                        <option value="{{ $region->id }}" data-province-id="{{ $region->province_id }}">{{ $region->name }}</option>
                    @endforeach
                </select>
            </div>
            
            {{-- STATUS --}}
            <div class="flex items-center justify-center gap-2 border-r border-r-[#CFD5DC] cursor-pointer h-full px-2 text-[#1E1E1E]">
                <select id="agentDetailStatus"
                    class="w-full font-semibold text-center focus:outline-none cursor-pointer">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            {{-- DATES --}}
            <div
            class="border-r border-r-[#CFD5DC] cursor-pointer w-full relative grid grid-cols-1 items-center h-full">

                {{-- TOGGLE --}}
                <div id="agentDetailOpenDateDropdown" class="flex justify-center items-center gap-2">
                    <p id="agentDetailDateLabel" class="font-medium text-black">Date</p>
                    <i id="agentDetailIconDate" class="fas fa-chevron-down transition-transform duration-300 text-black" style="font-size: 12px;"></i>
                </div>

                {{-- DATE DROPDOWN --}}
                <div id="agentDetailDateDropdown"
                    style="display: none;"
                    class="absolute top-full left-0 mt-2 bg-white rounded-lg shadow-xl w-[350px] p-4 z-50 opacity-0 scale-95 pointer-events-none transition-all duration-200 ease-out origin-top overflow-visible">

                    <h3 class="font-semibold mb-2">Select Date Range</h3>

                        <div class="flex justify-center items-center">
                        <input type="text" id="agentDetailDateRange" class="shadow-none w-full" placeholder="Select date range">
                        </div>

                    <div class="flex justify-end gap-2 mt-3">

                        <button id="agentDetailCancelDate" class="px-3 py-1 text-[#303030]">
                            Cancel
                        </button>

                        <button id="agentDetailApplyDate"
                            class="px-3 py-1 bg-[#115640] text-white rounded-lg cursor-pointer">
                            Apply
                        </button>

                    </div>
                </div>
            </div>  

            {{-- RESET FILTER --}}
            <div id="agentDetailReset" class="flex items-center justify-center gap-2 cursor-pointer h-full">
                <i id="agentDetailResetIcon" class="fa fa-redo transition-transform duration-300 text-[#900B09] -scale-x-100   " style="font-size: 12px;"></i>
                <p class="font-medium text-[#900B09]">Reset Filter</p>
            </div>
        </div>
    </div>

    {{-- CONTENTS TABLES --}}
    <div class="max-xl:overflow-x-scroll">
        <table class="w-full bg-white rounded-br-lg rounded-bl-lg">

            {{-- HEADER TABLE --}}
            <thead class="text-[#1E1E1E]">
                <tr class="border-b border-b-[#CFD5DC]">
                    <th class="hidden">ID (hidden)</th>
                    <th class="p-1 md:p-2 lg:p-3">
                        Branch Name
                    </th>
                    <th class="p-1 md:p-2 lg:p-3">
                        Agent Name
                    </th>
                    <th class="p-1 md:p-2 lg:p-3">
                        Active Month
                    </th>
                    <th class="p-1 md:p-2 lg:p-3">  
                        Regional
                    </th>
                    <th class="p-1 md:p-2 lg:p-3">
                        Province
                    </th>
                    <th class="p-1 md:p-2 lg:p-3">
                        City
                    </th>
                    <th class="p-1 md:p-2 lg:p-3">
                        Created At
                    </th>
                    <th class="p-1 md:p-2 lg:p-3">
                        Status
                    </th>
                </tr>
            </thead>
            <tbody id="agentDetailBody" class="text-[#1E1E1E]"></tbody>
            <tfoot id="agentDetailFoot"></tfoot>
        </table>
    </div>

    {{-- NAVIGATION ROWS --}}
    <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! bg-transparent border-t border-t-[#D9D9D9]">
        <div class="flex items-center gap-3">
            <p class="font-semibold">Show Rows</p>
            <select id="agentDetailPageSize" class="w-auto bg-white font-semibold p-2 rounded-md"
                onchange="loadAgentDetail('size', this.value)">
                <option value="5" selected>5</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <div id="agentDetailShowing" class="font-semibold">Showing 0-0 of 0</div>
            <div>
                <button id="agentDetailPrevBtn"
                    class="btn btn bg-white border! border-[#D9D9D9]! cursor-pointer!"
                    onclick="loadAgentDetail('prev')">
                    <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
                </button>
                <button id="agentDetailNextBtn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!"
                    onclick="loadAgentDetail('next')">
                    <i class="fas fa-chevron-right text-black" style="font-size: 12px;"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let agentTrendsChart = null;
    const agentDetailRegions = @json($regions ?? []);
    const agentExportBranchLabel = @json(auth()->user()?->branch?->name ?? 'Current Branch');
    let agentDetailDatePicker = null;
    let agentDetailPage = 1;
    let agentDetailTotal = 0;
    let agentDetailPageSize = 5;
    let agentDetailSearchQuery = '';
    let agentDetailSearchTimeout = null;
    let agentDetailStartDate = '';
    let agentDetailEndDate = '';

    function formatAgentRupiah(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(Number(value || 0));
    }

    function formatAgentRupiahShort(value) {
        const amount = Number(value || 0);

        if (amount >= 1000000000) return 'Rp ' + (amount / 1000000000).toFixed(1) + ' M';
        if (amount >= 1000000) return 'Rp ' + (amount / 1000000).toFixed(1) + ' Jt';
        if (amount >= 1000) return 'Rp ' + (amount / 1000).toFixed(1) + ' Rb';

        return 'Rp ' + amount.toLocaleString('id-ID');
    }

    function normalizeAgentTrendMonthRange(monthFrom, monthTo) {
        let from = monthFrom ? parseInt(monthFrom, 10) : null;
        let to = monthTo ? parseInt(monthTo, 10) : null;

        if (from && !to) {
            to = from;
        } else if (!from && to) {
            from = to;
        }

        if (from && to && from > to) {
            [from, to] = [to, from];
        }

        return {
            monthFrom: from ? String(from) : '',
            monthTo: to ? String(to) : '',
        };
    }

    function getAgentSelectedOptionLabel(selectId, fallbackLabel) {
        const select = document.getElementById(selectId);

        if (!select) {
            return fallbackLabel;
        }

        const selectedOption = select.options[select.selectedIndex];
        const label = selectedOption?.text?.trim() || fallbackLabel;

        return label || fallbackLabel;
    }

    function sanitizeAgentExportFilenamePart(value) {
        return String(value || '')
            .replace(/[\\/:*?"<>|]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function getAgentTrendsExportFilename() {
        const branchName = sanitizeAgentExportFilenamePart(agentExportBranchLabel);
        const salesName = sanitizeAgentExportFilenamePart(
            getAgentSelectedOptionLabel('salesQuery', 'All Sales')
        );
        const now = new Date();
        const timestamp = [
            now.getFullYear(),
            String(now.getMonth() + 1).padStart(2, '0'),
            String(now.getDate()).padStart(2, '0')
        ].join('-') + '_' + [
            String(now.getHours()).padStart(2, '0'),
            String(now.getMinutes()).padStart(2, '0'),
            String(now.getSeconds()).padStart(2, '0')
        ].join('-');

        return `Agent Trends - ${branchName} - ${salesName} - ${timestamp}`;
    }

    async function downloadAgentTrendsAsPng() {
        if (!agentTrendsChart) {
            return;
        }

        try {
            const result = await agentTrendsChart.dataURI();
            const link = document.createElement('a');

            link.href = result.imgURI;
            link.download = `${getAgentTrendsExportFilename()}.png`;
            document.body.appendChild(link);
            link.click();
            link.remove();
        } catch (error) {
            console.error('Error downloadAgentTrendsAsPng:', error);
        }
    }

    function renderAgentTrendsChart(agentTrends) {
        const chartElement = document.querySelector('#agentTrendsChart');

        if (!chartElement || typeof ApexCharts === 'undefined') {
            return;
        }

        const labels = Array.isArray(agentTrends?.labels) ? agentTrends.labels : [];
        const series = Array.isArray(agentTrends?.series) ? agentTrends.series : [];

        if (agentTrendsChart) {
            agentTrendsChart.destroy();
            agentTrendsChart = null;
        }

        agentTrendsChart = new ApexCharts(chartElement, {
            chart: {
                type: 'line',
                height: 380,
                toolbar: {
                    show: true,
                    export: {
                        png: {
                            filename: getAgentTrendsExportFilename()
                        }
                    }
                },
                zoom: {
                    enabled: false
                }
            },
            series: series,
            xaxis: {
                categories: labels,
                title: {
                    text: String(agentTrends?.group_by || 'month').toUpperCase()
                }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return formatAgentRupiahShort(value);
                    }
                }
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            markers: {
                size: 4
            },
            dataLabels: {
                enabled: false
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return formatAgentRupiah(value);
                    }
                }
            },
            legend: {
                position: 'top'
            },
            noData: {
                text: 'Belum ada data'
            }
        });

        agentTrendsChart.render();
    }

    function toAgentNumber(value) {
        const number = Number(value);

        return Number.isFinite(number) ? number : 0;
    }

    function formatAgentNumber(value) {
        return toAgentNumber(value).toLocaleString('id-ID');
    }

    function formatAgentCompareDateLabel(value) {
        if (!value) {
            return '';
        }

        const date = new Date(value + 'T00:00:00');
        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short'
        });
    }

    function formatAgentCompareDateRangeLabel(compareData) {
        return '['
            + formatAgentCompareDateLabel(compareData.start_date)
            + ' - '
            + formatAgentCompareDateLabel(compareData.end_date)
            + ']';
    }

    function formatAgentComparePart(metric, formatter = formatAgentNumber, suffix = '') {
        if (!metric) {
            return null;
        }

        const delta = toAgentNumber(metric.delta);
        const sign = delta > 0 ? '+' : (delta < 0 ? '-' : '');

        return `<span class="font-bold">${sign}${formatter(Math.abs(delta))}</span>${suffix}`;
    }

    function renderAgentCompareParts(elementId, compareData, parts) {
        const element = document.getElementById(elementId);
        if (!element) {
            return;
        }

        if (!compareData?.enabled || !compareData?.start_date || !compareData?.end_date) {
            element.innerHTML = '';
            element.className = 'hidden';
            return;
        }

        const formattedParts = parts
            .map(function (part) {
                return formatAgentComparePart(part.metric, part.formatter, part.suffix || '');
            })
            .filter(Boolean);

        if (!formattedParts.length) {
            element.innerHTML = '';
            element.className = 'hidden';
            return;
        }

        const firstDelta = toAgentNumber(parts[0]?.metric?.delta);
        const toneClass = firstDelta > 0
            ? 'text-[#009951]'
            : (firstDelta < 0 ? 'text-[#900B09]' : 'text-[#757575]');

        element.innerHTML = formattedParts.join(' | ') + ' ' + formatAgentCompareDateRangeLabel(compareData);
        element.className = 'text-xs mt-2 leading-5 break-words ' + toneClass;
    }

    function renderAgentSummaryKpi(result) {
        const achievement = Number(result?.kpi?.agent_achievement?.achievement_amount || 0);
        const totalActive = result?.kpi?.total_active || {};
        const agentLeads = result?.kpi?.total_agent_leads || {};
        const total = Number(totalActive.total || 0);
        const active = Number(totalActive.active || 0);
        const inactive = Number(totalActive.inactive || 0);
        const totalAgentLeads = Number(agentLeads.total || 0);
        const totalAgentAvailableLeads = Number(agentLeads.published || 0);
        const agentColdLeads = Number(agentLeads.cold || 0);
        const agentWarmLeads = Number(agentLeads.warm || 0);
        const agentHotLeads = Number(agentLeads.hot || 0);
        const agentDealLeads = Number(agentLeads.deal || 0);

        const achievementElement = document.getElementById('achievementAgent');
        const totalElement = document.getElementById('totalAgent');
        const activeElement = document.getElementById('totalAgentActive');
        const inactiveElement = document.getElementById('totalAgentInactive');
        const totalAgentLeadsElement = document.getElementById('totalAgentLeads');
        const totalAgentAvailableLeadsElement = document.getElementById('totalAgentAvailableLeads');
        const agentColdLeadsElement = document.getElementById('agentColdLeads');
        const agentWarmLeadsElement = document.getElementById('agentWarmLeads');
        const agentHotLeadsElement = document.getElementById('agentHotLeads');
        const agentDealLeadsElement = document.getElementById('agentDealLeads');

        if (achievementElement) {
            achievementElement.textContent = formatAgentRupiah(achievement);
            achievementElement.className = 'font-semibold text-xl xl:text-2xl text-[#1E1E1E]';
        }

        if (totalElement) {
            totalElement.textContent = `${total.toLocaleString('id-ID')} Agents`;
            totalElement.className = 'font-semibold text-xl xl:text-2xl text-[#1E1E1E]';
        }

        if (activeElement) {
            activeElement.textContent = `Active Agents: ${active.toLocaleString('id-ID')}`;
            activeElement.className = 'text-xs';
        }

        if (inactiveElement) {
            inactiveElement.textContent = `Inactive Agents: ${inactive.toLocaleString('id-ID')}`;
            inactiveElement.className = 'text-xs';
        }

        if (totalAgentLeadsElement) {
            totalAgentLeadsElement.textContent = totalAgentLeads.toLocaleString('id-ID');
            totalAgentLeadsElement.className = 'font-semibold text-[#1E1E1E] text-xl xl:text-2xl';
        }

        if (totalAgentAvailableLeadsElement) {
            totalAgentAvailableLeadsElement.textContent = `Available Leads: ${totalAgentAvailableLeads.toLocaleString('id-ID')}`;
            totalAgentAvailableLeadsElement.className = 'text-xs';
        }

        if (agentColdLeadsElement) {
            agentColdLeadsElement.textContent = `${agentColdLeads.toLocaleString('id-ID')} Cold`;
            agentColdLeadsElement.className = 'text-xs';
        }

        if (agentWarmLeadsElement) {
            agentWarmLeadsElement.textContent = `${agentWarmLeads.toLocaleString('id-ID')} Warm`;
            agentWarmLeadsElement.className = 'text-xs';
        }

        if (agentHotLeadsElement) {
            agentHotLeadsElement.textContent = `${agentHotLeads.toLocaleString('id-ID')} Hot`;
            agentHotLeadsElement.className = 'text-xs';
        }

        if (agentDealLeadsElement) {
            agentDealLeadsElement.textContent = `${agentDealLeads.toLocaleString('id-ID')} Deal`;
            agentDealLeadsElement.className = 'text-xs';
        }

        const compareData = result?.compare || {};
        const compareKpi = compareData?.agent_kpi || {};

        renderAgentCompareParts('compareAchievementAgent', compareData, [
            { metric: compareKpi.achievement_amount, formatter: formatAgentRupiah }
        ]);
        renderAgentCompareParts('compareTotalAgent', compareData, [
            { metric: compareKpi.total_agents_total, suffix: ' agents' },
            { metric: compareKpi.total_agents_active, suffix: ' active' },
            { metric: compareKpi.total_agents_inactive, suffix: ' inactive' }
        ]);
        renderAgentCompareParts('compareTotalActiveAgentLeads', compareData, [
            { metric: compareKpi.active_agent_leads_total, suffix: ' total' },
            { metric: compareKpi.active_agent_leads_published, suffix: ' available' },
            { metric: compareKpi.active_agent_leads_cold, suffix: ' cold' },
            { metric: compareKpi.active_agent_leads_warm, suffix: ' warm' },
            { metric: compareKpi.active_agent_leads_hot, suffix: ' hot' },
            { metric: compareKpi.active_agent_leads_deal, suffix: ' deal' }
        ]);
    }

    function escapeAgentDetailHtml(value) {
        const element = document.createElement('div');
        element.textContent = value ?? '-';

        return element.innerHTML;
    }

    function getAgentDetailRegions() {
        return Array.isArray(agentDetailRegions)
            ? agentDetailRegions
            : Object.values(agentDetailRegions || {});
    }

    function renderAgentDetailCityOptions(selectedValue = '') {
        const citySelect = document.getElementById('agentDetailCity');
        const provinceSelect = document.getElementById('agentDetailProvince');

        if (!citySelect) {
            return;
        }

        const provinceId = provinceSelect?.value || '';
        const currentValue = selectedValue || citySelect.value || '';
        const regions = getAgentDetailRegions().filter(function (region) {
            return !provinceId || String(region.province_id || '') === String(provinceId);
        });

        citySelect.innerHTML = '';
        citySelect.appendChild(new Option('All City', ''));

        regions.forEach(function (region) {
            const option = new Option(region.name || '-', region.id || '');
            option.dataset.provinceId = region.province_id || '';
            citySelect.appendChild(option);
        });

        const hasCurrentValue = Array.from(citySelect.options).some(function (option) {
            return option.value === String(currentValue);
        });

        citySelect.value = hasCurrentValue ? String(currentValue) : '';

        if (window.jQuery && jQuery.fn?.select2) {
            jQuery(citySelect).trigger('change.select2');
        }
    }

    function setAgentDetailTableMessage(message) {
        const tbody = document.getElementById('agentDetailBody');
        const tfoot = document.getElementById('agentDetailFoot');

        if (tfoot) {
            tfoot.innerHTML = '';
        }

        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-3 text-[#1E1E1E]">
                        ${escapeAgentDetailHtml(message)}
                    </td>
                </tr>
            `;
        }
    }

    function renderAgentDetailRows(rows) {
        const tbody = document.getElementById('agentDetailBody');
        const tfoot = document.getElementById('agentDetailFoot');

        if (!tbody) {
            return;
        }

        if (tfoot) {
            tfoot.innerHTML = '';
        }

        if (!rows.length) {
            setAgentDetailTableMessage('No data found');
            return;
        }

        tbody.innerHTML = rows.map(function (item) {
            const isActive = Number(item.is_active || 0) === 1;
            const statusClass = isActive ? 'status-deal' : 'status-trash';

            return `
                <tr class="border-t border-t-[#D9D9D9]">
                    <td class="hidden">${escapeAgentDetailHtml(item.id)}</td>
                    <td class="p-1 lg:p-3">${escapeAgentDetailHtml(item.branch_name)}</td>
                    <td class="p-1 lg:p-3">${escapeAgentDetailHtml(item.agent_name)}</td>
                    <td class="p-1 lg:p-3">${escapeAgentDetailHtml(item.active_month)}</td>
                    <td class="p-1 lg:p-3">${escapeAgentDetailHtml(item.regional)}</td>
                    <td class="p-1 lg:p-3">${escapeAgentDetailHtml(item.province_name)}</td>
                    <td class="p-1 lg:p-3">${escapeAgentDetailHtml(item.city_name)}</td>
                    <td class="p-1 lg:p-3">${escapeAgentDetailHtml(item.created_at)}</td>
                    <td class="p-1 lg:p-3">
                        <span class="inline-block lg:px-2 lg:py-1 rounded-sm ${statusClass}">
                            ${escapeAgentDetailHtml(item.status_name)}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');

        if (tfoot) {
            tfoot.innerHTML = `
                <tr class="font-semibold border-t-[#D9D9D9] border-t text-[#1E1E1E]">
                    <td class="hidden"></td>
                    <td class="p-2 lg:p-3">Total</td>
                    <td class="p-2 lg:p-3"></td>
                    <td class="p-2 lg:p-3"></td>
                    <td class="p-2 lg:p-3"></td>
                    <td class="p-2 lg:p-3"></td>
                    <td class="p-2 lg:p-3"></td>
                    <td class="p-2 lg:p-3">${agentDetailTotal.toLocaleString('id-ID')} Agents</td>
                    <td class="p-2 lg:p-3"></td>
                </tr>
            `;
        }
    }

    function updateAgentDetailPagination(pagination) {
        agentDetailPage = Number(pagination.current_page || 1);
        agentDetailPageSize = Number(pagination.per_page || agentDetailPageSize || 5);
        agentDetailTotal = Number(pagination.total || 0);

        const prevBtn = document.getElementById('agentDetailPrevBtn');
        const nextBtn = document.getElementById('agentDetailNextBtn');
        const showing = document.getElementById('agentDetailShowing');
        const pageSizeSelect = document.getElementById('agentDetailPageSize');
        const lastPage = Number(pagination.last_page || 1);
        const from = Number(pagination.from || 0);
        const to = Number(pagination.to || 0);

        if (prevBtn) prevBtn.disabled = agentDetailPage <= 1;
        if (nextBtn) nextBtn.disabled = agentDetailPage >= lastPage;
        if (showing) showing.innerText = `Showing ${from}-${to} of ${agentDetailTotal}`;
        if (pageSizeSelect && pageSizeSelect.value !== String(agentDetailPageSize)) {
            pageSizeSelect.value = String(agentDetailPageSize);
        }
    }

    async function loadAgentDetail(action = 'init', value = null) {
        const API_URL = '/api/dashboard/bm/agent-summary';

        if (action === 'filter' || action === 'search') {
            agentDetailPage = 1;
        }

        if (action === 'prev' && agentDetailPage > 1) {
            agentDetailPage--;
        }

        if (action === 'next') {
            agentDetailPage++;
        }

        if (action === 'size' && value) {
            agentDetailPageSize = parseInt(value, 10) || 5;
            agentDetailPage = 1;
        }

        const params = new URLSearchParams({
            page: agentDetailPage,
            per_page: agentDetailPageSize,
            search: agentDetailSearchQuery
        });

        const provinceId = document.getElementById('agentDetailProvince')?.value || '';
        const regionId = document.getElementById('agentDetailCity')?.value || '';
        const status = document.getElementById('agentDetailStatus')?.value || '';

        if (provinceId) params.append('province_id', provinceId);
        if (regionId) params.append('region_id', regionId);
        if (status !== '') params.append('status', status);
        if (agentDetailStartDate) params.append('start_date', agentDetailStartDate);
        if (agentDetailEndDate) params.append('end_date', agentDetailEndDate);

        if (typeof applySuperAdminGeneralFilterToParams === 'function') {
            applySuperAdminGeneralFilterToParams(params, {
                withBranch: false,
                withSales: true
            });
        }

        try {
            setAgentDetailTableMessage('Loading data...');

            const response = await fetch(`${API_URL}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const result = await response.json();

            if (!result || result.status !== 'success') {
                throw new Error('API returned failed status');
            }

            const agentDetail = result['agent-detail'] || {};
            const rows = Array.isArray(agentDetail.data) ? agentDetail.data : [];
            const pagination = agentDetail.pagination || {};

            updateAgentDetailPagination(pagination);
            renderAgentDetailRows(rows);
        } catch (error) {
            console.error('Load Agent Detail Error:', error);
            setAgentDetailTableMessage('Failed to load data');
        }
    }

    function closeAgentDetailDateDropdown() {
        const dropdown = document.getElementById('agentDetailDateDropdown');
        const icon = document.getElementById('agentDetailIconDate');

        if (dropdown) {
            dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
            dropdown.style.display = 'none';
        }

        if (icon) {
            icon.classList.remove('rotate-180');
        }
    }

    function toggleAgentDetailDateDropdown() {
        const dropdown = document.getElementById('agentDetailDateDropdown');
        const icon = document.getElementById('agentDetailIconDate');

        if (!dropdown) {
            return;
        }

        const shouldOpen = dropdown.style.display === 'none' || dropdown.classList.contains('opacity-0');

        if (shouldOpen) {
            dropdown.style.display = 'block';
            dropdown.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');

            if (icon) {
                icon.classList.add('rotate-180');
            }

            return;
        }

        closeAgentDetailDateDropdown();
    }

    function initAgentDetailDateFilter() {
        const openBtn = document.getElementById('agentDetailOpenDateDropdown');
        const dropdown = document.getElementById('agentDetailDateDropdown');
        const icon = document.getElementById('agentDetailIconDate');
        const input = document.getElementById('agentDetailDateRange');
        const cancelBtn = document.getElementById('agentDetailCancelDate');
        const applyBtn = document.getElementById('agentDetailApplyDate');

        if (!openBtn || !dropdown || !input || !cancelBtn || !applyBtn || typeof flatpickr === 'undefined') {
            return;
        }

        agentDetailDatePicker = flatpickr(input, {
            mode: 'range',
            inline: true,
            dateFormat: 'Y-m-d'
        });

        openBtn.addEventListener('click', function () {
            toggleAgentDetailDateDropdown();
        });

        cancelBtn.addEventListener('click', function () {
            closeAgentDetailDateDropdown();
        });

        applyBtn.addEventListener('click', function () {
            const dates = agentDetailDatePicker?.selectedDates || [];

            if (dates.length !== 2) {
                return;
            }

            agentDetailStartDate = agentDetailDatePicker.formatDate(dates[0], 'Y-m-d');
            agentDetailEndDate = agentDetailDatePicker.formatDate(dates[1], 'Y-m-d');

            const label = document.getElementById('agentDetailDateLabel');
            if (label) {
                label.innerText = `${agentDetailStartDate} -> ${agentDetailEndDate}`;
            }

            closeAgentDetailDateDropdown();
            loadAgentDetail('filter');
        });
    }

    function initAgentDetailSelect2() {
        if (!window.jQuery || !jQuery.fn?.select2) {
            return;
        }

        jQuery('#agentDetailProvince, #agentDetailCity').select2({
            width: '100%',
            dropdownCssClass: 'select2-dropdown-modern'
        });
    }

    function bindAgentDetailSelectChange(selector, callback) {
        const element = document.querySelector(selector);

        if (!element) {
            return;
        }

        if (window.jQuery) {
            jQuery(element).on('change', callback);
        } else {
            element.addEventListener('change', callback);
        }
    }

    function resetAgentDetailFilters() {
        agentDetailSearchQuery = '';
        agentDetailStartDate = '';
        agentDetailEndDate = '';
        agentDetailPage = 1;
        agentDetailPageSize = 5;

        const searchInput = document.getElementById('agentDetailSearch');
        const provinceSelect = document.getElementById('agentDetailProvince');
        const citySelect = document.getElementById('agentDetailCity');
        const statusSelect = document.getElementById('agentDetailStatus');
        const dateLabel = document.getElementById('agentDetailDateLabel');
        const pageSizeSelect = document.getElementById('agentDetailPageSize');

        if (searchInput) searchInput.value = '';
        if (provinceSelect) provinceSelect.value = '';
        if (statusSelect) statusSelect.value = '';
        if (pageSizeSelect) pageSizeSelect.value = '5';
        if (dateLabel) dateLabel.innerText = 'Date';

        renderAgentDetailCityOptions('');

        if (citySelect) {
            citySelect.value = '';
        }

        if (agentDetailDatePicker && typeof agentDetailDatePicker.clear === 'function') {
            agentDetailDatePicker.clear();
        }

        if (window.jQuery && jQuery.fn?.select2) {
            jQuery('#agentDetailProvince, #agentDetailCity').val('').trigger('change.select2');
        }

        closeAgentDetailDateDropdown();
        loadAgentDetail('filter');
    }

    function initAgentDetailFilters() {
        renderAgentDetailCityOptions();
        initAgentDetailSelect2();
        initAgentDetailDateFilter();

        bindAgentDetailSelectChange('#agentDetailProvince', function () {
            renderAgentDetailCityOptions('');
            loadAgentDetail('filter');
        });

        bindAgentDetailSelectChange('#agentDetailCity', function () {
            loadAgentDetail('filter');
        });

        bindAgentDetailSelectChange('#agentDetailStatus', function () {
            loadAgentDetail('filter');
        });

        const searchInput = document.getElementById('agentDetailSearch');
        if (searchInput) {
            searchInput.addEventListener('keyup', function () {
                clearTimeout(agentDetailSearchTimeout);

                agentDetailSearchTimeout = setTimeout(function () {
                    agentDetailSearchQuery = searchInput.value.trim();
                    loadAgentDetail('search');
                }, 500);
            });
        }

        const resetBtn = document.getElementById('agentDetailReset');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                resetAgentDetailFilters();
            });
        }
    }

    async function loadAgentSummary() {
        try {
            const year = document.getElementById('agentFilterYear')?.value || '';
            const month = document.getElementById('agentFilterMonth')?.value || '';
            const normalizedRange = normalizeAgentTrendMonthRange(
                document.getElementById('agentFilterMonthFrom')?.value || '',
                document.getElementById('agentFilterMonthTo')?.value || ''
            );
            const monthFrom = normalizedRange.monthFrom;
            const monthTo = normalizedRange.monthTo;
            const monthFromSelect = document.getElementById('agentFilterMonthFrom');
            const monthToSelect = document.getElementById('agentFilterMonthTo');

            if (monthFromSelect && monthFromSelect.value !== monthFrom) {
                monthFromSelect.value = monthFrom;
            }

            if (monthToSelect && monthToSelect.value !== monthTo) {
                monthToSelect.value = monthTo;
            }

            const params = new URLSearchParams();

            if (year) params.append('year', year);
            if (month) {
                params.append('month', month);
            } else if (monthFrom && monthTo) {
                params.append('month_from', monthFrom);
                params.append('month_to', monthTo);
            }

            if (typeof applySuperAdminGeneralFilterToParams === 'function') {
                applySuperAdminGeneralFilterToParams(params, {
                    withBranch: false,
                    withSales: true,
                    withGridDate: true,
                    withCompareDate: true
                });
            }

            const apiUrl = params.toString()
                ? `/api/dashboard/bm/agent-summary?${params.toString()}`
                : '/api/dashboard/bm/agent-summary';
            const response = await fetch(apiUrl);

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const result = await response.json();

            if (result.status !== 'success') {
                throw new Error('API returned failed status');
            }

            renderAgentTrendsChart(result.agent_trends || {});
            renderAgentSummaryKpi(result);
        } catch (error) {
            console.error('Error loadAgentSummary:', error);
        }
    }

    function handleAgentTrendFilterChange() {
        const month = document.getElementById('agentFilterMonth');
        const monthFrom = document.getElementById('agentFilterMonthFrom');
        const monthTo = document.getElementById('agentFilterMonthTo');

        if (!month || !monthFrom || !monthTo) {
            loadAgentSummary();
            return;
        }

        if (document.activeElement === month && month.value) {
            monthFrom.value = '';
            monthTo.value = '';
        }

        if ((document.activeElement === monthFrom || document.activeElement === monthTo) && (monthFrom.value || monthTo.value)) {
            month.value = '';
        }

        loadAgentSummary();
    }

    window.addEventListener('super-admin-general-filter-change', function () {
        loadAgentSummary();
        loadAgentDetail('filter');
    });

    document.addEventListener('DOMContentLoaded', function () {
        const downloadButton = document.getElementById('downloadAgentGeneralTrendsPng');

        if (downloadButton) {
            downloadButton.addEventListener('click', function () {
                downloadAgentTrendsAsPng();
            });
        }

        initAgentDetailFilters();
        loadAgentSummary();
        loadAgentDetail();
    });
</script>
