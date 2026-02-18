@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-body pt-3">
          <form method="POST" action="{{ route('masters.parts.save', $form_data->id) }}" id="form"
            back-url="{{ route('masters.parts.index') }}" require-confirmation="true">
            @csrf

            <div class="mb-3">
              <label class="form-label">SKU <i class="required">*</i></label>
              <input type="text" name="sku" class="form-control" value="{{ old('sku', $form_data->sku) }}" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Part Name <i class="required">*</i></label>
              <input type="text" name="name" class="form-control" value="{{ old('name', $form_data->name) }}" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Price <i class="required">*</i></label>
              <input type="number" step="0.01" name="price" class="form-control"
                value="{{ old('price', $form_data->price) }}" required>
            </div>

            @include('partials.common.save-btn-form', ['backUrl' => route('masters.parts.index')])
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection