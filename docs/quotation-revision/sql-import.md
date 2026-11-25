# Quotation Revision SQL Import

[Script SQL](../../database/sql/2026_11_22_quotation_revision_flow.sql) menggantikan enam migration quotation revision untuk import melalui phpMyAdmin ketika Artisan tidak tersedia di terminal cPanel. Script disusun dari branch `feat/quotation-revision-flow`, ditinjau sampai commit `5369372`.

Pemeriksaan git log menggunakan rentang 20 November 2026 pukul 00:00:00 sampai 22 November 2026 pukul 23:59:59 WIB. Ada enam commit yang mengubah migration dalam rentang tersebut, semuanya tanggal 20 November. Tidak ditemukan migration tambahan pada tanggal 21 atau 22 November dalam rentang ini.

| Commit | Migration | Isi |
| --- | --- | --- |
| `f391177` | `2026_11_20_000000_add_revision_fields_to_quotations` | Kolom revision pada quotation, draft/snapshot dan review; index quotation pada snapshot. |
| `18bafe6` | `2026_11_20_000100_backfill_legacy_quotation_revision_codes` | Mengisi kode R0 legacy yang masih kosong. |
| `650b9d5` | `2026_11_20_000200_add_quotation_document_counter` | ENUM counter quotation dan seed sequence tertinggi. |
| `82f82a3` | `2026_11_20_000300_add_bm_approved_quotation_status` | ENUM status `approved_by_bm`. |
| `91cc4cd` | `2026_11_20_000400_add_revision_metadata_to_quotation_logs` | Nomor/kode revisi pada quotation log. |
| `b24ed35` | `2026_11_20_000500_add_quotation_draft_lock_version` | Versi draft untuk deteksi konflik. |

## Prasyarat

Pilih database aplikasi yang benar sebelum import. File tidak berisi `USE` atau nama database tertentu. Backup database dan lampiran, kemudian hentikan sementara mutasi quotation, worker dan scheduler selama import. DDL MySQL melakukan implicit commit; file ini bukan transaksi tunggal yang bisa dibatalkan seluruhnya.

Schema aplikasi sebelum feature ini harus sudah tersedia, termasuk migration lama untuk `temp_quotations`, `temp_quotation_items`, `temp_quotation_payment_terms`, penambahan `quotation_logs.temp_quotation_id` dan nullable `quotation_logs.quotation_id`, serta `document_counters` dengan unique index pasangan `type,date`. Tabel `migrations` Laravel harus sudah tersedia. Script ini tidak membuat tabel dasar yang hilang dan tidak menggantikan migration fitur lain di luar rentang commit tersebut.

Preflight memeriksa tabel InnoDB, kolom dasar, bentuk ENUM existing dan unique index counter. Jika schema berbeda atau belum lengkap, script menampilkan `BLOCKED` dan melewati mutasi. Periksa penyebabnya; jangan mengubah flag preflight untuk memaksa import. Nilai nomor quotation yang melampaui kapasitas counter unsigned INT juga diblokir sebelum perubahan schema.

## Import melalui phpMyAdmin

1. Buka phpMyAdmin dari cPanel dan pilih database aplikasi di sidebar.
2. Buka tab **Import**, pilih `2026_11_22_quotation_revision_flow.sql`, lalu jalankan sebagai SQL. Import seluruh file dalam satu proses/koneksi; bagian-bagiannya menggunakan session variables.
3. Pastikan output preflight menunjukkan `READY`. Jangan aktifkan opsi mengabaikan error. Jika muncul error SQL, hentikan, catat pesan dan periksa schema sebelum mencoba lagi.
4. Pastikan hasil akhir `APPLIED`, enam baris migration tercatat, sembilan kolom tambahan ditampilkan, dan counter quotation memiliki tanggal `1970-01-01` serta angka yang sesuai.
5. Cocokkan schema dan hasil data, lalu gunakan kode aplikasi dari branch yang sesuai. Jalankan skenario browser dan pemeriksaan downstream sebelum membuka kembali akses tulis. Lihat [deployment notes](deployment-notes.md).

Script tidak memerlukan stored procedure, hak `CREATE ROUTINE`, atau perintah `DELIMITER`. Tetap diperlukan hak baca metadata serta `SELECT`, `ALTER`, `UPDATE`, dan `INSERT` pada schema terkait. Penambahan index dilakukan melalui `ALTER TABLE`.

## Data dan import ulang

- Kolom yang sudah ada tidak ditambahkan ulang. Nilai revisi dan `lock_version` existing dipertahankan; tipe/default kolom tambahan yang sudah ada tidak diperbaiki otomatis.
- Backfill hanya mengisi kode yang NULL/kosong pada quotation revision 0 yang sudah bernomor. `updated_at`, status, nominal, items dan payment terms tidak diubah. Soft-deleted quotation juga ikut backfill, mengikuti migration Laravel.
- Seed counter mengambil angka tertinggi dari nomor dengan format persis `QT_DEAL_<digits>`, termasuk soft-deleted. Nomor seperti `QT001`, huruf kecil atau suffix tidak dihitung. Counter existing yang lebih tinggi tidak diturunkan. Ticket/work order counter tidak berubah.
- Snapshot legacy R0 tetap dibuat secara lazy oleh kode aplikasi; SQL tidak membuat snapshot atau keputusan review fiktif.
- Keenam nama migration dicatat pada tabel `migrations` dalam satu batch baru. Baris migration yang sudah tercatat dan nomor batch-nya dipertahankan. Karena itu, Artisan kelak tidak menjalankan ulang keenam migration ini.
- Import ulang seluruh file didukung untuk schema yang sesuai dan proses yang tidak berjalan bersamaan. Jika import sebelumnya error atau timeout, periksa hasil parsial terlebih dahulu. Jangan menandai migration secara manual sebelum semua perubahan berhasil.
- Jangan menjalankan import secara paralel dengan import atau Artisan migration lain. Script tidak menyediakan rollback otomatis. Pencatatan batch berarti rollback Artisan kelak dapat mencoba menjalankan `down()` untuk migration tersebut; baca batas rollback pada deployment notes terlebih dahulu.

## Validasi lokal

Script dijalankan pada instance MySQL 8.4.3 baru dalam direktori temporary menggunakan fixture buatan, tanpa bootstrap Laravel, membaca `.env`, atau menghubungi database aplikasi/PROD. Hasil: **Passed, 21 checks**.

Pemeriksaan mencakup backfill, seed dari quotation soft-deleted, nomor malformed, status baru, review/status/timestamp existing tetap utuh, import ulang tanpa migration duplikat atau penurunan counter, pelestarian revisi current, serta preflight untuk prasyarat hilang dan overflow counter. Instance database dihentikan setelah pengujian.

Untuk mengulang pada komputer development yang memiliki binary MySQL lokal:

```powershell
rtk proxy php tests/Integration/quotation-revision-sql-local.php
```

[Pengujian SQL lokal](../../tests/Integration/quotation-revision-sql-local.php) memakai binary Laragon MySQL 8.4.3 secara default. Path binary alternatif dapat diberikan sebagai argumen pertama. Runner selalu menginisialisasi datadir baru dan port loopback sementara; tidak menerima host atau nama database aplikasi sebagai target.

Versi MySQL/MariaDB pada hosting dan eksekusi melalui phpMyAdmin hosting belum diverifikasi. Lulus pada MySQL lokal tidak membuktikan hak akses, batas waktu import, atau kompatibilitas versi server hosting. Belum ada import ke cPanel atau PROD selama pengerjaan ini.
