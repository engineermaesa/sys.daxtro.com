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

Task 4 menambahkan `can_send_service_form` dan memperbarui guard tanda tangan v3 (lihat §3.17). `can_close` belum memeriksa syarat Happy Call/survey; syarat itu ditegakkan server saat `POST /close` dan akan masuk `close_blockers` di Task 9.

#### 3.9 Deploy

`php artisan migrate` menjalankan `2026_10_29_000100_seed_aftersales_v3_roles.php`, yang membuat role Head, permission baru, dan menyesuaikan permission role. Idempotent. Instalasi baru lewat `RolePermissionSeeder` menghasilkan hasil yang sama.

#### 3.10 Verifikasi Task 2

- Test baru: `AftersalesRolesV3Test` (seeder idempotent dan matriks permission, capabilities per role, unlock/sign pada dokumen terkunci), tambahan di `TicketAuthorizationTest`. Test existing yang menandatangani sebagai supervisor dengan `manage` diperbarui ke `documents.sign`.
- Regression: lihat §5.
- Belum diverifikasi: migration seeding di MySQL deployment; pengisian user per role oleh tim.

### Task 3 — Fondasi siklus dan migration (§9 PRD) · IMPLEMENTED

#### 3.11 Transisi workflow dan identitas siklus

Memakai **default OQ-19**, belum merupakan jawaban final tim: ticket yang sudah ada saat migration tetap `workflow_version: "legacy"`; ticket yang dibuat setelah migration memakai `"v3"`. Penanda ini disimpan di server, bukan pilihan FE. `POST /tickets` mengabaikan input `workflow_version` dan `cycle_id` dari client. Update ticket tidak memindahkan workflow.

Task 3 adalah fondasi data. Task 4 kini mengaktifkan aturan submit/sign untuk `v3`; stop SLA di survey dan gate Close baru mengikuti task berikutnya. Ticket legacy tidak dikonversi ke aturan baru oleh migration ini.

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
- Diterapkan pada database aplikasi lokal `local_daxx` pada 2026-10-30. Penerapan production belum diverifikasi.

#### 3.14 Verifikasi Task 3

- `TicketCyclesTest`: backfill aktif/soft-deleted, histori ambigu, default workflow baru, siklus awal idempotent, relasi child, asal siklus respons, rollback transaksi, foreign key lintas ticket, unique nomor siklus, migration down/up.
- `ImprovementsTest`: pembuatan API mengabaikan workflow/cycle dari client; Recoming membuat siklus baru dengan visit/log yang sesuai; detail menampilkan urutan siklus; reschedule mempertahankan ID; Recoming berulang saat masih Recoming ditolak.
- Smoke test **MySQL 8.4.3** pada database sementara terpisah: migration up, backfill legacy, default v3/siklus awal, alokasi Recoming, foreign key lintas ticket, down, dan reapply **lulus**. Database sementara sudah dihapus; database aplikasi tidak diubah.
- Regression akhir: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Aftersales tests/Unit/Aftersales` → **OK, 239 tests, 1793 assertions** (SQLite in-memory). Formatter PHP pada file yang berubah dan `git diff --check` lulus.

### Task 4 — Submit, sign, dan publish dokumen (AS-DOC-01..05) · IMPLEMENTED

#### 3.15 Keputusan dan alur v3

User memilih **default PRD v3 untuk OQ-02/03/09/10**: kunjungan awal memakai Send to SPV; Technician bersama Customer tetap mengisi klausul Agreement; tanda tangan Technician/Customer tetap wajib; PDF, lock, dan email tetap berlaku. Badge/modal `Saved!` tidak dipakai pada workflow v3. Pelestarian tanda tangan saat unlock Recoming sudah ditambahkan pada Task 5 (§3.24).

Aturan baru berlaku untuk `workflow_version: "v3"`. Ticket `legacy` mempertahankan draft/save, tanda tangan, publikasi, dan response lama, termasuk modal Agreement `Saved!`.

1. Technician menyiapkan isian, foto, dan tanda tangan Technician/Customer. Endpoint edit online existing tetap menjadi staging **draft**, tidak memfinalisasi dokumen. Draft offline di perangkat tetap tanggung jawab FE; pemeriksaan revision ditambahkan pada Task 11.
2. Technician yang ditugaskan mengirim `POST /tickets/{ticket}/service-form/submit`. Service Form harus lengkap, termasuk inspeksi, diagnosis/foto existing, data unit/service type, serta tanda tangan Technician dan Customer. Tanda tangan SPV belum diperlukan pada tahap ini.
3. Form yang terkirim dikunci untuk edit dan status siklus menjadi `awaiting_spv_signature`. Agreement masih boleh dilengkapi Technician sampai SPV menandatanganinya.
4. SPV **yang ditunjuk pada `supervisor_id`** menandatangani kedua dokumen lewat endpoint signatures existing. SPV tidak dapat menandatangani sebelum submit. Pada saat sign, data dokumen dan tanda tangan Technician/Customer harus lengkap.
5. Setelah kedua dokumen lengkap dan ditandatangani SPV, sistem otomatis mencatat satu publikasi/log, menerbitkan satu survey link, dan membuat antrean email dua dokumen × tiga penerima per siklus. Tidak perlu memanggil `/save` atau `/documentation`.

`documentation_status` pada siklus menjadi sumber status workflow: **`incomplete` → `awaiting_spv_signature` → `documentation_published`**. `progress` ticket tetap mengikuti log operasional existing; menunggu tanda tangan bukan step progress baru.

#### 3.16 Request dan response submit/sign

Semua path relatif terhadap `/api/aftersales`. Auth session dan CSRF mengikuti §1. Tidak ada permission baru.

`POST /tickets/{ticket}/service-form/submit` — hanya Technician yang ditugaskan dengan `aftersales.documents.fill`; sukses **200**. Setelah isian dan tanda tangan dipersiapkan melalui endpoint existing, request nyata yang diuji adalah `{}`. Endpoint juga dapat menerima payload isian seperti `PUT /service-form` agar penulisan isian dan pemeriksaan kelengkapan berlangsung atomik.

| Field opsional | Aturan |
|---|---|
| `service_type`, `unit`, `inspections`, `diagnosis` | Validasi sama seperti `PUT /service-form`; diagnosis masih memakai `problem`, `cause`, `action` sampai Task 6 |
| `signatures` | Array maksimal 2; `role` hanya `technician` / `customer`, masing-masing unik; `signature` PNG data URI; `signer_name` wajib secara bisnis untuk Customer |

Contoh response submit (ID mengikuti fixture API test; waktu ilustratif):

```json
{"status":"success","message":"Service Form sent to SPV Technician.","data":{"ticket_id":1,"cycle_id":1,"documentation_status":"awaiting_spv_signature","submitted_at":"2026-10-30T08:00:00.000000Z","submitted_by":1}}
```

Request submit ulang pada siklus yang sudah terkirim mengembalikan status/timestamp submission yang sama, tanpa menerapkan ulang payload atau membuat publikasi/link/email. Konflik revision untuk payload offline belum tersedia sampai Task 11.

`POST /tickets/{ticket}/documents/{type}/signatures` tetap menerima `{type}` = `service_form` / `agreement`. Nama role API untuk SPV tetap **`supervisor`**. Contoh request PNG valid dari test:

```json
{"role":"supervisor","signature":"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a4X8AAAAASUVORK5CYII="}
```

Sukses **201**, envelope existing `{"status":"success","message":"Signature saved","data":{...}}`. `data.status` tetap `awaiting_spv_signature` setelah tanda tangan SPV pertama, lalu `documentation_published` setelah yang kedua. Kedua urutan tanda tangan didukung. Retry tanda tangan SPV yang sudah difinalisasi mengembalikan tanda tangan existing, tanpa mengganti file atau menggandakan efek publikasi.

Signature yang difinalisasi mengisi `saved_at` / `saved_by_user_id` internal dokumen. Form yang baru disubmit tetap `saved_at: null`. Kolom DB `status` pada dokumen mempertahankan state internal draft/saved, ditambah pending untuk lock; FE menggunakan status workflow yang diserialisasi API, bukan membaca schema DB.

#### 3.17 Perubahan read API dan capabilities

| Endpoint / field | Perilaku v3 |
|---|---|
| `GET /tickets`, `GET /tickets/{ticket}` | Tambahan `documentation_status`; status nested form/agreement pada detail dan ringkasan `documents` mengikuti tiga status workflow |
| `GET /tickets/{ticket}/documents` | `data.documentation_status`; masing-masing `data.documents.{type}.status` mengikuti status siklus |
| `GET /tickets/{ticket}/service-form`, `.../agreement`, response signatures/unlock | `data.status` mengikuti status siklus |
| `GET .../service-form` | Tambahan `submission_completeness`, yang mengecualikan tanda tangan SPV; `completeness` tetap menghitung seluruh tanda tangan |
| `data.current_cycle`, `data.cycles[]` pada ticket detail | Tambahan `documentation_status`, `service_form_submitted_at`, `service_form_submitted_by` |
| `data.capabilities.can_send_service_form` pada ticket detail | True untuk Technician pemilik saat siklus incomplete, form belum terkunci, progress bukan Closed/Recoming, dan snapshot isian server lengkap tanpa SPV |
| `data.capabilities.can_sign_service_form` / `can_sign_agreement` | Hanya SPV yang ditugaskan, setelah submission, dokumen lengkap dan belum difinalisasi |
| `data.can.submit` pada ringkasan Service Form | Kondisi sama dengan `can_send_service_form` |
| `data.can.save` pada dokumen v3 | Selalu false; tombol lama Save tidak dipakai |

Edit isian, tanda tangan Technician/Customer, diagnosis, dan upload foto Service Form ditolak setelah submit; termasuk upload melalui camera link yang dibuat sebelum submit. PDF belum dapat diunduh sampai publikasi kedua dokumen selesai. Permission baca dan pembatasan data survey Technician tetap seperti Task 2.

#### 3.18 Error dan jalur lama

Error workflow baru memakai `{"status":"error","code":"...","message":"..."}`. Error kelengkapan juga dapat membawa `errors` per dokumen.

| HTTP | `code` | Kondisi |
|---|---|---|
| 409 | `legacy_document_workflow` | Submit endpoint baru dipakai untuk ticket legacy |
| 409 | `service_form_not_submitted` | SPV sign sebelum Technician submit |
| 409 | `document_save_disabled` | Endpoint `/documents/{type}/save` dipakai untuk v3; gunakan submit/sign |
| 409 | `manual_documentation_disabled` | Endpoint `/documentation` manual dipakai untuk v3; tidak menulis log, part, foto, atau link |
| 409 | `document_locked` | Tanda tangan diubah pada dokumen yang terkunci |
| 409 | `cycle_already_published` | Mengedit/mengirim ulang siklus published setelah unlock; jadwalkan siklus Recoming baru |
| 409 | `agreement_already_published` | Unlock Agreement yang sudah published atau berada pada siklus Recoming; Agreement tetap berlaku |
| 409 | `documentation_not_published` | PDF diminta sebelum publikasi kedua dokumen selesai |
| 422 | `service_form_incomplete` | Submit dengan field/foto/diagnosis/tanda tangan Technician atau Customer belum lengkap |
| 422 | `document_incomplete` | Dokumen belum lengkap ketika SPV menandatangani / ketika publikasi diperiksa |

401/403/404 tetap mengikuti auth, permission/assignment, dan keberadaan resource. Validasi bentuk payload tetap Laravel 422 standar. Ticket Closed/Recoming dan jalur edit existing dapat mengembalikan 422 tanpa `code`, mengikuti guard existing.

#### 3.19 Atomicity, migration, dan batas task

- Migration `2026_10_30_000100_add_document_publication_workflow.php` menambahkan metadata submission pada `ticket_cycles`, tabel `ticket_document_publications` dengan **unique `cycle_id`** dan FK gabungan pemilik ticket/siklus, serta `publication_id` pada dispatch email. Status DB form/agreement diperluas menjadi string agar mendukung pending tanpa mengubah nilai legacy.
- Submit/sign mengunci ticket dan dokumen, sehingga dua tanda tangan SPV tidak bisa menerbitkan dua publikasi. Signature terakhir, publikasi, log, link, status siklus, dan enam row dispatch ditulis dalam satu transaksi. Queue email dijalankan setelah commit. Email yang kosong tetap membuat row `skipped`, sesuai perilaku existing.
- Penjadwalan Recoming memeriksa ulang lock form di dalam transaksi; form pending harus di-unlock Admin lebih dulu. Reschedule tidak membuat publikasi baru.
- Jika transaksi gagal, signature terakhir dan metadata publikasi dibatalkan, file signature baru dibersihkan, dan tidak ada job email yang dikirim. Penghapusan file signature v3 yang diganti/di-unlock dilakukan setelah commit.
- Publikasi historis dari log aktif yang sudah memiliki `cycle_id` di-backfill sekali per siklus; migration tidak membuat link/email baru. Log soft-deleted atau tanpa identitas siklus tidak dianggap publikasi aktif. Link/dispatch historis tidak ditebak relasinya ke publikasi baru.
- Rollback menghapus metadata task 4, mengembalikan pending form menjadi draft, dan memulihkan enum draft/saved. Dokumen, signature, log, survey link, dan dispatch existing dipertahankan.
- Jalankan migration pada jendela maintenance seperti Task 3. **Sudah diterapkan ke database aplikasi lokal `local_daxx` pada 2026-10-30; production belum diverifikasi.**
- Task 5 menambahkan unlock dengan pelestarian tanda tangan dan aturan Recoming baru; Task 8 menangani survey phone call; Task 11 menangani revision/conflict offline. SLA, Happy Call, dan Close belum berubah pada Task 4.

#### 3.20 Verifikasi Task 4

- `DocumentWorkflowV3Test`: kelengkapan nyata unit/diagnosis/foto/signature, submit atomik/idempotent, freeze edit, kedua urutan sign SPV, retry, otorisasi/assignment/guest/Closed, bypass manual/save, PDF gate, unique publikasi per siklus, publikasi siklus berikutnya, kegagalan dispatch yang rollback signature/file/link/log, serta migration down/up tanpa pengiriman ulang email/link.
- Smoke test **MySQL 8.4.3** pada database sementara: perluasan/pemulihan enum status, backfill publikasi, unique per siklus, FK lintas ticket, dan down/up **lulus**. Database sementara dihapus; database aplikasi tidak diubah.
- Regression akhir: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Aftersales tests/Unit/Aftersales` → **OK, 255 tests, 2009 assertions** (SQLite in-memory). Test khusus v3: **16 tests, 216 assertions**. Pemeriksaan formatter PHP pada file task 4 dan `git diff --check` lulus. Uji konkurensi dua request sungguhan dan e2e FE tetap masuk Task 12.

