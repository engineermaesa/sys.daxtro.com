# Integrate Contract: Aftersales PRD v2.2 (BE ke FE)

Dokumen khusus integrasi backend v2.2. Task 1–8 (jenis mesin, Generator Machine Name, Import Customer, Deals Review, gate kontak Customer, filter Visit Date, Overall Rating teknisi, dan fondasi log import bersama) sudah diimplementasikan. Kontrak task berikutnya ditambahkan setelah implementasinya selesai.

## Aturan umum

- Base URL endpoint: `/api/aftersales`.
- Autentikasi menggunakan session login web, bukan bearer token.
- Kirim header `Accept: application/json` pada semua request.
- Request POST/PUT/DELETE wajib menyertakan token CSRF, misalnya header `X-CSRF-TOKEN` dari meta `csrf-token` pada halaman aplikasi.
- Session tidak valid: HTTP 401; permission tidak mencukupi: 403; resource tidak ditemukan: 404; validasi gagal: 422.
- Response create/update menggunakan `{ "message": "...", "data": { "data": { ... } } }`. Objek mesin berada di `response.data.data` pada body JSON.
- Response GET Customer menggunakan `{ "status": "success", "data": { "products": [...] } }`.

## Task 1: Mesin Daxtro dan non-Daxtro

Scope bagian ini: penyimpanan, validasi, response mesin, dan matching serial (IM-74–76, IM-79–80, IM-82–83). Ketentuan Machine Name diperluas pada task 2, dan Import Customer pada task 3 di bawah. Perubahan tampilan/PDF dikerjakan pada task berikutnya.

### Endpoint dan akses

| Method | Endpoint | Kegunaan |
|---|---|---|
| POST | `/api/aftersales/customers/{customer}/products` | Tambah mesin; sukses HTTP 201 |
| PUT | `/api/aftersales/customers/products/{product}` | Edit mesin; sukses HTTP 200 |
| GET | `/api/aftersales/customers/{customer}` | Baca mesin melalui `data.products[]` |

Session login, CSRF untuk mutasi, dan `Accept: application/json` mengikuti aturan umum di atas. Semua endpoint di atas membutuhkan permission `masters.service-customers` (After Sales/Supervisor dengan permission tersebut). Tidak memiliki permission: 403; ID tidak ditemukan/soft-deleted: 404; validasi: 422.

### Field request Add/Edit Machine

| Field | Aturan |
|---|---|
| `is_daxtro` | Boolean JSON `true`/`false`; juga menerima `1`, `0`, `"1"`, `"0"`. Tidak menerima `"Yes"`/`"No"`, `"true"`/`"false"`, atau null. Jika tidak dikirim saat Add, default true; saat Edit, mempertahankan jenis tersimpan. |
| `machine_name` | String maksimal 255 karakter. Untuk nama manual/non-Daxtro wajib terisi; mode auto mengikuti task 2 di bawah. |
| `brand` | Wajib untuk non-Daxtro, string maksimal 255 karakter. Untuk Daxtro, input diabaikan dan disimpan null; FE dapat menampilkan label Daxtro berdasarkan `is_daxtro`. |
| `serial_number` | Wajib pada form Add/Edit Daxtro, maksimal 255 karakter, dinormalisasi ke huruf besar tanpa spasi. Placeholder seperti `-` tidak dianggap serial valid. Serial Daxtro harus unik, termasuk unit soft-deleted. Non-Daxtro: input diabaikan dan disimpan null. |
| `compressor`, `refrigerant` | Opsional untuk kedua jenis, nullable string maksimal 255 karakter; nilai bebas, tanpa mapping. Field Refrigerant Type di FE dikirim sebagai `refrigerant`. |
| `capacity` | Opsional, nullable angka >= 0 dalam ton; pecahan diperbolehkan, tanpa daftar kapasitas tetap. |
| `ref_product_type_id` | Model/Machine Category; opsional pada endpoint task ini, nullable ID yang ada di `ref_product_types`. |
| `product_id`, `industry_id` | Opsional, nullable ID existing dari `ref_products` / `ref_industries`. |
| `note` | Opsional, nullable string maksimal 10000 karakter. |
| `warranty_start`, `warranty_end` | Daxtro: opsional tanggal (disarankan `YYYY-MM-DD`), end >= start. Non-Daxtro: diabaikan. |
| `pm_contract_status` | Daxtro: opsional, jika dikirim harus `none`, `basic`, atau `premium`. Non-Daxtro: diabaikan, tersimpan `none`. |

Field opsional yang tidak dikirim saat Edit dipertahankan, kecuali normalisasi data yang tidak berlaku menurut jenis mesin dan regenerasi nama auto pada task 2. Untuk mengosongkan field opsional, kirim null. PUT tetap mewajibkan Brand atau Serial sesuai jenis efektif; aturan `machine_name` mengikuti task 2.

`warranty_period` dan `pm_frequency` bukan input manual. Pada Daxtro keduanya mengikuti aturan existing: tanggal akhir default dari masa garansi Product atau 12 bulan; Basic = `6 months`, Premium = `3 months`, None = null.

### Contoh tambah non-Daxtro

```json
{
  "is_daxtro": false,
  "machine_name": "Brand X chiller",
  "brand": "Brand X",
  "capacity": 6.5,
  "compressor": "Copeland",
  "refrigerant": "R22"
}
```

Response mutasi tetap memakai envelope existing: objek mesin ada pada `response.data.data`. Contoh field relevan (response juga memuat field model dan relasi existing):

```json
{
  "message": "Product unit added successfully",
  "data": {
    "data": {
      "id": 123,
      "is_daxtro": false,
      "machine_name": "Brand X chiller",
      "brand": "Brand X",
      "compressor": "Copeland",
      "refrigerant": "R22",
      "serial_number": null,
      "warranty_period": null,
      "warranty_start": null,
      "warranty_end": null,
      "warranty_active": null,
      "pm_contract_status": "none",
      "pm_frequency": null
    }
  }
}
```

Pada GET Customer, field yang sama tersedia di `response.data.products[]`. `is_daxtro` selalu boolean untuk data tersimpan; `compressor` nullable. Internal `normalized_serial` tidak diekspos.

### Jenis mesin, transisi, dan tanggung jawab FE

