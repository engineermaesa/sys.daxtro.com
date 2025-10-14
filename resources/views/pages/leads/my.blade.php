@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>My Leads</strong>
      <div class="d-flex gap-2">
        <a href="{{ route('leads.my.form') }}" class="btn btn-sm btn-primary mr-2">
            <i class="bi bi-plus-circle me-1"></i> Add Manual Leads
        </a>
        <button id="toggleFilterBtn" class="btn btn-sm btn-outline-primary" type="button" data-toggle="collapse" data-target="#filterCollapse">
          <i class="bi bi-funnel-fill me-1"></i> Toggle Filters
        </button>
      </div>
    </div>
    <div class="collapse" id="filterCollapse">
      <div class="card-body pt-3 pb-0">
        <div id="filterNote" class="text-muted small mb-2"></div>
        <div class="row mb-3" id="dateFilterRow">
          <div class="col-md-3">
            <input type="date" id="filter_start" class="form-control form-control-sm" placeholder="Start date" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3">
            <input type="date" id="filter_end" class="form-control form-control-sm" placeholder="End date" onfocus="this.showPicker()">
          </div>
          <div class="col-md-2 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnFilter">
              <i class="bi bi-search me-1"></i> Apply Filter
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="card-body pt-4">

      {{-- Custom Full-Width Tab Navigation --}}
      <ul class="nav nav-tabs mb-3 w-100 no-border" id="leadTabs" role="tablist">
        @foreach (['cold', 'warm', 'hot', 'deal'] as $tab)
          <li class="nav-item flex-fill text-center" style="border: none;">
            <a class="nav-link {{ $loop->first ? 'active' : '' }}"
               id="{{ $tab }}-tab"
               data-toggle="tab"
               href="#{{ $tab }}"
               role="tab"
               style="border: none; font-weight: 500;">
              {{ ucfirst($tab) }}
              <span class="badge badge-pill badge-{{ $tab === 'cold' ? 'primary' : ($tab === 'warm' ? 'warning' : ($tab === 'hot' ? 'danger' : 'success')) }}">
                {{ $leadCounts[$tab] ?? 0 }}
              </span>
            </a>
          </li>
        @endforeach
      </ul>

      <div class="tab-content" id="leadTabsContent">
        @foreach (['cold', 'warm', 'hot', 'deal'] as $tab)
          <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
               id="{{ $tab }}"
               role="tabpanel"
               aria-labelledby="{{ $tab }}-tab">            
            <div class="table-responsive">
              <table id="{{ $tab }}LeadsTable"
                     class="table table-sm w-100">
                <thead class="thead-light">
                  <tr>
                    <th>ID (hidden)</th>
                    @if($tab === 'cold')
                      <th>Nama</th>
                      <th>Sales Name</th>
                      <th>Telephone</th>
                      <th>Source</th>
                      <th>Needs</th>
                      <th>Segment</th>
                      <th>City</th>
                      <th>Regional</th>
                      <th class="text-center">Status</th>
                    @else
                      <th>Claimed At</th>
                      <th>Lead Name</th>
                      <th>Segment</th>
                      <th class="text-center">Status</th>
                    @endif
                    <th class="text-center">Action</th>
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

