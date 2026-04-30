@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs! lg:text-sm!">
    <div class="pt-4">
        <div class="flex items-center gap-3">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M6 20C5.45 20 4.97917 19.8042 4.5875 19.4125C4.19583 19.0208 4 18.55 4 18C4 17.45 4.19583 16.9792 4.5875 16.5875C4.97917 16.1958 5.45 16 6 16C6.55 16 7.02083 16.1958 7.4125 16.5875C7.80417 16.9792 8 17.45 8 18C8 18.55 7.8042 19.0208 7.4125 19.4125C7.0208 19.8042 6.55 20 6 20ZM16 20C15.45 20 14.9792 19.8042 14.5875 19.4125C14.1958 19.0208 14 18.55 14 18C14 17.45 14.1958 16.9792 14.5875 16.5875C14.9792 16.1958 15.45 16 16 16C16.55 16 17.0208 16.1958 17.4125 16.5875C17.8042 16.9792 18 17.45 18 18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20ZM4.2 2H18.95C19.3333 2 19.625 2.17083 19.825 2.5125C20.025 2.85417 20.0333 3.2 19.85 3.55L16.3 9.95C16.1167 10.2833 15.8708 10.5417 15.5625 10.725C15.2542 10.9083 14.9167 11 14.55 11H7.1L6 13H17C17.2833 13 17.5208 13.0958 17.7125 13.2875C17.9042 13.4792 18 13.7167 18 14C18 14.2833 17.9042 14.5208 17.7125 14.7125C17.5208 14.9042 17.2833 15 17 15H6C5.25 15 4.68333 14.6708 4.3 14.0125C3.91667 13.3542 3.9 12.7 4.25 12.05L5.6 9.6L2 2H1C0.716667 2 0.479167 1.90417 0.2875 1.7125C0.0958333 1.52083 0 1.28333 0 1C0 0.716667 0.0958333 0.479167 0.2875 0.2875C0.479167 0.0958333 0.716667 0 1 0H2.625C2.80833 0 2.98333 0.05 3.15 0.15C3.31667 0.25 3.44167 0.391667 3.525 0.575L4.2 2Z" fill="#115640"/>
            </svg>
            <h1 class="text-[#115640] font-semibold lg:text-2xl text-lg">Orders</h1>
        </div>
    </div>

    <div id="forAllCardsCounts" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mt-4">
        <x-orders.summary-card id="summary-all-total-orders" label="Total Orders" tone="blue" value="{{ $counts['all'] ?? 0 }}" />
        <x-orders.summary-card id="summary-all-total-billing" label="Total Billing" tone="green" value="Rp 0" />
        <x-orders.summary-card id="summary-all-paid-amount" label="Paid Amount" tone="yellow" value="Rp 0" />
        <x-orders.summary-card id="summary-all-remaining-amount" label="Remaining Amount" tone="red" value="Rp 0" />
    </div>

    <div id="forPendingCardsCounts" class="hidden grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mt-4">
        <x-orders.summary-card id="summary-pending-total-orders" label="Pending Orders" tone="yellow" value="{{ $counts['pending'] ?? 0 }}" />
        <x-orders.summary-card id="summary-pending-total-billing" label="Pending Billing" tone="blue" value="Rp 0" />
        <x-orders.summary-card id="summary-pending-paid-amount" label="Paid Amount" tone="green" value="Rp 0" />
        <x-orders.summary-card id="summary-pending-remaining-amount" label="Remaining Amount" tone="red" value="Rp 0" />
    </div>

    <div id="forCompletedCardsCounts" class="hidden grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 mt-4">
        <x-orders.summary-card id="summary-completed-total-orders" label="Completed Orders" tone="green" value="{{ $counts['complete'] ?? 0 }}" />
        <x-orders.summary-card id="summary-completed-total-billing" label="Completed Billing" tone="blue" value="Rp 0" />
        <x-orders.summary-card id="summary-completed-latest-payment-date" label="Latest Completed Payment" tone="yellow" value="-" />
    </div>

    <div class="mt-4 rounded-lg border-[#D9D9D9]">
        <div class="bg-white lg:grid lg:grid-cols-[3fr_1fr] border-b border-[#D9D9D9] p-3 gap-4 rounded-tr-lg rounded-tl-lg sm:gap-3 grid grid-cols-1">
            <div class="sm:grid sm:grid-cols-2 sm:grid-cols-[3fr_1fr] gap-4 lg:hidden">
                <div class="border border-gray-300 rounded-lg flex items-center p-2">
                    <i class="fas fa-search text-[#6B7786] px-2"></i>
                    <input type="text" placeholder="Search" class="searchInput w-full px-3 py-1 border-none focus:outline-[#115640]" />
                </div>
            </div>

            <div class="lg:grid lg:grid-cols-[1fr_3fr] gap-4 max-lg:hidden">
                <div class="border border-gray-300 rounded-lg lg:flex! items-center p-2 hidden h-full">
                    <i class="fas fa-search text-[#6B7786] px-2"></i>
                    <input type="text" placeholder="Search" class="searchInput w-full px-3 py-1 border-none focus:outline-[#115640]" />
                </div>

                <div class="grid grid-cols-4 items-center border border-gray-300 rounded-lg text-[#1E1E1E] max-lg:text-xs! h-full">
                    <div class="flex items-center justify-center gap-2 border-r border-[#D9D9D9] cursor-pointer py-2 h-full px-2">
                        <select id="branch-filter-new" class="w-full font-semibold text-center focus:outline-none cursor-pointer">
                            <option value="">All Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center justify-center gap-2 border-r border-[#D9D9D9] cursor-pointer py-2 h-full px-2">
                        <select id="source-filter-new" class="w-full font-semibold text-center focus:outline-none cursor-pointer">
                            <option value="">All Source</option>
                            @foreach($sources as $source)
                                <option value="{{ $source->id }}">{{ $source->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="cursor-pointer w-full relative grid grid-cols-1 items-center h-full border-r border-[#D9D9D9]">
                        <div id="openDateDropdown" class="flex justify-center items-center gap-2">
                            <p id="dateLabel" class="font-medium text-black">Date</p>
                            <i id="iconDate" class="fas fa-chevron-down transition-transform duration-300 text-black" style="font-size: 12px;"></i>
                        </div>

                        <div id="dateDropdown" class="absolute top-full left-0 mt-2 bg-white rounded-lg shadow-xl w-[350px] p-4 z-50 opacity-0 scale-95 pointer-events-none transition-all duration-200 ease-out origin-top overflow-visible">
                            <h3 class="font-semibold mb-2">Select Date Range</h3>
                            <div class="flex justify-center items-center">
                                <input type="text" id="source-date-range" class="shadow-none w-full" placeholder="Select date range">
                            </div>
                            <div class="flex justify-end gap-2 mt-3">
                                <button id="cancelDate" type="button" class="px-3 py-1 text-[#303030]">Cancel</button>
                                <button id="applyDate" type="button" class="px-3 py-1 bg-[#115640] text-white rounded-lg cursor-pointer">Apply</button>
                            </div>
                        </div>
                    </div>

                    <div id="resetFilter" class="flex items-center justify-center gap-2 py-2 cursor-pointer h-full">
                        <i class="fa fa-redo transition-transform duration-300 text-[#900B09] -scale-x-100" style="font-size: 12px;"></i>
                        <p class="font-medium text-[#900B09]">Reset Filter</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 max-lg:hidden">
                <div class="border border-[#D5D5D5] rounded-lg grid grid-cols-3 h-full">
                    @foreach (['all' => 'All', 'pending' => 'Pending', 'completed' => 'Completed'] as $tab => $label)
                        <div data-status="{{ $tab }}" class="orders-nav-tab text-center cursor-pointer py-2 h-full border-r border-r-[#D5D5D5] flex items-center justify-center {{ $loop->first ? 'active-nav' : '' }}">
                            <p class="text-[#083224]">
                                {{ $label }}
                                <span id="nav-count-{{ $tab }}">({{ $tab === 'completed' ? ($counts['complete'] ?? 0) : ($counts[$tab] ?? 0) }})</span>
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @foreach(['all', 'pending', 'completed'] as $tab)
            <div data-status-wrapper="{{ $tab }}" class="orders-table-container {{ $loop->first ? '' : 'hidden' }}">
                <div class="max-xl:overflow-x-scroll">
                    <table id="{{ $tab }}OrdersTableNew" class="w-full bg-white rounded-br-lg rounded-bl-lg">
                        <thead class="text-[#1E1E1E]">
                            <tr class="border-b border-b-[#D9D9D9]">
                                <th class="p-1 lg:p-3">Order No</th>
                                <th class="p-1 lg:p-3">Customer</th>
                                <th class="p-1 lg:p-3">Branch</th>
                                <th class="p-1 lg:p-3">Sales</th>
                                <th class="p-1 lg:p-3">Quotation No</th>
                                <th class="p-1 lg:p-3">Total Billing</th>
                                @if($tab !== 'completed')
                                    <th class="p-1 lg:p-3">Paid Billing</th>
                                    <th class="p-1 lg:p-3">Remaining Billing</th>
                                @endif
                                <th class="p-1 lg:p-3">Payment Progress</th>
                                <th class="p-1 lg:p-3">Latest Payment Date</th>
                                <th class="p-1 lg:p-3">Order Status</th>
                                <th class="p-1 lg:p-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="{{ $tab }}BodyTable"></tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center px-3 py-2 text-[#1E1E1E]! bg-transparent">
                    <div class="flex items-center gap-3">
                        <p class="font-semibold">Show Rows</p>
                        <select id="{{ $tab }}PageSizeSelect" class="w-auto bg-white font-semibold p-2 rounded-md" onchange="changePageSize('{{ $tab }}', this.value)">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <div id="{{ $tab }}Showing" class="font-semibold">Showing 0-0 of 0</div>
                        <div>
                            <button id="{{ $tab }}PrevBtn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!" onclick="goPrev('{{ $tab }}')">
                                <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
                            </button>
                            <button id="{{ $tab }}NextBtn" class="btn bg-white border! border-[#D9D9D9]! cursor-pointer!" onclick="goNext('{{ $tab }}')">
                                <i class="fas fa-chevron-right text-black" style="font-size: 12px;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

<div class="modal fade" id="progressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Progress Logs</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="progressTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="progress-log-tab" data-toggle="tab" href="#progress-log" role="tab">Progress Log</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="activity-log-tab" data-toggle="tab" href="#activity-log" role="tab">Activity Log</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="progress-log" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="progressLogsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Step</th>
                                        <th>Note</th>
                                        <th>User</th>
                                        <th>Attachment</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="activity-log" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="activityLogsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Activity</th>
                                        <th>Note</th>
                                        <th>User</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let activeTabState = 'all';
    const DEFAULT_PAGE_SIZE = 10;
    const pageState = { all: 1, pending: 1, completed: 1 };
    const pageSizeState = { all: DEFAULT_PAGE_SIZE, pending: DEFAULT_PAGE_SIZE, completed: DEFAULT_PAGE_SIZE };
    const totals = { all: 0, pending: 0, completed: 0 };
    let selectedStartDate = '';
    let selectedEndDate = '';
    let orderDatePicker = null;

    const ordersListUrl = '{{ route('orders.list') }}';
    const ordersCountsUrl = '{{ route('orders.counts') }}';
    const ordersApiBaseUrl = '{{ url('/api/orders') }}';

    function debounce(callback, delay) {
        let timeout;
        return function () {
            clearTimeout(timeout);
            const context = this;
            const args = arguments;
            timeout = setTimeout(function () {
                callback.apply(context, args);
            }, delay);
        };
    }

    function formatRupiah(value) {
        const number = Number(value || 0);
        return 'Rp ' + number.toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }

    function hasRemainingBillingColumn(tab) {
        return tab === 'all' || tab === 'pending';
    }

    function tableColspan(tab) {
        return hasRemainingBillingColumn(tab) ? 12 : 10;
    }

    function formatPaidBilling(tab, paidAmount, totalBilling) {
        const paid = Number(paidAmount || 0);
        const total = Number(totalBilling || 0);

        if (tab === 'all' && paid === total) {
            return formatRupiah(paid);
        }

        return formatRupiah(paid);
    }

    function formatRemainingBilling(tab, value) {
        const amount = Number(value || 0);

        if (tab === 'all' && amount === 0) {
            return '<span class="font-semibold text-[#115640]">Payment Completed</span>';
        }

        return '<span class="font-semibold text-[#900B09]">' + formatRupiah(amount) + '</span>';
    }

    function buildFilterParams(tab) {
        const params = {
            search: $('.searchInput').first().val() || '',
            source_id: $('#source-filter-new').val() || '',
            branch_id: $('#branch-filter-new').val() || '',
            start_date: selectedStartDate,
            end_date: selectedEndDate,
            page: pageState[tab] || 1,
            per_page: pageSizeState[tab] || DEFAULT_PAGE_SIZE
        };

        if (tab === 'pending') {
            params.payment_status = 'pending';
        } else if (tab === 'completed') {
            params.payment_status = 'complete';
        }

        return params;
    }

    function updatePagerUI(tab, total) {
        const page = pageState[tab] || 1;
        const perPage = pageSizeState[tab] || DEFAULT_PAGE_SIZE;
        const start = total === 0 ? 0 : ((page - 1) * perPage) + 1;
        const end = Math.min(page * perPage, total);

        $('#' + tab + 'Showing').text('Showing ' + start + '-' + end + ' of ' + total);
        $('#' + tab + 'PrevBtn').prop('disabled', page <= 1);
        $('#' + tab + 'NextBtn').prop('disabled', end >= total);
    }

    function renderRows(tab, rows) {
        const tbody = $('#' + tab + 'BodyTable');
        tbody.empty();

        if (!rows || rows.length === 0) {
            tbody.html('<tr><td colspan="' + tableColspan(tab) + '" class="text-center p-3 text-[#1E1E1E]">No data available</td></tr>');
            return;
        }

        rows.forEach(function (row) {
          const orderStatus = (row.order_status || '').toLowerCase();
          const paidBillingCell = hasRemainingBillingColumn(tab)
              ? `<td class="p-1 md:p-2 lg:p-3">${formatPaidBilling(tab, row.paid_amount, row.total_billing)}</td>`
              : '';
          const remainingBillingCell = hasRemainingBillingColumn(tab)
              ? `<td class="p-1 md:p-2 lg:p-3">${formatRemainingBilling(tab, row.remaining_amount)}</td>`
              : '';

          const orderStatusClass =
              orderStatus === 'publish'
                  ? 'status-cold'
                  : (orderStatus === 'done' || orderStatus === 'completed')
                      ? 'status-deal'
                      : 'status-warm';

            tbody.append(`
                <tr class="border-t border-t-[#D9D9D9] text-[#1E1E1E]">
                    <td class="p-1 md:p-2 lg:p-3">${row.order_no || '-'}</td>
                    <td class="p-1 md:p-2 lg:p-3">${row.customer || '-'}</td>
                    <td class="p-1 md:p-2 lg:p-3">${row.branch || '-'}</td>
                    <td class="p-1 md:p-2 lg:p-3">${row.sales || '-'}</td>
                    <td class="p-1 md:p-2 lg:p-3">${row.quotation_no || '-'}</td>
                    <td class="p-1 md:p-2 lg:p-3">${formatRupiah(row.total_billing)}</td>
                    ${paidBillingCell}
                    ${remainingBillingCell}
                    <td class="p-1 md:p-2 lg:p-3">${row.payment_progress || '-'}</td>
                    <td class="p-1 md:p-2 lg:p-3">${row.latest_payment_date || '-'}</td>
                    <td class="p-1 md:p-2 lg:p-3">
                      <span class="${orderStatusClass} p-1">
                        ${row.order_status || '-'}
                      </span>
                    </td>
                    <td class="text-center p-1 md:p-2 lg:p-3">${row.actions || '-'}</td>
                </tr>
            `);
        });
    }

    function loadOrders(tab) {
        const tbody = $('#' + tab + 'BodyTable');
        tbody.html('<tr><td colspan="' + tableColspan(tab) + '" class="text-center p-3 text-[#1E1E1E]">Loading data...</td></tr>');

        $.ajax({
            url: ordersListUrl,
            type: 'GET',
            data: buildFilterParams(tab),
            headers: { 'Accept': 'application/json' },
            success: function (result) {
                totals[tab] = result.total || 0;
                renderRows(tab, result.data || []);
                updatePagerUI(tab, result.total || 0);
            },
            error: function () {
                tbody.html('<tr><td colspan="' + tableColspan(tab) + '" class="text-center p-3 text-red-500">Failed to load orders</td></tr>');
            }
        });
    }

    function renderMetric(prefix, data) {
        data = data || {};
        $('#summary-' + prefix + '-total-orders').text(data.total_orders || 0);
        $('#summary-' + prefix + '-total-billing').text(formatRupiah(data.total_billing || 0));
        $('#summary-' + prefix + '-paid-amount').text(formatRupiah(data.paid_amount || 0));
        $('#summary-' + prefix + '-remaining-amount').text(formatRupiah(data.remaining_amount || 0));
        $('#summary-' + prefix + '-latest-payment-date').text(data.latest_payment_date || '-');
    }

    function loadCounts() {
        $.ajax({
            url: ordersCountsUrl,
            type: 'GET',
            data: buildFilterParams(activeTabState),
            headers: { 'Accept': 'application/json' },
            success: function (result) {
                $('#nav-count-all').text('(' + (result.all || 0) + ')');
                $('#nav-count-pending').text('(' + (result.pending || 0) + ')');
                $('#nav-count-completed').text('(' + (result.completed || result.complete || 0) + ')');

                renderMetric('all', result.cards?.all);
                renderMetric('pending', result.cards?.pending);
                renderMetric('completed', result.cards?.completed);
            }
        });
    }

    function resetAllPages() {
        pageState.all = 1;
        pageState.pending = 1;
        pageState.completed = 1;
    }

    function reloadActiveTab() {
        loadOrders(activeTabState);
    }

    function switchTab(tab) {
        activeTabState = tab;

        $('[data-status-wrapper]').addClass('hidden');
        $('[data-status-wrapper="' + tab + '"]').removeClass('hidden');

        $('.orders-nav-tab').removeClass('active-nav');
        $('.orders-nav-tab[data-status="' + tab + '"]').addClass('active-nav');

        $('#forAllCardsCounts, #forPendingCardsCounts, #forCompletedCardsCounts').addClass('hidden');
        if (tab === 'pending') {
            $('#forPendingCardsCounts').removeClass('hidden');
        } else if (tab === 'completed') {
            $('#forCompletedCardsCounts').removeClass('hidden');
        } else {
            $('#forAllCardsCounts').removeClass('hidden');
        }

        loadOrders(tab);
        loadCounts();
    }

    window.changePageSize = function (tab, value) {
        pageSizeState[tab] = Number(value || DEFAULT_PAGE_SIZE);
        pageState[tab] = 1;
        loadOrders(tab);
    };

    window.goPrev = function (tab) {
        if ((pageState[tab] || 1) > 1) {
            pageState[tab] -= 1;
            loadOrders(tab);
        }
    };

    window.goNext = function (tab) {
        const perPage = pageSizeState[tab] || DEFAULT_PAGE_SIZE;
        const currentEnd = (pageState[tab] || 1) * perPage;
        if (currentEnd < (totals[tab] || 0)) {
            pageState[tab] += 1;
            loadOrders(tab);
        }
    };

    function toggleDateDropdown(forceClose) {
        const dropdown = $('#dateDropdown');
        const icon = $('#iconDate');
        const shouldClose = forceClose === true || !dropdown.hasClass('pointer-events-none');

        if (shouldClose) {
            dropdown.addClass('opacity-0 scale-95 pointer-events-none');
            icon.removeClass('rotate-180');
        } else {
            dropdown.removeClass('opacity-0 scale-95 pointer-events-none');
            icon.addClass('rotate-180');
            if (orderDatePicker) {
                orderDatePicker.open();
            }
        }
    }

    $(document).ready(function () {
        if (typeof flatpickr !== 'undefined') {
            orderDatePicker = flatpickr('#source-date-range', {
                mode: 'range',
                inline: true,
                dateFormat: 'Y-m-d',
                onClose: function () {
                    // Keep the selected range visible; filtering only runs from Apply.
                }
            });
        }

        $('.orders-nav-tab').on('click', function () {
            switchTab($(this).data('status'));
        });

        $('.searchInput').on('input', debounce(function () {
            $('.searchInput').val($(this).val());
            resetAllPages();
            reloadActiveTab();
            loadCounts();
        }, 400));

        $('#source-filter-new, #branch-filter-new').on('change', function () {
            resetAllPages();
            reloadActiveTab();
            loadCounts();
        });

        $('#openDateDropdown').on('click', function () {
            toggleDateDropdown(false);
        });

        $('#cancelDate').on('click', function () {
            toggleDateDropdown(true);
        });

        $('#applyDate').on('click', function () {
            const dates = orderDatePicker ? orderDatePicker.selectedDates : [];

            if (dates.length !== 2) {
                return;
            }

            if (dates.length === 2) {
                selectedStartDate = flatpickr.formatDate(dates[0], 'Y-m-d');
                selectedEndDate = flatpickr.formatDate(dates[1], 'Y-m-d');
                $('#dateLabel').text(selectedStartDate + ' - ' + selectedEndDate);
            }

            resetAllPages();
            reloadActiveTab();
            loadCounts();
            toggleDateDropdown(true);
        });

        $('#resetFilter').on('click', function () {
            $('.searchInput').val('');
            $('#source-filter-new').val('');
            $('#branch-filter-new').val('');
            $('#source-date-range').val('');
            selectedStartDate = '';
            selectedEndDate = '';
            $('#dateLabel').text('Date');

            if (orderDatePicker) {
                orderDatePicker.clear();
            }

            resetAllPages();
            switchTab('all');
        });

        $(document).on('click', '.btn-progress-log', function () {
            const orderId = $(this).data('order');
            const progressTbody = $('#progressLogsTable tbody');
            const activityTbody = $('#activityLogsTable tbody');

            progressTbody.html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');
            activityTbody.html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');
            $('#progressModal').modal('show');

            $.get(ordersApiBaseUrl + '/' + orderId + '/progress-logs', function (data) {
                let rows = '';
                data.forEach(function (item) {
                    const attachment = item.attachment
                        ? '<a href="' + item.attachment + '" target="_blank" class="btn btn-sm btn-outline-secondary">Download</a>'
                        : '-';

                    rows += '<tr>' +
                        '<td>' + item.logged_at + '</td>' +
                        '<td>' + item.step + ' - ' + item.step_label + '</td>' +
                        '<td>' + (item.note || '') + '</td>' +
                        '<td>' + item.user + '</td>' +
                        '<td>' + attachment + '</td>' +
                    '</tr>';
                });
                progressTbody.html(rows || '<tr><td colspan="5" class="text-center">No logs</td></tr>');
            });

            $.get(ordersApiBaseUrl + '/' + orderId + '/activity-logs', function (data) {
                let rows = '';
                data.forEach(function (item) {
                    rows += '<tr>' +
                        '<td>' + item.date + '</td>' +
                        '<td>' + item.action + '</td>' +
                        '<td>' + (item.description || '') + '</td>' +
                        '<td>' + item.user + '</td>' +
                    '</tr>';
                });
                activityTbody.html(rows || '<tr><td colspan="4" class="text-center">No logs</td></tr>');
            });
        });

        loadOrders('all');
        loadCounts();
    });
</script>
@endsection

@section('styles')
<style>
    .orders-nav-tab.active-nav {
        background-color: #E7F3EE;
    }

    .orders-table-container {
        display: block;
    }

    button:disabled {
        opacity: .45;
        cursor: not-allowed !important;
    }
</style>
@endsection
