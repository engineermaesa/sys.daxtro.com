<h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">Regional Reach Overview</h1>

<div class="grid grid-cols-1 2xl:grid-cols-3 gap-3 mt-2">
    {{-- Top 10 Regions by Lead Volume --}}
    <div class="bg-white p-3 border border-[#D9D9D9] rounded-lg">

        <div class="flex items-center justify-between">
            <h1 class="text-[#757575] font-semibold">Top 10 Regions by Lead Volume</h1>
            
            <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                <x-icon.bar-charts/>
            </div>
        </div>

        <div id="regionalReachChart" class="mt-4"></div>
        
    </div>

    {{-- Region Coverage (Province) --}}
    <div class="bg-white p-3 border border-[#D9D9D9] rounded-lg">

        <div class="flex items-center justify-between">
            <h1 class="text-[#757575] font-semibold">Region Coverage (Province)</h1>
            
            <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                <x-icon.globe/>
            </div>
        </div>

        <div class="triggerModalTable">
            <div class="mt-3 text-[#757575]">
                <p id="achievementRegion">0/</p>
                <p id="totalRegion">0</p>
            </div>

            <div class="flex items-center justify-start gap-2 mt-3">
                <p id="percentageAchievementRegion">0</p>
                <p class="text-[#1E1E1E] text-xs">Achievement</p>
            </div>
        </div>
        
    </div>

    {{-- City Coverage --}}
    <div class="bg-white p-3 border border-[#D9D9D9] rounded-lg">

        <div class="flex items-center justify-between">
            <h1 class="text-[#757575] font-semibold">City Coverage</h1>
            
            <div class="p-3 border border-[#D9D9D9] rounded-md text-[#417866]">
                <x-icon.location/>
            </div>
        </div>

        <div class="triggerModalTable">
            <div class="mt-3 text-[#757575]">
                <p id="achievementCity">0/</p>
                <p id="totalCity">0</p>
            </div>

            <div class="flex items-center justify-start gap-2 mt-3">
                <p id="percentageAchievementCity">0</p>
                <p class="text-[#1E1E1E] text-xs">Achievement</p>
            </div>
        </div>
        
    </div>
</div>


