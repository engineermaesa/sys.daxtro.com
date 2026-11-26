# Integration Contract — Aftersales v3.1

## BE-01 · Multi-Technician dan hak akses

PRD `AS-ROLE-05–09`, `AS-TEC-01/02/03/06/07/08`, `AS-TKT-01/02`. Gunakan kontrak v3.0 untuk transport dan endpoint lain yang tidak dijelaskan di sini.

### Backend lokal

Jalankan `php artisan migrate` pada database pengembangan dengan baseline Aftersales v3.0. Migration `2026_11_26_100000_create_ticket_technicians_table` membuat pivot dan menyalin teknisi lama ke posisi pertama. Migration `2026_11_26_100100_grant_aftersales_assignment_permissions` menambah permission untuk SPV dan Head. Pada setup baru, jalankan seeder role Aftersales sesuai panduan v3.0. Pastikan template dokumen aktif dan sudah ada akun Technician, SPV Technician, Head Aftersales, dan Admin Aftersales.

Semua path di bawah relatif ke `/api/aftersales`. Gunakan sesi login dan CSRF yang sama seperti v3.0. Kirim `Accept: application/json` agar pesan validasi berbentuk JSON.

### Opsi Add dan Assign

| Endpoint | Permintaan | Respons |
|---|---|---|
| `GET /tickets/assignment-technicians` | Tanpa parameter | `data[]` berisi `id`, `name`, dan `role_code` dengan nilai `technician` atau `supervisor_after_sales`. SPV dapat muncul tanpa profil Technician. |
| `GET /tickets/supervisors` | Contoh `?technician_ids[]=10&technician_ids[]=22`. Kirim ulang setelah pilihan teknisi berubah. Tanpa parameter mengembalikan opsi SPV. | `data[]` berisi `id` dan `name`. `head_required` menunjukkan apakah pilihan teknisi memuat SPV. `selected_supervisor_id` berisi ID Head yang harus dipilih otomatis, atau `null`. |

Jika roster memuat SPV, API memakai satu Head yang tersedia. Bila ada beberapa akun Head aktif, akun dengan ID terkecil dipakai secara konsisten. Jika belum ada Head, respons lookup berisi `head_required: true`, `data: []`, dan `selected_supervisor_id: null`. Penyimpanan assignment tersebut mendapat `422` pada `supervisor_id`.

```json
{
  "status": "success",
  "data": [
    {"id": 4, "name": "Head Aftersales"}
  ],
  "head_required": true,
  "selected_supervisor_id": 4
}
```

### Tulis assignment

| Endpoint | Ketentuan |
|---|---|
| `POST /tickets` | Tetap menerima field Add Ticket v3.0. Kirim `technician_ids` dan `supervisor_id` untuk membuat tiket sekaligus menugaskan teknisi. Add tanpa assignment tetap didukung. |
| `PUT /tickets/{id}` | Tetap memerlukan field Edit Ticket v3.0. Jika dikirim, `technician_ids` mengganti roster. Omit field assignment untuk mempertahankan roster. |
| `POST /tickets/{id}/assign` | Kirim `technician_ids` sebagai array berurutan dan `supervisor_id`, dengan `note` opsional. Endpoint ini mengganti roster. |

Contoh payload Assign dengan SPV di roster.

```json
{
  "technician_ids": [10, 22],
  "supervisor_id": 4,
  "note": "Kunjungan bersama"
}
```

Array wajib memiliki minimal satu ID unik. Item pertama menjadi teknisi utama. Jika roster berisi SPV, `supervisor_id` boleh dihilangkan karena server memilih Head. Jika semua teknisi dalam roster adalah Technician, pilih SPV sebagai Supervisor. Saat Edit/Assign, Supervisor SPV yang masih valid boleh digunakan kembali dengan menghilangkan `supervisor_id`.

Client lama dapat mengirim `assigned_technician_id` sebagai pengganti array. ID yang sama dengan teknisi utama mempertahankan roster. ID berbeda mengganti roster dengan satu teknisi. Gunakan array saat pengguna ingin menyisakan satu teknisi utama dari roster lama. Nilai `null` pada field lama di Edit mempertahankan assignment. Kirim hanya satu format assignment.

Respons sukses memakai envelope Add/Edit/Assign yang lama. Baca roster di `data.data.technicians`.

```json
{
  "message": "Technician assigned successfully",
  "data": {
    "data": {
      "id": 81,
      "assigned_technician_id": 10,
      "supervisor_id": 4,
      "technicians": [
        {"id": 10, "name": "Technician A"},
        {"id": 22, "name": "Technician B"}
      ]
    }
  }
}
```

