@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-body pt-3">
              <form method="POST" 
                action="{{ $saveUrl ?? route('masters.regions.save', $form_data->id) }}"
                id="form"
                back-url="{{ $backUrl ?? route('masters.regions.index') }}"
                require-confirmation="true">
            @csrf

            {{-- Regional --}}
            <div class="mb-3">
              <label class="form-label">Regional <i class="required">*</i></label>
              <select name="regional_id" id="regionalSelect" class="form-control select2" required>
                <option value="">Select Regional</option>
                @foreach($regionals as $reg)
                  <option value="{{ $reg->id }}"
                    {{ old('regional_id', $form_data->regional_id) == $reg->id ? 'selected' : '' }}>
                    {{ $reg->name }}
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Province (chained) --}}
            <div class="mb-3">
              <label class="form-label">Province <i class="required">*</i></label>
              <select name="province_id" id="provinceSelect" class="form-control select2" required>
                <option value="">Select Province</option>
                @foreach($provinces as $prov)
                  <option value="{{ $prov->id }}"
                    {{ old('province_id', $form_data->province_id) == $prov->id ? 'selected' : '' }}>
                    {{ $prov->name }}
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Branch --}}
            <div class="mb-3">
              <label class="form-label">Branch <i class="required">*</i></label>
              <select name="branch_id" class="form-control select2" required>
                <option value="">Select Branch</option>
                @foreach($branches as $branch)
                  <option value="{{ $branch->id }}"
                    {{ old('branch_id', $form_data->branch_id) == $branch->id ? 'selected' : '' }}>
                    {{ $branch->name }}
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Region Name & Code --}}
            <div class="mb-3">
              <label class="form-label">Region Name <i class="required">*</i></label>
              <input type="text"
                     name="name"
                     class="form-control"
                     value="{{ old('name', $form_data->name) }}"
                     required>
            </div>

            <div class="mb-3">
              <label class="form-label">Code <i class="required">*</i></label>
              <input type="text"
                     name="code"
                     class="form-control"
                     value="{{ old('code', $form_data->code) }}"
                     required>
            </div>

            @include('partials.common.save-btn-form', [
              'backUrl' => route('masters.regions.index')
            ])
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@section('scripts')
<script>
  $('#regionalSelect').on('change', function() {
    var regId    = $(this).val();
    var $provSel = $('#provinceSelect');
    $provSel.html('<option>Loading...</option>');

    if (!regId) {
      $provSel.html('<option value="">Select Province</option>');
      return;
    }

    $.get("{{ route('masters.regions.provinces') }}", { regional_id: regId })
      .done(function(data) {
        var options = '<option value="">Select Province</option>';
        $.each(data, function(_, prov) {
          options += '<option value="' + prov.id + '">' + prov.name + '</option>';
        });
        $provSel.html(options);
      })
      .fail(function() {
        $provSel.html('<option value="">— Error loading —</option>');
      });
  });
</script>
@endsection
