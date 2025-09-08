@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">
    <div class="card-header"><strong>Trash Leads</strong></div>
    <div class="card-body pt-4">
      <ul class="nav nav-tabs mb-3 w-100 no-border" id="trashLeadTabs" role="tablist">
        @foreach (['cold', 'warm'] as $tab)
          <li class="nav-item flex-fill text-center" style="border: none;">
            <a class="nav-link {{ $loop->first ? 'active' : '' }}"
               id="{{ $tab }}-tab"
               data-toggle="tab"
               href="#{{ $tab }}"
               role="tab"
               style="border: none; font-weight: 500;">
              {{ ucfirst($tab) }}
              <span class="badge badge-pill badge-{{ $tab === 'cold' ? 'primary' : 'warning' }}">
                {{ $leadCounts[$tab] ?? 0 }}
              </span>
            </a>
          </li>
        @endforeach
      </ul>

      <div class="tab-content" id="trashLeadTabsContent">
        @foreach (['cold', 'warm'] as $tab)
          <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
               id="{{ $tab }}"
               role="tabpanel"
               aria-labelledby="{{ $tab }}-tab">
            <div class="table-responsive">
              <table id="{{ $tab }}TrashLeadsTable" class="table table-sm w-100">
                <thead class="thead-light">
                  <tr>
                    <th>ID (hidden)</th>
                    <th>Claimed At</th>
                    <th>Lead Name</th>
                    <th>Segment</th>
                    <th>Source</th>
                    <th>First Sales</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>
        @endforeach
      </div>

    </div>
  </div>
</section>

<!-- Assign Lead Modal -->
<div class="modal fade" id="assignLeadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="assignLeadForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Assign Lead</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="assign_claim_id">
        <div class="mb-3">
          <label for="assign_branch_id" class="form-label">Branch</label>
          <select id="assign_branch_id" class="form-select select2">
            <option value="">-- Select Branch --</option>
            @foreach($branches as $branch)
              <option value="{{ $branch->id }}">{{ $branch->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-3">
          <label for="assign_sales_id" class="form-label">Sales</label>
          <select id="assign_sales_id" class="form-select select2">
            <option value="">-- Select Sales --</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Assign</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
function initTrashTable(selector, route) {
  $(selector).DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url : route,
      type: 'POST',
      data: {
        _token : '{{ csrf_token() }}'
      }
    },
    columns: [
      { data: 'id', visible: false },
      { data: 'claimed_at', name: 'claimed_at', render: function (data) { if(!data) return ''; return new Date(data).toLocaleString('en-GB', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'}); } },
      { data: 'lead_name', name: 'lead_name' },
      { data: 'segment_name', name: 'segment_name' },
      { data: 'source_name', name: 'source_name' },
      { data: 'first_sales_name', name: 'first_sales_name' },
      { data: 'meeting_status', orderable: false, searchable: false, className: 'text-center' },
      { data: 'actions', orderable: false, searchable: false, className: 'text-center', width: '200px' }
    ],
    order: [[0, 'desc']]
  });
}

$(function () {
  initTrashTable('#coldTrashLeadsTable', '{{ route('trash-leads.cold.list') }}');
  initTrashTable('#warmTrashLeadsTable', '{{ route('trash-leads.warm.list') }}');
  
  $(document).on('click', '.restore-lead', function(){
    const url = $(this).data('url');

    Swal.fire({
      title: 'Restore Lead',
      text: 'This lead will be restored to your list.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#aaa'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(url, {_token: '{{ csrf_token() }}'}, function(res){
          notif(res.message || 'Lead restored successfully');
          $('#coldTrashLeadsTable').DataTable().ajax.reload();
          $('#warmTrashLeadsTable').DataTable().ajax.reload();
        }).fail(function(xhr){
          let err = 'Failed to restore lead';
          if(xhr.responseJSON && xhr.responseJSON.message){
            err = xhr.responseJSON.message;
          }
          notif(err, 'error');
        });
      }
    });
  });

  $(document).on('click', '.assign-lead', function(){
    const claimId = $(this).data('claim');
    const branchId = $(this).data('branch');
    $('#assign_claim_id').val(claimId);
    $('#assign_branch_id').val(branchId).trigger('change');
    $('#assignLeadModal').modal('show');
  });

  function loadSales(branchId) {
    const url = '{{ url('users/sales-by-branch') }}/' + branchId;
    const $sales = $('#assign_sales_id');
    $sales.html('<option value="">-- Select Sales --</option>');
    if(!branchId) return;
    $.get(url, function(data){
      data.forEach(function(u){
        $sales.append('<option value="'+u.id+'">'+u.name+'</option>');
      });
    });
  }

  $('#assign_branch_id').on('change', function(){
    loadSales($(this).val());
  });

  $('#assignLeadForm').on('submit', function(e){
    e.preventDefault();
    const claimId = $('#assign_claim_id').val();
    const salesId = $('#assign_sales_id').val();
    if(!salesId) { notif('Please select sales', 'error'); return; }
    const url = '{{ url('trash-leads/assign') }}/' + claimId;
    $.post(url, {sales_id: salesId, _token: '{{ csrf_token() }}'}, function(res){
      notif(res.message || 'Lead assigned successfully');
      $('#assignLeadModal').modal('hide');
      $('#coldTrashLeadsTable').DataTable().ajax.reload();
      $('#warmTrashLeadsTable').DataTable().ajax.reload();
    }).fail(function(xhr){
      let err = 'Failed to assign lead';
      if(xhr.responseJSON && xhr.responseJSON.message){
        err = xhr.responseJSON.message;
      }
      notif(err, 'error');
    });
  });
});
</script>
@endsection

@section('styles')
<style>
  .nav-tabs.full-clean {
    border-bottom: none;
    display: flex;
    width: 100%;
    justify-content: space-between;
  }

  .nav-tabs.full-clean .nav-item {
    flex: 1;
    text-align: center;
  }

  .nav-tabs.full-clean .nav-link {
    border: none;
    background: transparent;
    font-weight: 500;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    transition: background-color 0.3s ease;
  }

  .nav-tabs .nav-item.show .nav-link, .nav-tabs .nav-link.active {
    background-color: #115641 !important;
    color: white !important;
  }

  .nav-tabs.full-clean .nav-link .badge {
    margin-left: 0.4rem;
    font-size: 85%;
    vertical-align: middle;
  }
</style>
@endsection
