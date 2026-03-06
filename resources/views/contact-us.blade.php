<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us | DAXTRO</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/favicon.png') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/select2.min.css') }}?ver=1.0.3">
  <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/sweetalert2.min.css') }}?ver=1.0.3">
  <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/main.css') }}?ver=1.0.3">
  @vite('resources/css/app.css')
</head>
<body>
  
  <div class="min-h-screen! text-xs lg:text-sm! bg-[#E8EFEC] px-40 pt-20 flex items-start justify-center">
      <div id="lead-entries">
        @auth
            @if(auth()->user()->role?->code === 'branch_manager')
                <h1 class="text-4xl text-[#1E1E1E] font-bold mb-5">Adds Manually Leads</h1>
            @endif
        @endauth
        
        @guest
            <h1 class="text-4xl text-[#1E1E1E] font-bold mb-5">Daxtro - Contact Us</h1>
        @endguest

        <div class="lead-entry">
            @if(session('success'))
            <div id="success-message"></div>
            @endif
            <form action="{{ route('contact-us.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-2 gap-5">
                <div class="grid grid-cols-1">
                    {{-- PRIMARY CONTACT --}}
                    <div class="bg-white rounded-lg">
                        <h1 class="text-black uppercase border-b border-b-[#D9D9D9] p-3 font-semibold">Primary Contact</h1>
                        <div class="p-3 grid grid-cols-5 gap-3 justify-between">
                            {{-- FOR MR/MRS --}}
                            <div>
                                <div class="grid grid-cols-1 gap-1">
                                    <label class="text-[#1E1E1E]! mb-1!">Title <i class="required">*</i></label>
                                    <select name="title" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" required>
                                        <option value="Mr">Mr</option>
                                        <option value="Mrs">Mrs</option>
                                    </select>
                                </div>
                            </div>
        
                            {{-- FOR NAME --}}
                            <div class="grid grid-cols-1 gap-1">
                                <label class="text-[#1E1E1E]! mb-1!">Name <i class="required">*</i></label>
                                <input type="text" name="name" placeholder="Nama Lengkap" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" required>
                            </div>
        
                            {{-- FOR POSITION --}}
                            <div class="grid grid-cols-1 gap-1">
                                <label class="text-[#1E1E1E]! mb-1!">Position <i class="required">*</i></label>
                                <select name="jabatan_id" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" required>
                                    <option value="" disabled selected>Pilih</option>
                                    @foreach($jabatans as $jabatan)
                                    <option value="{{ $jabatan->id }}">{{ $jabatan->name }}</option>
                                    @endforeach
                                </select>
                            </div>
        
                            {{-- FOR PHONE --}}
                            <div class="grid grid-cols-1 gap-1">
                                <label class="text-[#1E1E1E]! mb-1!">Phone Number<i class="required">*</i></label>
                                <input type="text" name="phone" placeholder="0812xxxxxxx" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" required>
                            </div>
        
                            {{-- FOR EMAIL --}}
                            <div class="grid grid-cols-1 gap-1">
                                <label class="text-[#1E1E1E]! mb-1!">Email</label>
                                <input type="email" name="email" placeholder="email@domain.com" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" required>
                            </div>
                        </div>
                    </div>
        
                    {{-- COMPANY DETAILS AND LEAD CLASSIFICATION --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mt-3">
                        {{-- COMPANY DETAILS --}}
                        <div class="bg-white rounded-lg">
                            <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] text-[#1E1E1E]!">Company Details</h1>
                            <div class="p-3">
                                {{-- COMPANY NAME FIELD --}}
                                <div class="w-full grid grid-cols-1">
                                    <label class="text-[#1E1E1E]! mb-2! block!">Company Name<i class="required">*</i></label>
                                    <input type="text" name="company" placeholder="Nama Perusahaan" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" required>
                                </div>
        
                                {{-- CUSTOMER CITY AND PROVINCE FIELD SELECT --}}
                                <div class="w-full grid grid-cols-2 gap-2 mt-3">
                                    {{-- CUSTOMER CITY FIELD SELECT --}}
                                    <div class="w-full">
                                        <label class="text-[#1E1E1E]! mb-2! block!">Customer City <i class="required">*</i></label>
                                        <select name="region_id" class="select2 region-select rounded-lg! px-3! py-2! border! border-[#D9D9D9]! text-[#1E1E1E]! focus:outline-none!" required>
                                            <option value="" disabled selected>Pilih</option>
                                            <option value="ALL">All Regions (will show in all regions)</option>
                                            @foreach($regions as $region)
                                            <option value="{{ $region['id'] }}">{{ $region['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
        
                                    {{-- CUSTOMER PROVINCE FIELD SELECT --}}
                                    <div class="w-full">
                                        <label class="text-[#1E1E1E]! mb-2! block!">Customer Province <i class="required">*</i></label>
                                        <select name="province" class="select2 province-select bg-[#D9D9D9]! rounded-lg! px-3! py-2! border! border-[#D9D9D9]! text-[#1E1E1E]! focus:outline-none!" disabled>
                                        <option value="" selected>Pilih</option>
                                        @foreach($provinces as $province)
                                        <option value="{{ $province }}">{{ $province }}</option>
                                        @endforeach
                                        </select>
                                        <input type="hidden" name="province" class="province-hidden">
                                    </div>
                                </div>
                            </div>
                        </div>
        
                        {{-- LEAD CLASSIFICATION --}}
                        <div class="bg-white rounded-lg">
                            <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] text-[#1E1E1E]!">Leads Classification</h1>
                            <div class="p-3">
                                {{-- SOURCE SELECT FIELD --}}
                                <div class="w-full grid grid-cols-1">
                                    <label class="text-[#1E1E1E]! mb-2! block!">Source<i class="required">*</i></label>
                                    <select name="source_id" class="select2 source-select rounded-lg! px-3! py-2! border! border-[#D9D9D9]! text-[#1E1E1E]! focus:outline-none!" required>
                                        <option value="" disabled selected>Pilih</option>
                                        @php
                                            $filter = [
                                                'Ads Google',
                                                'Website',
                                                'Meta',
                                                'Linked In',
                                                'Tik Tok',
                                                'Friends Recommendation',
                                                'Canvas', 
                                                'Visit', 
                                                'Expo RHVAC Jakarta 2025',
                                                'Association',
                                                'Business Association',
                                                'Repeat Order',
                                                'Sales Independen',
                                                'Aftersales',
                                                'Office Walk In',
                                                'Media with QR/Referral',
                                                'Agent / Reseller',
                                                'Youtube',
                                                'Google Search',
                                                'Telemarketing',
                                            ];
                                            $isNew = empty($form_data->source_id);
                                        @endphp
        
                                        @foreach ($sources as $source)
                                            @if(in_array($source['name'], $filter))
                                            <option value="{{ $source['id'] }}">{{ $source['name'] }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
        
                                {{-- CUSTOMER TYPE FIELD SELECT --}}
                                <div class="w-full grid grid-cols-1 mt-3">
                                    {{-- CUSTOMER CITY FIELD SELECT --}}
                                    <div class="w-full">
                                        <label class="text-[#1E1E1E]! mb-2! block!">Customer Type <i class="required">*</i></label>
                                        <select name="customer_type" class="form-select select2" required>
                                            <option value="" disabled selected>Pilih</option>
                                            @foreach($customerTypes as $type)
                                            <option value="{{ $type->name }}">{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
        
                                {{-- EXISTING CUSTOMER INDUSTRY SELECT FIELD --}}
                                <div class="w-full grid grid-cols-1 mt-3">
                                    <label class="text-[#1E1E1E]! mb-2! block!">Existing Customer Industry<i class="required">*</i></label>
                                    <select name="industry_id" class="form-select select2 industry-select" required>
                                    <option value="" disabled selected>Pilih</option>
                                    @foreach($industries as $industry)
                                        <option value="{{ $industry->id }}">{{ $industry->name }}</option>
                                    @endforeach
                                    <option value="other">Lainnya</option>
                                    </select>
                                    <input type="text" name="other_industry" class="form-control mt-2 industry-other d-none" placeholder="Isi industri" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- REQUIREMENT & CONTEXT --}}
                <div class="grid grid-cols-1">
                    <div class="bg-white rounded-lg">
                    <h1 class="uppercase font-semibold p-3 border-b border-b-[#D9D9D9] text-[#1E1E1E]!">
                        Requirement & Context
                    </h1>
                    <div class="p-3">
                        {{-- PRIMARY CONTACT --}}
                        <div class="grid grid-cols-4 gap-3">
        
                            {{-- CONTACTING US FIELD --}}
                            <div class="w-full grid grid-cols-1">
                                <label class="form-label contact-reason-label text-[#1E1E1E]! mb-2! block!">Reason of Contacting Us</label>
                                <textarea name="contact_reason" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" rows="2" placeholder="Type Here..."></textarea>
                            </div>
        
                            {{-- COMPETITOR OFFER FIELD --}}
                            <div class="w-full grid grid-cols-1">
                                <label class="text-[#1E1E1E]! mb-2! block!">Competitor Offer</label>
                                <textarea name="competitor_offer" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" rows="2"  placeholder="Type Here..."></textarea>
                            </div>
        
                            {{-- OPEN BUSINESS FIELD --}}
                            <div class="w-full grid grid-cols-1">
                                <label class="text-[#1E1E1E]! mb-2! block!">Reason to Open Business</label>
                                <textarea name="business_reason" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" rows="2" placeholder="Type Here..."></textarea>
                            </div>
        
                            {{-- INDUSTRY REMARK FIELD --}}
                            <div class="w-full grid grid-cols-1">
                                <label class="text-[#1E1E1E]! mb-2! block!">Industry Remark</label>
                                <textarea name="industry_remark" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" placeholder="Additional comments about the industry" rows="2"></textarea>
                            </div>
                        </div>
        
                        {{-- COMPANY DETAILS AND LEAD CLASSIFICATION --}}
                        <div class="grid grid-cols-2 mt-3 gap-3">
                            <div>
                            <label class="text-[#1E1E1E]! mb-2! block!">Needs (Ice Machine Type)<i class="required">*</i></label>
                            <select name="needs" class="select2 px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none!" required>
                                <option value="" disabled selected>Pilih</option>
                                <option value="Tube Ice">Tube Ice</option>
                                <option value="Cube Ice">Cube Ice</option>
                                <option value="Block Ice">Block Ice</option>
                                <option value="Flake ice">Flake ice</option>
                                <option value="Slurry Ice">Slurry Ice</option>
                                <option value="Flake Ice">Flake Ice</option>
                                <option value="Cold Room">Cold Room</option>
                                <option value="Other">Other</option>
                            </select>
                            </div>
                        
                            {{-- TONASE FIELD NUMBER --}}
                            <div class="w-full">
                                <label class="text-[#1E1E1E]! mb-2! block!">Tonase</label>
                                <input type="number" step="0.01" name="tonase" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! appearance-none bg-white w-full! h-10" placeholder="0.00">
                            </div>
                        </div>
        
                        {{-- REQQUIREMENT & CONTEXT --}}
                        <div class="w-full grid grid-cols-2 mt-3 gap-3">
                            {{-- LEFT ITEMS FIELD --}}
                            <div class="w-full grid grid-cols-1">
                                {{-- CITY FACTORY TO BE FIELD --}}
                                <div class="w-full">
                                    <label class="text-[#1E1E1E]! mb-2! block!">City Factory To Be</label>
                                    <select name="factory_city_id" class="select2 factory-region-select rounded-lg! px-3! py-2! border! border-[#D9D9D9]! text-[#1E1E1E]! focus:outline-none!">
                                        <option value="" disabled selected>Pilih</option>
                                        <option value="ALL">All Cities</option>
                                        @foreach($regions as $region)
                                        <option value="{{ $region['id'] }}">{{ $region['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
        
                                {{-- PROVINCE FACTORY TO BE FIELD --}}
                                <div class="w-full mt-3">
                                    <label class="text-[#1E1E1E]! mb-2! block!">Province Factory To Be</label>
                                    <select name="factory_province" class="form-select select2 factory-province-select" disabled>
                                    <option value="" selected>Pilih</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province }}">{{ $province }}</option>
                                    @endforeach
                                    </select>
                                    <input type="hidden" name="factory_province" class="factory-province-hidden">
                                </div>
        
                                {{-- INDUSTRY TO BE SELECT FIELD --}}
                                <div class="w-full mt-3">
                                    <label class="text-[#1E1E1E]! mb-2! block!">Industry To Be</label>
                                    <select name="factory_industry_id" class="form-select select2 factory-industry-select">
                                        <option value="" disabled selected>Pilih</option>
                                        @foreach($industries as $industry)
                                            <option value="{{ $industry->id }}">{{ $industry->name }}</option>
                                        @endforeach
                                        <option value="other">Lainnya</option>
                                    </select>
                                    <input type="text" name="factory_other_industry" 
                                        class="form-control mt-2 factory-industry-other d-none" 
                                        placeholder="Isi industri"/>
                                </div>
                            </div>
        
                            {{-- RIGHT ITEMS FIELD --}}
                            <div class="w-full grid grid-cols-1">
        
                                {{-- TONNAGE REMARK FIELD --}}
                                <div class="w-full mt-3">
                                    <label class="text-[#1E1E1E]! mb-2! block!">Tonage Remark</label>
                                    <textarea name="tonage_remark" class="px-3! py-2! border! border-[#D9D9D9]! rounded-lg! text-[#1E1E1E]! focus:outline-none! w-full!" rows="9" placeholder="Type Here..."></textarea>
                                </div>
                            </div>
                        </div>    
                    </div>
                </div>
                </div>
            </div>
                <div class="flex justify-end gap-3 mt-3">
                    @auth
                        @if(auth()->user()->role?->code === 'branch_manager')
                            <a href="{{ route('leads.available') }}"
                            class="inline-block text-center w-[125px] px-3 py-2 border border-[#083224] text-[#083224] font-semibold rounded-lg cursor-pointer transition-all duration-300 bg-white">
                            Back
                            </a>
                        @endif
                    @endauth
                    <button type="submit" class="inline-block text-center w-[125px] px-3 py-2 bg-[#115640] transition-all duration-300 hover:bg-[#083224] text-white font-semibold rounded-lg cursor-pointer">Save</button>
                </div>
            </form>
        </div>
    </div>
  </div>
  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- jQuery -->
  <script src="{{ asset('sb-admin-2/vendor/jquery/jquery.min.js') }}"></script>
  <!-- Select2 JS -->
  <script src="{{ asset('assets/js/select2.min.js') }}?ver=1.0.3"></script>
  <script src="{{ asset('assets/js/sweetalert2.min.js') }}?ver=1.0.3"></script>
  <script>
$(document).ready(function () {

    $('.select2').select2({
        width: '100%'
    });

    const regions = @json($regions);

    function setProvince() {
      const regionId = $('.region-select').val();

      if (regionId === 'ALL' || !regionId) {
          $('.province-select')
              .val('')
              .prop('readonly', true)
              .trigger('change');

          $('.factory-province-select')
              .val('')
              .prop('readonly', true)
              .trigger('change');

          return;
      }

      const region = regions.find(r => String(r.id) === String(regionId));
      const province = region && region.province ? region.province.name : '';

      $('.province-select')
          .val(province)
          .trigger('change');

      $('.province-hidden').val(province);

      $('.factory-province-select')
          .val(province)
          .prop('readonly', true)
          .trigger('change');

      $('.factory-province-hidden').val(province);
  }

  $('.region-select').on('change', setProvince);
  setProvince();

    function setFactoryProvince() {
        const regionId = $('.factory-region-select').val();

        if (regionId === 'ALL' || !regionId) {
            $('.factory-province-select').val('').trigger('change');
            return;
        }

        const region = regions.find(r => String(r.id) === String(regionId));
        const province = region && region.province ? region.province.name : '';

        $('.factory-province-select').val(province).trigger('change');
    }

    $('.factory-region-select').on('change', setFactoryProvince);
    setFactoryProvince();

    function toggleSourceFields() {
        const selectedText = $('.source-select option:selected').text().trim();

        if (selectedText === 'Agent / Reseller') {
            $('.agent-fields').removeClass('hidden');
            $('#agent_title, #agent_name').prop('required', true);
        } else {
            $('.agent-fields').addClass('hidden');
            $('#agent_title, #agent_name')
                .prop('required', false)
                .val('');
        }

        if (selectedText === 'Canvas') {
            $('.canvas-fields').removeClass('hidden');
            $('#spk_canvassing').prop('required', true);
        } else {
            $('.canvas-fields').addClass('hidden');
            $('#spk_canvassing')
                .prop('required', false)
                .val('');
        }
    }

    $('.source-select').on('change', toggleSourceFields);
    toggleSourceFields();

    function toggleIndustryOther() {
        if ($('.industry-select').val() === 'other') {
            $('.industry-other')
                .removeClass('d-none hidden')
                .prop('required', true);
        } else {
            $('.industry-other')
                .addClass('hidden')
                .prop('required', false)
                .val('');
        }
    }

    $('.industry-select').on('change', toggleIndustryOther);
    toggleIndustryOther();


    // =============================
    // FACTORY INDUSTRY OTHER
    // =============================
    function toggleFactoryIndustryOther() {
        if ($('.factory-industry-select').val() === 'other') {
            $('.factory-industry-other')
                .removeClass('d-none hidden')
                .prop('required', true);
        } else {
            $('.factory-industry-other')
                .addClass('hidden')
                .prop('required', false)
                .val('');
        }
    }

    $('.factory-industry-select').on('change', toggleFactoryIndustryOther);
    toggleFactoryIndustryOther();


    // =============================
    // SUCCESS ALERT
    // =============================
    if (document.getElementById('success-message')) {
        Swal.fire({
            icon: 'success',
            html: `Thank you for submitting your inquiry to DAXTRO.<br>
                   We've successfully received your information.<br>
                   Our team will review your needs and reach out shortly.<br><br>
                   At DAXTRO, every detail is engineered for your progress.`
        });
    }

});
</script>
</body>
</html>