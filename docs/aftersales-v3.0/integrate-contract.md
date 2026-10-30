# Integration Contract — Aftersales v3.0

Dokumen kontrak BE → FE untuk Aftersales PRD v3.0. Diperbarui setiap task BE selesai; tiap task menambah satu bagian di §3 dan satu baris di §5.

Baseline kode: `aa8b054`. Bagian di bawah hanya memuat perilaku yang **sudah diimplementasi dan diuji**. Task yang belum dikerjakan tidak ada di dokumen ini.

## 1. Transport dan autentikasi

- Base URL internal: `/api/aftersales`. Path memakai ID integer internal.
- Auth: cookie session login existing, **bukan Bearer token**. Route memakai middleware `api`, `web`, `auth`. Kirim `Accept: application/json`; mutation JSON juga butuh `Content-Type: application/json` dan token CSRF (`X-CSRF-TOKEN`).
- Waktu bisnis server: `Asia/Jakarta`.

## 2. Envelope dan error

Envelope **tidak seragam** antar endpoint (perilaku existing, tidak diubah):

| Jenis | Bentuk |
|---|---|
| List | `{"status":"success","data":[...],"total":n,"current_page":n,"last_page":n}` |
| Detail | `{"status":"success","data":{...}}` |
| Mutation machine | `{"message":"...","data":{"data":{...}}}` (dua lapis `data`) |

Error:

| HTTP | Arti |
|---|---|
| 401 | Belum login |
| 403 | Tidak punya permission `masters.service-customers` |
| 404 | Customer/machine tidak ditemukan |
| 419 | CSRF/session tidak sesuai |
| 422 | Validasi gagal |

422 memakai format Laravel standar:

```json
{"message":"The trader name field is required.","errors":{"trader_name":["The trader name field is required."]}}
```

## 3. Task yang sudah selesai

### Task 1 — Machine Trader dan filter Customers (AS-MCH-01..04) · IMPLEMENTED

Permission semua endpoint di bagian ini: `masters.service-customers`.

#### 3.1 Field Trader pada machine

Kolom baru `service_customer_products.trader_name` (string, nullable di DB, max 255). Wajib divalidasi server saat Non-Daxtro.

| Endpoint | Perubahan |
|---|---|
| `POST /customers/{customer}/products` | Terima `trader_name`. Status 201 |
| `PUT /customers/products/{product}` | Terima `trader_name`. Status 200 |
| `GET /customers/{customer}` | Setiap item `data.products[]` membawa `trader_name` |
| `GET /tickets/{ticket}` | `data.customer_product` membawa `trader_name` (machine di-serialisasi penuh) |

Aturan:

| `is_daxtro` | `brand` | `trader_name` | `serial_number`, warranty, PM |
|---|---|---|---|
| `true` (default saat create) | diabaikan, disimpan `null` | diabaikan, disimpan `null` | serial wajib; warranty/PM sesuai input |
| `false` | **wajib** | **wajib** | diabaikan; serial/warranty `null`, `pm_contract_status:"none"`, `pm_frequency:null` |

- Pada `PUT`, `is_daxtro` yang tidak dikirim mempertahankan nilai machine saat ini. Machine Non-Daxtro yang di-`PUT` tanpa `trader_name` ditolak 422.
- Mengubah machine Non-Daxtro menjadi Daxtro mengosongkan `brand` dan `trader_name`.
- FE tidak perlu mengirim `trader_name` untuk machine Daxtro. Jika dikirim, nilainya dibuang.
- Menampilkan Brand/Trader hanya untuk Non-Daxtro di Ticket Detail adalah tugas FE (cek `is_daxtro`).

Request Non-Daxtro:

```json
{"is_daxtro":false,"machine_name":"Customer chiller","brand":"Other Brand","trader_name":"Trader Co","capacity":6,"compressor":"Copeland","refrigerant":"R22","note":"External machine"}
```

Response 201 (subset):

