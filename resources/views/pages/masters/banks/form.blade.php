@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-body pt-3">
          <form method="POST" action="{{ route('masters.banks.save', $form_data->id) }}"
                id="form"
                back-url="{{ route('masters.banks.index') }}"
                require-confirmation="true">
            @csrf

            <div class="mb-3">
              <label class="form-label">Bank Name <i class="required">*</i></label>
              <input type="text" name="name" class="form-control" value="{{ old('name', $form_data->name) }}" required>
            </div>

            @include('partials.common.save-btn-form', ['backUrl' => route('masters.banks.index')])
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

@endsection
