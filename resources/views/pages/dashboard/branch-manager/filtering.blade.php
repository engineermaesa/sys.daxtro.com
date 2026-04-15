<h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">Sales Filters</h1>
<div class="w-3/4 2xl:w-1/2 grid grid-cols-3 bg-white border border-gray-300 rounded-lg">
    
    {{-- SALES --}}
    <div class="flex items-center justify-center gap-2 border-r border-r-[#D9D9D9] cursor-pointer h-full px-2 text-[#1E1E1E] p-3">
        <select id="salesQuery"
        class="w-full font-semibold text-center focus:outline-none cursor-pointer">
            <option value="">All Sales</option>
            @foreach($salesUsers as $sales)
                <option value="{{ $sales->id }}" data-branch-id="{{ $sales->branch_id }}">{{ $sales->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- DATES --}}
    <div class="border-r border-r-[#CFD5DC] cursor-pointer w-full relative grid grid-cols-1 items-center h-full">

        {{-- TOGGLE --}}
        <div id="bmGridOpenDateDropdown" class="flex justify-center items-center gap-2">
            <p id="bmGridDateLabel" class="font-medium text-black">Date</p>
            <i id="bmGridIconDate" class="fas fa-chevron-down transition-transform duration-300 text-black" style="font-size: 12px;"></i>
        </div>

        {{-- DATE DROPDOWN --}}
        <div id="bmGridDateDropdown"
            class="absolute top-full left-0 mt-2 bg-white rounded-lg shadow-xl w-[350px] p-4 z-50 opacity-0 scale-95 pointer-events-none transition-all duration-200 ease-out origin-top overflow-visible">

            <h3 class="font-semibold mb-2">Select Date Range</h3>

            <div class="flex justify-center items-center">
                <input type="text" id="bmGridDateRange" class="shadow-none w-full" placeholder="Select date range">
            </div>

            <div class="flex justify-end gap-2 mt-3">

                <button id="bmGridCancelDate" class="px-3 py-1 text-[#303030]">
                    Cancel
                </button>

                <button id="bmGridApplyDate"
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
    const BM_DEFAULT_BRANCH_ID = @json(isset($currentBranchId) ? (string) $currentBranchId : '');
    const bmGridDateFilter = {
        start_date_grid: null,
        end_date_grid: null
    };

    function resolveGeneralFilterBranchId() {
        const branchIdFromSelect = document.getElementById('branchesQuery')?.value || '';
        return branchIdFromSelect || BM_DEFAULT_BRANCH_ID || '';
    }

    function getSuperAdminGeneralFilter() {
        const branchId = resolveGeneralFilterBranchId();
        const salesId = document.getElementById('salesQuery')?.value || '';

        return {
            branch_id: branchId || null,
            sales_id: salesId || null,
            start_date_grid: bmGridDateFilter.start_date_grid,
            end_date_grid: bmGridDateFilter.end_date_grid,
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

    function syncSalesOptionsWithBranch() {
        const salesSelect = document.getElementById('salesQuery');

        if (!salesSelect) {
            return;
        }

        const selectedBranch = resolveGeneralFilterBranchId();
        const salesOptions = Array.from(salesSelect.options).slice(1);

        salesOptions.forEach((option) => {
            const optionBranchId = option.dataset.branchId || '';
            const isVisible = !selectedBranch || optionBranchId === selectedBranch;

            option.hidden = !isVisible;
            option.disabled = !isVisible;
        });

        const selectedSalesOption = salesSelect.options[salesSelect.selectedIndex];
        if (selectedSalesOption && selectedSalesOption.disabled) {
            salesSelect.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const branchSelect = document.getElementById('branchesQuery');
        const salesSelect = document.getElementById('salesQuery');
        const resetBtn = document.getElementById('generalFilterReset');
        const dateLabel = document.getElementById('bmGridDateLabel');
        const dateDropdown = document.getElementById('bmGridDateDropdown');
        const dateIcon = document.getElementById('bmGridIconDate');
        const dateInput = document.getElementById('bmGridDateRange');
        const dateOpenBtn = document.getElementById('bmGridOpenDateDropdown');
        const dateCancelBtn = document.getElementById('bmGridCancelDate');
        const dateApplyBtn = document.getElementById('bmGridApplyDate');
        let bmGridDatePicker = null;

        if (!salesSelect) {
            return;
        }

        function setDateLabel() {
            if (!dateLabel) {
                return;
            }

            if (bmGridDateFilter.start_date_grid && bmGridDateFilter.end_date_grid) {
                dateLabel.textContent = bmGridDateFilter.start_date_grid + ' -> ' + bmGridDateFilter.end_date_grid;
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
            bmGridDateFilter.start_date_grid = null;
            bmGridDateFilter.end_date_grid = null;

            if (shouldClearPicker && bmGridDatePicker) {
                bmGridDatePicker.clear();
            } else if (shouldClearPicker && dateInput) {
                dateInput.value = '';
            }

            setDateLabel();
        }

        if (dateInput && typeof flatpickr !== 'undefined') {
            bmGridDatePicker = flatpickr(dateInput, {
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
                if (bmGridDatePicker && bmGridDatePicker.selectedDates.length === 2) {
                    bmGridDateFilter.start_date_grid = flatpickr.formatDate(bmGridDatePicker.selectedDates[0], 'Y-m-d');
                    bmGridDateFilter.end_date_grid = flatpickr.formatDate(bmGridDatePicker.selectedDates[1], 'Y-m-d');
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
        syncSalesOptionsWithBranch();

        if (branchSelect) {
            branchSelect.addEventListener('change', function () {
                syncSalesOptionsWithBranch();
                broadcastSuperAdminGeneralFilterChange();
            });
        }

        salesSelect.addEventListener('change', function () {
            broadcastSuperAdminGeneralFilterChange();
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (branchSelect) {
                    branchSelect.value = '';
                }
                salesSelect.value = '';
                syncSalesOptionsWithBranch();
                resetDateFilter(true);
                closeDateDropdown();
                broadcastSuperAdminGeneralFilterChange();
            });
        }
    });
</script>
