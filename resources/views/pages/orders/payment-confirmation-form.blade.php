@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
  <div class="pt-4">
    <div class="flex items-center gap-3">
      @if (in_array(auth()->user()->role?->code, ['sales', 'branch_manager', 'sales_director', 'super_admin']))
                    <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12ZM2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V4C0 3.45 0.195833 2.97917 0.5875 2.5875C0.979167 2.19583 1.45 2 2 2H3V1C3 0.716667 3.09583 0.479167 3.2875 0.2875C3.47917 0.0958333 3.71667 0 4 0C4.28333 0 4.52083 0.0958333 4.7125 0.2875C4.90417 0.479167 5 0.716667 5 1V2H13V1C13 0.716667 13.0958 0.479167 13.2875 0.2875C13.4792 0.0958333 13.7167 0 14 0C14.2833 0 14.5208 0.0958333 14.7125 0.2875C14.9042 0.479167 15 0.716667 15 1V2H16C16.55 2 17.0208 2.19583 17.4125 2.5875C17.8042 2.97917 18 3.45 18 4V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2Z"
                            fill="#115640" />
                    </svg>
                    <h1 class="text-[#115640] font-semibold text-lg lg:text-2xl">Leads</h1>
      @else
          <svg width="20" height="19" viewBox="0 0 20 19" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M14.35 16.175L17.875 12.625C18.075 12.425 18.3125 12.325 18.5875 12.325C18.8625 12.325 19.1 12.425 19.3 12.625C19.5 12.825 19.6 13.0625 19.6 13.3375C19.6 13.6125 19.5 13.85 19.3 14.05L15.05 18.3C14.85 18.5 14.6125 18.6 14.3375 18.6C14.0625 18.6 13.825 18.5 13.625 18.3L11.5 16.175C11.3167 15.975 11.225 15.7375 11.225 15.4625C11.225 15.1875 11.325 14.95 11.525 14.75C11.725 14.55 11.9583 14.45 12.225 14.45C12.4917 14.45 12.725 14.55 12.925 14.75L14.35 16.175ZM5.7125 9.7125C5.90417 9.52083 6 9.28333 6 9C6 8.71667 5.90417 8.47917 5.7125 8.2875C5.52083 8.09583 5.28333 8 5 8C4.71667 8 4.47917 8.09583 4.2875 8.2875C4.09583 8.47917 4 8.71667 4 9C4 9.28333 4.09583 9.52083 4.2875 9.7125C4.47917 9.90417 4.71667 10 5 10C5.28333 10 5.52083 9.90417 5.7125 9.7125ZM5.7125 5.7125C5.90417 5.52083 6 5.28333 6 5C6 4.71667 5.90417 4.47917 5.7125 4.2875C5.52083 4.09583 5.28333 4 5 4C4.71667 4 4.47917 4.09583 4.2875 4.2875C4.09583 4.47917 4 4.71667 4 5C4 5.28333 4.09583 5.52083 4.2875 5.7125C4.47917 5.90417 4.71667 6 5 6C5.28333 6 5.52083 5.90417 5.7125 5.7125ZM13 10C13.2833 10 13.5208 9.90417 13.7125 9.7125C13.9042 9.52083 14 9.28333 14 9C14 8.71667 13.9042 8.47917 13.7125 8.2875C13.5208 8.09583 13.2833 8 13 8H9C8.71667 8 8.47917 8.09583 8.2875 8.2875C8.09583 8.47917 8 8.71667 8 9C8 9.28333 8.09583 9.52083 8.2875 9.7125C8.47917 9.90417 8.71667 10 9 10H13ZM13 6C13.2833 6 13.5208 5.90417 13.7125 5.7125C13.9042 5.52083 14 5.28333 14 5C14 4.71667 13.9042 4.47917 13.7125 4.2875C13.5208 4.09583 13.2833 4 13 4H9C8.71667 4 8.47917 4.09583 8.2875 4.2875C8.09583 4.47917 8 4.71667 8 5C8 5.28333 8.09583 5.52083 8.2875 5.7125C8.47917 5.90417 8.71667 6 9 6H13ZM2 18C1.45 18 0.979167 17.8042 0.5875 17.4125C0.195833 17.0208 0 16.55 0 16V2C0 1.45 0.195833 0.979167 0.5875 0.5875C0.979167 0.195833 1.45 0 2 0H16C16.55 0 17.0208 0.195833 17.4125 0.5875C17.8042 0.979167 18 1.45 18 2V8.875C18 9.14167 17.9458 9.39583 17.8375 9.6375C17.7292 9.87917 17.5833 10.0917 17.4 10.275L14.35 13.35L13.625 12.625C13.2417 12.2417 12.7708 12.05 12.2125 12.05C11.6542 12.05 11.1833 12.2417 10.8 12.625L9.4 14.05C9.2 14.25 9.05 14.4708 8.95 14.7125C8.85 14.9542 8.8 15.2 8.8 15.45C8.8 15.6833 8.83333 15.8958 8.9 16.0875C8.96667 16.2792 9.06667 16.4667 9.2 16.65C9.4 16.9333 9.4375 17.2292 9.3125 17.5375C9.1875 17.8458 8.96667 18 8.65 18H2Z" fill="#115640"/>
          </svg>
          <h1 class="text-[#115640] font-semibold lg:text-2xl text-lg">Quotations</h1>
      @endif
    </div>
    <div class="flex items-center mt-2 gap-3">
      @if (in_array(auth()->user()->role?->code, ['sales', 'branch_manager', 'sales_director', 'super_admin']))
        <a href="{{ route('leads.my') }}" class="text-[#757575] hover:no-underline">My Leads</a>
        <i class="fas fa-chevron-right text-[#757575]" style="font-size: 12px;"></i>
      @else
      <a href="{{ route('quotations.index') }}" class="text-[#757575] hover:no-underline">Quotation Approvals</a>
      <i class="fas fa-chevron-right text-[#757575]" style="font-size: 12px;"></i>
      @endif
      <a href="javascript:history.back()" class="text-[#757575] hover:no-underline">View Quotation</a>
      <i class="fas fa-chevron-right text-[#757575]" style="font-size: 12px;"></i>
      <a href="{{ route('payment-confirmation.terms.payment.confirm.form', [$leadId, $term ?? 'bf']) }}" class="text-[#083224] underline">
          Payment Confirmation
      </a>
    </div>
  </div>
  <div class="mt-4">
    @php
      $confirmation   = $proforma->paymentConfirmation;
      $financeRequest = $confirmation?->financeRequest;
      $readonly = $confirmation && (! $financeRequest || $financeRequest->status !== 'rejected');
    @endphp

    {{-- Payment Confirmation Status --}}
    @if ($confirmation)
      <div class="bg-white border border-[#D9D9D9] rounded-lg mb-4">
        <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] bg-[#115640] text-white rounded-tl-lg rounded-tr-lg">
          Payment Confirmation Status
        </h1>
        <div class="p-3">
          @if ($financeRequest && in_array($financeRequest->status, ['approved', 'rejected']))
            @php
              $isApproved = $financeRequest->status === 'approved';
              $statusClass = $isApproved
                ? 'bg-[#E7F5EF] border-[#115640] text-[#115640]'
                : 'bg-[#FDD3D0] border-[#900B09] text-[#900B09]';
            @endphp
            <div class="w-full flex items-center p-3 border rounded-lg gap-5 {{ $statusClass }}">
              <x-icon.info/>
              <div>
                <p>
                  <strong>{{ ucfirst($financeRequest->status) }} by Finance:</strong>
                  {{ $financeRequest->notes ?? '-' }}
                </p>
              </div>
            </div>
          @else
            <div class="w-full flex items-center p-3 bg-[#E7F5EF] border border-[#115640] rounded-lg text-[#115640] gap-5">
              <x-icon.info/>
              <div>
                <strong>Payment Confirmed</strong>
                <p>Paid At: <strong>{{ $confirmation->paid_at?->format('d M Y') }}</strong></p>
                <p>Confirmed By: <strong>{{ $confirmation->confirmedBy->name ?? '-' }}</strong></p>
                <p>Confirmed At: <strong>{{ $confirmation->confirmed_at ? $confirmation->confirmed_at->format('d M Y H:i') : '-' }}</strong></p>
              </div>
            </div>
          @endif
        </div>
      </div>
    @endif

    {{-- Quotation and Proforma Info --}}
    <div class="bg-white border border-[#D9D9D9] rounded-lg mb-4">
      <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] bg-[#115640] text-white rounded-tl-lg rounded-tr-lg">
        Quotation and Proforma Info
      </h1>
      <div class="p-3">
        <div class="border border-[#D9D9D9] rounded-lg">
          <table class="w-full text-[#1E1E1E]">
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Quotation No</th>
              <td class="p-3">{{ $proforma->quotation->quotation_no ?? '-' }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Proforma No</th>
              <td class="p-3">{{ $proforma->proforma_no ?? '-' }}</td>
            </tr>
            <tr class="border-b border-b-[#D9D9D9]">
              <th class="p-3">Term</th>
              <td class="p-3">{{ $term === 'bf' ? 'Booking Fee' : 'Term ' . $term }}</td>
            </tr>
            <tr>
              <th class="p-3">Amount</th>
              <td class="p-3">Rp{{ number_format($proforma->amount, 0, ',', '.') }}</td>
            </tr>
          </table>
        </div>
      </div>
    </div>

    {{-- Form --}}
    <div class="bg-white border border-[#D9D9D9] rounded-lg mb-4">
      <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] bg-[#115640] text-white rounded-tl-lg rounded-tr-lg">
        Payment Confirmation Form
      </h1>
      <div class="p-3">
        <form method="POST"
              action="{{ route('payment-confirmation.terms.payment.confirm', [$leadId, $term]) }}"
              enctype="multipart/form-data">
          @csrf

          <div class="mb-3">
            <label class="text-[#1E1E1E] mb-1 block">Payer Name <i class="required">*</i></label>
            <input type="text"
                  name="payer_name"
                  value="{{ old('payer_name', $confirmation->payer_name ?? '') }}"
                  class="form-control w-full block @error('payer_name') is-invalid @enderror"
                  {{ $readonly ? 'readonly' : 'required' }}>
            @error('payer_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label class="text-[#1E1E1E] mb-1 block">Payer Bank <i class="required">*</i></label>
            <input type="text"
                  name="payer_bank"
                  value="{{ old('payer_bank', $confirmation->payer_bank ?? '') }}"
                  class="form-control w-full block @error('payer_bank') is-invalid @enderror"
                  {{ $readonly ? 'readonly' : 'required' }}>
            @error('payer_bank')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label class="text-[#1E1E1E] mb-1 block">Payer Account Number <i class="required">*</i></label>
            <input type="text"
                  name="payer_account_number"
                  value="{{ old('payer_account_number', $confirmation->payer_account_number ?? '') }}"
                  class="form-control w-full block @error('payer_account_number') is-invalid @enderror"
                  {{ $readonly ? 'readonly' : 'required' }}>
            @error('payer_account_number')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label class="text-[#1E1E1E] mb-1 block">Paid At <i class="required">*</i></label>
            <input type="date"
                  onfocus="this.showPicker()"
                  name="paid_at"
                  value="{{ old('paid_at', $confirmation?->paid_at?->format('Y-m-d') ?? '') }}"
                  class="form-control w-full block @error('paid_at') is-invalid @enderror"
                  {{ $readonly ? 'readonly' : 'required' }}>
            @error('paid_at')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label class="text-[#1E1E1E] mb-1 block">Amount <i class="required">*</i></label>
            <input type="text"
                  value="Rp{{ number_format($proforma->amount, 0, ',', '.') }}"
                  class="form-control w-full block"
                  readonly>
          </div>

          <div class="mb-3">
            <label class="text-[#1E1E1E] mb-1 block">
              Proof of Payment <i class="required">*</i> <small class="text-muted">(PDF, JPG, PNG)</small>
            </label>

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
              <input type="file"
                    class="form-control w-full block @error('attachment_id') is-invalid @enderror"
                    id="attachment_id"
                    name="attachment_id"
                    accept=".pdf,.jpg,.jpeg,.png"
                    required>
              @error('attachment_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
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
      </div>
    </div>
  </div>
</section>

@endsection
