@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <strong>Admins</strong>
      <button class="btn btn-sm btn-outline-primary" type="button" data-toggle="collapse" data-target="#filterCollapse">
        <i class="bi bi-funnel-fill me-1"></i> Toggle Filters
      </button>
    </div>

    <div class="collapse" id="filterCollapse">
      <div class="card-body pt-3 pb-0">
        <div class="row g-2">
          <div class="col-md-2">
            <select id="filter_company" class="form-select form-select-sm select2">
              <option value="">-- Company --</option>
              @foreach ($companies as $company)
                <option value="{{ $company->id }}">{{ $company->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <select id="filter_branch" class="form-select form-select-sm select2">
              <option value="">-- Branch --</option>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" data-company="{{ $branch->company_id }}">{{ $branch->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <select id="filter_role" class="form-select form-select-sm select2">
              <option value="">-- Role --</option>
              @foreach ($roles as $role)
                <option value="{{ $role->id }}">{{ $role->name }}</option>
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

    <div class="card-body pt-4">
      @include('partials.common.create-btn', [
          'url' => route('users.form'),
          'title' => 'Admin'
      ])

      <div class="table-responsive overflow-auto">
        <table id="adminsTable" class="table table-bordered table-sm nowrap w-100">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Role</th>
              <th>Company</th>
              <th>Branch</th>
              <th>Name</th>
              <th>NIP</th>
              <th>Email</th>
              <th>Target</th>
              <th>Registered At</th>
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
    const role = @json(auth()->user()->role->code ?? '');
    const hideCompany = ['finance_director', 'accountant_director', 'branch_manager'].includes(role);
    const hideBranch  = role === 'branch_manager';

    const columns = [
      { data: 'id', visible: false },
      { data: 'role_name' },
      { data: 'company_name', visible: !hideCompany },
      { data: 'branch_name',  visible: !hideBranch },
      { data: 'name', name: 'name' },
      { data: 'nip', name: 'nip' },
      { data: 'email', name: 'email' },
      { data: 'target', render: function(data) { return data ? 'Rp' + data : '-'; } },
      { data: 'created_at', render: function (data) { if(!data) return ''; return new Date(data).toLocaleString('en-GB', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'}); } },
      { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
    ];

    const table = $('#adminsTable').DataTable({
      scrollX: true,
      processing: true,
      serverSide: true,
      ajax: {
        url: '{{ route("users.list") }}',
        type: 'GET',
        data: function(d){
          d.company_id = $('#filter_company').val();
          d.branch_id  = $('#filter_branch').val();
          d.role_id    = $('#filter_role').val();
        }
      },
      columns: columns,
      order: [[0, 'desc']]
    });

    $('#btnFilter').on('click', function(){
      table.ajax.reload();
    });
  });
  </script>
@endsection
