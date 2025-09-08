@extends('layouts.app')

@section('content')
<section class="section">
  <div class="row">
    <div class="col-xl-6 offset-xl-3">
      <div class="card">
        <div class="card-header">
          <strong>Meeting Result</strong>
        </div>
        <div class="card-body pt-3">
          <form id="form"
            method="POST"
            action="{{ route('leads.my.cold.meeting.result.save', $data->id) }}"
            back-url="{{ route('leads.my') }}"
            enctype="multipart/form-data"
            require-confirmation="true">
            @csrf

            <div class="mb-3">
              <label for="result" class="form-label">Meeting Outcome <i class="required">*</i></label>
              <br>
              <select name="result" id="result" class="form-select w-100" required>
                <option value="">-- Select Outcome --</option>
                <option value="yes" @selected(old('result', $data->result) === 'yes')>Interested (Convert to Warm)</option>
                <option value="no" @selected(old('result', $data->result) === 'no')>Not Interested (Move to Trash Cold)</option>
                <option value="waiting" @selected(old('result', $data->result) === 'waiting')>Waiting (Consideration)</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="summary" class="form-label">Meeting Summary <i class="required">*</i></label>
              <textarea name="summary" id="summary" class="form-control" rows="4" required>{{ old('summary', $data->summary) }}</textarea>
            </div>

            <div class="form-group" id="attachmentField" style="display: none;">
              <label for="attachment_id" class="form-label">Supporting Attachment <i class="required">*</i></label>
              <div class="custom-file">
                <input type="file" class="custom-file-input" id="attachment_id" name="attachment_id" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                <label class="custom-file-label" for="attachment_id">Choose file...</label>
              </div>
            </div>

            <div class="d-flex justify-content-between">
              <a href="{{ route('leads.my') }}" class="btn btn-secondary">Back</a>
              <button type="submit" class="btn btn-primary">Submit Result</button>
            </div>
          </form>
        </div>
      </div>
    </div>
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
