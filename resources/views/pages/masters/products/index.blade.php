@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Products</strong>
    </div>
    <div class="card-body pt-4">
        <div class="d-flex justify-content-end mb-2">
        <a href="{{ route('masters.products.import') }}" class="btn btn-sm btn-secondary me-2">
          <i class="bi bi-upload me-1"></i> Import Product
        </a>
        <a href="{{ route('masters.products.form') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-plus-circle me-1"></i> Add Product
        </a>
        </div>

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
  $('#productsTable').DataTable({
    processing: true,
    serverSide: true,
    scrollX: true,
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
      {
        data: 'actions',
        orderable: false,
        searchable: false,
        className: 'text-center'
      }
    ],
    order: [[0, 'desc']]
  });
});

// Flash message
$(function(){
  @if(session('success'))
    const flashSuccess = {!! json_encode(session('success')) !!};
    if (typeof Notyf !== 'undefined') {
      const n = new Notyf({position: {x: 'right', y: 'top'}, duration: 6000});
      n.success({message: flashSuccess});
    } else {
      alert(flashSuccess);
    }
  @endif

  @if(session('error'))
    const flashError = {!! json_encode(session('error')) !!};
    if (typeof Notyf !== 'undefined') {
      const n = new Notyf({position: {x: 'right', y: 'top'}, duration: 6000});
      n.error({message: flashError});
    } else {
      alert(flashError);
    }
  @endif
});
</script>

<style>
#productsTable th,
#productsTable td {
  white-space: nowrap;
  vertical-align: middle;
}
</style>
@endsection