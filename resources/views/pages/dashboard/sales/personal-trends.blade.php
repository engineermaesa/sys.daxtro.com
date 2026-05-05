<h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">Personal Trends</h1>
<div class="grid grid-cols-1 bg-white p-3 rounded-lg border border-[#D9D9D9] mt-2">
    <div class="flex items-center justify-between gap-4 mb-4">
        <div class="grid grid-cols-4 gap-5 flex-1">
            <select id="filterYear" class="border border-[#D9D9D9] rounded-lg px-3 py-2" onchange="handleTrendFilterChange()">
                <option value="2026">2026</option>
                <option value="2025">2025</option>
            </select>
        
            <select id="filterMonth" class="border border-[#D9D9D9] rounded-lg px-3 py-2" onchange="handleTrendFilterChange()">
                <option value="">All Month</option>
                <option value="1">January</option>
                <option value="2">February</option>
                <option value="3">March</option>
                <option value="4">April</option>
                <option value="5">May</option>
                <option value="6">June</option>
                <option value="7">July</option>
                <option value="8">August</option>
                <option value="9">September</option>
                <option value="10">October</option>
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

        <button
            id="downloadPersonalTrendsPng"
            type="button"
            class="shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-[#115640] text-white rounded-lg cursor-pointer"
        >
            <x-icon.download/>
            Download PNG
        </button>
    </div>

    <div id="personalTrendsChart" class="w-full">
    </div>
</div>


<script>
    let personalTrendsChart = null;
    const SALES_AUTH_BRANCH_NAME = @json(optional(auth()->user()?->branch)->name ?? 'Branch');
    const SALES_AUTH_NAME = @json(auth()->user()?->name ?? 'Sales');

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

    function normalizeTrendMonthRange(monthFrom, monthTo) {
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

    function sanitizeTrendExportFilenamePart(value) {
        return String(value || '')
            .replace(/[\\/:*?"<>|]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function getPersonalTrendsExportFilename() {
        const branchName = sanitizeTrendExportFilenamePart(SALES_AUTH_BRANCH_NAME || 'Branch');
        const salesName = sanitizeTrendExportFilenamePart(SALES_AUTH_NAME || 'Sales');
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

        return `${branchName} - ${salesName} - ${timestamp}`;
    }

    async function downloadPersonalTrendsAsPng() {
        if (!personalTrendsChart) {
            return;
        }

        try {
            const fileName = getPersonalTrendsExportFilename();
            const result = await personalTrendsChart.dataURI();
            const link = document.createElement('a');

            link.href = result.imgURI;
            link.download = `${fileName}.png`;
            document.body.appendChild(link);
            link.click();
            link.remove();
        } catch (error) {
            console.error('Error downloadPersonalTrendsAsPng:', error);
        }
    }

    function renderPersonalTrendsChart(response) {
        const exportFilename = getPersonalTrendsExportFilename();
        const options = {
            chart: {
                type: 'area',
                height: 380,
                toolbar: {
                    show: true,
                    export: {
                        png: {
                            filename: exportFilename
                        }
                    }
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
            personalTrendsChart.destroy();
            personalTrendsChart = null;
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
            const normalizedRange = normalizeTrendMonthRange(
                document.getElementById('filterMonthFrom')?.value || '',
                document.getElementById('filterMonthTo')?.value || ''
            );
            const monthFrom = normalizedRange.monthFrom;
            const monthTo = normalizedRange.monthTo;

            const monthFromSelect = document.getElementById('filterMonthFrom');
            const monthToSelect = document.getElementById('filterMonthTo');
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
            } else {
                if (monthFrom && monthTo) {
                    params.append('month_from', monthFrom);
                    params.append('month_to', monthTo);
                }
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

    document.addEventListener('DOMContentLoaded', function () {
        const downloadButton = document.getElementById('downloadPersonalTrendsPng');

        if (!downloadButton) {
            return;
        }

        downloadButton.addEventListener('click', function () {
            downloadPersonalTrendsAsPng();
        });
    });

</script>
