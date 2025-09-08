@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Finance Requests</strong>
    </div>
    <div class="card-body pt-4">

      {{-- Updated Full-Width Tab Navigation (like Leads) --}}
      <ul class="nav nav-tabs mb-3 w-100 no-border full-clean" id="financeTabs" role="tablist">
        @php
          $types = ['meeting-expense','payment-confirmation'];
          $badgeColors = [
            'meeting-expense' => 'info',
            'payment-confirmation' => 'warning',
          ];
        @endphp
        @foreach($types as $t)
          <li class="nav-item flex-fill text-center" role="presentation">
            <a href="#"
               class="nav-link {{ $loop->first ? 'active' : '' }}"
               data-type="{{ $t }}"
               role="tab"
               style="border: none; font-weight: 500;">
              {{ ucwords(str_replace('-', ' ', $t)) }}
              <span class="badge badge-pill badge-{{ $badgeColors[$t] }}">
                {{-- Placeholder: count not passed here --}}
              </span>
            </a>
          </li>
        @endforeach
      </ul>

      <div class="table-responsive">
        <table id="financeRequestsTable" class="table table-bordered table-sm w-100">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Status</th>
              <th>Requester</th>
              <th>Requested At</th>
              <th>Decided At</th>
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
  let currentType = 'meeting-expense'; // Default tab
  $('.nav-link[data-type="' + currentType + '"]').addClass('active');

  const table = $('#financeRequestsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route("finance-requests.list") }}',
      type: 'POST',
      data: function (d) {
        d._token = '{{ csrf_token() }}';
        d.type = currentType;
      }
    },
    columns: [
      { data: 'id', visible: false },
      { data: 'status_badge', orderable: false, searchable: false },
      { data: 'requester_name' },
      {
        data: 'created_at', name: 'created_at',
        render: function (data) {
          if (!data) return '';
          return new Date(data).toLocaleString('en-GB', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
          });
        }
      },
      {
        data: 'decided_at', name: 'decided_at',
        render: function (data) {
          if (!data) return '';
          return new Date(data).toLocaleString('en-GB', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
          });
        }
      },
      { data: 'actions', orderable: false, searchable: false, className: 'text-center', width: '160px' }
    ],
    order: [[0, 'desc']]
  });

  $('#financeTabs .nav-link').on('click', function (e) {
    e.preventDefault();
    $('.nav-link').removeClass('active');
    $(this).addClass('active');
    currentType = $(this).data('type');
    table.ajax.reload();
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
  gap: 0.5rem;
  padding: 0;
  margin: 0;
}

.nav-tabs.full-clean .nav-item {
  flex: 1;
  text-align: center;
}

.nav-tabs.full-clean .nav-link {
  display: block;
  width: 100%;
  border: none;
  font-weight: 500;
  padding: 0.6rem 0.75rem;
  border-radius: 0.5rem;
  background-color: transparent;
  transition: background-color 0.2s ease;
}

.nav-tabs.full-clean .nav-link.active {
  background-color: #3d63d2 !important;
  color: #fff !important;
}

.nav-tabs.full-clean .nav-link .badge {
  margin-left: 0.35rem;
  font-size: 0.75rem;
  vertical-align: middle;
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

  .nav-tabs .nav-item.show .nav-link,
  .nav-tabs .nav-link.active {
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
