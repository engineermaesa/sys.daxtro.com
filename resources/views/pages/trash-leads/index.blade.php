@extends('layouts.app')

@section('content')
{{-- <section class="section">
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
</section> --}}

<section class="min-h-screen">
  {{-- HEADER PAGES --}}
  <div class="pt-4">
      <div class="flex items-center gap-3">        
      <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12ZM2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V4C0 3.45 0.195833 2.97917 0.5875 2.5875C0.979167 2.19583 1.45 2 2 2H3V1C3 0.716667 3.09583 0.479167 3.2875 0.2875C3.47917 0.0958333 3.71667 0 4 0C4.28333 0 4.52083 0.0958333 4.7125 0.2875C4.90417 0.479167 5 0.716667 5 1V2H13V1C13 0.716667 13.0958 0.479167 13.2875 0.2875C13.4792 0.0958333 13.7167 0 14 0C14.2833 0 14.5208 0.0958333 14.7125 0.2875C14.9042 0.479167 15 0.716667 15 1V2H16C16.55 2 17.0208 2.19583 17.4125 2.5875C17.8042 2.97917 18 3.45 18 4V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2Z" fill="#115640"/>
      </svg>
      <h1 class="text-[#115640] font-semibold text-2xl">Leads</h1>
      </div>
      <p class="mt-1 text-[#115640] text-lg">Trash Leads</p>
  </div>

  <div class="mt-4 bg-white rounded-lg border-r border-l border-t border-[#D9D9D9]">
    {{-- NAVIGATION TABLES --}}
    <div class="flex justify-between items-center border-b border-[#D9D9D9] p-3 gap-4">
        {{-- SEARCH TABLES --}}
        <div class="w-1/6 border border-gray-300 rounded-lg flex items-center p-2">
            <div class="px-2">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.5 13C4.68333 13 3.14583 12.3708 1.8875 11.1125C0.629167 9.85417 0 8.31667 0 6.5C0 4.68333 0.629167 3.14583 1.8875 1.8875C3.14583 0.629167 4.68333 0 6.5 0C8.31667 0 9.85417 0.629167 11.1125 1.8875C12.3708 3.14583 13 4.68333 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.8125 10.5625 9.6875 9.6875C10.5625 8.8125 11 7.75 11 6.5C11 5.25 10.5625 4.1875 9.6875 3.3125C8.8125 2.4375 7.75 2 6.5 2C5.25 2 4.1875 2.4375 3.3125 3.3125C2.4375 4.1875 2 5.25 2 6.5C2 7.75 2.4375 8.8125 3.3125 9.6875C4.1875 10.5625 5.25 11 6.5 11Z" fill="#6B7786"/>
                </svg>
            </div>
            <input type="text" placeholder="Search" class="w-full px-3 py-1 border-none focus:outline-[#115640] "/>
        </div>
        {{-- NAVIGATION STATUS TABLES --}}
        <div class="w-4/6 border border-[#D5D5D5] rounded-lg grid grid-cols-4">
            @foreach (['all', 'cold', 'warm', 'hot'] as $tab)
                {{-- NAVIGATION STATUS --}}
                  <div class="text-center cursor-pointer py-2 h-full border-r border-r-[#D5D5D5] nav-leads-active" data-status="{{ $tab }}">
                    <p class="text-[#083224]">
                        {{ $loop->first ? 'All Status' : ucfirst($tab) }}
                        <span 
                            class="{{ 
                                $tab === 'all' 
                                    ? 'span-all' 
                                    : ($tab === 'cold' 
                                        ? 'span-cold' 
                                        : ($tab === 'warm' 
                                            ? 'span-warm' 
                                            : 'span-hot'
                                        )
                                    )
                                }}">
                            {{ $loop->first ? '(' . $leadCounts['all'] . ')' : $leadCounts[$tab] }}
                        </span>
                    </p>
                </div>
            @endforeach
        </div>
        {{-- Manual add removed: trash leads are automated --}}
    </div>

    {{-- CONTENTS TABLES --}}
    <div class="">
      @foreach(['cold', 'warm', 'hot'] as $tab)
        <div data-status-wrapper="{{ $tab }}">
          <table id="{{ $tab }}TrashLeadsTableTailwind" class="w-full">
                    {{-- HEADER TABLE --}}
                    <thead class="text-[#1E1E1E]">
                        <tr class="border-b border-b-[#CFD5DC]">
                            <th class="hidden">ID (hidden)</th>
                            <th class="p-3">
                                Claimed At
                            </th>
                            <th class="">  
                                Lead Name
                            </th>
                            <th>
                                Segment
                            </th>
                            <th>
                                Source
                            </th>
                            <th>
                                First Sales
                            </th>
                            <th>
                              Status
                            </th>
                            <th class="text-center">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody id="{{ $tab }}BodyTailwind"></tbody>
                </table>
            </div>
        @endforeach
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
    @php
      $trashRoutes = [];
      if(\Illuminate\Support\Facades\Route::has('trash-leads.cold.list')) {
        $trashRoutes['cold'] = route('trash-leads.cold.list');
      }
      if(\Illuminate\Support\Facades\Route::has('trash-leads.warm.list')) {
        $trashRoutes['warm'] = route('trash-leads.warm.list');
      }
      if(\Illuminate\Support\Facades\Route::has('trash-leads.hot.list')) {
        $trashRoutes['hot'] = route('trash-leads.hot.list');
      }
    @endphp
    const trashRoutes = @json($trashRoutes);

    function initTailwindTable(tab){
      const selector = '#' + tab + 'TrashLeadsTableTailwind';
      if(!trashRoutes[tab]){ console.warn('No route defined for '+tab); return; }
      if(!$.fn.dataTable.isDataTable(selector)){
        initTrashTable(selector, trashRoutes[tab]);
      } else {
        $(selector).DataTable().ajax.reload();
      }
    }

    function showTables(status){
        if(status === 'all'){
        $('[data-status-wrapper]').show();
        ['cold','warm','hot'].forEach(function(tab){
          if(trashRoutes[tab]){
            initTailwindTable(tab);
          }
        });
      } else {
        $('[data-status-wrapper]').hide();
        $('[data-status-wrapper="'+status+'"]').show();
        if(trashRoutes[status]){
          initTailwindTable(status);
        }
      }
      $('.nav-leads-active').removeClass('active-nav');
      $('.nav-leads-active[data-status="'+status+'"]').addClass('active-nav');
    }

    // init: show all
    showTables('all');

    // nav click
    $(document).on('click', '.nav-leads-active', function(){
      const status = $(this).data('status');
      if(!status) return;
      showTables(status);
    });

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
  @if(\Illuminate\Support\Facades\Route::has('trash-leads.cold.list'))
    initTrashTable('#coldTrashLeadsTable', '{{ route('trash-leads.cold.list') }}');
  @endif
  @if(\Illuminate\Support\Facades\Route::has('trash-leads.warm.list'))
    initTrashTable('#warmTrashLeadsTable', '{{ route('trash-leads.warm.list') }}');
  @endif
  
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
