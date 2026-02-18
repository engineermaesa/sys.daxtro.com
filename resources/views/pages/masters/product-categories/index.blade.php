@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Product Categories</strong>
    </div>
    <div class="card-body pt-4">
        @include('partials.common.create-btn', [
          'url' => $formUrl ?? ($apiFormUrl ?? route('masters.product-categories.form')),
          'title' => 'Product Category'
        ])

      <div class="table-responsive">
        <table id="productCategoriesTable" class="table table-bordered table-sm w-100">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Code</th>
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
  $('#productCategoriesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ $listUrl ?? route("masters.product-categories.list") }}',
      type: 'GET',
      headers: { 'Accept': 'application/json' }
    },
    columns: [
      { data: 'id', visible: false },
      { data: 'name' },
      { data: 'code' },
      { data: 'actions', orderable: false, searchable: false, className: 'text-center', width: '200px' }
    ],
    order: [[0, 'desc']]
  });
});
</script>
@endsection
