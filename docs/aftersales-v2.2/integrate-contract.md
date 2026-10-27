# Integrate Contract: Aftersales PRD v2.2 (BE ke FE)

Dokumen khusus integrasi backend v2.2. Saat ini hanya task 1 yang sudah diimplementasikan; kontrak task berikutnya ditambahkan setelah implementasinya selesai.

## Aturan umum

- Base URL endpoint: `/api/aftersales`.
- Autentikasi menggunakan session login web, bukan bearer token.
- Kirim header `Accept: application/json` pada semua request.
- Request POST/PUT/DELETE wajib menyertakan token CSRF, misalnya header `X-CSRF-TOKEN` dari meta `csrf-token` pada halaman aplikasi.
- Session tidak valid: HTTP 401; permission tidak mencukupi: 403; resource tidak ditemukan: 404; validasi gagal: 422.
- Response create/update menggunakan `{ "message": "...", "data": { "data": { ... } } }`. Objek mesin berada di `response.data.data` pada body JSON.
- Response GET Customer menggunakan `{ "status": "success", "data": { "products": [...] } }`.

## Task 1: Mesin Daxtro dan non-Daxtro

Scope bagian ini: penyimpanan, validasi, response mesin, dan matching serial (IM-74–76, IM-79–80, IM-82–83). Generator Machine Name, template Import Customer v2.2, dan perubahan tampilan/PDF dikerjakan pada task berikutnya. Machine Name masih wajib dikirim pada task ini.

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
| `machine_name` | Wajib untuk kedua jenis, string maksimal 255 karakter. Belum ada generator pada task ini. |
| `brand` | Wajib untuk non-Daxtro, string maksimal 255 karakter. Untuk Daxtro, input diabaikan dan disimpan null; FE dapat menampilkan label Daxtro berdasarkan `is_daxtro`. |
| `serial_number` | Wajib pada form Add/Edit Daxtro, maksimal 255 karakter, dinormalisasi ke huruf besar tanpa spasi. Placeholder seperti `-` tidak dianggap serial valid. Serial Daxtro harus unik, termasuk unit soft-deleted. Non-Daxtro: input diabaikan dan disimpan null. |
| `compressor`, `refrigerant` | Opsional untuk kedua jenis, nullable string maksimal 255 karakter; nilai bebas, tanpa mapping. Field Refrigerant Type di FE dikirim sebagai `refrigerant`. |
| `capacity` | Opsional, nullable angka >= 0 dalam ton; pecahan diperbolehkan, tanpa daftar kapasitas tetap. |
| `ref_product_type_id` | Model/Machine Category; opsional pada endpoint task ini, nullable ID yang ada di `ref_product_types`. |
| `product_id`, `industry_id` | Opsional, nullable ID existing dari `ref_products` / `ref_industries`. |
| `note` | Opsional, nullable string maksimal 10000 karakter. |
| `warranty_start`, `warranty_end` | Daxtro: opsional tanggal (disarankan `YYYY-MM-DD`), end >= start. Non-Daxtro: diabaikan. |
| `pm_contract_status` | Daxtro: opsional, jika dikirim harus `none`, `basic`, atau `premium`. Non-Daxtro: diabaikan, tersimpan `none`. |

Field opsional yang tidak dikirim saat Edit dipertahankan, kecuali normalisasi data yang tidak berlaku menurut jenis mesin. Untuk mengosongkan field opsional, kirim null. PUT tetap mewajibkan `machine_name`, serta Brand atau Serial sesuai jenis efektif.

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
