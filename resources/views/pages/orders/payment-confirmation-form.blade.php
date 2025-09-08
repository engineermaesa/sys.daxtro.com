@extends('layouts.app')

@section('content')
<section class="section">
  <div class="card">
    <div class="card-header">
      <strong>Payment Confirmation</strong>
    </div>
    <div class="card-body">
      @php
        $confirmation   = $proforma->paymentConfirmation;
        $financeRequest = $confirmation?->financeRequest;
        $readonly = $confirmation && (! $financeRequest || $financeRequest->status !== 'rejected');
      @endphp

      {{-- Payment Confirmation Status --}}
      @if ($confirmation && $financeRequest && in_array($financeRequest->status, ['approved', 'rejected']))
      <div class="alert alert-{{ $financeRequest->status === 'approved' ? 'success' : 'danger' }}">
        <strong>{{ ucfirst($financeRequest->status) }} by Finance:</strong> {{ $financeRequest->notes }}
      </div>
      @elseif ($confirmation)
      <div class="alert alert-success">
        <strong>Payment Confirmed</strong><br>
        Paid At: <strong>{{ $confirmation->paid_at?->format('d M Y') }}</strong><br>
        Confirmed By: <strong>{{ $confirmation->confirmedBy->name ?? '-' }}</strong><br>
        Confirmed At: <strong>{{ $confirmation->confirmed_at ? $confirmation->confirmed_at->format('d M Y H:i') : '-' }}</strong>
      </div>
      @endif

      {{-- Quotation and Proforma Info --}}
      <table class="table table-sm mb-4">
        <tr><th>Quotation No</th><td>{{ $proforma->quotation->quotation_no ?? '-' }}</td></tr>
        <tr><th>Proforma No</th><td>{{ $proforma->proforma_no ?? '-' }}</td></tr>
        <tr><th>Term</th><td>{{ $term === 'bf' ? 'Booking Fee' : 'Term ' . $term }}</td></tr>
        <tr><th>Amount</th><td>Rp{{ number_format($proforma->amount, 0, ',', '.') }}</td></tr>
      </table>

      {{-- Form --}}
      <form method="POST"
            action="{{ route('payment-confirmation.terms.payment.confirm', [$leadId, $term]) }}"
            enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
          <label class="form-label">Payer Name</label>
          <input type="text"
                 name="payer_name"
                 value="{{ old('payer_name', $confirmation->payer_name ?? '') }}"
                 class="form-control @error('payer_name') is-invalid @enderror"
                 {{ $readonly ? 'readonly' : '' }}>
          @error('payer_name')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <label class="form-label">Payer Bank</label>
          <input type="text"
                name="payer_bank"
                value="{{ old('payer_bank', $confirmation->payer_bank ?? '') }}"
                class="form-control @error('payer_bank') is-invalid @enderror"
                {{ $readonly ? 'readonly' : '' }}>
          @error('payer_bank')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <label class="form-label">Payer Account Number</label>
          <input type="text"
                name="payer_account_number"
                value="{{ old('payer_account_number', $confirmation->payer_account_number ?? '') }}"
                class="form-control @error('payer_account_number') is-invalid @enderror"
                {{ $readonly ? 'readonly' : '' }}>
          @error('payer_account_number')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>


        <div class="mb-3">
          <label class="form-label">Paid At <i class="required">*</i></label>
          <input type="date"
                 onfocus="this.showPicker()"
                 name="paid_at"
                 value="{{ old('paid_at', $confirmation?->paid_at?->format('Y-m-d') ?? '') }}"
                 class="form-control @error('paid_at') is-invalid @enderror"
                 {{ $readonly ? 'readonly' : '' }}>
          @error('paid_at')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <label class="form-label">Amount <i class="required">*</i></label>
          <input type="text"
                 value="Rp{{ number_format($proforma->amount, 0, ',', '.') }}"
                 class="form-control"
                 readonly>
        </div>

        <div class="mb-3">
          <label class="form-label d-block">Proof of Payment <i class="required">*</i> <small class="text-muted">(PDF, JPG, PNG)</small></label>

          @if($confirmation && $confirmation->attachment)
            <div class="bg-light p-3 rounded border d-flex justify-content-between align-items-center mb-3">
              <div>
                <i class="bi bi-file-earmark-check-fill text-success mr-2"></i>
                <span class="text-muted">File uploaded.</span>
              </div>
              <a href="{{ route('attachments.download', $confirmation->attachment_id) }}"
                target="_blank"
                class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-download"></i> View File
              </a>
            </div>
          @endif

          @if (! $readonly)
            <div class="custom-file">
              <input type="file"
                     class="custom-file-input @error('attachment_id') is-invalid @enderror"
                     id="attachment_id"
                     name="attachment_id"
                     accept=".pdf,.jpg,.jpeg,.png">
              <label class="custom-file-label" for="attachment_id">Choose file...</label>
              @error('attachment_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
          @endif
        </div>

        @if(!$readonly)
        <div class="d-flex justify-content-end">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-send"></i> Submit Confirmation
          </button>
        </div>
        @endif
      </form>

      <div class="mt-4">
        <a href="{{ url()->previous() }}" class="btn btn-light">Back</a>
      </div>
    </div>
  </div>
</section>
@endsection

@section('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.querySelector('.custom-file-input');
    if (fileInput) {
      fileInput.addEventListener('change', function (e) {
        const fileName = e.target.files[0]?.name || 'Choose file...';
        e.target.nextElementSibling.innerText = fileName;
      });
    }
  });
</script>
@endsection
