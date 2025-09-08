@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-12">
      <div class="card">
        <div class="card-body pt-3">
          <form method="POST" action="{{ route('orders.progress.save', $order->id) }}"
                id="form"
                back-url="{{ route('orders.show', $order->id) }}"
                require-confirmation="true">
            @csrf
            <div class="mb-3">
              <label class="form-label">Progress Step <i class="required">*</i></label>
              <br>
              @php
                $steps = [
                    1 => 'Order Publish',
                    2 => 'On Production',
                    3 => 'Running Test',
                    4 => 'Delivery to Indonesia',
                    5 => 'Legal Confirmation',
                    6 => 'Delivery to Customer Location',
                    7 => 'Installation',
                    8 => 'BAST',
                ];
                @endphp

                <select name="progress_step" class="form-select w-100" required>
                @foreach($steps as $key => $label)
                    <option value="{{ $key }}">{{ $key }} - {{ $label }}</option>
                @endforeach
                </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Logged At <i class="required">*</i></label>
              <input type="date" name="logged_at" class="form-control" value="{{ date('Y-m-d') }}" required onfocus="this.showPicker()">
            </div>
            <div class="mb-3">
              <label class="form-label">Note</label>
              <textarea name="note" class="form-control" rows="3"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label d-block">Attachment <small class="text-muted">(PDF, JPG, PNG)</small></label>
              <div class="custom-file">
                <input type="file" class="custom-file-input" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png">
                <label class="custom-file-label" for="attachment">Choose file...</label>
              </div>
            </div>
            @include('partials.common.save-btn-form', ['backUrl' => route('orders.show', $order->id)])
          </form>
        </div>
      </div>
    </div>
  </div>
  </section>
@endsection

@section('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('attachment');
    if (fileInput) {
      fileInput.addEventListener('change', function (e) {
        const name = e.target.files[0]?.name || 'Choose file...';
        e.target.nextElementSibling.innerText = name;
      });
    }
  });
</script>
@endsection