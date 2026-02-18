@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-body pt-3">
              <form method="POST" action="{{ $saveUrl ?? route('masters.expense-types.save', $form_data->id) }}"
                id="form"
                back-url="{{ $backUrl ?? route('masters.expense-types.index') }}"
                require-confirmation="true">
            @csrf

            <div class="mb-3">
              <label class="form-label">Expense Type Name <i class="required">*</i></label>
              <input type="text" name="name" class="form-control" value="{{ old('name', $form_data->name) }}" required>
            </div>

            @include('partials.common.save-btn-form', ['backUrl' => route('masters.expense-types.index')])
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
