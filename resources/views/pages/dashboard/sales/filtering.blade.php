<h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">Sales Filters</h1>
<div class="w-1/2 grid grid-cols-3 bg-white border border-gray-300 rounded-lg">
            
    {{-- FILTERS BY --}}
    <div class="flex items-center justify-center gap-2 border-r border-r-[#CFD5DC] cursor-pointer h-full text-[#1E1E1E] p-3">                        
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7.02059 16C6.73725 16 6.49975 15.9042 6.30809 15.7125C6.11642 15.5208 6.02059 15.2833 6.02059 15V9L0.220588 1.6C-0.0294118 1.26667 -0.0669118 0.916667 0.108088 0.55C0.283088 0.183333 0.587255 0 1.02059 0H15.0206C15.4539 0 15.7581 0.183333 15.9331 0.55C16.1081 0.916667 16.0706 1.26667 15.8206 1.6L10.0206 9V15C10.0206 15.2833 9.92476 15.5208 9.73309 15.7125C9.54142 15.9042 9.30392 16 9.02059 16H7.02059ZM8.02059 8.3L12.9706 2H3.07059L8.02059 8.3Z" fill="#0D0F11"/>
        </svg>
        <p class="font-medium">Filter By</p>
    </div>

    {{-- DATES --}}
    <div
    class="border-r border-r-[#CFD5DC] cursor-pointer w-full relative grid grid-cols-1 items-center h-full">

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
    const salesGridDateFilter = {
        start_date_grid: null,
        end_date_grid: null,
    };

    function getSuperAdminGeneralFilter() {
        return {
            start_date_grid: salesGridDateFilter.start_date_grid,
            end_date_grid: salesGridDateFilter.end_date_grid,
        };
    }

    function applySuperAdminGeneralFilterToParams(params, options = {}) {
        const withGridDate = options.withGridDate !== false;
        const generalFilter = getSuperAdminGeneralFilter();

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