- Gunakan `is_daxtro` untuk menampilkan/menyembunyikan Brand, Serial Number, dan kartu Warranty & Contract. Non-Daxtro menampilkan `–` untuk warranty dan PM; `pm_contract_status = none` bukan berarti kartu PM harus ditampilkan.
- `warranty_active` untuk non-Daxtro adalah **null (tidak berlaku)**; untuk Daxtro tetap boolean existing. FE harus membedakan null dari false (warranty tidak aktif).
- Pergantian Daxtro ke non-Daxtro menghapus serial beserta matching key, seluruh warranty, dan PM; ini juga berlaku bila field lama masih terkirim. Pergantian kembali tidak memulihkan nilai tersebut. FE perlu meminta pengguna melengkapi serial kembali.
- Pergantian non-Daxtro ke Daxtro memerlukan serial valid dan mengosongkan Brand. Validasi gagal tidak menyimpan perubahan.
- Untuk Daxtro dari Deal/import, serial tetap boleh kosong saat dibuat melalui proses tersebut; menyimpan lewat endpoint form Add/Edit tetap memerlukan serial.
- Mesin existing diberi `is_daxtro = true`, termasuk soft-deleted; migrasi tidak mengubah nama, kapasitas, serial, Brand, maupun warranty existing. Brand legacy dikosongkan jika mesin disimpan lewat endpoint form sebagai Daxtro.
- Matching serial untuk create/edit dan pemeriksaan serial import hanya membaca mesin Daxtro. Unit non-Daxtro tidak menjadi kandidat serial.
- Machine Code masih tersedia pada response existing untuk kompatibilitas dan rumus accessor tidak berubah. FE tidak perlu menampilkannya sesuai v2.2; perubahan PDF berada pada task dokumen berikutnya.

Contoh validasi HTTP 422:

```json
{
  "message": "The brand field is required.",
  "errors": {
    "brand": ["The brand field is required."]
  }
}
```

Gunakan key `errors` untuk pemetaan ke input; teks pesan validasi dapat mengikuti locale aplikasi. Field yang mungkin gagal antara lain `is_daxtro`, `machine_name`, `brand`, `serial_number`, `capacity`, `compressor`, dan tanggal warranty.

## Task 2: Generator Machine Name

Meliputi IM-77–78 dan perlindungan data existing pada IM-82. Generator berjalan pada endpoint Add/Edit Machine yang sama, melalui service `MachineNames` yang juga dipakai oleh Import Customer pada task 3.

### Rumus dan format

- Prefix berasal dari `ProductType.code_prefix`, berdasarkan `ref_product_type_id`.
- TD, CD, BD, FD, SD, RO, dan prefix selain RD: series = Capacity (ton) × 10. Capacity tidak dibatasi daftar price list.
- RD: series = Capacity ÷ 3 × 10, dibulatkan ke bilangan bulat terdekat (setengah dibulatkan ke atas). Prefix Cold Room legacy VCD dinormalisasi menjadi RD untuk nama baru.
- Format: `[Prefix][series] | [Compressor] [Refrigerant Type]`. Bagian kosong dihilangkan, tanpa pemisah `|` jika keduanya kosong. Spasi awal/akhir pada Compressor dan Refrigerant diabaikan ketika membentuk nama.
- Contoh: 15 ton + Tube + Bitzer + R404A → `TD150 | Bitzer R404A`; tanpa Compressor/Refrigerant → `TD150`; Tube 7 ton → `TD70`; Tube 7.25 ton → `TD72.5`; Cold Room 6 ton → `RD20`.
- Hasil akhir maksimal 255 karakter. Overflow kapasitas atau nama terlalu panjang ditolak dengan 422, bukan dipotong diam-diam.
- Machine Code tetap menggunakan rumus accessor existing (misalnya `TD 15` untuk 15 ton), tidak mengikuti rumus nama baru.

### Mode nama pada Add/Edit dan response

Field baru `machine_name_mode` menerima `auto` atau `manual`. Jika dikirim, null/nilai lain ditolak 422. Nilai tersimpan dikembalikan pada objek mesin (`data.data.machine_name_mode` untuk mutasi, `data.products[].machine_name_mode` untuk GET Customer).

| Kondisi | Perilaku backend |
|---|---|
| Add Daxtro, mode tidak dikirim, nama kosong/tidak dikirim | Mode auto; nama dibuat backend. Model dengan `code_prefix` dan Capacity wajib tersedia. |
| Add Daxtro, nama diisi, mode tidak dikirim | Mode manual untuk kompatibilitas client lama; nama yang dikirim dipertahankan. |
| Add/Edit Daxtro, mode `auto` eksplisit | Backend selalu menghitung nama dari field efektif yang tersimpan/dikirim. `machine_name` yang dikirim diabaikan; FE tidak menjadi sumber kebenaran hasil generator. |
| Edit mode auto, mode tidak dikirim, nama tidak dikirim atau sama dengan nama saat ini | Regenerasi hanya jika Model/Capacity/Compressor/Refrigerant berubah. Perubahan Note/Serial/Warranty saja tidak mengubah nama. |
| Edit Daxtro, mode tidak dikirim, nama non-kosong diubah | Beralih ke manual, termasuk jika field pembentuk nama juga diubah pada request yang sama. |
| Edit mode manual, nama tidak dikirim | Nama tersimpan dipertahankan, walaupun empat field pembentuk nama berubah. |
| Mode `manual` eksplisit | Nama wajib tersedia: dapat menggunakan nama tersimpan saat Edit jika field tidak dikirim. Mengirim null/kosong untuk nama manual ditolak 422. |
| Non-Daxtro | Selalu mode manual; `machine_name` tetap wajib dikirim saat Add/Edit. Request mode auto tidak menjalankan generator dan dinormalisasi menjadi manual. |

Model/Capacity tetap opsional untuk nama manual. Pada mode auto, keduanya harus tersedia ketika generator dijalankan; field Edit yang tidak dikirim mengambil nilai tersimpan. Untuk mengosongkan Compressor/Refrigerant, kirim null, sehingga nama auto berikutnya tidak memuat bagian tersebut.

Migrasi menambahkan mode default auto untuk mesin existing, termasuk soft-deleted, tanpa menimpa nama atau capacity. Membaca mesin dan mengedit field selain empat field pembentuk nama tidak menjalankan generator. Nama existing baru digenerate saat salah satu field tersebut berubah atau mode auto diminta eksplisit. Jika nama existing ingin dipertahankan saat mengubah spesifikasi, kirim mode manual. Koreksi capacity existing tetap dilakukan manual oleh After Sales.

Contoh request Add Daxtro:

