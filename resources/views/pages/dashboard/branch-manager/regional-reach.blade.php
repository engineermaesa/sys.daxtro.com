<h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">Regional Reach Overview</h1>

<div class="grid grid-cols-1 2xl:grid-cols-3 gap-3 mt-2">
    <div class="bg-white p-3 border border-[#D9D9D9] rounded-lg">
        <div class="flex items-center justify-between">
            <h1 class="text-[#757575] font-semibold">Top 10 Provinces by Lead Volume</h1>

            <button id="triggerTopProvinces" type="button"
                class="p-3 border border-[#D9D9D9] rounded-md text-[#417866] cursor-pointer duration-200 hover:bg-gray-200">
                <x-icon.bar-charts/>
            </button>
        </div>

        <div id="regionalReachChart" class="mt-4"></div>
    </div>

    <div class="bg-white p-3 border border-[#D9D9D9] rounded-lg">
        <div class="flex items-center justify-between">
            <h1 class="text-[#757575] font-semibold">Province Coverage</h1>

            <button id="triggerProvinceCoverage" type="button"
                class="p-3 border border-[#D9D9D9] rounded-md text-[#417866] cursor-pointer duration-200 hover:bg-gray-200">
                <x-icon.globe/>
            </button>
        </div>

        <div class="triggerModalTable">
            <div class="mt-3 text-[#757575]">
                <p id="achievementRegion">0/</p>
                <p id="totalRegion">0</p>
            </div>

            <div class="flex items-center justify-start gap-2 mt-3">
                <p id="percentageAchievementRegion">0%</p>
                <p class="text-[#1E1E1E] text-xs">Achievement</p>
            </div>
        </div>
    </div>

    <div class="bg-white p-3 border border-[#D9D9D9] rounded-lg">
        <div class="flex items-center justify-between">
            <h1 class="text-[#757575] font-semibold">City Coverage</h1>

            <button id="triggerMappingBranch" type="button"
                class="p-3 border border-[#D9D9D9] rounded-md text-[#417866] cursor-pointer duration-200 hover:bg-gray-200">
                <x-icon.location/>
            </button>
        </div>

        <div class="triggerModalTable">
            <div class="mt-3 text-[#757575]">
                <p id="achievementCity">0/</p>
                <p id="totalCity">0</p>
            </div>

            <div class="flex items-center justify-start gap-2 mt-3">
                <p id="percentageAchievementCity">0%</p>
                <p class="text-[#1E1E1E] text-xs">Achievement</p>
            </div>
        </div>
    </div>
</div>

{{-- TOP PROVINCE MODAL --}}
<div class="modal fade" id="topProvincesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-[#1E1E1E] text-lg font-semibold">Top 10 Provinces</h5>
                <button type="button" class="close cursor-pointer" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-3">
                    <div class="w-full border border-gray-300 rounded-lg flex items-center p-2">
                        <input id="topProvincesSearch" type="text" placeholder="Search lead, sales, source, province, city"
                            class="w-full px-2 py-1 border-none focus:outline-none">
                    </div>

                    <select id="topProvincesProvinceFilter"
                        class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 focus:outline-none">
                        <option value="">All Provinces</option>
                    </select>

                    <select id="topProvincesRegionFilter"
                        class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 focus:outline-none">
                        <option value="">All Cities</option>
                    </select>
                </div>

                <div class="border border-[#D9D9D9] rounded-lg mb-3 overflow-x-auto">
                    <table class="w-full bg-white">
                        <thead class="text-[#1E1E1E]">
                            <tr class="border-b border-b-[#D9D9D9]">
                                <th class="p-2 lg:p-3">Province</th>
                                <th class="p-2 lg:p-3">City</th>
                                <th class="p-2 lg:p-3">Lead</th>
                                <th class="p-2 lg:p-3">Branch</th>
                                <th class="p-2 lg:p-3">Sales</th>
                                <th class="p-2 lg:p-3">Source</th>
                                <th class="p-2 lg:p-3">Stage</th>
                                <th class="p-2 lg:p-3">Claimed At</th>
                            </tr>
                        </thead>
                        <tbody id="topProvincesTableBody" class="text-[#1E1E1E]"></tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! bg-transparent">
                    <div class="flex items-center gap-3">
                        <p class="font-semibold">Show Rows</p>
                        <select id="topProvincesPageSize" class="w-auto bg-white font-semibold p-2 rounded-md">
                            <option value="5" selected>5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <div id="topProvincesShowing" class="font-semibold">Showing 0-0 of 0</div>
                        <div>
                            <button id="topProvincesPrevBtn"
                                class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!">
                                <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
                            </button>
                            <button id="topProvincesNextBtn"
                                class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!">
                                <i class="fas fa-chevron-right text-black" style="font-size: 12px;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- COVERAGE MODAL --}}
