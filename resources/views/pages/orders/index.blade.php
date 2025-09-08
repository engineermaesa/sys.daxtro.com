@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">

    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <strong>Orders</strong>
      <div class="d-flex gap-2">
        <button id="btnExport" class="btn btn-sm btn-success mr-2" type="button">
          <i class="bi bi-download me-1"></i> Export Excel
        </button>
        <button class="btn btn-sm btn-outline-primary" type="button" data-toggle="collapse" data-target="#filterCollapse">
          <i class="bi bi-funnel-fill me-1"></i> Toggle Filters
        </button>
      </div>
    </div>

    <div class="collapse" id="filterCollapse">
      <div class="card-body pt-3 pb-0">
        @php
          $showRegionFilter = $roleCode !== 'sales';
        @endphp
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <input type="date" id="filter_start" class="form-control form-control-sm" placeholder="Start date" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3">
            <input type="date" id="filter_end" class="form-control form-control-sm" placeholder="End date" onfocus="this.showPicker()">
          </div>
        </div>
        <div class="row g-2">
          <div class="col-md-2">
            <label class="form-label mb-0">Branch</label>
            <select id="filter_branch" class="form-select form-select-sm select2">
              <option value="">All</option>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label mb-0">Source</label>
            <select id="filter_source" class="form-select form-select-sm select2">
              <option value="">All</option>
              @foreach ($sources as $source)
                <option value="{{ $source->id }}">{{ $source->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label mb-0">Segment</label>
            <select id="filter_segment" class="form-select form-select-sm select2">
              <option value="">All</option>
              @foreach ($segments as $segment)
                <option value="{{ $segment->id }}">{{ $segment->name }}</option>
              @endforeach
            </select>
          </div>
          @if($showRegionFilter)
          <div class="col-md-2">
            <label class="form-label mb-0">Region</label>
            <select id="filter_region" class="form-select form-select-sm select2">
              <option value="">All</option>
              @foreach ($regions as $region)
                <option value="{{ $region->id }}" data-branch="{{ $region->branch_id }}">{{ $region->name }}</option>
              @endforeach
            </select>
          </div>
          @endif
          <div class="col-md-2">
            <label class="form-label mb-0">Min Total Billing</label>
            <input type="number" step="1" min="0" id="filter_min_total" class="form-control form-control-sm" placeholder="0">
          </div>
          <div class="col-md-2">
            <label class="form-label mb-0">Max Total Billing</label>
            <input type="number" step="1" min="0" id="filter_max_total" class="form-control form-control-sm" placeholder="0">
          </div>
          <div class="col-md-2 d-grid" style="margin-top: 20px;">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnFilter">
              <i class="bi bi-search me-1"></i> Apply Filters
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="card-body pt-4">
      <ul class="nav nav-tabs mb-3 w-100 no-border full-clean" id="orderTabs" role="tablist">
        <li class="nav-item flex-fill text-center">
          <a class="nav-link active" id="all-tab" data-toggle="tab" href="#all" role="tab" style="border:none;font-weight:500;">
            All Payments
            <span class="badge badge-pill bg-primary" id="all-count">{{ $counts['all'] ?? 0 }}</span>
          </a>
        </li>
        <li class="nav-item flex-fill text-center">
          <a class="nav-link" id="pending-tab" data-toggle="tab" href="#pending" role="tab" style="border:none;font-weight:500;">
            Pending Payment
            <span class="badge badge-pill bg-warning" id="pending-count">{{ $counts['pending'] ?? 0 }}</span>
          </a>
        </li>
        <li class="nav-item flex-fill text-center">
          <a class="nav-link" id="complete-tab" data-toggle="tab" href="#complete" role="tab" style="border:none;font-weight:500;">
            Complete Payment
            <span class="badge badge-pill bg-success" id="complete-count">{{ $counts['complete'] ?? 0 }}</span>
          </a>
        </li>
      </ul>

      <div class="tab-content" id="orderTabsContent">
        <div class="tab-pane fade show active" id="all" role="tabpanel">
          <div class="table-responsive">
            <table id="allOrdersTable" class="table table-bordered table-sm w-100">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Order No</th>
                  <th>Customer</th>
                  <th>Source</th>
                  <th>Segment</th>
                  @if($showRegionFilter)
                  <th>Region</th>
                  @endif
                  <th>Quotation No</th>
                  <th>Total Billing</th>
                  <th>Status</th>
                  <th>Complete Termin</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
        <div class="tab-pane fade" id="pending" role="tabpanel">
          <div class="table-responsive">
            <table id="pendingOrdersTable" class="table table-bordered table-sm w-100">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Order No</th>
                  <th>Customer</th>
                  <th>Source</th>
                  <th>Segment</th>
                  @if($showRegionFilter)
                  <th>Region</th>
                  @endif
                  <th>Quotation No</th>
                  <th>Total Billing</th>
                  <th>Status</th>
                  <th>Complete Termin</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
        <div class="tab-pane fade" id="complete" role="tabpanel">
          <div class="table-responsive">
            <table id="completeOrdersTable" class="table table-bordered table-sm w-100">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Order No</th>
                  <th>Customer</th>
                  <th>Source</th>
                  <th>Segment</th>
                  @if($showRegionFilter)
                  <th>Region</th>
                  @endif
                  <th>Quotation No</th>
                  <th>Total Billing</th>
                  <th>Status</th>
                  <th>Complete Termin</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Progress Logs Modal -->
<div class="modal fade" id="progressModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Progress Logs</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <ul class="nav nav-tabs mb-3" id="progressTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <a class="nav-link active" id="progress-log-tab" data-toggle="tab" href="#progress-log" role="tab">Progress Log</a>
          </li>
          <li class="nav-item" role="presentation">
            <a class="nav-link" id="activity-log-tab" data-toggle="tab" href="#activity-log" role="tab">Activity Log</a>
          </li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane fade show active" id="progress-log" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm table-bordered" id="progressLogsTable">
                <thead class="table-light">
                  <tr>
                    <th>Date</th>
                    <th>Step</th>
                    <th>Note</th>
                    <th>User</th>
                    <th>Attachment</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
          <div class="tab-pane fade" id="activity-log" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm table-bordered" id="activityLogsTable">
                <thead class="table-light">
                  <tr>
                    <th>Date</th>
                    <th>Activity</th>
                    <th>Note</th>
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
  </div>

</div>
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

  filterRegions();
  $('#filter_branch').on('change', filterRegions);

  function loadCounts() {
    $.post('{{ route('orders.counts') }}', {
      _token: '{{ csrf_token() }}',
      segment_id: $('#filter_segment').val(),
      source_id: $('#filter_source').val(),
      region_id: $('#filter_region').val(),
      branch_id: $('#filter_branch').val(),
      start_date: $('#filter_start').val(),
      end_date: $('#filter_end').val(),
      min_total: $('#filter_min_total').val(),
      max_total: $('#filter_max_total').val()
    }, function(res){
      $('#all-count').text(res.all);
      $('#pending-count').text(res.pending);
      $('#complete-count').text(res.complete);
    });
  }

  function initTable(selector, status) {
    return $(selector).DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '{{ route("orders.list") }}',
        type: 'POST',
        data: function (d) {
          d._token = '{{ csrf_token() }}';
          d.segment_id = $('#filter_segment').val();
          d.source_id = $('#filter_source').val();
          d.region_id = $('#filter_region').val();
          d.branch_id = $('#filter_branch').val();
          d.start_date = $('#filter_start').val();
          d.end_date = $('#filter_end').val();
          d.min_total = $('#filter_min_total').val();
          d.max_total = $('#filter_max_total').val();
          d.payment_status = status;
        }
      },
      columns: [
        { data: 'id', visible: false },
        { data: 'order_no' },
        { data: 'customer_name' },
        { data: 'source_name' },
        { data: 'segment_name' },
        @if($showRegionFilter)
        { data: 'region_name' },
        @endif
        { data: 'quotation_no' },
        {
          data: 'total_billing',
          className: 'text-end',
          render: $.fn.dataTable.render.number('.', ',', 0, 'Rp ')
        },
        { data: 'status' },
        { data: 'completion' },
        { data: 'actions', orderable: false, searchable: false, className: 'text-center', width: '160px' }
      ],
      order: [[0, 'desc']]
    });
  }

  const allTable = initTable('#allOrdersTable', '');
  const pendingTable = initTable('#pendingOrdersTable', 'pending');
  const completeTable = initTable('#completeOrdersTable', 'complete');

  $('#btnFilter').on('click', function(){
    allTable.ajax.reload();
    pendingTable.ajax.reload();
    completeTable.ajax.reload();
    loadCounts();
  });

  $('#btnExport').on('click', function(){
    const params = new URLSearchParams({
      segment_id: $('#filter_segment').val(),
      source_id: $('#filter_source').val(),
      region_id: $('#filter_region').val(),
      branch_id: $('#filter_branch').val(),
      start_date: $('#filter_start').val(),
      end_date: $('#filter_end').val(),
      min_total: $('#filter_min_total').val(),
      max_total: $('#filter_max_total').val()
    });
    window.location = '{{ route('orders.export') }}?' + params.toString();
  });

  $('#allOrdersTable, #pendingOrdersTable, #completeOrdersTable').on('click', '.btn-progress-log', function(){
    const orderId = $(this).data('order');
    const progressTbody = $('#progressLogsTable tbody');
    const activityTbody = $('#activityLogsTable tbody');
    progressTbody.html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');
    activityTbody.html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');
    $('#progressModal').modal('show');
    $.get('{{ url('orders') }}/' + orderId + '/progress-logs', function(data){
      let rows = '';
      data.forEach(function(item){
        let attachment = '-';
        if (item.attachment) {
          attachment = '<a href="'+item.attachment+'" target="_blank" class="btn btn-sm btn-outline-secondary">Download</a>';
        }
        rows += '<tr>'+
                  '<td>'+item.logged_at+'</td>'+
                  '<td>'+item.step+' - '+item.step_label+'</td>'+
                  '<td>'+ (item.note || '') +'</td>'+
                  '<td>'+item.user+'</td>'+
                  '<td>'+attachment+'</td>'+
                '</tr>';
      });
      progressTbody.html(rows || '<tr><td colspan="5" class="text-center">No logs</td></tr>');
    });
    $.get('{{ url('orders') }}/' + orderId + '/activity-logs', function(data){
      let rows = '';
      data.forEach(function(item){
        rows += '<tr>'+
                  '<td>'+item.date+'</td>'+
                  '<td>'+item.action+'</td>'+
                  '<td>'+ (item.description || '') +'</td>'+
                  '<td>'+item.user+'</td>'+
                '</tr>';
      });
      activityTbody.html(rows || '<tr><td colspan="4" class="text-center">No logs</td></tr>');
    });
  });

  $('#orderTabs a[data-toggle="tab"]').on('shown.bs.tab', function(){
    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
  });

loadCounts();
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