Laravel mengembalikan `422` dengan `errors.technician_ids`, `errors["technician_ids.0"]`, atau `errors.supervisor_id` sesuai validasi yang gagal. Mutasi assignment pada tiket Closed ditolak. Role tanpa izin mutasi mendapat `403`.

### Baca tiket dan dokumen

- `GET /tickets`, `GET /tickets/{id}`, dan `GET /tickets/ready-to-close` menyertakan `technicians: [{id, name}]` sesuai urutan assignment. `technicians` kosong untuk tiket tanpa assignment. `technician` tetap membawa teknisi utama untuk client lama. Filter lama `assigned_technician_id` dan pencarian nama di All Ticket mencakup semua anggota roster. `average_score` tetap satu nilai per tiket.
- Head dapat membaca semua Steps. Ready to Close tetap merupakan daftar khusus untuk proses Close. Head meninjau atau menandatangani sebagai Supervisor hanya ketika `supervisor_id` adalah ID akun Head tersebut. Status dan kelengkapan dokumen tetap menentukan kapan aksi tanda tangan tersedia.
- SPV bisa membaca tiket tempat ia menjadi Supervisor atau anggota roster. Gunakan `can` pada respons Service Form/Agreement dan `capabilities` pada Ticket Detail untuk menentukan aksi. SPV yang ditugaskan sebagai teknisi dapat mengisi dan menandatangani dalam kapasitas teknisi. Percobaan menandatangani sebagai Supervisor pada tiketnya sendiri mendapat `403`.
- Teknisi yang ditugaskan berbagi Service Form dan Agreement pada ID tiket yang sama. Assignment tidak dikirim ulang pada Recoming. Skor survey tiket dihitung penuh untuk setiap teknisi pada penilaian teknisi.
- Link upload foto inspection yang diterbitkan untuk teknisi tetap dapat dipakai saat ia masih menjadi anggota roster. Link dari teknisi yang sudah dilepas mengembalikan `403`.
- Terus kirim `revision` dari Service Form pada Save Draft dan Submit v3. Jika teknisi lain sudah menyimpan versi yang lebih baru, API mengembalikan `409` dengan `code: "service_form_conflict"`, `current_revision`, dan `reload_url`. Pertahankan draft lokal dan minta pengguna memuat form terbaru. Jika aktor sudah dilepas dan kehilangan akses, API mengembalikan `403`.

### Checklist integrasi FE

1. Ambil opsi teknisi, pilih dua Technician biasa, pilih Supervisor SPV, dan simpan. Pastikan tiket muncul untuk kedua teknisi dan masing-masing melihat Agreement serta Form yang sama.
2. Tambah SPV ke roster. Ambil ulang opsi Supervisor dan pastikan Head dipilih. Uji role Head untuk review dan role SPV untuk edit sebagai teknisi. Pastikan keduanya melihat aksi yang sesuai dengan capability.
3. Lepas salah satu teknisi. Pastikan tiket hilang dari daftarnya dan detail ditolak jika pengguna tersebut tidak punya akses lain.
4. Buka Service Form pada dua sesi, simpan perubahan pada sesi pertama, lalu kirim draft dengan revisi lama dari sesi kedua. Pastikan pesan konflik muncul dan data server tetap terjaga.
5. Uji tiket tanpa assignment, satu teknisi, dan lebih dari dua teknisi pada list/detail. Tampilkan maksimal dua nama langsung di list, `+n` untuk sisanya, dan daftar lengkap saat hover atau tap.

### Verifikasi BE

- Suite PHPUnit Aftersales lulus dengan 370 tes dan 3.517 assertion, termasuk pemilihan Supervisor, akses setelah assignment berubah, konflik revisi, tanda tangan Head, dan skor untuk kedua teknisi.
- Runner integrasi MySQL 8.4.3 lulus dengan 15 skenario dan 646 assertion pada database sementara, termasuk backfill assignment lama, Add/Edit multi-technician, dan akses memakai role dan permission hasil seeding.

## BE-02 · Tanda tangan Recoming

PRD `AS-REC-08–10`. Jalankan migrasi setelah BE-01. Paket ini tidak memerlukan seeder.

```powershell
php artisan migrate --path=database/migrations/2026_11_26_100200_add_recoming_signature_policy.php
```

Semua path di bawah relatif ke `/api/aftersales`.

### Jadwalkan Recoming dan muat ulang dokumen