```json
{
  "is_daxtro": true,
  "machine_name_mode": "auto",
  "ref_product_type_id": 1,
  "capacity": 15,
  "compressor": "Bitzer",
  "refrigerant": "R404A",
  "serial_number": "SN-150"
}
```

ID kategori pada contoh adalah placeholder; gunakan ID Tube dari `GET /api/aftersales/customers/machine-references`. Response Add mengikuti envelope task 1 dengan `machine_name = TD150 | Bitzer R404A` dan `machine_name_mode = auto`.

### Preview nama sebelum Save

`POST /api/aftersales/customers/machine-name-preview`

Session login, permission `masters.service-customers`, CSRF, dan header JSON sama dengan endpoint mutasi. Endpoint ini hanya menghitung nama Daxtro, tidak membuat/mengubah mesin dan tidak memerlukan Serial Number.

| Field | Aturan |
|---|---|
| `ref_product_type_id` | Wajib ID Machine Category existing, dengan `code_prefix` terisi. |
| `capacity` | Wajib angka >= 0 dalam ton; pecahan diperbolehkan. |
| `compressor`, `refrigerant` | Opsional nullable string maksimal 255 karakter per field. |

Request:

```json
{
  "ref_product_type_id": 1,
  "capacity": 15,
  "compressor": "Bitzer",
  "refrigerant": "R404A"
}
```

Response HTTP 200 (preview memakai `data`, berbeda dengan envelope Add/Edit):

```json
{
  "data": {
    "machine_name": "TD150 | Bitzer R404A",
    "machine_name_mode": "auto"
  }
}
```

Validasi generator mengembalikan 422 dengan key `errors.ref_product_type_id` jika Model/prefix tidak tersedia, `errors.capacity` untuk Capacity yang tidak valid/tidak tersedia, dan `errors.machine_name` jika hasil nama terlalu panjang. Save yang gagal tidak menyimpan sebagian perubahan.

### Alur integrasi FE

1. Baca `machine_name_mode` dari data mesin. Add Daxtro baru dimulai dalam mode auto.
2. Saat mode auto, panggil preview ketika salah satu dari empat field berubah dan gunakan response terbaru; cegah hasil request lama menimpa hasil request yang lebih baru.
3. Ketika pengguna mengedit nama secara manual, ubah mode lokal ke manual dan hentikan auto-fill.
4. Saat Save, kirim mode secara eksplisit. Mode auto membuat backend menghitung ulang; mode manual mempertahankan nama pengguna. Mengirim hasil preview sebagai nama tanpa mode pada Add dianggap input manual.
5. Jika menyediakan aksi kembali ke nama otomatis, kirim `machine_name_mode: auto` untuk mengaktifkan generator kembali.

## Task 3: Pembaruan Import Customer

Meliputi IM-84–85 dan penerapan aturan mesin task 1–2 pada import. Endpoint existing dipertahankan; template baru dan payload review diperluas. Tidak ada perubahan frontend pada task ini.

### Endpoint dan alur

Semua endpoint memerlukan login dan permission `masters.service-customers`. Untuk batch existing, hanya uploader yang dapat membaca, mengoreksi, Apply, dan export (pengguna lain mendapat 403). Mutasi memerlukan CSRF.

| Method | Endpoint | Request / hasil |
|---|---|---|
| GET | `/api/aftersales/customers/imports/template` | Binary `.xlsx` terbaru; gunakan blob/Content-Disposition untuk download. |
| POST | `/api/aftersales/customers/imports` | Multipart FormData field `file`, `.xlsx` maksimal 20 MB dan 5000 baris data; HTTP 201. Jangan set multipart boundary secara manual. |
| GET | `/api/aftersales/customers/imports/{import}` | Baca batch, summary, dan seluruh row review; HTTP 200. |
| PUT | `/api/aftersales/customers/imports/{import}/review` | JSON `lock_version` dan `rows[]` koreksi/keputusan; HTTP 200, lock_version bertambah. |
| POST | `/api/aftersales/customers/imports/{import}/apply` | JSON `lock_version` terbaru; HTTP 200. Apply ulang batch yang sudah applied tidak membuat data ganda. |
| GET | `/api/aftersales/customers/imports/{import}/export` | Binary Excel review semua baris beserta status, errors, warnings, keputusan, dan result. FE dapat menggunakannya sebagai Download Error/Review File. |

Response JSON pada Upload/Show/Review/Apply berbentuk `{ "data": { "import": {...}, "summary": {...}, "rows": [...] } }`, bukan envelope mutasi mesin `data.data`. Upload dan review hanya menyimpan staging/log import; Customer dan mesin belum dimutasi sampai Apply.

### Template v2.2 dan perubahan header

File download memiliki tiga sheet: `Import Customer`, `Guide`, dan `Reference`. Data dibaca dari `Import Customer`, header di baris 1. Urutan dan nama header harus tepat; file lama atau tambahan kolom data ditolak 422 dengan `errors.file`.

```text
Customer Name* | PIC Name | Phone | Email | Address | Google Maps Link |
Province | City / Region | Industry | Daxtro Machine | Machine Category* |
Machine Name | Brand | Capacity | Compressor | Refrigerant Type |
Serial Number | Warranty Start | Warranty End | PM Contract | Note
```

Jumlah kolom menjadi 21 (A–U). `Machine Name*` lama diganti `Machine Name` karena wajibnya kondisional. `Machine Category*` tetap nama header literal; kewajibannya mengikuti jenis mesin di bawah. Sheet Guide/Reference merupakan bantuan pengisian, bukan sumber master database. Kedua baris contoh berwarna biru harus dihapus atau diganti sebelum upload.