### Task 5 — Recoming v3 dan pelestarian tanda tangan (AS-REC-01/03/06/07) · IMPLEMENTED

#### 3.21 Keputusan dan kompatibilitas

User memilih **default PRD untuk OQ-06**: jadwal phone call dicatat manual dengan tanggal/jam tersendiri, tanpa integrasi telepon, Technician mengikuti ticket awal. Foto dan part tidak wajib untuk phone call. Default OQ-10 yang telah disepakati juga diterapkan: tanda tangan SPV, Technician, dan Customer dipertahankan saat unlock Service Form untuk Recoming.

Tipe baru berlaku untuk ticket `v3`. Ticket `legacy` tetap menerima request visit-site lama tanpa `recoming_type`; `phone_call` ditolak pada workflow legacy. Semua aksi create/reschedule kini memerlukan `aftersales.tickets.manage` langsung di controller, termasuk legacy. Permission Technician lama `aftersales.tickets.recoming` tidak dapat dipakai untuk melewati guard tersebut.

Reason untuk **request baru**, baik v3 maupun legacy:

| Nilai API | Label |
|---|---|
| `sparepart_unavailable` | Part unavailable |
| `further_work_needed` | Further work needed |
| `site_access` | Site access or condition |
| `unfinished_previous_work` | Unfinished from previous work |

`other` historis, termasuk row soft-deleted, dipertahankan dan tetap terbaca. Memilih `other` untuk request baru menghasilkan Laravel 422 `errors.reason`.

#### 3.22 Create Recoming

`POST /tickets/{ticket}/recoming` — permission `aftersales.tickets.manage`, sukses **201**. Untuk v3:

| Field | Aturan |
|---|---|
| `recoming_type` | Wajib: `phone_call` / `visit_site` |
| `reason` | Wajib, salah satu empat nilai di §3.21 |
| `planned_visit_at` | Wajib hanya untuk `visit_site`; tanggal hari ini atau sesudahnya; dilarang terisi untuk phone call |
| `planned_call_at` | Wajib hanya untuk `phone_call`; tanggal **dan jam**, tidak boleh sebelum waktu sekarang; dilarang terisi untuk visit site |
| `notes` | Opsional, string maksimal 10000 |
| `assigned_technician_id`, `supervisor_id` | Dilarang terisi; assignment mengikuti ticket awal |

Waktu tanpa timezone dianggap Asia/Jakarta. `planned_call_at` menerima SQL datetime (`2026-11-01 14:30:00`), `datetime-local` (`2026-11-01T14:30`), dan ISO dengan offset/UTC termasuk pecahan detik (`2026-11-01T07:30:00.000Z`). Tanggal tanpa jam ditolak.

Request phone call yang diuji:

```json
{"recoming_type":"phone_call","reason":"unfinished_previous_work","planned_call_at":"2026-11-01 14:30:00"}
```

Response (subset):

```json
{"message":"Recoming scheduled successfully","data":{"data":{"id":1,"ticket_id":1,"cycle_id":2,"recoming_type":"phone_call","visit_no":null,"ticket_visit_id":null,"planned_visit_at":null,"planned_call_at":"2026-11-01T07:30:00.000000Z","reason":"unfinished_previous_work","from_step":"repair","created_by_user_id":1}}}
```

Request visit site:

```json
{"recoming_type":"visit_site","reason":"further_work_needed","planned_visit_at":"2026-11-01 09:00:00","notes":"Follow-up at customer site"}
```

- Create v3 boleh dari progress mana pun sebelum Closed, selama bukan Recoming yang masih aktif dan Service Form telah di-unlock. Request saat masih Recoming ditolak 422; gunakan reschedule untuk siklus aktif.
- `visit_site` membuat tepat satu `ticket_visits` yang menunjuk siklus baru. `ticket_visit_id` adalah ID visit tersebut; `visit_no` mengikuti jumlah visit fisik, bukan nomor siklus.
- `phone_call` **tidak membuat visit fisik**. `visit_no`, `ticket_visit_id`, dan `planned_visit_at` bernilai null; `planned_call_at` menyimpan jadwal telepon.
- Alokasi siklus, visit fisik jika diperlukan, Recoming, deaktivasi survey link lama, dan log dilakukan dalam satu transaksi dengan row lock ticket. Kegagalan membatalkan seluruh perubahan. Agreement dan assignment tidak diubah.

#### 3.23 Reschedule, read API, dan capability

`POST /tickets/{ticket}/recoming/reschedule` — hanya Admin melalui `aftersales.tickets.manage`. Request hanya membawa jadwal sesuai tipe existing, serta `note` opsional maksimal 1000:

```json
{"planned_call_at":"2026-11-02T04:00:00.000Z","note":"Customer requested a later call"}
```

Untuk visit site gunakan `planned_visit_at`. `recoming_type` tidak dapat diubah melalui reschedule. Sukses **200** dengan `{"message":"Follow-up schedule rescheduled successfully","data":{"data":{...recoming...}}}`. ID Recoming, siklus, dan visit tetap sama.

