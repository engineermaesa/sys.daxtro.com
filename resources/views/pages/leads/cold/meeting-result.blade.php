@extends('layouts.app')

@section('content')

<section class="min-h-screen">
  <div class="pt-4">
    <div class="flex items-center gap-3">
        <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12ZM2 20C1.45 20 0.979167 19.8042 0.5875 19.4125C0.195833 19.0208 0 18.55 0 18V4C0 3.45 0.195833 2.97917 0.5875 2.5875C0.979167 2.19583 1.45 2 2 2H3V1C3 0.716667 3.09583 0.479167 3.2875 0.2875C3.47917 0.0958333 3.71667 0 4 0C4.28333 0 4.52083 0.0958333 4.7125 0.2875C4.90417 0.479167 5 0.716667 5 1V2H13V1C13 0.716667 13.0958 0.479167 13.2875 0.2875C13.4792 0.0958333 13.7167 0 14 0C14.2833 0 14.5208 0.0958333 14.7125 0.2875C14.9042 0.479167 15 0.716667 15 1V2H16C16.55 2 17.0208 2.19583 17.4125 2.5875C17.8042 2.97917 18 3.45 18 4V18C18 18.55 17.8042 19.0208 17.4125 19.4125C17.0208 19.8042 16.55 20 16 20H2Z"
                fill="#115640" />
        </svg>
        <h1 class="text-[#115640] font-semibold text-2xl">Leads</h1>
    </div>
    <div class="flex items-center mt-2 gap-3">
        <a href="javascript:history.back()" class="text-[#757575] hover:no-underline">My Leads</a>
        <i class="fas fa-chevron-right text-[#757575]" style="font-size: 12px;"></i>
        <a href="/" class="text-[#083224] underline">
          Meeting Result
        </a>
    </div>
    <form id="form"
      method="POST"
      action="{{ route('leads.my.cold.meeting.result.save', $data->id) }}"
      back-url="{{ route('leads.my') }}"
      enctype="multipart/form-data"
      require-confirmation="true">
      @csrf

      <div class="bg-white border border-[#D9D9D9] rounded mt-4">
        <h1 class="font-semibold text-[#1E1E1E] uppercase w-full p-3 border-b border-b-[#D9D9D9]">
            Meeting Plan
        </h1>

        <div class="px-3 py-1 text-[#1E1E1E]">
          <label for="result" class="font-semibold">Meeting Outcome <span class="text-[#EC221F]">*</span></label>
          <select name="result" id="result" class="w-full px-3 py-2 rounded-lg border border-[#D9D9D9]" required>
            <option value="">-- Select Outcome --</option>
            <option value="yes" @selected(old('result', $data->result) === 'yes')>Interested (Convert to Warm)</option>
            <option value="no" @selected(old('result', $data->result) === 'no')>Not Interested (Move to Trash Cold)</option>
            <option value="waiting" @selected(old('result', $data->result) === 'waiting')>Waiting (Consideration)</option>
          </select>
        </div>

        <div class="px-3 py-1 text-[#1E1E1E]">
          <label for="summary" class="font-semibold">Meeting Summary <span class="text-[#EC221F]">*</span></label>
          <textarea name="summary" id="summary" class="w-full px-3 py-2 rounded-lg border border-[#D9D9D9]" rows="4" placeholder="Type Here To Input Summary..." required>{{ old('summary', $data->summary) }}</textarea>
        </div>

        <div class="px-3 py-1 pb-3 text-[#1E1E1E]" id="attachmentField" style="display: none;">
          <label for="attachment_id" class="font-semibold">Supporting Attachment <span class="text-[#EC221F]">*</span></label>
          <div class="custom-file">
            <input type="file" class="custom-file-input cursor-pointer w-full border border-[#D9D9D9] focus:outline-none!" id="attachment_id" name="attachment_id" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
            <label class="custom-file-label" for="attachment_id">Choose file...</label>
          </div>
        </div>
      </div>
      <div class="flex justify-end gap-3 mt-5">
        <a href="{{ route('leads.my') }}" class="cursor-pointer px-3 py-2 bg-white border border-[#115640] rounded-lg text-[#1E1E1E] font-semibold">Back</a>
        <button type="submit" class="cursor-pointer px-3 py-2 bg-[#115640] border border-[#115640] rounded-lg text-white font-semibold">Submit Result</button>
      </div>
    </form>
  </div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const resultSelect = document.getElementById('result');
    const attachmentField = document.getElementById('attachmentField');
    const attachmentInput = document.getElementById('attachment_id');

    function toggleAttachment(val) {
      if (val === 'yes') {
        attachmentField.style.display = 'block';
        attachmentInput.setAttribute('required', 'required');
      } else {
        attachmentField.style.display = 'none';
        attachmentInput.removeAttribute('required');
        attachmentInput.value = '';
        attachmentInput.nextElementSibling.innerText = 'Choose file...';
      }
    }

    toggleAttachment(resultSelect.value);
    resultSelect.addEventListener('change', function () {
      toggleAttachment(this.value);
    });

    attachmentInput.addEventListener('change', function () {
      const fileName = this.files[0] ? this.files[0].name : 'Choose file...';
      this.nextElementSibling.innerText = fileName;
    });
  });
</script>
@endsection
