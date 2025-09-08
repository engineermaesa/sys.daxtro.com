@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-body pt-3">
          <form method="POST" action="{{ route('users.roles.save', ['id' => $form_data->id]) }}" id="form" back-url="{{ route('users.roles.index') }}" require-confirmation="true">
            @csrf

            <div class="mb-3">
              <label for="name" class="form-label">Role Name <i class="required">*</i></label>
              <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $form_data->name) }}" required>
            </div>

            @include('partials.common.save-btn-form', ['backUrl' => route('users.roles.index')])
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