- Reschedule hanya saat progress masih Recoming dan **sebelum hari jadwal tiba** menurut Asia/Jakarta, untuk kedua tipe. Pada hari jadwal itu sendiri atau setelah Repair, ditolak 422. Guard diperiksa ulang setelah ticket dikunci.
- `POST /tickets/{ticket}/visits` tidak dapat dipakai untuk melewati batas reschedule: pada siklus Recoming v3 visit-site, endpoint ini menggunakan guard yang sama dan mengubah visit yang ditautkan. Setelah Repair/publikasi, perubahan jadwal visit siklus itu ditolak; buat siklus baru untuk kunjungan berikutnya.
- Pada siklus phone call, `/visits` dan `/on-site` ditolak **409 `phone_call_has_no_visit`**. Untuk pekerjaan fisik selanjutnya, jadwalkan siklus Recoming `visit_site` baru.
- Reschedule visit-site dengan relasi visit yang hilang ditolak 409; tidak menebak atau mengubah visit lain. Pergantian siklus di tengah request juga ditolak 409. Error validasi input memakai format Laravel 422; user selain Admin mendapat 403.
- Nested `recomings[]` serta `current_recoming` pada list/detail ticket membawa `recoming_type`, `planned_call_at`, dan `ticket_visit_id`, di samping field existing. `current_recoming` tetap hanya non-null saat progress Recoming.
- `visit_history` dan `visit_date` tetap berkaitan dengan visit fisik. Phone call tidak ditampilkan sebagai kunjungan fisik baru; jadwal telepon dibaca dari `planned_call_at`.
- `capabilities.can_trigger_recoming` hanya untuk Admin pada ticket terbuka yang tidak sedang Recoming. `can_reschedule` hanya untuk Admin dan jadwal yang masih boleh dipindah. `can_schedule` false pada phone-call dan pada siklus visit-site yang sudah tidak boleh di-reschedule.
- Scheduler tidak membawa phone call ke On Site berdasarkan visit lama. Task 6 kini membuat seluruh Recoming v3 menunggu edit pertama Technician untuk masuk Repair (§3.29).

#### 3.24 Unlock dan submit ulang

`POST /tickets/{ticket}/documents/service_form/unlock` tetap Admin-only. Untuk form v3 yang sudah published atau berada pada siklus Recoming, **semua tanda tangan existing dipertahankan**, termasuk ID, nama/actor, waktu, dan file PNG. Isian inspeksi/diagnosis serta Agreement tidak dihapus atau dibuat ulang.

- Unlock sebelum menjadwalkan Recoming tetap diperlukan (RC-04). Unlock tidak membuat siklus baru. Publikasi lama tetap tercatat; mengedit ulang sebelum siklus baru dijadwalkan tetap diblok.
- Setelah siklus lanjutan mencapai progress yang mengizinkan submit, Technician memakai `POST .../service-form/submit` existing. Jika tanda tangan SPV masih ada dan form lengkap, sistem menyimpan form lalu memublikasikan siklus tersebut lewat jalur idempotent Task 4, **tanpa tanda tangan SPV baru**. Agreement lama tetap berlaku.
- Untuk `visit_site`, publikasi siklus baru menerbitkan satu link survey baru. Untuk `phone_call`, publisher tidak menerbitkan link site; metadata publikasi memiliki `satisfaction_link_id: null`. Template dan pencatatan survey phone call tetap Task 8.
- Pada phone call, pemeriksaan kelengkapan tidak mewajibkan foto inspeksi/diagnosis. `groups[].items[].fields[].photo_required` bernilai false dan `diagnosis.photos_required` false pada response Service Form. Visit site tetap mengikuti ketentuan foto existing.
- Unlock koreksi sebelum publikasi pertama pada siklus awal tetap menghapus signature form yang dikoreksi. Unlock pada workflow legacy juga mempertahankan perilaku lama (menghapus signature). Pelestarian Recoming tidak diterapkan secara retroaktif pada ticket legacy.

#### 3.25 Migration dan batas task

Migration baru: `2026_10_30_000200_add_recoming_v3_scheduling.php`.

- `ticket_recomings`: tambah `recoming_type` default `visit_site`, `planned_call_at` nullable/index, `ticket_visit_id` nullable/FK; `visit_no` dan `planned_visit_at` menjadi nullable; `reason` diperluas menjadi string untuk reason baru.
- Data lama, termasuk soft-deleted, tetap visit-site dan reason historis tidak diubah. Relasi visit hanya di-backfill bila ada **tepat satu** visit dengan ticket dan `cycle_id` yang sama. Mapping yang ambigu tetap null; jalur legacy mempertahankan perilaku reschedule existing.
- Rollback mengembalikan schema lama bila datanya masih kompatibel. Jika sudah ada phone call, reason `unfinished_previous_work`, atau jadwal/visit_no null, rollback **ditolak sebelum perubahan schema**. Data baru perlu dipertahankan/diekspor dan ditangani dengan migration rollback eksplisit; tidak ada konversi jadwal telepon menjadi visit palsu atau penghapusan data otomatis.
- **Sudah dijalankan pada database aplikasi lokal `local_daxx` pada 2026-10-30**. Tidak ada seeder/permission baru pada task ini.
- Task 6 menambahkan scope siklus pada temuan existing dan Repair pada edit pertama. Task 7 mencakup setup/perhitungan SLA Recoming. Task 8 mencakup template/response survey phone call. Task 5 tidak membuat integrasi telepon atau pencatatan survey otomatis.

#### 3.26 Verifikasi Task 5

