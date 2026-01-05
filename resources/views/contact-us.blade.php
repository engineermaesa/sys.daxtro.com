<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us | DAXTRO</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/favicon.png') }}">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Select2 CSS -->
  <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/select2.min.css') }}?ver=1.0.3">
  <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/sweetalert2.min.css') }}?ver=1.0.3">
  <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/main.css') }}?ver=1.0.3">
  <style>
    /* Corporate color palette */
    :root {
      --primary-color: #004080;   /* Dark Blue */
      --secondary-color: #66a3ff; /* Light Blue */
      --accent-color: #f2f2f2;    /* Light Gray */
      --text-color: #333333;
    }
    body {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text-color);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 0;
    }
    .card {
      background-color: #ffffff;
      border: none;
      border-radius: 1rem;
      box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
      max-width: 800px;
      width: 100%;
    }
    .card-header {
      background-color: var(--primary-color);
      border-top-left-radius: 1rem;
      border-top-right-radius: 1rem;
      padding: 1.5rem;
      text-align: center;
    }
    .card-header h2 {
      margin: 0;
      color: #ffffff;
      font-weight: 600;
    }
    .form-label {
      font-weight: 600;
    }
    .form-control,
    .form-select,
    .form-control:focus,
    .form-select:focus {
      border-radius: 0.5rem;
      border: 1px solid #ccd1d9;
      box-shadow: none;
    }
    .btn-primary {
      background-color: var(--primary-color);
      border: none;
      border-radius: 0.5rem;
      padding: 0.75rem 1.5rem;
      font-weight: 600;
      transition: background-color 0.3s ease;
    }
    .btn-primary:hover {
      background-color: #003366;
    }
    .section-divider {
      border-top: 2px solid #e9ecef;
      margin: 2rem 0 1.5rem 0;
      padding-top: 1.5rem;
    }
    .section-title {
      font-weight: 600;
      color: var(--primary-color);
      margin-bottom: 1rem;
    }
    .agent-fields, .canvas-fields {
      display: none;
    }
    .industry-other, .factory-industry-other {
      display: none;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="card-header">
      <h2>DAXTRO - Contact Us</h2>
    </div>
    <div class="card-body p-4">
      @if(session('success'))
        <div id="success-message"></div>
      @endif
      <form action="{{ route('contact-us.store') }}" method="POST">
        @csrf
        
        <!-- Contact Information Section -->
        <div class="row g-3">
          <div class="col-12">
            <h5 class="section-title">Contact Information</h5>
          </div>
          
          <div class="col-md-2">
            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
            <select class="form-select" id="title" name="title" required>
              <option value="Mr">Mr</option>
              <option value="Mrs">Mrs</option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" placeholder="Nama Lengkap" required>
          </div>
          <div class="col-md-6">
            <label for="jabatan_id" class="form-label">Jabatan</label>
            <select class="form-select select2" id="jabatan_id" name="jabatan_id">
              <option value="" disabled selected>Pilih</option>
              @foreach($jabatans as $jabatan)
                <option value="{{ $jabatan->id }}">{{ $jabatan->name }}</option>
              @endforeach
            </select>
          </div>
          
          <div class="col-md-6">
            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="phone" name="phone" placeholder="0812xxxxxxx" required>
          </div>                    
          <div class="col-md-6">
            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="email" name="email" placeholder="email@domain.com" required>
          </div>
        </div>

        <!-- Source & Company Information Section -->
        <div class="section-divider">
          <div class="row g-3">
            <div class="col-12">
              <h5 class="section-title">Source & Company Information</h5>
            </div>
            
            <div class="col-md-6">
              <label for="source_id" class="form-label">Source <span class="text-danger">*</span></label>
              <select class="form-select select2" id="source_id" name="source_id" required>
                <option value="" disabled selected>Pilih</option>
                @php
                  $filter = [
                      'Ads Google', 'Website', 'Meta', 'Linked In', 'Tik Tok',
                      'Friends Recommendation', 'Canvas', 'Visit', 'Expo RHVAC Jakarta 2025',
                      'Association', 'Business Association', 'Repeat Order', 'Sales Independen',
                      'Aftersales', 'Office Walk In', 'Media with QR/Referral', 'Agent / Reseller',
                      'Youtube', 'Google Search', 'Telemarketing'
                  ];
                @endphp
                @foreach($sources as $source)
                  @if(in_array($source['name'], $filter))
                    <option value="{{ $source['id'] }}">{{ $source['name'] }}</option>
                  @endif
                @endforeach
              </select>
            </div>

            <!-- Agent fields (hidden by default) -->
            <div class="col-md-2 agent-fields">
              <label for="agent_title" class="form-label">Agent Title</label>
              <select class="form-select" id="agent_title" name="agent_title">
                <option value="">Select Title</option>
                <option value="Mr">Mr</option>
                <option value="Mrs">Mrs</option>
                <option value="Ms">Ms</option>
                <option value="Dr">Dr</option>
              </select>
            </div>
            <div class="col-md-4 agent-fields">
              <label for="agent_name" class="form-label">Agent Name</label>
              <input type="text" class="form-control" id="agent_name" name="agent_name" placeholder="Enter agent name">
            </div>

            <!-- Canvas fields (hidden by default) -->
            <div class="col-md-6 canvas-fields">
              <label for="spk_canvassing" class="form-label">SPK Canvassing</label>
              <input type="text" class="form-control" id="spk_canvassing" name="spk_canvassing" placeholder="Enter SPK Canvassing details">
            </div>

            <div class="col-md-6">
              <label for="company" class="form-label">Company <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="company" name="company" placeholder="Nama Perusahaan" required>
            </div>

            <div class="col-md-6">
              <label for="customer_type" class="form-label">Customer Type <span class="text-danger">*</span></label>
              <select class="form-select select2" id="customer_type" name="customer_type" required>
                <option value="" disabled selected>Pilih</option>
                @foreach($customerTypes as $type)
                  <option value="{{ $type->name }}">{{ $type->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-12">
              <label for="contact_reason" class="form-label">Reason of Contacting Us</label>
              <textarea class="form-control" id="contact_reason" name="contact_reason" rows="2"></textarea>
            </div>

            <div class="col-md-12">
              <label for="business_reason" class="form-label">Reason to Open Business</label>
              <textarea class="form-control" id="business_reason" name="business_reason" rows="2"></textarea>
            </div>

            <div class="col-md-12">
              <label for="competitor_offer" class="form-label">Competitor Offer</label>
              <textarea class="form-control" id="competitor_offer" name="competitor_offer" rows="2"></textarea>
            </div>
          </div>
        </div>

        <!-- Location Information Section -->
        <div class="section-divider">
          <div class="row g-3">
            <div class="col-12">
              <h5 class="section-title">Location Information</h5>
            </div>
            
            <div class="col-md-6">
              <label for="region_id" class="form-label">Customer City <span class="text-danger">*</span></label>
              <select class="form-select select2" id="region_id" name="region_id" required>
                <option value="" disabled selected>Pilih</option>
                <option value="ALL">All Regions (will show in all regions)</option>
                @foreach($regions as $region)
                  <option value="{{ $region['id'] }}">{{ $region['name'] }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label for="province" class="form-label">Customer Province <span class="text-danger">*</span></label>
              <select class="form-select select2 province-select" id="province" name="province" required>
                <option value="" selected>Pilih</option>
                @foreach($provinces as $province)
                  <option value="{{ $province }}">{{ $province }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>

        <!-- Industry Information Section -->
        <div class="section-divider">
          <div class="row g-3">
            <div class="col-12">
              <h5 class="section-title">Industry Information</h5>
            </div>
            
            <div class="col-md-12">
              <label for="industry_id" class="form-label">Existing Customer Industry <span class="text-danger">*</span></label>
              <select class="form-select select2" id="industry_id" name="industry_id" required>
                <option value="" disabled selected>Pilih</option>
                @foreach($industries as $industry)
                  <option value="{{ $industry->id }}">{{ $industry->name }}</option>
                @endforeach
                <option value="other">Lainnya</option>
              </select>
              <input type="text" class="form-control mt-2 industry-other" id="other_industry" name="other_industry" placeholder="Isi industri" />
            </div>

            <div class="col-md-12">
              <label for="industry_remark" class="form-label">Industry Remark</label>
              <textarea class="form-control" id="industry_remark" name="industry_remark" placeholder="Additional comments about the industry" rows="2"></textarea>
            </div>
          </div>
        </div>

        <!-- Factory Information Section -->
        <div class="section-divider">
          <div class="row g-3">
            <div class="col-12">
              <h5 class="section-title">Factory Information (Optional)</h5>
            </div>
            
            <div class="col-md-6">
              <label for="factory_city_id" class="form-label">City Factory To Be</label>
              <select class="form-select select2" id="factory_city_id" name="factory_city_id">
                <option value="" disabled selected>Pilih</option>
                <option value="ALL">All Cities</option>
                @foreach($regions as $region)
                  <option value="{{ $region['id'] }}">{{ $region['name'] }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label for="factory_province" class="form-label">Province Factory To Be</label>
              <select class="form-select select2" id="factory_province" name="factory_province">
                <option value="" selected>Pilih</option>
                @foreach($provinces as $province)
                  <option value="{{ $province }}">{{ $province }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-12">
              <label for="factory_industry_id" class="form-label">Industry To Be</label>
              <select class="form-select select2" id="factory_industry_id" name="factory_industry_id">
                <option value="" disabled selected>Pilih</option>
                @foreach($industries as $industry)
                  <option value="{{ $industry->id }}">{{ $industry->name }}</option>
                @endforeach
                <option value="other">Lainnya</option>
              </select>
              <input type="text" class="form-control mt-2 factory-industry-other" id="factory_other_industry" name="factory_other_industry" placeholder="Isi industri" />
            </div>
          </div>
        </div>

        <!-- Product Needs Section -->
        <div class="section-divider">
          <div class="row g-3">
            <div class="col-12">
              <h5 class="section-title">Product Needs</h5>
            </div>
            
            <div class="col-md-8">
              <label for="needs" class="form-label">Needs <span class="text-danger">*</span></label>
              <select class="form-select select2" id="needs" name="needs" required>
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
            <div class="col-md-4">
              <label for="tonase" class="form-label">Tonase</label>
              <input type="number" step="0.01" class="form-control" id="tonase" name="tonase" placeholder="0.00">
            </div>

            <div class="col-md-12">
              <label for="tonage_remark" class="form-label">Tonage Remark</label>
              <textarea class="form-control" id="tonage_remark" name="tonage_remark" rows="2"></textarea>
            </div>
          </div>
        </div>

        <input type="hidden" name="segment_id" />
        
        <div class="mt-4 text-center">
          <button type="submit" class="btn btn-primary">Submit</button>
        </div>
      </form>
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
      // Initialize Select2 for all except province
      $('.select2').not('#province, .province-select').select2({ width: '100%' });

      // Initialize Select2 specifically for province (disabled)
      $('#province').select2({
        width: '100%'
      }).on('select2:opening', e => e.preventDefault());

      // Style disabled province
      $('#province').next('.select2-container').find('.select2-selection').css({
        'background-color': '#e9ecef',
        'color': '#6c757d',
        'pointer-events': 'none',
        'border-color': '#ced4da'
      });

      // Auto-set province based on city
      const regions = @json($regions);

      function setProvince() {
        const regionId = $('#region_id').val();
        if (regionId === 'ALL') {
          $('#province').val('').trigger('change.select2');
          return;
        }
        const region = regions.find(r => String(r.id) === String(regionId));
        const province = region && region.province ? region.province.name : '';
        $('#province').val(province).trigger('change.select2');
      }

      $('#region_id').on('change', setProvince);
      setProvince();

      // Auto-set factory province based on factory city
      $('#factory_city_id').on('change', function() {
        const regionId = $(this).val();
        if (regionId === 'ALL' || !regionId) {
          $('#factory_province').val('').trigger('change.select2');
          return;
        }
        const region = regions.find(r => String(r.id) === String(regionId));
        const province = region && region.province ? region.province.name : '';
        $('#factory_province').val(province).trigger('change.select2');
      });

      // Handle source-specific fields
      function toggleSourceFields() {
        const selectedText = $('#source_id option:selected').text().trim();
        
        // Handle Agent / Reseller
        if (selectedText === 'Agent / Reseller') {
          $('.agent-fields').show();
          $('#agent_title, #agent_name').prop('required', true);
        } else {
          $('.agent-fields').hide();
          $('#agent_title, #agent_name').prop('required', false).val('');
        }
        
        // Handle Canvas
        if (selectedText === 'Canvas') {
          $('.canvas-fields').show();
          $('#spk_canvassing').prop('required', true);
        } else {
          $('.canvas-fields').hide();
          $('#spk_canvassing').prop('required', false).val('');
        }
      }

      $('#source_id').on('change', toggleSourceFields);
      toggleSourceFields();

      // Handle industry "other" option
      function toggleIndustryOther() {
        if ($('#industry_id').val() === 'other') {
          $('.industry-other').show().prop('required', true);
        } else {
          $('.industry-other').hide().prop('required', false).val('');
        }
      }

      $('#industry_id').on('change', toggleIndustryOther);
      toggleIndustryOther();

      // Handle factory industry "other" option
      function toggleFactoryIndustryOther() {
        if ($('#factory_industry_id').val() === 'other') {
          $('.factory-industry-other').show().prop('required', true);
        } else {
          $('.factory-industry-other').hide().prop('required', false).val('');
        }
      }

      $('#factory_industry_id').on('change', toggleFactoryIndustryOther);
      toggleFactoryIndustryOther();

      // Success message
      if (document.getElementById('success-message')) {
        Swal.fire({
          icon: 'success',
          html: `Thank you for submitting your inquiry to DAXTRO.<br>` +
                `We've successfully received your information.<br>` +
                `Our team will review your needs and reach out shortly to help design the most suitable ice machine solution for your business journey.<br><br>` +
                `At DAXTRO, every detail is engineered for your progress.`
        });
      }
    });
  </script>
</body>
</html>