| Kolom | Aturan v2.2 |
|---|---|
| `Daxtro Machine` | `Yes`/`No`, tidak peka huruf besar/kecil, spasi luar diabaikan. Kosong = Yes. Nilai selain itu menjadi Error baris. Ini teks Excel, berbeda dari boolean JSON `is_daxtro` pada API mesin. |
| `Customer Name*` | Wajib di semua baris. |
| `Machine Category*` | Wajib pada Yes; harus cocok dengan master kategori. Pada No boleh kosong, tetapi jika diisi tetap harus cocok dengan master. |
| `Capacity` | Wajib pada Yes, termasuk bila Machine Name manual diisi. Pada No opsional. Jika diisi: angka finite >= 0 dalam ton, boleh pecahan. |
| `Machine Name` | Yes: opsional; kosong dibuat oleh generator task 2 dan mode auto. Jika diisi dipertahankan sebagai manual. No: wajib dan selalu manual. |
| `Brand` | Wajib pada No. Pada Yes diabaikan dan menghasilkan Warning jika diisi; tidak disimpan. |
| `Compressor`, `Refrigerant Type` | Teks bebas opsional, maksimal 255 karakter per field. Disimpan sebagai `compressor` dan `refrigerant`. |
| `Serial Number` | Pada Yes boleh kosong; normalisasi/matching serial tetap berlaku. Pada No diabaikan dan menghasilkan Warning jika diisi; tidak digunakan untuk duplicate/matching/transfer mesin. |
| `Warranty Start`, `Warranty End`, `PM Contract` | Pada No diabaikan dan menghasilkan Warning jika diisi, termasuk tanggal/PM yang tidak valid. Pada Yes mengikuti aturan tanggal dan PM existing. |
| `Phone`, `Email` | Kosong menjadi Warning, tidak memblokir import. Nomor placeholder yang menjadi kosong setelah normalisasi juga diberi Warning. Nilai terisi yang tidak valid tetap Error sesuai validasi existing. |

Matching Customer (Exact/Possible/New), normalisasi telepon, master lokasi/Industry, tanggal Indonesia, pemindahan serial Daxtro dalam file, dan Already exists tetap mengikuti mekanisme existing. `product_id` mesin hasil import tetap null. Serial existing pada Customer aktif dilewati tanpa update; mesin pada Customer terhapus tetap archived sesuai Warning `keep_machine_archived`.

Tidak ada fallback Capacity dari angka pada nama mesin lagi. Konversi data lama dilakukan sebelum upload: TD/CD/BD/FD/SD/RO dibagi 10; RD/VCD dibagi 10 lalu dikali 3. Contoh `TD50` → Capacity `5`, `RD20`/`VCD20` → Capacity `6`. Kapasitas kosong pada Yes tetap Error walaupun nama berisi kode. Import tidak melakukan perbaikan massal kapasitas mesin existing.

### Payload review untuk FE

`rows[].input` menggunakan nama header Excel terbaru di atas, sedangkan `rows[].review.machine` memakai nama field backend. Contoh potongan row Daxtro dengan nama otomatis:

```json
{
  "id": 101,
  "row_number": 2,
  "input": {
    "Daxtro Machine": "Yes",
    "Machine Category*": "Tube",
    "Machine Name": "",
    "Capacity": "15",
    "Compressor": "Bitzer",
    "Refrigerant Type": "R404A"
  },
  "review": {
    "template_version": "2.2",
    "status": "new",
    "errors": [],
    "warnings": ["Email is empty. Complete the customer contact before creating a new ticket."],
    "machine": {
      "product_id": null,
      "is_daxtro": true,
      "machine_name": "TD150 | Bitzer R404A",
      "machine_name_mode": "auto",
      "capacity": "15",
      "compressor": "Bitzer",
      "refrigerant": "R404A",
      "brand": null
    }
  }
}
```

Contoh ini hanya memuat field relevan; response juga memuat data Customer, candidates, keputusan/result, dan field mesin lainnya. Pada No, review menyediakan `is_daxtro=false`, `machine_name_mode=manual`, serial/warranty null, dan `pm_contract_status=none`. Pada mesin tersimpan, `warranty_active=null` serta `pm_frequency=null` mengikuti task 1.

`review.status` tetap salah satu `new`, `exact`, `possible`, `already_exists`, `error`. **Warning merupakan array terpisah**, bukan status baru. `summary.warning` menghitung jumlah row yang memiliki Warning, bukan jumlah pesan; satu row dapat masuk hitungan status sekaligus Warning. Baris Error tetap dikembalikan dalam response upload HTTP 201, ditandai di review, dan tidak diterapkan saat Apply. Header/file invalid ditolak pada level request (422).

### Koreksi review dan Apply

Contoh koreksi menggunakan ID row staging (`rows[].id`), bukan `row_number`:

```json
{
  "lock_version": 0,
  "rows": [{
    "id": 101,
    "input": {
      "Capacity": "7",
      "Compressor": "Bitzer",
      "Refrigerant Type": "R404A"
    }
  }]
}
```

Koreksi memakai label header persis (misalnya `Daxtro Machine`, bukan `is_daxtro`). Backend menghitung ulang review beserta nama hasil generator. Selama `input["Machine Name"]` kosong, generator mengikuti koreksi empat field pembentuk nama. Jangan menyalin nama hasil `review.machine.machine_name` ke input tersebut kecuali pengguna memang ingin menyimpannya sebagai manual. Jika ingin kembali otomatis, kosongkan `input["Machine Name"]`; tidak ada kolom input `machine_name_mode` pada template import.

Possible match harus diberi keputusan sebelum Apply melalui `decision.action`: `link`, `create_new`, atau `skip`. Link memakai tepat satu target `customer_id`, `lead_id`, atau `row_id` (row sebelumnya pada batch yang sama). Gunakan struktur keputusan existing. Review dapat membatalkan keputusan sebelumnya ketika data baris berubah, sehingga FE perlu membaca ulang payload response terbaru.

Apply memakai `{ "lock_version": 1 }` sesuai versi terbaru. Lock version usang atau review lama tanpa `template_version=2.2` mendapat 409; batch lama yang belum diterapkan perlu diunggah ulang dengan template terbaru atau direview ulang memakai field v2.2 yang benar. Batch lama yang sudah applied tetap idempotent. Tidak ada migrasi schema baru pada task ini.

Export memuat seluruh 21 kolom input ditambah Row/Status/Errors/Warnings/Decision/Result. File export bukan template upload karena memiliki kolom metadata; salin data koreksi ke template terbaru untuk upload ulang.

## Task 4: Deals Review

Meliputi IM-86–87. Backend menyediakan jumlah pending, daftar terfilter, detail perbandingan, dan keputusan. Tab, badge merah, serta modal/halaman review dibuat tim FE.

### Status dan antrean

`CustomerDeal.review_status` adalah field baru dengan nilai `pending_review` atau `resolved`. `service_customer_id` tetap nullable selama pending. Field `status` existing (`needs_review`/`linked`) tetap tersedia untuk kompatibilitas.

