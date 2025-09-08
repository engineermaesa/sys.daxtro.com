@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Products</strong>
    </div>
    <div class="card-body pt-4">
      @include('partials.common.create-btn', [
          'url' => route('masters.products.form'),
          'title' => 'Product'
      ])

      <div class="table-responsive">
        <table id="productsTable" class="table table-bordered table-sm w-100">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Type</th>
              <th>SKU</th>
              <th>Name</th>
              <th>Corp. Price</th>
              <th>Gov. Price</th>
              <th>Personal Price</th>
              <th>FOB Price</th>
              <th>BDI Price</th>
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
  $('#productsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route("masters.products.list") }}',
      type: 'POST',
      data: { _token: '{{ csrf_token() }}' }
    },
    columns: [
      { data: 'id', visible: false },
      { data: 'product_type_name' },
      { data: 'sku' },
      { data: 'name' },
      {
        data: 'corporate_price',
        render: $.fn.dataTable.render.number('.', ',', 0, 'Rp')
      },
      {
        data: 'government_price',
        render: $.fn.dataTable.render.number('.', ',', 0, 'Rp')
      },
      {
        data: 'personal_price',
        render: $.fn.dataTable.render.number('.', ',', 0, 'Rp')
      },
      {
        data: 'fob_price',
        render: $.fn.dataTable.render.number('.', ',', 0, 'Rp')
      },
      {
        data: 'bdi_price',
        render: $.fn.dataTable.render.number('.', ',', 0, 'Rp')
      },
      { data: 'actions', orderable: false, searchable: false, className: 'text-center', width: '200px' }
    ],
    order: [[0, 'desc']]
  });
});
</script>
@endsection
