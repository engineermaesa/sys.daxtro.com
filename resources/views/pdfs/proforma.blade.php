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

        .company-info h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }

        .company-info p {
            margin: 0;
            font-size: 12px;
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

        .right {
            text-align: right;
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

        /* ================= PAYMENT ================= */
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

        .payment-item {
            margin-bottom: 4px;
        }

        .payment-item .label {
            display: inline-block;
            width: 150px;
            font-weight: bold;
            text-align: left;
        }

        .payment-item .colon {
            display: inline-block;
            width: 10px;
            text-align: center;
        }

        .payment-item .value {
            display: inline-block;
            text-align: left;
        }
    </style>
</head>

<body>

<<<<<<< HEAD
<div class="header">
    <img src="{{ public_path('assets/images/logo.png') }}" class="logo" alt="Logo">
    <div class="company-info">
        <h4>PT. Pandu Naradipta Danendra</h4>
        <p>Komplek, Harmoni Plaza, Jl. Suryopranoto No.17 Blok A, RT.2/RW.8, Petojo Utara, Kecamatan Gambir, Kota Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10130</p>
        <p>Telp: (021) 22066090 | Email: info@daxtro.com</p>
=======
    <div class="header">
        <img src="{{ public_path('assets/images/logo.png') }}" class="logo" alt="Logo">
        <div class="company-info"> <br>
            <h4>PT. PANDU NARADIPTA DANENDRA</h4>
            <p>Komplek Harmoni Plaza Blok A No. 16-17, Jl. Suryopranoto, Jakarta Pusat</p>
            <p>Telp: (021) 22066090 | Email: info@daxtro.com</p>
        </div>
>>>>>>> 403f5e52823f885c40ca9aa0686353ae63431fb5
    </div>

    <!-- HEADER TITLE + META -->
    <div style="margin-top: 30px;">

        <div style="float: left;">
            <h3 style="margin: 0;">PROFORMA INVOICE</h3>
        </div> <br>

        <div class="meta" style="float: right;">
            <div class="meta-item">
                <span class="label">No</span>
                <span class="colon">:</span>
                <span class="value">{{ $proforma->proforma_no }}</span>
            </div>
            <div class="meta-item">
                <span class="label">Tanggal</span>
                <span class="colon">:</span>
                <span class="value">{{ $proforma->issued_at?->format('d/m/Y') }}</span>
            </div>
            <div class="meta-item">
                <span class="label">Jatuh Tempo</span>
                <span class="colon">:</span>
                <span class="value">
                    @if($proforma->issued_at)
                    {{ $proforma->issued_at->copy()->addDays(15)->format('d/m/Y') }}
                    @else
                    -
                    @endif
                </span>
            </div>
        </div>

        <div style="clear: both;"></div>

    </div>
    <p style="margin-top:10px;"><strong>Kepada:</strong> {{ $quotation->lead->name ?? '-' }}</p>

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

            <tr>
                <td colspan="3" class="right"><strong>Total Invoice</strong></td>
                <td class="right">
                    <strong>{{ number_format($quotation->items->sum('line_total'), 0, ',', '.') }}</strong>
                </td>
            </tr>

            <tr>
                <td colspan="3" class="right"><strong>PPN (
                        @if(isset($quotation->tax_pct))
                        {{ rtrim(rtrim(number_format($quotation->tax_pct, 2, ',', '.'), '0'), ',') }}%
                        @else
                        0%
                        @endif
                        )</strong></td>
                <td class="right">
                    <strong>{{ number_format($quotation->tax_total ?? (($quotation->grand_total ??
                        $quotation->items->sum('line_total')) * (($quotation->tax_pct ?? 0) / 100)), 0, ',', '.')
                        }}</strong>
                </td>
            </tr>
            <tr>
                <td colspan="3" class="right"><strong>Grand Total</strong></td>
                <td class="right">
                    <strong>{{ number_format($quotation->grand_total ?? $quotation->items->sum('line_total'), 0, ',',
                        '.') }}</strong>
                </td>
            </tr>

            @php
            $label = 'Amount';
            if ($proforma->proforma_type === 'booking_fee') {
            $label = 'Booking Fee';
            } elseif (in_array($proforma->proforma_type, ['down_payment', 'term_payment'], true)) {
            $term = $quotation->paymentTerms->firstWhere('term_no', $proforma->term_no);
            $percentage = $term?->percentage;
            $termDesc = $term?->description;

            if ($termDesc) {
            $label = 'Jumlah Tertagih / ' . $termDesc;
            } else {
            $label = $proforma->proforma_type === 'down_payment' ? 'Down Payment' : 'Term Payment';
            }

            if ($percentage) {
            $label .= ' (' . rtrim(rtrim(number_format($percentage, 2, ',', '.'), '0'), ',') . '%)';
            }
            }
            @endphp

            <tr>
                <td colspan="3" class="right"><strong>{{ $label }}</strong></td>
                <td class="right">
                    <strong>{{ number_format($proforma->amount, 0, ',', '.') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- PAYMENT -->
    <div class="payment-instructions">
        <h4>Instruksi Pembayaran</h4>
        <p>Silakan melakukan transfer ke rekening berikut:</p>

        <div class="payment-item">
            <div class="label">Bank</div>
            <div class="colon">:</div>
            <div class="value">MANDIRI PT</div>
        </div>

        <div class="payment-item">
            <div class="label">Nomor Rekening</div>
            <div class="colon">:</div>
            <div class="value">1210012339754</div>

        </div>
        <div class="payment-item">
            <div class="label">Nama Akun</div>
            <div class="colon">:</div>
            <div class="value">PT. Pandu Naradipta Danendra</div>
        </div>
        
        <h4 style="margin-top:15px;">Harga belum termasuk:</h4>
        <p>• Kebutuhan alat & bahan penunjang instalasi di lokasi (kabel power, tandon air, dll)</p>
        <p>• Biaya pengiriman dari Port Indonesia ke lokasi Customer</p>
        <p>• Akomodasi & transportasi teknisi selama proses instalasi</p>

        @php
        $total = $quotation->items->sum('line_total');
        @endphp

<div class="payment-instructions">
    <h4>Payment Instructions</h4>
    <p>Please make the transfer to the following account:</p>
    <p><strong>Bank:</strong> MANDIRI PT</p>
    <p><strong>Account Number:</strong> 1210012339754</p>
    <p><strong>Account Name:</strong> PT. Pandu Naradipta Danendra</p>
    <p><strong>Branch:</strong> KCP Jakarta Duta Merlin</p>
</div>

        <p style="margin-top:10px;">
            <i>Rekening Pembayaran akan diinformasikan melalui Invoice/Surat Pengantar Tagihan resmi.
                Setiap pembayaran harap disertai bukti transfer yang dikirimkan kepada kami.</i>
        </p>

        <h4 style="margin-top:15px;">Estimasi Pengiriman</h4>
        <table class="no-border">
            <tr>
                <td>Produksi</td>
                <td>±60 hari</td>
            </tr>
            <tr>
                <td>Pengiriman (Laut)</td>
                <td>±15 hari</td>
            </tr>
            <tr>
                <td>Clearance & Bea Cukai</td>
                <td>±10 hari</td>
            </tr>
        </table>

        <h4 style="margin-top:15px;">Instalasi</h4>
        <p>
            Persiapan tempat, saluran air, instalasi listrik, tenaga bantu, serta seluruh perlengkapan pendukung lainnya
            (termasuk alat berat & perkakas) disediakan oleh Customer. Tim teknisi kami akan melakukan supervisi dan
            pendampingan hingga proses instalasi dan uji coba selesai. Keterlambatan Instalasi dikarenakan ketidaksiapan
            Lokasi menjadi tanggungjawab Pembeli.
        </p>

        <h4 style="margin-top:15px;">Syarat & Ketentuan</h4>
        <ul style="padding-left: 20px;">
            <li style="margin-bottom: 10px;">
                Payment : Pembayaran yang telah diterima tidak dapat dikembalikan dengan alasan apapun. Pembatalan
                sepihak setelah DP masuk mengakibatkan Pembeli kehilangan hak atas dana tersebut sebagai kompensasi
                biaya yang telah berjalan (Pasal 1464 KUHP).
            </li>

            <li style="margin-bottom: 10px;">
                Penyesuaian Harga & Kurs : Harga yang disepakati dapat ditinjau kembali apabila terjadi fluktuasi kurs
                ekstrem atau perubahan regulasi perpajakan impor sebelum barang dirilis dari pabean. Bill of Lading
                hanya diterbitkan setelah seluruh kewajiban pembayaran terpenuhi.
            </li>

            <li style="margin-bottom: 10px;">
                Estimasi Pengiriman di atas merupakan proyeksi normal. Keterlambatan yang disebabkan oleh faktor di
                luar kendali kami — seperti keterbatasan ketersediaan kontainer, kondisi cuaca laut, proses bea cukai,
                atau kebijakan ekspor-impor yang berlaku — tidak dapat kami pertanggung jawabkan dan tidak menjadi dasar
                klaim penalti. </li>

            <li>
                Segala perselisihan diselesaikan melalui jalur Hukum Perdata. Kami tidak melayani negosiasi melalui
                pihak ketiga yang tidak memiliki hubungan hukum resmi dengan kontrak ini. </li>
        </ul>
>>>>>>> 49c2ebad4b6e8c62c47542aa3016458e5205512f

        <div class="payment-item">
            <div class="label">Cabang</div>
            <div class="colon">:</div>
            <div class="value">KCP Jakarta Duta Merlin</div>
        </div>
    </div>

    <p style="margin-top: 30px;">Terima kasih atas kepercayaan Anda.</p>

</body>

</html>