- `RecomingV3Test`: pemisahan call/visit, timezone dan format datetime FE, empat reason, penolakan perubahan assignment, guard Admin meskipun Technician memiliki permission lama, kedua jalur reschedule dan cutoff hari/Repair, tidak ada On Site dari visit lama, rollback transaksi, kompatibilitas legacy, dan migration roundtrip/proteksi rollback.
- `DocumentWorkflowV3Test`: signature/file/Agreement tidak berubah setelah unlock dan submit Recoming; publikasi baru tanpa sign SPV ulang; foto phone call opsional; tidak ada link survey site untuk phone call; koreksi awal dan legacy tetap sesuai guard lama.
- Smoke test **MySQL 8.4.3** pada database sementara: migration up, backfill data aktif/soft-deleted, penyimpanan phone call/reason baru, penolakan rollback yang membuang data, down/up kompatibel, dan penolakan mapping visit ambigu **lulus**. Database uji telah dihapus; database aplikasi tidak diubah.
- Regression akhir: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Aftersales tests/Unit/Aftersales` → **OK, 268 tests, 2154 assertions** (SQLite in-memory). Formatter PHP pada file task 5 dan `git diff --check` lulus. Uji konkurensi dua request sungguhan dan e2e FE tetap Task 12.

### Task 6 — Temuan per siklus dan Repair pada edit pertama (AS-REC-05) · IMPLEMENTED

#### 3.27 Penyesuaian desain yang disepakati

Setelah membandingkan existing dengan PRD, user menyetujui pemakaian ulang diagnosis existing. **Tidak dibuat tabel `recoming_findings` paralel.** `ticket_diagnosis_entries` diperluas dengan `cycle_id`; Service Form, inspeksi, histori, dan foto existing tetap dipakai. Ini adalah penyesuaian desain penyimpanan terhadap §9 PRD, dengan kebutuhan fungsional AS-REC-05 tetap diterapkan.

- Satu temuan per kombinasi `(ticket_service_form_id, field, cycle_id)` untuk v3, dilindungi unique index dan FK ke `ticket_cycles`.
- Storage tetap memakai field `problem`, **`cause`**, `action`. API menerima `root_cause` sebagai alias `cause`; tidak ada duplikasi atau penulisan ulang teks histori.
- Unique index lama `(form, field, visit_no)` tetap dipertahankan. Pada v3, `visit_no` diagnosis adalah nomor kelompok catatan kompatibilitas yang dialokasikan server, **bukan identitas siklus atau bukti visit fisik**. Pakai `cycle_id` untuk grouping v3, terutama pada phone call. Nomor kelompok baru dialokasikan dari histori form agar tidak berbenturan dengan catatan lama.
- `visit_no` pada `ticket_recomings` tetap bermakna nomor visit fisik sebagaimana Task 5; phone call tetap null. Field ini berbeda konteks dari nomor kelompok diagnosis.
- Perilaku diagnosis legacy tetap berdasarkan visit dan histori existing. Tidak ada perpindahan foto atau part ke tabel/model baru.

#### 3.28 Request, response, dan kelengkapan

Endpoint tetap `PUT /tickets/{ticket}/service-form` atau `POST /tickets/{ticket}/service-form/submit`, hanya Technician yang ditugaskan dengan `aftersales.documents.fill`. Tidak ada endpoint temuan baru atau permission baru.

Contoh request yang diuji:

```json
{"diagnosis":[{"field":"problem","body":"New cycle problem"},{"field":"root_cause","body":"New cycle root_cause"},{"field":"action","body":"New cycle action"}]}
```

- Field lama `cause` tetap diterima. Mengirim `cause` dan `root_cause` sekaligus ditolak Laravel **422** sebagai field yang duplikat, setelah normalisasi alias.
- `id` opsional hanya untuk memperbarui temuan pada **form, field, dan siklus aktif yang sama**. Tanpa ID, server memperbarui/membuat temuan berdasarkan field dan siklus aktif.
- ID milik siklus lama, histori tanpa pemetaan siklus, atau field berbeda menghasilkan **409 `finding_not_current`**. ID dari form lain / tidak ditemukan menghasilkan **404**. `cycle_id` dan nomor kelompok ditentukan server; input client tidak dapat mengubah kepemilikannya.
- Tiga teks `problem`, `cause`/`root_cause`, dan `action` wajib lengkap pada **siklus aktif** sebelum submit/publikasi v3. Teks dari siklus lama tetap tampil sebagai histori dan tidak memenuhi kelengkapan siklus baru.
- Saat finalisasi v3, hanya temuan siklus aktif yang dikunci. Foto tetap mengikuti aturan existing: ketentuan site tetap berlaku, phone call tidak wajib foto, dan foto lama yang terkunci tetap readonly.
- Upload diagnosis photo `POST .../service-form/diagnosis/photos` juga menerima `field: "root_cause"`, disimpan sebagai `cause`.

`GET .../service-form` dan response edit tetap memakai envelope existing. Tambahan pada `data.diagnosis`:

| Field | Arti |
|---|---|
| `current_cycle_id` | ID siklus aktif untuk v3; null untuk legacy |
| `current_findings` | Temuan siklus aktif dengan key kanonis `problem`, `root_cause`, `action`; array kosong jika belum diisi; null untuk legacy |
| `field_aliases` | `{"root_cause":"cause"}` |
| `fields[].key` | Tetap `problem`, `cause`, `action` untuk kompatibilitas FE/template/PDF existing |
| `fields[].canonical_key` | Key kanonis; `cause` dipetakan menjadi `root_cause` |
| `fields[].entries[]` | Histori tetap ada; ditambah `cycle_id` dan `field` kanonis. Entri siklus lama/tanpa mapping pada v3 memiliki `locked: true` |

Contoh response edit v3 (subset):

```json
{"status":"success","data":{"diagnosis":{"current_cycle_id":2,"field_aliases":{"root_cause":"cause"},"current_findings":[{"id":4,"cycle_id":2,"field":"problem","body":"New cycle problem","locked":false},{"id":5,"cycle_id":2,"field":"root_cause","body":"New cycle root_cause","locked":false},{"id":6,"cycle_id":2,"field":"action","body":"New cycle action","locked":false}]}}}
```

Metadata tanggal temuan v3 memakai actual start visit siklus tersebut jika tersedia, kemudian jadwal visit/call siklus tersebut; siklus awal mempertahankan fallback waktu visit existing. Histori visit dari siklus lain tidak dipakai sebagai tanggal temuan Recoming baru.

#### 3.29 Edit pertama, Repair, dan scheduler

Kolom baru pada `ticket_cycles`: `service_form_first_edited_at` dan `service_form_first_edited_by`. Keduanya juga tersedia pada `current_cycle` / `cycles[]` di ticket detail. `first_draft_at` lama tetap ada untuk umur form/template; tidak di-reset setiap Recoming.

- Pada siklus Recoming v3, perubahan pertama oleh Technician mencatat marker per siklus dan log `repair` dengan actor serta `cycle_id` yang benar. Tidak perlu On Site manual dan tidak perlu menunggu scheduler/hari visit untuk mencatat bahwa Technician sudah mulai mengedit.
- Edit yang dihitung mencakup perubahan isian form/inspeksi/diagnosis, capture tanda tangan Technician/Customer pada Service Form, dan perubahan foto form yang diotorisasi. Pembacaan form, pembuatan link kamera, payload kosong, serta penyimpanan nilai bisnis yang sama tidak memicu Repair.
- Edit berikutnya tidak menggandakan marker/log otomatis. Perubahan siklus berikutnya memiliki marker tersendiri. Reschedule ditolak setelah edit pertama karena progress sudah keluar dari Recoming.
- Submit pertama dapat membawa temuan lengkap secara langsung: penulisan temuan → Repair → validasi → publikasi berada dalam satu transaksi. Bila field tidak valid, temuan belum lengkap, atau finalisasi gagal, perubahan data, marker, dan log Repair ikut dibatalkan.
- Ticket/form dikunci dengan urutan yang konsisten. Jalur progress manual v3 juga mengunci ticket dan memeriksa ulang state, sehingga request On Site lama tidak dapat menimpa Repair yang sudah tercatat.
- `tickets:resume-recoming` **melewati semua ticket v3**, baik site maupun phone call. Hari jadwal tiba saja tidak menambahkan log On Site/Repair. Ticket legacy tetap mendapat On Site ketika visit due, dengan pemeriksaan ulang di bawah row lock agar eksekusi berulang tidak menggandakan transisi.
- Awal kunjungan pertama dan workflow legacy mempertahankan alur existing; perubahan Repair per siklus berlaku untuk Recoming v3.

#### 3.30 Migration, histori, dan batas task

Migration: `2026_10_30_000300_scope_diagnosis_to_ticket_cycles.php`.

- Tambah `cycle_id` nullable/FK dan unique index temuan per siklus, serta metadata edit pertama pada `ticket_cycles`. Tidak ada seed baru.
- Backfill hanya bila ticket memiliki tepat satu siklus dan form tidak memiliki beberapa entri untuk field yang sama. Ticket/form soft-deleted tetap ikut diperiksa. ID, teks, key `cause`, tanggal, dan nomor kelompok lama dipertahankan.
- Histori dengan beberapa siklus atau kelompok field yang ambigu tetap `cycle_id: null`. Tidak ditebak dari `visit_no`, tidak digabung, dan tidak dihapus. Pada v3, histori tersebut readonly dan temuan siklus aktif perlu diisi secara eksplisit.
- Rollback tanpa temuan Recoming bersiklus dapat menghapus metadata baru sambil mempertahankan catatan diagnosis. Jika sudah ada temuan yang terikat ke siklus nomor >1, rollback ditolak sebelum DDL agar kepemilikan histori tidak hilang; perlu migration rollback eksplisit.
- **Migration task 6 sudah diterapkan ke database aplikasi lokal `local_daxx` pada 2026-10-30.** Gunakan jendela maintenance untuk schema/backfill seperti task sebelumnya.
- Setup/perhitungan SLA tetap Task 7; varian dan respons survey tetap Task 8; Happy Call/Close dan revision offline tetap task berikutnya.

#### 3.31 Verifikasi Task 6

- Test API: temuan baru memicu Repair satu kali, alias `root_cause`/`cause`, histori readonly dan isolasi form lain, duplicate alias, no-op/read/validasi gagal tidak memulai Repair, rollback submit, dua siklus phone call tanpa visit, tanggal berdasarkan visit siklus, capture foto sebagai edit, serta scheduler legacy/v3.
- Test migration: backfill termasuk soft-deleted, histori ambigu, unique temuan per siklus terpisah dari nomor visit legacy, roundtrip yang mempertahankan teks, dan proteksi rollback.
- Smoke test **MySQL 8.4.3** pada database sementara: migration, backfill, unique/FK, down/up, marker/log Repair idempotent, dan proteksi rollback **lulus**. Database sementara dihapus; database aplikasi tidak diubah.
- Regression akhir: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Aftersales tests/Unit/Aftersales` → **OK, 281 tests, 2416 assertions** (SQLite in-memory). Formatter PHP pada file task 6 dan `git diff --check` lulus. Uji konkurensi dua request sungguhan dan e2e FE tetap Task 12.

### Task 7 — SLA per siklus (IMPLEMENTED)

AS-REC-04, AS-SLA-01/02. Default **OQ-14 disetujui user**: reschedule menghitung ulang deadline berbasis durasi dari jadwal baru; deadline eksplisit tetap. Aturan berikut berlaku untuk `workflow_version: "v3"`; perhitungan legacy tetap memakai Close.

#### 3.32 Setup SLA Recoming wajib

`POST /tickets/{ticket}/recoming` tetap khusus Admin dengan `aftersales.tickets.manage`. Tambahkan setup SLA pada request Task 5. Field berada pada level teratas, bukan object bersarang.

Contoh request durasi yang diuji:

```json
{"recoming_type":"phone_call","reason":"unfinished_previous_work","planned_call_at":"2026-11-02 14:30:00","sla_mode":"duration","sla_value":2,"sla_unit":"day"}
```

Alternatif deadline:

```json
{"recoming_type":"visit_site","reason":"further_work_needed","planned_visit_at":"2026-11-02 09:00:00","sla_mode":"deadline","sla_due_at":"2026-11-04 17:00:00"}
```

| Field | Aturan |
|---|---|
| `sla_mode` | Wajib `duration` atau `deadline` |
| `sla_value`, `sla_unit` | Wajib untuk duration; integer positif dan `hour`/`day`; tidak boleh diisi untuk deadline |
| `sla_due_at` | Wajib untuk deadline, minimal waktu jadwal; tidak boleh diisi untuk duration |

Durasi dihitung dari `planned_call_at` atau `planned_visit_at` dalam Asia/Jakarta. Deadline harus dalam rentang timestamp database yang didukung. Validasi gagal menghasilkan **422** dengan `errors.<field>`; tidak membuat cycle/visit/log dan tidak mengakhiri siklus sebelumnya.

Response tetap **201** dengan envelope `data.data` existing. Tambahan `data.data.cycle` berisi siklus hasil setup, termasuk `sla_mode`, `sla_started_at`, `sla_value`, `sla_unit`, `sla_due_at`, dan `sla_stopped_at`. `sla_satisfaction_id` adalah pointer internal dan disembunyikan dari resource, termasuk histori `cycles[]`.

Saat Recoming berhasil disimpan, siklus lama mendapat `ended_at`, SLA lamanya berhenti berjalan pada batas itu jika belum dihentikan survey. Siklus baru mempunyai SLA sendiri, dan `tickets.sla_stopped_at` di-reset. `tickets.sla_value/unit/due_at` tetap merepresentasikan **setup awal**, sehingga histori tidak ditimpa oleh setup Recoming.

#### 3.33 Reschedule dan edit ticket

`POST /tickets/{ticket}/recoming/reschedule` tetap mengikuti permission dan batas waktu Task 5. Untuk siklus yang sudah mempunyai setup, kirim hanya jadwal baru dan note opsional; field `sla_*` tidak boleh diisi (422).

- `duration`: `sla_started_at` bergeser ke jadwal baru dan `sla_due_at` dihitung ulang dengan nilai/unit semula.
- `deadline`: `sla_started_at` bergeser, tetapi deadline tidak berubah. Jadwal baru yang melewati deadline tetap memakai deadline tersebut dan dapat menghasilkan overdue.
- Jalur site `POST /tickets/{ticket}/visits` saat reschedule memakai aturan yang sama.
- Siklus Recoming v3 historis yang belum mempunyai setup tidak diberi deadline rekaan. Jika masih memenuhi syarat reschedule, request reschedule wajib membawa setup SLA lengkap seperti §3.32.
- Create ticket menyinkronkan SLA awal ke cycle 1. Edit detail ticket biasa dengan setup SLA yang sama tidak menggeser deadline. Perubahan setup awal setelah survey menghentikan SLA atau setelah Recoming dimulai ditolak 422 (`errors.sla_due_at`); detail lain tetap dapat diperbarui.

