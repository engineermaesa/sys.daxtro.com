@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Access Privileges</strong>
    </div>
    <div class="card-body pt-4">
      <div class="table-responsive">
        <table id="privilegesTable" class="table table-bordered table-sm w-100">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Role</th>
              <th>Last Updated</th>
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
  $('#privilegesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route("settings.permissions-settings.list") }}',
      type: 'GET',
      dataType: 'json',
      headers: { 'Accept': 'application/json' }
    },
    columns: [
      { data: 'id', visible: false },
      { data: 'name' },
      { data: 'updated_at' },
      { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
    ],
    order: [[0, 'desc']]
  });
});
</script>
@endsection