```json
{"message":"Product unit added successfully","data":{"data":{"id":12,"customer_id":1,"is_daxtro":false,"machine_name":"Customer chiller","brand":"Other Brand","trader_name":"Trader Co","serial_number":null,"warranty_start":null,"warranty_end":null,"warranty_active":null,"pm_contract_status":"none","pm_frequency":null}}}
```

Error 422 saat Non-Daxtro tanpa Trader:

```json
{"message":"The trader name field is required.","errors":{"trader_name":["The trader name field is required."]}}
```

Mesin lama Non-Daxtro yang dibuat sebelum Task 1 (termasuk dari import) memiliki `trader_name: null`. Tidak ada backfill otomatis: Trader wajib terisi saat machine itu diedit berikutnya, sesuai PRD §12.1.

#### 3.2 Filter dan hitungan Machines di list Customers

`GET /customers`

| Query | Nilai | Default | Keterangan |
|---|---|---|---|
| `machine_scope` | `all` \| `daxtro` \| `non_daxtro` | `all` | Nilai lain → 422 `errors.machine_scope` |
| `search`, `page`, `per_page` | tidak berubah | `per_page` 15, dibatasi 1..100 | |

Semantik filter (mesin yang soft-deleted tidak dihitung):

| Customer | `all` | `daxtro` | `non_daxtro` |
|---|---|---|---|
| Hanya mesin Daxtro | ya | ya | tidak |
| Campuran (≥1 Daxtro dan ≥1 Non-Daxtro) | ya | **tidak** | ya |
| Hanya mesin Non-Daxtro | ya | tidak | ya |
| Tanpa mesin | ya | tidak | tidak |

`is_daxtro = NULL` dianggap Daxtro. `total`, `last_page`, dan isi halaman konsisten dengan filter (filter dijalankan di query sebelum paginasi).

Field baru di setiap item `data[]`:

| Field | Tipe | Arti |
|---|---|---|
| `daxtro_machines_count` | int | Jumlah mesin Daxtro aktif |
| `non_daxtro_machines_count` | int | Jumlah mesin Non-Daxtro aktif |
| `products_count` | int | Tidak berubah; total mesin (= jumlah keduanya) |

Contoh `GET /customers?machine_scope=non_daxtro` (subset):

```json
{"status":"success","data":[{"id":7,"name":"Mixed","products_count":2,"daxtro_machines_count":1,"non_daxtro_machines_count":1}],"total":1,"current_page":1,"last_page":1}
```

Badge kolom Machines (`[n] Daxtro`, `[n] Non-Daxtro`) dibuat FE dari dua count di atas.

#### 3.3 Di luar Task 1

- Template Import Customer belum menerima kolom Trader (menunggu OQ-11). Baris Non-Daxtro hasil import memiliki `trader_name: null`.
- Tidak ada perubahan pada `GET /customers/machine-references`, `POST /customers/machine-name-preview`, atau envelope response.

#### 3.4 Verifikasi Task 1

- `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Aftersales/ImprovementsTest.php`: **OK, 81 tests, 833 assertions** (SQLite in-memory).
- Test baru: `test_trader_is_required_for_non_daxtro_and_nulled_for_daxtro`, `test_customer_list_filters_by_machine_scope_and_counts_machines_per_type`.
- Test existing yang membuat machine Non-Daxtro lewat API diperbarui agar menyertakan `trader_name`.
- Regression penuh `tests/Feature/Aftersales tests/Unit/Aftersales`: **OK, 224 tests, 1694 assertions** (222 baseline + 2 baru).
- Belum diverifikasi: migration di MySQL deployment. Migration `2026_10_29_000000_add_trader_name_to_service_customer_products.php` hanya menambah kolom nullable dan mengecek `hasColumn` sebelum menambah/menghapus.

### Task 2 — Role, permission, dan capabilities ticket (AS-ROLE-01..04) · IMPLEMENTED

Dikerjakan memakai **usulan default PRD** karena OQ-07, OQ-12, OQ-13 belum dijawab. Jika jawabannya berbeda, bagian ini direvisi.