<div class="modal fade" id="coverageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-[#1E1E1E] text-lg font-semibold">Province Coverage</h5>
                <button type="button" class="close cursor-pointer" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-3">
                    <div class="w-full border border-gray-300 rounded-lg flex items-center p-2">
                        <input id="coverageSearch" type="text" placeholder="Search lead, sales, source, province, city"
                            class="w-full px-2 py-1 border-none focus:outline-none">
                    </div>

                    <select id="coverageProvinceFilter"
                        class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 focus:outline-none">
                        <option value="">All Provinces</option>
                    </select>

                    <select id="coverageRegionFilter"
                        class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 focus:outline-none">
                        <option value="">All Cities</option>
                    </select>
                </div>

                <div class="border border-[#D9D9D9] rounded-lg mb-3 overflow-x-auto">
                    <table class="w-full bg-white">
                        <thead class="text-[#1E1E1E]">
                            <tr class="border-b border-b-[#D9D9D9]">
                                <th class="p-2 lg:p-3">Province</th>
                                <th class="p-2 lg:p-3">City</th>
                                <th class="p-2 lg:p-3">Lead</th>
                                <th class="p-2 lg:p-3">Branch</th>
                                <th class="p-2 lg:p-3">Sales</th>
                                <th class="p-2 lg:p-3">Source</th>
                                <th class="p-2 lg:p-3">Stage</th>
                                <th class="p-2 lg:p-3">Claimed At</th>
                            </tr>
                        </thead>
                        <tbody id="coverageTableBody" class="text-[#1E1E1E]"></tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! bg-transparent">
                    <div class="flex items-center gap-3">
                        <p class="font-semibold">Show Rows</p>
                        <select id="coveragePageSize" class="w-auto bg-white font-semibold p-2 rounded-md">
                            <option value="5" selected>5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <div id="coverageShowing" class="font-semibold">Showing 0-0 of 0</div>
                        <div>
                            <button id="coveragePrevBtn"
                                class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!">
                                <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
                            </button>
                            <button id="coverageNextBtn"
                                class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!">
                                <i class="fas fa-chevron-right text-black" style="font-size: 12px;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MAPPING MODAL --}}
