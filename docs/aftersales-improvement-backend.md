# API Contract Aftersales Improvement untuk Tim Frontend

Dokumen ini mengacu pada implementasi `routes/api.php` dan controller saat ini. Bagian 1–9 adalah kontrak integrasi FE; lampiran di bagian akhir menjelaskan migrasi dan aturan bisnis backend. Seluruh ID, nama, jumlah, dan timestamp pada contoh adalah ilustrasi. Contoh objek model menampilkan field yang dibutuhkan FE; backend dapat mengembalikan field model tambahan seperti `created_by`, `updated_by`, `deleted_at`, dan relasi. Jangan mengandalkan field tambahan yang tidak dibahas di sini.

## 1. Konvensi request, ID, dan response

- Path di katalog endpoint sudah lengkap, termasuk `/api`. Jangan menambahkan prefix lagi.
- Endpoint internal menggunakan session login aplikasi. Kirim cookie session (`credentials: 'same-origin'` atau konfigurasi Axios aplikasi) dan `Accept: application/json`. Untuk mutasi gunakan mekanisme CSRF aplikasi yang sudah ada. Jangan mengasumsikan autentikasi Bearer/Sanctum baru.
- JSON menggunakan `Content-Type: application/json`. Upload Excel menggunakan `FormData`; biarkan browser menentukan `Content-Type` beserta multipart boundary.
- Timestamp ditampilkan sesuai zona waktu aplikasi oleh FE. Contoh timestamp JSON menggunakan UTC (`Z`). Untuk input tanggal form mesin kirim `YYYY-MM-DD`. Input koreksi tanggal impor menggunakan format template, misalnya `21/10/2026`.
- Field nullable dapat bernilai `null`. Untuk update, field yang tidak dikirim umumnya dipertahankan; kirim `null` untuk mengosongkan field yang menerima null. Pengecualian warranty dan turunan Province dijelaskan di bagian mesin/customer.
- Response file adalah binary download, bukan JSON. Gunakan `blob` dan nama file dari `Content-Disposition`.

### 1.1 ID yang tidak boleh tertukar

| Parameter / field | Entitas sebenarnya | Sumber ID untuk FE |
|---|---|---|
| `{customer}`, `customer_id` pada Move | ServiceCustomer / customer After Sales | `GET /api/aftersales/customers` → `data[].id` |
| `{product}` pada `/customers/products/{product}` | Unit mesin milik customer (`ServiceCustomerProduct`) | Customer detail → `data.products[].id` |
| `product_id` di payload mesin | Master Product Sales; boleh null | Bukan ID unit mesin dan bukan Machine Category |
| `ref_product_type_id` | Machine Category; label form lama: Model | Machine references → `data.categories[].id` |
| `{import}` | Batch upload | Upload response → `data.import.id` |
| `rows[].id`, `decision.row_id` | ID baris staging impor | Upload/review response → `data.rows[].id`; bukan nomor Excel |
| `row_number` | Nomor baris Excel, header dihitung baris 1 | Hanya untuk tampilan; bukan URL/ID request |
| `{deal}` pada resolve | CustomerDeal / item antrean review | Deal queue → `data[].id`; bukan `lead_id` |
| `{ticket}` | Ticket | Ticket list/detail → `data[].id` / `data.id` |
| `{technician}` | TechnicianProfile | Technician list → `data[].id`; berbeda dari `user_id` |

### 1.2 Envelope response aktual

Response belum memakai satu envelope seragam. Gunakan mapping ini, jangan menerapkan satu extractor `response.data.data` untuk seluruh endpoint. Path di bawah dihitung dari **body JSON**, bukan objek response Axios.

| Operasi | Path objek/daftar pada body JSON | HTTP sukses |
|---|---|---|
| Customer list | `data` (array), pagination di root | 200 |
| Customer detail, Province, Region | `data` | 200 |
| Create / Update Customer | `data.data` | 201 / 200 |
| Create / Update Machine | `data.data` | 201 / 200 |
| Move Machine | `data` | 200 |
| Import Upload / Show / Review / Apply | `data.import`, `data.summary`, `data.rows` | 201 / 200 / 200 / 200 |
| Deal queue | `data` (array), paginator Laravel di root | 200 |
| Resolve Deal | `data` | 200 |
| Supervisor list | `data` (array) | 200 |
| Happy Call Completed | `data` (TicketLog) | 201, termasuk request ulang |
| Close | `data.data` (TicketLog) | 201 |
| Delete customer/mesin, save prefix | Hanya `message` | 200 |

Contoh bentuk mutation lama yang mempunyai **dua lapis data**:

```json
{
  "message": "Customer created successfully",
  "data": {
    "data": {"id": 8, "name": "Global Dynamic"}
  }
}
```

Jangan menggunakan `status === 'success'` sebagai satu-satunya penentu sukses; beberapa endpoint tidak mengirim `status`. Gunakan HTTP status dan envelope per endpoint.

## 2. Katalog endpoint dan penempatan fitur

Kolom penempatan adalah pemetaan integrasi FE berdasarkan mockup. Antrean Deal belum memiliki mockup tersendiri; letakkan akses review di Customer list / indikator Lead updated, bukan membuat kartu Deal History.

| ID | Method dan endpoint lengkap | Kegunaan | Penempatan / pemicu di FE | Response / detail |
|---|---|---|---|---|
| C01 | `GET /api/aftersales/customers` | List, pencarian, pagination customer | Customer list (gambar 1); juga dropdown tujuan Move | §3.1 |
| C02 | `GET /api/aftersales/customers/{customer}` | Detail customer beserta seluruh mesin dan Deal | Machine List (gambar 5), Edit Customer, Edit Machine | §3.2 |
| C03 | `POST /api/aftersales/customers` | Buat customer manual | Save Customer pada Add Customer (gambar 6) | §3.3 |
| C04 | `PUT /api/aftersales/customers/{customer}` | Update data customer | Save Customer pada Edit Customer | §3.3 |
| C05 | `GET /api/aftersales/customers/provinces` | Opsi Province | Form customer dan koreksi lokasi import | §3.4 |
| C06 | `GET /api/aftersales/customers/regions?province_id={id}` | Opsi City sesuai Province | Setelah Province dipilih | §3.4 |
| C07 | `GET /api/aftersales/customers/{customer}/phone-changes` | Audit perubahan nomor WhatsApp | Riwayat nomor pada detail/edit bila ditampilkan | §3.5 |
| C08 | `DELETE /api/aftersales/customers/{customer}` | Soft delete customer | Aksi Delete Customer bila tersedia pada menu | §3.5 |
| I01 | `GET /api/aftersales/customers/imports/template` | Download file Excel asli | Import step 1 → Download .xlsx (gambar 2) | §4.1 |
| I02 | `POST /api/aftersales/customers/imports` | Upload dan buat preview | Import step 2 → Browse / Drop file (gambar 3) | §4.2 |
| I03 | `GET /api/aftersales/customers/imports/{import}` | Ambil ulang preview batch milik uploader | Resume / reload import step 3 | §4.3 |
| I04 | `PUT /api/aftersales/customers/imports/{import}/review` | Simpan koreksi dan keputusan matching | Import step 3 → Link / Create new / Skip / koreksi (gambar 4) | §4.4 |
| I05 | `POST /api/aftersales/customers/imports/{import}/apply` | Commit hasil review | Import step 3 → Apply | §4.5 |
| I06 | `GET /api/aftersales/customers/imports/{import}/export` | Download review, warning, error, hasil | Import step 3 → Download Error File | §4.6 |
| M01 | `GET /api/aftersales/customers/machine-references` | Category, prefix, Industry, PM | Add/Edit Machine (gambar 8) dan dropdown review | §5.1 |
| M02 | `POST /api/aftersales/customers/{customer}/products` | Tambah unit mesin | Save Machine pada New Machine | §5.2 |
| M03 | `PUT /api/aftersales/customers/products/{product}` | Edit unit mesin | Save Machine pada Edit Machine | §5.2 |
| M04 | `POST /api/aftersales/customers/products/{product}/move` | Pindah pemilik mesin | Menu … → Move Machine → submit modal (gambar 7) | §5.3 |
| M05 | `DELETE /api/aftersales/customers/products/{product}` | Soft delete unit mesin | Aksi Delete Machine bila tersedia | §5.4 |
| M06 | `POST /api/masters/product-types/save/{id}` | Ubah prefix kategori yang sudah ada | Master Product Types, bukan form unit mesin | §5.5 |
| D01 | `GET /api/aftersales/customers/deal-reviews` | Antrean Possible Deal / Lead updated | Customer list → review konversi/perubahan | §6.1 |
| D02 | `POST /api/aftersales/customers/deal-reviews/{deal}/resolve` | Putuskan link/create/apply/ignore | Submit dialog review Deal / Lead updated | §6.2 |
| T01 | `GET /api/aftersales/tickets/supervisors` | Opsi Supervisor After Sales | Add/Edit Ticket → Supervisor | §7.1 |
| T02 | `GET /api/aftersales/tickets` | List tiket dan reminder | All Ticket → kolom STEPS | §7.2 |
| T03 | `GET /api/aftersales/tickets/{ticket}` | Detail, logs, dan reminder | Ticket Detail / buka Update Logs | §7.2 |
| T04 | `POST /api/aftersales/tickets/{ticket}/happy-call` | Catat Happy Call Completed | Update Logs → Activity Happy Call Completed (gambar 9) | §7.3 |
| T05 | `POST /api/aftersales/tickets/{ticket}/close` | Tutup manual dengan guard | Update Logs → Activity Closed | §7.4 |

