@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-body pt-3">
              <form method="POST"
                action="{{ $saveUrl ?? route('masters.provinces.save', $form_data->id) }}"
                id="form"
                back-url="{{ $backUrl ?? route('masters.provinces.index') }}"
                require-confirmation="true">
            @csrf

            <div class="mb-3">
              <label class="form-label">Regional <i class="required">*</i></label>
              <select name="regional_id" class="form-control select2" required>
                <option value="">Select Regional</option>
                @foreach($regionals as $reg)
                  <option value="{{ $reg->id }}" {{ old('regional_id', $form_data->regional_id) == $reg->id ? 'selected' : '' }}>{{ $reg->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Province Name <i class="required">*</i></label>
              <input type="text" name="name" class="form-control" value="{{ old('name', $form_data->name) }}" required>
            </div>

            @include('partials.common.save-btn-form', ['backUrl' => $backUrl ?? route('masters.provinces.index')])
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