<div class="modal fade" id="mappingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-[#1E1E1E] text-lg font-semibold">Branch Mapping</h5>
                <button type="button" class="close cursor-pointer" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="grid grid-cols-4 gap-3 mb-3">
                    <div class="w-full border border-gray-300 rounded-lg flex items-center p-2 lg:col-span-1">
                        <input id="mappingSearch" type="text" placeholder="Search branch, province, city"
                            class="w-full px-2 py-1 border-none focus:outline-none">
                    </div>

                    <select id="mappingBranchFilter"
                        class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 focus:outline-none">
                        <option value="">All Branches</option>
                    </select>

                    <select id="mappingProvinceFilter"
                        class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 focus:outline-none">
                        <option value="">All Provinces</option>
                    </select>

                    <select id="mappingRegionFilter"
                        class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 focus:outline-none">
                        <option value="">All Cities</option>
                    </select>
                </div>

                <div class="border border-[#D9D9D9] rounded-lg mb-3 overflow-x-auto">
                    <table class="w-full bg-white">
                        <thead class="text-[#1E1E1E]">
                            <tr class="border-b border-b-[#D9D9D9]">
                                <th class="p-2 lg:p-3">Branch</th>
                                <th class="p-2 lg:p-3">Province</th>
                                <th class="p-2 lg:p-3">City</th>
                            </tr>
                        </thead>
                        <tbody id="mappingTableBody" class="text-[#1E1E1E]"></tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! bg-transparent">
                    <div class="flex items-center gap-3">
                        <p class="font-semibold">Show Rows</p>
                        <select id="mappingPageSize" class="w-auto bg-white font-semibold p-2 rounded-md">
                            <option value="5" selected>5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <div id="mappingShowing" class="font-semibold">Showing 0-0 of 0</div>
                        <div>
                            <button id="mappingPrevBtn"
                                class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!">
                                <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
                            </button>
                            <button id="mappingNextBtn"
                                class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!">
                                <i class="fas fa-chevron-right text-black" style="font-size: 12px;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let regionalReachChart = null;
    let regionalReachRequestId = 0;
    let regionalReachTopSummary = [];
    let regionalReachCoverageSummary = {
        total_provinces: 0,
        reached_provinces: 0,
        total_cities: 0,
        reached_cities: 0
    };

    const regionalReachModalRequestIds = {
        top: 0,
        coverage: 0,
        mapping: 0,
    };

    const regionalReachModalState = {
        top: {
            page: 1,
            perPage: 5,
            lastPage: 1,
            search: '',
            province: '',
            region: '',
        },
        coverage: {
            page: 1,
            perPage: 5,
            lastPage: 1,
            search: '',
            province: '',
            region: '',
        },
        mapping: {
            page: 1,
            perPage: 5,
            lastPage: 1,
            search: '',
            branch: '',
            province: '',
            region: '',
        }
    };

    const regionalReachSearchTimers = {
        top: null,
        coverage: null,
        mapping: null,
    };

    const regionalReachModalConfig = {
        top: {
            blockKey: 'top_10_provinces',
            modalId: 'topProvincesModal',
            triggerId: 'triggerTopProvinces',
            searchId: 'topProvincesSearch',
            provinceId: 'topProvincesProvinceFilter',
            regionId: 'topProvincesRegionFilter',
            pageSizeId: 'topProvincesPageSize',
            prevId: 'topProvincesPrevBtn',
            nextId: 'topProvincesNextBtn',
            showingId: 'topProvincesShowing',
            tbodyId: 'topProvincesTableBody',
            emptyColspan: 8,
            buildParams: function (state) {
                return {
                    top_page: state.page,
                    top_per_page: state.perPage,
                    top_search: state.search || '',
                    top_province: state.province || '',
                    top_region: state.region || '',
                };
            },
            renderRows: function (items) {
                return items.map(function (item) {
                    return `
                        <tr class="border-t border-t-[#D9D9D9]">
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(item.province || '-')}</td>
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(item.region || '-')}</td>
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(item.lead_name || '-')}</td>
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(item.branch_name || '-')}</td>
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(item.sales_name || '-')}</td>
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(item.source || '-')}</td>
                            <td class="p-2 lg:p-3">${renderRegionalReachStageBadge(item.lead_stage)}</td>
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(formatRegionalReachDate(item.claimed_at))}</td>
                        </tr>
                    `;
                }).join('');
            }
        },
        coverage: {
            blockKey: 'coverage',
            modalId: 'coverageModal',
            triggerId: 'triggerProvinceCoverage',
            searchId: 'coverageSearch',
            provinceId: 'coverageProvinceFilter',
            regionId: 'coverageRegionFilter',
            pageSizeId: 'coveragePageSize',
            prevId: 'coveragePrevBtn',
            nextId: 'coverageNextBtn',
            showingId: 'coverageShowing',
            tbodyId: 'coverageTableBody',
            emptyColspan: 8,
            buildParams: function (state) {
                return {
                    coverage_page: state.page,
                    coverage_per_page: state.perPage,
                    coverage_search: state.search || '',
                    coverage_province: state.province || '',
                    coverage_region: state.region || '',
                };
            },
            renderRows: function (items) {
                return items.map(function (item) {
                    return `
                        <tr class="border-t border-t-[#D9D9D9]">
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(item.province_name || '-')}</td>
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(item.region_name || '-')}</td>
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(item.lead_name || '-')}</td>
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(item.branch_name || '-')}</td>
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(item.sales_name || '-')}</td>
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(item.source || '-')}</td>
                            <td class="p-2 lg:p-3">${renderRegionalReachStageBadge(item.lead_stage)}</td>
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(formatRegionalReachDate(item.claimed_at))}</td>
                        </tr>
                    `;
                }).join('');
            }
        },
        mapping: {
            blockKey: 'branch_mapping',
            modalId: 'mappingModal',
            triggerId: 'triggerMappingBranch',
            searchId: 'mappingSearch',
            branchId: 'mappingBranchFilter',
            provinceId: 'mappingProvinceFilter',
            regionId: 'mappingRegionFilter',
            pageSizeId: 'mappingPageSize',
            prevId: 'mappingPrevBtn',
            nextId: 'mappingNextBtn',
            showingId: 'mappingShowing',
            tbodyId: 'mappingTableBody',
            emptyColspan: 3,
            buildParams: function (state) {
                return {
                    mapping_page: state.page,
                    mapping_per_page: state.perPage,
                    mapping_search: state.search || '',
                    mapping_branch: state.branch || '',
                    mapping_province: state.province || '',
                    mapping_region: state.region || '',
                };
            },
            renderRows: function (items) {
                return items.map(function (item) {
                    return `
                        <tr class="border-t border-t-[#D9D9D9]">
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(item.branch_name || '-')}</td>
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(item.province_name || '-')}</td>
                            <td class="p-2 lg:p-3">${escapeRegionalReachHtml(item.region_name || '-')}</td>
                        </tr>
                    `;
                }).join('');
            }
        }
    };

    function normalizeRegionalReachText(value) {
        return String(value || '').trim();
    }

    function escapeRegionalReachHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatRegionalReachDate(value) {
        if (!value) {
            return '-';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return date.toLocaleString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function renderRegionalReachStageBadge(stage) {
        const label = normalizeRegionalReachText(stage) || '-';
        let className = 'status-trash';

        if (label === 'Cold') {
            className = 'status-cold';
        } else if (label === 'Warm') {
            className = 'status-warm';
        } else if (label === 'Hot') {
            className = 'status-hot';
        } else if (label === 'Deal') {
            className = 'status-deal';
        }

        return `<span class="inline-block px-2 py-1 rounded-sm ${className}">${escapeRegionalReachHtml(label)}</span>`;
    }

    function buildRegionalReachParams(extraParams = {}) {
        const params = new URLSearchParams();

        if (typeof applySuperAdminGeneralFilterToParams === 'function') {
            applySuperAdminGeneralFilterToParams(params, {
                withBranch: true,
                withSales: true
            });
        }

        if (typeof getSuperAdminGeneralFilter === 'function') {
            const generalFilter = getSuperAdminGeneralFilter();

            if (generalFilter.start_date_grid && !params.has('start_date')) {
                params.append('start_date', generalFilter.start_date_grid);
            }

            if (generalFilter.end_date_grid && !params.has('end_date')) {
                params.append('end_date', generalFilter.end_date_grid);
            }
        }

        Object.entries(extraParams).forEach(function ([key, value]) {
            if (value !== null && value !== undefined && value !== '') {
                params.set(key, value);
            }
        });

        return params;
    }

    async function fetchRegionalReachData(extraParams = {}) {
        const params = buildRegionalReachParams(extraParams);
        const query = params.toString();
        const endpoint = query
            ? '/api/dashboard/bm/lead-volume?' + query
            : '/api/dashboard/bm/lead-volume';

        const response = await fetch(endpoint, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        return response.json();
    }

    function renderRegionalReachChart(summary) {
        const chartElement = document.querySelector('#regionalReachChart');
        if (!chartElement) {
            return;
        }

        const labels = summary.map(function (item) {
            return normalizeRegionalReachText(item?.province) || '-';
        });

        const series = summary.map(function (item) {
            return Number(item?.total_leads || 0);
        });

        const grandTotal = summary.reduce(function (total, item) {
            return total + Number(item?.total_leads || 0);
        }, 0);

        if (regionalReachChart) {
            regionalReachChart.destroy();
            regionalReachChart = null;
        }

        regionalReachChart = new ApexCharts(chartElement, {
            chart: {
                type: 'donut',
                height: 280,
                toolbar: {
                    show: false
                }
            },
            labels: labels,
            series: series,
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            name: {
                                show: true
                            },
                            value: {
                                show: true,
                                formatter: function (value) {
                                    return Number(value || 0).toLocaleString('id-ID');
                                }
                            },
                            total: {
                                show: true,
                                showAlways: true,
                                label: 'Total Leads',
                                formatter: function () {
                                    return Number(grandTotal || 0).toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            },
            legend: {
                position: 'right',
                horizontalAlign: 'left'
            },
            dataLabels: {
                enabled: false
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return Number(value || 0).toLocaleString('id-ID') + ' leads';
                    }
                }
            },
            noData: {
                text: 'Belum ada data'
            }
        });

        regionalReachChart.render();
    }

    function resolveRegionalReachTone(percentage) {
        if (percentage >= 70) {
            return {
                countClass: 'text-[#009951] text-2xl font-semibold',
                percentClass: 'text-[#009951] status-finish font-semibold'
            };
        }

        if (percentage >= 35) {
            return {
                countClass: 'text-[#E8B931] text-2xl font-semibold',
                percentClass: 'text-[#E8B931] status-waiting font-semibold'
            };
        }

        return {
            countClass: 'text-[#900B09] text-2xl font-semibold',
            percentClass: 'text-[#900B09] status-expired font-semibold'
        };
    }

    function renderRegionalReachMetric(countId, totalId, percentageId, reached, total) {
        const reachedCount = Number(reached || 0);
        const totalCount = Number(total || 0);
        const percentage = totalCount > 0 ? (reachedCount / totalCount) * 100 : 0;
        const tone = resolveRegionalReachTone(percentage);

        const countElement = document.getElementById(countId);
        const totalElement = document.getElementById(totalId);
        const percentageElement = document.getElementById(percentageId);

        if (countElement) {
            countElement.textContent = reachedCount + '/';
            countElement.className = tone.countClass;
        }

        if (totalElement) {
            totalElement.textContent = totalCount;
            totalElement.className = 'text-[#1E1E1E] text-2xl font-semibold';
        }

        if (percentageElement) {
            percentageElement.textContent = percentage.toFixed(2) + '%';
            percentageElement.className = tone.percentClass;
        }
    }

    function renderRegionalReachOverview(result) {
        regionalReachTopSummary = Array.isArray(result?.top_10_provinces?.summary)
            ? result.top_10_provinces.summary
            : [];

        regionalReachCoverageSummary = result?.coverage || {
            total_provinces: 0,
            reached_provinces: 0,
            total_cities: 0,
            reached_cities: 0
        };

        renderRegionalReachChart(regionalReachTopSummary);
        renderRegionalReachMetric(
            'achievementRegion',
            'totalRegion',
            'percentageAchievementRegion',
            regionalReachCoverageSummary.reached_provinces,
            regionalReachCoverageSummary.total_provinces
        );
        renderRegionalReachMetric(
            'achievementCity',
            'totalCity',
            'percentageAchievementCity',
            regionalReachCoverageSummary.reached_cities,
            regionalReachCoverageSummary.total_cities
        );
    }

    function resetRegionalReachView() {
        regionalReachTopSummary = [];
        regionalReachCoverageSummary = {
            total_provinces: 0,
            reached_provinces: 0,
            total_cities: 0,
            reached_cities: 0
        };

        renderRegionalReachOverview({
            top_10_provinces: {
                summary: []
            },
            coverage: regionalReachCoverageSummary
        });
    }

    async function loadRegionalReach() {
        const requestId = ++regionalReachRequestId;

        try {
            const result = await fetchRegionalReachData();

            if (requestId !== regionalReachRequestId) {
                return;
            }

            renderRegionalReachOverview(result);
        } catch (error) {
            if (requestId !== regionalReachRequestId) {
                return;
            }

            console.error('Error loadRegionalReach:', error);
            resetRegionalReachView();
        }
    }

    function renderRegionalReachSelect(selectId, options, selectedValue, placeholder, valueKey, labelKey) {
        const select = document.getElementById(selectId);
        if (!select) {
            return '';
        }

        const normalizedSelectedValue = String(selectedValue || '');
        const optionItems = Array.isArray(options) ? options : [];

        select.innerHTML = '';

        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = placeholder;
        select.appendChild(placeholderOption);

        optionItems.forEach(function (option) {
            const optionElement = document.createElement('option');
            optionElement.value = String(option?.[valueKey] ?? '');
            optionElement.textContent = String(option?.[labelKey] ?? '');
            select.appendChild(optionElement);
        });

        select.value = normalizedSelectedValue;
        return select.value || '';
    }

    function renderRegionalReachModal(type, block) {
        const config = regionalReachModalConfig[type];
        const state = regionalReachModalState[type];
        const tbody = document.getElementById(config.tbodyId);
        const items = Array.isArray(block?.items) ? block.items : [];
        const pagination = block?.pagination || {
            page: 1,
            per_page: state.perPage,
            total: 0,
            last_page: 1,
            from: 0,
            to: 0
        };
        const filters = block?.filters || {};

        state.page = Number(pagination.page || 1);
        state.lastPage = Number(pagination.last_page || 1);
        state.perPage = Number(pagination.per_page || state.perPage);

        const pageSizeSelect = document.getElementById(config.pageSizeId);
        if (pageSizeSelect) {
            pageSizeSelect.value = String(state.perPage);
        }

        if (config.provinceId) {
            const provinceOptions = filters.provinces || [];
            const provinceValueKey = type === 'top' ? 'value' : 'id';
            const provinceLabelKey = type === 'top' ? 'label' : 'name';
            state.province = renderRegionalReachSelect(
                config.provinceId,
                provinceOptions,
                state.province,
                'All Provinces',
                provinceValueKey,
                provinceLabelKey
            );
        }

        if (config.regionId) {
            const regionOptions = filters.regions || [];
            const regionValueKey = type === 'top' ? 'value' : 'id';
            const regionLabelKey = type === 'top' ? 'label' : 'name';
            state.region = renderRegionalReachSelect(
                config.regionId,
                regionOptions,
                state.region,
                'All Cities',
                regionValueKey,
                regionLabelKey
            );
        }

        if (config.branchId) {
            state.branch = renderRegionalReachSelect(
                config.branchId,
                filters.branches || [],
                state.branch,
                'All Branches',
                'id',
                'name'
            );
        }

        if (tbody) {
            if (!items.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="${config.emptyColspan}" class="text-center py-3 text-[#1E1E1E]">
                            No data found
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = config.renderRows(items);
            }
        }

        const showingElement = document.getElementById(config.showingId);
        if (showingElement) {
            showingElement.textContent = `Showing ${pagination.from || 0}-${pagination.to || 0} of ${pagination.total || 0}`;
        }

        const prevButton = document.getElementById(config.prevId);
        const nextButton = document.getElementById(config.nextId);

        if (prevButton) {
            prevButton.disabled = state.page <= 1;
        }

        if (nextButton) {
            nextButton.disabled = state.page >= state.lastPage;
        }
    }

    function setRegionalReachModalLoading(type) {
        const config = regionalReachModalConfig[type];
        const tbody = document.getElementById(config.tbodyId);

        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="${config.emptyColspan}" class="text-center py-3 text-[#1E1E1E]">
                        Loading data...
                    </td>
                </tr>
            `;
        }
    }

    async function loadRegionalReachModal(type, action = 'init', value = null) {
        const config = regionalReachModalConfig[type];
        const state = regionalReachModalState[type];

        if (!config || !state) {
            return;
        }

        if (action === 'filter' || action === 'search') {
            state.page = 1;
        }

        if (action === 'prev' && state.page > 1) {
            state.page -= 1;
        }

        if (action === 'next' && state.page < state.lastPage) {
            state.page += 1;
        }

        if (action === 'size' && value) {
            state.perPage = Number(value || 10);
            state.page = 1;
        }

        const requestId = ++regionalReachModalRequestIds[type];
        setRegionalReachModalLoading(type);

        try {
            const result = await fetchRegionalReachData(config.buildParams(state));

            if (requestId !== regionalReachModalRequestIds[type]) {
                return;
            }

            renderRegionalReachModal(type, result?.[config.blockKey] || {});
        } catch (error) {
            if (requestId !== regionalReachModalRequestIds[type]) {
                return;
            }

            console.error('Error loadRegionalReachModal:', type, error);
            renderRegionalReachModal(type, {});
        }
    }

    function bindRegionalReachModal(type) {
        const config = regionalReachModalConfig[type];
        const state = regionalReachModalState[type];

        const trigger = document.getElementById(config.triggerId);
        if (trigger) {
            trigger.addEventListener('click', function () {
                $('#' + config.modalId).modal('show');
                loadRegionalReachModal(type, 'open');
            });
        }

        const searchInput = document.getElementById(config.searchId);
        if (searchInput) {
            searchInput.addEventListener('keyup', function () {
                state.search = this.value.trim();

                clearTimeout(regionalReachSearchTimers[type]);
                regionalReachSearchTimers[type] = setTimeout(function () {
                    loadRegionalReachModal(type, 'search');
                }, 400);
            });
        }

        if (config.provinceId) {
            const provinceSelect = document.getElementById(config.provinceId);
            if (provinceSelect) {
                provinceSelect.addEventListener('change', function () {
                    state.province = this.value || '';
                    state.region = '';
                    loadRegionalReachModal(type, 'filter');
                });
            }
        }

        if (config.regionId) {
            const regionSelect = document.getElementById(config.regionId);
            if (regionSelect) {
                regionSelect.addEventListener('change', function () {
                    state.region = this.value || '';
                    loadRegionalReachModal(type, 'filter');
                });
            }
        }

        if (config.branchId) {
            const branchSelect = document.getElementById(config.branchId);
            if (branchSelect) {
                branchSelect.addEventListener('change', function () {
                    state.branch = this.value || '';
                    state.province = '';
                    state.region = '';
                    loadRegionalReachModal(type, 'filter');
                });
            }
        }

        const pageSizeSelect = document.getElementById(config.pageSizeId);
        if (pageSizeSelect) {
            pageSizeSelect.addEventListener('change', function () {
                loadRegionalReachModal(type, 'size', this.value);
            });
        }

        const prevButton = document.getElementById(config.prevId);
        if (prevButton) {
            prevButton.addEventListener('click', function () {
                loadRegionalReachModal(type, 'prev');
            });
        }

        const nextButton = document.getElementById(config.nextId);
        if (nextButton) {
            nextButton.addEventListener('click', function () {
                loadRegionalReachModal(type, 'next');
            });
        }
    }

    function reloadOpenRegionalReachModals() {
        Object.keys(regionalReachModalConfig).forEach(function (type) {
            const modalId = regionalReachModalConfig[type].modalId;
            if ($('#' + modalId).hasClass('show')) {
                loadRegionalReachModal(type, 'filter');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        Object.keys(regionalReachModalConfig).forEach(function (type) {
            bindRegionalReachModal(type);
        });

        loadRegionalReach();
    });

    window.addEventListener('super-admin-general-filter-change', function () {
        loadRegionalReach();
        reloadOpenRegionalReachModals();
    });
</script>
