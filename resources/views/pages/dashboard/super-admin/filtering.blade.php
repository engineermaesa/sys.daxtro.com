<h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">General Filters</h1>
<div class="w-full 2xl:w-3/4 grid grid-cols-1 md:grid-cols-5 bg-white border border-[#D9D9D9] rounded-lg">

    {{-- BRANCH --}}
    <div class="flex items-center justify-center gap-2 border-r border-r-[#CFD5DC] cursor-pointer h-full px-2 text-[#1E1E1E] p-3">
        <select id="branchesQuery"
        class="w-full font-semibold text-center focus:outline-none cursor-pointer">
            <option value="">All Branches</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
    
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
        <div id="openDateDropdownGrid" class="flex justify-center items-center gap-2">
            <p id="dateLabelGrid" class="font-medium text-black">Date</p>
            <i id="iconDateGrid" class="fas fa-chevron-down transition-transform duration-300 text-black" style="font-size: 12px;"></i>
        </div>

        {{-- DATE DROPDOWN --}}
        <div id="dateDropdownGrid"
            class="absolute top-full left-0 mt-2 bg-white rounded-lg shadow-xl w-[350px] p-4 z-50 opacity-0 scale-95 pointer-events-none transition-all duration-200 ease-out origin-top overflow-visible">

            <h3 class="font-semibold mb-2">Select Date Range</h3>

            <div class="flex justify-center items-center">
                <input type="text" id="source-date-range-grid" class="shadow-none w-full" placeholder="Select date range">
            </div>

            <div class="flex justify-end gap-2 mt-3">
                <button id="cancelDateGrid" class="px-3 py-1 text-[#303030]">
                    Cancel
                </button>

                <button id="applyDateGrid" class="px-3 py-1 bg-[#115640] text-white rounded-lg cursor-pointer">
                    Apply
                </button>
            </div>
        </div>
    </div>

    {{-- COMPARE DATES --}}
    <div class="border-r border-r-[#CFD5DC] cursor-pointer w-full relative grid grid-cols-1 items-center h-full">
        {{-- TOGGLE --}}
        <div id="openCompareDateDropdownGrid" class="flex justify-center items-center gap-2 px-2 py-3">
            <p id="compareDateLabelGrid" class="font-medium text-black text-center">Compare Dates</p>
            <i id="iconCompareDateGrid" class="fas fa-chevron-down transition-transform duration-300 text-black" style="font-size: 12px;"></i>
        </div>

        {{-- COMPARE DATE DROPDOWN --}}
        <div id="compareDateDropdownGrid"
            class="absolute top-full right-0 mt-2 bg-white rounded-lg shadow-xl w-[720px] p-4 z-50 opacity-0 scale-95 pointer-events-none transition-all duration-200 ease-out origin-top overflow-visible">

            <h3 class="font-semibold mb-3">Compare Dates</h3>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-semibold text-[#1E1E1E] mb-2">Base Date</p>
                    <input type="text" id="compareStartDateGrid" class="shadow-none w-full" placeholder="Select base date">
                </div>

                <div>
                    <p class="text-sm font-semibold text-[#1E1E1E] mb-2">Compare Date</p>
                    <input type="text" id="compareEndDateGrid" class="shadow-none w-full" placeholder="Select compare date">
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-3">
                <button id="cancelCompareDateGrid" class="px-3 py-1 text-[#303030]">
                    Cancel
                </button>

                <button id="applyCompareDateGrid" class="px-3 py-1 bg-[#115640] text-white rounded-lg cursor-pointer">
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
    const superAdminGridDateFilter = {
        start_date_grid: null,
        end_date_grid: null
    };

    const superAdminCompareDateFilter = {
        compare_start_date: null,
        compare_end_date: null
    };

    function getSuperAdminGeneralFilter() {
        const branchId = document.getElementById('branchesQuery')?.value || '';
        const salesId = document.getElementById('salesQuery')?.value || '';

        return {
            branch_id: branchId || null,
            sales_id: salesId || null,
            start_date_grid: superAdminGridDateFilter.start_date_grid,
            end_date_grid: superAdminGridDateFilter.end_date_grid,
            compare_start_date: superAdminCompareDateFilter.compare_start_date,
            compare_end_date: superAdminCompareDateFilter.compare_end_date,
        };
    }

    function applySuperAdminGeneralFilterToParams(params, options = {}) {
        const withBranch = options.withBranch !== false;
        const withSales = options.withSales !== false;
        const withGridDate = options.withGridDate === true;
        const withCompareDate = options.withCompareDate === true;
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

        if (
            withCompareDate
            && generalFilter.compare_start_date
            && generalFilter.compare_end_date
            && !params.has('compare_start_date')
            && !params.has('compare_end_date')
        ) {
            params.append('compare_start_date', generalFilter.compare_start_date);
            params.append('compare_end_date', generalFilter.compare_end_date);
        }
    }

    function broadcastSuperAdminGeneralFilterChange() {
        window.dispatchEvent(new CustomEvent('super-admin-general-filter-change', {
            detail: getSuperAdminGeneralFilter()
        }));
    }

    function syncSalesOptionsWithBranch() {
        const branchSelect = document.getElementById('branchesQuery');
        const salesSelect = document.getElementById('salesQuery');

        if (!branchSelect || !salesSelect) {
            return;
        }

        const selectedBranch = branchSelect.value || '';
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
        const dateLabel = document.getElementById('dateLabelGrid');
        const dateDropdown = document.getElementById('dateDropdownGrid');
        const dateIcon = document.getElementById('iconDateGrid');
        const dateInput = document.getElementById('source-date-range-grid');
        const dateOpenBtn = document.getElementById('openDateDropdownGrid');
        const dateCancelBtn = document.getElementById('cancelDateGrid');
        const dateApplyBtn = document.getElementById('applyDateGrid');
        const compareDateLabel = document.getElementById('compareDateLabelGrid');
        const compareDateDropdown = document.getElementById('compareDateDropdownGrid');
        const compareDateIcon = document.getElementById('iconCompareDateGrid');
        const compareDateOpenBtn = document.getElementById('openCompareDateDropdownGrid');
        const compareStartInput = document.getElementById('compareStartDateGrid');
        const compareEndInput = document.getElementById('compareEndDateGrid');
        const compareDateCancelBtn = document.getElementById('cancelCompareDateGrid');
        const compareDateApplyBtn = document.getElementById('applyCompareDateGrid');
        let gridDatePicker = null;
        let compareStartPicker = null;
        let compareEndPicker = null;

        if (!branchSelect || !salesSelect) {
            return;
        }

        function setDateLabel() {
            if (!dateLabel) {
                return;
            }

            if (superAdminGridDateFilter.start_date_grid && superAdminGridDateFilter.end_date_grid) {
                dateLabel.textContent = superAdminGridDateFilter.start_date_grid + ' -> ' + superAdminGridDateFilter.end_date_grid;
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

        function setCompareDateLabel() {
            if (!compareDateLabel) {
                return;
            }

            if (superAdminCompareDateFilter.compare_start_date && superAdminCompareDateFilter.compare_end_date) {
                compareDateLabel.textContent = superAdminCompareDateFilter.compare_start_date + ' vs ' + superAdminCompareDateFilter.compare_end_date;
                return;
            }

            compareDateLabel.textContent = 'Compare Dates';
        }

        function closeCompareDateDropdown() {
            if (compareDateDropdown) {
                compareDateDropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
            }
            if (compareDateIcon) {
                compareDateIcon.classList.remove('rotate-180');
            }
        }

        function notifyCompareDate(message) {
            if (typeof notif === 'function') {
                notif(message, 'error');
                return;
            }

            alert(message);
        }

        function resetCompareDateFilter(shouldClearPicker = true) {
            superAdminCompareDateFilter.compare_start_date = null;
            superAdminCompareDateFilter.compare_end_date = null;

            if (shouldClearPicker) {
                if (compareStartPicker) {
                    compareStartPicker.clear();
                } else if (compareStartInput) {
                    compareStartInput.value = '';
                }

                if (compareEndPicker) {
                    compareEndPicker.clear();
                    compareEndPicker.set('minDate', null);
                } else if (compareEndInput) {
                    compareEndInput.value = '';
                }
            }

            setCompareDateLabel();
        }

        function resetDateFilter(shouldClearPicker = true) {
            superAdminGridDateFilter.start_date_grid = null;
            superAdminGridDateFilter.end_date_grid = null;

            if (shouldClearPicker && gridDatePicker) {
                gridDatePicker.clear();
            } else if (shouldClearPicker && dateInput) {
                dateInput.value = '';
            }

            setDateLabel();
        }

        if (dateInput && typeof flatpickr !== 'undefined') {
            gridDatePicker = flatpickr(dateInput, {
                mode: 'range',
                inline: true,
                dateFormat: 'Y-m-d',
            });
        }

        if (compareStartInput && compareEndInput && typeof flatpickr !== 'undefined') {
            compareEndPicker = flatpickr(compareEndInput, {
                inline: true,
                dateFormat: 'Y-m-d',
                disableMobile: true,
                onChange: function (selectedDates, dateStr) {
                    superAdminCompareDateFilter.compare_end_date = dateStr || null;
                }
            });

            compareStartPicker = flatpickr(compareStartInput, {
                inline: true,
                dateFormat: 'Y-m-d',
                disableMobile: true,
                onChange: function (selectedDates, dateStr) {
                    superAdminCompareDateFilter.compare_start_date = dateStr || null;

                    if (compareEndPicker) {
                        compareEndPicker.set('minDate', dateStr || null);
                    }

                    if (
                        dateStr
                        && superAdminCompareDateFilter.compare_end_date
                        && superAdminCompareDateFilter.compare_end_date < dateStr
                    ) {
                        superAdminCompareDateFilter.compare_end_date = null;
                        if (compareEndPicker) {
                            compareEndPicker.clear();
                        }
                    }
                }
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
                    closeCompareDateDropdown();
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
                if (gridDatePicker && gridDatePicker.selectedDates.length === 2) {
                    superAdminGridDateFilter.start_date_grid = flatpickr.formatDate(gridDatePicker.selectedDates[0], 'Y-m-d');
                    superAdminGridDateFilter.end_date_grid = flatpickr.formatDate(gridDatePicker.selectedDates[1], 'Y-m-d');
                } else {
                    resetDateFilter(false);
                }

                setDateLabel();
                closeDateDropdown();
                broadcastSuperAdminGeneralFilterChange();
            });
        }

        if (compareDateOpenBtn && compareDateDropdown) {
            compareDateOpenBtn.addEventListener('click', function () {
                const isClosed = compareDateDropdown.classList.contains('opacity-0');

                if (isClosed) {
                    compareDateDropdown.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
                    if (compareDateIcon) {
                        compareDateIcon.classList.add('rotate-180');
                    }
                    closeDateDropdown();
                } else {
                    closeCompareDateDropdown();
                }
            });
        }

        if (compareDateCancelBtn) {
            compareDateCancelBtn.addEventListener('click', function () {
                closeCompareDateDropdown();
            });
        }

        if (compareDateApplyBtn) {
            compareDateApplyBtn.addEventListener('click', function () {
                const baseDate = superAdminCompareDateFilter.compare_start_date;
                const compareDate = superAdminCompareDateFilter.compare_end_date;

                if (!baseDate || !compareDate) {
                    notifyCompareDate('Please select Base Date and Compare Date');
                    return;
                }

                if (compareDate < baseDate) {
                    notifyCompareDate('Compare Date cannot be earlier than Base Date');
                    superAdminCompareDateFilter.compare_end_date = null;
                    if (compareEndPicker) {
                        compareEndPicker.clear();
                    }
                    setCompareDateLabel();
                    return;
                }

                setCompareDateLabel();
                closeCompareDateDropdown();
                broadcastSuperAdminGeneralFilterChange();
            });
        }

        document.addEventListener('click', function (event) {
            if (dateDropdown && dateOpenBtn && !dateDropdown.contains(event.target) && !dateOpenBtn.contains(event.target)) {
                closeDateDropdown();
            }

            if (
                compareDateDropdown
                && compareDateOpenBtn
                && !compareDateDropdown.contains(event.target)
                && !compareDateOpenBtn.contains(event.target)
            ) {
                closeCompareDateDropdown();
            }
        });

        setDateLabel();
        setCompareDateLabel();
        syncSalesOptionsWithBranch();

        branchSelect.addEventListener('change', function () {
            syncSalesOptionsWithBranch();
            broadcastSuperAdminGeneralFilterChange();
        });

        salesSelect.addEventListener('change', function () {
            broadcastSuperAdminGeneralFilterChange();
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                branchSelect.value = '';
                salesSelect.value = '';
                syncSalesOptionsWithBranch();
                resetDateFilter(true);
                resetCompareDateFilter(true);
                closeDateDropdown();
                closeCompareDateDropdown();
                broadcastSuperAdminGeneralFilterChange();
            });
        }
    });
</script>
