@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Finance Requests</strong>
    </div>
    <div class="card-body pt-4">
      <ul class="nav nav-tabs mb-3 w-100 no-border full-clean" id="financeTabs" role="tablist">
          @php
            $types = ['meeting-expense','payment-confirmation', 'expense-realization'];
            $badgeColors = [
              'meeting-expense' => 'info',
              'payment-confirmation' => 'warning',
              'expense-realization' => 'secondary',
            ];
          @endphp
          @foreach($types as $t)
            <li class="nav-item flex-fill text-center" role="presentation">
              <a href="#"
                class="nav-link {{ $loop->first ? 'active' : '' }}"
                data-type="{{ $t }}"
                role="tab"
                style="border: none; font-weight: 500;">
                {{ ucwords(str_replace('-', ' ', $t)) }}
                <span class="badge badge-pill badge-{{ $badgeColors[$t] }}">
                  {{ $counts[$t] }}
                </span>
              </a>
            </li>
          @endforeach
      </ul>

      <div class="table-responsive">
        <table id="financeRequestsTable" class="table table-bordered table-sm w-100">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Status</th>
              <th>Requester</th>
              <th>Meeting Date</th>
              <th>Requested At</th>
              <th>Decided At</th>
              <th>Amount</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</section>

<!-- Expense Realization Modal -->
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
  let currentType = 'meeting-expense';
  let expenseTypes = [];
  let currentMeetingExpenseId = null;

  // Fetch expense types
  $.get('/api/expense-types', function(data) {
    expenseTypes = data;
    populateExpenseTypeSelects();
  });

  function populateExpenseTypeSelects() {
    const options = expenseTypes.map(et => `<option value="${et.id}">${et.name}</option>`).join('');
    $('#realizationExpenseTable .realization-type').html('<option value="">-- Select Type --</option>' + options);
  }

  $('.nav-link[data-type="' + currentType + '"]').addClass('active');

  const table = $('#financeRequestsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route("finance-requests.list") }}',
      type: 'POST',
      data: function (d) {
        d._token = '{{ csrf_token() }}';
        d.type = currentType;
      }
    },
    columns: [
      { data: 'id', visible: false },
      { data: 'status_badge', orderable: false, searchable: false },
      { data: 'requester_name' },
      { 
        data: 'meeting_date', 
        visible: false,
        render: function (data) {
          if (!data) return '';
          return new Date(data).toLocaleString('en-GB', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
          });
        }
      },
      {
        data: 'created_at', name: 'created_at',
        render: function (data) {
          if (!data) return '';
          return new Date(data).toLocaleString('en-GB', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
          });
        }
      },
      {
        data: 'decided_at', name: 'decided_at',
        render: function (data) {
          if (!data) return '';
          return new Date(data).toLocaleString('en-GB', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
          });
        }
      },
      { data: 'amount', className: 'text-end' },
      { data: 'actions', orderable: false, searchable: false, className: 'text-center', width: '160px' }
    ],
    order: [[0, 'desc']],
    columnDefs: [
      {
        targets: [3, 4],
        visible: false
      }
    ]
  });

  $('#financeTabs .nav-link').on('click', function (e) {
    e.preventDefault();
    $('.nav-link').removeClass('active');
    $(this).addClass('active');
    currentType = $(this).data('type');
    
    if (currentType === 'expense-realization') {
      table.column(3).visible(true);
      table.column(4).visible(true);
    } else {
      table.column(3).visible(false);
      table.column(4).visible(false);
    }
    
    table.ajax.reload();
  });

  // Open expense realization modal for meeting expense
  $(document).on('click', '.btn-create-realization', function() {
    const financeRequestId = $(this).data('id');
    const meetingExpenseId = $(this).data('meeting-expense-id');
    
    $('#financeRequestId').val(financeRequestId);
    currentMeetingExpenseId = meetingExpenseId;

    // Fetch original expenses
    $.get('/api/meeting-expense-details/' + meetingExpenseId, function(data) {
      const tbody = $('#originalExpenseTable tbody');
      tbody.empty();
      
      let totalAmount = 0;
      data.forEach(detail => {
        tbody.append(`
          <tr>
            <td>${detail.expense_type.name}</td>
            <td>${detail.notes || '-'}</td>
            <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(detail.amount)}</td>
          </tr>
        `);
        totalAmount += detail.amount;
      });
      
      tbody.append(`
        <tr class="table-light fw-bold">
          <td colspan="2">Total Original Amount</td>
          <td class="text-end">Rp ${new Intl.NumberFormat('id-ID').format(totalAmount)}</td>
        </tr>
      `);
    });

    $('#expenseRealizationModal').modal('show');
  });

  // Add realization expense row
  $('#addRealizationExpense').on('click', function() {
    const row = $('#realizationExpenseTable tbody tr:first').clone();
    row.find('input').val('');
    row.find('select').val('');
    $('#realizationExpenseTable tbody').append(row);
  });

  // Remove realization expense row
  $(document).on('click', '.remove-realization-expense', function() {
    if ($('#realizationExpenseTable tbody tr').length > 1) {
      $(this).closest('tr').remove();
    }
  });

  // Submit realization and approve
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
      url: '{{ route("finance-requests.approve-with-realization") }}',
      type: 'POST',
      data: {
        _token: '{{ csrf_token() }}',
        finance_request_id: financeRequestId,
        meeting_expense_id: currentMeetingExpenseId,
        realization_expenses: realizationData,
        notes: $('#realizationNotes').val()
      },
      success: function(response) {
        if (response.success) {
          alert(response.message);
          $('#expenseRealizationModal').modal('hide');
          table.ajax.reload();
        } else {
          alert(response.message);
        }
      },
      error: function(xhr) {
        alert('Error: ' + (xhr.responseJSON?.message || 'Failed to process'));
      }
    });
  });
});
</script>
@endsection