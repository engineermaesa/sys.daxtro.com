<section>
    {{-- FILTERING --}}
    @include('pages.dashboard.branch-manager.filtering')

    {{-- SALES KPI GRID --}}
    @include('pages.dashboard.branch-manager.sales-kpi')

    {{-- REGIONAL REACH --}}
    @include('pages.dashboard.branch-manager.regional-reach')

    {{-- ACTIVITY OPPORTUNITIES --}}
    @include('pages.dashboard.branch-manager.active-opportunities')
    
    {{-- LEADS PERFORMANCE--}}
    @include('pages.dashboard.branch-manager.leads-performance')

    {{-- SALES TRENDS --}}
    @include('pages.dashboard.branch-manager.sales-trends')

    {{-- SUMMARY --}}
    {{-- @include('pages.dashboard.branch-manager.summary') --}}

    {{-- AGENTS --}}
    @include('pages.dashboard.branch-manager.agent')

</section>

<script>
    // MAIN DOM 
    document.addEventListener("DOMContentLoaded", function () {
        loadDashboardGrid();
        loadActivity();
        loadSource();
        loadSegment();
        loadPersonalTrends();
    });
    
    // PAGINATION STATE
    const DEFAULT_PAGE_SIZE = 5;

    let activityPage = 1;
    let activityTotal = 0;
    
    let activityPageSize = DEFAULT_PAGE_SIZE;
    let leadsPerformancePageSize = DEFAULT_PAGE_SIZE;

    // MAIN FUNCTION RUPIAH
    function formatRupiah(number) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0
        }).format(number);
    }

    function handleTrendFilterChange() {
        const month = document.getElementById('filterMonth');
        const monthFrom = document.getElementById('filterMonthFrom');
        const monthTo = document.getElementById('filterMonthTo');

        if (document.activeElement === month && month.value) {
            monthFrom.value = '';
            monthTo.value = '';
        }

        if ((document.activeElement === monthFrom || document.activeElement === monthTo) && (monthFrom.value || monthTo.value)) {
            month.value = '';
        }

        loadPersonalTrends();
    }

    function getChartTitle(groupBy) {
        switch (groupBy) {
            case 'week':
                return 'Personal Trends per Minggu';
            case 'quarter':
                return 'Personal Trends per Quarter';
            default:
                return 'Personal Trends per Bulan';
        }
    }


</script>