Survei tiga level menggunakan endpoint survei/template/teknisi yang sudah ada; pemetaan request dan response per fitur ada pada §8. Tidak ada endpoint baru untuk mengonversi Lead dari FE: proses otomatis berjalan dari perubahan Lead di backend.

## 3. Customer

Semua C01–C08 membutuhkan permission `masters.service-customers`. C04 juga membutuhkan `aftersales.customer-phone.change` bila nomor telepon berubah.

### 3.1 C01 — Customer list / pilihan tujuan Move

Query opsional: `search` (nama, telepon, atau nama Region), `page` (default 1), `per_page` (default 15, dibatasi 1–100). Tidak ada filter `source` yang diimplementasikan. Urutan ID terbaru dahulu.

Response 200 (contoh field konsumsi FE):

```json
{
  "status": "success",
  "data": [{
    "id": 8,
    "name": "Global Dynamic",
    "pic_name": "Raisa Sakila",
    "phone": "6285781357651",
    "email": null,
    "address": "Jl. Contoh No. 1",
    "address_maps_url": "https://maps.app.goo.gl/contoh",
    "ref_province_id": 36,
    "ref_region_id": 3671,
    "province": {"id": 36, "name": "Banten"},
    "region": {"id": 3671, "name": "Kota Tangerang", "province_id": 36},
    "source": "manual",
    "products_count": 3,
    "lead_no_longer_deal": false,
    "lead_updated": false,
    "deals": []
  }],
  "total": 1,
  "current_page": 1,
  "last_page": 1
}
```

Kolom SOURCE: `manual` → Manual, `import` → Import, `deal_conversion` → Deal. Tambahkan badge Lead no longer Deal bila `lead_no_longer_deal=true`; indikator ini terpisah dari `source`. `lead_updated=true` membuka review D01/D02. Nilai kosong pada PIC/lokasi/phone ditampilkan sebagai placeholder UI.

Untuk modal Move, gunakan endpoint yang sama dengan `search`. Muat halaman berikutnya bila hasil dropdown belum lengkap; jangan menganggap halaman pertama berisi semua customer. Sembunyikan pemilik saat ini dari pilihan tujuan.

### 3.2 C02 — Detail / Machine List

Tidak ada request body. Response 200 adalah `{"status":"success","data":{...}}`. `data` berisi field customer C01 (tanpa jaminan `products_count`) ditambah:

| Path | Tipe | Pemakaian FE |
|---|---|---|
| `data.products` | array Machine, dapat kosong | Sumber tabel Machine List; tidak ada endpoint list mesin terpisah |
| `data.products[].product` | object atau null | Master Product; null untuk impor/manual tanpa Product |
| `data.products[].product_type` | object atau null | Label Model dan prefix; nama JSON adalah `product_type` |
| `data.products[].industry` | object atau null | Label industri |
| `data.deals` | array CustomerDeal | Status hubungan Lead; masing-masing memuat `lead` dan `lead.quotation` bila tersedia |

Struktur Machine ada di §5.2. Untuk Edit Machine, cari `data.products` berdasarkan **ID unit mesin**. Daftar mesin tidak dipaginasi oleh endpoint ini; sorting/filter tabel mesin dapat dilakukan FE. Setelah Save/Move/Delete, ambil ulang C02 dan C01 bila badge/jumlah pada list ikut ditampilkan.

### 3.3 C03/C04 — Add/Edit Customer

Request JSON yang sama untuk kedua form; hanya `name` wajib pada Create. Pada Update, `name` boleh tidak dikirim, tetapi bila dikirim tidak boleh kosong.

```json
{
  "name": "Global Dynamic",
  "address": "Jl. Contoh No. 1",
  "address_maps_url": "https://maps.app.goo.gl/contoh",
  "ref_province_id": 36,
  "ref_region_id": 3671,
  "pic_name": "Raisa Sakila",
  "email": "pic@example.com",
  "phone": "081234567890"
}
```

| Field | Validasi |
|---|---|
| `name` | string, maks 255; wajib Create |
| `address` | nullable string |
| `address_maps_url` | nullable URL Google Maps, maks 2048; domain palsu ditolak |
| `ref_province_id`, `ref_region_id` | nullable integer, harus ada di master |
| `pic_name` | nullable string maks 255 |
| `email` | nullable email maks 255 |
| `phone` | nullable string maks 50; form manual tidak otomatis menormalisasi ke `62` |

Province diselaraskan oleh backend mengikuti Region. Pada Edit, kirim pasangan lokasi yang lengkap bila ingin mengubahnya; mengirim `ref_region_id=null` dengan Province berbeda dapat tetap memakai Region lama untuk penurunan Province. Jangan mengasumsikan bahwa mengosongkan Region otomatis mengosongkan Province.

Response Create 201 / Update 200: `{"message":"Customer created successfully","data":{"data":{"id":8,"name":"Global Dynamic"}}}`; Update memakai message `Customer updated successfully`. Field lain di objek mengikuti data tersimpan. Nilai default database seperti `source` belum tentu hadir pada response Create sebelum reload. **Setelah sukses panggil C02/C01**, bukan mengharuskan mutation response memuat seluruh badge/relasi.

Open Maps tidak memanggil API: buka `address_maps_url` di tab baru jika terisi, dengan `rel="noopener noreferrer"`. Tidak ada endpoint geocoding/autofill alamat.

Kompatibilitas form FE: `maps_link` diterima sebagai alias `address_maps_url` pada Create/Update Customer dan dikembalikan pada JSON/model Blade dengan nilai yang sama. Keduanya memakai validasi Google Maps; jika kedua field dikirim dengan nilai berbeda, request ditolak 422 pada `errors.maps_link`. Penyimpanan utama tetap `address_maps_url`, sehingga data dari impor dan Deal memakai field yang konsisten. Migration `2026_10_22_110000_reconcile_customer_maps_fields` menyalin Maps lama dari kolom FE hanya bila nilai backend masih null.

### 3.4 C05/C06 — Dropdown Province dan City

C05 tanpa query/body → 200:

```json
{"status":"success","data":[{"id":36,"name":"Banten"}]}
```

C06 wajib query `province_id` → 200:

```json
{"status":"success","data":[{"id":3671,"name":"Kota Tangerang"}]}
```

Reset opsi City ketika Province berubah. Customer form mengirim ID master, sedangkan **koreksi impor mengirim nama** Province/City pada header template, bukan `ref_region_id`. Respons Region C06 hanya berisi `id,name`; Province diketahui dari query yang dikirim.

### 3.5 C07/C08 — Audit phone dan delete customer

C07 tanpa body → 200:

```json
{"status":"success","data":[{"old_phone":"081111111111","new_phone":"082222222222","changed_by":"Admin","changed_at":"2026-10-22T03:00:00.000000Z"}]}
```

C08 tanpa body → 200: `{"message":"Customer deleted successfully"}`. Setelah sukses tutup detail dan reload C01. Ini soft delete; tidak ada response objek customer.

