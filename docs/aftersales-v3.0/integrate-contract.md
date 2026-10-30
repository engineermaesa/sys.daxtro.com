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

## 4. Pending (belum dikerjakan)

Task 3–12 sesuai daftar task BE. Bagian kontraknya ditambahkan ke §3 saat task selesai.

## 5. Change log

| Tanggal | Task | Status |
|---|---|---|
| 2026-10-29 | Task 1: Machine Trader dan filter Customers | Implemented dan diuji (224 test lulus); migration baru, belum dijalankan di MySQL deployment |
| 2026-10-29 | Task 2: Role, permission, capabilities ticket | Implemented dan diuji (229 test lulus, 1717 assertions); memakai usulan default OQ-07/12/13; migration seeding belum dijalankan di MySQL deployment |