#### 3.5 Empat role

| Role PRD | `role.code` | Catatan |
|---|---|---|
| Admin Aftersales | `after_sales` | Tidak berubah kodenya |
| Technician | `technician` | Tidak berubah kodenya |
| SPV Technician | `supervisor_after_sales` | Nama tampilan diubah jadi "SPV Technician"; kode tetap supaya tiket lama tidak rusak |
| Head Aftersales | `head_after_sales` | **Role baru.** Kode ini keputusan BE; ubah jika tim punya konvensi lain |

BE **tidak memindahkan user antar role**. Daftar user per role (OQ-07) harus diisi tim lewat menu user/role. Tidak ada endpoint baru untuk itu.

#### 3.6 Matriks permission (hasil seeding)

| Permission | Admin | Technician | SPV | Head |
|---|---|---|---|---|
| `aftersales.tickets.manage` (add/edit/schedule/unlock/Happy Call, baca semua) | ya | – | – | – |
| `aftersales.tickets.assign` | ya | – | – | – |
| `aftersales.tickets.view-all` (**baru**, baca semua tiket + dokumen) | (lewat manage) | – | ya | ya |
| `aftersales.documents.sign` (**baru**, tanda tangan sebagai SPV) | – | – | ya | – |
| `aftersales.documents.fill` | – | ya | – | – |
| `aftersales.tickets.view-own` | – | ya | – | – |
| `aftersales.tickets.recoming` | – (cukup `manage`) | **dicabut** | – | – |
| `aftersales.tickets.close` | **dicabut** | – | – | ya |
| `satisfaction-survey.view`, `customers.view`, `dashboard` | ya | `dashboard` | ya | ya |

Perubahan perilaku yang perlu diketahui FE:

- `aftersales.tickets.close` dicabut dari **semua** role selain Head, termasuk `super_admin`. `POST /tickets/{id}/close` → **403** untuk mereka.
- Technician tidak lagi bisa memicu atau reschedule Recoming (permission `aftersales.tickets.recoming` dicabut). Kode endpoint belum diubah (Task 5); penutupan terjadi lewat permission.
- SPV **tidak lagi** memiliki `manage`, `assign`, atau hak Admin lainnya.
- Admin yang kebetulan menjadi `supervisor_id` tiket **tidak bisa** tanda tangan atau Save sebagai supervisor (butuh `documents.sign`). Tiket lama dengan `supervisor_id` bukan user SPV harus di-reassign supervisor-nya.

#### 3.7 Akses baca

Endpoint berikut menerima user dengan `manage` **atau** `view-all` (sebelumnya hanya `manage`; Technician tetap hanya tiket miliknya lewat `view-own`):

`GET /tickets`, `GET /tickets/{ticket}`, halaman tiket, dan seluruh endpoint baca dokumen (`GET .../documents`, `.../service-form`, `.../agreement`, PDF).

Untuk user `view-all`, `GET /tickets/{ticket}` juga mengembalikan `survey_responses` dan `satisfaction` (ringkasan skor, status review, review note), sama seperti Admin. Technician tetap tidak menerima keduanya.

Aksi tulis tetap butuh permission masing-masing (`manage`, `assign`, `close`, dst.). `dispatches` pada ringkasan dokumen hanya untuk Admin dan SPV yang ditunjuk di tiket tersebut; resend email tetap Admin saja.

#### 3.8 `data.capabilities` di `GET /tickets/{ticket}`

Objek baru berisi boolean. FE **harus** memakainya untuk menampilkan atau menyembunyikan tombol, bukan menebak dari nama role. Nilai mencerminkan guard server saat ini untuk user yang login dan tiket tersebut.