#### 3.34 Survey, review, dan perhitungan ulang

Stop SLA memakai **`filled_at` response terakhir** dalam siklus yang sama, diurutkan `filled_at DESC, id DESC`, tanpa response soft-deleted/rejected. Status yang menghentikan SLA adalah `normal`, `needs_review`, dan `reviewed`. `needs_review` menghentikan SLA secara provisional agar input Admin segera menghentikannya; ini bukan perubahan aturan agregasi/rating valid yang ditangani Task 8.

- Submit publik dan on-behalf Admin memperbarui survey, pointer, dan stop SLA dalam transaksi yang sama. Kegagalan menyimpan jawaban membatalkan semuanya.
- Approve mempertahankan waktu submit sebagai stop; waktu approval tidak memperpanjang SLA.
- Reject menghitung ulang: kembali ke response yang masih diterima sebelumnya; jika tidak ada, stop dihapus dan SLA berjalan lagi memakai start/deadline semula. Reject tidak memberikan durasi baru.
- Siklus yang sudah diakhiri tetap tertutup pada `ended_at`. Review response lama tidak mengubah mirror `tickets.sla_stopped_at` milik siklus terbaru. Soft delete/restore response melalui model juga menghitung ulang.
- Submit link milik siklus lama atau reject response dari siklus lama menghasilkan **409 `survey_cycle_not_current`**, tanpa menerbitkan link untuk siklus baru. Guard review existing (hanya needs_review, response terbaru, progress yang sesuai) tetap berlaku.
- Reject survey site menerbitkan link pengganti seperti existing. Phone call tidak membuat link pengganti; endpoint/template input survey phone call tetap Task 8.

Urutan lock pada submit/review: ticket dahulu, kemudian link/response; recompute mengunci ticket dan cycle. Perubahan siklus dan stop SLA memakai transaksi yang sama agar histori tidak tertimpa oleh request lama.

#### 3.35 Resource SLA

`GET /tickets` dan `GET /tickets/{ticket}` menambahkan **`current_sla`**; null untuk legacy. `current_cycle`/`cycles[]` tetap membawa snapshot setup SLA tiap siklus.

Contoh subset summary pada v3 yang dihentikan survey:

```json
{"current_sla":{"cycle_id":2,"cycle_no":2,"mode":"duration","state":"stopped","label":"Stopped","started_at":"2026-11-02T07:30:00.000000Z","due_at":"2026-11-04T07:30:00.000000Z","stopped_at":"2026-11-02T08:30:00.000000Z","closed_at":null,"elapsed_seconds":3600,"is_overdue":false,"result":"On Time"},"sla_status":"On Time"}
```

| Field | Arti |
|---|---|
| `state` | `running`, `stopped`, `closed`, atau `not_configured` |
| `label` | `Running` untuk siklus awal berjalan; `Cycle n` untuk Recoming berjalan; `Stopped`/`Closed`/`Not configured` untuk state lain |
| `started_at`, `due_at`, `stopped_at`, `closed_at` | Waktu SLA, berbeda dari waktu cycle dibuat; serialized timestamp mengikuti resource existing |
| `elapsed_seconds` | Durasi nonnegatif dari start sampai stop, akhir cycle, atau waktu sekarang; membeku saat stop/akhir cycle |
| `is_overdue` | Pembanding batas waktu tersebut terhadap deadline; null bila setup belum tersedia |
| `result` | `On Time`/`Overtime` setelah survey menghentikan SLA; null sebelum stop |

`sla_status` mempertahankan nilai kompatibel `Pending`/`On Time`/`Overtime` (atau null tanpa deadline). Pada v3 hasil sudah tersedia setelah survey, tanpa menunggu Closed. Technician hanya mendapat metadata SLA; pointer/isi survey tidak ditambahkan ke resource Technician.

#### 3.36 Migration dan verifikasi Task 7

Migration: `2026_10_30_000400_add_cycle_sla_setup.php`; menambah `sla_mode`, `sla_started_at`, dan FK internal `sla_satisfaction_id` pada `ticket_cycles`. Tidak ada seed baru.