<script>
    let regionalReachChart = null;
    let regionalReachDataset = [];
    let regionalReachSeries = [];
    let regionalReachLabels = [];
    let regionalReachRequestId = 0;
    let grand_regional_leads = 0;

    const REGIONAL_TOTAL_PROVINCE = 38;
    const REGIONAL_TOTAL_CITY = 514;

    function normalizeRegionalReachText(value) {
        return $.trim(String(value || ''));
    }

    function isValidRegionalReachValue(value) {
        return value !== '' && value !== '-';
    }

    function buildRegionalReachParams() {
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

        return params;
    }

    function renderRegionalReachChart() {
        const chartElement = document.querySelector('#regionalReachChart');
        if (!chartElement) {
            return;
        }

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
            labels: regionalReachLabels,
            series: regionalReachSeries,
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
                                    return Number(grand_regional_leads || 0).toLocaleString('id-ID');
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

    function calculateUniqueProvinceCount() {
        const provinceMap = new Map();

        regionalReachDataset.forEach(function (item) {
            const provinceName = normalizeRegionalReachText(item?.province);
            const provinceKey = provinceName.toLowerCase();

            if (isValidRegionalReachValue(provinceName) && !provinceMap.has(provinceKey)) {
                provinceMap.set(provinceKey, provinceName);
            }
        });

        return provinceMap.size;
    }

    function calculateUniqueCityCount() {
        const cityMap = new Map();

        regionalReachDataset.forEach(function (item) {
            const leads = Array.isArray(item?.leads) ? item.leads : [];

            leads.forEach(function (lead) {
                const cityName = normalizeRegionalReachText(lead?.nama_kota);
                const cityKey = cityName.toLowerCase();

                if (isValidRegionalReachValue(cityName) && !cityMap.has(cityKey)) {
                    cityMap.set(cityKey, cityName);
                }
            });
        });

        return cityMap.size;
    }

    function renderRegionCoverage() {
        const provinceCount = calculateUniqueProvinceCount();
        const provincePercentage = REGIONAL_TOTAL_PROVINCE > 0
            ? (provinceCount / REGIONAL_TOTAL_PROVINCE) * 100
            : 0;
        if(provincePercentage >= 70){
            $('#achievementRegion').text(provinceCount + '/').addClass("text-[#009951] text-2xl font-semibold");
        }else if(provincePercentage >= 35){
            $('#achievementRegion').text(provinceCount + '/').addClass("text-[#E8B931] text-2xl font-semibold");
        } else{
            $('#achievementRegion').text(provinceCount + '/').addClass("text-[#900B09] text-2xl font-semibold");
        }
        
        $('#totalRegion').text(REGIONAL_TOTAL_PROVINCE).addClass("text-[#1E1E1E] text-2xl font-semibold");
        
        if(provincePercentage >= 70){
            $('#percentageAchievementRegion').text(provincePercentage.toFixed(2) + '%').addClass("text-[#009951] status-finish font-semibold");
        }else if(provincePercentage >= 35){
            $('#percentageAchievementRegion').text(provincePercentage.toFixed(2) + '%').addClass("text-[#E8B931] status-waiting font-semibold");
        } else{
            $('#percentageAchievementRegion').text(provincePercentage.toFixed(2) + '%').addClass("text-[#900B09] status-expired font-semibold");
        }
    }

    function renderCityCoverage() {
        const cityCount = calculateUniqueCityCount();
        const cityPercentage = REGIONAL_TOTAL_CITY > 0
            ? (cityCount / REGIONAL_TOTAL_CITY) * 100
            : 0;

        if(cityPercentage >= 70){
            $('#achievementCity').text(cityCount + '/').addClass("text-[#009951] text-2xl font-semibold");
        }else if(cityPercentage >= 35){
            $('#achievementCity').text(cityCount + '/').addClass("text-[#E8B931] text-2xl font-semibold");
        } else{
            $('#achievementCity').text(cityCount + '/').addClass("text-[#900B09] text-2xl font-semibold");
        }

        $('#totalCity').text(REGIONAL_TOTAL_CITY).addClass("text-[#1E1E1E] text-2xl font-semibold");

        if(cityPercentage >= 70){
            $('#percentageAchievementCity').text(cityPercentage.toFixed(2) + '%').addClass("text-[#009951] status-finish font-semibold");
        }else if(cityPercentage >= 35){
            $('#percentageAchievementCity').text(cityPercentage.toFixed(2) + '%').addClass("text-[#E8B931] status-waiting font-semibold");
        } else{
            $('#percentageAchievementCity').text(cityPercentage.toFixed(2) + '%').addClass("text-[#900B09] status-expired font-semibold");
        }
    }

    function resetRegionalReachView() {
        regionalReachDataset = [];
        regionalReachLabels = [];
        regionalReachSeries = [];
        grand_regional_leads = 0;

        renderRegionalReachChart();
        renderRegionCoverage();
        renderCityCoverage();
    }

    function loadRegionalReach() {
        const requestId = ++regionalReachRequestId;
        const params = buildRegionalReachParams();
        const query = params.toString();
        const endpoint = query
            ? '/api/dashboard/lead-volume?' + query
            : '/api/dashboard/lead-volume';

        $.ajax({
            url: endpoint,
            method: 'GET',
            dataType: 'json',
            success: function (result) {
                if (requestId !== regionalReachRequestId) {
                    return;
                }

                regionalReachDataset = Array.isArray(result?.data) ? result.data : [];
                regionalReachLabels = regionalReachDataset.map(function (item) {
                    const provinceName = normalizeRegionalReachText(item?.province);
                    return provinceName || '-';
                });
                regionalReachSeries = regionalReachDataset.map(function (item) {
                    return Number(item?.total_leads || 0);
                });
                grand_regional_leads = regionalReachDataset.reduce(function (total, item) {
                    return total + Number(item?.total_leads || 0);
                }, 0);

                renderRegionalReachChart();
                renderRegionCoverage();
                renderCityCoverage();
            },
            error: function (error) {
                if (requestId !== regionalReachRequestId) {
                    return;
                }

                console.error('Error loadRegionalReach:', error);
                resetRegionalReachView();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadRegionalReach();
    });

    window.addEventListener('super-admin-general-filter-change', function () {
        loadRegionalReach();
    });
</script>
