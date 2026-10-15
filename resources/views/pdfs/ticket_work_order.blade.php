<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <style>
    @page {
      size: 210mm 297mm;
      margin: 0;
    }

    body {
      margin: 0;
      font-family: 'DejaVu Sans', sans-serif;
      font-size: 10pt;
      line-height: 1.4;
      color: #1E1E1E;
    }

    .content {
      padding: 15mm 15mm;
    }

    h1 {
      text-align: center;
      font-size: 16pt;
      font-weight: bold;
      color: #115640;
      margin: 0 0 4px;
    }

    .subtitle {
      text-align: center;
      font-size: 10pt;
      font-style: italic;
      color: #757575;
      margin: 0 0 15px;
    }

    .meta {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }

    .meta td {
      padding: 3px 4px;
      vertical-align: top;
    }

    .meta td.label {
      font-weight: bold;
      width: 22%;
    }

    .meta td.colon {
      width: 2%;
    }

    .meta td.value {
      width: 26%;
    }

    .section-title {
      background: #115640;
      color: #fff;
      font-weight: bold;
      padding: 5px 8px;
      font-size: 9pt;
      text-transform: uppercase;
      margin: 12px 0 6px;
    }

    .description {
      padding: 0 2px;
      white-space: pre-line;
    }

    table.items {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }

    table.items th,
    table.items td {
      border: 1px solid #D9D9D9;
      padding: 4px 6px;
      font-size: 9pt;
    }

    table.items th {
      background: #E6F4EF;
      color: #115640;
      font-weight: bold;
      text-align: left;
    }

    .text-right {
      text-align: right;
    }

    .total-row td {
      font-weight: bold;
      background: #F5F5F5;
      vertical-align: middle;
    }

    .signatures {
      width: 100%;
      margin-top: 40px;
    }

    .signatures td {
      width: 50%;
      text-align: center;
      vertical-align: top;
      padding-top: 60px;
    }

    .signatures .sign-label {
      padding-top: 0;
      font-weight: bold;
    }

    .signatures .sign-name {
      border-top: 1px solid #1E1E1E;
      padding-top: 4px;
      display: inline-block;
      min-width: 180px;
    }
  </style>
</head>

