{{-- resources/views/pdfs/quotation.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    @page { size: 210mm 297mm; margin: 0; }
    body {
      margin: 0;
      font-family: 'DejaVu Sans', sans-serif;
      font-size: 10pt;
      line-height: 1.4;
    }
    .bg {
      position: fixed; z-index: -1;
      top: 0; left: 0; width: 210mm; height: 297mm;
      background: url('{{ public_path('assets/images/background-hd.png') }}') no-repeat;
      background-size: cover;
    }
    .content {
      padding: 25mm 20mm;
    }
    h1 {
      text-align: center;
      font-family: 'Times New Roman', serif;
      font-size: 20pt;
      font-weight: bold;
    }
    .subtitle {
      text-align: center;
      font-size: 12pt;
      font-style: italic;
      color: #d4a307;
      margin: 0 0 15px;
    }
    .meta {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }
    .meta td {
      padding: 2px 4px;
      vertical-align: top;
    }
    .meta strong {
      display: inline-block;
      width: 60px;
    }
    .items {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }
    .items th,
    .items td {
      border: 1px solid #000;
      padding: 4px 6px;
    }
    .items th {
      background: #1e472b;
      color: #fff;
      font-weight: bold;
      text-align: center;
    }
    .text-center { text-align: center; }
    .text-right  { text-align: right; }
    .label      { font-weight: bold; }
  </style>
</head>
<body>

  <div class="bg"></div>

  <div class="content">
    @php
      $firstItem     = $quotation->items->first();
      $hasDiscount   = $quotation->items->contains(fn($item) => $item->discount_pct > 0);
      $sumPre        = $quotation->items->sum(fn($item) => $item->unit_price * $item->qty);
      $sumDisc       = $quotation->items->sum(fn($item) => $item->unit_price * $item->qty * ($item->discount_pct/100));
      $netTotal      = $sumPre - $sumDisc;
    @endphp

    {{-- KEEP THIS EXACT LINE --}}
    <h1 style="margin: 100px 0 4px;">
      Surat Penawaran Cold Storage <u>{{ $firstItem?->product->name ?? '-' }}</u>
    </h1>

    <p class="subtitle">“Best Deal”</p>

    <table class="meta">
      <tr>
        <td><strong>Kepada</strong></td>
        <td>:</td>
        <td>{{ $quotation->lead->name ?? '-' }}</td>
        <td><strong>No</strong></td>
        <td>:</td>
        <td>{{ $quotation->quotation_no ?? '-' }}</td>
      </tr>
      <tr>
        <td><strong>Alamat</strong></td>
        <td>:</td>
        <td>{{ $quotation->lead->address ?? '-' }}</td>
        <td><strong>Tanggal</strong></td>
        <td>:</td>
        <td>{{ optional($quotation->created_at)->format('d M Y') ?? '-' }}</td>
      </tr>
      <tr>
        <td><strong>Kantor</strong></td>
        <td>:</td>
        <td>{{ $quotation->lead->company ?? '-' }}</td>
        <td><strong>Masa Berlaku</strong></td>
        <td>:</td>
        <td>
          {{ $quotation->expiry_date
              ? \Carbon\Carbon::parse($quotation->expiry_date)->format('d M Y')
              : '-' }}
        </td>
      </tr>
    </table>

    <table class="items">
      <thead>
        <tr>
          <th width="5%">No</th>
          <th width="30%">Produk</th>
          <th width="15%">Harga Unit (IDR)</th>
          <th width="8%">Qty</th>
          <th width="15%">Sub Total</th>
          @if($hasDiscount)
            <th width="12%">Diskon</th>
          @endif
          <th width="15%">Grand Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach($quotation->items as $i => $item)
          @php
            $subtotal   = $item->unit_price * $item->qty;
            $discAmount = $subtotal * ($item->discount_pct/100);
            $grandTotal = $subtotal - $discAmount;
          @endphp
          <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td>
              @if($item->product)
                <strong>{{ $item->product->name }}</strong>
                @if($item->product->description && $item->product->description !== '-')
                  <br>
                  <span style="font-size:9pt; line-height:1.2;">
                    {!! nl2br(e($item->product->description)) !!}
                  </span>
                @endif
                @if($item->description && $item->description !== '-')
                  <hr style="margin:4px 0;">
                  <span style="font-size:9pt; line-height:1.2;">
                    {!! nl2br(e($item->description)) !!}
                  </span>
                @endif
              @elseif($item->description && $item->description !== '-')
                <strong>{{ $item->description }}</strong>
              @endif
            </td>
            <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
            <td class="text-center">{{ $item->qty }}</td>
            <td class="text-right">Rp {{ number_format($subtotal,    0, ',', '.') }}</td>
            @if($hasDiscount)
              <td class="text-right">
                {{ $item->discount_pct ? $item->discount_pct.'%' : '-' }}
              </td>
            @endif
            <td class="text-right">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        @if($sumDisc > 0)
          <tr>
            <td colspan="{{ $hasDiscount ? 5 : 4 }}" class="text-right label">Sub Total</td>
            <td class="text-right" colspan="2">Rp {{ number_format($sumPre, 0, ',', '.') }}</td>
          </tr>
          <tr>
            <td colspan="{{ $hasDiscount ? 5 : 4 }}" class="text-right label">Diskon</td>
            <td class="text-right" colspan="2">- Rp {{ number_format($sumDisc, 0, ',', '.') }}</td>
          </tr>
        @endif
        <tr>
          <td colspan="{{ $hasDiscount ? 5 : 4 }}" class="text-right label">Grand Total</td>
          <td class="text-right" colspan="2">
            <strong>Rp {{ number_format($netTotal, 0, ',', '.') }}</strong>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</body>
</html>
