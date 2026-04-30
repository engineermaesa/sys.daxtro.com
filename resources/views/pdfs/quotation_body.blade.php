{{-- resources/views/pdfs/quotation_body.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <style>
    /* Font Definitions */
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

    /* Page Layout */
    @page {
      size: A4;
      margin: 0;
    }

    body {
      font-family: 'Montserrat', sans-serif;
      font-size: 5pt;
      margin: 0;
      line-height: 1.3;
    }

    .page-container {
      position: relative;
      width: 210mm;
      height: 297mm;
    }

    /* Header & Footer */
    .header-slice,
    .footer-slice {
      position: absolute;
      left: 0;
      width: 100%;
    }

    .header-slice {
      top: 0;
      z-index: 3;
      /* height: 50mm;  */
    }

    .footer-slice {
      bottom: 0;
      height: auto;
      z-index: 0;
    }

    /* Content Container */
    .content {
      position: absolute;
      top: 18mm;
      bottom: 22mm;
      left: 12mm;
      right: 12mm;
      border: 1px solid #000;
      z-index: 2;
    }

    /* Typography */
    .section-title {
      font-weight: bold;
      font-size: 5pt;
    }

    .section-title-quotation {
      font-weight: bold;
      font-size: 6pt;
      background-color: #cfd2ceff;
    }

    .text-bold {
      font-weight: bold;
    }

    .text-center {
      text-align: center;
    }

    .text-right {
      text-align: right;
    }

    .text-uppercase {
      text-transform: uppercase;
    }

    /* Info Table Styles */
    .info-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 6pt;
      font-size: 7.5pt;
    }

    .info-table td {
      /* border: 1px solid #000; */
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
    }

    .info-table .header-row td {
      text-align: center;
      padding: 4pt 4pt;
    }

    .info-table .title-cell {
      width: 60%;
    }

    .info-table .quotation-cell,
    .info-table .sales-cell {
      width: 20%;
    }

    .info-table .customer-cell {
      width: 40%;
      padding: 6pt 2pt;
    }

    .info-table .publisher-cell {
      width: 60%;
      padding: 6pt 2pt;
    }

    /* Customer & Publisher Info */
    .company-name {
      font-weight: bold;
      text-transform: uppercase;
      margin-bottom: 1pt;
    }

    .contact-info {
      margin-bottom: 0.5pt;
      font-size: 5pt;
    }

    .address-info {
      font-size: 5pt;
    }

    .section-header {
      margin-bottom: 2px;
      padding-bottom: 1pt;
    }

    .content-wrapper {
      /* margin-top: 4pt;
      line-height: 1.3; */
    }

    /* Items Table */
    .items-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 5pt;
    }

    .items-table thead th {
      font-weight: bold;
      text-align: center;
      padding: 6pt 5pt;
      /* border-bottom: 1pt solid #000; */
    }

    .items-table tbody td {
      border-bottom: 1px solid #8d8d8d;
      padding: 6pt 5pt;
      vertical-align: top;
    }

    /* Column Widths */
    .col-no {
      width: 5%;
    }

    .col-machine {
      width: 20%;
    }

    .col-spec {
      width: 20%;
    }

    .col-unit-price {
      width: 20%;
    }

    .col-qty {
      width: 8%;
    }

    .col-price {
      width: 20%;
    }

    .col-disc {
      width: 10%;
    }

    .col-final {
      width: 22%;
    }

    /* Totals Table */
    .totals-table {
      width: 100%;
      font-size: 4.5pt;
      border-collapse: collapse;
      margin-top: 6px;
      line-height: 1.05;
    }

    .totals-table td {
      padding: 1.5pt 3pt;
      vertical-align: middle;
    }

    .totals-label {
      text-align: right;
      width: 72%;
      font-weight: bold;
      padding-right: 2pt;
    }

    .totals-colon {
      width: 1.5%;
      text-align: center;
      font-weight: bold;
      padding: 0 2pt;
    }

    .totals-value {
      text-align: right;
      width: 16.5%;
      font-weight: bold;
    }

    /* Terms & Conditions */
    .terms-table {
      width: 100%;
      margin-top: 20px;
      border-collapse: collapse;
    }

    /* Payment table styles */
    .payment-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 6pt;
      font-size: 5pt;
    }

    .payment-table th,
    .payment-table td {
      border: 1px solid #000;
      padding: 4pt 3pt;
      vertical-align: top;
    }

    .payment-table thead th {
      background-color: #f3f3f3;
      font-weight: bold;
      text-align: left;
    }

    /* Estimate table styles (left-aligned label:value) */
    .estimate-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 4.5pt;
      margin: 0;
      table-layout: fixed;
    }

    .estimate-table td {
      vertical-align: top;
      padding: 0;
    }

    .estimate-table .est-label {
      width: 30%;
      text-align: left;
      padding-right: 4pt;
    }

    .estimate-table .est-colon {
      width: 2%;
      text-align: left;
      padding: 0 2pt;
    }

    .estimate-table .est-value {
      width: 68%;
      text-align: left;
      padding-left: 2pt;
    }

    .terms-header {
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
      text-align: center;
      padding: 4pt;
      font-weight: bold;
      font-size: 6pt;
      background-color: #cfd2ceff;
    }

    .terms-content {
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
      padding: 3pt 4pt 2pt 6pt;
      vertical-align: top;
    }

    .terms-section {
      margin-bottom: 2pt;
    }

    .terms-title {
      font-weight: bold;
      margin-bottom: 4pt;
      font-size: 5pt;
      line-height: 1.1;
    }

    .terms-list {
      margin: 0;
      padding-left: 10pt;
      font-size: 4.5pt;
      line-height: 1.15;
    }

    .terms-list li {
      margin-bottom: 1.5pt;
    }

    /* Two Column Layout for Terms */
    .two-column {
      display: table;
      width: 100%;
    }

    .column-left {
      display: table-cell;
      width: 48%;
      vertical-align: top;
      padding-right: 8pt;
    }

    .column-right {
      display: table-cell;
      width: 48%;
      vertical-align: top;
      padding-left: 8pt;
    }

    /* Signatures */
    .signature-section {
      border-top: none;
      padding: 12pt 6pt 6pt 6pt;
      vertical-align: top;
    }

    .signature-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 4pt;
    }

    .signature-left,
    .signature-right {
      width: 48%;
      vertical-align: top;
      text-align: left;
      padding-top: 2pt;
    }

    .signature-spacer {
      width: 6%;
    }

    .signature-greeting {
      font-size: 5pt;
      margin-bottom: 10pt;
    }

    .signature-company {
      font-size: 4.5pt;
      margin-bottom: 6pt;
      font-weight: bold;
    }

    .signature-line {
      margin-top: 16pt;
      border-bottom: 1px solid #000;
      width: 80%;
      margin-bottom: 6pt;
    }

    .signature-name {
      font-size: 4.5pt;
      font-weight: bold;
      text-transform: uppercase;
      line-height: 1.1;
      margin-top: 2pt;
    }

    .signature-role {
      font-size: 5pt;
      margin-top: 1pt;
    }

    .signature-transparent {
      color: transparent;
    }
  </style>
