<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo {
            width: 120px;
        }

        /* .company-info {
            margin-left: 20px;
        } */

        .company-info h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }

        .company-info p {
            margin: 0;
            font-size: 12px;
        }

        h3.title {
            text-align: center;
            margin: 30px 0 10px;
        }

        p {
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
        }

        th {
            background: #eee;
            text-align: left;
        }

        .no-border td {
            border: none;
            padding: 3px 6px;
        }

        .right {
            text-align: right;
        }

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

        .page-break {
            page-break-before: always;
            break-before: page;
        }

        tr {
            page-break-inside: avoid;
        }

        /* ================= META (FIX ALIGN RIGHT + SEJAJAR :) ================= */
        .meta {
            text-align: right;
            font-size: 12px;
        }

        .meta-item {
            margin-bottom: 4px;
        }

        .meta-item .label {
            display: inline-block;
            width: 90px;
            /* kunci biar sejajar */
            font-weight: bold;
            text-align: left;
        }

        .meta-item .colon {
            display: inline-block;
            width: 10px;
            text-align: center;
        }

        .meta-item .value {
            display: inline-block;
            min-width: 120px;
            text-align: left;
        }
    </style>
</head>

<body>


    <div class="header">
        <img src="{{ public_path('assets/images/logo.png') }}" class="logo" alt="Logo">
        <div class="company-info"> <br>
            <h4>PT. PANDU NARADIPTA DANENDRA</h4>
            <p>Komplek Harmoni Plaza Blok A No. 16-17, Jl. Suryopranoto, Petojo Utara, Gambir, Jakarta Pusat</p>
            <p>Telp: (021) 22066090 | Email: info@daxtro.com</p>
        </div>
    </div>

    <!-- HEADER TITLE + META -->
    <div style="margin-top: 30px;">

        <div style="float: left;">
            <h3 style="margin: 0;">INVOICE</h3>
        </div> <br>

        <div class="meta" style="float: right;">
            <div class="meta-item">
                <span class="label">No</span>
                <span class="colon">:</span>
                <span class="value">{{ $invoice->invoice_no }}</span>
            </div>
            <div class="meta-item">
                <span class="label">Tanggal</span>
                <span class="colon">:</span>
                <span class="value">{{ $invoice->issued_at ?
                    \Illuminate\Support\Carbon::parse($invoice->issued_at)->format('d/m/Y') : '-'
                    }}</span>
            </div>
        </div>

        <div style="clear: both;"></div>

    </div>



    <p><strong>Kepada:</strong> {{ $quotation->lead->name ?? '-' }}</p>

    <table>
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th class="right">Qty</th>
                <th class="right">Harga Unit</th>
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
            if ($invoice->invoice_type === 'booking_fee') {
            $label = 'Booking Fee';
            } elseif (in_array($invoice->invoice_type, ['down_payment', 'final'], true)) {
            $term = $quotation->paymentTerms->firstWhere('term_no', $invoice->proforma->term_no);
            $percentage = $term?->percentage;
            $termDesc = $term?->description;

            if ($termDesc) {
            $label = 'Jumlah Terbayar / ' . $termDesc;
            } else {
            $label = $invoice->invoice_type === 'down_payment' ? 'Down Payment' : 'Final Payment';
            }

            if ($percentage) {
            $label .= ' (' . rtrim(rtrim(number_format($percentage, 2, ',', '.'), '0'), ',') . '%)';
            }
            }
            @endphp
            <tr>
                <td colspan="3" class="right"><strong>{{ $label }}</strong></td>
                <td class="right"><strong>{{ number_format($invoice->amount, 0, ',', '.') }}</strong></td>
            </tr>

            @php
                $paidConfirmations = \App\Models\Orders\PaymentConfirmation::whereHas('proforma', function($q) use ($quotation) {
                    $q->where('quotation_id', $quotation->id);
                })->whereNotNull('confirmed_at')->sum('amount');

                $invoicePayments = \App\Models\Orders\InvoicePayment::whereHas('invoice', function($q) use ($quotation) {
                    $q->whereHas('proforma', function($q2) use ($quotation) {
                        $q2->where('quotation_id', $quotation->id);
                    });
                })->sum('amount');

                $paidTotal = ($paidConfirmations ?? 0) + ($invoicePayments ?? 0);
                $grand = $quotation->grand_total ?? $quotation->items->sum('line_total');
                $remaining = $grand - $paidTotal;
                if ($remaining < 0) {
                    $remaining = 0;
                }
            @endphp
            <tr>
                <td colspan="3" class="right"><strong>Sisa Tagihan</strong></td>
                <td class="right">
                    <strong>{{ number_format($remaining, 0, ',', '.') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="payment-instructions">
        <h4>Instruksi Pembayaran</h4>
        <p>Invoice ini telah dibayar dan dikonfirmasi. Tidak diperlukan tindakan lebih lanjut.</p>
        <p>Jika Anda memiliki pertanyaan, silakan hubungi tim keuangan kami di finance@daxtro.com</p>
    </div>

    <div class="payment-instructions page-break" style="margin-top:20px;">
        <h4>SYARAT & KETENTUAN PEMBAYARAN (IMPORTANT NOTICE)</h4>

        <p><strong>1. Sifat Pembayaran Uang Muka (DP):</strong></p>
        <p>- Seluruh pembayaran Uang Muka (Down Payment) yang diterima oleh PT Pandu Mahardika Perdana (Daxtro) bersifat
            <strong>Non-Refundable (Tidak Dapat Dikembalikan)</strong>.
        </p>
        <p>- Dana DP dianggap sebagai komitmen tanda jadi pengadaan dan akan langsung dialokasikan untuk biaya material,
            alokasi slot produksi di pabrik, serta biaya administrasi ekspor-impor.</p>

        <p style="margin-top:10px;"><strong>2. Pembatalan Sepihak:</strong></p>
        <p>- Apabila Pembeli melakukan pembatalan pesanan secara sepihak setelah dana DP diterima dan/atau proses
            produksi telah dimulai, maka Pembeli dianggap melepaskan hak atas dana DP tersebut kepada Penjual sebagai
            kompensasi biaya produksi yang telah berjalan (Sesuai Pasal 1464 KUHPerdata).</p>

        <p style="margin-top:10px;"><strong>3. Kepatuhan Termin Pembayaran:</strong></p>
        <p>- Keterlambatan pembayaran pada setiap termin (termasuk termin pelunasan dokumen/pengiriman) akan berakibat
            pada penangguhan pengiriman unit secara otomatis oleh sistem.</p>
        <p>- Segala biaya tambahan yang timbul akibat keterlambatan pembayaran termin (seperti biaya sewa gudang/
            warehouse fee di China atau biaya penumpukan/ demurrage di pelabuhan) menjadi tanggung jawab penuh Pembeli.
        </p>

        <p style="margin-top:10px;"><strong>4. Penyesuaian Kurs & Biaya Negara:</strong></p>
        <p>- Harga yang disepakati dapat ditinjau kembali apabila terjadi fluktuasi kurs mata uang asing yang ekstrem
            atau perubahan regulasi pajak negara (PPN/PPh Impor) sebelum barang dirilis dari wilayah pabean.</p>
        <p>- Rilis dokumen kepemilikan barang (Bill of Lading) hanya akan dilakukan setelah kewajiban termin pembayaran
            terkait dipenuhi secara utuh.</p>

        <p style="margin-top:10px;"><strong>5. Penyelesaian Sengketa:</strong></p>
        <p>- Segala bentuk perselisihan yang timbul dari kontrak ini adalah murni ranah Hukum Perdata.</p>
        <p>- Pihak Penjual tidak melayani penagihan atau negosiasi melalui pihak ketiga yang tidak memiliki hubungan
            hukum resmi dan sah dengan kontrak ini.</p>
    </div>
    <p style="margin-top: 30px;">Terima kasih atas kepercayaan Anda.</p>
</body>

</html>