<!-- Activity Logs Modal -->
<div class="modal fade" id="activityLogModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Activity Logs</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="table-responsive mb-3">
          <table class="table table-sm table-bordered">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>Activity</th>
                <th>Note</th>
                <th>Attachment</th>
                <th>User</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <form id="activityLogForm">
          <div class="form-row align-items-end">
            <div class="col-md-3">
              <select name="activity_id" class="form-control form-control-sm" required>
                <option value="">-- Activity --</option>
                @foreach($activities as $act)
                  <option value="{{ $act->id }}">{{ $act->code }} - {{ $act->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <input type="date" name="logged_at" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required onfocus="this.showPicker()">
            </div>
            <div class="col-md-3">
              <input type="text" name="note" class="form-control form-control-sm" placeholder="Note">
            </div>
            <div class="col-md-3">
              <div class="custom-file">
                <input type="file" class="custom-file-input" id="activity_attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                <label class="custom-file-label" for="activity_attachment">Attachment</label>
              </div>
            </div>
            <div class="col-md-1">
              <button type="submit" class="btn btn-sm btn-primary">Add</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Quotation Logs Modal -->
<div class="modal fade" id="quotationLogModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Quotation Logs</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-sm table-bordered">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>Action</th>
                <th>User</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
function initLeadTable(selector, route, type = 'default') {
  let columns;
  if(type === 'cold') {
    columns = [
      { data: 'id', visible: false },
      { data: 'name' },
      { data: 'sales_name' },
      { data: 'phone' },
      { data: 'source', name: 'source' },
      { data: 'needs' },
      { data: 'segment_name' },
      { data: 'city_name' },
      { data: 'regional_name' },
      { data: 'meeting_status', orderable: false, searchable: false, className: 'text-center' },
      { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
    ];
  } else {
    columns = [
      { data: 'id', visible: false },
      { data: 'claimed_at', name: 'claimed_at', render: function (data) { if(!data) return ''; return new Date(data).toLocaleString('en-GB', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'}); } },
      { data: 'lead_name', name: 'lead_name' },
      { data: 'segment_name', name: 'segment_name' },
      { data: 'meeting_status', orderable: false, searchable: false, className: 'text-center' },
      { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
    ];
  }

  return $(selector).DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url : route,
      type: 'POST',
      data: function(d){
        d._token = '{{ csrf_token() }}';
        d.start_date = $('#filter_start').val();
        d.end_date = $('#filter_end').val();
      }
    },
    columns: columns,
    order: [[0, 'desc']]
  });
}

$(function () {
  const coldTable = initLeadTable('#coldLeadsTable', '{{ route("leads.my.cold.list") }}', 'cold');
  const warmTable = initLeadTable('#warmLeadsTable', '{{ route("leads.my.warm.list") }}');
  const hotTable  = initLeadTable('#hotLeadsTable',  '{{ route("leads.my.hot.list") }}');
  const dealTable = initLeadTable('#dealLeadsTable', '{{ route("leads.my.deal.list") }}');

  const notes = {
    warm: 'Filter tanggal berdasarkan Tanggal Approve pertama',
    hot:  'Filter tanggal berdasarkan Booking Fee',
    deal: 'Filter tanggal berdasarkan Termin Satu (Complete)'
  };

  const $toggleFilterBtn = $('#toggleFilterBtn');
  const $filterCollapse = $('#filterCollapse');

  function updateBadgeCounts() {
    $.post('{{ route('leads.my.counts') }}', {
      _token: '{{ csrf_token() }}',
      start_date: $('#filter_start').val(),
      end_date: $('#filter_end').val()
    }, function(data){
      $('#cold-tab .badge').text(data.cold);
      $('#warm-tab .badge').text(data.warm);
      $('#hot-tab .badge').text(data.hot);
      $('#deal-tab .badge').text(data.deal);
    });
  }

  function updateFilterVisibility() {
    const activeId = $('#leadTabs .nav-link.active').attr('id');
    if (activeId === 'cold-tab') {
      $('#dateFilterRow').hide();
      $('#filterNote').text('');
      $filterCollapse.collapse('hide');
      $toggleFilterBtn.hide();
    } else {
      $('#dateFilterRow').show();
      $toggleFilterBtn.show();
      if (activeId === 'warm-tab') $('#filterNote').text(notes.warm);
      if (activeId === 'hot-tab')  $('#filterNote').text(notes.hot);
      if (activeId === 'deal-tab') $('#filterNote').text(notes.deal);
    }
  }

  updateFilterVisibility();
  $('#leadTabs a[data-toggle="tab"]').on('shown.bs.tab', updateFilterVisibility);
  updateBadgeCounts();

  $('#btnFilter').on('click', function(){
    warmTable.ajax.reload();
    hotTable.ajax.reload();
    dealTable.ajax.reload();
    updateBadgeCounts();
  });

  const fileInput = document.getElementById('activity_attachment');
  if (fileInput) {
    fileInput.addEventListener('change', function (e) {
      const name = e.target.files[0]?.name || 'Attachment';
      e.target.nextElementSibling.innerText = name;
    });
  }
});

// Cancel Meeting
$(document).on('click', '.cancel-meeting', function (e) {
  e.preventDefault();
  const url = $(this).data('url');
  const isOnline = $(this).data('online') === 1 || $(this).data('online') === '1';
  const isRejected = $(this).data('status') === 'rejected';
  const text = isOnline || isRejected
    ? 'Are you sure you want to cancel this meeting?'
    : 'Please return the expense to finance before cancelling. Have you returned it?';

  Swal.fire({
    title: 'Cancel Meeting',
    text: text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes',
    cancelButtonText: 'No',
    confirmButtonColor: '#d33',
    cancelButtonColor: '#aaa'
  }).then((result) => {
    if (result.isConfirmed) {
      $.post(url, { _token: $('meta[name="csrf-token"]').attr('content') }, function (res) {
        notif(res.message || 'Meeting canceled');
        $('#coldLeadsTable').DataTable().ajax.reload();
      }).fail(function (xhr) {
        let err = 'Failed to cancel meeting';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          err = xhr.responseJSON.message;
        }
        notif(err, 'error');
      });
    }
  });
});

// Activity Logs
$(document).on('click', '.btn-activity-log', function (e) {
  e.preventDefault();
  const url = $(this).data('url');
  const tbody = $('#activityLogModal tbody');
  $('#activityLogForm').data('url', url);
  tbody.html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');
  $('#activityLogModal').modal('show');
  $.get(url, function(data){
    let rows = '';
    data.forEach(function(item){
      rows += '<tr>'+
                '<td>'+item.logged_at+'</td>'+
                '<td>'+item.code+' - '+item.activity+'</td>'+
                '<td>'+ (item.note || '') +'</td>'+
                '<td>'+(item.attachment ? '<a href="'+item.attachment+'" class="btn btn-sm btn-outline-secondary">Download</a>' : '-')+'</td>'+
                '<td>'+item.user+'</td>'+
              '</tr>';
    });
    tbody.html(rows || '<tr><td colspan="5" class="text-center">No logs</td></tr>');
  });
});

$('#activityLogForm').on('submit', function(e){
  e.preventDefault();
  const url = $(this).data('url');
  const formData = new FormData(this);
  formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
  $.ajax({
    url: url,
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(res){
      notif(res.message || 'Saved');
      $("#activityLogForm input[name='note']").val('');
      $('#activity_attachment').val('');
      $('#activity_attachment').next('.custom-file-label').text('Attachment');
      $.get(url, function(data){
        let rows = '';
        data.forEach(function(item){
          rows += '<tr>'+
                    '<td>'+item.logged_at+'</td>'+
                    '<td>'+item.code+' - '+item.activity+'</td>'+
                    '<td>'+ (item.note || '') +'</td>'+
                    '<td>'+(item.attachment ? '<a href="'+item.attachment+'" class="btn btn-sm btn-outline-secondary">Download</a>' : '-')+'</td>'+
                    '<td>'+item.user+'</td>'+
                  '</tr>';
        });
        $('#activityLogModal tbody').html(rows || '<tr><td colspan="5" class="text-center">No logs</td></tr>');
      });
    },
    error: function(xhr){
      let err = 'Failed to save log';
      if(xhr.responseJSON && xhr.responseJSON.message){
        err = xhr.responseJSON.message;
      }
      notif(err, 'error');
    }
  });
});

// Quotation Logs
$(document).on('click', '.btn-quotation-log', function (e) {
  e.preventDefault();
  const url = $(this).data('url');
  const tbody = $('#quotationLogModal tbody');
  tbody.html('<tr><td colspan="3" class="text-center">Loading...</td></tr>');
  $('#quotationLogModal').modal('show');
  $.get(url, function(data){
    let rows = '';
    data.forEach(function(item){
      rows += '<tr>'+
                '<td>'+item.logged_at+'</td>'+
                '<td>'+item.action+'</td>'+
                '<td>'+item.user+'</td>'+
              '</tr>';
    });
    tbody.html(rows || '<tr><td colspan="3" class="text-center">No logs</td></tr>');
  });
});

// Trash Lead
$(document).on('click', '.trash-lead', function (e) {
  e.preventDefault();
  const url = $(this).data('url');

  Swal.fire({
    title: 'Trash Lead',
    text: 'Provide a reason for trashing this lead',
    input: 'textarea',
    inputAttributes: { required: true },
    inputPlaceholder: 'Enter reason here...',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Submit',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#d33',
    cancelButtonColor: '#aaa',
    preConfirm: (note) => {
      if (!note) {
        Swal.showValidationMessage('Note is required');
      }
      return note;
    }
  }).then((result) => {
    if (result.isConfirmed) {
      $.post(url, { _token: $('meta[name="csrf-token"]').attr('content'), note: result.value }, function (res) {
        notif(res.message || 'Lead moved to trash');
        $('#coldLeadsTable').DataTable().ajax.reload();
        $('#warmLeadsTable').DataTable().ajax.reload();
      }).fail(function (xhr) {
        let err = 'Failed to trash lead';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          err = xhr.responseJSON.message;
        }
        notif(err, 'error');
      });
    }
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
