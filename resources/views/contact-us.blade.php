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
      max-width: 600px;
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
        <div class="row g-3">
          <div class="col-md-6">
            <label for="source_id" class="form-label">Source</label>
            <select class="form-select select2" id="source_id" name="source_id" required>
              <option value="" disabled selected>Pilih</option>
              @foreach($sources as $source)
                <option value="{{ $source['id'] }}">{{ $source['name'] }}</option>
              @endforeach
            </select>
          </div>      
          <div class="col-md-6">
            <label for="customer_type" class="form-label">Customer Type</label>
            <select class="form-select select2" id="customer_type" name="customer_type" required>
              <option value="" disabled selected>Pilih</option>
              @foreach($customerTypes as $type)
                <option value="{{ $type->name }}">{{ $type->name }}</option>
              @endforeach
            </select>
          </div>
          <input type="hidden" name="segment_id" />
          <div class="col-md-6">
            <label for="region_id" class="form-label">City</label>
            <select class="form-select select2" id="region_id" name="region_id" required>
              <option value="" disabled selected>Pilih</option>
              @foreach($regions as $region)
                <option value="{{ $region['id'] }}">{{ $region['name'] }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label for="province" class="form-label">Province</label>
            <select class="form-select select2 province-select" id="province" name="province" required>
              <option value="" disabled selected>Pilih</option>
              @foreach($provinces as $province)
                <option value="{{ $province }}">{{ $province }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-12">
            <label for="company" class="form-label">Company</label>
            <input type="text" class="form-control" id="company" name="company" placeholder="Nama Perusahaan" required>
          </div>
          <div class="col-md-2">
            <label for="title" class="form-label">Title</label>
            <select class="form-select" id="title" name="title" required>
              <option value="Mr">Mr</option>
              <option value="Mrs">Mrs</option>
            </select>
          </div>
          <div class="col-md-10">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" placeholder="Nama Lengkap" required>
          </div>
          <div class="col-md-12">
            <label for="jabatan_id" class="form-label">Jabatan</label>
            <select class="form-select select2" id="jabatan_id" name="jabatan_id">
              <option value="" disabled selected>Pilih</option>
              @foreach($jabatans as $jabatan)
                <option value="{{ $jabatan->id }}">{{ $jabatan->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label for="phone" class="form-label">Phone Number</label>
            <input type="text" class="form-control" id="phone" name="phone" placeholder="0812xxxxxxx" required>
          </div>                    
          <div class="col-md-6">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="email@domain.com" required>
          </div>
          <div class="col-md-8">
            <label for="needs" class="form-label">Needs</label>
            <select class="form-select select2" id="needs" name="needs" required>
              <option value="" disabled selected>Pilih</option>
              <option value="Tube Ice ( Mesin Es Kristal Tabung )">Tube Ice ( Mesin Es Kristal Tabung )</option>
              <option value="Cube Ice ( Mesin Es Kristal Kubus )">Cube Ice ( Mesin Es Kristal Kubus )</option>
              <option value="Block Ice ( Mesin Es Balok )">Block Ice ( Mesin Es Balok )</option>
              <option value="Flake ice ( Mesin Es Pecah )">Flake ice ( Mesin Es Pecah )</option>
              <option value="Slurry Ice ( Es Bubur halus )">Slurry Ice ( Es Bubur halus )</option>
              <option value="Flake Ice ( Es Serpih )">Flake Ice ( Es Serpih )</option>
              <option value="Cold Room ( Ruang Pendingin )">Cold Room ( Ruang Pendingin )</option>
              <option value="Other ( Keperluan Kustom )">Other ( Keperluan Kustom )</option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="tonase" class="form-label">Tonase (Optional)</label>
            <input type="number" step="0.01" class="form-control" id="tonase" name="tonase" placeholder="0.00" style="padding-top: 5px; padding-bottom: 5px;">
          </div>
        </div>
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
      // Inisialisasi Select2 untuk semua kecuali #province
      $('.select2').not('#province').select2({ width: '100%' });

      // Inisialisasi Select2 khusus untuk #province (harus terakhir agar tidak ditimpa)
      $('#province').select2({
        width: '100%'
      }).on('select2:opening', e => e.preventDefault());

      // Tambahkan gaya disable seperti "disabled"
      $('#province').next('.select2-container').find('.select2-selection').css({
        'background-color': '#e9ecef',
        'color': '#6c757d',
        'pointer-events': 'none',
        'border-color': '#ced4da' // biar seragam dengan form-control lainnya
      });

      // Atur otomatis provinsi sesuai City
      const regions = @json($regions);

      function setProvince() {
        const regionId = $('#region_id').val();
        const region = regions.find(r => String(r.id) === String(regionId));
        const province = region && region.province ? region.province.name : '';
        $('#province').val(province).trigger('change.select2');
      }

      $('#region_id').on('change', setProvince);
      setProvince();

      // Pesan sukses (SweetAlert)
      if (document.getElementById('success-message')) {
        Swal.fire({
          icon: 'success',
          html: `Thank you for submitting your inquiry to DAXTRO.<br>` +
                `We’ve successfully received your information.<br>` +
                `Our team will review your needs and reach out shortly to help design the most suitable ice machine solution for your business journey.<br><br>` +
                `At DAXTRO, every detail is engineered for your progress.`
        });
      }
    });

  </script>
</body>
</html>
