@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-body pt-3">
          <form method="POST" action="{{ route('masters.branches.save', $form_data->id) }}"
                id="form"
                back-url="{{ route('masters.branches.index') }}"
                require-confirmation="true">
            @csrf

            <div class="mb-3">
              <label class="form-label">Company <i class="required">*</i></label>
              <select name="company_id" class="form-control" required>
                <option value="">Select Company</option>
                @foreach($companies as $company)
                  <option value="{{ $company->id }}" {{ old('company_id', $form_data->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Branch Name <i class="required">*</i></label>
              <input type="text" name="name" class="form-control" value="{{ old('name', $form_data->name) }}" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Code <i class="required">*</i></label>
              <input type="text" name="code" class="form-control" value="{{ old('code', $form_data->code) }}" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Address</label>
              <textarea name="address" class="form-control">{{ old('address', $form_data->address) }}</textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Target</label>
              <input type="number" name="target" class="form-control" value="{{ old('target', $form_data->target) }}" placeholder="Enter target amount" min="0" step="0.01">
              <small class="form-text text-muted">Enter target amount in Rupiah</small>
            </div>

            @include('partials.common.save-btn-form', ['backUrl' => route('masters.branches.index')])
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
