@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-body pt-3">
          <h5 class="card-title">Edit Access for Role: <strong>{{ $role->name }}</strong></h5>

          <form method="POST" action="{{ route('settings.permissions-settings.save', $role->id) }}" id="form" back-url="{{ route('settings.permissions-settings.index') }}" require-confirmation="true">
            @csrf

            <div class="mb-3">
              <label class="form-label">Permissions</label>
              <div class="row">
                @foreach($permissions as $perm)
                  @php $checked = $assigned->firstWhere('permission_id', $perm->id); @endphp
                  <div class="col-md-4">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $perm->id }}" id="perm_{{ $perm->id }}" {{ $checked ? 'checked' : '' }}>
                      <label class="form-check-label" for="perm_{{ $perm->id }}">{{ $perm->name }} <small class="text-muted">({{ $perm->code }})</small></label>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>

            @include('partials.common.save-btn-form', ['backUrl' => route('settings.permissions-settings.index')])
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
