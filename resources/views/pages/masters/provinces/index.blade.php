@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Provinces</strong>
    </div>
    <div class="card-body pt-4">
      @include('partials.common.create-btn', [
          'url' => route('masters.provinces.form'),
          'title' => 'Province'
      ])

      <div class="table-responsive">
        <table id="provincesTable" class="table table-bordered table-sm w-100">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Regional</th>
              <th>Name</th>
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
  $('#provincesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route("masters.provinces.list") }}',
      type: 'POST',
      data: { _token: '{{ csrf_token() }}' }
    },
    columns: [
      { data: 'id', visible: false },
      { data: 'regional_name' },
      { data: 'name' },
      { data: 'actions', orderable: false, searchable: false, className: 'text-center', width: '200px' }
    ],
    order: [[0, 'desc']]
  });
});
</script>
@endsection
