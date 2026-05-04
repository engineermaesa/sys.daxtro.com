@extends('layouts.app')

@section('content')
@php
  $financeRequestTypes = [
    'meeting-expense' => 'Meeting Expense',
    'payment-confirmation' => 'Payment Confirmation',
    'expense-realization' => 'Expense Realization',
  ];
@endphp

<section class="min-h-screen sm:text-xs lg:text-sm">
  <div class="pt-4">
    <div class="flex items-center gap-2">
      <svg width="20" height="20" viewBox="0 0 22 27" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path opacity="0.8" d="M2.66667 26.6667C1.93333 26.6667 1.30556 26.4056 0.783333 25.8833C0.261111 25.3611 0 24.7333 0 24V2.66667C0 1.93333 0.261111 1.30556 0.783333 0.783333C1.30556 0.261111 1.93333 0 2.66667 0H13.3333L21.3333 8V24C21.3333 24.7333 21.0722 25.3611 20.55 25.8833C20.0278 26.4056 19.4 26.6667 18.6667 26.6667H2.66667ZM9.33333 22.6667H12V21.3333H13.3333C13.7111 21.3333 14.0278 21.2056 14.2833 20.95C14.5389 20.6944 14.6667 20.3778 14.6667 20V16C14.6667 15.6222 14.5389 15.3056 14.2833 15.05C14.0278 14.7944 13.7111 14.6667 13.3333 14.6667H9.33333V13.3333H14.6667V10.6667H12V9.33333H9.33333V10.6667H8C7.62222 10.6667 7.30556 10.7944 7.05 11.05C6.79444 11.3056 6.66667 11.6222 6.66667 12V16C6.66667 16.3778 6.79444 16.6944 7.05 16.95C7.30556 17.2056 7.62222 17.3333 8 17.3333H12V18.6667H6.66667V21.3333H9.33333V22.6667ZM12.2333 8H17.5667L12.2333 2.66667V8Z" fill="#115640"/>
      </svg>
      <h1 class="text-[#115640] font-semibold lg:text-2xl text-lg">Finance</h1>
    </div>
    <p class="mt-1 text-[#115640] lg:text-lg text-sm">Finance Requests</p>
  </div>

  <div class="mt-4 rounded-lg border border-[#D9D9D9] bg-white">
    <div class="grid grid-cols-1 gap-3 p-3">
      <div class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_3fr]">
        <div class="flex items-center rounded-lg border border-gray-300 p-2">
          <div class="px-2 text-[#6B7786]">
            <i class="fas fa-search"></i>
          </div>
          <input
            type="text"
            placeholder="Search"
            class="finance-request-search-input w-full border-none px-3 py-1 shadow-none focus:outline-[#115640]">
        </div>

        <div class="grid grid-cols-1 rounded-lg border border-gray-300 text-[#1E1E1E] md:grid-cols-5">
          <div class="flex items-center border-b border-[#D9D9D9] px-2 py-2 md:border-b-0 md:border-r">
            <select id="finance-request-branch-filter" class="w-full cursor-pointer text-center font-semibold focus:outline-none">
              <option value="">All Branches</option>
              @foreach($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="flex items-center border-b border-[#D9D9D9] px-2 py-2 md:border-b-0 md:border-r">
            <select id="finance-request-sales-filter" class="w-full cursor-pointer text-center font-semibold focus:outline-none">
              <option value="">All Sales</option>
              @foreach($sales as $salesUser)
                <option
                  value="{{ $salesUser->id }}"
                  data-branch-id="{{ $salesUser->branch_id }}"
                  data-role-code="{{ $salesUser->role?->code }}">
                  {{ $salesUser->name }}{{ $salesUser->role?->code === 'branch_manager' ? ' (BM)' : '' }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="relative grid cursor-pointer grid-cols-1 items-center border-b border-[#D9D9D9] py-2 md:border-b-0 md:border-r">
            <button type="button" id="finance-request-date-toggle" class="flex items-center justify-center gap-2 px-2">
              <span id="finance-request-date-label" class="font-medium text-black">Date</span>
              <i id="finance-request-date-icon" class="fas fa-chevron-down text-black transition-transform duration-300" style="font-size: 12px;"></i>
            </button>

            <div id="finance-request-date-dropdown" class="pointer-events-none absolute left-0 top-full z-50 mt-2 w-[380px] origin-top scale-95 rounded-lg bg-white p-4 opacity-0 shadow-xl transition-all duration-200 ease-out">
              <label for="finance-request-date-mode" class="mb-1 block font-semibold">Date Mode</label>
              <select id="finance-request-date-mode" class="mb-3 w-full rounded-md border border-[#D9D9D9] p-2 focus:outline-[#115640]">
                <option value="requested_at">Requested At</option>
                <option value="meeting_date">Meeting Date / Paid At</option>
                <option value="decided_at">Decided At</option>
              </select>

              <h3 class="mb-2 font-semibold">Select Date Range</h3>
              <input type="text" id="finance-request-date-range" class="w-full shadow-none" placeholder="Select date range">

              <div class="mt-3 flex justify-end gap-2">
                <button type="button" id="finance-request-date-cancel" class="px-3 py-1 text-[#303030]">Cancel</button>
                <button type="button" id="finance-request-date-apply" class="cursor-pointer rounded-lg bg-[#115640] px-3 py-1 text-white">Apply</button>
              </div>
            </div>
          </div>

          <div class="flex items-center border-b border-[#D9D9D9] px-2 py-2 md:border-b-0 md:border-r">
            <select id="finance-request-status-filter" class="w-full cursor-pointer text-center font-semibold focus:outline-none">
              <option value="">All Status</option>
              <option value="approved">Approved</option>
              <option value="pending">Pending</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>

          <button type="button" id="finance-request-reset-filter" class="cursor-pointer flex h-full items-center justify-center gap-2 px-2 py-2 text-[#900B09]">
            <i class="fa fa-redo -scale-x-100 text-[#900B09]" style="font-size: 12px;"></i>
            <span class="font-medium">Reset Filter</span>
          </button>
        </div>
      </div>
    </div>
    <div class="grid grid-cols-1 overflow-hidden border-t border-t-[#D9D9D9] border-b border-b-[#D9D9D9] md:grid-cols-3">
      @foreach($financeRequestTypes as $type => $label)
        <button
          type="button"
          data-type="{{ $type }}"
          class="finance-request-tab flex items-center justify-center gap-2 border-b border-[#D5D5D5] px-3 py-2 text-center font-semibold text-[#1E1E1E] last:border-b-0 md:border-b-0 md:border-r md:last:border-r-0 cursor-pointer {{ $loop->first ? 'bg-[#E7F3EE] text-[#115640]' : 'bg-white' }}">
          <span>{{ $label }}</span>
          <span id="finance-request-count-{{ $type }}" class="rounded-full bg-[#115640] px-2 py-0.5 text-xs font-semibold text-white">
            {{ $counts[$type] ?? 0 }}
          </span>
        </button>
      @endforeach
    </div>
    @foreach($financeRequestTypes as $type => $label)
      <div data-type-wrapper="{{ $type }}" class="finance-request-table-container {{ $loop->first ? '' : 'hidden' }}">
        <div class="max-xl:overflow-x-auto">
          <table id="finance-request-table-{{ $type }}" class="w-full min-w-[980px] bg-white">
            <thead class="text-[#1E1E1E]">
              <tr class="border-b border-b-[#D9D9D9]">
                <th class="p-2 text-left lg:p-3">Branch</th>
                <th class="p-2 text-left lg:p-3">Sales</th>
                <th class="p-2 text-left lg:p-3">Lead Name</th>
                @if($type === 'payment-confirmation')
                  <th class="p-2 text-left lg:p-3">Payment Date</th>
                  <th class="p-2 text-left lg:p-3">Reference</th>
                @else
                  <th class="p-2 text-left lg:p-3">Meeting Date</th>
                  <th class="p-2 text-left lg:p-3">Location</th>
                @endif
                <th class="p-2 text-left lg:p-3">Requested At</th>
                <th class="p-2 text-left lg:p-3">Decided At</th>
                <th class="p-2 text-right lg:p-3">Amount</th>
                <th class="p-2 text-center lg:p-3">Status</th>
                <th class="p-2 text-center lg:p-3">Action</th>
              </tr>
            </thead>
            <tbody id="finance-request-table-body-{{ $type }}"></tbody>
          </table>
        </div>

        <div class="flex flex-col gap-3 px-3 py-2 text-[#1E1E1E] md:flex-row md:items-center md:justify-between">
          <div class="flex items-center gap-3">
            <span class="font-semibold">Show Rows</span>
            <select data-type="{{ $type }}" class="finance-request-page-size w-auto rounded-md bg-white p-2 font-semibold">
              <option value="5">5</option>
              <option value="10" selected>10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <div id="finance-request-showing-{{ $type }}" class="font-semibold">Showing 0-0 of 0</div>
            <div class="flex gap-1">
              <button type="button" data-type="{{ $type }}" data-direction="prev" class="finance-request-page-button btn bg-white border! border-[#D9D9D9]! cursor-pointer!">
                <i class="fas fa-chevron-left text-black" style="font-size: 12px;"></i>
              </button>
              <button type="button" data-type="{{ $type }}" data-direction="next" class="finance-request-page-button btn bg-white border! border-[#D9D9D9]! cursor-pointer!">
                <i class="fas fa-chevron-right text-black" style="font-size: 12px;"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>
</section>

<div class="modal fade" id="expenseRealizationModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Expense Realization</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="financeRequestId">
        <div class="mb-3">
          <label class="form-label"><strong>Original Expenses:</strong></label>
          <div class="table-responsive">
            <table class="table table-sm table-bordered" id="originalExpenseTable">
              <thead class="table-light">
                <tr>
                  <th>Type</th>
                  <th>Notes</th>
                  <th class="text-end">Amount</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>

        <hr>

        <div class="mb-3">
          <label class="form-label"><strong>Realized Expenses:</strong></label>
          <table class="table table-bordered table-sm" id="realizationExpenseTable">
            <thead class="table-light">
              <tr>
                <th>Type</th>
                <th>Notes</th>
                <th style="width: 150px;">Amount</th>
                <th style="width: 40px;"></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <select name="realization_expense_type_id[]" class="form-select realization-type">
                    <option value="">-- Select Type --</option>
                  </select>
                </td>
                <td>
                  <input type="text" name="realization_expense_notes[]" class="form-control" placeholder="Notes">
                </td>
                <td>
                  <input type="number" step="0.01" name="realization_expense_amount[]" class="form-control text-end" placeholder="0.00">
                </td>
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-danger remove-realization-expense">&times;</button>
                </td>
              </tr>
            </tbody>
          </table>
          <button type="button" id="addRealizationExpense" class="btn btn-sm btn-outline-primary">Add Expense</button>
        </div>

        <div class="mb-3">
          <label for="realizationNotes" class="form-label">Notes</label>
          <textarea id="realizationNotes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" id="submitRealization" class="btn btn-primary">Create & Approve</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
  const financeRequestTypes = @json(array_keys($financeRequestTypes));
  const financeRequestState = {
    type: 'meeting-expense',
    search: '',
    branch_id: '',
    sales_id: '',
    status: '',
    date_mode: 'requested_at',
    date_start: '',
    date_end: '',
    page: {},
    per_page: {}
  };

  let financeRequestDatePicker = null;
  let financeRequestSearchTimeout = null;
  let expenseTypes = [];
  let currentMeetingExpenseId = null;

  financeRequestTypes.forEach(function (type) {
    financeRequestState.page[type] = 1;
    financeRequestState.per_page[type] = 10;
  });

  $.get('/api/expense-types', function(data) {
    expenseTypes = data || [];
    populateExpenseTypeSelects();
  });

  function populateExpenseTypeSelects() {
    const options = expenseTypes.map(function (expenseType) {
      return '<option value="' + escapeHtml(expenseType.id) + '">' + escapeHtml(expenseType.name) + '</option>';
    }).join('');
    $('#realizationExpenseTable .realization-type').html('<option value="">-- Select Type --</option>' + options);
  }

  function escapeHtml(value) {
    return $('<div>').text(value == null || value === '' ? '-' : value).html();
  }

  function formatDate(value) {
    if (!value) return '-';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';

    return date.toLocaleString('en-GB', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function getFilterPayload() {
    return {
      search: financeRequestState.search,
      branch_id: financeRequestState.branch_id,
      sales_id: financeRequestState.sales_id,
      status: financeRequestState.status,
      date_mode: financeRequestState.date_mode,
      date_start: financeRequestState.date_start,
      date_end: financeRequestState.date_end
    };
  }

  function getListPayload(type) {
    return {
      ...getFilterPayload(),
      type: type,
      page: financeRequestState.page[type],
      per_page: financeRequestState.per_page[type]
    };
  }

  function setActiveTab(type) {
    financeRequestState.type = type;

    $('.finance-request-tab').each(function () {
      const isActive = $(this).data('type') === type;
      $(this).toggleClass('bg-[#E7F3EE] text-[#115640]', isActive);
      $(this).toggleClass('bg-white text-[#1E1E1E]', !isActive);
    });

    $('.finance-request-table-container').addClass('hidden');
    $('[data-type-wrapper="' + type + '"]').removeClass('hidden');
  }

  function resetActivePage() {
    financeRequestState.page[financeRequestState.type] = 1;
  }

  function refreshActiveTabAndCounts() {
    loadFinanceRequestRows(financeRequestState.type);
    loadFinanceRequestCounts();
  }

  function loadFinanceRequestRows(type) {
    const tbody = $('#finance-request-table-body-' + type);
    const colSpan = 10;

    tbody.html('<tr><td colspan="' + colSpan + '" class="p-4 text-center text-[#6B7786]">Loading...</td></tr>');

    $.ajax({
      url: '/api/finance-requests/list',
      type: 'GET',
      dataType: 'json',
      data: getListPayload(type),
      success: function (response) {
        renderFinanceRequestRows(type, response.data || []);
        updateFinanceRequestPagination(type, response);
      },
      error: function (xhr) {
        const message = xhr.responseJSON?.message || 'Failed to load finance requests.';
        tbody.html('<tr><td colspan="' + colSpan + '" class="p-4 text-center text-[#900B09]">' + escapeHtml(message) + '</td></tr>');
        updateFinanceRequestPagination(type, { total: 0, from: 0, to: 0, current_page: 1, last_page: 1 });
      }
    });
  }

  function renderFinanceRequestRows(type, rows) {
    const tbody = $('#finance-request-table-body-' + type);

    if (!rows.length) {
      tbody.html('<tr><td colspan="10" class="p-4 text-center text-[#6B7786]">No finance requests found.</td></tr>');
      return;
    }

    const html = rows.map(function (row) {
      const firstContext = type === 'payment-confirmation'
        ? formatDate(row.payment_date)
        : formatDate(row.meeting_date);
      const secondContext = type === 'payment-confirmation'
        ? row.reference
        : row.location;

      return [
        '<tr class="border-b border-b-[#F0F0F0] text-[#1E1E1E]">',
          '<td class="p-2 lg:p-3">' + escapeHtml(row.branch_name) + '</td>',
          '<td class="p-2 lg:p-3">' + escapeHtml(row.sales_name) + '</td>',
          '<td class="p-2 lg:p-3">' + escapeHtml(row.lead_name) + '</td>',
          '<td class="p-2 lg:p-3">' + escapeHtml(firstContext) + '</td>',
          '<td class="p-2 lg:p-3">' + escapeHtml(secondContext) + '</td>',
          '<td class="p-2 lg:p-3">' + escapeHtml(formatDate(row.requested_at)) + '</td>',
          '<td class="p-2 lg:p-3">' + escapeHtml(formatDate(row.decided_at)) + '</td>',
          '<td class="p-2 text-right lg:p-3">' + escapeHtml(row.amount) + '</td>',
          '<td class="p-2 text-center lg:p-3">' + (row.status_badge || '-') + '</td>',
          '<td class="p-2 text-center lg:p-3">' + (row.actions || '-') + '</td>',
        '</tr>'
      ].join('');
    }).join('');

    tbody.html(html);
  }

  function updateFinanceRequestPagination(type, response) {
    const total = response.total || 0;
    const from = response.from || 0;
    const to = response.to || 0;
    const currentPage = response.current_page || 1;
    const lastPage = response.last_page || 1;

    financeRequestState.page[type] = currentPage;
    $('#finance-request-showing-' + type).text('Showing ' + from + '-' + to + ' of ' + total);

    $('.finance-request-page-button[data-type="' + type + '"][data-direction="prev"]').prop('disabled', currentPage <= 1);
    $('.finance-request-page-button[data-type="' + type + '"][data-direction="next"]').prop('disabled', currentPage >= lastPage);
  }

  function loadFinanceRequestCounts() {
    $.ajax({
      url: '/api/finance-requests/counts',
      type: 'GET',
      dataType: 'json',
      data: getFilterPayload(),
      success: function (counts) {
        financeRequestTypes.forEach(function (type) {
          $('#finance-request-count-' + type).text(counts[type] || 0);
        });
      }
    });
  }

  function updateSalesOptions() {
    const branchId = financeRequestState.branch_id;
    const salesSelect = $('#finance-request-sales-filter');
    let currentSalesVisible = true;

    salesSelect.find('option').each(function () {
      const option = $(this);
      const value = option.val();

      if (!value) {
        option.prop('hidden', false);
        return;
      }

      const optionBranch = String(option.data('branch-id') || '');
      const isVisible = !branchId || optionBranch === String(branchId);
      option.prop('hidden', !isVisible);

      if (value === financeRequestState.sales_id && !isVisible) {
        currentSalesVisible = false;
      }
    });

    if (!currentSalesVisible) {
      financeRequestState.sales_id = '';
      salesSelect.val('');
    }
  }

  function initFlatpickr() {
    const input = document.getElementById('finance-request-date-range');
    if (input && typeof flatpickr !== 'undefined') {
      financeRequestDatePicker = flatpickr(input, {
        mode: 'range',
        inline: true,
        dateFormat: 'Y-m-d'
      });
    }
  }

  function closeDateDropdown() {
    $('#finance-request-date-dropdown').addClass('opacity-0 scale-95 pointer-events-none');
    $('#finance-request-date-icon').removeClass('rotate-180');
  }

  function resetFinanceRequestFilters() {
    clearTimeout(financeRequestSearchTimeout);
    financeRequestState.search = '';
    financeRequestState.branch_id = '';
    financeRequestState.sales_id = '';
    financeRequestState.status = '';
    financeRequestState.date_mode = 'requested_at';
    financeRequestState.date_start = '';
    financeRequestState.date_end = '';

    financeRequestTypes.forEach(function (type) {
      financeRequestState.page[type] = 1;
    });

    $('.finance-request-search-input').val('');
    $('#finance-request-branch-filter').val('');
    $('#finance-request-sales-filter').val('');
    $('#finance-request-status-filter').val('');
    $('#finance-request-date-mode').val('requested_at');
    $('#finance-request-date-label').text('Date');
    $('#finance-request-date-range').val('');
    updateSalesOptions();
    closeDateDropdown();

    if (financeRequestDatePicker) {
      financeRequestDatePicker.clear();
    }

    refreshActiveTabAndCounts();
  }

  $('.finance-request-tab').on('click', function () {
    const type = $(this).data('type');
    setActiveTab(type);
    refreshActiveTabAndCounts();
  });

  $('.finance-request-search-input').on('input', function () {
    const value = $(this).val();
    $('.finance-request-search-input').val(value);
    clearTimeout(financeRequestSearchTimeout);

    financeRequestSearchTimeout = setTimeout(function () {
      financeRequestState.search = value;
      resetActivePage();
      refreshActiveTabAndCounts();
    }, 350);
  });

  $('#finance-request-branch-filter').on('change', function () {
    financeRequestState.branch_id = $(this).val();
    updateSalesOptions();
    resetActivePage();
    refreshActiveTabAndCounts();
  });

  $('#finance-request-sales-filter').on('change', function () {
    financeRequestState.sales_id = $(this).val();
    resetActivePage();
    refreshActiveTabAndCounts();
  });

  $('#finance-request-status-filter').on('change', function () {
    financeRequestState.status = $(this).val();
    resetActivePage();
    refreshActiveTabAndCounts();
  });

  $('#finance-request-date-toggle').on('click', function () {
    $('#finance-request-date-dropdown').toggleClass('opacity-0 scale-95 pointer-events-none');
    $('#finance-request-date-icon').toggleClass('rotate-180');
    if (financeRequestDatePicker) {
      financeRequestDatePicker.open();
    }
  });

  $('#finance-request-date-cancel').on('click', closeDateDropdown);

  $('#finance-request-date-apply').on('click', function () {
    const selectedDates = financeRequestDatePicker ? financeRequestDatePicker.selectedDates : [];
    if (selectedDates.length !== 2) return;

    financeRequestState.date_mode = $('#finance-request-date-mode').val() || 'requested_at';
    financeRequestState.date_start = financeRequestDatePicker.formatDate(selectedDates[0], 'Y-m-d');
    financeRequestState.date_end = financeRequestDatePicker.formatDate(selectedDates[1], 'Y-m-d');

    $('#finance-request-date-label').text(financeRequestState.date_start + ' -> ' + financeRequestState.date_end);
    resetActivePage();
    refreshActiveTabAndCounts();
    closeDateDropdown();
  });

  $('#finance-request-reset-filter').on('click', resetFinanceRequestFilters);

  $('.finance-request-page-size').on('change', function () {
    const type = $(this).data('type');
    financeRequestState.per_page[type] = Number($(this).val()) || 10;
    financeRequestState.page[type] = 1;
    loadFinanceRequestRows(type);
  });

  $('.finance-request-page-button').on('click', function () {
    const type = $(this).data('type');
    const direction = $(this).data('direction');
    const currentPage = financeRequestState.page[type] || 1;

    financeRequestState.page[type] = direction === 'next'
      ? currentPage + 1
      : Math.max(currentPage - 1, 1);

    loadFinanceRequestRows(type);
  });

  $(document).on('click', function (event) {
    const target = $(event.target);
    if (!target.closest('#finance-request-date-dropdown, #finance-request-date-toggle').length) {
      closeDateDropdown();
    }
  });

  $(document).on('click', '.btn-create-realization', function() {
    const financeRequestId = $(this).data('id');
    const meetingExpenseId = $(this).data('meeting-expense-id');

    $('#financeRequestId').val(financeRequestId);
    currentMeetingExpenseId = meetingExpenseId;

    $.get('/api/meeting-expense-details/' + meetingExpenseId, function(data) {
      const tbody = $('#originalExpenseTable tbody');
      tbody.empty();

      let totalAmount = 0;
      (data || []).forEach(function (detail) {
        const amount = Number(detail.amount || 0);
        tbody.append(
          '<tr>' +
            '<td>' + escapeHtml(detail.expense_type?.name || '-') + '</td>' +
            '<td>' + escapeHtml(detail.notes || '-') + '</td>' +
            '<td class="text-end">Rp ' + new Intl.NumberFormat('id-ID').format(amount) + '</td>' +
          '</tr>'
        );
        totalAmount += amount;
      });

      tbody.append(
        '<tr class="table-light fw-bold">' +
          '<td colspan="2">Total Original Amount</td>' +
          '<td class="text-end">Rp ' + new Intl.NumberFormat('id-ID').format(totalAmount) + '</td>' +
        '</tr>'
      );
    });

    $('#expenseRealizationModal').modal('show');
  });

  $('#addRealizationExpense').on('click', function() {
    const row = $('#realizationExpenseTable tbody tr:first').clone();
    row.find('input').val('');
    row.find('select').val('');
    $('#realizationExpenseTable tbody').append(row);
  });

  $(document).on('click', '.remove-realization-expense', function() {
    if ($('#realizationExpenseTable tbody tr').length > 1) {
      $(this).closest('tr').remove();
    }
  });

  $('#submitRealization').on('click', function() {
    const financeRequestId = $('#financeRequestId').val();
    const realizationData = [];
    let isValid = true;

    $('#realizationExpenseTable tbody tr').each(function() {
      const typeId = $(this).find('[name="realization_expense_type_id[]"]').val();
      const notes = $(this).find('[name="realization_expense_notes[]"]').val();
      const amount = $(this).find('[name="realization_expense_amount[]"]').val();

      if (!typeId || !amount) {
        isValid = false;
        return false;
      }

      realizationData.push({
        expense_type_id: typeId,
        notes: notes,
        amount: amount
      });
    });

    if (!isValid) {
      alert('Please fill in all expense details');
      return;
    }

    $.ajax({
      url: '/api/finance-requests/approve-with-realization',
      type: 'POST',
      data: {
        finance_request_id: financeRequestId,
        meeting_expense_id: currentMeetingExpenseId,
        realization_expenses: realizationData,
        notes: $('#realizationNotes').val()
      },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          $('#expenseRealizationModal').modal('hide');
          refreshActiveTabAndCounts();
        } else {
          alert(response.message);
        }
      },
      error: function(xhr) {
        alert('Error: ' + (xhr.responseJSON?.message || 'Failed to process'));
      }
    });
  });

  initFlatpickr();
  updateSalesOptions();
  setActiveTab(financeRequestState.type);
  refreshActiveTabAndCounts();
});
</script>
@endsection
