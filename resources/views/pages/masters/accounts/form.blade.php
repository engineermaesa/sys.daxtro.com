@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
  <div class="pt-4">
    <div class="flex items-center gap-3 text-[#115640]">
        <x-icon.account/>
        <h1 class="font-semibold text-2xl">Accounts</h1>
    </div>
    <div class="flex items-center mt-2 gap-3">
        <a href="javascript:history.back()" class="text-[#757575] hover:no-underline">All Accounts</a>
        <i class="fas fa-chevron-right text-[#757575]" style="font-size: 12px;"></i>
        <a href="{{ route('masters.accounts.form', $form_data->id) }}" class="text-[#083224] underline">
            {{ $form_data->id ? 'Edit Accounts' : 'Create Accounts' }}
        </a>
    </div>
  </div>

  <div class="bg-white rounded-lg p-3 border border-[#D9D9D9] mt-4">
    <form method="POST" action="{{ route('masters.accounts.save', $form_data->id) }}"
      id="form"
      back-url="{{ route('masters.accounts.index') }}"
      require-confirmation="true">
      @csrf

      <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="w-full grid grid-cols-1">
          <label class="text-[#1E1E1E]! mb-2! block!">Company <i class="required">*</i></label>
          <select id="accountCompanySelect"
            name="company_id"
            class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none! bg-white!"
            required>
            <option value="">Select Company</option>
            @foreach($companies as $company)
              <option value="{{ $company->id }}" {{ old('company_id', $form_data->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="w-full grid grid-cols-1">
          <label class="text-[#1E1E1E]! mb-2! block!">Bank <i class="required">*</i></label>
          <select id="accountBankSelect"
            name="bank_id"
            class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none! bg-white!"
            required>
            <option value="">Select Bank</option>
            @foreach($banks as $bank)
              <option value="{{ $bank->id }}" {{ old('bank_id', $form_data->bank_id) == $bank->id ? 'selected' : '' }}>{{ $bank->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="w-full grid grid-cols-1">
          <label class="text-[#1E1E1E]! mb-2! block!">Account Number <i class="required">*</i></label>
          <input id="accountNumberInput"
            type="text"
            name="account_number"
            placeholder="Account Number"
            class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
            value="{{ old('account_number', $form_data->account_number) }}"
            required>
        </div>

        <div class="w-full grid grid-cols-1">
          <label class="text-[#1E1E1E]! mb-2! block!">Holder Name <i class="required">*</i></label>
          <input id="accountHolderNameInput"
            type="text"
            name="holder_name"
            placeholder="Holder Name"
            class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
            value="{{ old('holder_name', $form_data->holder_name) }}"
            required>
        </div>
      </div>

      <div class="flex justify-end py-3">
        @include('partials.template.save-btn-form', ['backUrl' => 'back'])
      </div>
    </form>
  </div>
</section>
@endsection

@section('scripts')
<script>
  const accountFormUrl = '{{ route('masters.accounts.form', $form_data->id) }}';
  const accountOldValues = {
    company_id: @json(old('company_id')),
    bank_id: @json(old('bank_id')),
    account_number: @json(old('account_number')),
    holder_name: @json(old('holder_name')),
  };

  function hasOldAccountValue(value) {
    return value !== null && value !== undefined && value !== '';
  }

  function appendAccountOptions(select, rows, selectedValue, placeholder) {
    select.empty();
    select.append('<option value="">' + placeholder + '</option>');

    (rows || []).forEach(function (row) {
      const value = String(row.id || '');
      const selected = String(selectedValue || '') === value ? ' selected' : '';
      select.append('<option value="' + value + '"' + selected + '>' + $('<div>').text(row.name || '-').html() + '</option>');
    });
  }

  function hydrateAccountFormFromApi(payload) {
    const data = payload?.data || {};
    const formData = data.form_data || {};
    const companyId = hasOldAccountValue(accountOldValues.company_id) ? accountOldValues.company_id : formData.company_id;
    const bankId = hasOldAccountValue(accountOldValues.bank_id) ? accountOldValues.bank_id : formData.bank_id;
    const accountNumber = hasOldAccountValue(accountOldValues.account_number) ? accountOldValues.account_number : formData.account_number;
    const holderName = hasOldAccountValue(accountOldValues.holder_name) ? accountOldValues.holder_name : formData.holder_name;

    appendAccountOptions($('#accountCompanySelect'), data.companies || [], companyId, 'Select Company');
    appendAccountOptions($('#accountBankSelect'), data.banks || [], bankId, 'Select Bank');
    $('#accountNumberInput').val(accountNumber || '');
    $('#accountHolderNameInput').val(holderName || '');
  }

  $(document).ready(function () {
    $.ajax({
      url: accountFormUrl,
      type: 'GET',
      headers: { 'Accept': 'application/json' },
      success: hydrateAccountFormFromApi
    });
  });
</script>
@endsection