<body>

  <div class="content">
    @php
      $customer = $ticket->customer;
      $picContact = trim(($customer->pic_name ?? '') . (($customer->phone ?? '') ? ' (' . $customer->phone . ')' : ''));
      $totalByCurrency = $costs->groupBy(fn ($cost) => strtoupper($cost->currency ?? 'IDR'))
          ->map(fn ($group) => $group->sum(fn ($cost) => (float) $cost->amount));
      $totalIdr = $costs->sum(fn ($cost) => $cost->amount_idr);
      $hasForeign = $costs->contains(fn ($cost) => strtolower($cost->currency ?? 'idr') !== 'idr');

      // Final Cost = total category + total sparepart (IDR), ditampilkan juga dalam mata uang pilihan tiket
      $totalSparepartIdr = $partsEstimated->sum(fn ($part) => (float) $part->qty * (float) $part->unit_price);
      $finalCostIdr = $totalIdr + $totalSparepartIdr;
      $displayCurrency = strtolower($ticket->cost_display_currency ?? 'idr');
      $idrPerUnit = ['idr' => 1, 'usd' => (float) ($rates['idr_per_usd'] ?? 0), 'cny' => (float) ($rates['idr_per_cny'] ?? 0)];
      $fmtCurrency = function (float $idr, string $cur) use ($idrPerUnit) {
          $rate = $idrPerUnit[$cur] ?? 1;
          if ($cur === 'idr' || $rate <= 0) {
              return 'Rp ' . number_format($idr, 0, ',', '.');
          }
          return ($cur === 'usd' ? 'USD ' : 'CNY ') . number_format($idr / $rate, 2, ',', '.');
      };
      $fmtNum = function (float $idr, string $cur) use ($idrPerUnit) {
          $rate = $idrPerUnit[$cur] ?? 1;
          if ($cur === 'idr' || $rate <= 0) {
              return number_format($idr, 0, ',', '.');
          }
          return number_format($idr / $rate, 2, ',', '.');
      };
    @endphp

    <h1>WORK ORDER / SURAT TUGAS SERVIS</h1>
    <p class="subtitle">DAXTRO AFTERSALES &amp; TECHNICAL SERVICES</p>

    <table class="meta">
      <tr>
        <td class="label">No. Work Order:</td>
        <td class="colon">:</td>
        <td class="value">{{ $workOrder->wo_number ?? '-' }}</td>
        <td class="label">Tanggal WO:</td>
        <td class="colon">:</td>
        <td class="value">{{ optional($workOrder->issued_at)->format('m/d/Y') ?? '-' }}</td>
      </tr>
      <tr>
        <td class="label">No. Ticket Reference:</td>
        <td class="colon">:</td>
        <td class="value">{{ $ticket->ticket_code ?? '-' }}</td>
        <td class="label">Prioritas:</td>
        <td class="colon">:</td>
        <td class="value">{{ strtoupper($ticket->priority ?? '-') }}</td>
      </tr>
      <tr>
        <td class="label">Nama Customer:</td>
        <td class="colon">:</td>
        <td class="value">{{ $customer->name ?? '-' }}</td>
        <td class="label">Teknisi Assigned:</td>
        <td class="colon">:</td>
        <td class="value">{{ $ticket->technician->name ?? '-' }}</td>
      </tr>
      <tr>
        <td class="label">Alamat Customer:</td>
        <td class="colon">:</td>
        <td class="value">{{ $customer->address ?? '-' }}</td>
        <td class="label">Area / Wilayah:</td>
        <td class="colon">:</td>
        <td class="value">{{ $customer->region->name ?? '-' }}</td>
      </tr>
      <tr>
        <td class="label">PIC Contact:</td>
        <td class="colon">:</td>
        <td class="value">{{ $picContact ?: '-' }}</td>
        <td class="label">Jadwal Visit:</td>
        <td class="colon">:</td>
        <td class="value">{{ optional($ticket->visits->sortByDesc('scheduled_at')->first()?->scheduled_at ?? $workOrder->visit->scheduled_at ?? null)->format('d/m/Y H:i') ?? '-' }}</td>
      </tr>
    </table>

    <div class="section-title">Deskripsi Keluhan &amp; Problem</div>
    <p class="description">{{ $ticket->description ?: '-' }}</p>

    <div class="section-title">Estimasi Sparepart &amp; Material Needed</div>
    <table class="items">
      <thead>
        <tr>
          <th width="16%">Part Code</th>
          <th width="30%">Nama Item</th>
          <th width="9%">Qty</th>
          <th width="13%">Satuan</th>
          <th width="16%">Harga Satuan</th>
          <th width="16%">Total</th>
        </tr>
      </thead>
      <tbody>
        @forelse($partsEstimated as $part)
        <tr>
          <td>{{ $part->sparepart->part_number ?? '-' }}</td>
          <td>{{ $part->sparepart->name ?? '-' }}</td>
          <td>{{ rtrim(rtrim(number_format((float) $part->qty, 2, '.', ''), '0'), '.') }}</td>
          <td>{{ ucfirst($part->unit ?? '-') }}</td>
          <td class="text-right">{{ (float) $part->unit_price > 0 ? $fmtNum((float) $part->unit_price, 'idr') : '-' }}</td>
          <td class="text-right">{{ (float) $part->unit_price > 0 ? $fmtNum((float) $part->qty * (float) $part->unit_price, 'idr') : '-' }}</td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="text-right" style="text-align:center;">Tidak ada estimasi sparepart</td>
        </tr>
        @endforelse
        @if($partsEstimated->count())
        <tr class="total-row">
          <td colspan="5">TOTAL SPAREPART ({{ strtoupper($displayCurrency) }})</td>
          <td class="text-right">{{ $fmtNum($totalSparepartIdr, $displayCurrency) }}</td>
        </tr>
        @endif
      </tbody>
    </table>

    <div class="section-title">Estimasi Cost</div>
    <table class="items">
      <thead>
        <tr>
          <th width="30%">Category</th>
          <th width="25%">Amount</th>
          <th width="20%">Unit of Material</th>
          <th width="25%">Remarks</th>
        </tr>
      </thead>
      <tbody>
        @forelse($costs as $cost)
        <tr>
          <td>{{ ucfirst($cost->category ?? '-') }}</td>
          <td class="text-right">{{ number_format((float) $cost->amount, 0, ',', '.') }}</td>
          <td>{{ strtoupper($cost->currency ?? '-') }}</td>
          <td>{{ $cost->remarks ?: '-' }}</td>
        </tr>
        @empty
        <tr>
          <td colspan="4" style="text-align:center;">Tidak ada estimasi cost</td>
        </tr>
        @endforelse
        <tr class="total-row">
          <td>TOTAL SPAREPART</td>
          <td class="text-right">{{ $fmtNum($totalSparepartIdr, $displayCurrency) }}</td>
          <td>{{ strtoupper($displayCurrency) }}</td>
          <td></td>
        </tr>
        <tr class="total-row">
          <td>TOTAL CATEGORY</td>
          <td class="text-right">{{ $fmtNum($totalIdr, $displayCurrency) }}</td>
          <td>{{ strtoupper($displayCurrency) }}</td>
          <td></td>
        </tr>
        <tr class="total-row">
          <td><strong>FINAL COST</strong></td>
          <td class="text-right"><strong>{{ $fmtNum($finalCostIdr, $displayCurrency) }}</strong></td>
          <td>{{ strtoupper($displayCurrency) }}</td>
          <td>Total category + total sparepart</td>
        </tr>
      </tbody>
    </table>

    <table class="signatures">
      <tr>
        <td class="sign-label">Teknisi Pelaksana</td>
        <td class="sign-label">Customer / Disetujui</td>
      </tr>
      <tr>
        <td><span class="sign-name">( {{ $ticket->technician->name ?? '-' }} )</span></td>
        <td><span class="sign-name">( {{ $customer->name ?? '-' }} )</span></td>
      </tr>
    </table>
  </div>
</body>

</html>