1. Admin memanggil `POST /tickets/{id}/documents/service_form/unlock` jika Service Form terkunci. Unlock membuka form yang sama. Karena tipe Recoming baru diketahui pada langkah berikutnya, tanda tangan dari siklus yang sudah dipublikasikan tetap tersedia pada respons Unlock.
2. Admin memanggil `POST /tickets/{id}/recoming` dengan `recoming_type`, jadwal, alasan, dan SLA seperti v3.0. Pada `visit_site` yang mengikuti aturan baru, server mengosongkan ketiga tanda tangan **Service Form** saat jadwal tersimpan. Untuk `phone_call`, tanda tangan yang ada tetap tersimpan. Agreement yang sudah dipublikasikan tetap digunakan untuk kedua tipe.
3. Setelah Recoming dibuat, muat ulang `GET /tickets/{id}/service-form` dan `GET /tickets/{id}/documents`. Server memberikan revisi baru. `service_form_resign_required` bernilai `true` untuk siklus site yang harus tanda tangan ulang. Field ini tersedia di `data` pada Service Form, di ringkasan `data.documents.service_form` pada Documents, dan di `data.data.cycle` pada respons buat Recoming.

### Visit site

| Tahap | Respons dan tindakan FE |
|---|---|
| Setelah jadwal tersimpan | `status: "incomplete"` dan `signatures.technician`, `signatures.customer`, serta `signatures.supervisor` bernilai `null`. Tampilkan input tanda tangan berdasarkan `can.sign_technician` dan `can.sign_customer`. |
| Teknisi melengkapi dan mengirim form | Simpan temuan dan tanda tangan Technician serta Customer. Panggil `POST /tickets/{id}/service-form/submit` dengan `revision` terkini. Respons `data.documentation_status` menjadi `awaiting_spv_signature`. Belum ada link survey aktif untuk siklus tersebut. |
| Supervisor menandatangani | Gunakan `can.sign_supervisor` dan kirim `POST /tickets/{id}/documents/service_form/signatures` dengan `role: "supervisor"` dan `signature`. Supervisor adalah akun yang ditetapkan pada tiket, termasuk Head sesuai BE-01. Respons `data.status` menjadi `documentation_published` dan server menerbitkan satu link survey untuk siklus tersebut. |

Jika Supervisor mencoba tanda tangan sebelum form dikirim, API mengembalikan `409 service_form_not_submitted`. Jika form belum lengkap, Submit mengembalikan `422 service_form_incomplete`. Setelah publikasi, muat ulang `GET /tickets/{id}/survey-link` untuk mengambil URL aktif. Request tanda tangan Supervisor yang diulang memakai tanda tangan yang sudah tersimpan dan publikasi yang sama.

Jika Admin membuka kembali Service Form site saat menunggu Supervisor, server mengosongkan tanda tangan pada form itu lagi dan mengembalikan status ke `incomplete`. Muat ulang form sebelum melanjutkan karena revision berubah.

### Phone call

- Ikuti `can.sign_technician`, `can.sign_customer`, dan `can.sign_supervisor` pada Service Form. Ketiganya `false`. Tanda tangan dari siklus sebelumnya tetap dapat dibaca di `signatures`. Request membuat tanda tangan baru untuk Service Form mengembalikan `409 phone_call_keeps_signatures`.
- Kirim form lengkap ke endpoint Submit dengan `revision` seperti biasa. Jika Agreement sudah selesai, hasilnya langsung `documentation_published`. Jika tiket belum memiliki Agreement yang selesai, publikasi tetap menunggu Agreement. Signature Service Form tidak menjadi syarat pada jalur phone call.
- `GET /tickets/{id}/survey-link` mengembalikan `variant: "phone_call"`, `status: "not_applicable"`, dan `url: null`. Admin memakai `GET /tickets/{id}/survey-response` dan `POST /tickets/{id}/survey-response` untuk survey langsung setelah publikasi.

### Kompatibilitas dan checklist FE

- Form yang sudah di-unlock sebelum migrasi dan siklus site yang sudah berjalan tetap memakai aturan tanda tangan sebelumnya. Status dan tanda tangan historis tidak diubah oleh migrasi.
- Uji ulang site dari Unlock, penjadwalan, tanda tangan Technician dan Customer, Submit, tanda tangan Head atau SPV yang berwenang, hingga link aktif. Pastikan Agreement tetap sama dan link hanya diterbitkan sekali.
- Uji phone call dari Unlock dan penjadwalan hingga Submit. Pastikan tanda tangan lama tampil sebagai riwayat, tombol tanda tangan ulang tersembunyi, serta survey langsung tersedia bagi Admin setelah publikasi.