## 4. Import Customer — step 1, 2, dan 3

Semua I01–I06 membutuhkan `masters.service-customers`. Untuk batch yang sudah dibuat, hanya **uploader batch tersebut** yang dapat mengaksesnya; user After Sales lain tetap mendapat 403.

### 4.1 I01 — Step 1 Download Template

GET tanpa body → 200 binary Excel dengan `Content-Disposition: attachment`, nama `DAXX_Import_Customer_Template.xlsx`. File berisi sheet Import Customer, Guide, Reference. Ini download langsung, tidak membuat batch dan tidak mengubah step backend.

### 4.2 I02 — Step 2 Upload

Kirim multipart dengan satu field `file`: `.xlsx`, maksimal 20 MB, maksimal 5000 baris data. Header wajib identik dengan template; jangan membuat file CSV atau mengunggah layout spreadsheet lama.

```js
const form = new FormData();
form.append('file', selectedFile);
// Gunakan instance HTTP aplikasi yang sudah menangani cookie dan CSRF.
const response = await api.post('/api/aftersales/customers/imports', form);
const preview = response.data.data; // Axios: response.data adalah body JSON.
```

Response 201 menggunakan schema Preview §4.3. Simpan `preview.import.id` dan `preview.import.lock_version`, lalu render step 3. Upload hanya menyimpan staging review; Customer/Machine belum dibuat. Bila 422, tetap di step 2 dan tampilkan `errors.file` atau `message`.

**Perbedaan dari mockup:** Next tanpa file belum didukung backend. Jangan menampilkan data dummy sebagai preview nyata; step 3 baru aktif setelah upload sukses. Tidak ada endpoint Back/Next/Cancel/delete batch atau endpoint list batch. Perpindahan step bersifat state FE; bila ingin resume, FE harus menyimpan ID batch hasil upload. Menutup modal tidak menjalankan Apply.

### 4.3 I03 dan schema Preview bersama

I03 tanpa body → 200. I02 (201), I03 (200), I04 (200), I05 (200) memakai envelope yang sama:

```json
{
  "data": {
    "import": {
      "id": 15,
      "file_name": "customers.xlsx",
      "status": "review",
      "lock_version": 0,
      "created_count": 0,
      "matched_count": 0,
      "error_count": 0,
      "created_by_user_id": 7,
      "applied_at": null,
      "created_at": "2026-10-22T03:00:00.000000Z",
      "updated_at": "2026-10-22T03:00:00.000000Z"
    },
    "summary": {"exact":0,"possible":0,"new":1,"already_exists":0,"error":0,"warning":1},
    "rows": [{
      "id": 21,
      "customer_import_id": 15,
      "row_number": 2,
      "input": {
        "Customer Name*":"Global Dynamic","PIC Name":"Raisa Sakila","Phone":"081234567890",
        "Email":"pic@example.com","Address":"Jl. Contoh No. 1","Google Maps Link":"",
        "Province":"Banten","City / Region":"Kota Tangerang","Industry":"",
        "Machine Category*":"Tube","Machine Name*":"TD 5","Brand":"Daxtro","Capacity":"5",
        "Serial Number":"SN-001","Warranty Start":"21/10/2026","Warranty End":"","PM Contract":"Basic","Note":""
      },
      "review": {
        "status":"new",
        "errors":[],
        "warnings":["Google Maps Link is empty.","Industry is empty.","Warranty End is empty.","Note is empty."],
        "customer":{"name":"Global Dynamic","pic_name":"Raisa Sakila","phone":"6281234567890","email":"pic@example.com","address":"Jl. Contoh No. 1","address_maps_url":null,"ref_province_id":36,"ref_region_id":3671},
        "machine":{"product_id":null,"machine_name":"TD 5","ref_product_type_id":1,"industry_id":null,"brand":"Daxtro","capacity":"5","serial_number":"SN-001","warranty_start":"2026-10-21","warranty_end":"2027-10-21","pm_contract_status":"basic","note":null},
        "candidates":[]
      },
      "decision":null,
      "result":null
    }]
  }
}
```

`input` adalah nilai asli spreadsheet; tanggal Excel dapat berupa angka serial, bukan selalu string. Pakai `review.customer` / `review.machine` untuk preview nilai hasil parsing dan `input` untuk editor koreksi. `rows` tidak dipaginasi. Field tanggal pada `review.machine` memakai `YYYY-MM-DD`, berbeda dari serialisasi model Machine pada §5.

| Field | Makna / render FE |
|---|---|
| `import.status` | `review` atau `applied`; status applied mengunci editor dan Apply |
| `summary.exact/possible/new/already_exists/error` | Jumlah masing-masing status review; total kelimanya = jumlah rows |
| `summary.warning` | Jumlah **baris** dengan minimal satu warning; overlap dengan status lain |
| `import.created_count` | Jumlah Customer baru saat Apply, bukan jumlah mesin |
| `import.matched_count` | Jumlah baris yang memakai Customer yang sudah ditemukan saat Apply; bukan jumlah Customer unik |
| `import.error_count` | Jumlah baris Error yang dilewati saat Apply; sebelum Apply masih 0 |
| `row.review.status` | Status matching/validasi pada preview, tidak berubah menjadi `applied` setelah commit |
| `row.result` | null sebelum Apply; hasil commit per baris setelah Apply |

Filter tab All / Needs decision / Warning / Error pada step 3 dilakukan **di FE** atas `rows`; backend tidak menyediakan query filter preview. Needs decision = `review.status==='possible'` dan belum ada `decision`. Warning = `review.warnings.length>0`.

Candidate pada Possible/Exact berbentuk union:

```json
[
  {"type":"customer","id":8,"status":"possible","name":"Global Dynamic","phone":"6281234567891","ref_region_id":3671},
  {"type":"lead","id":50,"status":"possible","customer_id":null,"name":"Mr Global Dynamic","phone":"081234567891"},
  {"type":"row","id":20,"row_number":2,"status":"exact","name":"Global Dynamic"}
]
```

Daftar di atas hanya contoh bentuk tiap tipe, bukan hasil matching dari preview contoh sebelumnya. Candidate tidak selalu memiliki field yang sama. `type=lead` menggunakan `id` Lead dan dapat memiliki `customer_id` bila sudah ditautkan; `type=row` menggunakan ID staging row.

### 4.4 I04 — Step 3 simpan keputusan / koreksi

Kirim JSON dengan `lock_version` terbaru dan minimal satu item `rows`. `rows[].id` wajib unik dan milik batch URL. Maksimal 5000 items.

```json
{
  "lock_version":0,
  "rows":[
    {"id":21,"decision":{"action":"link","customer_id":8}},
    {"id":22,"decision":{"action":"create_new"}},
    {"id":23,"decision":{"action":"skip"}},
    {"id":24,"input":{"Province":"Jawa Timur","City / Region":"Ponorogo","Phone":"081234567890","Warranty Start":"21/10/2026"}}
  ]
}
```

| Pilihan FE | `decision` yang dikirim |
|---|---|
| Link existing Customer | `{"action":"link","customer_id":8}` |
| Link Sales Deal | `{"action":"link","lead_id":50}` |
| Gabung ke Customer hasil baris terdahulu | `{"action":"link","row_id":20}` |
| Create new | `{"action":"create_new"}` |
| Skip row | `{"action":"skip"}` |
| Hapus keputusan | `null` |

Link harus memiliki tepat satu target. `row_id` harus menunjuk baris lebih awal dalam batch yang sama; bila target akhirnya dilewati/tidak menghasilkan Customer, Apply ditolak 422. Jangan mengirim pilihan placeholder sebagai ID 0.

Koreksi `input` menggunakan **nama header persis**, termasuk `*`, spasi, dan huruf besar. Kirim hanya kolom yang dikoreksi. Tidak ada field koreksi `ref_region_id` langsung: dropdown memakai C05/C06, lalu kirim nama Province/City ke `input`. Unknown Industry boleh null dengan warning; category yang tidak ditemukan menjadi Error. Field template lain boleh dikoreksi dengan cara yang sama.

Response 200 = Preview §4.3, dengan `lock_version` naik satu dan seluruh baris direview ulang. **Ganti state preview FE dengan seluruh response**, karena status/warning/candidate baris lain bisa berubah. Keputusan lama dipertahankan hanya bila hasil review baris tetap sama; koreksi dapat menghapus keputusan yang perlu dipilih ulang. Tunggu request review selesai sebelum Apply; serialize autosave review agar tidak mengirim revisi yang sama secara paralel.

