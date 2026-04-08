<section>
    {{-- FILTERING --}}
    @include('pages.dashboard.super-admin.filtering')

    {{-- PERSONAL KPI GRID --}}
    @include('pages.dashboard.super-admin.general-kpi')

    {{-- ACTIVITY OPPORTUNITIES --}}
    @include('pages.dashboard.super-admin.active-opportunities')
    
    {{-- LEADS PERFORMANCE--}}
    @include('pages.dashboard.super-admin.leads-performance')

    {{-- PERSONAL TRENDS --}}
    @include('pages.dashboard.super-admin.general-trends')

    {{-- SUMMARY --}}
    @include('pages.dashboard.super-admin.summary')

</section>

<script>
    let fp = null;

    // MAIN DOM 
    document.addEventListener("DOMContentLoaded", function () {
        loadDashboardGrid();
        loadActivity();
        loadSource();
        loadSegment();
        loadPersonalTrends();
        loadSummary();

        function initFlatpickr() {
            var input = document.getElementById('source-date-range');
            if (input && typeof flatpickr !== 'undefined') {
            fp = flatpickr(input, {
                mode: 'range',
                inline: true,
                dateFormat: 'Y-m-d',
                onClose: function(selectedDates, dateStr, instance) {
                // keep input populated; actual apply happens via Apply button
                }
            });
            }
        }

        function filterDate(){
            const openBtn = document.getElementById('openDateDropdown');
            const dropdown = document.getElementById('dateDropdown');
            const chevron = document.getElementById('iconDate');

            if (openBtn) {
            openBtn.onclick = () => {
                if (dropdown) {
                dropdown.classList.toggle('opacity-0');
                dropdown.classList.toggle('scale-95');
                dropdown.classList.toggle('pointer-events-none');
                }
                if (chevron) chevron.classList.toggle('rotate-180');
                if (fp) fp.open();
            };
            }

            const cancelBtn = document.getElementById('cancelDate');
            if (cancelBtn) cancelBtn.addEventListener('click', () => {
            if (dropdown) dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
            });

            const applyBtn = document.getElementById('applyDate');
            if (applyBtn) applyBtn.addEventListener('click', () => {
            const dates = (fp && fp.selectedDates) ? fp.selectedDates : [];
            if (dates.length !== 2) return;

            const startDate = dates[0].toISOString().split('T')[0];
            const endDate = dates[1].toISOString().split('T')[0];

            const label = document.getElementById('dateLabel');
            if (label) label.innerText = `${startDate} -> ${endDate}`;

            loadAvailableLeads(startDate, endDate);

            if (dropdown) dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
            });
        }

        initFlatpickr();
        filterDate();

    });
    
    // PAGINATION STATE
    const DEFAULT_PAGE_SIZE = 5;

    let activityPage = 1;
    let activityTotal = 0;
    
    let activityPageSize = DEFAULT_PAGE_SIZE;
    let leadsPerformancePageSize = DEFAULT_PAGE_SIZE;

    // FILTER STATE
    let filterSource = '';
    let filterStage = '';
    let filterStartDate = '';
    let filterEndDate = '';

    let searchQuery = '';
    let searchTimeout = null;

    // MAIN FUNCTION SEARCH
    function getSearchQuery() {
        return searchQuery;
    }

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
