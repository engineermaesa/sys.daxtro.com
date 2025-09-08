@extends('layouts.app')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css" rel="stylesheet">
@endsection

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-body pt-3">
          <form method="POST" action="{{ route('masters.products.save', $form_data->id) }}"
                id="form"
                back-url="{{ route('masters.products.index') }}"
                require-confirmation="true">
            @csrf
            <div class="mb-3">
              <label class="form-label">Product Type <i class="required">*</i></label>
              <select name="product_type_id" class="form-select select2" required>
                <option value="">-- Select Type --</option>
                @foreach($types as $type)
                  <option value="{{ $type->id }}" {{ old('product_type_id', $selectedType) == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Categories</label>
              <select name="category_ids[]" class="form-select select2" multiple>
                @foreach($categories as $cat)
                  <option value="{{ $cat->id }}" {{ in_array($cat->id, old('category_ids', $selectedCategories ?? [])) ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Parts</label>
              <select name="part_ids[]" class="form-select select2" multiple>
                @foreach($parts as $part)
                  <option value="{{ $part->id }}" {{ in_array($part->id, old('part_ids', $selectedParts ?? [])) ? 'selected' : '' }}>{{ $part->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">SKU <i class="required">*</i></label>
              <input type="text" name="sku" class="form-control" value="{{ old('sku', $form_data->sku) }}" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Product Name <i class="required">*</i></label>
              <input type="text" name="name" class="form-control" value="{{ old('name', $form_data->name) }}" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control summernote">{{ old('description', $form_data->description) }}</textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">FOB Price (Rp.)</label>
              <input type="text" name="fob_price" class="form-control currency-input" value="{{ old('fob_price', number_format($form_data->fob_price, 0, ',', '.')) }}">
            </div>

            <div class="mb-3">
              <label class="form-label">BDI Price (Rp.)</label>
              <input type="text" name="bdi_price" class="form-control currency-input" value="{{ old('bdi_price', number_format($form_data->bdi_price, 0, ',', '.')) }}">
            </div>

            <div class="mb-3">
              <label class="form-label">Corporate Price (Rp.)</label>
              <input type="text" name="corporate_price" class="form-control currency-input" value="{{ old('corporate_price', number_format($form_data->corporate_price, 0, ',', '.')) }}">
            </div>

            <div class="mb-3">
              <label class="form-label">Government Price (Rp.)</label>
              <input type="text" name="government_price" class="form-control currency-input" value="{{ old('government_price', number_format($form_data->government_price, 0, ',', '.')) }}">
            </div>

            <div class="mb-3">
              <label class="form-label">Personal Price (Rp.)</label>
              <input type="text" name="personal_price" class="form-control currency-input" value="{{ old('personal_price', number_format($form_data->personal_price, 0, ',', '.')) }}">
            </div>

            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" role="switch" id="warranty_available" name="warranty_available" value="1" {{ old('warranty_available', $form_data->warranty_available) ? 'checked' : '' }}>
              <label class="form-check-label" for="warranty_available">Warranty Available</label>
            </div>

            <div class="mb-3 warranty-time-field {{ old('warranty_available', $form_data->warranty_available) ? '' : 'd-none' }}">
              <label class="form-label">Warranty Time (Month)</label>
              <input type="number" name="warranty_time_month" class="form-control" value="{{ old('warranty_time_month', $form_data->warranty_time_month) }}">
            </div>

            <div class="mb-3">
              <label class="form-label">VAT (%)</label>
              <input type="number" name="vat" class="form-control" value="{{ old('vat', $form_data->vat) }}">
            </div>

            @include('partials.common.save-btn-form', ['backUrl' => route('masters.products.index')])
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js"></script>
<script>
$(function () {
  $('#warranty_available').on('change', function () {
    $('.warranty-time-field').toggleClass('d-none', !$(this).is(':checked'));
  });

  function formatCurrency(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    if (value === '') {
      input.value = '';
      return;
    }
    input.value = parseInt(value, 10).toLocaleString('en-US'); // comma separator
  }


  $('.currency-input').each(function () {
    formatCurrency(this);
  });

  $(document).on('input', '.currency-input', function () {
    formatCurrency(this);
  });
});
</script>
@endsection
