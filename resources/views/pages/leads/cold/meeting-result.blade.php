@extends('layouts.app')

@section('content')

<section class="min-h-screen">
  <div class="pt-4">

    <div class="flex items-center gap-3">
      <svg width="18" height="20" viewBox="0 0 18 20" fill="none">
        <path
          d="M2 16.85C2.9 15.9667 3.94583 15.2708 5.1375 14.7625C6.32917 14.2542 7.61667 14 9 14C10.3833 14 11.6708 14.2542 12.8625 14.7625C14.0542 15.2708 15.1 15.9667 16 16.85V4H2V16.85ZM9 12C8.03333 12 7.20833 11.6583 6.525 10.975C5.84167 10.2917 5.5 9.46667 5.5 8.5C5.5 7.53333 5.84167 6.70833 6.525 6.025C7.20833 5.34167 8.03333 5 9 5C9.96667 5 10.7917 5.34167 11.475 6.025C12.1583 6.70833 12.5 7.53333 12.5 8.5C12.5 9.46667 12.1583 10.2917 11.475 10.975C10.7917 11.6583 9.96667 12 9 12Z"
          fill="#115640" />
      </svg>
      <h1 class="text-[#115640] font-semibold sm:text-lg lg:text-2xl">Leads</h1>
    </div>

    <form id="form" method="POST"
      action="{{ route('leads.my.cold.meeting.result.save', $data->id) }}"
      enctype="multipart/form-data">

      @csrf

      <div class="bg-white border border-[#D9D9D9] rounded mt-4">

        <h1 class="font-semibold text-[#1E1E1E] uppercase w-full p-2 lg:p-3 border-b">
          Meeting Plan
        </h1>

        {{-- Meeting Outcome --}}
        <div class="p-3">
          <label class="font-semibold">
            Meeting Outcome <span class="text-[#EC221F]">*</span>
          </label>

          <select name="result" id="result"
            class="w-full p-2 rounded-lg border border-[#D9D9D9]" required>

            <option value="">-- Select Outcome --</option>

            <option value="yes" @selected(old('result',$data->result)=='yes')>
              Interested (Convert to Warm)
            </option>

            <option value="no" @selected(old('result',$data->result)=='no')>
              Not Interested (Move to Trash Cold)
            </option>

            <option value="waiting" @selected(old('result',$data->result)=='waiting')>
              Waiting (Consideration)
            </option>

          </select>
        </div>

        {{-- ⭐ Interest Level --}}
        <div class="p-3" id="interestLevelField" style="display:none;">

          <style>
            .star-rating{
              display:flex;
              flex-direction:row-reverse;
              justify-content:flex-end;
              font-size:28px;
              gap:4px;
            }

            .star-rating input{
              display:none;
            }

            .star-rating label{
              cursor:pointer;
              color:#D1D5DB;
              transition:0.2s;
            }

            .star-rating input:checked ~ label{
              color:#FACC15;
            }

            .star-rating label:hover,
            .star-rating label:hover ~ label{
              color:#FACC15;
            }
          </style>

          <label class="font-semibold">
            Level of Interest <span class="text-[#EC221F]">*</span>
          </label>

          <div class="star-rating">

            @for ($i = 5; $i >= 1; $i--)
              <input type="radio"
                     name="interest_level"
                     id="star{{ $i }}"
                     value="{{ $i }}"
                     @checked((int) old('interest_level', optional($data->lead)->interest_level) === $i)
              >

              <label for="star{{ $i }}">★</label>
            @endfor

          </div>

          <p class="text-xs text-[#757575] mt-1">
            1 = paling rendah, 5 = paling tinggi.
          </p>

        </div>

        {{-- Summary --}}
        <div class="p-3">
          <label class="font-semibold">
            Meeting Summary <span class="text-[#EC221F]">*</span>
          </label>

          <textarea name="summary"
            class="w-full p-2 rounded-lg border border-[#D9D9D9]"
            rows="4"
            required>{{ old('summary',$data->summary) }}</textarea>
        </div>

        {{-- Attachment --}}
        <div class="p-3" id="attachmentField" style="display:none;">

          <label class="font-semibold">
            Supporting Attachment
          </label>

          <input type="file"
            name="attachment_id[]"
            id="attachment_id"
            multiple
            class="w-full border p-2 rounded-lg">

        </div>

      </div>

      <div class="flex justify-end gap-3 mt-5">

        <a href="{{ route('leads.my') }}"
          class="p-2 px-3 border border-[#115640] rounded-lg">
          Back
        </a>

        <button type="submit"
          class="p-2 px-3 bg-[#115640] text-white rounded-lg">
          Submit Result
        </button>

      </div>

    </form>
  </div>
</section>

<script>

document.addEventListener('DOMContentLoaded',function(){

  const result = document.getElementById('result')
  const interestField = document.getElementById('interestLevelField')
  const attachmentField = document.getElementById('attachmentField')

  function toggle(val){

    if(val){
      attachmentField.style.display='block'
    }else{
      attachmentField.style.display='none'
    }

    if(val === 'yes'){
      interestField.style.display='block'
    }else{
      interestField.style.display='none'
    }

  }

  toggle(result.value)

  result.addEventListener('change',function(){
    toggle(this.value)
  })

})

</script>

@endsection