@extends('layouts.app')

@section('content')
<div class="min-h-screen">
  {{-- HEADER PAGES --}}
  <div class="pt-4">
    <div class="flex items-center gap-3">        
      <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12ZM2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V4C0 3.45 0.195833 2.97917 0.5875 2.5875C0.979167 2.19583 1.45 2 2 2H3V1C3 0.716667 3.09583 0.479167 3.2875 0.2875C3.47917 0.0958333 3.71667 0 4 0C4.28333 0 4.52083 0.0958333 4.7125 0.2875C4.90417 0.479167 5 0.716667 5 1V2H13V1C13 0.716667 13.0958 0.479167 13.2875 0.2875C13.4792 0.0958333 13.7167 0 14 0C14.2833 0 14.5208 0.0958333 14.7125 0.2875C14.9042 0.479167 15 0.716667 15 1V2H16C16.55 2 17.0208 2.19583 17.4125 2.5875C17.8042 2.97917 18 3.45 18 4V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2Z" fill="#115640"/>
      </svg>
      <h1 class="text-[#115640] font-semibold text-2xl">Leads</h1>
    </div>
    <p class="mt-1 text-[#115640] text-lg">Available Leads</p>
  </div>
  <section class="bg-white rounded-lg mt-3">
    {{-- FILTERS SOURCE CONVERSION --}}
    <div class="flex px-3 py-4 gap-3">
      {{-- FILTERS SEARCH --}}
        <div class="w-1/4 border border-gray-300 rounded-lg flex items-center p-2">
            <div class="px-2">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.5 13C4.68333 13 3.14583 12.3708 1.8875 11.1125C0.629167 9.85417 0 8.31667 0 6.5C0 4.68333 0.629167 3.14583 1.8875 1.8875C3.14583 0.629167 4.68333 0 6.5 0C8.31667 0 9.85417 0.629167 11.1125 1.8875C12.3708 3.14583 13 4.68333 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.8125 10.5625 9.6875 9.6875C10.5625 8.8125 11 7.75 11 6.5C11 5.25 10.5625 4.1875 9.6875 3.3125C8.8125 2.4375 7.75 2 6.5 2C5.25 2 4.1875 2.4375 3.3125 3.3125C2.4375 4.1875 2 5.25 2 6.5C2 7.75 2.4375 8.8125 3.3125 9.6875C4.1875 10.5625 5.25 11 6.5 11Z" fill="#6B7786"/>
                </svg>
            </div>
            <input id="globalSearch" type="text" placeholder="Search..." class="w-full px-3 py-1 border-none focus:outline-[#115640] "/>
        </div>
        {{-- FILTERS MENUS --}}
        <div class="w-1/2 grid grid-cols-4 items-center border border-gray-300 rounded-lg">
            {{-- FILTERS BY --}}
            <div class="flex items-center justify-center gap-2 border-r border-r-[#CFD5DC] cursor-pointer py-2 h-full">                        
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7.02059 16C6.73725 16 6.49975 15.9042 6.30809 15.7125C6.11642 15.5208 6.02059 15.2833 6.02059 15V9L0.220588 1.6C-0.0294118 1.26667 -0.0669118 0.916667 0.108088 0.55C0.283088 0.183333 0.587255 0 1.02059 0H15.0206C15.4539 0 15.7581 0.183333 15.9331 0.55C16.1081 0.916667 16.0706 1.26667 15.8206 1.6L10.0206 9V15C10.0206 15.2833 9.92476 15.5208 9.73309 15.7125C9.54142 15.9042 9.30392 16 9.02059 16H7.02059ZM8.02059 8.3L12.9706 2H3.07059L8.02059 8.3Z" fill="#0D0F11"/>
                </svg>
                <p class="font-medium">Filter By</p>
            </div>
            {{-- SOURCES --}}
            <div id="conversionListSourceMenu" class="flex items-center justify-center gap-2 border-r border-r-[#CFD5DC] cursor-pointer py-2 h-full px-2">
                <select id="source-filter-new"
                class="w-full font-semibold text-center focus:outline-none cursor-pointer">
                  <option value="">All Source</option>
                  @foreach($leadSources as $source)
                    <option value="{{ $source->id }}">{{ $source->name }}</option>
                  @endforeach
                </select>
            </div>
            {{-- DATES --}}
            <div
              class="border-r border-r-[#CFD5DC] cursor-pointer w-full relative grid grid-cols-1 items-center h-full">

              {{-- TOGGLE --}}
              <div id="openDateDropdown" class="flex justify-center items-center gap-2">
                  <p id="dateLabel" class="font-medium text-black">Date</p>
                  <i id="iconDate" class="fas fa-chevron-down transition-transform duration-300 text-black" style="font-size: 12px;"></i>
              </div>

              {{-- DATE DROPDOWN --}}
              <div id="dateDropdown"
                  class="absolute top-full left-0 mt-2 bg-white rounded-lg shadow-xl w-[350px] p-4 z-50 opacity-0 scale-95 pointer-events-none transition-all duration-200 ease-out origin-top overflow-visible">

                  <h3 class="font-semibold mb-2">Select Date Range</h3>

                    <div class="flex justify-center items-center">
                      <input type="text" id="source-date-range" class="shadow-none w-full" placeholder="Select date range">
                    </div>

                  <div class="flex justify-end gap-2 mt-3">

                      <button id="cancelDate" class="px-3 py-1 text-[#303030]">
                          Cancel
                      </button>

                      <button id="applyDate"
                          class="px-3 py-1 bg-[#115640] text-white rounded-lg cursor-pointer">
                          Apply
                      </button>

                  </div>
              </div>
            </div>  
            {{-- RESET FILTER --}}
            <div class="flex items-center justify-center gap-2 py-2 cursor-pointer h-full">
                <i id="chevronFiltersReset" class="fa fa-redo transition-transform duration-300 text-[#900B09] -scale-x-100   " style="font-size: 12px;"></i>
                <p class="font-medium text-[#900B09]">Reset Filter</p>
            </div>
        </div>
        {{-- BUTTON EXCEL EXPORT --}}
        <button id="btnExport" class="w-1/4 bg-[#115640] text-white font-semibold rounded-lg cursor-pointer" type="button">
          <i class="bi bi-download me-1"></i> Export Excel
        </button>
    </div>
    {{-- TABLES AVAILABLE LEADS --}}
    <div class="bg-white rounded py-4 px-3">
      <table id="availableLeadsTable" class="w-full table-fixed">
        <thead class="border-b border-b-[#CFD5DC] border-t border-t-[#CFD5DC]">
          <tr>
            {{-- <th>ID</th> --}}
            <th class="font-bold text-left p-3">Published At</th>
            <th class="font-bold text-left">Name</th>
            <th class="font-bold text-left">Branch</th>
            <th class="font-bold text-left">Regional</th>
            <th class="font-bold text-left">Source</th>
            <th class="font-bold text-left">Segment</th>
            <th class="font-bold text-left">Actions</th>
          </tr>
        </thead>
        <tbody id="availableLeadsBody">
          
        </tbody>
      </table>
    </div>
  </section>