### Verifikasi BE

- Suite PHPUnit Aftersales lulus dengan 371 tes dan 3.586 assertion, termasuk kompatibilitas untuk form yang dibuka sebelum rilis.
- Runner integrasi MySQL 8.4.3 lulus dengan 16 skenario dan 692 assertion pada database sementara. Dua request tanda tangan Head yang berjalan bersamaan menghasilkan satu publikasi dan satu link survey site.

## BE-03 · Upload foto galeri

PRD `AS-PHO-01–03`. Paket ini memakai skema foto yang sudah tersedia. Tidak ada migrasi atau seeder tambahan.

### Upload langsung ke field angka

Panggil `POST /api/aftersales/tickets/{id}/service-form/inspection/photos` dengan `multipart/form-data`, sesi login, dan CSRF seperti endpoint Aftersales lain. Gunakan akun teknisi yang ditugaskan, dan simpan nilai angka terlebih dahulu.

| Field | Isi |
|---|---|
| `item_number` | Nomor item inspection, misalnya `1`. |
| `field_key` | Key field angka yang nilainya sudah tersimpan, misalnya `pressure`. |
| `photo` | Satu file foto per request, maksimal 10 MB. Kamera dan galeri memakai field yang sama. |
| `caption` | Opsional, maksimal 500 karakter. |
| `captured_at` | Opsional. Kirim timestamp ISO 8601 jika waktu pengambilan diketahui. Jika tidak dikirim, foto langsung disimpan dengan `null`. |

Contoh respons `201`.

```json
{
  "status": "success",
  "data": {
    "id": 72,
    "caption": "Pressure gauge",
    "captured_at": null,
    "url": "/api/aftersales/tickets/81/service-form/photos/72",
    "thumbnail_url": "/api/aftersales/tickets/81/service-form/photos/72?thumb=1"
  }
}
```

Satu field angka dapat menampung maksimal lima foto dari semua sumber. Upload menambah foto. Gunakan `DELETE /api/aftersales/tickets/{id}/service-form/inspection/photos/{photo}` untuk menghapus satu foto pada form yang masih dapat diedit. Respons sukses adalah `204`. Perubahan nilai atau satuan angka menghapus bukti yang terkait dengan nilai lama. Kelengkapan field yang mewajibkan bukti tetap membutuhkan minimal satu foto sebelum Submit.

Muat ulang `GET /api/aftersales/tickets/{id}/service-form` setelah upload atau hapus untuk mengambil `revision` terbaru. Setiap field memiliki `photos[]` dalam urutan upload. Field `photo` tetap menyediakan foto terakhir atau `null` untuk klien lama. PDF Service Form memuat seluruh foto field beserta caption.

### Kamera QR dan diagnosis

- Endpoint `POST /api/aftersales/tickets/{id}/service-form/photo-links`, halaman `/capture/{token}`, dan `POST /api/inspection-photo/{token}` tetap berlaku. Kirim `source=gallery` pada endpoint token bila file dipilih dari galeri. Tanpa `captured_at`, jalur galeri menyimpan `null` dan jalur kamera QR memakai waktu server seperti sebelumnya. Respons upload token tetap memuat `data.photo_id`.
- `GET /api/inspection-photo/{token}` tetap mengembalikan `has_photo` dan menambahkan `photo_count` serta `latest_photo_id`. Saat menunggu foto tambahan melalui QR, bandingkan `latest_photo_id` dengan nilai saat polling dimulai.
- `POST /api/aftersales/tickets/{id}/service-form/diagnosis/photos` menerima foto dan `caption` seperti sebelumnya, dengan `captured_at` opsional. Field diagnosis menerima paling banyak lima foto seperti sebelumnya.
- Server menyimpan foto sebagai JPEG dengan sisi terpanjang maksimal 1.600 piksel dan membuat thumbnail 400 piksel untuk field angka. JPEG, PNG, WebP, GIF, dan BMP didukung lewat PHP GD. HEIC/HEIF memerlukan Imagick dengan dukungan format tersebut. Bila tidak tersedia, kirim JPEG atau PNG.

### Error dan checklist FE

