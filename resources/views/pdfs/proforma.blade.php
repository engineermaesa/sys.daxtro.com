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
            page-break-inside: avoid;
        }

        /* Ensure the payment-instructions block forced to a single page
           - reduce font-size, margins and paddings inside this page to help fit */
        .payment-instructions.page-break {
            page-break-before: always;
            break-before: page;
            page-break-inside: avoid;
            break-inside: avoid-page;
            font-size: 11px;
            padding: 8px;
            margin-top: 8px !important;
            line-height: 1.2;
        }

        .payment-instructions.page-break h4 {
            margin: 5px 0;
            font-size: 13px;
            text-decoration: none;
            text-align: left;
        }

        .payment-instructions.page-break p,
        .payment-instructions.page-break td,
        .payment-instructions.page-break th {
            margin: 4px 0;
            font-size: 11px;
        }

        .payment-instructions.page-break table {
            margin-top: 6px;
        }

        .payment-instructions.page-break .no-border td {
            padding: 4px 6px;
            font-size: 11px;
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
        <div class="company-info">
            <h4>PT. PANDU NARADIPTA DANENDRA</h4> 
            <p>Komplek Harmoni Plaza Blok A No. 16-17, Jl. Suryopranoto, Petojo Utara, Gambir, Jakarta Pusat</p>
            <p>Telp: (021) 22066090 | Email: info@daxtro.com</p>
        </div>
>>>>>>> 403f5e52823f885c40ca9aa0686353ae63431fb5
    </div>

    <div style="display: flex; justify-content: space-between; margin-top: 30px; margin-bottom: 10px;">
        <h3 style="margin: 0;">PROFORMA INVOICE</h3>
        <div style="text-align: right; font-size: 12px;">
            <div><strong>No:</strong> {{ $proforma->proforma_no }}</div>
            <div><strong>Tanggal:</strong> {{ $proforma->issued_at?->format('d/m/Y') }}</div>
        </div>
    </div>

    <p><strong>Customer:</strong> {{ $quotation->lead->name ?? '-' }}</p>

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
                <td colspan="3" class="right"><strong>Grand Total</strong></td>
                <td class="right"><strong>{{ number_format($quotation->items->sum('line_total'), 0, ',', '.')
                        }}</strong></td>
            </tr>
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
        <h4>Instruksi Pembayaran</h4>
        <p>Silakan melakukan transfer ke rekening berikut:</p>
        <p><strong>Bank:</strong> MANDIRI PT</p>
        <p><strong>Nomor Rekening:</strong> 1210012339754</p>
        <p><strong>Nama:</strong> PT. Pandu Naradipta Danendra</p>
        <p><strong>Cabang:</strong> KCP Jakarta Duta Merlin</p>
    </div>

    <div class="payment-instructions page-break">
        <h4 style="text-align:left;">NOTE:</h4>

        <h4>Harga sudah termasuk:</h4>
        <p>• PPN 11%</p>
        <p>• Pengiriman dari Guangzhou, China ke Port Jakarta / Surabaya</p>
        <p>• Garansi Komponen Mesin <b>12 bulan</b> sejak BAST ditandatangani (syarat & ketentuan klaim berlaku)</p>

        <h4 style="margin-top:15px;">Harga belum termasuk:</h4>
        <p>• Kebutuhan alat & bahan penunjang instalasi di lokasi (kabel power, tandon air, dll)</p>
        <p>• Biaya pengiriman dari Port Indonesia ke lokasi Customer</p>
        <p>• Akomodasi & transportasi teknisi selama proses instalasi</p>

        @php
        $total = $quotation->items->sum('line_total');
        @endphp

<<<<<<< HEAD
<div class="payment-instructions">
    <h4>Payment Instructions</h4>
    <p>Please make the transfer to the following account:</p>
    <p><strong>Bank:</strong> MANDIRI PT</p>
    <p><strong>Account Number:</strong> 1210012339754</p>
    <p><strong>Account Name:</strong> PT. Pandu Naradipta Danendra</p>
    <p><strong>Branch:</strong> KCP Jakarta Duta Merlin</p>
</div>
=======
        <h4 style="margin-top:15px;">Skema Pembayaran</h4>
        <table>
            <thead>
                <tr>
                    <th>Termin</th>
                    <th class="right">Persentase</th>
                    <th class="right">Nominal</th>
                    <th>Waktu Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                @if($quotation->paymentTerms->isNotEmpty())
                @foreach($quotation->paymentTerms as $pt)
                @php
                $perc = $pt->percentage ?? 0;
                $percLabel = rtrim(rtrim(number_format($perc, 2, ',', '.'), '0'), ',');
                $nominal = $total * ($perc / 100);
                @endphp
                <tr>
                    <td>{{ 'Termin ' . $pt->term_no }}</td>
                    <td class="right">{{ $percLabel }}%</td>
                    <td class="right">Rp {{ number_format($nominal, 0, ',', '.') }}</td>
                    <td>{{ $pt->description ?? '-' }}</td>
                </tr>
                @endforeach
                @else
                <tr>
                    <td>Down Payment</td>
                    <td class="right">50%</td>
                    <td class="right">Rp {{ number_format($total * 0.5, 0, ',', '.') }}</td>
                    <td>Sebagai tanda jadi / konfirmasi order</td>
                </tr>
                <tr>
                    <td>Termin 2 - Pengiriman</td>
                    <td class="right">40%</td>
                    <td class="right">Rp {{ number_format($total * 0.4, 0, ',', '.') }}</td>
                    <td>Sebelum mesin dikirim ke Indonesia</td>
                </tr>
                <tr>
                    <td>Termin 3 - Pelunasan</td>
                    <td class="right">10%</td>
                    <td class="right">Rp {{ number_format($total * 0.1, 0, ',', '.') }}</td>
                    <td>Setelah mesin sampai di Indonesia</td>
                </tr>
                @endif
            </tbody>
        </table>
>>>>>>> 403f5e52823f885c40ca9aa0686353ae63431fb5

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

    </div>

    <p style="margin-top: 30px;">Terima kasih atas kepercayaan Anda.</p>
</body>

</html>