- Backfill hanya ticket v3: start dari siklus awal/jadwal Recoming yang terhubung, deadline existing dipertahankan sebagai mode deadline, dan stop dihitung dari response yang memenuhi aturan §3.34. Response setelah `ended_at` tidak dihitung untuk siklus arsip. Legacy tidak dihitung ulang.
- Deadline Recoming historis yang belum ada tetap null. Rollback menghapus metadata baru/FK, mempertahankan response, deadline, serta timestamp stop existing; mode durasi tidak dapat dipulihkan dari metadata yang sudah dihapus.
- **Migration task 7 sudah diterapkan ke database aplikasi lokal `local_daxx` pada 2026-10-30.** Backup SQL dibuat sebelum migration; cache dibersihkan dan aplikasi kembali live. Tidak ada seed baru.
- Test mencakup setup wajib/eksklusif, validasi/rentang deadline, reschedule durasi/deadline, penutupan siklus, stop provisional, approve/reject/fallback, histori terisolasi, rollback submit, soft delete/restore, SLA awal tidak bergeser, dan legacy.
- Smoke MySQL **8.4.3 lulus** pada database sementara: migration/backfill/FK/down-up, reschedule kedua mode, dan stop/approve/reject/delete/restore survey. Database sementara dihapus; database aplikasi tidak diubah.
- Regression: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Aftersales tests/Unit/Aftersales` → **OK, 297 tests, 2526 assertions** (SQLite in-memory). Formatter PHP dan `git diff --check` lulus. Uji konkurensi dua request sungguhan dan e2e FE tetap Task 12.

### Task 8 — Survey site/phone call dan skor per ticket (IMPLEMENTED)

AS-REC-02, AS-SVY-01/02/03. Default **OQ-04/08/18 disetujui user**: Admin menginput jawaban Customer untuk phone call, tujuh aspek memakai skala 1–3, dan skor per ticket merupakan tambahan atas Overall Rating. Default OQ-06 tetap manual tanpa integrasi telepon. Teknisi tidak menginput survey atau membaca jawaban/review note.

#### 3.37 Template berdasarkan varian dan snapshot per siklus

- `ref_survey_templates.variant`: `site` atau `phone_call`. Template existing dan histori link/response tetap site, dengan ID, content, jawaban, dan versi existing dipertahankan.
- Siklus awal memakai site; Recoming `visit_site` memakai site; Recoming `phone_call` memakai phone_call.
- `GET /survey-templates?variant=phone_call` memfilter versi dan audit varian itu; default query adalah `site` untuk kompatibilitas. `POST /survey-templates/draft` menerima `{"variant":"phone_call"}`; tanpa variant berarti site. Varian tidak dikenal menghasilkan 422.
- Setiap varian mempunyai active/draft terpisah. Aktivasi phone call tidak menonaktifkan site. Nomor `version` tetap unik secara global, sehingga urutan nomor dalam satu varian dapat melompat. Resource template menambahkan `variant`. Endpoint edit/preview/activate existing tetap memakai ID template dan aturan lock_version/preview token yang sama.
- Default phone call berisi tujuh rating wajib, satu Yes/No wajib, dan comments opsional. Ketujuh aspek phone call tidak dapat dinonaktifkan, diubah menjadi optional/non-rating, atau ditambah rating kedelapan melalui editor. Label tetap dapat diperbarui. Site mempertahankan perilaku editor existing.
- Saat dokumentasi siklus v3 dipublikasikan, `ticket_cycles.survey_template_id` dipatok ke versi aktif untuk varian siklus itu. Perubahan/aktivasi template berikutnya tidak mengubah snapshot tersebut. Siklus berikutnya memakai versi aktif pada publikasinya sendiri. Field ini tersedia pada `current_cycle`/`cycles[]`.
- Link site tetap mematok versinya sendiri. Regenerate dan penggantian link setelah reject mempertahankan snapshot siklus. Phone call tidak menerbitkan link saat publikasi, regenerate, atau reject.

Key pertanyaan phone call tetap kompatibel dengan storage existing:

| Key | Label default phone call |
|---|---|
| `timeliness` | Call Timeliness |
| `technician_attitude` | Technician attitude |
| `technical_knowledge` | Technical knowledge |
| `work_speed` | Speed of Resolving the Issue |
| `solution_quality` | Quality of the Solution |
| `explanation_quality` | Explanation of the Solution |
| `overall_satisfaction` | Overall satisfaction |
| `machine_ok` | Issue resolved by phone? |
| `feedback` | Comments & Suggestions |

Rating 1/2/3 = Kurang Puas/Puas/Sangat Puas. `work_neatness` dan `grooming` tidak ada pada phone call. Nilai kedua aspek tersebut tetap null, bukan nol. Jawaban No pada `machine_ok` tetap wajib menyertakan `machine_issue_note`.

#### 3.38 Form dan submit phone call tanpa link

Endpoint internal memakai base `/api/aftersales`. **Admin harus mempunyai kedua permission** `aftersales.tickets.manage` dan `aftersales.satisfaction.submit`. Permission submit lama saja tidak memberi Technician akses ke jalur phone call.

1. `GET /tickets/{ticket}/survey-response` membaca form phone call yang dipatok pada siklus aktif.
2. `POST /tickets/{ticket}/survey-response` mengirim jawaban Customer. Endpoint yang sama tetap melayani on-behalf site melalui link seperti sebelumnya.

Kedua jalur phone call hanya tersedia setelah dokumentasi siklus aktif dipublikasikan, dengan progress Documentation Published atau Satisfaction Submitted. GET untuk site menghasilkan 422; FE site tetap memakai flow survey-link existing.

GET **200**, subset response:

```json
{"status":"success","data":{"ticket_id":1,"cycle_id":2,"variant":"phone_call","ref_survey_template_id":2,"can_submit":true,"template":{"version":2,"title":"Phone Call Satisfaction Survey","scale_labels":["Kurang Puas","Puas","Sangat Puas"],"sections":[]}}}
```

`sections` pada response nyata berisi seluruh pertanyaan aktif dari snapshot. Kirim kembali **cycle_id dan ref_survey_template_id** dari GET, bukan versi aktif global saat submit.

Contoh POST yang diuji (signature opsional untuk Admin):

```json
{"cycle_id":2,"ref_survey_template_id":2,"reason":"Customer answers recorded after the call","signer_name":"Customer","signer_position":"Manager","answers":{"timeliness":3,"technician_attitude":3,"technical_knowledge":3,"work_speed":3,"solution_quality":3,"explanation_quality":3,"overall_satisfaction":3,"machine_ok":true,"feedback":"Text answer"}}
```

POST **201**, envelope mutation existing: `data.data` adalah response survey tersimpan. Server menentukan `ticket_id`, `cycle_id`, template, `source: "admin"`, `review_status: "needs_review"`, `review_reasons: ["admin_input"]`, actor, dan `filled_at`. Input client `source`/`review_status` tidak dapat melewati review. Reason disimpan sebagai `admin_input_reason`. Tidak ada link/OTP yang diminta atau dibuat.

Submit berhasil langsung mencatat log Satisfaction Submitted pada siklus itu dan menghentikan SLA secara provisional menurut Task 7. Tidak menunggu approval untuk progress/stop SLA. Response, jawaban, log, dan stop SLA berada dalam transaksi yang sama; kegagalan menyimpan jawaban membatalkan seluruh perubahan tersebut. Signature opsional yang baru dibuat juga dibersihkan bila transaksi gagal.

| HTTP/code | Kondisi |
|---|---|
| 403 | Permission input/read phone call tidak lengkap |
| 422 + `errors.<field>` | Tujuh rating belum lengkap, di luar 1–3, key tidak dikenal, reason/identitas belum lengkap, No tanpa penjelasan, atau ID wajib tidak diberikan |
| 422 | Dokumentasi/progress siklus belum memenuhi syarat |
| 409 `survey_cycle_not_current` | Form berasal dari siklus lama |
| 409 `survey_template_changed` | ID template tidak sesuai snapshot siklus |
| 409 `survey_already_submitted` | Sudah ada response pada siklus itu yang belum di-reject |
| 409 `phone_call_has_no_survey_link` | Share/regenerate link pada phone call |

GET form sesudah submit mengembalikan `can_submit: false`. Setelah response current di-reject, `can_submit: true`; submit pengganti membuat row baru dan mempertahankan histori rejected. Retry submit yang sudah berhasil menghasilkan 409 tanpa membuat row/log baru. Submit/review memakai row lock ticket dan cycle; uji dua request paralel sesungguhnya tetap Task 12.

`GET /tickets/{ticket}/survey-link` pada phone call mengembalikan `variant: "phone_call"`, `status: "not_applicable"`, `url: null`, `template: null`, `can_share: false`, `can_regenerate: false`. Tidak mengembalikan link site dari siklus lama.

#### 3.39 Current dan valid per siklus

Pada v3, current adalah response terbaru **dalam cycle_id yang sama**, dengan urutan `filled_at DESC, id DESC`, tanpa soft-deleted. Respons dari beberapa siklus dapat sama-sama current. Histori tanpa cycle/waktu submit tidak masuk rating v3 dan tidak ditebak kepemilikannya.

Valid untuk rating v3:

- Response current dengan `source: "link"` dan status `normal`/`reviewed`.
- Response current dengan `source: "admin"` dan status **`reviewed`**, termasuk phone call dan site on-behalf.
- `needs_review`, `rejected`, source lain, dan response non-current tidak masuk skor. Bila response current pending/rejected, rating tidak mengambil ulang response valid yang lebih lama dalam siklus itu. Siklus lain yang valid tetap dihitung.

Definisi ini berbeda dari stop SLA provisional Task 7: needs_review dapat menghentikan SLA, tetapi belum memberikan skor Technician. Reject tetap menghitung ulang SLA sesuai §3.34; replacement rating baru masuk setelah valid.

List/detail Satisfaction Survey menambahkan `cycle_id`, `variant`, dan `is_valid`; `is_current` kini mengikuti siklus pada v3. Histori `responses[].current` juga per siklus. Form readonly menambahkan cycle_id/variant. Ringkasan manager di ticket `survey_responses[]` menambahkan variant/is_current/is_valid, tanpa menghapus histori existing.

`survey_filled` pada detail v3 berarti ada response pada **siklus aktif** yang tidak rejected, termasuk pending review. Response dari siklus lama tidak membuat field ini true. Field ini bukan indikator validitas rating. Legacy mempertahankan semantik filled existing.

#### 3.40 Angka skor dan akses Technician

`GET /tickets` dan `GET /tickets/{ticket}` menambahkan **`average_score`**, angka satu desimal atau null. Nilai merupakan rata-rata seluruh **nilai rating yang dijawab** dari response current-valid tiap siklus ticket; Yes/No, comments, nilai null, dan aspek yang tidak tersedia pada varian tidak dihitung. Pembulatan dilakukan setelah agregasi, bukan dari rata-rata yang sudah dibulatkan per response.

Contoh pengujian: site berisi 9 rating bernilai 1 dan phone call berisi 7 rating bernilai 3 yang sudah reviewed menghasilkan `(9×1 + 7×3) / 16 = 1.9`. Ketika phone call masih pending, nilai ticket hanya 1.0 dari siklus site valid. Saat siklus baru belum mempunyai response, skor histori valid tetap tersedia, sementara survey_filled siklus aktif false.

- Technician hanya mendapat angka untuk ticket yang di-assign kepadanya, melalui guard ownership existing. Jawaban, feedback, identitas pengisi, review note, dan response survey tetap tidak dibuka kepada Technician.
- `/technicians/me/overall-rating` tetap hanya mengembalikan `overall_rating` dan `rating_scale_max: 3`; input user_id/ticket filter tidak dapat memilih Technician lain. Overall Rating/Technician Rating dan KPI CSAT mengikutkan response v3 site/phone call yang valid menggunakan nilai aspek yang dijawab.
- `rating_count` pada ringkasan Technician menghitung jumlah response current-valid yang mempunyai rating, sehingga pada v3 dapat lebih dari satu per ticket (satu per siklus).
- Bobot agregasi dan pengecualian admin untuk data **legacy** pada rating Technician dipertahankan: satu bobot per rata-rata response legacy current-valid, sementara nilai aspek response v3 dipakai sebagai bobot agregasi v3. Alur review/SLA legacy tidak dikonversi oleh migration task ini.
- `GET /survey-templates/question-scores` mempertahankan envelope existing dan menambahkan `variant` pada setiap item. Agregasi v3 memakai current-valid per siklus, termasuk Admin reviewed. Grouping memakai variant + question_key, agar label Call Timeliness tidak menimpa Arrival Punctuality. Aturan laporan data legacy dipertahankan.

#### 3.41 Migration dan batas lingkup

Migration baru:

- `2026_10_30_000500_add_survey_template_variants.php`: kolom variant pada template/state, satu state per varian, dan template phone call default. Tidak menimpa template site yang sudah diedit. Tidak perlu seed role/permission atau seeder demo.
- `2026_10_30_000510_pin_cycle_survey_template.php`: FK nullable `survey_template_id` pada cycle. Backfill hanya cycle v3 yang sudah Documentation Published; site memakai versi link siklus yang tersedia, kemudian fallback versi site aktif; phone call memakai versi phone aktif. Cycle belum published dan seluruh legacy tetap tanpa snapshot baru.

Rollback variant ditolak sebelum penghapusan jika sudah ada response/link/audit atau snapshot cycle phone call. Rollback snapshot ditolak jika sudah ada cycle yang dipatok, agar histori template tidak hilang diam-diam. Roundtrip tanpa histori/pin baru mempertahankan template site dan jawaban existing.

**Dua migration task 8 sudah diterapkan ke database aplikasi lokal `local_daxx` pada 2026-10-30 (batch 32).** Backup SQL dibuat sebelum migration; cache dibersihkan dan aplikasi kembali live. Varian phone diinisialisasi migration; tidak ada assignment role atau seed data demo. Integrasi telepon otomatis, perubahan layar FE, Happy Call/Close siklus terbaru (Task 9), dan pengujian konkurensi/e2e (Task 12) tidak ditambahkan pada task ini.

#### 3.42 Verifikasi Task 8

- Test API: draft/aktivasi terpisah, tujuh aspek wajib, larangan Technician, no-link/OTP, validasi dan stale snapshot, submit ganda, reject/replacement tanpa link, rollback jawaban/signature/SLA/progress, serta snapshot tetap setelah aktivasi versi baru.
- Test skor: gabungan 9/7 aspek, valid/current per siklus, pending tidak mengambil ulang history, report dipisah per varian, Overall Rating v3 tanpa memilih user lain, dan list/detail Technician tidak membocorkan jawaban/review note.
- Test migration: roundtrip mempertahankan site/response existing, backfill site/phone published, dan proteksi rollback.
- Smoke **MySQL 8.4.3 lulus** pada DB sementara: migration/FK/roundtrip, backfill kedua varian, aktivasi terpisah, snapshot, SLA provisional, rating setelah approve, agregasi report SQL, dan proteksi rollback. Database sementara dihapus; database aplikasi tidak diubah.
- Regression: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Aftersales tests/Unit/Aftersales` → **OK, 309 tests, 2667 assertions** (SQLite in-memory). Formatter PHP pada file task 8 dan `git diff --check` lulus. Konkurensi dua request sungguhan dan e2e FE tetap Task 12.

### Task 9 — Happy Call, Ready to Close, dan Close (IMPLEMENTED)

AS-HC-01/02/03/04 dan AS-CLS-01. Default **OQ-01/05/13/15/16 disetujui user**: Admin mencatat Happy Call, Head melakukan Close manual, deadline H+3 dari waktu submit survey, Happy Call mengikuti siklus terakhir, dan eskalasi dalam aplikasi sekali per siklus. Admin penerima eskalasi dipetakan ke `tickets.created_by` yang masih ber-role `after_sales`. Perilaku berikut berlaku untuk ticket **v3**; gate/reminder legacy existing tetap dipertahankan.

