@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-body pt-3">
          <form id="form" method="POST" action="{{ route('users.save', ['id' => $data->id ?? '']) }}" back-url="{{ route('users.index') }}">
            @csrf

            <div class="mb-3">
              <label for="role_id" class="form-label">Role <i class="required">*</i></label>
              <select name="role_id" id="role_id" class="form-select select2" required>
                <option value="">-- Select Role --</option>
                @php
                  $authIsSuperAdmin = auth()->user()?->role?->code === 'super_admin';
                  $filteredRoles = $authIsSuperAdmin ? $roles : $roles->reject(fn($r) => $r->code === 'super_admin');
                @endphp

                @foreach($filteredRoles as $role)
                  <option value="{{ $role->id }}" {{ old('role_id', $data->role_id ?? '') == $role->id ? 'selected' : '' }}>
                    {{ $role->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label for="company_id" class="form-label">Company <i class="required">*</i></label>
              <select name="company_id" id="company_id" class="form-select select2" required>
                <option value="">-- Select Company --</option>
                @foreach($companies as $company)
                  <option value="{{ $company->id }}" {{ old('company_id', $data->company_id ?? '') == $company->id ? 'selected' : '' }}>
                    {{ $company->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label for="branch_id" class="form-label">Branch <i class="required">*</i></label>
              <select name="branch_id" id="branch_id" class="form-select select2" required>
                <option value="">-- Select Branch --</option>                
                  @foreach($branches as $b)
                    <option value="{{ $b->id }}"
                      {{ old('branch_id', $data->branch_id ?? '') == $b->id ? 'selected' : '' }}>
                      {{ $b->name }}
                    </option>
                  @endforeach
              </select>
            </div>


            <div class="mb-3">
              <label for="name" class="form-label">Name <i class="required">*</i></label>
              <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $data->name ?? '') }}" required>
            </div>

            <div class="mb-3">
              <label for="email" class="form-label">Email <i class="required">*</i></label>
              <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $data->email ?? '') }}" required>
            </div>

            <div class="mb-3">
              <label for="nip" class="form-label">NIP <i class="required">*</i></label>
              <input type="text" name="nip" id="nip" class="form-control" value="{{ old('nip', $data->nip ?? '') }}" required>
            </div>

            <div class="mb-3">
              <label for="phone" class="form-label">Phone</label>
              <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $data->phone ?? '') }}">
            </div>

            <div class="mb-3">
              <label for="password" class="form-label">
                Password{!! empty($data->id) ? ' <i class="required">*</i>' : '' !!}
              </label>
              <div class="input-group">
                <input type="password" name="password" id="password" class="form-control" {{ empty($data->id) ? 'required' : '' }}>
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
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" {{ empty($data->id) ? 'required' : '' }}>
                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_confirmation">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

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
});
</script>
@endsection