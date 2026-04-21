<h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">Sales Filters</h1>
<div class="w-3/4 2xl:w-1/2 grid grid-cols-2 bg-white border border-gray-300 rounded-lg">

    {{-- DATES --}}
    <div
    class="border-r border-r-[#CFD5DC] cursor-pointer w-full relative grid grid-cols-1 items-center h-full py-3">

        {{-- TOGGLE --}}
        <div id="salesGridOpenDateDropdown" class="flex justify-center items-center gap-2">
            <p id="salesGridDateLabel" class="font-medium text-black">Date</p>
            <i id="salesGridIconDate" class="fas fa-chevron-down transition-transform duration-300 text-black" style="font-size: 12px;"></i>
        </div>

        {{-- DATE DROPDOWN --}}
        <div id="salesGridDateDropdown"
            class="absolute top-full left-0 mt-2 bg-white rounded-lg shadow-xl w-[350px] p-4 z-50 opacity-0 scale-95 pointer-events-none transition-all duration-200 ease-out origin-top overflow-visible">

            <h3 class="font-semibold mb-2">Select Date Range</h3>

                <div class="flex justify-center items-center">
                <input type="text" id="salesGridDateRange" class="shadow-none w-full" placeholder="Select date range">
                </div>

            <div class="flex justify-end gap-2 mt-3">

                <button id="salesGridCancelDate" class="px-3 py-1 text-[#303030]">
                    Cancel
                </button>

                <button id="salesGridApplyDate"
                    class="px-3 py-1 bg-[#115640] text-white rounded-lg cursor-pointer">
                    Apply
                </button>

            </div>
        </div>
    </div>

    {{-- RESET FILTERS --}}
    <div id="generalFilterReset" class="flex items-center justify-center gap-2 cursor-pointer h-full">
        <i id="resetQuery" class="fa fa-redo transition-transform duration-300 text-[#900B09] -scale-x-100   " style="font-size: 12px;"></i>
        <p class="font-medium text-[#900B09]">Reset Filter</p>
    </div>
</div>

<script>
    const SALES_DEFAULT_BRANCH_ID = @json(auth()->user()?->branch_id ? (string) auth()->user()->branch_id : '');
    const SALES_DEFAULT_SALES_ID = @json(auth()->id() ? (string) auth()->id() : '');
    const salesGridDateFilter = {
        start_date_grid: null,
        end_date_grid: null,
    };

    function getSuperAdminGeneralFilter() {
        return {
            branch_id: SALES_DEFAULT_BRANCH_ID || null,
            sales_id: SALES_DEFAULT_SALES_ID || null,
            start_date_grid: salesGridDateFilter.start_date_grid,
            end_date_grid: salesGridDateFilter.end_date_grid,
        };
    }

    function applySuperAdminGeneralFilterToParams(params, options = {}) {
        const withBranch = options.withBranch !== false;
        const withSales = options.withSales !== false;
        const withGridDate = options.withGridDate === true;
        const generalFilter = getSuperAdminGeneralFilter();

        if (withBranch && generalFilter.branch_id && !params.has('branch_id')) {
            params.append('branch_id', generalFilter.branch_id);
        }

        if (withSales && generalFilter.sales_id && !params.has('sales_id')) {
            params.append('sales_id', generalFilter.sales_id);
        }

        if (
            withGridDate
            && generalFilter.start_date_grid
            && generalFilter.end_date_grid
            && !params.has('start_date_grid')
            && !params.has('end_date_grid')
        ) {
            params.append('start_date_grid', generalFilter.start_date_grid);
            params.append('end_date_grid', generalFilter.end_date_grid);
        }
    }

    function broadcastSuperAdminGeneralFilterChange() {
        window.dispatchEvent(new CustomEvent('super-admin-general-filter-change', {
            detail: getSuperAdminGeneralFilter()
        }));
    }

    document.addEventListener('DOMContentLoaded', function () {
        const resetBtn = document.getElementById('generalFilterReset');
        const dateLabel = document.getElementById('salesGridDateLabel');
        const dateDropdown = document.getElementById('salesGridDateDropdown');
        const dateIcon = document.getElementById('salesGridIconDate');
        const dateInput = document.getElementById('salesGridDateRange');
        const dateOpenBtn = document.getElementById('salesGridOpenDateDropdown');
        const dateCancelBtn = document.getElementById('salesGridCancelDate');
        const dateApplyBtn = document.getElementById('salesGridApplyDate');
        let salesGridDatePicker = null;

        function setDateLabel() {
            if (!dateLabel) {
                return;
            }

            if (salesGridDateFilter.start_date_grid && salesGridDateFilter.end_date_grid) {
                dateLabel.textContent = salesGridDateFilter.start_date_grid + ' -> ' + salesGridDateFilter.end_date_grid;
                return;
            }

            dateLabel.textContent = 'Date';
        }

        function closeDateDropdown() {
            if (dateDropdown) {
                dateDropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
            }
            if (dateIcon) {
                dateIcon.classList.remove('rotate-180');
            }
        }

        function resetDateFilter(shouldClearPicker = true) {
            salesGridDateFilter.start_date_grid = null;
            salesGridDateFilter.end_date_grid = null;

            if (shouldClearPicker && salesGridDatePicker) {
                salesGridDatePicker.clear();
            } else if (shouldClearPicker && dateInput) {
                dateInput.value = '';
            }

            setDateLabel();
        }

        if (dateInput && typeof flatpickr !== 'undefined') {
            salesGridDatePicker = flatpickr(dateInput, {
                mode: 'range',
                inline: true,
                dateFormat: 'Y-m-d',
            });
        }

        if (dateOpenBtn && dateDropdown) {
            dateOpenBtn.addEventListener('click', function () {
                const isClosed = dateDropdown.classList.contains('opacity-0');

                if (isClosed) {
                    dateDropdown.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
                    if (dateIcon) {
                        dateIcon.classList.add('rotate-180');
                    }
                } else {
                    closeDateDropdown();
                }
            });
        }

        if (dateCancelBtn) {
            dateCancelBtn.addEventListener('click', function () {
                closeDateDropdown();
            });
        }

        if (dateApplyBtn) {
            dateApplyBtn.addEventListener('click', function () {
                if (salesGridDatePicker && salesGridDatePicker.selectedDates.length === 2) {
                    salesGridDateFilter.start_date_grid = flatpickr.formatDate(salesGridDatePicker.selectedDates[0], 'Y-m-d');
                    salesGridDateFilter.end_date_grid = flatpickr.formatDate(salesGridDatePicker.selectedDates[1], 'Y-m-d');
                } else {
                    resetDateFilter(false);
                }

                setDateLabel();
                closeDateDropdown();
                broadcastSuperAdminGeneralFilterChange();
            });
        }

        document.addEventListener('click', function (event) {
            if (!dateDropdown || !dateOpenBtn) {
                return;
            }

            if (!dateDropdown.contains(event.target) && !dateOpenBtn.contains(event.target)) {
                closeDateDropdown();
            }
        });

        setDateLabel();

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                resetDateFilter(true);
                closeDateDropdown();
                broadcastSuperAdminGeneralFilterChange();
            });
        }
    });
</script>
