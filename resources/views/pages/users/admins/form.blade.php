@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-body pt-3">
          @php
          $roleCode = auth()->user()?->role?->code ?? null;
          $isBranchManager = $roleCode === 'branch_manager';
          @endphp

          <form id="form" method="POST" action="{{ route('users.save', ['id' => $data->id ?? '']) }}"
            back-url="{{ route('users.index') }}">
            @csrf

            <div class="mb-3">
              <label for="role_id" class="form-label">Role <i class="required">*</i></label>
              <select name="role_id" id="role_id" class="form-select select2" {{ $isBranchManager ? 'disabled'
                : 'required' }}>
                <option value="">-- Select Role --</option>
                @php
                $authIsSuperAdmin = auth()->user()?->role?->code === 'super_admin';
                $filteredRoles = $authIsSuperAdmin ? $roles : $roles->reject(fn($r) => $r->code === 'super_admin');
                @endphp

                @foreach($filteredRoles as $role)
                <option value="{{ $role->id }}" {{ old('role_id', $data->role_id ?? '') == $role->id ? 'selected' : ''
                  }}>
                  {{ $role->name }}
                </option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label for="company_id" class="form-label">Company <i class="required">*</i></label>
              <select name="company_id" id="company_id" class="form-select select2" {{ $isBranchManager ? 'disabled'
                : 'required' }}>
                <option value="">-- Select Company --</option>
                @foreach($companies as $company)
                <option value="{{ $company->id }}" {{ old('company_id', $data->company_id ?? '') == $company->id ?
                  'selected' : '' }}>
                  {{ $company->name }}
                </option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label for="branch_id" class="form-label">Branch <i class="required">*</i></label>
              <select name="branch_id" id="branch_id" class="form-select select2" {{ $isBranchManager ? 'disabled'
                : 'required' }}>
                <option value="">-- Select Branch --</option>
                @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ old('branch_id', $data->branch_id ?? '') == $b->id ? 'selected' : '' }}>
                  {{ $b->name }}
                </option>
                @endforeach
              </select>
            </div>


            <div class="mb-3">
              <label for="name" class="form-label">Name <i class="required">*</i></label>
              <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $data->name ?? '') }}"
                {{ $isBranchManager ? 'readonly' : 'required' }}>
            </div>

            <div class="mb-3">
              <label for="email" class="form-label">Email <i class="required">*</i></label>
              <input type="email" name="email" id="email" class="form-control"
                value="{{ old('email', $data->email ?? '') }}" {{ $isBranchManager ? 'readonly' : 'required' }}>
            </div>

            <div class="mb-3">
              <label for="nip" class="form-label">NIP <i class="required">*</i></label>
              <input type="text" name="nip" id="nip" class="form-control" value="{{ old('nip', $data->nip ?? '') }}" {{
                $isBranchManager ? 'readonly' : 'required' }}>
            </div>

            <div class="mb-3">
              <label for="grade" class="form-label">Grade <i class="required">*</i></label>
              <select
                name="grade"
                id="grade"
                class="form-select select2"
                {{ $isBranchManager ? 'disabled' : 'required' }}
              >
                <option value="">-- Select Grade --</option>
                @for ($g = 1; $g <= 8; $g++)
                  <option value="{{ $g }}" {{ (int) old('grade', $data->grade ?? '') === $g ? 'selected' : '' }}>
                    {{ $g }}
                  </option>
                @endfor
              </select>
            </div>

            <div class="mb-3">
              <label for="phone" class="form-label">Phone</label>
              <input type="text" name="phone" id="phone" class="form-control"
                value="{{ old('phone', $data->phone ?? '') }}" {{ $isBranchManager ? 'readonly' : '' }}>
            </div>

            @php
            $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
            ];
            $oldMonthlyTargets       = old('monthly_targets', $data->monthly_targets ?? []);
            $oldMonthlyLeadsTargets  = old('monthly_leads_targets', $data->monthly_leads_targets ?? []);
            $oldMonthlyVisitTargets  = old('monthly_visit_targets', $data->monthly_visit_targets ?? []);
            @endphp

            <div class="mb-3">
              <label class="form-label">Target:</label>

              {{-- total tahunan yang akan dikirim ke backend (field target lama) --}}
              <input type="hidden" name="target" id="target" value="{{ old('target', $data->target_total ?? 0) }}">
              <input type="hidden" name="target_leads" id="target_leads" value="{{ old('target_leads', $data->target_leads_total ?? 0) }}">
              <input type="hidden" name="target_visit" id="target_visit" value="{{ old('target_visit', $data->target_visit_total ?? 0) }}">

              <div class="table-responsive">
                <table class="table table-bordered align-middle mb-2">
                  <thead class="table-light">
                    <tr>
                      <th style="width: 25%">Bulan</th>
                      <th style="width: 15%">Percentage (%)</th>
                      <th style="width: 20%">Amount (Rp)</th>
                      <th style="width: 20%">Target Leads</th>
                      <th style="width: 20%">Target Visit</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($months as $monthIndex => $monthName)
                    @php
                    $rowOld        = $oldMonthlyTargets[$monthIndex] ?? [];
                    $rowOldLeads   = $oldMonthlyLeadsTargets[$monthIndex] ?? [];
                    $rowOldVisit   = $oldMonthlyVisitTargets[$monthIndex] ?? [];

                    $percentage = $rowOld['percentage'] ?? null;
                    $amount     = $rowOld['amount'] ?? null;
                    $leads      = $rowOldLeads['leads'] ?? null;
                    $visits     = $rowOldVisit['visits'] ?? null;
                    @endphp
                    <tr>
                      <td>{{ $monthName }}</td>
                      <td>
                        <input type="number" name="monthly_targets[{{ $monthIndex }}][percentage]" class="form-control"
                          value="{{ $percentage }}" min="0" max="100" step="0.01" placeholder="0">
                      </td>
                      <td>
                        <input type="number" name="monthly_targets[{{ $monthIndex }}][amount]"
                          class="form-control monthly-amount" value="{{ $amount }}" min="0" step="0.01" placeholder="0">
                      </td>
                      <td>
                        <input type="number" name="monthly_leads_targets[{{ $monthIndex }}][leads]"
                          class="form-control monthly-leads" value="{{ $leads }}" min="0" step="1" placeholder="0">
                      </td>
                      <td>
                        <input type="number" name="monthly_visit_targets[{{ $monthIndex }}][visits]"
                          class="form-control monthly-visits" value="{{ $visits }}" min="0" step="1" placeholder="0">
                      </td>
                    </tr>
                    @endforeach
                  </tbody>
                  <tfoot>
                    <tr>
                      <th colspan="2" class="text-end">Total</th>
                      <th>
                        Rp <span id="target_total_display">0</span>
                      </th>
                      <th>
                        <span id="target_leads_total_display">0</span> Leads
                      </th>
                      <th>
                        <span id="target_visit_total_display">0</span> Visit
                      </th>
                    </tr>
                  </tfoot>
                </table>
              </div>

              <small class="form-text text-muted">
                Isi target per bulan. Total Amount akan otomatis dijumlahkan dan disimpan sebagai target tahunan.
              </small>
            </div>

            @unless($isBranchManager)
            <div class="mb-3">
              <label for="password" class="form-label">
                Password{!! empty($data->id) ? ' <i class="required">*</i>' : '' !!}
              </label>
              <div class="input-group">
                <input type="password" name="password" id="password" class="form-control" {{ empty($data->id) ?
                'required' : '' }}>
                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <div class="mb-3">
              <label for="password_confirmation" class="form-label">
                Confirm Password{!! empty($data->id) ? ' <i class="required">*</i>' : '' !!}
              </label>
              <div class="input-group">
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" {{
                  empty($data->id) ? 'required' : '' }}>
                <button class="btn btn-outline-secondary toggle-password" type="button"
                  data-target="password_confirmation">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
            @endunless

            @include('partials.common.save-btn-form', ['backUrl' => route('users.index')])
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
@section('scripts')
<script>
  $(function () {  
  const $branch = $('#branch_id');
  const role = @json(auth()->user()->role?->code ?? '');
  const userBranchId = @json(auth()->user()->branch_id ?? null);

  $(document).on('click', '.toggle-password', function () {
    const targetId = $(this).data('target');
    const input = $('#' + targetId);
    const icon = $(this).find('i');

    const isPassword = input.attr('type') === 'password';
    input.attr('type', isPassword ? 'text' : 'password');
    icon.toggleClass('bi-eye').toggleClass('bi-eye-slash');
  });

  if (role === 'branch_manager') {
    $branch.closest('.mb-3').hide();
  }

  const initialBranchId = "{{ old('branch_id', $data->branch_id ?? '') }}";

  function recalcTargetTotals() {
    let totalAmount = 0;
    let totalLeads  = 0;
    let totalVisits = 0;

    $('.monthly-amount').each(function () {
      const val = parseFloat($(this).val());
      if (!isNaN(val)) {
        totalAmount += val;
      }
    });

    $('.monthly-leads').each(function () {
      const val = parseFloat($(this).val());
      if (!isNaN(val)) {
        totalLeads += val;
      }
    });

    $('.monthly-visits').each(function () {
      const val = parseFloat($(this).val());
      if (!isNaN(val)) {
        totalVisits += val;
      }
    });

    $('#target_total_display').text(
      totalAmount.toLocaleString('id-ID', { maximumFractionDigits: 2 })
    );
    $('#target_leads_total_display').text(
      totalLeads.toLocaleString('id-ID', { maximumFractionDigits: 0 })
    );
    $('#target_visit_total_display').text(
      totalVisits.toLocaleString('id-ID', { maximumFractionDigits: 0 })
    );

    $('#target').val(totalAmount);
    $('#target_leads').val(totalLeads);
    $('#target_visit').val(totalVisits);
  }

  $(document).on('input change', '.monthly-amount, .monthly-leads, .monthly-visits', recalcTargetTotals);

  // Hitung total di awal saat form dibuka
  recalcTargetTotals();
});
</script>
@endsection