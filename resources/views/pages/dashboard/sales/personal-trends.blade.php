<h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">Personal Trends</h1>
<div class="grid grid-cols-1 bg-white p-3 rounded-lg border border-[#D9D9D9] mt-2">
    <div class="grid grid-cols-4 gap-5">
        <select id="filterYear" class="border border-[#D9D9D9] rounded-lg px-3 py-2" onchange="handleTrendFilterChange()">
            <option value="2026">2026</option>
            <option value="2025">2025</option>
        </select>
    
        <select id="filterMonth" class="border border-[#D9D9D9] rounded-lg px-3 py-2" onchange="handleTrendFilterChange()">
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
    
        <select id="filterMonthFrom" class="border border-[#D9D9D9] rounded-lg px-3 py-2" onchange="handleTrendFilterChange()">
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
    
        <select id="filterMonthTo" class="border border-[#D9D9D9] rounded-lg px-3 py-2" onchange="handleTrendFilterChange()">
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

    <div id="personalTrendsChart" class="w-full">
    </div>
</div>


<script>
    let personalTrendsChart = null;

    function formatRupiahShort(value) {
        if (value >= 1000000000) return 'Rp ' + (value / 1000000000).toFixed(1) + ' M';
        if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + ' Jt';
        if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(1) + ' Rb';
        return 'Rp ' + value;
    }

    function buildSeries(datasets) {
        return datasets.map(item => ({
            name: item.name,
            data: item.data
        }));
    }

    function renderPersonalTrendsChart(response) {
        const options = {
            chart: {
                type: 'area',
                height: 380,
                toolbar: {
                    show: true
                },
                zoom: {
                    enabled: false
                }
            },
            series: buildSeries(response.datasets),
            xaxis: {
                categories: response.labels,
                title: {
                    text: response.group_by.toUpperCase()
                }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return formatRupiahShort(value);
                    }
                }
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.08,
                    stops: [0, 90, 100]
                }
            },
            dataLabels: {
                enabled: false
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return new Intl.NumberFormat('id-ID', {
                            style: 'currency',
                            currency: 'IDR',
                            maximumFractionDigits: 0
                        }).format(value);
                    }
                }
            },
            legend: {
                position: 'top'
            },
            noData: {
                text: 'Belum ada data'
            }
        };

        if (personalTrendsChart) {
            personalTrendsChart.updateOptions({
                xaxis: options.xaxis,
                yaxis: options.yaxis,
                stroke: options.stroke,
                fill: options.fill,
                legend: options.legend,
                tooltip: options.tooltip
            });
            personalTrendsChart.updateSeries(options.series);
            return;
        }

        personalTrendsChart = new ApexCharts(
            document.querySelector("#personalTrendsChart"),
            options
        );

        personalTrendsChart.render();
    }
    
    async function loadPersonalTrends() {
        try {
            const year = document.getElementById('filterYear')?.value || '';
            const month = document.getElementById('filterMonth')?.value || '';
            const monthFrom = document.getElementById('filterMonthFrom')?.value || '';
            const monthTo = document.getElementById('filterMonthTo')?.value || '';

            const params = new URLSearchParams();

            if (year) params.append('year', year);
            if (month) {
                params.append('month', month);
            } else {
                if (monthFrom) params.append('month_from', monthFrom);
                if (monthTo) params.append('month_to', monthTo);
            }

            const response = await fetch(`/api/leads/personal-trend?${params.toString()}`);
            const result = await response.json();

            if (result.status === 'success') {
                renderPersonalTrendsChart(result);
            } else {
                console.error('Gagal ambil data trends');
            }
        } catch (error) {
            console.error('Error loadPersonalTrends:', error);
        }
    }

</script>