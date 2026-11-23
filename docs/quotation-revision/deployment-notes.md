# Quotation Revision Deployment Notes

Catatan ini disusun setelah UAT otomatis lokal pada 22 November 2026. Tidak ada perintah deployment atau migrasi database aplikasi yang dijalankan. Instruksi user saat ini hanya mengizinkan local development. Dokumen ini merupakan persiapan untuk rehearsal pada salinan database lokal, bukan otorisasi mengakses PROD.

Hasil dan batas pengujian tersedia di [laporan UAT](uat-local-2026-11-22.md). Pengujian MySQL, browser dan transport notifikasi sebenarnya belum dilakukan; temuan INC-01 dan PDF-01 masih terbuka.

Jika Artisan tidak tersedia, gunakan [panduan import SQL melalui phpMyAdmin](sql-import.md). Script pengganti keenam migration sudah diuji terpisah pada fixture MySQL lokal; rehearsal terhadap clone data aplikasi dan pengujian alur bisnis pada MySQL tetap diperlukan.

## Persiapan salinan lokal

1. Gunakan clone database yang disposable dan storage lokal terpisah. Pastikan host, nama database, environment, cache konfigurasi dan seluruh transport menunjuk lingkungan lokal sebelum menjalankan Artisan.
2. Backup database dan lampiran secara konsisten, termasuk signed document, proforma/invoice dan bukti pembayaran. Catat jumlah quotation, snapshot, review, log, payment dan ledger serta posisi document counter.
3. Pastikan schema dasar sudah tersedia. Repository memakai schema existing dari SQL dump; rangkaian migration tidak menggantikan seluruh schema awal. Jangan memakai `migrate:fresh` pada database CRM existing.
4. Verifikasi migration temp quotation/items/payment terms dan penyesuaian quotation logs tanggal `2026_08_20`, serta migration `2026_09_03_010500_create_document_counters_table.php`, sudah tercatat sesuai schema clone.
5. Hentikan sementara mutasi quotation, worker dan scheduler pada clone selama upgrade agar kode dan schema konsisten. Periksa dependensi PHP dan Node dari lockfile serta izin tulis storage/cache.

## Urutan migration

| File pada `database/migrations` | Perubahan dan perhatian |
| --- | --- |
| `2026_11_20_000000_add_revision_fields_to_quotations.php` | Menambahkan nomor/kode revisi dan referensi snapshot/review. Legacy quotation dimulai dari revision 0. |
| `2026_11_20_000100_backfill_legacy_quotation_revision_codes.php` | Mengisi kode R0 untuk quotation bernomor yang belum memiliki kode, termasuk soft-deleted. Tidak membuat seluruh snapshot historis atau menulis ulang status bisnis. |
| `2026_11_20_000200_add_quotation_document_counter.php` | Menambahkan type `quotation`; seed dari angka tertinggi nomor `QT_DEAL_<number>`, termasuk soft-deleted. Counter memakai tanggal sentinel `1970-01-01` sebagai sequence global, bukan reset harian. Seed tidak menurunkan counter existing. |
| `2026_11_20_000300_add_bm_approved_quotation_status.php` | Menambahkan status `approved_by_bm` pada ENUM. BM approve tidak lagi otomatis mengirim quotation ke Finance. |
| `2026_11_20_000400_add_revision_metadata_to_quotation_logs.php` | Menambahkan nomor/kode revisi pada log agar event terikat revisi yang sesuai. Log legacy dapat memakai referensi snapshot; event tanpa referensi yang diketahui tidak diasumsikan sebagai revisi current. |
| `2026_11_20_000500_add_quotation_draft_lock_version.php` | Menambahkan `lock_version` default 0 untuk mendeteksi stale draft. Tab/form lama perlu dimuat ulang setelah upgrade. |

Setelah koneksi clone lokal dipastikan, jalankan keenam migration di atas secara berurutan dengan pola berikut, menggunakan nama file persis dari tabel:

```powershell
rtk proxy php artisan migrate --path=database/migrations/2026_11_20_000000_add_revision_fields_to_quotations.php
```

