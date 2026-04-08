<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadActivityListSeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            ['code' => 'A01', 'name' => 'First Contact (Call/WA)', 'description' => 'Inisiasi awal (gabungan A01 & A02).'],
            ['code' => 'A02', 'name' => 'Kirim Company Profile / Katalog', 'description' => 'Pengenalan solusi dan brand Daxtro.'],
            ['code' => 'A03', 'name' => 'Gali Kebutuhan (Discovery)', 'description' => 'Penentuan kapasitas mesin & spek teknis.'],
            ['code' => 'A04', 'name' => 'Entry Baru dari Canvassing', 'description' => 'Data yang didapat langsung dari lapangan.'],
            ['code' => 'A05', 'name' => 'Visit Lapangan / Cek Lokasi', 'description' => 'Survey infrastruktur (listrik, air, ruang).'],
            ['code' => 'A06', 'name' => 'Penjelasan Teknis / Demo', 'description' => 'Edukasi detail spesifikasi mesin ke user.'],
            ['code' => 'A07', 'name' => 'Online Meeting / Video Call', 'description' => 'Alternatif visit untuk customer luar kota.'],
            ['code' => 'A08', 'name' => 'Kirim Quotation / Penawaran', 'description' => 'Pengiriman estimasi harga awal.'],
            ['code' => 'A09', 'name' => 'Kirim Testimoni / Studi Kasus', 'description' => 'Social proof untuk meyakinkan customer.'],
            ['code' => 'A10', 'name' => 'Quotation Final / Negosiasi', 'description' => 'Harga kesepakatan akhir setelah negosiasi.'],
            ['code' => 'A11', 'name' => 'Deal Sementara / SPK', 'description' => 'Sudah komitmen (tunggu DP/Legal).'],
            ['code' => 'A12', 'name' => 'Penagihan DP (Proforma)', 'description' => 'Aktivitas penagihan untuk mengunci order.'],
            ['code' => 'A13', 'name' => 'Koordinasi Internal', 'description' => 'Persiapan unit dengan tim gudang/teknisi.'],
            ['code' => 'A14', 'name' => 'Koordinasi Instalasi & Kirim', 'description' => 'Proses pengiriman dan setting mesin.'],
            ['code' => 'A15', 'name' => 'Repeat Order / Customer Lama', 'description' => 'Maintenance rutin atau tambah unit baru.'],
        ];

        foreach ($activities as $act) {
            DB::table('lead_activity_lists')->updateOrInsert(
                ['code' => $act['code']],
                ['name' => $act['name'], 'description' => $act['description']]
            );
        }
    }
}