| Key | True jika |
|---|---|
| `can_edit` | punya `manage` |
| `can_assign` | punya `aftersales.tickets.assign` |
| `can_schedule` | punya `manage` |
| `can_reschedule` | tiket Recoming dan jadwal lanjutan belum tiba, dan user punya `manage` (atau Technician pemilik dengan `recoming`, yang kini tidak ada di seeding) |
| `can_trigger_recoming` | tiket belum Closed, dan `manage` (atau Technician pemilik dengan `recoming`) |
| `can_unlock_service_form` | `manage` dan Service Form sedang terkunci (saved) |
| `can_fill_service_form` | Technician pemilik, form belum terkunci, tiket belum Closed |
| `can_sign_service_form` | user adalah `supervisor_id` tiket, punya `documents.sign`, form belum terkunci, tiket belum Closed |
| `can_sign_agreement` | idem, untuk Agreement |
| `can_record_happy_call` | `manage`, tiket belum Closed dan bukan Recoming |
| `can_close` | punya `close` dan tiket belum Closed |

Contoh untuk Head Aftersales pada tiket Documentation Published:

```json
{"status":"success","data":{"id":10,"progress":"documentation_published","capabilities":{"can_edit":false,"can_assign":false,"can_schedule":false,"can_reschedule":false,"can_trigger_recoming":false,"can_unlock_service_form":false,"can_fill_service_form":false,"can_sign_service_form":false,"can_sign_agreement":false,"can_record_happy_call":false,"can_close":true}}}
```

Belum ada di `capabilities` (ditambahkan di task terkait): `can_send_service_form` (Task 4). `can_close` belum memeriksa syarat Happy Call/survey; syarat itu ditegakkan server saat `POST /close` dan akan masuk `close_blockers` di Task 9.

#### 3.9 Deploy

`php artisan migrate` menjalankan `2026_10_29_000100_seed_aftersales_v3_roles.php`, yang membuat role Head, permission baru, dan menyesuaikan permission role. Idempotent. Instalasi baru lewat `RolePermissionSeeder` menghasilkan hasil yang sama.

#### 3.10 Verifikasi Task 2

- Test baru: `AftersalesRolesV3Test` (seeder idempotent dan matriks permission, capabilities per role, unlock/sign pada dokumen terkunci), tambahan di `TicketAuthorizationTest`. Test existing yang menandatangani sebagai supervisor dengan `manage` diperbarui ke `documents.sign`.
- Regression: lihat §5.
- Belum diverifikasi: migration seeding di MySQL deployment; pengisian user per role oleh tim.

### Task 3 — Fondasi siklus dan migration (§9 PRD) · IMPLEMENTED

#### 3.11 Transisi workflow dan identitas siklus

Memakai **default OQ-19**, belum merupakan jawaban final tim: ticket yang sudah ada saat migration tetap `workflow_version: "legacy"`; ticket yang dibuat setelah migration memakai `"v3"`. Penanda ini disimpan di server, bukan pilihan FE. `POST /tickets` mengabaikan input `workflow_version` dan `cycle_id` dari client. Update ticket tidak memindahkan workflow.

Task 3 adalah fondasi data. Penanda `v3` belum mengaktifkan aturan submit/sign baru, stop SLA di survey, atau gate Close baru; implementasinya mengikuti Task 4–9. Ticket legacy tidak dikonversi ke aturan baru oleh migration ini.

