@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-md-6 mb-3">
      <div class="card h-100">
        <div class="card-header"><strong>Download Template</strong></div>
        <div class="card-body d-flex flex-column align-items-start">
          <a href="{{ route('leads.import.template') }}" class="btn btn-success mb-3">
            <i class="bi bi-download"></i> Download Template
          </a>
          <button class="btn btn-link p-0" type="button" data-toggle="collapse" data-target="#importHelp"
            aria-expanded="false">
            Need help?
          </button>
          <div id="importHelp" class="collapse mt-2">
            <div class="alert alert-info small mb-0">
              Required fields are marked with *. Date format for <code>published_at</code> should be YYYY-MM-DD.
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 mb-3">
      <div class="card h-100">
        <div class="card-header"><strong>Upload Leads File</strong></div>
        <div class="card-body">
          <form id="uploadForm" action="{{ route('leads.import.preview') }}" method="POST"
            enctype="multipart/form-data">
            @csrf
            <div class="custom-file mb-3">
              <input type="file" class="custom-file-input" id="importFile" name="import_file" accept=".xlsx,.csv"
                required>
              <label class="custom-file-label" for="importFile">Choose Excel/CSV file...</label>
            </div>
            <button type="submit" class="btn btn-primary">Preview Import</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  @isset($rows)
  <div class="card mt-3">
    <form id="submitForm" method="POST" action="{{ route('leads.import.store') }}">
      @csrf
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Preview Results</strong>
        <button type="submit" class="btn btn-success" {{ $hasError ? 'disabled' : '' }}>Submit Verified Leads</button>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table id="previewTable" class="table table-bordered table-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>source_id*</th>
                <th>segment_id*</th>
                <th>region_id*</th>
                <th>lead_name</th>
                <th>lead_email</th>
                <th>lead_phone</th>
                <th>lead_needs</th>
                <th>nip_sales</th>
                <th>published_at</th>
                <th>status_stage</th>
                <th>Meeting Type</th>
                <th>Meeting URL</th>
                <th>Start Time Meeting</th>
                <th>End Time Meeting</th>
                <th>Meeting City</th>
                <th>Meeting Address</th>
                <th>Expense Type</th>
                <th>Expense Notes</th>
                <th>Expense Amount</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @php $lastGroup = null; $groupIndex = 0; @endphp
              @foreach($rows as $idx => $row)
              @php
                $currentGroup   = $row['group_key'] ?? $idx;
                $isFirstInGroup = $currentGroup !== $lastGroup;
                if ($isFirstInGroup) {
                  $groupIndex++;
                }
                $lastGroup = $currentGroup;
              @endphp
              <tr class="{{ $row['error'] ? 'table-danger' : '' }}" data-index="{{ $idx }}">
                <td data-order="{{ $groupIndex }}">{{ $isFirstInGroup ? $groupIndex : '' }}</td>
                <input type="hidden" name="rows[{{ $idx }}][group_key]" value="{{ $row['group_key'] ?? '' }}">
                @if($isFirstInGroup)
                  <td>
                    <select name="rows[{{ $idx }}][source_id]" class="form-control form-control-sm">
                      <option value="">--</option>
                      @foreach($sources as $s)
                      <option value="{{ $s->id }}" {{ $s->id == $row['source_id'] ? 'selected' : '' }}>{{ $s->name }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td>
                    <select name="rows[{{ $idx }}][segment_id]" class="form-control form-control-sm">
                      <option value="">--</option>
                      @foreach($segments as $seg)
                      <option value="{{ $seg->id }}" {{ $seg->id == $row['segment_id'] ? 'selected' : '' }}>{{ $seg->name }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td>
                    <select name="rows[{{ $idx }}][region_id]" class="form-control form-control-sm">
                      <option value="">All Region</option>
                      @foreach($regions as $r)
                      <option value="{{ $r->id }}" {{ $r->id == $row['region_id'] ? 'selected' : '' }}>{{ $r->name }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td><input type="text" name="rows[{{ $idx }}][lead_name]" value="{{ $row['lead_name'] }}" class="form-control form-control-sm"></td>
                  <td><input type="text" name="rows[{{ $idx }}][lead_email]" value="{{ $row['lead_email'] }}" class="form-control form-control-sm"></td>
                  <td><input type="text" name="rows[{{ $idx }}][lead_phone]" value="{{ $row['lead_phone'] }}" class="form-control form-control-sm"></td>
                  <td><input type="text" name="rows[{{ $idx }}][lead_needs]" value="{{ $row['lead_needs'] }}" class="form-control form-control-sm"></td>
                  <td>
                    <select name="rows[{{ $idx }}][nip_sales]" class="form-control form-control-sm">
                      <option value="">--</option>
                      @foreach($users as $u)
                      <option value="{{ $u->nip }}" {{ $u->nip == $row['nip_sales'] ? 'selected' : '' }}>{{ $u->nip }} - {{ $u->name }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td><input type="text" name="rows[{{ $idx }}][published_at]" value="{{ $row['published_at'] }}" class="form-control form-control-sm"></td>
                  <td>
                    @php($stage = $row['status_stage'] ?? '')
                    <select name="rows[{{ $idx }}][status_stage]" class="form-control form-control-sm">
                      <option value="" {{ $stage==='' ? 'selected' : '' }}>--</option>
                      <option value="cold" {{ $stage==='cold' ? 'selected' : '' }}>cold</option>
                      <option value="warm" {{ $stage==='warm' ? 'selected' : '' }}>warm</option>
                      <option value="hot" {{ $stage==='hot' ? 'selected' : '' }}>hot</option>
                      <option value="deal" {{ $stage==='deal' ? 'selected' : '' }}>deal</option>
                      <option value="available" {{ $stage==='available' ? 'selected' : '' }}>available</option>
                    </select>
                  </td>
                  <td>
                    @php($mtId = $row['meeting_type_id'] ?? null)
                    <input type="text" class="form-control form-control-sm" value="{{ isset($meetingTypes) ? optional($meetingTypes->firstWhere('id', $mtId))->name : $mtId }}" readonly>
                  </td>
                  <td>
                    <input type="text" class="form-control form-control-sm" value="{{ $row['meeting_url'] ?? '' }}" readonly>
                  </td>
                  <td>
                    <input type="text" class="form-control form-control-sm" value="{{ $row['meeting_start_at'] ?? '' }}" readonly>
                  </td>
                  <td>
                    <input type="text" class="form-control form-control-sm" value="{{ $row['meeting_end_at'] ?? '' }}" readonly>
                  </td>
                  <td>
                    @php($cityRaw = $row['meeting_city'] ?? '')
                    <input type="text" class="form-control form-control-sm" value="{{ ($cityRaw !== '' && isset($regions)) ? optional($regions->firstWhere('id', is_numeric($cityRaw) ? (int) $cityRaw : $cityRaw))->name ?? $cityRaw : $cityRaw }}" readonly>
                  </td>
                  <td>
                    <input type="text" class="form-control form-control-sm" value="{{ $row['meeting_address'] ?? '' }}" readonly>
                  </td>
                @else
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                @endif
                <td>
                  @php($etId = $row['expense_type_id'] ?? null)
                  <input type="text" class="form-control form-control-sm"
                    value="{{ isset($expenseTypes) ? optional($expenseTypes->firstWhere('id', $etId))->name : $etId }}"
                    readonly>
                </td>
                <td>
                  <input type="text" class="form-control form-control-sm" value="{{ $row['expense_notes'] ?? '' }}" readonly>
                </td>
                <td>
                  <input type="text" class="form-control form-control-sm" value="{{ $row['expense_amount'] ?? '' }}" readonly>
                </td>
                @if($isFirstInGroup)
                  <td>
                    @if($row['error'])
                    <span class="badge badge-danger">{{ $row['error'] }}</span>
                    @else
                    <span class="badge badge-success">OK</span>
                    @endif
                  </td>
                @else
                  <td></td>
                @endif
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i
                      class="bi bi-x"></i></button>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </form>
  </div>
  @endisset
</section>
@endsection

@section('scripts')
@if(isset($rows))
<script>
  $(function(){
  document.addEventListener('DOMContentLoaded', function () {
    bsCustomFileInput.init();

    $('#uploadForm').on('submit', function(){
      loading();
    });

    @if(session('success'))
      notif('{{ session('success') }}');
    @endif
  });

  const table = $('#previewTable').DataTable({
    paging: true,
    searching: true,
    info: false,
    scrollX: true,
    fixedHeader: true
  });

  $('#previewTable').on('click', '.remove-row', function(){
    table.row($(this).closest('tr')).remove().draw();
  });

  $('#submitForm').on('submit', function(e){
    e.preventDefault();
    Swal.fire({
      title: 'Submit leads?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes',
      cancelButtonText: 'Cancel'
    }).then(res => {
      if(res.isConfirmed){
        loading();
        this.submit();
      }
    });
  });
});
</script>
@endif
<script>
  $('#uploadForm').on('submit', function(){
  loading();
});
@if(session('success'))
  notif('{{ session('success') }}');
@endif
</script>
@endsection