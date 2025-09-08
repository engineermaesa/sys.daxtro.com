@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Accounts</strong>
    </div>
    <div class="card-body pt-4">
      @include('partials.common.create-btn', [
          'url' => route('masters.accounts.form'),
          'title' => 'Account'
      ])

      <div class="table-responsive">
        <table id="accountsTable" class="table table-bordered table-sm w-100">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Company</th>
              <th>Bank</th>
              <th>Account Number</th>
              <th>Holder Name</th>
              <th class="text-center">Actions</th>
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
  $('#accountsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route("masters.accounts.list") }}',
      type: 'POST',
      data: { _token: '{{ csrf_token() }}' }
    },
    columns: [
      { data: 'id', visible: false },
      { data: 'company_name' },
      { data: 'bank_name' },
      { data: 'account_number' },
      { data: 'holder_name' },
      { data: 'actions', orderable: false, searchable: false, className: 'text-center', width: '200px' }
    ],
    order: [[0, 'desc']]
  });
});
</script>
@endsection
