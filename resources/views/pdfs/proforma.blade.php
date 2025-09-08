<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 0; padding: 20px; }
        .header { display: flex; align-items: center; margin-bottom: 20px; }
        .logo { width: 120px; }
        .company-info { margin-left: 20px; }
        .company-info h4 { margin: 0 0 5px 0; font-size: 16px; }
        .company-info p { margin: 0; font-size: 12px; }

        h3.title { text-align: center; margin: 30px 0 10px; }
        p { margin: 2px 0; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; }
        th { background: #eee; text-align: left; }
        .no-border td { border: none; padding: 3px 6px; }
        .right { text-align: right; }

        .payment-instructions {
            margin-top: 40px;
            border: 1px solid #000;
            padding: 10px;
            background-color: #f9f9f9;
        }

        .payment-instructions h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="header">
    <img src="{{ public_path('assets/images/logo.png') }}" class="logo" alt="Logo">
    <div class="company-info">
        <h4>PT. Daxtro Teknologi Indonesia</h4>
        <p>Jl. Teknologi No. 88, Jakarta Selatan, Indonesia</p>
        <p>Telp: (021) 123-45678 | Email: info@daxtro.co.id</p>
    </div>
</div>

<div style="display: flex; justify-content: space-between; margin-top: 30px; margin-bottom: 10px;">
    <h3 style="margin: 0;">PROFORMA INVOICE</h3>
    <div style="text-align: right; font-size: 12px;">
        <div><strong>No:</strong> {{ $proforma->proforma_no }}</div>
        <div><strong>Date:</strong> {{ $proforma->issued_at?->format('d/m/Y') }}</div>
    </div>
</div>

<p><strong>Customer:</strong> {{ $quotation->lead->name ?? '-' }}</p>

<table>
    <thead>
        <tr>
            <th>Description</th>
            <th class="right">Qty</th>
            <th class="right">Unit Price</th>
            <th class="right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($quotation->items as $item)
        <tr>
            <td>{{ $item->description }}</td>
            <td class="right">{{ $item->qty }}</td>
            <td class="right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
            <td class="right">{{ number_format($item->line_total, 0, ',', '.') }}</td>
        </tr>
        @endforeach
        @php
            $label = 'Amount';
            if ($proforma->proforma_type === 'booking_fee') {
                $label = 'Booking Fee';
            } elseif ($proforma->proforma_type === 'down_payment' || $proforma->proforma_type === 'term_payment') {
                // find matching term percentage if exists
                $term = $quotation->paymentTerms->firstWhere('term_no', $proforma->term_no);
                $percentage = $term?->percentage;
                $label = $proforma->proforma_type === 'down_payment'
                    ? 'Down Payment'
                    : 'Term Payment';
                if ($percentage) {
                    $label .= ' (' . rtrim(rtrim(number_format($percentage, 2, ',', '.'), '0'), ',') . '%)';
                }
            }
        @endphp
        <tr>
            <td colspan="3" class="right"><strong>{{ $label }}</strong></td>
            <td class="right"><strong>{{ number_format($proforma->amount, 0, ',', '.') }}</strong></td>
        </tr>
    </tbody>
</table>

<div class="payment-instructions">
    <h4>Payment Instructions</h4>
    <p>Please make the transfer to the following account:</p>
    <p><strong>Bank:</strong> BCA (Bank Central Asia)</p>
    <p><strong>Account Number:</strong> 123-456-7890</p>
    <p><strong>Account Name:</strong> PT. Daxtro Teknologi Indonesia</p>
    <p><strong>Branch:</strong> Jakarta Selatan</p>
</div>

<p style="margin-top: 30px;">Thank you for your business.</p>

</body>
</html>