- Pending Deals Review: `review_status=pending_review`, belum memiliki Customer, `left_deal_at` kosong, dan Lead masih berstatus Deal dengan `deal_at` terisi.
- Lead updates: Deal yang sudah memiliki Customer dan `pending_changes` terisi. Ini antrean Apply/Ignore existing, **tidak dihitung pada badge Deals Review**.
- Jika Lead keluar stage Deal atau `deal_at` kosong, Deal tidak dihitung sebagai pending aktif. Data tidak dihapus. Lead pending yang masuk Deal lagi kembali ke antrean tanpa membuat Customer/mesin ganda.
- Perubahan data Lead yang belum ditautkan memperbarui snapshot/candidates pending, bukan membuat Lead updates. Detail review menghitung kandidat dari Customer aktif terbaru, termasuk jumlah mesin aktifnya.
- Migrasi mengisi `review_status=pending_review` untuk data legacy `status=needs_review` tanpa Customer; lainnya resolved. Relasi, snapshot, dan pending_changes existing dipertahankan.

### Endpoint dan permission

Semua endpoint memerlukan login dan permission `masters.service-customers` (After Sales/Supervisor yang memiliki permission tersebut). Tidak memiliki permission: 403. Mutasi menggunakan CSRF dan `Accept: application/json`.

| Method | Endpoint | Kegunaan |
|---|---|---|
| GET | `/api/aftersales/customers/deal-reviews/pending-count` | Jumlah global untuk sidebar/tab. |
| GET | `/api/aftersales/customers/deal-reviews?queue=pending` | Tab Deals Review; paginator Laravel existing. |
| GET | `/api/aftersales/customers/deal-reviews?queue=lead_updates` | Antrean perubahan Lead yang sudah ditautkan. |
| GET | `/api/aftersales/customers/deal-reviews` | Default `queue=all`, gabungan pending aktif dan Lead updates untuk kompatibilitas. |
| GET | `/api/aftersales/customers/deal-reviews/{deal}` | Detail perbandingan dan aksi yang tersedia. |
| POST | `/api/aftersales/customers/deal-reviews/{deal}/resolve` | Keputusan Link/Create new atau Apply/Ignore existing. |

Parameter `{deal}` adalah ID `CustomerDeal`, bukan ID Lead atau Customer. List menerima `page` dan `per_page` (1–100, default 25), urutan ID paling awal dahulu. `queue` hanya menerima `all`, `pending`, `lead_updates`; query invalid mendapat 422. Jumlah badge selalu global, tidak mengikuti halaman atau filter queue.

### Payload jumlah badge

Response pending-count HTTP 200:

```json
{
  "data": {
    "pending_count": 3,
    "badge_label": "3",
    "has_pending": true
  }
}
```

`pending_count` selalu jumlah asli. `badge_label` menjadi `"99+"` jika lebih dari 99; saat nol menjadi null dan `has_pending=false`. FE menyembunyikan badge pada nol. Nilai yang sama tersedia pada **root body JSON** di response list, detail, dan resolve:

```json
{
  "data": [],
  "current_page": 1,
  "per_page": 25,
  "total": 0,
  "pending_count": 0,
  "badge_label": null,
  "has_pending": false
}
```

Contoh list di atas dipersingkat; metadata paginator Laravel lainnya tetap tersedia. Item list mempertahankan field CustomerDeal dan relasi `lead.quotation`, `lead.factory_city`, serta `customer`. Nomor quotation berada di `lead.quotation.quotation_no`, lokasi pabrik di `lead.factory_city`, dan telepon Lead di `lead.phone`.

### Detail perbandingan

Response HTTP 200 berbentuk `data.deal`, `data.from_deal`, `data.candidates`, `data.customer`, `data.allowed_actions`, dengan metadata badge pada root.

| Path body JSON | Isi |
|---|---|
| `data.deal` | CustomerDeal tanpa relasi, termasuk `id`, `review_status`, `status`, `service_customer_id`, `updated_at`, snapshot, dan pending_changes. |
| `data.from_deal` | Data Lead saat ini: name, phone ternormalisasi, email, address, ref_region_id/ref_province_id, region/province, lead_id, quotation_no, deal_at. |
| `data.from_deal.machine_count` | Jumlah unit mesin eligible dari quotation; hanya Product dengan product_type_id dan item yang tidak di-merge. |
| `data.from_deal.machines[]` | Ringkasan item: quotation_item_id, product_id, machine_name (nama Product), ref_product_type_id, quantity. Belum membuat unit mesin. |
| `data.candidates[]` | `{ "match_status": "possible", "customer": {...} }`; match_status juga dapat exact jika data berubah setelah Deal masuk antrean. |
| `data.candidates[].customer` | Customer aktif dengan kontak, source, region, province, dan `products_count` (jumlah mesin aktif). Customer soft-deleted tidak ditawarkan. |
| `data.customer` | Customer yang sudah ditautkan untuk review Lead updates; null untuk Deal pending. |
| `data.allowed_actions` | Pending aktif: `["link","create_new"]`; Lead updates: `["apply","ignore"]`; selain itu `[]`. |

Kandidat ditinjau berdasarkan data terbaru saat detail dibuka. Link dapat memilih Customer aktif dari kandidat atau pencarian Customer existing; backend tetap memeriksa target dan status Deal saat Save.

### Keputusan dan pembaruan badge

Link to existing:

```json
{
  "action": "link",
  "customer_id": 42,
  "updated_at": "2026-10-27T03:00:00.000000Z"
}
```

Create new:

```json
{
  "action": "create_new",
  "updated_at": "2026-10-27T03:00:00.000000Z"
}
```

Kirim `updated_at` persis dari `data.deal.updated_at` hasil detail atau item list terbaru; timestamp adalah pemeriksaan optimistic concurrency existing. Jangan membentuk ulang dari tanggal tampilan lokal.

Resolve sukses HTTP 200 mempertahankan objek CustomerDeal di `data`, dengan `review_status=resolved`, `status=linked`, dan `service_customer_id` terisi. Root `pending_count`/`badge_label`/`has_pending` sudah diperbarui setelah transaksi; gunakan untuk memperbarui sidebar dan tab serta keluarkan baris yang selesai dari queue pending. Mesin dibuat per unit quotation eligible satu kali, dalam transaksi yang sama; audit `lead_link` atau `lead_create_new` dicatat.

`skip` tidak diterima (422). `apply`/`ignore` hanya untuk perubahan Lead yang sudah memiliki Customer, bukan alternatif melewati keputusan pending. Untuk Apply yang mengubah nomor telepon, permission tambahan `aftersales.customer-phone.change` tetap diperlukan dan perubahan nomor tetap diaudit.