### 4.5 I05 — Apply

Request: `{"lock_version":1}`. Response 200 = Preview §4.3, dengan `import.status="applied"`, `applied_at` terisi, counters diperbarui, revisi naik satu. Contoh `rows[].result`:

```json
{"status":"applied","customer_id":8,"machine_id":30}
```

Atau `{"status":"skipped"}` untuk Error / Already exists dari preview / keputusan Skip. Jika serial baru ditemukan sudah ada **ketika Apply**, hasil baris menjadi `{"status":"already_exists"}`. Jangan mengasumsikan semua baris non-Error selalu berhasil; baca `result`.

Possible tanpa keputusan memblok seluruh Apply (422), termasuk Possible yang ingin dilewati: tetap kirim keputusan Skip lebih dahulu. Error dan Already exists tidak dapat dipaksa masuk melalui Create new; keduanya dilewati. Warning saja tidak memblok. Konflik customer yang muncul setelah preview dapat mengembalikan 409 dan membatalkan transaksi; lakukan GET I03, jalankan PUT I04 untuk mereview ulang, kemudian minta user memeriksa keputusan terbaru.

Request ulang untuk batch yang sudah applied mengembalikan hasil batch yang sama tanpa membuat data baru. Setelah sukses, tampilkan hasil/counter, tutup modal sesuai UX, dan reload C01 serta C02 bila halaman mesin terbuka. Apply bukan endpoint preview: jangan memanggilnya saat Next dari step 2.

### 4.6 I06 — Download Error File

