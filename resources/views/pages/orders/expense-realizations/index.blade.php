@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>My Expense Realizations</strong>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="expenseRealizationsTable" class="table table-bordered table-sm w-100">
          <thead class="table-light">
            <tr>
              <th>Meeting</th>
              <th>Lead Name</th>
              <th>Meeting Date</th>
              <th>Original Amount</th>
              <th>Realized Amount</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
</section>
@endsection

@section('scripts')
<script>
$(function () {
  const table = $('#expenseRealizationsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route("expense-realizations.list") }}',
      type: 'POST',
      data: function (d) {
        d._token = '{{ csrf_token() }}';
      }
    },
    columns: [
      { data: 'meeting_info', orderable: false },
      { data: 'lead_name' },
      { data: 'meeting_date', render: function(data) {
        return data ? new Date(data).toLocaleString('en-GB', {
          day: '2-digit', month: 'short', year: 'numeric',
          hour: '2-digit', minute: '2-digit'
        }) : '-';
      }},
      { data: 'original_amount', className: 'text-end' },
      { data: 'realized_amount_formatted', className: 'text-end' },
      { data: 'status_badge', orderable: false },
      { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
    ],
    order: [[0, 'desc']]
  });
});

function submitRealization(id) {
  if (confirm('Are you sure you want to submit this expense realization for approval?')) {
    $.post('{{ route("expense-realizations.submit", ":id") }}'.replace(':id', id), {
      _token: '{{ csrf_token() }}'
    })
    .done(function(response) {
      if (response.success) {
        alert(response.message);
        $('#expenseRealizationsTable').DataTable().ajax.reload();
      } else {
        alert(response.message);
      }
    })
    .fail(function() {
      alert('Failed to submit expense realization.');
    });
  }
}
</script>
@endsection