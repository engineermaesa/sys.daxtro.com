<h1 class="text-[#083224] font-semibold uppercase mt-5 text-lg">General Filters</h1>
<div class="w-1/2 grid grid-cols-4 bg-white border border-[#D9D9D9] rounded-lg">
            
    {{-- FILTERS BY --}}
    <div class="flex items-center justify-center gap-2 border-r border-r-[#CFD5DC] cursor-pointer h-full text-[#1E1E1E] p-3">                        
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7.02059 16C6.73725 16 6.49975 15.9042 6.30809 15.7125C6.11642 15.5208 6.02059 15.2833 6.02059 15V9L0.220588 1.6C-0.0294118 1.26667 -0.0669118 0.916667 0.108088 0.55C0.283088 0.183333 0.587255 0 1.02059 0H15.0206C15.4539 0 15.7581 0.183333 15.9331 0.55C16.1081 0.916667 16.0706 1.26667 15.8206 1.6L10.0206 9V15C10.0206 15.2833 9.92476 15.5208 9.73309 15.7125C9.54142 15.9042 9.30392 16 9.02059 16H7.02059ZM8.02059 8.3L12.9706 2H3.07059L8.02059 8.3Z" fill="#0D0F11"/>
        </svg>
        <p class="font-medium">Filter By</p>
    </div>

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

    {{-- RESET FILTERS --}}
    <div id="generalFilterReset" class="flex items-center justify-center gap-2 cursor-pointer h-full">
        <i id="resetQuery" class="fa fa-redo transition-transform duration-300 text-[#900B09] -scale-x-100   " style="font-size: 12px;"></i>
        <p class="font-medium text-[#900B09]">Reset Filter</p>
    </div>
</div>

<script>
    function getSuperAdminGeneralFilter() {
        const branchId = document.getElementById('branchesQuery')?.value || '';
        const salesId = document.getElementById('salesQuery')?.value || '';

        return {
            branch_id: branchId || null,
            sales_id: salesId || null,
        };
    }

    function applySuperAdminGeneralFilterToParams(params, options = {}) {
        const withBranch = options.withBranch !== false;
        const withSales = options.withSales !== false;
        const generalFilter = getSuperAdminGeneralFilter();

        if (withBranch && generalFilter.branch_id && !params.has('branch_id')) {
            params.append('branch_id', generalFilter.branch_id);
        }

        if (withSales && generalFilter.sales_id && !params.has('sales_id')) {
            params.append('sales_id', generalFilter.sales_id);
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

        if (!branchSelect || !salesSelect) {
            return;
        }

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
                broadcastSuperAdminGeneralFilterChange();
            });
        }
    });
</script>