GET tanpa body → 200 binary `.xlsx`, MIME `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, filename `customer-import-{id}-review.xlsx`. File berisi **semua baris review**, bukan hanya Error: kolom template ditambah Row, Status, Errors, Warnings, Decision, Result. Hasil export bukan template upload karena header tambahan; jika ingin upload ulang, pindahkan data koreksi ke template asli I01.

## 5. Machine dan konfigurasi

M01–M05 membutuhkan `masters.service-customers`. Tidak ada endpoint terpisah untuk menghitung code/warranty/frequency sebelum Save; FE dapat menghitung preview lokal, tetapi nilai response backend menjadi acuan setelah Save.

### 5.1 M01 — Dropdown dan prefix

GET tanpa body → 200:

```json
{"data":{"categories":[{"id":1,"name":"Tube","code_prefix":"TD"},{"id":2,"name":"Cube","code_prefix":"CD"}],"industries":[{"id":1,"name":"Manufacturing"}],"pm_contracts":{"none":null,"basic":"6 months","premium":"3 months"}}}
```

Label Model pada mockup menggunakan `categories`, bukan Product. Refrigerant tetap free text; tidak ada endpoint opsi Refrigerant. `product_id` bersifat opsional dan tidak perlu dipilih untuk mesin impor/manual. Gunakan dropdown PM berlabel None/Basic/Premium dengan nilai lowercase `none/basic/premium`. Legacy PM di luar daftar dapat ditampilkan belum dipilih; jangan otomatis menimpa nilai lama sampai user memilih PM baru.

### 5.2 M02/M03 — Save Machine

Request contoh:

```json
{"product_id":null,"machine_name":"CD 50 - Cube Ice Machine","serial_number":"SN-001","ref_product_type_id":2,"industry_id":1,"brand":"Daxtro","capacity":"50","refrigerant":"R404A","note":"Optional note","warranty_start":"2026-10-21","warranty_end":"2027-10-21","pm_contract_status":"basic"}
```

| Field | Create dan Update |
|---|---|
| `machine_name`, `serial_number` | Wajib setiap request, string maks 255. Serial placeholder/kosong ditolak; serial harus unik setelah normalisasi, termasuk unit soft-deleted |
| `product_id`, `ref_product_type_id`, `industry_id` | Opsional nullable integer, ID harus ada di master |
| `brand`, `refrigerant` | Opsional nullable string maks 255 |
| `capacity` | Opsional nullable numeric ≥ 0, satuan ton; tersimpan sebagai kolom teks, sehingga response dapat string |
| `note` | Opsional nullable string maks 10000 |
| `warranty_start`, `warranty_end` | Opsional nullable tanggal; kirim YYYY-MM-DD |
| `pm_contract_status` | Opsional; bila dikirim wajib `none/basic/premium`, bukan null |
| `machine_code`, `warranty_period`, `pm_frequency` | Read-only, jangan dikirim sebagai input |

Serial boleh awalnya null dari Deal/import, tetapi saat user Save lewat form manual wajib dilengkapi. `ref_product_type_id` secara validasi backend nullable; bila UX mewajibkan Model, validasi itu berada di FE.

Response M02 201 / M03 200:

```json
{
  "message":"Product unit added successfully",
  "data":{"data":{
    "id":30,"customer_id":8,"product_id":null,
    "machine_name":"CD 50 - Cube Ice Machine","serial_number":"SN-001",
    "ref_product_type_id":2,"industry_id":1,"brand":"Daxtro","capacity":"50","refrigerant":"R404A","note":"Optional note",
    "warranty_start":"2026-10-21T00:00:00.000000Z","warranty_end":"2027-10-21T00:00:00.000000Z","warranty_period":"1 Year",
    "pm_contract_status":"basic","pm_frequency":"6 months","machine_code":"CD 50","warranty_active":true,
    "product":null,"product_type":{"id":2,"name":"Cube","code_prefix":"CD"}
  }}
}
```

M03 message: `Product unit updated successfully`. Contoh Machine adalah subset field model; tanggal model diserialisasi sebagai timestamp ISO, jadi normalisasikan kembali ke tanggal kalender saat mengisi date input. `warranty_active` bergantung tanggal server, jangan hardcode contoh di atas.

Aturan preview FE dan penyimpanan:

- Code = `code_prefix + ' ' + capacity` tanpa pengali. Bila prefix/capacity tidak ada, code null. Nama mesin tetap diketik user; jangan menimpa ketika Model berubah.
- End default = Start + Product warranty months bila Product punya warranty; selain itu 12 bulan. Pada Add tanpa Product gunakan 12 bulan. Detail relasi `product` pada mesin yang sudah memiliki Product dapat dipakai membaca konfigurasi warranty. Tidak ada endpoint kalkulasi tanggal tersendiri.
- Start baru/berubah dan End tidak diubah → backend menghitung ulang End. Jika user mengedit End, kirim tanggal End yang dimaksud. End null dengan Start terisi akan diisi otomatis lagi, bukan disimpan kosong.
- Period = selisih bulan penuh; kelipatan 12 ditampilkan Year/Years, selain itu Months. End sebelum Start → 422 `errors.warranty_end`.
- Basic → `6 months`, Premium → `3 months`, None → null. Frequency tidak dapat diedit. PM lama dipertahankan pada edit yang tidak mengirim pilihan baru.

Sesudah Save, reload C02. Jangan menambahkan `customer_id` ke Update untuk memindah mesin; gunakan M04.

### 5.3 M04 — Move Machine modal

Buka dari menu … pada baris unit. Ambil pilihan tujuan dari C01, isi Note awal `Previously owned by [customer lama]`, lalu submit:

```json
{"customer_id":42,"note":"Previously owned by Global Dynamic"}
```

`customer_id` wajib integer customer yang masih tersedia. Note nullable, maks 10000. Omitted/blank Note memakai default backend. Response 200:

```json
{"data":{"id":30,"customer_id":42,"machine_name":"CD 50 - Cube Ice Machine","serial_number":"SN-001","note":"Optional note\nPreviously owned by Global Dynamic","machine_code":"CD 50","pm_contract_status":"basic","pm_frequency":"6 months","customer":{"id":42,"name":"PT Destination"},"product_type":{"id":2,"name":"Cube","code_prefix":"CD"}}}
```

Objek `data` juga berisi field Machine lain, termasuk warranty yang dipertahankan. Unit tidak digandakan. Note ditambahkan ke Note lama. Ticket lama tetap milik customer lama. Sesudah sukses tutup modal, reload Machine List customer asal; mesin sudah tidak muncul di sana. Reload dropdown/list jika jumlah mesin ditampilkan.

422 open ticket: `{"message":"This machine has open tickets. Close them first."}`. 422 tujuan sama: `{"message":"Select a different destination customer."}`. Pertahankan modal dan tampilkan pesan, jangan menghapus baris secara optimistis sebelum response sukses.

### 5.4 M05 — Delete Machine

DELETE tanpa body → 200 `{"message":"Product unit deleted successfully"}`. Reload C02 setelah sukses. Endpoint soft delete; tidak mengembalikan Machine.

### 5.5 M06 — Master category prefix

Untuk kategori existing kirim `POST /api/masters/product-types/save/2` dengan `{"name":"Cube","code_prefix":"CD"}`. `name` wajib; `code_prefix` opsional nullable, alfanumerik maks 20 dan disimpan uppercase. Mengirim prefix membutuhkan `masters.product-types`. Response 200 `{"message":"Product type saved successfully"}`; reload M01 untuk membaca prefix hasil simpan. Route master `/save` tanpa ID juga tersedia untuk membuat kategori dengan format sama. Form mesin tidak perlu memanggil endpoint ini.

## 6. Sales Deal review dan Lead updated

D01/D02 membutuhkan `masters.service-customers`. Jangan membuat customer manual sebagai pengganti proses review; konversi otomatis dan antrean review sudah ditangani backend.

### 6.1 D01 — Antrean review

Query `page` opsional. Ukuran halaman tetap 25; tidak ada filter `search`, `status`, `customer_id`, atau `per_page` khusus. Data diurutkan ID naik. Filter item per customer di FE bila dialog dibuka dari satu customer; ambil halaman berikutnya bila diperlukan.

Response 200 memakai **paginator Laravel di root**, bukan `{"data":{"data":...}}`:

```json
{
  "current_page":1,
  "data":[{
    "id":12,"service_customer_id":null,"lead_id":50,"status":"needs_review",
    "candidates":[{"type":"customer","id":8,"status":"possible","name":"Global Dynamic","phone":"6281234567891","ref_region_id":3671}],
    "lead_snapshot":{"name":"Mr Global Dynamic","phone":"6281234567890","email":null,"address":null,"ref_region_id":3671,"ref_province_id":36},
    "pending_changes":null,"left_deal_at":null,
    "created_at":"2026-10-22T03:00:00.000000Z","updated_at":"2026-10-22T03:00:00.000000Z",
    "lead":{"id":50,"name":"Mr Global Dynamic","quotation":null},"customer":null
  }],
  "first_page_url":"https://example.test/api/aftersales/customers/deal-reviews?page=1",
  "from":1,"last_page":1,"last_page_url":"https://example.test/api/aftersales/customers/deal-reviews?page=1",
  "links":[],"next_page_url":null,"path":"https://example.test/api/aftersales/customers/deal-reviews","per_page":25,"prev_page_url":null,"to":1,"total":1
}
```

Paginator mengirim `links` navigasi standar Laravel; contoh `links` di atas disingkat. Dua mode dialog:

| Kondisi item | Isi dialog | Aksi |
|---|---|---|
| `status=needs_review` | Lead/snapshot dan candidate customer | Link existing atau Create new |
| `service_customer_id` terisi dan `pending_changes` tidak null | Data `customer` saat ini dibanding `pending_changes` | Apply atau Ignore |

`pending_changes` adalah snapshot **lengkap** field customer dari Lead, bukan daftar patch `{old,new}`; hitung perbedaannya di FE. `left_deal_at` adalah indikator Lead keluar Deal, bukan perintah menghapus customer/mesin/tiket.

### 6.2 D02 — Resolve

Kirim `updated_at` **persis seperti JSON D01**, termasuk pecahan detik dan `Z`; jangan format ulang menggunakan tanggal tampilan.

```json
{"action":"link","customer_id":8,"updated_at":"2026-10-22T03:00:00.000000Z"}
```

| Action | Berlaku pada | Field tambahan | Efek |
|---|---|---|---|
| `link` | needs_review | `customer_id` wajib | Tautkan Lead ke existing customer |
| `create_new` | needs_review | Tidak kirim customer_id | Buat Customer dan mesin quotation yang memenuhi aturan |
| `apply` | linked + pending_changes | Tidak kirim customer_id | Salin snapshot customer terkini dari Lead; field tambahan After Sales seperti Maps tetap |
| `ignore` | linked + pending_changes | Tidak kirim customer_id | Akui snapshot tanpa menimpa Customer |

Apply yang mengubah phone membutuhkan permission tambahan `aftersales.customer-phone.change`. Response 200: `{"data":{"id":12,"service_customer_id":8,"lead_id":50,"status":"linked","candidates":null,"lead_snapshot":{"name":"Mr Global Dynamic","phone":"6281234567890","email":null,"address":null,"ref_region_id":3671,"ref_province_id":36},"pending_changes":null,"left_deal_at":null,"created_at":"2026-10-22T03:00:00.000000Z","updated_at":"2026-10-22T03:01:00.000000Z"}}`.

Relasi `lead/customer` pada response resolve tidak selalu dimuat; reload D01 dan C01/C02 setelah sukses. Item yang sudah tidak needs_review dan tidak punya pending_changes hilang dari antrean. 409 berarti timestamp stale, sudah resolved, tidak ada pending perubahan, atau Lead sudah tidak eligible; reload antrean dan tampilkan pesan. Tidak ada action `skip` untuk antrean Deal; Skip hanya tersedia pada impor.

## 7. Ticket: Supervisor, reminder, dan Update Logs

### 7.1 T01 — Opsi Supervisor

Permission `aftersales.tickets.manage`. GET tanpa body → 200:

```json
{"status":"success","data":[{"id":55,"name":"Supervisor A"}]}
```

`id` di sini adalah User ID. Simpan sebagai `supervisor_id` pada endpoint Ticket existing `POST /api/aftersales/tickets` atau `PUT /api/aftersales/tickets/{ticket}` bersama payload Ticket lengkap existing; jangan memakai endpoint Happy Call untuk mengubah supervisor. Supervisor baru harus dari role `supervisor_after_sales`. Pada tiket lama, supervisor existing mungkin tidak ada dalam dropdown baru: tampilkan nilai existing dari detail, jangan menghapus otomatis; mempertahankan ID existing diizinkan. Memilih user baru di luar role mengembalikan 422 pada `errors.supervisor_id`.

### 7.2 T02/T03 — Baca reminder dan logs

T02 untuk manager memakai `aftersales.tickets.manage`; teknisi memakai `aftersales.tickets.view-own` dan hanya melihat tiket sendiri. T03 mempertahankan aturan akses detail existing. Query T02 yang relevan: `page`, `per_page` (default 15), `search`, `customer_id`, `supervisor=me`, `supervisor_id`, `category`, `priority`, `assigned_technician_id`, `progress`. Filter progress saat ini dilakukan setelah pagination backend; jangan menghitung total hasil progress sebagai jumlah global yang pasti.

Response list 200: `{"status":"success","data":[Ticket],"total":1,"current_page":1,"last_page":1}`. Detail 200: `{"status":"success","data":Ticket}`. Field tambahan berikut ada pada kedua Ticket:

```json
{
  "id":70,
  "ticket_code":"DAX-AF-20261022-1",
  "happy_call_required":true,
  "progress":"satisfaction_submitted",
  "reminders":{
    "survey_due_at":"2026-10-25T03:00:00.000000Z",
    "survey_due":false,
    "happy_call_due":true,
    "happy_call_completed":false,
    "happy_call_required":true,
    "rating_scale_max":3
  }
}
```

Ini **potongan objek Ticket**, bukan envelope endpoint tambahan. Field lain mengikuti kontrak Ticket baseline [integrate-contract.md](integrate-contract.md). Render Survey due / Happy Call due di bawah STEPS berdasarkan boolean backend, bukan perhitungan tanggal FE. `survey_due_at` boleh null. Due hanya reminder; jangan men-disable aktivitas lain hanya karena due=true. Tiket Closed tidak mempunyai due reminder aktif.

Detail memuat `data.logs` untuk tabel Update Logs, berupa array TicketLog dengan `step`, `note`, `actor_user_id`, timestamp dan relasi `actor` bila tersedia. Gunakan `actor.name` untuk USER, `created_at` untuk DATE. `happy_call_completed` adalah nilai API; label UI Happy Call Completed. List tidak mengekspos jawaban survei untuk teknisi.

### 7.3 T04 — Happy Call Completed

Permission `aftersales.tickets.manage`. Request `{"note":"Customer sudah dihubungi"}`; Note opsional nullable string maks 10000. Response 201:

```json
{"data":{"id":105,"ticket_id":70,"step":"happy_call_completed","actor_user_id":7,"note":"Customer sudah dihubungi","created_at":"2026-10-22T03:10:00.000000Z","updated_at":"2026-10-22T03:10:00.000000Z"}}
```

Pengulangan request pada cycle yang sama tetap 201 dan mengembalikan log existing; **bukan mengedit Note existing**. Tidak ada endpoint edit Note Happy Call dalam improvement ini. Tiket Closed atau sedang Recoming → 422. Setelah sukses reload T03 (logs, progress, reminder) dan T02 bila list terlihat.

T04 tidak memvalidasi bahwa survei sudah submitted atau berumur satu hari. FE jangan mengasumsikan backend menolak Happy Call lebih awal. Bila user memilih Activity lain pada Update Logs, gunakan endpoint aktivitas existing yang sesuai; tidak ada satu endpoint generic `POST /logs` pada scope ini.

### 7.4 T05 — Closed

Permission `aftersales.tickets.close`. Request `{"note":"Pekerjaan selesai"}`; Note opsional nullable string. Response 201:

```json
{"message":"Ticket closed successfully","data":{"data":{"id":106,"ticket_id":70,"step":"closed","actor_user_id":7,"note":"Pekerjaan selesai","created_at":"2026-10-22T03:15:00.000000Z","updated_at":"2026-10-22T03:15:00.000000Z"}}}
```

Guard backend berurutan: tiket belum Closed; Happy Call pada cycle saat ini jika `happy_call_required=true`; documentation_published pada cycle; kemudian latest survey response tidak needs_review/rejected. Tidak otomatis Closed ketika Happy Call disimpan. Tiket lama dapat `happy_call_required=false`; jangan memblokir di FE hanya karena `happy_call_completed=false` pada tiket tersebut.

Contoh gagal Happy Call (422):

```json
{"message":"Cannot set to Closed. Happy Call Completed must be recorded first."}
```

Contoh gagal review (422) mempunyai envelope khusus:

```json
{"status":"error","message":"Cannot set to Closed. The latest survey response is Needs review. Review it first.","satisfaction_id":88}
```

Untuk Rejected message berbeda, tetapi `satisfaction_id` tetap menunjuk response yang harus ditangani. Tampilkan message; bila user punya akses review, sediakan navigasi ke response tersebut. Pertahankan modal ketika gagal. Sesudah sukses reload detail/list untuk status dan SLA terbaru.

## 8. Integrasi survei tiga level pada fitur existing

Bagian ini mencatat endpoint existing yang terdampak skala, bukan endpoint baru. Untuk struktur Ticket/Survey review baseline lengkap gunakan [integrate-contract.md](integrate-contract.md); bila contoh skor baseline masih `/5` atau di atas 3, **kontrak skala di dokumen ini yang berlaku**. Jangan menormalisasi lagi di FE: skor lama sudah dikonversi backend dan nilai original hanya untuk audit.

| Method dan endpoint | Penempatan FE / kegunaan | Request yang relevan | Response sukses yang dikonsumsi |
|---|---|---|---|
| `GET /api/aftersales/tickets/{ticket}/survey-link` | Detail tiket → kirim survei / isi atas nama customer | Tanpa body; manager atau assigned teknisi dengan permission share | 200 `status,data`; `data.template.scale_labels`, `sections`, `status`, `url`, `can_share`, `shared_at` |
| `POST /api/aftersales/tickets/{ticket}/survey-link/share` | Tombol Copy link / WhatsApp | `{"via":"copy"}` atau `{"via":"whatsapp"}` | 200 `status,message,data`; `data` sama dengan survey-link dan shared_at terbaru |
| `POST /api/aftersales/tickets/{ticket}/survey-response` | After Sales → isi atas nama customer | Jawaban dinamis + identitas + `reason` wajib; signature opsional | 201 `message,data.data` berisi TicketSatisfaction; source admin, needs_review |
| `GET /api/survey/{token}` | Landing survei customer | Token URL, tidak perlu login internal | 200 `status,data`; `data.state`, `locked_until`, `ticket_code`, `customer`, `phone_masked` |
| `POST /api/survey/{token}/otp/send` | Tombol kirim OTP | Tanpa body | 200 `status,message,data`; `phone_masked`, `expires_at`, `resend_available_at` |
| `POST /api/survey/{token}/otp/verify` | Submit OTP | `{"code":"123456"}` | 200 `status,message,data`; `session_token`, `session_expires_at` |
| `GET /api/survey/{token}/form` | Form survei setelah OTP | Header `X-Survey-Session: {session_token}` | 200 `status,data`; `data.job_information`, `aspects`, `template` |
| `POST /api/survey/{token}/submit` | Submit survei customer | Header session survei + payload dinamis di bawah | 201 `{"status":"success","message":"Thank you, your assessment has been submitted","data":{"submitted_at":"2026-10-22T03:00:00.000000Z"}}` |
| `GET /api/aftersales/technicians?period=30` | Technician list → Rating | Period existing: `all`, `30`, `90`, `365`; pagination existing | 200 `status,data[],total,current_page,last_page`; setiap item punya `rating`, `rating_count`, `rating_scale_max:3` |
| `GET /api/aftersales/technicians/{technician}?period=30` | Technician See Detail → kartu Rating | ID TechnicianProfile | 200 `status,data`; `data.score.rating`, `rating_scale_max`, breakdown existing |

Shape template untuk render rating (potongan `data.template` dari form/survey-link):

```json
{"version":1,"title":"Satisfaction Survey","scale_labels":["Kurang Puas","Puas","Sangat Puas"],"sections":[{"section_key":"ratings","title":"Ratings","is_active":true,"code":"A","questions":[{"question_key":"timeliness","label":"Arrival punctuality","hint":"","type":"rating","options":[],"required":true,"is_core":false,"is_active":true}]}]}
```

`sections` pada response berisi seluruh section aktif, bukan hanya contoh rating di atas. Iterasi section/questions dari template; jangan hardcode jumlah pertanyaan atau question_key. Nilai rating = indeks label + 1 (integer 1–3). Field yes_no tetap boolean, bukan skala rating.

Payload contoh berikut hanya valid bila template pinned memang mempunyai dua question_key yang dicontohkan; sertakan jawaban seluruh pertanyaan wajib dari template aktual:

```json
{"signer_name":"Budi","signer_position":"PIC","signature":"data:image/png;base64,...","answers":{"timeliness":3,"machine_ok":true},"machine_issue_note":null}
```

Signature customer wajib berupa data URI PNG valid; string terpotong pada contoh bukan data yang dapat disubmit. Jika `machine_ok=false`, wajib isi `machine_issue_note`. Rating 0, 4, 5, atau fractional ditolak 422. Untuk input atas nama customer tambahkan `reason` string maks 255 dan signature boleh tidak dikirim. Form/submit publik tanpa session OTP valid → 401; link tidak aktif/used/expired → 410; OTP lock → 423; rate limit → 429; gagal pengiriman WhatsApp → 503. Session OTP berbeda dari session login admin.

Kirim share dengan `via=copy` hanya ketika aksi copy dijalankan; membuka modal saja tidak mencatat pengiriman. GET survey-link dapat mengirim `template=null`, `url=null`, `can_share=false` bila link tidak aktif. Setelah Happy Call, form publik juga dapat ditolak karena progress tidak lagi berada pada fase survei; selesaikan alur survei sebelum pencatatan Happy Call di UX.

### 8.1 Template editor survei

Semua endpoint berikut membutuhkan `aftersales.tickets.manage`. Penempatan: Document Templates → Satisfaction Survey, bukan modal Import.

| Method dan endpoint | Kegunaan / request | Response |
|---|---|---|
| `GET /api/aftersales/survey-templates` | Muat versi dan audit | 200 `{"data":{"versions":[TemplateMetadata],"audit":Paginator}}` |
| `GET /api/aftersales/survey-templates/{id}` | Muat versi template | 200 `{"data":Template}` |
| `POST /api/aftersales/survey-templates/draft` | Buat/ambil draft; tanpa body | 200 `{"data":Template}` |
| `PUT /api/aftersales/survey-templates/{id}` | Save draft: `lock_version`, `title`, `content` lengkap | 200 `{"data":Template}` dengan revisi terbaru |
| `POST /api/aftersales/survey-templates/{id}/preview` | Preview: `{"lock_version":1}` | 200 `{"data":Template,"sections":[],"preview_token":"encrypted-token"}`; sections aktual dapat terisi |
| `POST /api/aftersales/survey-templates/{id}/activate` | `lock_version`, `confirmed:true`, `preview_token` dari preview terbaru | 200 `{"data":Template}` dengan status active |
| `GET /api/aftersales/survey-templates/question-scores?technician_id={userId}` | Report rata-rata per pertanyaan; filter User ID teknisi opsional | 200 `{"data":[{"question_key":"timeliness","label":"Arrival punctuality","response_count":2,"average_score":2.5}]}` |

`TemplateMetadata` mempunyai `id,version,status,title,lock_version,created_at,updated_at,activated_at,modified_by`. `Template` menambah `content`. `content` harus dikirim lengkap dari draft yang dimuat, terdiri dari `scale_labels` dan `sections`; perubahan improvement adalah `scale_labels` tepat tiga string, masing-masing maks 100, tidak boleh kosong saat aktivasi. Contoh nilai `['Kurang Puas','Puas','Sangat Puas']`. Pertahankan section/question key existing, flags dan konfigurasi questions; jangan membuat Save yang hanya mengirim scale_labels karena validasi membutuhkan sections. Preview token terkait user, ID, revisi, dan kedaluwarsa; setelah Save ulang harus Preview lagi sebelum Activate.

## 9. Penanganan error dan urutan integrasi FE

### 9.1 Error umum

Dengan `Accept: application/json`, validation error standar berbentuk:

```json
{"message":"The given data was invalid.","errors":{"serial_number":["This serial number already exists."]}}
```

Text `message` validasi dapat berisi ringkasan lain sesuai Laravel/lokalisasi. Tampilkan `errors[field]` untuk input terkait. Kegagalan business guard memakai HTTP yang sama tetapi dapat hanya berisi `{"message":"..."}` tanpa `errors`. Abaikan field debug tambahan seperti trace; jangan merendernya ke user. Error review Closed §7.4 memiliki `status:error,satisfaction_id` tambahan.

| HTTP | Arti | Tindakan FE |
|---|---|---|
| 401 | Session login / session OTP tidak valid | Internal: alur login existing; survei publik: verifikasi OTP ulang |
| 403 | Tidak punya permission atau bukan uploader | Tampilkan akses ditolak; jangan retry otomatis |
| 404 | ID tidak ditemukan / soft-deleted / row bukan milik batch | Reload konteks list/batch; jangan menganggap sukses |
| 409 | Data atau revisi berubah | Reload sumber, render ulang hasil dan minta user review; jangan retry Apply diam-diam |
| 419 | CSRF/session expired pada request internal | Gunakan alur refresh session/CSRF aplikasi |
| 422 | Input tidak valid atau guard bisnis menolak | Tampilkan errors/message, pertahankan form dan pilihan |
| 410 / 423 / 429 / 503 | State survei/OTP/delivery | Penanganan sesuai §8; tampilkan pesan dan cooldown bila tersedia |
| 500 | Server gagal | Tampilkan kegagalan umum; simpan konteks agar bisa reload/retry dengan aman |

### 9.2 Urutan implementasi per mockup

1. **Customer list (gambar 1):** C01 → render Source dan badge → C02 ketika masuk Machine List. Import Customer membuka state step 1, bukan langsung upload.
2. **Import (gambar 2–4):** I01 download → I02 upload → render Preview → I04 setiap keputusan/koreksi → simpan revisi terbaru → I05 Apply → baca result → reload C01. I06 dapat dipakai sebelum/sesudah Apply.
3. **Add/Edit Customer (gambar 6):** C05 → C06 → C03/C04; Open Maps membuka URL lokal dari field, tidak perlu API tambahan.
4. **Machine List dan Add/Edit (gambar 5/8):** C02 + M01 → M02/M03 → reload C02. Gunakan ID unit untuk Edit; `product_id=null` adalah kondisi valid.
5. **Move (gambar 7):** pilih unit dari C02 → opsi tujuan C01 → M04 → reload customer asal dan tujuan bila terbuka.
6. **Update Logs (gambar 9):** T03 → T04 saat Happy Call Completed → reload T03 → T05 saat Closed. Kedua aksi adalah request berbeda; jangan otomatis memanggil Close setelah Happy Call.
7. **Lead updated / Possible Deal:** D01 → dialog per item → D02 → reload D01 dan C01/C02. Tidak ada request konversi otomatis dari FE.
8. **Survei/rating:** gunakan template pinned dari form/link, submit integer 1–3, tampilkan skor `/3`; pertahankan OTP dan aturan review existing.

## Lampiran: backend handoff, deployment, dan aturan bisnis

Implements the backend requirements in `DAXX_Aftersales_Improvement_PRD.pdf` v0.2, on top of the existing v1.1/v2.0 implementation. Page layouts, modal/stepper rendering, mobile styling, PDF typography/watermark/signature positioning, and UI rating labels remain frontend work. No application Blade views, CSS, or JavaScript were changed.

## Deployment

1. Deploy backend code with a database backup and a maintenance window. Run `php artisan migrate` before serving the new code. The new migration adds import staging/audits and Deal links, expands machine fields, copies After Sales permissions into `supervisor_after_sales`, and normalizes existing survey scores.
2. Existing tickets retain `happy_call_required=false`; the database default for tickets created after migration is `true`. Supervisor assignments on existing tickets remain intact.
3. Historic 1–5 ratings map to 1–3 (`1,2 → 1; 3 → 2; 4,5 → 3`). Original aspect scores remain in `ticket_satisfactions.score_original`; original dynamic answers remain in `ticket_satisfaction_answers.score_original`. Both reporting paths use the normalized values. Existing question wording stays intact; scale labels become Kurang Puas, Puas, Sangat Puas.
4. Legacy PM values are retained until the contract is explicitly changed. New choices are `none`, `basic`, `premium` with automatic frequencies `null`, `6 months`, `3 months`.
5. Assign the new Supervisor After Sales role to the intended users. No users are reassigned automatically. The seeder `AftersalesSupervisorSeeder` can be rerun to copy any later After Sales permission additions.
6. Optional historical Deal backfill: inspect with `php artisan aftersales:sync-deals --dry-run`, then run `php artisan aftersales:sync-deals`. Possible matches enter the review queue. Existing conversions are skipped.

The migration deliberately refuses destructive rollback because it changes historical scoring and creates operational audit data. Restore a pre-rollout backup to reverse the release. No application database migration or backfill was executed during implementation; automated integration tests use an isolated SQLite database. MySQL migration execution still needs staging validation.

## Authentication and responses

All routes below use the application's existing authenticated web session and CSRF handling under `/api/aftersales`. Customer/import/machine/Deal actions require `masters.service-customers`. An import is accessible only to its uploader. Validation returns 422, permission failures 403, stale review conflicts 409. Import payloads are wrapped in `data`; that object contains `import`, `summary`, and `rows`.

## Customer import

Base: `/api/aftersales/customers`.

| Method | Route | Purpose |
|---|---|---|
| GET | `/imports/template` | Download the supplied original three-sheet Excel template |
| POST | `/imports` | Multipart upload using `file`; `.xlsx`, max 20 MB, max 5000 data rows |
| GET | `/imports/{id}` | Resume persisted review |
| PUT | `/imports/{id}/review` | Correct inputs and resolve matching decisions |
| POST | `/imports/{id}/apply` | Commit valid reviewed rows atomically |
| GET | `/imports/{id}/export` | Download all rows, warnings/errors, decisions, results as Excel |

Only the `Import Customer` sheet is parsed; row 1 must exactly match all 18 headers, including required-field asterisks. The downloadable template also includes Guide and Reference. Phone and Serial Number are text. This import does not accept the old 276-row spreadsheet layout directly; convert it to the provided template first.

Upload writes only review staging records, never Customers or Machines. Each row includes original `input`, a `review` object (`status`, `errors`, `warnings`, normalized `customer`, normalized `machine`, and `candidates`), an optional `decision`, and its eventual `result`.

Summary keys: `exact`, `possible`, `new`, `already_exists`, `error`, `warning`. Warning is an overlapping count, not a separate row status. Row IDs are database IDs; `row_number` is the spreadsheet line number, including the header.

Review request example:

```json
{
  "lock_version": 0,
  "rows": [
    {"id": 21, "decision": {"action": "link", "customer_id": 8}},
    {"id": 22, "decision": {"action": "create_new"}},
    {"id": 23, "decision": {"action": "skip"}},
    {"id": 24, "input": {"Phone": "08123456789", "Province": "Jawa Timur", "City / Region": "Ponorogo", "Warranty Start": "21/10/2026"}}
  ]
}
```

`link` accepts exactly one of `customer_id`, `lead_id`, or `row_id`. A `row_id` must refer to an earlier row in the same import, and that row must be applied successfully. Use the existing master province/region endpoints for selection, and send the selected master names in the corresponding input headers. Corrections are reparsed in spreadsheet order; changed downstream matches require review again. Each successful review increments `lock_version`. Send the latest version to subsequent reviews and Apply:

```json
{"lock_version": 1}
```

All Possible matches must have an explicit decision before Apply. Error and Already exists rows are skipped. Repeated Apply is idempotent. Apply rechecks current customer matches and existing serials; a new ambiguous customer conflict returns 409. Correct and resubmit review when this happens. The transaction rolls back on conflict.

Matching rules:

- Normalize leading Mr/Mrs/Pak/Bapak/Ibu/Bu titles and whitespace/case; normalize phones to `62...`. Empty phones never establish an Exact match. Scientific notation/malformed phones produce an Error.
- Exact: matching normalized name and phone. Possible: same name/city with different phone, same phone with a different name, or phone edit distance of one. Possible is never merged automatically.
- Match both existing ServiceCustomers and eligible Sales Deals, and earlier rows in the file. Multiple machines for one exact customer are grouped together. Existing customer fields are retained when linking; the incoming row stays available in review/export for comparison.
- Serial is normalized for case/whitespace. Existing serials (including soft-deleted units) are Already exists and skipped. Repeated serials within one file create one machine, with the last successfully applied row as owner, the earliest warranty dates retained, and previous owners appended to Note.
- Province follows the matched Region, with a warning on disagreement. Unknown/ambiguous master values generate warnings for manual correction; unknown categories generate Error. Unmatched Industry remains null.
- Only Customer Name, Machine Category, and Machine Name are required in import. Other blanks generate warnings. Serial placeholders `-` and `NO MACHINE ID` become null. Dates support Excel numeric dates, `dd/mm/yyyy`, ISO, and Indonesian month names/abbreviations. `30-Dec-00` is empty; future Start dates warn without silently changing the year.

## Customer and machine APIs

Existing customer create/update accepts optional `address_maps_url` (max 2048 characters), validated against Google Maps hosts and paths, including `maps.app.goo.gl`. No link is fetched by the server. Region determines Province.

Customer list/detail expose `source` (`manual`, `import`, `deal_conversion`), `lead_no_longer_deal`, and `lead_updated`. Detail includes `deals` and linked Lead/Quotation information. No Deal History screen is required.

`GET /customers/machine-references` returns `categories` with `id`, `name`, `code_prefix`, `industries`, and `pm_contracts`. Existing Product Type master save accepts optional `code_prefix`, protected by `masters.product-types`.

Existing create/update machine routes remain:

- `POST /customers/{customer}/products`
- `PUT /customers/products/{product}`

Fields: `machine_name` and a real `serial_number` are required for these manual form endpoints; `product_id` is optional. Also supported: `ref_product_type_id`, `industry_id`, `brand`, numeric `capacity` in tons, free-text `refrigerant`, `note`, `warranty_start`, `warranty_end`, and `pm_contract_status`. Serial must be unique after normalization. Machines generated by Deal/import may initially lack serial; manual Edit requires completion.

`machine_code`, `warranty_period`, and `pm_frequency` are calculated, not client-controlled. Machine code is category prefix plus literal capacity, e.g. `CD 50` and `TD 5`. Machine Name falls back to Product Name for legacy/Deal units. Warranty End defaults to Start plus Product warranty months when available, otherwise 12 months; explicit End remains editable. Period uses full months and displays `N Months` or whole `N Year(s)`. End before Start is rejected. Legacy PM fields stay intact on unrelated edits.

Move: `POST /customers/products/{product}/move`:

```json
{"customer_id": 42, "note": "Previously owned by Global Dynamic"}
```

Note is optional; omitted/blank uses `Previously owned by [old customer]` and appends to existing Note. Open tickets block transfer with `This machine has open tickets. Close them first.` Existing Ticket.customer_id, serial, warranty, and PM remain intact; the owner changes and a `machine_moved` audit records source, destination, actor, and note.

## Sales Deal integration

Model events detect eligible Leads (`status_id=5`, `deal_at` populated). Exact links to an existing customer, New creates one, Possible creates a `needs_review` queue item. `customer_deals.lead_id` is unique. One customer can have many Deals. Re-entry to Deal never creates another customer or machine; leaving Deal marks `left_deal_at` without altering customer, machines, or tickets. Later quotation changes are not applied automatically.

Initial machines are generated per quotation item quantity, only for Products with `product_type_id`, skipping merged items. Product/category/Lead industry are copied; serial, warranty dates, and PM details stay unset. Warranty Period reflects Product warranty configuration. Import machines use null product_id.

Lead changes after conversion do not overwrite After Sales data. They populate `pending_changes` and the Lead updated indicator. Use:

- `GET /customers/deal-reviews` — paginated review queue, `needs_review` and pending changes.
- `POST /customers/deal-reviews/{id}/resolve` — `action`: `link` plus `customer_id`, `create_new`, `apply`, or `ignore`; also send `updated_at` exactly as returned by the queue. A stale timestamp returns 409. Apply copies current Lead customer fields; Ignore acknowledges the current snapshot. Both audit the decision. Applying a changed phone also requires `aftersales.customer-phone.change` and records the phone change.

## Ticket and survey integration

`GET /tickets/supervisors` now lists users with role `supervisor_after_sales`. New assignments validate that role; unchanged legacy supervisors are retained. New-ticket supervisor signing requires the assigned user to have this role. Existing tickets retain the legacy signing authorization behavior.

`POST /tickets/{id}/happy-call` accepts optional `note`, requires `aftersales.tickets.manage`, and records `happy_call_completed`, actor, and timestamp. Repeated requests within the same visit cycle return the existing log. Closed/Recoming tickets reject this update. The status is included in `TicketLog::STEPS` after Satisfaction Submitted and before Closed.

`POST /tickets/{id}/close` retains existing permissions and documentation/review guards. For new tickets it first requires Happy Call Completed in the current visit cycle. Needs review and Rejected survey responses still block Closed. Tickets that existed before rollout are exempt from the Happy Call gate. Recoming begins a new cycle.

Ticket list/detail include `reminders`: `survey_due_at`, `survey_due`, `happy_call_due`, `happy_call_completed`, `happy_call_required`, and `rating_scale_max`. Survey due begins three days after Save Service Form if not shared/submitted; Happy Call due begins one day after the current response if no Happy Call is recorded. Reminders never block actions. Closed tickets have no due reminders.

Survey template save/activation now requires exactly three scale labels, and all dynamic rating answers accept only integers 1–3. OTP, signatures, question configuration, review rules, and current-response exclusions remain the baseline behavior. Technician score summaries include `rating_scale_max=3`. Frontend should use the returned scale labels and update `/5` displays to `/3`.

## Verification

Run `php vendor/bin/phpunit tests/Feature/Aftersales`. Integration coverage includes schema normalization/original scores, role permission copying, import preview/Apply/idempotency, 276 machine rows, matching decisions, malformed phones/dates, header rejection, review corrections and stale revisions, cross-uploader access, import-to-Deal linking, repeated Deal transitions, machine transfer/open-ticket guard, Maps validation, and Happy Call plus survey-review closure gates. Existing document/template/technician tests are included in the same run.
