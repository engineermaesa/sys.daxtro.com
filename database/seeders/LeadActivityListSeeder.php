<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadActivityListSeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            ['code' => 'A01', 'name' => 'Telepon Pertama', 'description' => 'Kontak awal ke customer untuk validasi data dan minat'],
            ['code' => 'A02', 'name' => 'Follow-up WhatsApp', 'description' => 'Kirim chat WA untuk melanjutkan komunikasi setelah data masuk'],
            ['code' => 'A03', 'name' => 'Follow-up Call Kedua', 'description' => 'Telepon lanjutan untuk menggali kebutuhan atau menindaklanjuti janji sebelumnya'],
            ['code' => 'A04', 'name' => 'Kirim Company Profile / Katalog', 'description' => 'Mengirim brosur, katalog, video, atau dokumen ke customer'],
            ['code' => 'A05', 'name' => 'Kirim Quotation / Estimasi Harga', 'description' => 'Penawaran harga pertama atau revisi sesuai kebutuhan customer'],
            ['code' => 'A06', 'name' => 'Gali Kebutuhan Mesin', 'description' => 'Menanyakan jenis es, kapasitas, lokasi, dan model usaha customer'],
            ['code' => 'A07', 'name' => 'Visit Lapangan / Cek Lokasi', 'description' => 'Kunjungan ke tempat calon pembeli atau pengecekan lokasi usaha'],
            ['code' => 'A08', 'name' => 'Kirim Follow-up Reminder', 'description' => 'Kirim pengingat atau susulan via WA/Email karena belum ada balasan'],
            ['code' => 'A09', 'name' => 'Kirim Testimoni / Studi Kasus', 'description' => 'Memberikan contoh keberhasilan customer lain sebagai pendekatan persuasif'],
            ['code' => 'A10', 'name' => 'Penjelasan Teknis Mesin', 'description' => 'Menjawab pertanyaan teknis tentang mesin, listrik, air, sparepart, dan lain-lain'],
            ['code' => 'A11', 'name' => 'Follow-up Internal / Koordinasi', 'description' => 'Menghubungi engineer / sales lain untuk bantu follow-up (support internal)'],
            ['code' => 'A12', 'name' => 'Deal Sementara / Pending Approval', 'description' => 'Customer sudah setuju tapi menunggu persetujuan internal / investor'],
            ['code' => 'A13', 'name' => 'Repeat Order / Follow-up Customer Lama', 'description' => 'Menangani customer yang sudah pernah beli mesin sebelumnya'],
            ['code' => 'A14', 'name' => 'Customer Tidak Aktif', 'description' => 'Tidak ada respon selama >7 hari, belum bisa dikontak'],
            ['code' => 'A15', 'name' => 'Customer Tolak / Tidak Minat', 'description' => 'Menyatakan tidak tertarik, beli di tempat lain, atau belum jadi beli'],
            ['code' => 'A16', 'name' => 'Data Tidak Valid / Kontak Mati', 'description' => 'Nomor tidak bisa dihubungi / alamat email salah'],
            ['code' => 'A17', 'name' => 'Kirim Ke Dealer / Mitra Regional', 'description' => 'Lead dialihkan ke dealer karena wilayah / tipe usaha tertentu'],
            ['code' => 'A18', 'name' => 'Masuk Proyek Tender / CSR', 'description' => 'Customer menyatakan lead berasal dari proyek tender, CSR, atau pengadaan instansi'],
            ['code' => 'A19', 'name' => 'Minta Kunjungan ke Pabrik', 'description' => 'Customer tertarik melihat produksi / pabrik DAXTRO secara langsung'],
            ['code' => 'A20', 'name' => 'Entry Baru dari Canvassing', 'description' => 'Lead masuk manual dari hasil canvassing lapangan'],
            ['code' => 'A21', 'name' => 'Follow-up Post Kunjungan', 'description' => 'Menindaklanjuti hasil visit yang sudah dilakukan sebelumnya'],
            ['code' => 'A22', 'name' => 'Kirim Form / Link Isian Customer', 'description' => 'Mengirim form untuk melengkapi data lead (potensi, lokasi, kebutuhan)'],
            ['code' => 'A23', 'name' => 'Cold → Masuk Trash', 'description' => 'Lead dinyatakan dibuang karena inaktif / tidak valid / reject total'],
            ['code' => 'A24', 'name' => 'Kunjungan Customer ke Kantor', 'description' => 'Customer datang langsung ke kantor DAXTRO untuk diskusi atau konsultasi'],
            ['code' => 'A25', 'name' => 'Online Meeting / Zoom / Video Call', 'description' => 'Pertemuan online untuk presentasi atau diskusi kebutuhan secara tatap maya'],
            ['code' => 'A26', 'name' => 'Quotation Publish / Finalisasi', 'description' => 'Quotation final diterbitkan dengan harga fix & layout disepakati bersama'],
        ];

        foreach ($activities as $act) {
            DB::table('lead_activity_lists')->updateOrInsert(
                ['code' => $act['code']],
                ['name' => $act['name'], 'description' => $act['description']]
            );
        }
    }
}