</div>
@endsection

@section('scripts')
<script>
$(function () {
  var fp = null;

  function initFlatpickr() {
    var input = document.getElementById('source-date-range');
    if (input && typeof flatpickr !== 'undefined') {
      fp = flatpickr(input, {
        mode: 'range',
        inline: true,
        dateFormat: 'Y-m-d',
        onClose: function(selectedDates, dateStr, instance) {
          // keep input populated; actual apply happens via Apply button
        }
      });
    }
  }

  function filterDate(){
    const openBtn = document.getElementById('openDateDropdown');
    const dropdown = document.getElementById('dateDropdown');
    const chevron = document.getElementById('iconDate');

    if (openBtn) {
      openBtn.onclick = () => {
        if (dropdown) {
          dropdown.classList.toggle('opacity-0');
          dropdown.classList.toggle('scale-95');
          dropdown.classList.toggle('pointer-events-none');
        }
        if (chevron) chevron.classList.toggle('rotate-180');
        if (fp) fp.open();
      };
    }

    const cancelBtn = document.getElementById('cancelDate');
    if (cancelBtn) cancelBtn.addEventListener('click', () => {
      if (dropdown) dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
    });

    const applyBtn = document.getElementById('applyDate');
    if (applyBtn) applyBtn.addEventListener('click', () => {
      const dates = (fp && fp.selectedDates) ? fp.selectedDates : [];
      if (dates.length !== 2) return;

      const startDate = dates[0].toISOString().split('T')[0];
      const endDate = dates[1].toISOString().split('T')[0];

      const label = document.getElementById('dateLabel');
      if (label) label.innerText = `${startDate} → ${endDate}`;

      loadAvailableLeads(startDate, endDate);

      if (dropdown) dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
    });
  }

  initFlatpickr();
  filterDate();

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

  // Reload when source filter changes
  $('#source-filter-new').on('change', function(){
    triggerLoadWithCurrentFilters();
  });

  // Debounced search handler
  function debounce(fn, wait){
    let t = null;
    return function(){
      const ctx = this, args = arguments;
      clearTimeout(t);
      t = setTimeout(() => fn.apply(ctx, args), wait);
    };
  }

  function triggerLoadWithCurrentFilters(){
    const dates = (fp && fp.selectedDates) ? fp.selectedDates : [];
    if (dates.length === 2) {
      const s = dates[0].toISOString().split('T')[0];
      const e = dates[1].toISOString().split('T')[0];
      loadAvailableLeads(s, e);
    } else {
      loadAvailableLeads();
    }
  }

  $('#globalSearch').on('input', debounce(function(){
    triggerLoadWithCurrentFilters();
  }, 400));

  function loadAvailableLeads(startDate = null, endDate = null) {
      const data = {
          branch_id: $('#filter_branch').val(),
          region_id: $('#filter_region').val(),
          source_id: $('#source-filter-new').length ? $('#source-filter-new').val() : null,
        q: $('#globalSearch').length ? $('#globalSearch').val().trim() : null,
          _token: '{{ csrf_token() }}'
      };

      if (startDate) data.start_date = startDate;
      if (endDate) data.end_date = endDate;

      $.ajax({
          url: '{{ route("leads.availables.list") }}',
          method: 'GET',
          data: data,
          success: function(res) {

              const tbody = $('#availableLeadsBody');
              tbody.empty();

              const rows = res.data ?? res;

              rows.forEach(row => {

                  const date = row.published_at
                      ? new Date(row.published_at).toLocaleString('en-GB', {
                          day:'2-digit',
                          month:'short',
                          year:'numeric',
                          hour:'2-digit',
                          minute:'2-digit'
                      })
                      : '';

                  tbody.append(`
                      <tr class="border-b border-b-[#CFD5DC]">
                          <td class="p-3">${date}</td>
                          <td>${row.name ?? ''}</td>
                          <td>${row.branch_name ?? ''}</td>
                          <td>${row.region_name ?? ''}</td>
                          <td>${row.source_name ?? ''}</td>
                          <td>${row.segment_name ?? ''}</td>
                            <td class="py-3 flex! items-center! justify-start! gap-3!">${row.actions ?? ''}</td>
                      </tr>
                  `);
              });
          }
      });
  }

  // FIRST LOAD
  loadAvailableLeads();

  // FILTER BUTTON
  $('#btnFilter').on('click', function(){
      const dates = (fp && fp.selectedDates) ? fp.selectedDates : [];
      if (dates.length === 2) {
        const s = dates[0].toISOString().split('T')[0];
        const e = dates[1].toISOString().split('T')[0];
        loadAvailableLeads(s, e);
      } else {
        loadAvailableLeads();
      }
  });

  $('#btnExport').on('click', function(){
    const params = new URLSearchParams();
    const branchInput = $('#filter_branch');
    const regionInput = $('#filter_region');
    const sourceInput = $('#source-filter-new');
    const qInput = $('#globalSearch');

    if (branchInput.length && branchInput.val()) {
      params.append('branch_id', branchInput.val());
    }

    if (regionInput.length && regionInput.val()) {
      params.append('region_id', regionInput.val());
    }

    if (sourceInput.length && sourceInput.val()) {
      params.append('source_id', sourceInput.val());
    }

    if (qInput.length && qInput.val().trim()) {
      params.append('q', qInput.val().trim());
    }

    // include date range if selected
    if (fp && fp.selectedDates && fp.selectedDates.length === 2) {
      const s = fp.selectedDates[0].toISOString().split('T')[0];
      const e = fp.selectedDates[1].toISOString().split('T')[0];
      params.append('start_date', s);
      params.append('end_date', e);
    }

    const query = params.toString();
    const url = '{{ route('leads.availables.export') }}' + (query ? '?' + query : '');
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
          loadAvailableLeads();
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