#### 3.43 Survey terbaru, deadline, dan histori Happy Call

- Survey current dipilih dalam **siklus aktif**, memakai aturan Task 8 (`filled_at DESC, id DESC`, tanpa soft-deleted). Survey atau Happy Call dari siklus lama tidak memenuhi syarat Happy Call/Close saat ini.
- Happy Call boleh dicatat setelah dokumentasi siklus aktif berstatus Documentation Published **dan** mempunyai log publikasi dengan cycle_id yang sama, progress bukan Recoming/Closed, serta survey current valid menurut §3.39. Survey link normal/reviewed dan Admin reviewed memenuhi syarat; needs_review/rejected tidak memenuhi syarat.
- `ticket_cycles.happy_call_due_at` = waktu submit survey current + **3 hari kalender**, pada jam yang sama di Asia/Jakarta. `tickets.happy_call_due_at` mencerminkan siklus aktif. Tidak memakai akhir hari atau tambahan waktu sejak approval.
- Deadline mulai tercatat saat survey disubmit, termasuk needs_review secara provisional. Approval tidak menggeser deadline; jika review baru selesai setelah H+3, Happy Call langsung overdue. Pending review bisa overdue dan mendapat eskalasi, tetapi Happy Call belum boleh dicatat sampai survey valid.
- Reject menghapus deadline current; replacement memakai waktu submit yang baru. Soft delete/restore survey menghitung ulang deadline. Review histori hanya memperbarui siklus terkait, bukan mirror ticket untuk siklus terbaru. Aturan fallback stop SLA Task 7 tetap terpisah dari current survey untuk Happy Call.
- Happy Call tetap disimpan sebagai `ticket_logs.step: "happy_call_completed"`, kini dengan `cycle_id` dan **`satisfaction_id`**. Completion hanya berlaku untuk survey yang ditautkan itu. Survey pengganti membutuhkan Happy Call baru meskipun waktu submit sama sampai detik yang sama. Log lama tetap tersimpan sebagai histori.
- Recoming membuat siklus baru dan mengosongkan mirror deadline aktif. Happy Call/Close harus kembali mengikuti dokumentasi, survey valid, dan Happy Call pada siklus terbaru.

#### 3.44 Endpoint Happy Call dan Close

`POST /tickets/{ticket}/happy-call` tetap memerlukan `aftersales.tickets.manage` (Admin). Request `note` opsional, maksimal 10000 karakter. FE dapat menambahkan `cycle_id` dan `satisfaction_id` dari snapshot/resource untuk mendeteksi halaman yang sudah stale. Jika diberikan, keduanya wajib integer yang cocok dengan kondisi terbaru. Field konteks ini opsional untuk kompatibilitas client existing.

Contoh request yang diuji:

```json
{"cycle_id":1,"satisfaction_id":1,"note":"Customer contacted"}
```

Response **201** tetap `{"data":{...ticket_log...}}`; log memuat actor_user_id, cycle_id, dan satisfaction_id. Retry pada survey/siklus yang sama mengembalikan log yang sama dan tidak mengubah note/tanggal/actor awal. Unique index cycle + satisfaction dan row lock ticket menjaga satu completion untuk pasangan itu.

`POST /tickets/{ticket}/close` pada v3 memerlukan **permission `aftersales.tickets.close` dan role `head_after_sales`**. Permission Close yang salah diberikan ke role lain tidak cukup. Request menerima `note` opsional maksimal 10000 karakter dan konteks cycle_id/satisfaction_id opsional seperti Happy Call.

Close memeriksa ulang di bawah lock ticket: belum Closed/Recoming, dokumentasi siklus aktif published, survey current valid, dan Happy Call yang ditautkan ke survey itu sudah selesai. **`happy_call_required: false` tidak melewati gate v3.** Sukses tetap **201**, envelope `{"message":"Ticket closed successfully","data":{"data":{...closed_log...}}}`. Retry setelah Closed ditolak 422 tanpa log Closed ganda. Scheduler tidak pernah melakukan Close.

| HTTP/code | Kondisi |
|---|---|
| 403 | Tidak mempunyai permission aksi; pada Close v3 juga bukan role Head |
| 409 `ticket_cycle_changed` | cycle_id client berbeda dari siklus aktif |
| 409 `survey_response_changed` | satisfaction_id client berbeda dari response current |
| 422 `ticket_in_recoming` | Happy Call/Close diminta saat masih Recoming |
| 422 `documentation_not_published` | Dokumentasi siklus aktif belum published/log publikasinya belum ada |
| 422 `survey_not_valid` | Survey siklus aktif belum ada, rejected, needs_review, atau tidak memenuhi aturan valid Task 8 |
| 422 `happy_call_required` | Close diminta sebelum Happy Call untuk survey current selesai |
| 422 | Ticket sudah Closed atau validasi field request gagal (`errors.<field>`) |

#### 3.45 Resource, capability, dan reminder

`GET /tickets`, `GET /tickets/{ticket}`, dan Ready to Close menambahkan `happy_call` (null untuk legacy):

```json
{"cycle_id":1,"satisfaction_id":1,"due_at":"2026-11-02T03:00:00.000000Z","done_at":null,"overdue":false,"status":"pending","survey_valid":true,"completed":false,"can_record":true}
```

| Field | Arti |
|---|---|
| `status` | `waiting_survey`, `needs_review`, `pending`, `overdue`, atau `completed` |
| `due_at` | H+3 dari submit current yang belum rejected; null bila belum tersedia |
| `done_at`, `completed` | Completion yang cocok dengan current cycle dan survey, bukan sekadar keberadaan log Happy Call lama |
| `overdue` | Waktu sekarang **lebih besar** dari deadline, ticket terbuka, dan completion current belum ada. Pada tepat deadline masih false |
| `survey_valid` | Survey current memenuhi aturan §3.39 |
| `can_record` | Workflow memenuhi syarat, belum completed, dan pembaca mempunyai permission Admin manage |

Satisfaction_id hanya disertakan bagi pembaca yang dapat melihat seluruh ticket (Admin/SPV/Head). Technician tetap tidak mendapat jawaban, identitas Customer pengisi survey, review note, atau daftar response survey melalui resource ini.

`capabilities.can_record_happy_call` pada detail v3 mengikuti guard Happy Call yang sebenarnya; false sebelum survey valid atau setelah completion. `capabilities.can_close` hanya true untuk Head dengan seluruh syarat v3 terpenuhi. Capability legacy tetap memakai aturan lama.

Reminder v3 mengganti Survey due/H+1 existing:

- `reminders.survey_due_at: null`, `survey_due: false`.
- `survey_waiting_days`: umur hari kalender sejak log Documentation Published siklus aktif ketika menunggu survey, atau null bila tidak sedang menunggu. Tidak ada cutoff/Close otomatis.
- `happy_call_due_at`: deadline H+3; `happy_call_due`: flag overdue yang sama dengan happy_call.overdue; `happy_call_completed`: completion current; `happy_call_required: true`; `rating_scale_max: 3`.
- Reminder legacy Survey due/H+1 dan grandfathered Happy Call tetap dipertahankan.

#### 3.46 Ready to Close: filter dan paginasi di database

**`GET /tickets/ready-to-close`**, akses Admin/SPV/Head melalui permission manage/view-all. Technician mendapat 403. Alias **`GET /tickets?ready_to_close=1`** disediakan agar layar Ready to Close existing dapat memakai perilaku yang sama.

| Query | Aturan |
|---|---|
| `search` | Opsional, maksimal 100 karakter; ticket code atau nama Customer |
| `page` | Integer minimal 1 |
| `per_page` | Integer 1–100, default 15 |

Queue berisi ticket v3 terbuka yang **siklus terbarunya** belum diakhiri dan mempunyai documentation_status serta log Documentation Published dalam cycle_id tersebut. Ticket legacy, soft-deleted, Closed, Recoming, serta yang hanya mempunyai publikasi pada siklus lama tidak masuk queue. Kondisi tersebut dan search diterapkan pada query sebelum count/paginate; bukan memfilter hasil satu halaman di memori.

Ticket published yang menunggu survey, review, atau Happy Call **tetap tampil**, sesuai PRD, dengan `can_close: false`. Urutan ID ticket terbaru lebih dahulu. Response memakai `status`, `data[]`, `total`, `current_page`, `last_page`:

```json
{"status":"success","data":[{"id":1,"ticket_code":"T-1","workflow_version":"v3","customer":{"id":1,"name":"Customer A"},"technician":{"id":4,"name":"User 4"},"cycle_id":1,"cycle_no":1,"documentation_status":"documentation_published","progress":"happy_call_completed","average_score":2,"happy_call":{"cycle_id":1,"satisfaction_id":1,"due_at":"2026-11-02T03:00:00.000000Z","done_at":"2026-10-30T03:00:00.000000Z","overdue":false,"status":"completed","survey_valid":true,"completed":true,"can_record":false},"can_close":true}],"total":1,"current_page":1,"last_page":1}
```

`can_close` memperhitungkan user pembaca: Admin/SPV tetap false walaupun Happy Call selesai. Ticket lama tetap dapat dilihat/ditutup melalui alur legacy existing; tidak dikonversi ke gate v3 oleh queue ini.

#### 3.47 Scheduler dan notifikasi overdue

Command baru **`php artisan tickets:notify-happy-call-overdue`**, dijadwalkan **setiap menit**, timezone Asia/Jakarta, dengan `withoutOverlapping()`. Terdaftar dan diverifikasi lewat `php artisan schedule:list`; scheduler OS/server tetap harus menjalankan Laravel scheduler existing. Command tidak dijalankan terhadap database aplikasi pada tahap implementasi ini.

