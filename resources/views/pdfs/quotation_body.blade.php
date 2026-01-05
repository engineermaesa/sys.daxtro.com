{{-- resources/views/pdfs/quotation_body.blade.php --}}
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

    @page { size: A4; margin: 0 }
    body {
      font-family: 'Montserrat', sans-serif;
      font-size: 8pt;
      margin: 0;
    }

    table.items td {
      border-bottom: 0.5pt solid #d6d6d6;
      padding: 6pt 5pt;
      vertical-align: top;
    }

    table.items thead th {
      border-bottom: 1pt solid #000;
      font-weight: bold;
      text-align: center;
      padding: 6pt 5pt;
    }

    .section-title {
      font-weight: bold;
      font-size: 8pt;
    }
    .info-table td {
      font-weight: normal;
      padding: 2pt 3pt;
    }


    .page-container { position: relative; width: 210mm; height: 297mm; }

    .header-slice, .footer-slice {
      position: absolute;
      left: 0;
      width: 100%;
    }
    .header-slice { top: 0; height: 50mm; }
    .footer-slice { bottom: 0; height: auto; }

    .content {
      position: absolute;
      top: 60mm;
      bottom: 25mm;
      left: 15mm;
      right: 15mm;
    }

    table.info-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
      font-size: 8pt;
    }
    .info-table td {
      vertical-align: top;
      padding: 3pt;
    }
    .info-table .label {
      font-weight: bold;
      width: 35%;
    }

    .text-bold { font-weight: bold; }

    table.items {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      font-size: 8pt;
    }
    table.items thead th {
      font-weight: bold;
      text-align: center;
      padding: 6pt 5pt;
    }
    table.items td {
      border-bottom: 1px solid #8d8d8d !important;
      padding: 6pt 5pt;
      vertical-align: top;
    }

    .text-center { text-align: center; }
    .text-right  { text-align: right; }

    .spec-label {
      font-weight: bold;
    }
    .spec-red {
      color: #000;
    }

    table.totals {
      width: 100%;
      font-size: 8pt;
      border-collapse: collapse;
      margin-top: 12px;
    }
    table.totals td {
      padding: 3pt 4pt;
    }
    .totals .label {
      text-align: right;
      width: 75%;
    }
    .totals .value {
      text-align: right;
      width: 25%;
    }
    .totals tr:last-child .label,
    .totals tr:last-child .value {
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="page-container">
    <img src="{{ public_path('assets/images/quotation/header-slice.png') }}" class="header-slice" />

    <div class="content">

      {{-- Info Section --}}
      <table class="info-table" style="width: 100%; margin-bottom: 16px;">
        <tr>
          <td style="width: 45%; vertical-align: top;">
            <div class="section-title" style="margin-bottom: 2px">Electronics Quotation</div>
              <table style="width: 100%; border-spacing: 0; font-size: 8pt; line-height: 1.2;">
                <tr>
                  <td style="width: 35%; padding: 0 2pt;">Quotation Number</td>
                  <td style="padding: 0 2pt; width: 2%;">:</td>
                  <td style="padding: 0 2pt;">{{ $quotation->quotation_no }}</td>
                </tr>
                <tr>
                  <td style="padding: 0 2pt;">Quotation Date</td>
                  <td style="padding: 0 2pt; width: 2%;">:</td>
                  <td style="padding: 0 2pt;">{{ $quotation->created_at->format('d M Y') }}</td>
                </tr>
                <tr>
                  <td style="padding: 0 2pt;">Quotation Due</td>
                  <td style="padding: 0 2pt; width: 2%;">:</td>
                  <td style="padding: 0 2pt;">
                    {{ $quotation->expiry_date->format('d M Y') }}
                    ({{ round($quotation->created_at->diffInDays($quotation->expiry_date)) }} Days)
                  </td>
                </tr>
              </table>
          </td>
          <td style="width: 5%;"></td> <!-- spacing between columns -->
          <td style="width: 45%; vertical-align: top;">
            <div class="section-title" style="margin-bottom: 2px">Office</div>
            Komplek Harmoni Plaza<br>
            Blok A No.16-17, Jl. Suryopranoto<br>
            Jakarta (10130)
          </td>
        </tr>
        <tr>
          <td style="vertical-align: top;">
            <div class="section-title">Customer Identity</div>
            <div style="border-top: 1px solid #000; margin-top: 2pt; padding-top: 2pt;">
              {{ $quotation->lead->name }}
            </div>
          </td>
          <td></td>
          <td style="vertical-align: top;">
            <div class="section-title">No Telephone</div>
            <div style="border-top: 1px solid #000; margin-top: 2pt; padding-top: 2pt;">
              {{ $quotation->lead->phone ?? '0821115236938' }}<br>
              {{ $quotation->lead->address ?? 'Jalan Bangka, Banjarmasin Kota no 14 b, Banjarmasin' }}
            </div>
          </td>
        </tr>
      </table>

      {{-- Items Table --}}
      <table class="items">
          <thead>
              <tr>
                  <th width="5%">No</th>
                  <th width="25%">Machine</th>
                  <th width="40%">Specification</th>
                  <th width="8%">Qty</th>
                  <th width="22%">Amount</th>
              </tr>
          </thead>
          <tbody>
              @php
                  $pdfItems = [];
                  $itemCounter = 1;
                  
                  foreach($quotation->items->where('is_visible_pdf', true) as $item) {
                      $pdfItem = [
                          'description' => $item->description,
                          'product' => $item->product,
                          'qty' => $item->qty,
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
                      <td class="text-center">{{ $item['qty'] }}</td>
                      <td class="text-right">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                  </tr>
              @endforeach
          </tbody>
      </table>

      {{-- Totals --}}
      @php
        $subtotal = $quotation->subtotal;
        $discountPct = $quotation->items->pluck('discount_pct')->max();
        $discountAmt = $subtotal - $quotation->grand_total;
      @endphp

      <table class="totals">
        <tr>
          <td class="label"><b>Total Price</b></td>
          <td class="label" style="width: 2%;">:</td>
          <td class="value" style="width: 15%;"><b>Rp {{ number_format($subtotal, 0, ',', '.') }}</b></td>
        </tr>
        <tr>
          <td class="label"><b>Discount (%)</b></td>
          <td class="label" style="width: 2%;">:</td>
          <td class="value" style="width: 15%;"><b>{{ $discountPct ?: '0' }}%</b></td>
        </tr>
        <tr>
          <td class="label"><b>Discount (Rp)</b></td>
          <td class="label" style="width: 2%;">:</td>
          <td class="value" style="width: 15%;"><b>Rp {{ number_format($discountAmt, 0, ',', '.') }}</b></td>
        </tr>
        <tr>
          <td class="label"><b>Grand Total</b></td>
          <td class="label" style="width: 2%;">:</td>
          <td class="value" style="width: 15%;"><b>Rp {{ number_format($quotation->grand_total, 0, ',', '.') }}</b></td>
        </tr>
        <tr>
          <td colspan="3" style="border-bottom: 1pt solid #000; padding-top: 6pt;"></td>
        </tr>1