Keputusan ulang setelah resolved, updated_at usang, Lead yang tidak lagi aktif sebagai Deal, atau identitas yang berubah selama penyimpanan mendapat 409. Pada 409, muat ulang detail dan jumlah pending sebelum menawarkan aksi lagi. Target Customer soft-deleted tidak dapat ditautkan (404); ID customer_id yang tidak ada mendapat 422. Transaksi gagal tidak meninggalkan Customer/mesin baru atau audit keputusan sebagian.

GET detail bersifat read-only dan dapat dibuka untuk data yang sudah resolved/tidak aktif; perhatikan allowed_actions. Resolusi dan pembacaan count tidak memerlukan endpoint khusus untuk tiap pengguna karena permission After Sales berlaku pada antrean global.

## Task 5: Gate kontak Customer saat membuat tiket

Meliputi IM-89 / AC-IM26. Pembuatan tiket baru memerlukan **Phone dan Email pada Customer tersimpan**. Data serial, warranty, PM, dan jenis Daxtro/non-Daxtro tidak menjadi syarat tambahan gate ini. Tidak ada migrasi schema pada task ini.

### Data Step 1 untuk FE

Endpoint existing `GET /api/aftersales/customers` dan `GET /api/aftersales/customers/{customer}` mendapat field tambahan `ticket_creation` pada setiap Customer. Permission endpoint Customer tetap `masters.service-customers`; tidak ada perubahan envelope/pagination existing.

| Endpoint | Path pada body JSON |
|---|---|
| GET list Customer | `data[].ticket_creation` |
| GET detail Customer | `data.ticket_creation` |

Contoh Customer belum lengkap:

```json
{
  "status": "success",
  "data": {
    "id": 42,
    "name": "Customer A",
    "phone": null,
    "email": null,
    "ticket_creation": {
      "can_create": false,
      "missing_fields": ["phone", "email"],
      "edit_customer_url": "/aftersales/customers/42/edit",
      "message": "Customer data is incomplete. Phone and Email are required because surveys and notifications are sent by email and phone."
    }
  }
}
```

Jika hanya Email kosong, `missing_fields=["email"]`. Jika lengkap, `can_create=true`, `missing_fields=[]`, dan `message=null`; edit_customer_url tetap tersedia. Field ini dihitung dari data saat dibaca, tidak disimpan dan tidak menambah query database per Customer.

`can_create` menunjukkan kelengkapan kontak saja, bukan pemberian permission. Permission membuat tiket tetap diperiksa terpisah pada server. Null, string kosong, atau whitespace dianggap belum diisi; nomor berupa placeholder tanda hubung (`-`, `---`) juga dianggap belum diisi. Gate ini tidak menambah aturan format Phone/Email baru; validasi format pada pengelolaan Customer/import tetap mengikuti endpoint masing-masing.

### Validasi saat Save Ticket

`POST /api/aftersales/tickets` tetap memakai request existing dan permission `aftersales.tickets.manage`. Server membaca ulang Customer aktif dan menguncinya dalam transaksi sebelum membuat nomor tiket, tiket, log, biaya, atau mutasi sparepart. Nilai Phone/Email maupun `ticket_creation.can_create` yang dikirim oleh FE dalam payload tiket bukan sumber validasi; gate selalu memakai data Customer tersimpan terbaru.

Kontak tidak lengkap mengembalikan HTTP 422:

```json
{
  "message": "Customer data is incomplete. Phone and Email are required because surveys and notifications are sent by email and phone.",
  "code": "customer_contact_incomplete",
  "customer_id": 42,
  "errors": {
    "customer.phone": ["Customer Phone is required before creating a new ticket."],
    "customer.email": ["Customer Email is required before creating a new ticket."]
  },
  "ticket_creation": {
    "can_create": false,
    "missing_fields": ["phone", "email"],
    "edit_customer_url": "/aftersales/customers/42/edit",
    "message": "Customer data is incomplete. Phone and Email are required because surveys and notifications are sent by email and phone."
  }
}
```

Key errors adalah string literal `customer.phone` / `customer.email` (flat keys, bukan objek customer bersarang), dan hanya field yang kurang yang disertakan. FE dapat mengenali gate melalui `code`, menampilkan message, memperbarui kondisi Step 1 dari `ticket_creation`, dan memakai edit_customer_url. Error validasi request lain tetap memakai bentuk existing dan tidak selalu memiliki code ini.

Jika kontak lengkap, response sukses tetap HTTP 201 dengan objek tiket pada `data.data`. Nomor tiket tidak terpakai pada kegagalan gate dan stok tidak berkurang. Customer soft-deleted tidak boleh dipakai membuat tiket baru (404); pemeriksaan ownership mesin serta permission existing tetap berlaku.

### Alur integrasi FE dan kompatibilitas

1. Setelah Customer dipilih pada Add Ticket Step 1, baca `ticket_creation`. Jika `can_create=false`, nonaktifkan Next dan tampilkan pesan beserta tautan Edit Customer.
2. Simpan pilihan Customer/mesin dan isian wizard saat pengguna melengkapi kontak, lalu ambil ulang GET detail Customer sebelum mengaktifkan Next. Pengubahan Phone tetap mengikuti permission `aftersales.customer-phone.change` dan audit existing.
3. Saat Save, tetap tangani 422 `customer_contact_incomplete`, karena data Customer dapat berubah setelah Step 1 dibuka. Jika Customer diganti, periksa ulang status Customer yang baru.
4. `PUT /api/aftersales/tickets/{ticket}` tidak terkena gate ini, termasuk tiket existing yang Customer-nya belum memiliki Phone/Email. Jangan menonaktifkan alur Edit Ticket existing hanya karena metadata Customer menunjukkan can_create=false.
5. Add/Edit Customer, Import Customer, dan konversi Deal tetap boleh menghasilkan kontak yang belum lengkap sesuai aturan existing; kewajiban baru ini berlaku saat membuat tiket. Tidak ada perubahan retroaktif pada tiket atau dokumen yang sudah ada.

## Task 6: Filter Visit Date

Meliputi bagian backend IM-92 / AC-IM28. `GET /api/aftersales/tickets` menerima parameter query baru `visit_date` untuk satu tanggal kunjungan. Tidak ada migrasi atau perubahan frontend pada task ini.

### Parameter dan aturan tanggal