- Hanya memproses ticket v3 dengan deadline siklus terbaru yang sudah lewat. State publikasi, survey, deadline, completion, dan Closed diperiksa ulang di bawah row lock ticket/cycle.
- Penerima: seluruh user Head aktif (`head_after_sales`, tidak soft-deleted) dan user `tickets.created_by` jika masih aktif dan ber-role `after_sales`. Admin lain dan Technician tidak menerima broadcast. Jika pembuat tidak tersedia, Head yang tersedia tetap menerima.
- Kanal **database notification** existing, tanpa email/WhatsApp. Notifikasi memuat `type: "happy_call_overdue"`, title/message, ticket_id/code, cycle_id, happy_call_due_at, dan URL `/aftersales/tickets/{id}`. Tidak membawa jawaban/review note.
- Tabel `ticket_happy_call_escalations` menyimpan receipt unik **cycle_id + user_id**, beserta notification_id, deadline saat eskalasi, dan notified_at. Notifikasi dan receipt ditulis dalam transaksi yang sama; kegagalan membatalkan keduanya sehingga retry masih dapat mengirim.
- Per penerima hanya satu notifikasi per siklus, termasuk bila scheduler diulang, deadline berubah karena replacement, atau notifikasi dibaca/dihapus. User Head baru dapat menerima sekali ketika menjadi penerima yang memenuhi syarat. Siklus Recoming baru mempunyai dedup sendiri.
- Completed/Closed, legacy, soft-deleted ticket, rejected/no survey, dan deadline yang hanya milik siklus lama tidak dieskalasi. Pending review yang sudah melewati H+3 tetap dieskalasi agar hambatan review tidak menyembunyikan overdue.
- Flag overdue tetap dihitung dari state terbaru sampai Happy Call selesai atau current survey/siklus berubah; mengirim notifikasi tidak menandai Happy Call selesai dan tidak menutup ticket.

#### 3.48 Migration dan verifikasi Task 9

Migration **`2026_10_30_000600_add_happy_call_cycle_tracking.php`**:

- Tambah FK nullable `ticket_logs.satisfaction_id` dan unique cycle + satisfaction untuk Happy Call. Log progress lain tetap null pada field ini. Tambah tabel receipt eskalasi dengan FK kepemilikan ticket/cycle, FK user, dan unique cycle + recipient.
- Backfill deadline hanya ticket v3, memakai response current masing-masing siklus; response sesudah ended_at siklus arsip tidak dipakai. Mirror ticket mengikuti cycle terbaru.
- Happy Call existing hanya ditautkan jika satu response dengan waktu submit diketahui pada siklus itu, statusnya diterima/valid, dan log Happy Call ada sesudah/saat submit. Hanya log pertama yang ditautkan bila ada duplikasi historis. Histori ambigu tetap disimpan tanpa tautan dan tidak memenuhi gate baru sampai Happy Call dicatat untuk survey current. Legacy tidak diubah.
- Rollback ditolak sebelum DDL jika sudah ada Happy Call yang ditautkan atau receipt eskalasi. Tanpa histori baru, down/up mempertahankan survey/log dan timestamp deadline existing. Tidak ada seed role/permission baru.
- **Migration task 9 belum diterapkan ke database aplikasi `local_daxx`.** Gunakan maintenance untuk penerapan schema/backfill sebelum menjalankan command pada database aplikasi.
- Test API/domain: survey valid terbaru, publication per siklus, role Head meskipun permission dipalsukan, mandatory Happy Call meskipun flag lama false, retry idempotent, konteks stale, replacement pada timestamp sama, Recoming, deadline approval/reject/delete/restore, recipient isolation, dedup setelah notifikasi dihapus, rollback notifikasi/receipt, dan capability.
- Test queue membuktikan total/halaman berasal dari query terfilter dengan LIMIT/OFFSET; test legacy memastikan reminder lama tetap berlaku. Test migration membuktikan mapping konservatif, data lama utuh, unique, dan proteksi rollback.
- Smoke **MySQL 8.4.3 lulus** pada database sementara: migration/down-up/backfill, FK ownership/unique, SQL pagination, guard completion survey, rollback dan dedup notifikasi, deadline approval, isolasi Recoming, serta proteksi rollback. Database sementara dihapus; database aplikasi tidak diubah.
- Regression: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Aftersales tests/Unit/Aftersales` → **OK, 323 tests, 2792 assertions** (SQLite in-memory). Formatter PHP dan `git diff --check` lulus. Uji dua request/worker sungguhan dan e2e FE tetap Task 12.

## 4. Pending (belum dikerjakan)

Task 10–12 sesuai daftar task BE. Bagian kontraknya ditambahkan ke §3 saat task selesai.

## 5. Change log

| Tanggal | Task | Status |
|---|---|---|
| 2026-10-29 | Task 1: Machine Trader dan filter Customers | Implemented dan diuji (224 test lulus); migration baru, belum dijalankan di MySQL deployment |
| 2026-10-29 | Task 2: Role, permission, capabilities ticket | Implemented dan diuji (229 test lulus, 1717 assertions); memakai usulan default OQ-07/12/13; migration seeding belum dijalankan di MySQL deployment |
| 2026-10-30 | Task 3: Fondasi siklus, relasi, workflow legacy/v3 | Implemented; 239 test lulus, 1793 assertions; smoke migration MySQL 8.4.3 lulus pada DB sementara; default OQ-19; belum migrate database aplikasi |
| 2026-10-30 | Task 4: Submit, sign, publish dokumen per siklus | Implemented; 255 test lulus, 2009 assertions; default OQ-02/03/09/10 disetujui user; smoke migration MySQL 8.4.3 lulus; belum migrate database aplikasi |
| 2026-10-30 | Penerapan lokal task 1–4 | Empat migration pending dan `AftersalesV3RoleSeeder` berhasil pada `local_daxx`; cache dibersihkan, aplikasi kembali live, tidak ada migration pending. Verifikasi: 16 ticket legacy memiliki 16 siklus awal; Close hanya Head. Belum ada user aktif ber-role Head; assignment user tetap manual. Status penerapan lokal pada baris ini memperbarui catatan pending deployment sebelumnya. |
| 2026-10-30 | Task 5: Recoming v3, jadwal call/visit, signature reuse | Implemented; 268 test lulus, 2154 assertions; default OQ-06/10 disetujui user; smoke migration MySQL 8.4.3 lulus; migration task 5 belum diterapkan ke database aplikasi |
| 2026-10-30 | Penerapan lokal task 5 | Migration `2026_10_30_000200_add_recoming_v3_scheduling` berhasil pada `local_daxx` menggunakan backup yang disiapkan user. Kolom baru terverifikasi, tidak ada migration pending, cache dibersihkan, aplikasi kembali live. Tidak ada row Recoming existing saat verifikasi; tidak diperlukan seed baru. Baris ini memperbarui status penerapan task 5 sebelumnya. |
| 2026-10-30 | Task 6: Temuan per siklus dan Repair pada edit pertama | Implemented dengan reuse diagnosis existing sesuai persetujuan user; 281 test lulus, 2416 assertions; smoke migration/Repair MySQL 8.4.3 lulus; migration task 6 belum diterapkan ke database aplikasi |
| 2026-10-30 | Penerapan lokal task 6 | Migration `2026_10_30_000300_scope_diagnosis_to_ticket_cycles` berhasil pada `local_daxx`. Backup SQL terbaru disimpan di `storage/app/private/backups/local_daxx-before-task6-20261030-125352-a4919e.sql`. Fingerprint dan jumlah 9 diagnosis existing tidak berubah; seluruhnya berhasil di-backfill ke siklus. Tidak ada migration pending, cache dibersihkan, aplikasi kembali live; tidak diperlukan seed baru. Baris ini memperbarui status penerapan task 6 sebelumnya. |
| 2026-10-30 | Task 7: Setup dan perhitungan SLA per siklus | Implemented; default OQ-14 disetujui user; 297 test lulus, 2526 assertions; smoke migration dan SLA MySQL 8.4.3 lulus pada DB sementara; migration task 7 belum diterapkan ke database aplikasi; tidak ada seed baru. |
| 2026-10-30 | Penerapan lokal task 7 | Migration `2026_10_30_000400_add_cycle_sla_setup` berhasil pada `local_daxx` (batch 31). Backup SQL: `storage/app/private/backups/local_daxx-before-task7-20261030-133421-55177d.sql`. Kolom baru dan FK survey terverifikasi; fingerprint data bisnis ticket/cycle/survey/diagnosis tetap sama (16 ticket legacy, 16 cycle, 9 survey, 9 diagnosis). Tidak ada ticket v3 existing untuk backfill SLA; perilaku v3 telah diuji pada pengujian task 7. Tidak ada migration pending, cache dibersihkan, aplikasi kembali live. Tidak diperlukan seed baru. Baris ini memperbarui status penerapan task 7 sebelumnya. |
| 2026-10-30 | Task 8: Survey dua varian dan skor per ticket | Implemented; default OQ-04/08/18 disetujui user, OQ-06 tetap manual; 309 test lulus, 2667 assertions; smoke migration/template/rating MySQL 8.4.3 lulus pada DB sementara; dua migration task 8 belum diterapkan ke database aplikasi; template phone call diinisialisasi migration, tanpa seed role/demo baru. |
| 2026-10-30 | Penerapan lokal task 8 | Kedua migration task 8 berhasil pada `local_daxx` (batch 32). Backup: `storage/app/private/backups/local_daxx-before-task8-20261030-151643-53bf5f.sql`. Kolom variant, state per varian, dan FK template cycle terverifikasi. Template site aktif tetap ID/version 1; phone call aktif ID/version 3 dengan tujuh rating wajib. Fingerprint data bisnis dan template site existing tetap sama (16 ticket legacy, 16 cycle, 9 survey, 9 diagnosis); tidak ada ticket v3 existing untuk backfill snapshot. Tidak ada migration pending, cache dibersihkan, aplikasi kembali live; tidak ada seed role/demo tambahan. Baris ini memperbarui status penerapan task 8 sebelumnya. |
| 2026-10-30 | Task 9: Happy Call, Ready to Close, dan Close | Implemented; default OQ-01/05/13/15/16 disetujui user; 323 test lulus, 2792 assertions; smoke migration/notifikasi/paginasi MySQL 8.4.3 lulus pada DB sementara; migration task 9 belum diterapkan ke database aplikasi; command overdue terdaftar setiap menit, tanpa seed baru. |
