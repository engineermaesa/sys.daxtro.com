@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Parts</strong>
    </div>
    <div class="card-body pt-4">
      @include('partials.common.create-btn', [
          'url' => route('masters.parts.form'),
          'title' => 'Part'
      ])

      <div class="table-responsive">
        <table id="partsTable" class="table table-bordered table-sm w-100">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Code / SKU</th>
              <th>Name</th>
              <th>Price</th>
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
  $('#partsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route("masters.parts.list") }}',
      type: 'GET'
    },
    columns: [
      { data: 'id', visible: false },
      { data: 'sku' },
      { data: 'name' },
      { 
        data: 'price',
        render: $.fn.dataTable.render.number('.', ',', 0, 'Rp')
      },
      { data: 'actions', orderable: false, searchable: false, className: 'text-center', width: '200px' }
    ],
    order: [[0, 'desc']]
  });
});
</script>
@endsection