| Parameter | Aturan |
|---|---|
| `visit_date` | Opsional, satu tanggal kalender dengan format tepat `YYYY-MM-DD`, misalnya `2026-10-27`. |
| Tidak dikirim / kosong | Tidak menerapkan filter tanggal; perilaku list existing dipertahankan. |
| Nilai invalid | HTTP 422 dengan key `errors.visit_date`: tanggal tidak valid (`2026-02-30`), format lokal (`27/10/2026`), timestamp, rentang, atau array tanggal ditolak. |

Tanggal mengikuti **Asia/Jakarta (WIB)** dan jadwal yang disimpan aplikasi: mulai pukul 00:00:00 pada tanggal tersebut sampai sebelum 00:00:00 hari berikutnya. Kirim tanggal pilihan date picker apa adanya; jangan mengubahnya menjadi timestamp UTC.

Filter memakai tanggal visit aktif terbaru yang sama dengan `visit_date` pada response existing, yaitu **`scheduled_at` terbesar** di antara visit yang belum soft-deleted. Bukan Created Date, SLA Due, actual_start/actual_end, atau sembarang tanggal pada Visit History. Task ini mempertahankan definisi Visit Date existing.

- Jika visit awal 20 Oktober dan follow-up Recoming 27 Oktober, tiket cocok untuk `visit_date=2026-10-27`, bukan 20 Oktober.
- Jika jadwal follow-up diubah, filter mengikuti jadwal terbaru yang tersimpan.
- Tiket tanpa visit aktif atau dengan semua scheduled_at kosong tidak masuk hasil saat filter tanggal aktif.
- Visit soft-deleted tidak memengaruhi pemilihan tanggal; tiket soft-deleted tetap dikecualikan.
- Beberapa visit pada hari yang sama tidak menggandakan baris tiket.

### Kombinasi filter dan pagination

Filter tanggal diterapkan di database **sebelum pagination**, sehingga `total`, `current_page`, dan `last_page` mengikuti hasil filter tanggal. Digabung dengan AND bersama parameter existing: `search`, `priority[]`, `supervisor=me` atau `supervisor_id`, serta filter kategori/teknisi/Customer jika dikirim.

Contoh:

```http
GET /api/aftersales/tickets?visit_date=2026-10-27&search=Rusman&priority[]=high&priority[]=medium&supervisor_id=12&per_page=15&page=1
Accept: application/json
```

Search tetap mencari Ticket Code, nama Customer, atau nama Technician dalam satu grup OR; hasil grup tersebut digabung AND dengan filter tanggal dan lainnya. Priority tetap menerima array seperti request existing (`priority[]=high&priority[]=medium`); tidak menambahkan format comma-separated baru pada API.

Response sukses tetap HTTP 200 dengan envelope existing:

```json
{
  "status": "success",
  "data": [],
  "total": 0,
  "current_page": 1,
  "last_page": 1
}
```

Contoh di atas adalah hasil kosong. Pada hasil berisi tiket, seluruh field existing dipertahankan, termasuk `visit_date`, `visit_no`, `current_recoming`, dan `priority`. `visit_date` pada JSON tetap timestamp dengan format existing; saat ditampilkan sebagai tanggal, FE menggunakan zona Asia/Jakarta.

### Permission dan integrasi FE

- Permission tetap `aftersales.tickets.manage` untuk seluruh tiket, atau `aftersales.tickets.view-own` untuk tiket teknisi sendiri. Query tanggal maupun filter assigned_technician_id tidak dapat melewati batas ownership teknisi. Tanpa permission mendapat 403.
- FE menambahkan date picker satu tanggal dan mengirim `visit_date` pada request list. Ketika filter berubah, kembalikan page ke 1.
- Simpan `visit_date` bersama search/Priority/Supervisor di query URL halaman, kemudian pulihkan nilainya saat reload. Penyimpanan state URL adalah tanggung jawab FE; backend membaca query pada setiap request.
- Reset menghapus visit_date beserta filter lain dari URL dan request, lalu memuat halaman pertama. Mengirim `visit_date=` juga menghilangkan filter tanggal, tetapi tidak menghapus filter lain yang masih dikirim.
- Backend tidak memindahkan posisi badge Priority atau mengubah layout tabel; pekerjaan tersebut tetap menjadi scope FE.

## Task 7: Overall Rating teknisi

Meliputi IM-93 / AC-IM29. Endpoint khusus akun login menyediakan satu angka agregat untuk header halaman Ticket milik teknisi. Tidak ada perubahan frontend, dashboard teknisi, permission baru, atau migrasi schema pada task ini.

### Endpoint dan akses

```http
GET /api/aftersales/technicians/me/overall-rating
Accept: application/json
```

- Auth menggunakan session login aplikasi. Tidak login mendapat 401.
- Membutuhkan permission `aftersales.tickets.view-own`. Tanpa permission mendapat 403. Teknisi tidak membutuhkan `masters.technicians` untuk membaca angka miliknya ini.
- Identitas teknisi selalu berasal dari user login (`users.id`), bukan `TechnicianProfile.id`, parameter path, atau query request.
- Tidak menerima pemilihan teknisi atau periode. Query tambahan seperti user_id, assigned_technician_id, period, search, Priority, dan visit_date tidak mengubah identitas maupun cakupan perhitungan. Rating ini mencakup seluruh periode dan tidak mengikuti pagination/filter daftar tiket.
- Endpoint GET bersifat read-only, tidak memerlukan CSRF, dan tidak mengubah respons survei maupun status tiket.

### Response untuk FE

HTTP 200:

```json
{
  "data": {
    "overall_rating": 2.7,
    "rating_scale_max": 3
  }
}
```

Jika belum ada respons yang dihitung:

```json
{
  "data": {
    "overall_rating": null,
    "rating_scale_max": 3
  }
}
```

`overall_rating` adalah angka yang dibulatkan ke satu angka desimal; angka bulat pada JSON dapat muncul sebagai `3`, sehingga FE perlu memformat satu desimal untuk tampilan `3.0 / 3`. Nilai null ditampilkan `–`, bukan `0 / 3`.

Response hanya berisi dua field di atas. Tidak ada jumlah respons, skor/aspek per respons, komentar, identitas Customer, nomor tiket, history, ataupun data completion rate. Endpoint profil/detail teknisi existing tetap membutuhkan `masters.technicians`; akses ke agregat sendiri tidak membuka akses ke endpoint tersebut.

### Aturan perhitungan bersama After Sales

