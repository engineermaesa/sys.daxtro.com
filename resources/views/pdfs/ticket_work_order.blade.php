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
        <td class="value">{{ optional($workOrder->visit->scheduled_at ?? null)->format('m/d/Y') ?? '-' }}</td>
      </tr>
    </table>

    <div class="section-title">Deskripsi Keluhan &amp; Problem</div>
    <p class="description">{{ $ticket->description ?: '-' }}</p>

    <div class="section-title">Estimasi Sparepart &amp; Material Needed</div>
    <table class="items">
      <thead>
        <tr>
          <th width="20%">Part Code</th>
          <th width="45%">Nama Item</th>
          <th width="15%">Qty</th>
          <th width="20%">Satuan</th>
        </tr>
      </thead>
      <tbody>
        @forelse($partsEstimated as $part)
        <tr>
          <td>{{ $part->sparepart->part_number ?? '-' }}</td>
          <td>{{ $part->sparepart->name ?? '-' }}</td>
          <td>{{ rtrim(rtrim(number_format((float) $part->qty, 2, '.', ''), '0'), '.') }}</td>
          <td>{{ ucfirst($part->unit ?? '-') }}</td>
        </tr>
        @empty
        <tr>
          <td colspan="4" class="text-right" style="text-align:center;">Tidak ada estimasi sparepart</td>
        </tr>
        @endforelse
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
        @foreach($totalByCurrency as $currency => $sum)
        <tr class="total-row">
          @if($loop->first)
          <td rowspan="{{ $totalByCurrency->count() }}">TOTAL</td>
          @endif
          <td class="text-right">{{ number_format($sum, 0, ',', '.') }}</td>
          <td>{{ $currency }}</td>
          <td></td>
        </tr>
        @endforeach
        @if($hasForeign)
        <tr class="total-row">
          <td>TOTAL (IDR)</td>
          <td class="text-right">{{ number_format($totalIdr, 0, ',', '.') }}</td>
          <td>IDR</td>
          <td></td>
        </tr>
        @endif
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
