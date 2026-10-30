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
- Scheduler existing tidak membawa phone call ke On Site berdasarkan visit lama. Perubahan penuh progress Repair pada edit pertama dan perilaku `ResumeRecomingTickets` untuk workflow v3 dilengkapi di Task 6.

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
- **Belum dijalankan pada database aplikasi lokal `local_daxx`**. Tidak ada seeder/permission baru pada task ini.
- Task 6 tetap mencakup temuan Problem/Root Cause/Action serta Repair pada edit pertama. Task 7 mencakup setup/perhitungan SLA Recoming. Task 8 mencakup template/response survey phone call. Task 5 tidak membuat integrasi telepon atau pencatatan survey otomatis.

#### 3.26 Verifikasi Task 5

- `RecomingV3Test`: pemisahan call/visit, timezone dan format datetime FE, empat reason, penolakan perubahan assignment, guard Admin meskipun Technician memiliki permission lama, kedua jalur reschedule dan cutoff hari/Repair, tidak ada On Site dari visit lama, rollback transaksi, kompatibilitas legacy, dan migration roundtrip/proteksi rollback.
- `DocumentWorkflowV3Test`: signature/file/Agreement tidak berubah setelah unlock dan submit Recoming; publikasi baru tanpa sign SPV ulang; foto phone call opsional; tidak ada link survey site untuk phone call; koreksi awal dan legacy tetap sesuai guard lama.
- Smoke test **MySQL 8.4.3** pada database sementara: migration up, backfill data aktif/soft-deleted, penyimpanan phone call/reason baru, penolakan rollback yang membuang data, down/up kompatibel, dan penolakan mapping visit ambigu **lulus**. Database uji telah dihapus; database aplikasi tidak diubah.
- Regression akhir: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Aftersales tests/Unit/Aftersales` → **OK, 268 tests, 2154 assertions** (SQLite in-memory). Formatter PHP pada file task 5 dan `git diff --check` lulus. Uji konkurensi dua request sungguhan dan e2e FE tetap Task 12.

## 4. Pending (belum dikerjakan)

Task 6–12 sesuai daftar task BE. Bagian kontraknya ditambahkan ke §3 saat task selesai.

## 5. Change log

| Tanggal | Task | Status |
|---|---|---|
| 2026-10-29 | Task 1: Machine Trader dan filter Customers | Implemented dan diuji (224 test lulus); migration baru, belum dijalankan di MySQL deployment |
| 2026-10-29 | Task 2: Role, permission, capabilities ticket | Implemented dan diuji (229 test lulus, 1717 assertions); memakai usulan default OQ-07/12/13; migration seeding belum dijalankan di MySQL deployment |
| 2026-10-30 | Task 3: Fondasi siklus, relasi, workflow legacy/v3 | Implemented; 239 test lulus, 1793 assertions; smoke migration MySQL 8.4.3 lulus pada DB sementara; default OQ-19; belum migrate database aplikasi |
| 2026-10-30 | Task 4: Submit, sign, publish dokumen per siklus | Implemented; 255 test lulus, 2009 assertions; default OQ-02/03/09/10 disetujui user; smoke migration MySQL 8.4.3 lulus; belum migrate database aplikasi |
| 2026-10-30 | Penerapan lokal task 1–4 | Empat migration pending dan `AftersalesV3RoleSeeder` berhasil pada `local_daxx`; cache dibersihkan, aplikasi kembali live, tidak ada migration pending. Verifikasi: 16 ticket legacy memiliki 16 siklus awal; Close hanya Head. Belum ada user aktif ber-role Head; assignment user tetap manual. Status penerapan lokal pada baris ini memperbarui catatan pending deployment sebelumnya. |
| 2026-10-30 | Task 5: Recoming v3, jadwal call/visit, signature reuse | Implemented; 268 test lulus, 2154 assertions; default OQ-06/10 disetujui user; smoke migration MySQL 8.4.3 lulus; migration task 5 belum diterapkan ke database aplikasi |