1. Sumber tiket adalah tiket aktif yang saat ini assigned kepada user login; tiket soft-deleted dikecualikan. Mengganti teknisi pada tiket memindahkan kontribusi rating ke teknisi yang baru, mengikuti aturan existing.
2. Hanya satu respons current per tiket. Untuk perhitungan rating, current dipilih berdasarkan `filled_at` (waktu submit) terbaru; jika sama, ID terbesar menjadi pembeda. Respons soft-deleted dikecualikan. Penentuan current dilakukan sebelum seleksi eligibility, sehingga tidak mengambil respons history yang valid untuk menggantikan current Needs review/Rejected/Admin.
3. Dihitung hanya `source=link` dengan `review_status=normal` atau `reviewed`, serta memiliki skor rata-rata. Source/status lain dikecualikan. Respons admin tetap tidak dihitung walaupun telah di-Approve.
4. Nilai per respons memakai `TicketSatisfaction.average_score` existing: jawaban rating pada respons/template yang tersimpan, atau kolom rating legacy yang sudah dinormalisasi pada migrasi sebelumnya. Jawaban Yes/No, teks, dan komentar tidak ikut dihitung.
5. Overall Rating adalah rata-rata skor rata-rata tiap respons current yang eligible, lalu dibulatkan satu desimal. Setiap respons memiliki bobot sama, meskipun jumlah pertanyaan ratingnya berbeda. Skala tetap 3.
6. Endpoint ini dan rating After Sales menggunakan helper perhitungan bersama agar hasilnya konsisten. Pemilihan current berdasarkan waktu submit juga berlaku pada rating After Sales; task ini tidak mengubah alur review, Close, atau link survei.

### Integrasi header Ticket teknisi

- Panggil endpoint rating ketika halaman daftar Ticket teknisi dibuka; GET daftar tiket tetap memakai `/api/aftersales/tickets` seperti sebelumnya.
- Tampilkan `overall_rating` dan `rating_scale_max` pada header secara read-only. Tidak perlu membuat dashboard teknisi.
- Filter tanggal/search/Priority atau perpindahan halaman tiket tidak mengubah Overall Rating ini. Gunakan endpoint yang sama jika ingin memuat ulang angka.
- Jangan menggunakan endpoint detail/rating per respons untuk header akun teknisi. Backend tetap membatasi response agregat sehingga data survei tidak perlu dikirim ke client teknisi untuk dihitung di FE.

## Task 8: Fondasi log import bersama

Meliputi fondasi IM-98. Model/tabel historical `CustomerImport` / `customer_imports` dan `CustomerImportRow` / `customer_import_rows` dipakai sebagai penyimpanan batch dan staging bersama. Nama tabel serta foreign key `customer_import_id` tetap, sehingga tidak memerlukan perubahan ID atau relasi existing.

### Tipe batch dan kompatibilitas

- Field baru `type` memiliki nilai `customer` atau `sparepart`.
- Migrasi mengisi seluruh log existing sebagai `customer`, termasuk batch review maupun applied. Status, jumlah hasil, uploader, applied_at, lock_version, input/review/decision/result per baris, dan ID existing dipertahankan.
- Default batch tetap `customer` untuk kompatibilitas. Service upload Customer juga menetapkan tipe secara eksplisit.
- Tipe batch ditetapkan backend dan tidak dapat diganti setelah batch dibuat. Model menolak nilai selain kedua tipe atau perubahan tipe dengan error validasi pada field `type`.
- Payload batch Customer pada Upload/Show/Review/Apply sekarang memuat `data.import.type = "customer"`. Envelope, header template, summary, keputusan, pagination/row identity, dan alur Apply task 3 tetap sama.

Contoh field batch yang relevan (nilai selain type merupakan ilustrasi):

```json
{
  "data": {
    "import": {
      "id": 12,
      "type": "customer",
      "file_name": "customers.xlsx",
      "status": "review",
      "lock_version": 0,
      "created_by_user_id": 7
    },
    "summary": {},
    "rows": []
  }
}
```

FE tidak perlu mengirim type maupun created_by_user_id pada upload. Jika field tersebut ikut dikirim, endpoint Customer tidak memakainya untuk memilih jenis batch atau uploader: hasilnya tetap customer dengan uploader user login.

### Akses berdasarkan jenis dan uploader

Backend menyediakan pemeriksaan akses bersama melalui `ImportAccess`, untuk dipakai modul Customer saat ini dan modul Sparepart pada task berikutnya.

| Jenis batch | Permission |
|---|---|
| `customer` | `masters.service-customers` |
| `sparepart` | `masters.spareparts` |

Pengguna memerlukan permission sesuai jenis, bukan cukup salah satu permission secara bebas. Akses batch existing tetap hanya untuk uploader batch tersebut, termasuk ketika pengguna lain juga memiliki permission jenis yang sama. Auth session dan CSRF pada mutasi mengikuti aturan umum.

- Endpoint Customer `/api/aftersales/customers/imports/{import}` beserta review/apply/export hanya menerima batch customer.
- ID batch sparepart yang dikirim ke endpoint Customer menghasilkan 404, sekalipun pengguna adalah uploader dan memiliki kedua permission. Backend menolak sebelum membaca atau menafsirkan baris Sparepart sebagai Customer.
- Tidak memiliki permission yang diperlukan atau bukan uploader mendapat 403. Tidak login pada endpoint HTTP tetap 401; ID batch tidak ditemukan tetap 404.
- Service Customer juga menolak batch non-customer pada review/payload/Apply. Apply memeriksa ulang tipe serta uploader setelah batch dibaca dan dikunci dalam transaksi, sebelum cabang idempotent batch applied maupun mutasi bisnis.
- Review memeriksa ulang akses pada batch yang sudah dikunci. Membaca atau memutuskan batch dengan jenis/owner yang salah tidak mengubah status, lock_version, maupun data barisnya.

### Batas scope dan kebutuhan FE

Task ini belum menambahkan endpoint upload/review/Apply Sparepart atau endpoint daftar seluruh log import. Jangan memakai endpoint Customer untuk batch sparepart. URL dan payload Sparepart dipublikasikan pada task 9–10 setelah implementasinya selesai.

Integrasi Customer existing tetap memakai endpoint task 3; tambahan FE hanya perlu mengenali field type jika ingin menampilkan jenis batch. Tidak ada perubahan frontend atau template Excel pada task ini. Model mendukung query `ofType(...)`, relasi uploader `createdBy`, dan relasi baris `batch` untuk penggunaan backend berikutnya, tanpa mengekspos data user tambahan di response existing.
