@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-body pt-3">
          <form method="POST" action="{{ route('masters.accounts.save', $form_data->id) }}"
                id="form"
                back-url="{{ route('masters.accounts.index') }}"
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
              <label class="form-label">Bank <i class="required">*</i></label>
              <select name="bank_id" class="form-control" required>
                <option value="">Select Bank</option>
                @foreach($banks as $bank)
                  <option value="{{ $bank->id }}" {{ old('bank_id', $form_data->bank_id) == $bank->id ? 'selected' : '' }}>{{ $bank->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Account Number <i class="required">*</i></label>
              <input type="text" name="account_number" class="form-control" value="{{ old('account_number', $form_data->account_number) }}" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Holder Name <i class="required">*</i></label>
              <input type="text" name="holder_name" class="form-control" value="{{ old('holder_name', $form_data->holder_name) }}" required>
            </div>

            @include('partials.common.save-btn-form', ['backUrl' => route('masters.accounts.index')])
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
