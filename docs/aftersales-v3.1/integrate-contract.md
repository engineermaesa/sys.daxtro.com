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
