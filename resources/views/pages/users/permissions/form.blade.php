@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-body pt-3">
          <form method="POST" action="{{ route('users.permissions.save', ['id' => $form_data->id]) }}" id="form" back-url="{{ route('users.permissions.index') }}" require-confirmation="true">
            @csrf

            <div class="mb-3">
              <label for="name" class="form-label">Permission Name <i class="required">*</i></label>
              <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $form_data->name) }}" required>
            </div>

            <div class="mb-3">
              <label for="code" class="form-label">Code <i class="required">*</i></label>
              <input type="text" class="form-control" name="code" id="code" value="{{ old('code', $form_data->code) }}" required>
            </div>

            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <input type="text" class="form-control" name="description" id="description" value="{{ old('description', $form_data->description) }}">
            </div>

            @include('partials.common.save-btn-form', ['backUrl' => route('users.permissions.index')])
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
