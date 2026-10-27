# Integrate Contract: Aftersales PRD v2.2 (BE ke FE)

Dokumen khusus integrasi backend v2.2. Task 1 (jenis mesin), task 2 (Generator Machine Name), dan task 3 (Import Customer) sudah diimplementasikan. Kontrak task berikutnya ditambahkan setelah implementasinya selesai.

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
