@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <strong>Available Leads</strong>
      @php
        $showFilter = in_array(auth()->user()->role?->code, ['super_admin', 'branch_manager', 'finance_director', 'accountant_director']);
      @endphp
      <div class="d-flex gap-2">
        <button id="btnExport" class="btn btn-sm btn-success mr-2" type="button">
          <i class="bi bi-download me-1"></i> Export Excel
        </button>
        @if($showFilter)
        <button class="btn btn-sm btn-outline-primary" type="button" data-toggle="collapse" data-target="#filterCollapse">
          <i class="bi bi-funnel-fill me-1"></i> Toggle Filters
        </button>
        @endif
      </div>
    </div>

    @if($showFilter)
    <div class="collapse" id="filterCollapse">
      <div class="card-body pt-3 pb-0">
        <div class="row g-2">
          <div class="col-md-2">
            <select id="filter_branch" class="form-select form-select-sm select2">
              <option value="">-- Branch --</option>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <select id="filter_region" class="form-select form-select-sm select2">
              <option value="">-- Regional --</option>
              @foreach ($regions as $region)
                <option value="{{ $region->id }}" data-branch="{{ $region->branch_id }}">{{ $region->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnFilter">
              <i class="bi bi-search me-1"></i> Apply Filters
            </button>
          </div>
        </div>
      </div>
    </div>
    @endif
    <div class="card-body pt-4">      

      <div class="table-responsive overflow-auto">
        <table id="availableLeadsTable" class="table table-bordered table-sm nowrap w-100">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Published At</th>
              <th>Name</th>
              <th>Branch</th>
              <th>Regional</th>
              <th>Source</th>
              <th>Segment</th>
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
  function filterRegions() {
    const branchId = $('#filter_branch').val();
    $('#filter_region option').each(function(){
      if(!branchId || $(this).val() === '') {
        $(this).show();
      } else {
        $(this).toggle($(this).data('branch') == branchId);
      }
    });
    if($('#filter_region option:selected').is(':hidden')) {
      $('#filter_region').val('');
    }
  }

  $('#filter_branch').on('change', filterRegions);
  filterRegions();

  const table = $('#availableLeadsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route("leads.available.list") }}',
      type: 'POST',
      data: function(d){
        d.branch_id = $('#filter_branch').val();
        d.region_id = $('#filter_region').val();
        d._token = '{{ csrf_token() }}';
      }
    },
    columns: [
      { data: 'id', visible: false },
      { data: 'published_at', name: 'published_at', render: function (data) { if(!data) return ''; return new Date(data).toLocaleString('en-GB', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'}); } },
      { data: 'name' },
      { data: 'branch_name' },
      { data: 'region_name' },
      { data: 'source_name' },
      { data: 'segment_name' },
      { data: 'actions', orderable: false, searchable: false, className: 'text-center', width: '200px' }
    ],
    order: [[0, 'desc']]
  });

  $('#btnFilter').on('click', function(){
    table.ajax.reload();
  });

  $('#btnExport').on('click', function(){
    const params = new URLSearchParams();
    const branchInput = $('#filter_branch');
    const regionInput = $('#filter_region');

    if (branchInput.length && branchInput.val()) {
      params.append('branch_id', branchInput.val());
    }

    if (regionInput.length && regionInput.val()) {
      params.append('region_id', regionInput.val());
    }

    const query = params.toString();
    const url = '{{ route('leads.available.export') }}' + (query ? '?' + query : '');
    window.location = url;
  });

  $(document).on('click', '.claim-lead', function(e){
    e.preventDefault();
    const url = $(this).attr('href');

    Swal.fire({
      title: 'Are you sure?',
      text: 'You are about to claim this lead.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, claim it!',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#aaa'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(url, {_token: '{{ csrf_token() }}'}, function(){
          notif('Lead claimed successfully');
          $('#availableLeadsTable').DataTable().ajax.reload();
        }).fail(function(xhr){
          let err = 'Failed to claim lead';
          if(xhr.responseJSON && xhr.responseJSON.message){
            err = xhr.responseJSON.message;
          }
          notif(err, 'error');
        });
      }
    });
  });
});
</script>
@endsection
