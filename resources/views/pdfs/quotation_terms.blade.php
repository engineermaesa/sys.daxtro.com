{{-- resources/views/pdfs/quotation_terms.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    @font-face {
      font-family: 'Montserrat';
      font-weight: normal;
      src: url('{{ storage_path("fonts/Montserrat-Regular.ttf") }}') format("truetype");
    }
    @font-face {
      font-family: 'Montserrat';
      font-weight: bold;
      src: url('{{ storage_path("fonts/Montserrat-Bold.ttf") }}') format("truetype");
    }

    @page { size: A4; margin: 0; }
    body {
      font-family: 'Montserrat', sans-serif;
      font-size: 8pt;
      margin: 0;
    }

    .page-container { position: relative; width: 210mm; height: 297mm; }
    .header-slice, .footer-slice {
      position: absolute; left: 0; width: 100%;
    }
    .header-slice { top: 0; height: auto; }
    .footer-slice { bottom: 0; height: auto; }

    .content {
      position: absolute;
      top: 35mm;    /* below header */
      bottom: 25mm; /* above footer */
      left: 15mm; right: 15mm;
      line-height: 1.2;
    }

    .section-title {
      text-align: center;
      font-weight: bold;
      font-size: 9pt;
      margin: 0;
    }
    .section-divider {
      border-top: 1pt solid #000;
      margin: 4pt 0;
    }

    ul {
      margin: 6pt 0 12pt 12pt;
      padding: 0;
    }
    ul li {
      margin-bottom: 4pt;
    }

    .signatures {
      display: flex;
      justify-content: space-between;
      margin-top: 30mm;
      font-size: 8pt;
    }
    .sign-box {
      width: 45%;
      text-align: left;
      line-height: 1.2;
    }
    .sign-box b { display: block; margin-top: 24pt; /* space for signature */ }

  </style>
</head>
<body>
  <div class="page-container">
    {{-- header slice --}}
    <img src="{{ public_path('assets/images/quotation/header-2-slice.png') }}" class="header-slice" />

    <div class="content">
      <div class="section-divider"></div>
      <div class="section-title">TERM &amp; CONDITIONS</div>
      <div class="section-divider"></div>

      <p><b>Price Includes:</b></p>
      {{-- Static bullets --}}
      <ul>
        <li>Full 1-year warranty with two free maintenance visits, covering machine, electrical system, and spare parts</li>
        <li>Shipping from Guangzhou, China to Indonesia Port</li>
        <li>Freon R507 Chemours</li>
        <li>Technician accommodation costs during installation and maintenance</li>
        <li>Staff training for machine operation</li>
        <li>Delivery from Indonesian port to customer’s site</li>
        <li>After-sales service</li>
        <li>Supporting equipment and materials for installation (such as water tank, pump, RO unit, scale, sealer, ice bin, and piping)</li>
      </ul>

      {{-- Dynamic Payment Terms --}}
      <p><b>Payment Terms:</b></p>
      <ul>
        @foreach($quotation->paymentTerms as $term)
          <li>Payment {{ $term->term_no }}: {{ $term->percentage }}% {{ $term->description }}</li>
        @endforeach
      </ul>

      {{-- Static Production Timeline, Warranty & Bank Info --}}
      <p><b>Production Timeline:</b></p>
      <ul>
        <li>Machine production: 65 days</li>
        <li>International shipping: 14 days</li>
        <li>Import clearance: 10 days</li>
        <li>Installation Requirements: Site preparation including structure, water lines, and electrical systems must be provided by the customer</li>
      </ul>

      <p><b>Warranty:</b></p>
      <ul>
        <li>Limited warranty of 12 months, starting from the official handover date (BAST)</li>
      </ul>

      <p><b>Bank Information:</b></p>
      <ul>
        <li>All payments must be transferred to the official company bank account as stated in the attached invoice or billing letter</li>
        @php
          $quoteExpiry = $quotation->created_at->copy()->addDays(15);
          $firstProforma = $quotation->proformas->sortBy('issued_at')->first();
          $proformaExpiry = $firstProforma
              ? $firstProforma->issued_at->copy()->addDays(30)
              : $quotation->created_at->copy()->addDays(30);
        @endphp
        <li>
          Quotation Validity: until {{ $quoteExpiry->format('d M Y') }}
          (15 days from issue)
        </li>
        <li>
          Quote valid for 15 days (until {{ $quoteExpiry->format('d M Y') }})
          &rarr; Pending Payment
        </li>
        <li>
          Proforma invoice valid for 30 days (until
          {{ $proformaExpiry->format('d M Y') }}) &rarr; Pending Payment
        </li>
      </ul>

      {{-- Signatures as a table --}}
      <table style="width:100%; margin-top:4rem; border-collapse:collapse;">
        <tr>
          {{-- Left column --}}
          <td style="vertical-align:top; width:50%; padding-top:2rem;">

            {{-- Label --}}
            <p style="margin:0 0 1.5rem 0; font-size:12pt; color:#000;">
              Your Sincerely,
            </p>

            {{-- Company line --}}
            <p style="margin:0 0 2rem 0; font-size:8pt; color:#dc3545;">
              PT. Pandu Naradipta Danendra
            </p>

            {{-- Sales name --}}
            <p style="margin:0 0 0.5rem 0; font-size:10pt; color:#dc3545; font-weight:bold;">
              {{ optional($claim->sales)->name }}
            </p>

            {{-- Sales role --}}
            <p style="margin:0 0 0; font-size:10pt; color:#dc3545;">
              {{ optional($claim->sales->role)->name }}
            </p>
          </td>

          {{-- Right column --}}
          <td style="vertical-align:top; width:50%; text-align:right; padding-top:2rem;">

            {{-- Label --}}
            <p style="margin:0 0 1.5rem 0; font-size:12pt; color:#000;">
              Menyetujui,
            </p>

            {{-- empty spacer to align company line height --}}
            <p style="margin:0 0 2rem 0; font-size:8pt; color:transparent;">
              &nbsp;
            </p>

            {{-- Customer name --}}
            <p style="margin:0 0 0.5rem 0; font-size:10pt; color:#dc3545; font-weight:bold;">
              {{ $quotation->lead->name }}
            </p>

            {{-- Customer status --}}
            <p style="margin:0 0 0; font-size:10pt; color:#dc3545;">
              Customer
            </p>
          </td>
        </tr>
      </table>
    </div>

    {{-- footer slice --}}
    <img src="{{ public_path('assets/images/quotation/footer-slice.png') }}" class="footer-slice" />
  </div>
</body>
</html>
