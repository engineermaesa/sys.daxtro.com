@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-body pt-3">
          <form method="POST" action="{{ route('masters.companies.save', $form_data->id) }}"
                id="form"
                back-url="{{ route('masters.companies.index') }}"
                require-confirmation="true">
            @csrf

            <div class="mb-3">
              <label class="form-label">Company Name <i class="required">*</i></label>
              <input type="text" name="name" class="form-control" value="{{ old('name', $form_data->name) }}" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Address</label>
              <textarea name="address" class="form-control">{{ old('address', $form_data->address) }}</textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" value="{{ old('phone', $form_data->phone) }}">
            </div>

            @include('partials.common.save-btn-form', ['backUrl' => route('masters.companies.index')])
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