- Tabel baru `ticket_cycles`: ID integer global dan `cycle_no` berurutan **per ticket**, dimulai dari `1` untuk siklus awal. Recoming pertama adalah siklus `2`. `cycle_no` berbeda dari `visit_no`; jumlah visit fisik tidak dipakai untuk mengalokasikan nomor siklus.
- Satu `ticket_recomings` menunjuk satu `cycle_id` unik. Service Form dan Agreement tetap satu per ticket, tanpa duplikasi dokumen per siklus.
- `ticket_visits`, `ticket_logs`, `ticket_satisfactions`, `satisfaction_links` mendapat `cycle_id`. Log `documentation_published` dan `happy_call_completed` ikut menunjuk siklus lewat kolom log ini; belum ada tabel publikasi atau Happy Call terpisah.
- Foreign key gabungan `(ticket_id, cycle_id)` memastikan referensi selalu ke siklus ticket yang sama. Unique `(ticket_id, cycle_no)` mencegah nomor ganda.
- ORM membuat siklus awal otomatis saat ticket dibuat. Penjadwalan Recoming mengunci row ticket, membuat siklus baru, menutup batas siklus sebelumnya lewat `ended_at`, lalu menulis Recoming, visit, dan log dalam satu transaksi. Kegagalan transaksi membatalkan seluruh alokasi siklus. Request yang mendapati progress berubah setelah pemeriksaan awal ditolak HTTP **409**, message `The ticket changed. Reload before scheduling Recoming.`
- Reschedule mengubah jadwal pada siklus yang sama; tidak membuat ID atau nomor baru.
- Visit, link, respons survey, dan log baru otomatis memakai siklus terkini. Respons survey melalui link dan log submit-nya mempertahankan `cycle_id` link asal. Aturan valid/current response dan penolakan link siklus lama dilengkapi di Task 8.

#### 3.12 Field response untuk FE

Auth dan permission endpoint tetap seperti Task 2. Tidak ada endpoint tulis siklus baru.

| Endpoint | Tambahan response |
|---|---|
| `POST /tickets` | `data.data.workflow_version` = `"v3"`; `data.data.logs[].cycle_id` menunjuk siklus awal |
| `GET /tickets` | `data[].workflow_version`, `happy_call_due_at`, `sla_stopped_at`; `cycle_id` pada nested log/visit/Recoming yang diserialisasi |
| `GET /tickets/{ticket}` | `data.workflow_version`, `data.current_cycle`, `data.cycles` (urut `cycle_no` naik), `data.happy_call_due_at`, `data.sla_stopped_at`; nested log/visit/Recoming/survey response membawa `cycle_id` sesuai serialisasi endpoint |
| `POST /tickets/{ticket}/recoming` | `data.data.cycle_id` untuk siklus baru |
| `GET /tickets` dan `GET /tickets/{ticket}` | `current_recoming.cycle_id` saat ticket masih Recoming; `current_recoming` tetap `null` di progress lain |

`GET /tickets/{ticket}` membawa `cycle_id` juga pada ringkasan `survey_responses[]` untuk Admin/SPV/Head. Pembatasan jawaban survey Technician tetap mengikuti Task 2; data siklus hanya memuat identitas dan waktu/SLA, tanpa jawaban atau review note.

Field objek `current_cycle` / setiap item `cycles[]`:

| Field | Tipe / arti |
|---|---|
| `id`, `ticket_id`, `cycle_no` | integer; ID siklus, pemilik, nomor urut |
| `started_at` | datetime; siklus awal dari waktu pembuatan ticket, Recoming dari waktu penjadwalan disimpan |
| `ended_at` | datetime atau null; batas siklus saat Recoming berikutnya disimpan, **bukan** tanggal SLA berhenti atau ticket Closed |
| `sla_value`, `sla_unit`, `sla_due_at` | nullable; siklus awal menyalin setup ticket; siklus Recoming belum diisi sampai Task 7 |
| `sla_stopped_at`, `happy_call_due_at` | datetime atau null; fondasi untuk Task 7 dan 9 |
| `created_at`, `updated_at` | datetime pencatatan row |

Contoh `GET /tickets/2` setelah Recoming (subset sesuai test API):

```json
{"status":"success","data":{"id":2,"workflow_version":"v3","happy_call_due_at":null,"sla_stopped_at":null,"current_cycle":{"id":3,"ticket_id":2,"cycle_no":2,"ended_at":null,"sla_value":null,"sla_unit":null,"sla_due_at":null,"sla_stopped_at":null,"happy_call_due_at":null},"cycles":[{"id":2,"ticket_id":2,"cycle_no":1},{"id":3,"ticket_id":2,"cycle_no":2}],"current_recoming":{"cycle_id":3,"visit_no":1,"reason":"further_work_needed","from_step":"repair"}}}
```

