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
    @page { size: A4; margin: 0; }
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
    .header-slice, .footer-slice {
      position: absolute;
      left: 0;
      width: 100%;
    }
    .header-slice { 
      top: 0; 
      /* height: 50mm;  */
    }
    .footer-slice { 
      bottom: 0; 
      height: auto; 
    }

    /* Content Container */
    .content {
      position: absolute;
      top: 20mm;
      bottom: 25mm;
      left: 15mm;
      right: 15mm;
      border: 1px solid #000;
    }

    /* Typography */
    .section-title {
      font-weight: bold;
      font-size: 5pt;
    }

    .section-title-quotation {
      font-weight: bold;
      font-size: 7pt;
      background-color: #cfd2ceff;
    }
    .text-bold { font-weight: bold; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-uppercase { text-transform: uppercase; }

    /* Info Table Styles */
    .info-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
      font-size: 8pt;
    }
    .info-table td {
      /* border: 1px solid #000; */
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
    }
    .info-table .header-row td {
      text-align: center;
      padding: 6pt;
    }
    .info-table .title-cell {
      width: 60%;
    }
    .info-table .quotation-cell, .info-table .sales-cell {
      width: 20%;
    }
    .info-table .customer-cell {
      width: 40%;
      padding: 8pt 3pt;
    }
    .info-table .publisher-cell {
      width: 60%;
      padding: 8pt 3pt;
    }

    /* Customer & Publisher Info */
    .company-name {
      font-weight: bold;
      text-transform: uppercase;
      margin-bottom: 2pt;
    }
    .contact-info {
      margin-bottom: 1pt;
      font-size: 5pt;
    }
    .address-info {
      font-size: 5pt;
    }
    .section-header {
      margin-bottom: 4px;
      padding-bottom: 2pt;
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
    .col-no { width: 5%; }
    .col-machine { width: 20%; }
    .col-spec { width: 20%; }
    .col-unit-price { width: 20%; }
    .col-qty { width: 8%; }
    .col-price { width: 20%; }
    .col-disc { width: 10%; }
    .col-final { width: 22%; }

    /* Totals Table */
    .totals-table {
      width: 100%;
      font-size: 5pt;
      border-collapse: collapse;
      margin-top: 12px;
    }
    .totals-table td {
      padding: 3pt 4pt;
    }
    .totals-label {
      text-align: right;
      width: 75%;
      font-weight: bold;
    }
    .totals-colon {
      width: 2%;
      text-align: center;
      font-weight: bold;
    }
    .totals-value {
      text-align: right;
      width: 15%;
      font-weight: bold;
    }

    /* Terms & Conditions */
    .terms-table {
      width: 100%;
      margin-top: 20px;
      border-collapse: collapse;
    }
    .terms-header {
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
      text-align: center;
      padding: 6pt;
      font-weight: bold;
      font-size: 7pt;
      background-color: #cfd2ceff;
    }
    .terms-content {
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
      padding: 8pt;
      vertical-align: top;
    }
    .terms-section {
      margin-bottom: 8pt;
    }
    .terms-title {
      font-weight: bold;
      margin-bottom: 4pt;
      font-size: 5pt;
    }
    .terms-list {
      margin: 0;
      padding-left: 12pt;
      font-size: 5pt;
      line-height: 1.3;
    }
    .terms-list li {
      margin-bottom: 2pt;
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
      padding: 12pt;
      vertical-align: top;
    }
    .signature-table {
      width: 100%;
      border-collapse: collapse;
    }
    .signature-left, .signature-right {
      width: 48%;
      vertical-align: top;
      text-align: left;
    }
    .signature-spacer {
      width: 4%;
    }
    .signature-greeting {
      font-size: 5pt;
      margin-bottom: 8pt;
    }
    .signature-company {
      font-size: 6pt;
      margin-bottom: 12pt;
      font-weight: bold;
    }
    .signature-line {
      margin-top: 20pt;
      border-bottom: 1px solid #000;
      width: 80%;
      margin-bottom: 4pt;
    }
    .signature-name {
      font-size: 5pt;
      font-weight: bold;
      text-transform: uppercase;
    }
    .signature-role {
      font-size: 5pt;
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
            <div class="section-title">Customer</div>
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
              <div class="section-title section-header">Customer Company</div>
              <div class="company-name">{{ $quotation->lead->company }}</div>
              <div class="contact-info">{{ $quotation->lead->name }}</div>
              <div class="contact-info">{{ $quotation->lead->phone }}</div>
              <div class="address-info">{{ $quotation->lead->company_address ?? 'Jalan Bangka, Banjarmasin Kota no 14 b, Banjarmasin' }}</div>
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
          <td class="totals-label">Discount (Rp)</td>
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
            <!-- Price Includes -->
            <div class="terms-section">
              <div class="terms-title">A. Terms and Conditions</div>
              <ul class="terms-list">
                <li>01. Goods will be delivered 90 weeks after down payment is received</li>
                <li>02. Down payment is required before production.</li>
                <li>03. The above quoted prices are valid for 2 weeks from the date of the quotation.</li>
                <li>04. Down payment or booking fee could not be refunded, except for installment by authorized bank chosen by Daxtro</li>
                <li>05. Price includes installation service and technician labor cost (Exception for FOB Price)</li>
                <li>06. Full warranty coverage for 1 (one) year.</li>
                <li>07. Price covers after-sales service and technical support.</li>
                <li>08. After-sales accommodations are not included</li>
                <li>09. Operator training for customer’s employees.</li>
                <li>10. Price excludes supporting tools and materials required for installation (e.g. power cables)</li>
                <li>11. Limited warranty of 12 (twelve) months starting from the date of official handover (BAST)</li>
              </ul>
            </div>

            <!-- Payment Terms -->
            <div class="terms-section">
              <div class="terms-title">B. Payment Terms</div>
              <ul class="terms-list">
                @if($quotation->paymentTerms && $quotation->paymentTerms->count() > 0)
                  @foreach($quotation->paymentTerms as $term)
                    <li>Payment {{ $term->term_no }}: {{ $term->percentage }}% {{ $term->description }}</li>
                  @endforeach
                @else
                  <li>Standard payment terms apply</li>
                @endif
              </ul>
            </div>

            <!-- Production Timeline -->
            <div class="terms-section">
              <div class="terms-title">C. Production Timeline</div>
              <ul class="terms-list">
                <li>Machine production: 60 days</li>
                <li>International shipping: 20 days</li>
                <li>Customs clearance: 10 days</li>
                <li>Installation Requirements: Site preparation including structure, water lines, and electrical systems must be provided by the customer</li>
              </ul>
            </div>

            <!-- Warranty & Bank Info (Two Columns) -->
            <div class="two-column">
              <div class="column-left">
                <div class="terms-title">D. Installation Requirements</div>
                <ul class="terms-list">
                  <li>01. Customer cooperativeness for sending requirements document is compulsory. Postponed project/installation time due to lack of document by customers is not Daxtro's concern</li>
                </ul>
              </div>

              <div class="column-right">
                <div class="terms-title">E. Bank Information:</div>
                <ul class="terms-list">
                  <li>All payments must be transferred to the official company bank account as stated in the attached invoice or billing letter</li>
                  @php
                    $quoteExpiry = $quotation->created_at->copy()->addDays(15);
                    $firstProforma = $quotation->proformas->sortBy('issued_at')->first();
                    $proformaExpiry = $firstProforma
                        ? $firstProforma->issued_at->copy()->addDays(30)
                        : $quotation->created_at->copy()->addDays(30);
                  @endphp
                  <li>Quotation valid until {{ $quoteExpiry->format('d M Y') }} (15 days from issue)</li>
                  <li>Proforma invoice valid until {{ $proformaExpiry->format('d M Y') }} (30 days from issue)</li>
                </ul>
              </div>
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