Ulangi pola tersebut untuk lima file berikutnya sesuai urutan. Perintah ini belum dijalankan dalam pengerjaan UAT. Rehearsal perlu membuktikan hak ALTER, perubahan ENUM, durasi DDL dan locking pada versi MySQL target; SQLite tidak memvalidasi hal tersebut.

## Perilaku data lama

- Quotation R0 legacy yang sudah bernomor membuat snapshot secara lazy ketika jalur kompatibilitas mengaksesnya. Snapshot dibuat sekali; draft lain tidak dijadikan revisi resmi secara otomatis.
- Data yang sudah memiliki nomor revisi lebih tinggi tidak dibuatkan R0 fiktif untuk menutup history yang hilang. Quotation tanpa nomor juga tidak diberi snapshot resmi fiktif.
- Pembatalan draft menggunakan soft-delete dan log. Pembatalan tersebut tidak mengubah quotation resmi menjadi status `cancelled` dan tidak menghapus snapshot yang sudah submitted.
- Proforma, signed document dan pembayaran existing tetap terkait quotation. Revisi tidak otomatis mengganti atau menerbitkan ulang dokumen tersebut.
- Nama perusahaan default PDF berubah sesuai PRD. Nilai company profile pengguna yang sudah terisi tidak diubah massal.
- Counter global harus mempertahankan angka tertinggi, termasuk nomor milik quotation soft-deleted. Jangan truncate atau reseed turun karena nomor yang sudah diterbitkan tidak boleh dipakai ulang.

## Verifikasi setelah migration pada clone

1. Bandingkan jumlah data dan posisi counter sebelum/sesudah. Periksa kode R0 legacy, status existing, referensi snapshot/review/log, serta nilai awal `lock_version`.
2. Pastikan hanya ada satu draft aktif per konteks quotation dan satu snapshot resmi per nomor revisi. Telusuri data duplikat sebelum menerima hasil rehearsal.
3. Submit quotation baru dan pastikan nomor melanjutkan sequence tertinggi. Buat dua submit bersamaan pada MySQL untuk membuktikan nomor unik dan respons konflik.
4. Jalankan ulang runner UAT terisolasi, lalu enam skenario browser pada clone: Sales → BM → Submit to Finance → Finance, revisi customer, reject BM/Finance, history/PDF dan cancel draft.
5. Periksa notifikasi Sales setelah BM approve dan Finance/Finance Director hanya setelah Submit to Finance, termasuk catatan BM. Periksa konflik form lama menghasilkan HTTP 409 dan pengguna dapat reload.
6. Verifikasi proforma, signed document, pembayaran, expiry quotation serta incentive dengan data representatif. Pertahankan INC-01 dan PDF-01 sebagai temuan terbuka sampai perbaikannya disetujui dan diuji.
7. Build aset memakai konfigurasi lokal yang sudah diverifikasi, perbarui cache sesuai environment, lalu mulai ulang worker dan scheduler dengan kode yang sama. Periksa job expiry dan kegagalan delivery notifikasi setelah commit.

Build yang sudah dilakukan untuk laporan UAT hanya menghasilkan aset pada direktori temporary. Tidak ada aset yang diterbitkan ke server atau environment lain.

## Rollback dan pemulihan

Jangan mengandalkan rollback migration massal tanpa memeriksa data. Migration status menolak downgrade jika masih ada quotation `approved_by_bm`, termasuk soft-deleted. Migration counter menolak downgrade ketika counter quotation sudah positif; seed dari nomor legacy saja dapat memenuhi kondisi ini.

Rollback metadata log dan kolom revisi dapat menghilangkan informasi history; rollback `lock_version` menghilangkan proteksi versi draft. Backfill kode legacy tidak menghapus kode pada `down()`. Karena itu, prioritaskan perbaikan maju atau kode aplikasi yang tetap kompatibel dengan schema baru, dan uji pilihan tersebut pada clone terlebih dahulu.

Jika pemulihan backup diperlukan, database dan lampiran harus dipulihkan pada titik konsisten sesuai rencana recovery yang telah diuji. Pertahankan catatan nomor yang sudah pernah diterbitkan agar tidak dipakai ulang. Tidak ada rollback, restore, atau perubahan database existing yang dijalankan selama tugas ini.
