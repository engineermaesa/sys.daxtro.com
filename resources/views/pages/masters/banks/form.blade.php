@extends('layouts.app')

@section('content')
<section class="min-h-screen sm:text-xs lg:text-sm">
  <div class="pt-4">
    <div class="flex items-center gap-3 text-[#115640]">
        <x-icon.bank/>
        <h1 class="font-semibold text-2xl">Banks</h1>
    </div>
    <div class="flex items-center mt-2 gap-3">
        <a href="javascript:history.back()" class="text-[#757575] hover:no-underline">All Banks</a>
        <i class="fas fa-chevron-right text-[#757575]" style="font-size: 12px;"></i>
        <a href="{{ route('masters.banks.form', $form_data->id) }}" class="text-[#083224] underline">
            {{ old('name', $form_data->name) ? 'Edit Banks' : 'Create Banks' }}
        </a>
    </div>
  </div>

  <div class="bg-white rounded-lg p-3 border border-[#D9D9D9] mt-4">
    <form method="POST" action="{{ route('masters.banks.save', $form_data->id) }}"
      id="form"
      back-url="{{ route('masters.banks.index') }}"
      require-confirmation="true">
      @csrf
      <div class="w-full grid grid-cols-1">
        <label class="text-[#1E1E1E]! mb-2! block!">Bank Name<i
                class="required">*</i></label>
        <input type="text" name="name"
          placeholder="Nama Bank"
          class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!"
          value="{{ old('name', $form_data->name) }}" required>
      </div>
      <div class="flex justify-end py-3">
        @include('partials.template.save-btn-form', ['backUrl' => 'back'])
      </div>
  </div>
</section>
@endsection