</head>

<body>
  <div class="page-container">
    <img src="{{ public_path('assets/images/quotation/header-8-slice-KILLYOURSELF.png') }}" class="header-slice" />

    <div class="content">
      <table class="info-table">
        <tr class="header-row">
          <td colspan="3" class="section-title-quotation">QUOTATION</td>
        </tr>

        <tr>
          <td class="title-cell">
            <div class="section-title">Customer ID: {{ $quotation->lead->id }}</div>
          </td>
          <td class="quotation-cell">
            <div class="section-title">Quotation No: {{ $quotation->quotation_no }}</div>
          </td>
          <td class="sales-cell">
            <div class="section-title text-uppercase">
              Sales Name: {{ optional($claim->sales)->name ?? 'Hizkia Rudi' }}
            </div>
          </td>
        </tr>

        <!-- Content Row -->
        <tr>
          <td class="customer-cell">
            <div class="content-wrapper">
              <div class="section-title section-header">Customer Company:</div>
              <div class="company-name">{{ $quotation->lead->company }}</div>
              <div class="contact-info">{{ $quotation->lead->name }}</div>
              <div class="contact-info">{{ $quotation->lead->phone }}</div>
              <div class="address-info">{{ $quotation->lead->company_address ?? 'Jalan Bangka, Banjarmasin Kota no 14 b,
                Banjarmasin' }}</div>
            </div>
          </td>

          <td colspan="2" class="publisher-cell">
            <div class="section-title section-header">Quotation Published By:</div>
            <div class="content-wrapper">
              <div class="company-name">
                {{ optional($user)->company_name ?? 'PT Pandu Mahardika Perdana' }}
              </div>
              @if($user && $user->company_name && $user->company_address)
              <div class="address-info">{{ $user->company_address }}</div>
              @if($user->company_address_2)
              <div class="address-info">{{ $user->company_address_2 }}</div>
              @endif
              <div class="address-info">{{ $user->company_city }} {{ $user->company_postal }}</div>
              @else
              <div class="address-info">Komplek Harmoni Plaza</div>
              <div class="address-info">Blok A No.16-17, Jl. Suryopranoto</div>
              <div class="address-info">Jakarta (10130)</div>
              @endif
            </div>
          </td>
        </tr>

        <!-- Items Table Row -->
        <td colspan="3">
          <table class="items-table">
            <thead>
              <tr>
                <th class="col-no">No</th>
                <th class="col-machine">Machine</th>
                <th class="col-spec">Specification</th>
                <th class="col-unit-price">Unit Price</th>
                <th class="col-qty">Qty</th>
                <th class="col-price">Price</th>
                <th class="col-disc">Disc</th>
                <th class="col-final">Final Price</th>
              </tr>
            </thead>
            <tbody>
              @php
              $pdfItems = [];

              foreach($quotation->items->where('is_visible_pdf', true) as $item) {
              $pdfItem = [
              'description' => $item->description,
              'product' => $item->product,
              'unit_price' => $item->unit_price,
              'qty' => $item->qty,
              'price' => $item->unit_price * $item->qty,
              'discount_pct' => $item->discount_pct,
              'amount' => $item->line_total,
              ];

              $mergedAmount = 0;
              foreach($quotation->items->where('merge_into_item_id', $item->id) as $mergedItem) {
              $mergedAmount += $mergedItem->line_total;
              }

              $pdfItem['amount'] += $mergedAmount;
              $pdfItems[] = $pdfItem;
              }
              @endphp

              @foreach($pdfItems as $i => $item)
              <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $item['product']->name ?? $item['description'] }}</td>
                <td>{{ $item['product']->sku ?? '—' }}</td>
                <td class="text-right">Rp {{ number_format($item['unit_price'], 0, ',', '.') }}</td>
                <td class="text-center">{{ $item['qty'] }}</td>
                <td class="text-right">Rp {{ number_format($item['price'], 0, ',', '.') }}</td>
                <td class="text-center">{{ $item['discount_pct'] }}%</td>
                <td class="text-right">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </td>
      </table>

      <!-- Totals Table -->
      @php
      $subtotal = $quotation->subtotal;
      $discountPct = $quotation->items->pluck('discount_pct')->max();
      $discountAmt = $subtotal - $quotation->grand_total;
      @endphp

      <table class="totals-table">
        <tr>
          <td class="totals-label">Total Price</td>
          <td class="totals-colon">:</td>
          <td class="totals-value">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
        </tr>
        <tr>
          <td class="totals-label">Discount (%)</td>
          <td class="totals-colon">:</td>
          <td class="totals-value">{{ $discountPct ?: '0' }}%</td>
        </tr>
        <tr>
          <td class="totals-label">PPN (11%)</td>
          <td class="totals-colon">:</td>
          <td class="totals-value">Rp {{ number_format($discountAmt, 0, ',', '.') }}</td>
        </tr>
        <tr>
          <td class="totals-label">Grand Total</td>
          <td class="totals-colon">:</td>
          <td class="totals-value">Rp {{ number_format($quotation->grand_total, 0, ',', '.') }}</td>
        </tr>
      </table>

      <!-- Terms & Conditions -->
      <table class="terms-table">
        <!-- Terms Header -->
        <tr>
          <td colspan="3" class="terms-header">TERMS & CONDITIONS</td>
        </tr>

        <!-- Terms Content -->
        <tr>
          <td colspan="3" class="terms-content">

            <!-- NOTE -->
            <div class="terms-section">
              <div class="terms-title">Harga sudah termasuk</div>
              <ul class="terms-list">
                <li>PPN 11%</li>
                <li>Pengiriman dari Guangzhou, China ke Port Jakarta / Surabaya</li>
                <li>Garansi Komponen Mesin 12 bulan sejak BAST ditandatangani (syarat & ketentuan klaim berlaku)</li>
              </ul>

              <div class="terms-title" style="margin-top:6pt">Harga belum termasuk</div>
              <ul class="terms-list">
                <li>Kebutuhan alat & bahan penunjang instalasi di lokasi (kabel power, tandon air, dll)</li>
                <li>Biaya pengiriman dari Port Indonesia ke lokasi Customer</li>
                <li>Akomodasi & transportasi teknisi selama proses instalasi</li>
              </ul>
            </div>

            <!-- Skema Pembayaran -->
            <div class="terms-section">
              <div class="terms-title">Skema Pembayaran</div>

              @php
              $terms = $quotation->paymentTerms ?? collect();
              @endphp

              @if($terms->isNotEmpty())
              <table class="payment-table">
                <thead>
                  <tr>
                    <th>Termin</th>
                    <th style="width:12%; text-align:center">Persentase</th>
                    <th style="width:22%; text-align:right">Nominal</th>
                    <th>Waktu Pembayaran</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($terms->sortBy('term_no') as $term)
                  <tr>
                    <td>
                      Termin {{ $term->term_no ?? ($loop->iteration) }}
                    </td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($term->percentage ?? 0, 2, ',', '.'), '0'),
                      ',') }}%</td>
                    <td class="text-right">Rp {{ number_format((float)($quotation->grand_total * (($term->percentage ??
                      0) / 100)), 0, ',', '.') }}</td>
                    <td>{{ $term->description ?? $term->due_description ?? $term->note ?? '-' }}</td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
              @else
              <table class="payment-table">
                <thead>
                  <tr>
                    <th>Termin</th>
                    <th>Persentase</th>
                    <th>Nominal</th>
                    <th>Waktu Pembayaran</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td></td>
                    <td class="text-center"></td>
                    <td class="text-right"></td>
                    <td></td>
                  </tr>
                </tbody>
              </table>
              @endif

              <p style="margin-top:10px;">
                <i>Rekening Pembayaran akan diinformasikan melalui Invoice/Surat Pengantar Tagihan resmi.
                Setiap pembayaran harap disertai bukti transfer yang dikirimkan kepada kami.</i>
              </p>
            </div>

            <!-- Estimasi Pengiriman -->
            <div class="terms-section">
              <div class="terms-title">Estimasi Pengiriman</div>
              <table class="estimate-table">
                <tr>
                  <td class="est-label">Produksi</td>
                  <td class="est-colon">:</td>
                  <td class="est-value">±60 hari</td>
                </tr>
                <tr>
                  <td class="est-label">Pengiriman (Laut)</td>
                  <td class="est-colon">:</td>
                  <td class="est-value">±15 hari</td>
                </tr>
                <tr>
                  <td class="est-label">Clearance & Bea Cukai</td>
                  <td class="est-colon">:</td>
                  <td class="est-value">±10 hari</td>
                </tr>
              </table>
            </div>

            <!-- Instalasi -->
            <div class="terms-section">
              <div class="terms-title">Instalasi</div>
              <p>
                Persiapan tempat, saluran air, instalasi listrik, tenaga bantu, serta seluruh perlengkapan
                pendukung lainnya (termasuk alat berat & perkakas) disediakan oleh Customer.
                Tim teknisi kami akan melakukan supervisi dan pendampingan hingga proses instalasi
                dan uji coba selesai. Keterlambatan instalasi dikarenakan ketidaksiapan lokasi menjadi
                tanggung jawab Pembeli.
              </p>
            </div>

            <!-- Syarat & Ketentuan -->
            <div class="terms-section">
              <div class="terms-title">Syarat & Ketentuan</div>
              <ul class="terms-list">
                <li>Payment: Pembayaran yang telah diterima tidak dapat dikembalikan dengan alasan apapun. Pembatalan
                  sepihak setelah DP masuk mengakibatkan Pembeli kehilangan hak atas dana tersebut sebagai kompensasi
                  biaya yang telah berjalan (Pasal 1464 KUHP).</li>
                <li>Penyesuaian Harga & Kurs: Harga yang disepakati dapat ditinjau kembali apabila terjadi fluktuasi
                  kurs ekstrem atau perubahan regulasi perpajakan impor sebelum barang dirilis dari pabean. Bill of
                  Lading hanya diterbitkan setelah seluruh kewajiban pembayaran terpenuhi.</li>
                <li>Estimasi pengiriman merupakan proyeksi normal. Keterlambatan akibat faktor di luar kendali
                  (ketersediaan kontainer, cuaca, bea cukai, regulasi ekspor-impor) tidak dapat menjadi dasar klaim
                  penalti.</li>
                <li>Segala perselisihan diselesaikan melalui jalur hukum perdata. Tidak melayani negosiasi melalui pihak
                  ketiga tanpa hubungan hukum resmi.</li>
              </ul>
            </div>

          </td>
        </tr>

        <!-- Signatures -->
        <tr>
          <td colspan="3" class="signature-section">
            <table class="signature-table">
              <tr>
                <!-- Left Signature -->
                <td class="signature-left">
                  <div class="signature-greeting">Created By</div>
                  {{-- <div class="signature-company">
                    {{ optional($user)->company_name ?? 'PT Pandu Mahardika Perdana' }}
                  </div> --}}
                  <div class="signature-company signature-transparent">{{ $quotation->lead->company }}</div>
                  <div class="signature-line"></div>
                  <div class="signature-name">
                    {{ optional($claim->sales)->name ?? 'Sales Representative' }}
                  </div>
                  <div class="signature-role">
                    {{ optional($claim->sales->role)->name ?? 'Machinery Consultant' }}
                  </div>
                </td>

                <!-- Spacer -->
                <td class="signature-spacer"></td>

                <!-- Right Signature -->
                <td class="signature-right">
                  <div class="signature-greeting">Customer</div>
                  <div class="signature-company signature-transparent">{{ $quotation->lead->company }}</div>
                  <div class="signature-line"></div>
                  <div class="signature-name">{{ $quotation->lead->name }}</div>
                  <div class="signature-role">Customer</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

    </div>

    <img src="{{ public_path('assets/images/quotation/footer-slice.png') }}" class="footer-slice" />
  </div>
</body>

</html>