- `403` untuk pengguna yang tidak ditugaskan, `410` untuk token QR kedaluwarsa, dan `422` untuk file tidak valid, field belum tersimpan, batas lima foto, atau form terkunci. Siklus yang sudah dipublikasikan mengembalikan `409 cycle_already_published` jika form sudah dibuka tetapi belum masuk siklus baru.
- Uji upload galeri tanpa `captured_at` dan dengan caption, lalu tambahkan bukti kamera ke field yang sama. Pastikan foto keenam memberi error dan satu foto dapat dihapus.
- Uji upload QR pada field yang sudah memiliki foto. Muat ulang form saat `latest_photo_id` berubah. Saat offline atau upload gagal, pertahankan pilihan file dan perbarui daftar setelah server mengembalikan sukses.

### Verifikasi BE

- Suite PHPUnit Aftersales lulus dengan 371 tes dan 3.586 assertion.
- Runner integrasi MySQL 8.4.3 lulus dengan 16 skenario dan 712 assertion. Pengujian mencakup galeri tanpa timestamp, kamera QR dengan timestamp, kompresi, caption, batas lima foto, penolakan pengguna yang tidak ditugaskan, dan penghapusan foto.

## BE-04 · Query sort dan filter Steps

PRD `AS-TKT-03–04`. Paket ini menggunakan skema database yang sudah ada. Tidak ada migrasi atau seeder tambahan.

### All Ticket

Gunakan `GET /api/aftersales/tickets`. Kirim Steps sebagai query array. Gabungkan dengan `priority`, `visit_date`, pencarian, dan filter daftar lain seperti biasa.

```text
GET /api/aftersales/tickets?steps[]=repair&steps[]=on_site&priority[]=high&visit_date=2026-11-26&per_page=15&page=1
```

- Satu atau beberapa `steps[]` diperlakukan sebagai **OR** antarpilihan. Filter lain berlaku sebagai **AND**. Hilangkan parameter `steps` untuk menampilkan semua Step yang dapat diakses pengguna.
- Nilai Step yang valid adalah `published`, `assigned`, `on_site`, `repair`, `waiting_sparepart`, `documentation_published`, `satisfaction_submitted`, `happy_call_completed`, `recoming`, dan `closed`. Nilai tunggal dengan format `steps=repair`, key yang tidak dikenal, dan pilihan duplikat menghasilkan `422` dengan validasi pada `steps` atau item seperti `steps.0`.
- Parameter lama `progress=repair` masih bisa dipakai dan sekarang ikut diterapkan sebelum pagination. Jika `progress` dan `steps[]` dikirim bersama, server memakai irisan keduanya.
- Respons tetap memakai `data`, `total`, `current_page`, dan `last_page`. `data[*].progress` adalah Step yang ditampilkan. Penentuan Step memakai log aktif terakhir dan aturan otomatis yang sama seperti Ticket Detail, termasuk `on_site` berdasarkan jadwal kunjungan dan pengecualian Recoming `phone_call`.

### Urutan default

Urutan dari atas ke bawah adalah **Happy Call Completed**, Recoming, Satisfaction Submitted, Documentation Published, Waiting Sparepart, Repair, On Site, Assigned, Published, lalu **Closed**. Happy Call Completed diprioritaskan sebagai Step sebelum Close. Urutan Recoming dan Step terbuka lainnya mengikuti `TicketLog::STEPS`. Izin Close tetap ditentukan oleh workflow tiket.

Untuk Step yang sama, server mengurutkan berdasarkan waktu terbaru dari pembuatan atau perubahan tiket, pembuatan log Step aktif terakhir, perubahan kunjungan aktif terakhir menurut jadwal, dan perubahan Recoming aktif terakhir. Jika waktunya sama, ID tiket terbesar tampil lebih dulu. Filter `visit_date` memakai tanggal kunjungan aktif terakhir dalam WIB.

### Checklist FE

- Tambahkan filter Steps multi-select dan kembali ke halaman 1 saat nilai filter berubah. Render urutan dari server.
- Uji kombinasi Steps, Priority, dan Visit Date dengan lebih dari satu halaman. Pastikan total cocok dengan hasil filter, termasuk saat memilih Closed secara eksplisit.
- Uji tampilan Ready to Close melalui endpoint yang sudah ada. Aturan dan urutan khusus halaman itu tetap mengikuti kontraknya sendiri.

### Verifikasi BE

- Suite PHPUnit Aftersales lulus dengan 373 tes dan 3.631 assertion.
- Runner integrasi MySQL 8.4.3 lulus dengan 17 skenario dan 720 assertion, termasuk pagination setelah filter Steps, penggabungan Priority dan Visit Date, serta prioritas Step sebelum Closed.