`visit_no: 1` pada contoh valid karena belum ada visit fisik sebelumnya; jangan menyamakannya dengan `cycle_no: 2`. Nama status, reason, request Recoming, envelope, dan error validasi existing belum diganti pada Task 3.

#### 3.13 Migration dan data historis

Migration: `2026_10_30_000000_create_ticket_cycles.php`. Jalankan lewat `php artisan migrate` pada jendela maintenance yang menghentikan penulisan ticket selama perubahan schema/backfill. Migration MySQL mencakup DDL dan tidak diasumsikan atomik; jangan memanggil `up()` berulang tanpa rollback.

- Semua ticket, termasuk yang soft-deleted, mendapat siklus awal. Recoming historis, termasuk yang soft-deleted, masing-masing mendapat siklus berdasarkan urutan ID; nilai bisnis dan `visit_no` lama dipertahankan.
- Ticket tanpa Recoming: seluruh visit/log/survey/link historis, termasuk row soft-deleted, aman dipautkan ke siklus awal.
- Ticket dengan Recoming: relasi Recoming ke siklus diisi. `cycle_id` visit/log/survey/link historis tetap **null**, karena timestamp lama tidak cukup untuk menentukan batas siklus secara andal. Jangan menganggap null sebagai siklus terkini atau memakai data itu untuk gate v3. Jalur legacy yang ada tetap dipertahankan.
- SLA awal menyalin setup ticket yang tersedia. Tidak membuat deadline SLA Recoming historis, tidak menghitung ulang SLA, tidak mengisi Happy Call due atau SLA stopped secara retroaktif.
- Rollback menghapus fondasi siklus dan kolom baru, sambil mempertahankan ticket, Recoming, visit, log, survey, serta link. Rollback membuang metadata siklus; pengulangan migration mengikuti aturan backfill legacy lagi.
- Belum dijalankan pada database aplikasi/deployment.

#### 3.14 Verifikasi Task 3

- `TicketCyclesTest`: backfill aktif/soft-deleted, histori ambigu, default workflow baru, siklus awal idempotent, relasi child, asal siklus respons, rollback transaksi, foreign key lintas ticket, unique nomor siklus, migration down/up.
- `ImprovementsTest`: pembuatan API mengabaikan workflow/cycle dari client; Recoming membuat siklus baru dengan visit/log yang sesuai; detail menampilkan urutan siklus; reschedule mempertahankan ID; Recoming berulang saat masih Recoming ditolak.
- Smoke test **MySQL 8.4.3** pada database sementara terpisah: migration up, backfill legacy, default v3/siklus awal, alokasi Recoming, foreign key lintas ticket, down, dan reapply **lulus**. Database sementara sudah dihapus; database aplikasi tidak diubah.
- Regression akhir: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Aftersales tests/Unit/Aftersales` → **OK, 239 tests, 1793 assertions** (SQLite in-memory). Formatter PHP pada file yang berubah dan `git diff --check` lulus.

## 4. Pending (belum dikerjakan)

Task 4–12 sesuai daftar task BE. Bagian kontraknya ditambahkan ke §3 saat task selesai.

## 5. Change log

| Tanggal | Task | Status |
|---|---|---|
| 2026-10-29 | Task 1: Machine Trader dan filter Customers | Implemented dan diuji (224 test lulus); migration baru, belum dijalankan di MySQL deployment |
| 2026-10-29 | Task 2: Role, permission, capabilities ticket | Implemented dan diuji (229 test lulus, 1717 assertions); memakai usulan default OQ-07/12/13; migration seeding belum dijalankan di MySQL deployment |
| 2026-10-30 | Task 3: Fondasi siklus, relasi, workflow legacy/v3 | Implemented; 239 test lulus, 1793 assertions; smoke migration MySQL 8.4.3 lulus pada DB sementara; default OQ-19; belum migrate database aplikasi |
