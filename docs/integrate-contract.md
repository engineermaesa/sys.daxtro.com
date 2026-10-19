# Integrate Contract: Aftersales PRD v2.0 (BE ke FE)

Dokumen ini adalah kontrak API untuk tim FE yang mengerjakan layar PRD v2.0 (`Aftersales_PRD_v2_0.pdf`). Semua endpoint di bawah **sudah tersedia di BE** (branch `feat/aftersales`). Kode layar dan nomor FR mengacu ke PRD v2.0.

Daftar isi:

1. [Aturan umum](#1-aturan-umum)
2. [Peta layar ke endpoint](#2-peta-layar-ke-endpoint)
3. [Tiket: All Ticket, Detail, Delete](#3-tiket)
4. [Visit Date dan Reschedule](#4-visit-date-dan-reschedule)
5. [Spareparts dan stok](#5-spareparts-dan-stok)
6. [Satisfaction Survey: Review dan gate Closed](#6-satisfaction-survey)
7. [Technician: Rating dan First-Visit Completion](#7-technician)
8. [Enum dan label](#8-enum-dan-label)
9. [Perubahan perilaku yang perlu diketahui FE](#9-perubahan-perilaku)
10. [Checklist FE per layar](#10-checklist-fe-per-layar)

---

## 1. Aturan umum

### Base URL dan auth

- Semua endpoint ada di bawah `/api/aftersales/...`.
- Auth memakai **session login web** (middleware `web` + `auth`), bukan token. Panggil dari halaman yang sudah login.
- Request yang mengubah data (`POST`, `PUT`, `DELETE`) **wajib** membawa header `X-CSRF-TOKEN`. Nilainya ada di `<meta name="csrf-token">` di `layouts/app.blade.php`; `$.ajaxSetup` di layout sudah memasangnya.
- Selalu kirim header `Accept: application/json`, supaya error (403, 404, 422) kembali sebagai JSON, bukan halaman HTML.

### Bentuk response

Ada **dua bentuk** response sukses. Perhatikan bentuk kedua, yang datanya bersarang.

**A. Endpoint baca (list atau detail):**

```json
{
  "status": "success",
  "data": { },
  "total": 42,
  "current_page": 1,
  "last_page": 3
}
```

`total`, `current_page`, dan `last_page` hanya ada di endpoint list.

**B. Endpoint aksi (create, update, delete, review, dll.):**

```json
{
  "message": "Part added successfully",
  "data": {
    "data": { }
  }
}
```

Objeknya ada di `response.data.data`, bukan `response.data`. Kalau aksinya tidak mengembalikan objek (contoh: delete, reschedule), key `data` tidak ada sama sekali:

```json
{ "message": "Ticket deleted successfully" }
```

### Bentuk error

| Status | Kapan | Body |
|---|---|---|
| 401 | Session habis | `{ "message": "Unauthenticated." }` |
| 403 | Tidak punya permission | `{ "message": "..." }` |
| 404 | Data tidak ada atau tiket sudah dihapus | `{ "message": "..." }` |
| 409 | Data berubah oleh user lain, minta reload | `{ "message": "..." }` |
| 422 | Validasi gagal | `{ "message": "...", "errors": { "field": ["pesan"] } }` |
| 422 | Aturan bisnis ditolak | `{ "message": "..." }` (tanpa `errors`) |

Rekomendasi helper untuk menampilkan pesan error:

```js
function errMsg(xhr, fallback) {
  const body = xhr.responseJSON || {};
  return Object.values(body.errors || {}).flat()[0] || body.message || fallback;
}
```

### Tanggal dan waktu

- Semua tanggal di JSON berformat **ISO 8601 UTC**, contoh `"2026-10-21T17:00:00.000000Z"`.
- Aplikasi memakai zona **Asia/Jakarta (WIB, UTC+7)**. **Selalu format dengan zona `Asia/Jakarta`.**
- **Jangan** memakai `new Date(v).toISOString().slice(0, 10)` untuk menampilkan tanggal. Contoh: tanggal kunjungan 22 Okt 2026 00:00 WIB dikirim sebagai `2026-10-21T17:00:00Z`, sehingga akan tampil **21 Okt** (salah).

```js
const fmtDate = v => v ? new Date(v).toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' }) : '—'; // 2026-10-22
```

- Saat **mengirim** tanggal (contoh `planned_visit_at`), kirim `YYYY-MM-DD` (tanggal WIB). BE yang mengonversi.

### Angka

- `ticket_parts.qty` dikirim sebagai **string desimal**, contoh `"2.00"`. Selalu bungkus dengan `Number(...)`.
- `planned_quantity`, `untracked_quantity`, dan `stock` berupa integer.
- Qty part yang **dikirim** ke BE **wajib bilangan bulat** (lihat bagian 9).

### Permission (role)

Semua aksi baru dikerjakan role **After Sales**. Teknisi hanya mendapat Reschedule, Share survey link, dan melihat kartu Spareparts.

| Permission code | Dipakai untuk |
|---|---|
| `aftersales.tickets.manage` | After Sales: kelola tiket, Delete, edit Spareparts, Reschedule, lihat respons survey |
| `aftersales.tickets.view-own` | Teknisi: lihat tiket miliknya saja |
| `aftersales.tickets.recoming` | Teknisi: Recoming dan Reschedule tiket miliknya |
| `aftersales.survey-link.share` | Teknisi: share survey link tiket miliknya |
| `satisfaction-survey.view` | Menu Satisfaction Survey |
| `satisfaction-survey.review` | Tombol Review (Approve/Reject) |
| `masters.technicians` | Menu Technician (Rating dan Completion hanya untuk After Sales, FR-E05) |

Di Blade: `auth()->user()->hasPermission('aftersales.tickets.manage')`. Sembunyikan tombol yang tidak boleh, meskipun BE tetap menolak dengan 403.

---

## 2. Peta layar ke endpoint

| Layar PRD | Endpoint utama | Bagian |
|---|---|---|
| A5 Add Ticket step 5 (stok kurang) | `POST /tickets` | 5.2 |
| A6 Technician list | `GET /technicians` | 7.1 |
| A7 Technician See Detail | `GET /technicians/{id}`, `GET /tickets?assigned_technician_id=` | 7.2 |
| A8 All Ticket (search, Priority, Delete) | `GET /tickets`, `GET /tickets/{id}`, `DELETE /tickets/{id}` | 3.1 – 3.3 |
| A9 Ticket Detail After Sales | `GET /tickets/{id}`, `POST .../recoming/reschedule`, `POST/PUT .../parts` | 3.2, 4, 5 |
| B2 Ticket Detail teknisi | `GET /tickets/{id}`, `POST .../recoming/reschedule` | 3.2, 4 |
| Update Logs, Closed ditolak | `POST /tickets/{id}/close` | 6.5 |
| D8 Satisfaction Survey list | `GET /satisfaction-surveys` | 6.1 |
| D9 Detail respons | `GET /satisfaction-surveys/{id}` | 6.2 |
| D10 Share survey link (resubmission) | `GET /tickets/{id}/survey-link` (sudah ada) | 6.4 |
| D12 Review Response | `GET /satisfaction-surveys/{id}`, `GET .../form`, `POST .../review` | 6.2, 6.3 |
| Sidebar badge Needs review | Fungsi PHP di Blade | 6.6 |

---

## 3. Tiket

### 3.1 `GET /api/aftersales/tickets`: list All Ticket (A8)

Dipakai juga oleh daftar tiket di Technician See Detail (A7) dan menu Ticket teknisi. Teknisi otomatis hanya melihat tiketnya sendiri.

**Query params**

| Param | Tipe | Keterangan |
|---|---|---|
| `page` | int | Default 1 |
| `per_page` | int | Default 15 |
| `search` | string | **Baru:** mencari `ticket_code`, nama customer, **dan nama teknisi** (FR-E23) |
| `priority[]` | array | **Baru:** multi-select `high`, `medium`, `low` (FR-E24). Juga menerima satu nilai `priority=high` |
| `supervisor` | `me` | Filter Supervisor: tiket dengan supervisor = user login (FR-08) |
| `assigned_technician_id` | int | **`user_id` teknisi** (bukan id profil); dipakai di A7 |
| `customer_id`, `category`, `progress` | | Sudah ada sejak v1.1 |

Semua filter digabung dengan **AND**.

Contoh: `GET /api/aftersales/tickets?search=arya&priority[]=high&priority[]=medium&supervisor=me&page=1`

> Dengan jQuery, `data: { priority: ['high', 'medium'] }` otomatis menjadi `priority[]=high&priority[]=medium`.

**Tombol Reset dan URL query (FR-E24)** dikerjakan di FE: simpan `search`, `priority`, `supervisor` di URL (`history.replaceState`), lalu baca ulang saat halaman dibuka. Reset mengosongkan ketiganya.

**Response 200**

```json
{
  "status": "success",
  "data": [
    {
      "id": 12,
      "ticket_code": "DAX-AF-20261012-2",
      "priority": "high",
      "category": "refrigeration_system",
      "description": "Compressor bunyi kasar",
      "created_at": "2026-10-12T02:10:00.000000Z",
      "customer": { "id": 3, "name": "PT Customer A", "province": { "name": "..." }, "region": { "name": "..." } },
      "customer_product": { "product": { "name": "..." } },
      "technician": { "id": 65, "name": "Arya" },
      "progress": "recoming",
      "current_recoming": {
        "visit_no": 2,
        "planned_visit_at": "2026-10-21T17:00:00.000000Z",
        "reason": "further_work_needed",
        "from_step": "repair"
      },
      "visit_date": "2026-10-21T17:00:00.000000Z",
      "visit_no": 2,
      "closed_at": null,
      "sla_status": "Pending",
      "aging_days": 7
    }
  ],
  "total": 4,
  "current_page": 1,
  "last_page": 1
}
```

**Kolom yang relevan untuk v2.0**

| Field | Untuk |
|---|---|
| `visit_date` | Kolom **VISIT DATE** = visit terbaru. Saat Recoming isinya tanggal kunjungan lanjutan (FR-E18) |
| `visit_no` | Nomor visit terbaru (1, 2, ...) |
| `progress` | Kolom Steps. Nilai `recoming` sekarang perlu ada di map label FE |
| `current_recoming` | Hanya terisi saat `progress = recoming`. Format Steps: `Recoming · visit {visit_no} · {tanggal}` (RC-09) |

Tiket yang sudah di-delete **tidak pernah** muncul.

### 3.2 `GET /api/aftersales/tickets/{id}`: Ticket Detail (A9, B2)

**Response 200** (dipotong; hanya field baru atau yang penting)

```json
{
  "status": "success",
  "data": {
    "id": 12,
    "ticket_code": "DAX-AF-20261012-2",
    "priority": "high",
    "progress": "recoming",
    "current_recoming": { "visit_no": 2, "planned_visit_at": "2026-10-21T17:00:00.000000Z", "reason": "further_work_needed", "from_step": "repair" },
    "documents": { "service_form": "draft", "agreement": "saved" },

    "visit_history": [
      { "visit_no": 1, "scheduled_at": "2026-10-11T17:00:00.000000Z", "note": null, "current": false },
      { "visit_no": 2, "scheduled_at": "2026-10-21T17:00:00.000000Z", "note": "Recoming: Further work needed - ganti kapasitor", "current": true }
    ],
    "can_reschedule": true,

    "parts": [
      {
        "id": 31,
        "ref_sparepart_id": 5,
        "type": "estimated",
        "qty": "1.00",
        "planned_quantity": 2,
        "untracked_quantity": 0,
        "unit": "pieces",
        "unit_price": "1500000.00",
        "sparepart": { "id": 5, "name": "Compressor 2 HP", "part_number": "CMP-2HP", "stock": 14 }
      }
    ],
    "stock_held": 3,

    "survey_filled": true,
    "survey_responses": [
      { "id": 88, "submitted_at": "2026-10-14T02:41:00.000000Z", "average_score": 3.2, "machine_ok": false, "source": "link", "review_status": "needs_review", "review_note": null },
      { "id": 80, "submitted_at": "2026-10-12T05:00:00.000000Z", "average_score": 4.6, "machine_ok": true, "source": "link", "review_status": "rejected", "review_note": "IP sama dengan teknisi" }
    ],
    "satisfaction": { }
  }
}
```

**Field baru**

| Field | Tipe | Untuk |
|---|---|---|
| `visit_history` | array | Kartu **Visit History** (FR-E19). Urut dari visit 1; `current: true` = visit terbaru. Label Visit Date di header = item `current` |
| `can_reschedule` | bool | Tampilkan tombol **Reschedule** hanya jika `true` (FR-E20) |
| `parts` | array | Kartu **Spareparts**. Pakai hanya item dengan `type === "estimated"` (bagian 5) |
| `stock_held` | int | Total stok yang sedang dipegang tiket. Dipakai di modal Delete |
| `survey_filled` | bool | Teknisi (B2): status survey **Filled** / belum, tanpa skor (BR-S6) |
| `survey_responses` | array \| null | After Sales (A9): kartu Satisfaction Survey. Index `0` = **current**, sisanya = **History** (FR-E37). **Selalu `null` untuk teknisi** |
| `satisfaction` | object | Respons current lengkap (sudah ada sejak v1.1). **Tidak dikirim lagi untuk teknisi** |

### 3.3 `DELETE /api/aftersales/tickets/{id}`: Delete ticket (A8, FR-E13 sampai FR-E16)

Soft delete. Hanya After Sales (`aftersales.tickets.manage`).

**Body**

```json
{ "reason": "Tiket dobel, sudah dibuat ulang DAX-AF-20261013-1" }
```

| Field | Rule |
|---|---|
| `reason` | wajib, string, maks 1000 |

**Response 200**

```json
{ "message": "Ticket deleted successfully" }
```

**Efek di BE (tidak perlu dikerjakan FE):**

- Tiket hilang dari All Ticket, daftar tiket teknisi, Total Ticket, Rating, dan Completion.
- Survey link mati; kalau customer membukanya, ia mendapat 410.
- Status teknisi dihitung ulang (Standby kalau tidak ada tiket aktif lain).
- Stok yang dipegang tiket dikembalikan.
- Tercatat di audit log `ticket_deleted` beserta alasannya.

**Isi modal Delete:** sebelum menampilkan modal, panggil `GET /tickets/{id}`, lalu:

| Kondisi | Tampilkan |
|---|---|
| `documents.agreement === 'saved' \|\| documents.service_form === 'saved' \|\| survey_filled` | Warning: dokumen sudah Saved atau survey sudah diisi, dan email yang sudah terkirim tidak bisa ditarik (FR-E14) |
| `stock_held > 0` | Info: `Spareparts held by this ticket ({stock_held} pcs) return to stock.` (FR-E45) |

Delete **tetap boleh** walau dokumen sudah Saved. Restore tidak tersedia di v2.0.

**Error**

| Status | Kapan |
|---|---|
| 422 `errors.reason` | Alasan kosong |
| 403 | Bukan After Sales |
| 404 | Tiket sudah terhapus |

---

## 4. Visit Date dan Reschedule

### 4.1 `POST /api/aftersales/tickets/{id}/recoming/reschedule` (A9, B2, FR-E20)

Memindahkan tanggal kunjungan lanjutan saat tiket berstatus Recoming.

**Siapa:** After Sales, atau teknisi pemilik tiket (permission `aftersales.tickets.recoming`).

**Kapan tombol tampil:** `can_reschedule === true` dari `GET /tickets/{id}`. Nilainya `true` hanya jika `progress === 'recoming'` **dan** tanggal kunjungan lanjutan belum tiba (masih besok atau lebih).

**Body**

```json
{ "planned_visit_at": "2026-10-25", "note": "Customer minta mundur" }
```

| Field | Rule |
|---|---|
| `planned_visit_at` | wajib, `YYYY-MM-DD`, **tidak boleh sebelum hari ini** (pakai `min` = hari ini WIB di date input) |
| `note` | opsional, maks 1000 |

**Response 200**

```json
{ "message": "Follow-up visit rescheduled successfully" }
```

Setelah sukses, reload `GET /tickets/{id}`. `visit_history`, `current_recoming.planned_visit_at`, dan `visit_date` sudah berubah. Penjadwal harian otomatis memakai tanggal baru. SLA due **tidak** berubah.

**Error**

| Status | Pesan |
|---|---|
| 422 | `Reschedule is only available before the follow-up visit date arrives.` |
| 422 `errors.planned_visit_at` | Tanggal kosong atau sudah lewat |
| 403 | Bukan After Sales dan bukan teknisi tiketnya |

### 4.2 Edit ticket pada tiket Recoming (FR-E21)

Wizard Edit tetap memanggil `POST /tickets/{id}/visits` dengan `{ scheduled_at }` seperti sekarang. Kalau tiket sedang **Recoming**, BE **tidak membuat visit baru**. BE memindahkan visit terbaru dan menyinkronkan tanggal Recoming. Tidak ada perubahan yang diperlukan di FE.

---

## 5. Spareparts dan stok

### 5.1 Konsep

- Part di **wizard step 4** = part tiket (`type: "estimated"`).
- **Stok berkurang saat Save Ticket** sebesar qty tiap part (FR-E39).
- Setelah itu After Sales mengoreksi pemakaian nyata di kartu **Spareparts** di Ticket Detail. Setiap perubahan langsung mengubah stok.

Kolom di kartu Spareparts (A9):

| Kolom | Sumber |
|---|---|
| Part | `part.sparepart.name` (+ `part_number`) |
| Planned | `part.planned_quantity`. **`null` tampilkan `—`** (part yang ditambahkan setelah tiket dibuat) |
| Used | `Number(part.qty)`. Bisa diedit oleh After Sales |
| Stock | `part.sparepart.stock` (stok gudang saat ini) |
| Label | `Number(part.qty) === 0` tampilkan **Not used**, dan aksi Remove disembunyikan |

**Siapa yang boleh edit:** hanya After Sales (`aftersales.tickets.manage`), dan **hanya selama `progress !== 'closed'`**. Teknisi (B2) melihat kartu yang sama dalam mode read-only. Setelah Closed, kartu terkunci (FR-E46).

### 5.2 `POST /api/aftersales/tickets` dan `PUT /api/aftersales/tickets/{id}`: Save/Edit Ticket (A5)

Endpoint sudah ada; yang berubah:

| Perubahan | Detail |
|---|---|
| `parts[].qty` **wajib integer** | `POST`: `integer|min:1`. `PUT`: `integer|min:0` |
| Stok kurang, Save **ditolak** | 422, seluruh tiket **tidak tersimpan** |
| Edit: part yang tidak dikirim lagi | Dianggap **Remove**: Used jadi 0, stok kembali, baris tetap ada sebagai "Not used" |
| Edit: tiket sudah Closed | Mengubah part ditolak 422; mengubah field lain tetap boleh |

**Error stok kurang (A5 banner, FR-E40):**

```json
{
  "message": "Not enough stock: Compressor 2 HP requested 3, available 2.",
  "errors": {
    "parts": ["Not enough stock: Compressor 2 HP requested 3, available 2."]
  }
}
```

Tampilkan `errors.parts[0]` di banner step 5: "Ticket was not saved. ...". Arahkan user kembali ke step 4.

**Penting untuk wizard Edit:**

- Prefill step 4 sebaiknya **melewatkan** part dengan `Number(qty) === 0` (baris "Not used"). Kalau ikut terkirim pun aman, karena `PUT` menerima `qty: 0`.
- jQuery **tidak mengirim array kosong**. Kalau user menghapus semua part, `parts: []` tidak sampai ke BE dan BE menganggap part tidak berubah. Kirim body sebagai JSON supaya array kosong ikut terkirim:

```js
$.ajax({ url, method: 'PUT', contentType: 'application/json', data: JSON.stringify(payload) });
```

### 5.3 `POST /api/aftersales/tickets/{id}/parts`: Add part (A9 dialog Add part)

**Body**

```json
{ "ref_sparepart_id": 5, "qty": 1, "unit": "pieces" }
```

| Field | Rule |
|---|---|
| `ref_sparepart_id` | wajib, id dari master sparepart |
| `qty` | wajib, integer ≥ 1 |
| `unit` | opsional: `pieces`, `set`, `lot`, `kilogram`. Default `pieces` |

Kalau part yang sama sudah ada di tiket, qty-nya **ditambahkan** ke baris itu (tidak membuat baris baru).

**Response 201**

```json
{
  "message": "Part added successfully",
  "data": {
    "data": {
      "id": 33, "ticket_id": 12, "ref_sparepart_id": 5, "type": "estimated",
      "qty": "1.00", "planned_quantity": null, "untracked_quantity": 0, "unit": "pieces",
      "sparepart": { "id": 5, "name": "Compressor 2 HP", "stock": 13 }
    }
  }
}
```

**Stok tersedia di dialog** ("Available stock: N"): ambil dari master sparepart `GET /api/aftersales/spareparts` (field `stock`), atau dari `sparepart.stock` kalau part sudah ada di tiket.

### 5.4 `PUT /api/aftersales/tickets/{id}/parts/{partId}`: ubah Used atau Remove (A9)

**Body**

```json
{ "qty": 1 }
```

| Field | Rule |
|---|---|
| `qty` | wajib, integer ≥ 0. **`0` = Remove** |

| Aksi | Efek stok |
|---|---|
| Used turun (3 ke 1) | Stok **bertambah** 2 (`ticket_return`) |
| Remove (`qty: 0`) | Qty penuh kembali, baris tetap ada sebagai "Not used" |
| Used naik (1 ke 4) | Stok **berkurang** 3 (`ticket_out`), ditolak kalau stok kurang |

**Response 200**: `{ "message": "Part updated successfully", "data": { "data": { ...part } } }`

Setelah Add, ubah, atau Remove berhasil, **reload `GET /tickets/{id}`** supaya kolom Stock dan Used sinkron.

### 5.5 Error kartu Spareparts

| Status | Body | Kapan |
|---|---|---|
| 422 | `errors.parts[0]` = `Not enough stock: {name} requested {n}, available {m}.` | Stok kurang |
| 422 | `message` = `Spareparts are locked after the ticket is Closed.` | Tiket sudah Closed |
| 422 | `errors.qty` | Qty bukan integer atau negatif |
| 404 | | Part bukan milik tiket ini |
| 403 | | Bukan After Sales |

### 5.6 Info stok untuk tiket lama

Tiket yang dibuat sebelum v2.0 tidak pernah mengurangi stok. BE menyimpan qty lamanya di `untracked_quantity`, dan bagian itu tidak pernah dipotong maupun dikembalikan (PRD 9.1). FE tidak perlu menampilkan apa pun untuk ini; cukup tahu kenapa stok tidak bertambah saat part tiket lama di-Remove.

Concurrency (dua user mengedit bersamaan) sudah ditangani di BE dengan row lock. FE cukup menampilkan pesan error kalau ada.

---

## 6. Satisfaction Survey

### 6.1 `GET /api/aftersales/satisfaction-surveys`: list (D8)

**Query params**

| Param | Keterangan |
|---|---|
| `search` | Ticket code, customer, teknisi |
| `review_status` | **Filter Review**: `normal`, `needs_review`, `reviewed`, `rejected` (baru). Kosong = All |
| `page`, `per_page` | `per_page` maks 100 |

**Response 200**

```json
{
  "status": "success",
  "data": [
    {
      "id": 88,
      "ticket_id": 12,
      "ticket_code": "DAX-AF-20261012-2",
      "customer": "PT Customer A",
      "technician": "Arya",
      "service_date": "2026-10-12",
      "submitted_at": "2026-10-14T02:41:00.000000Z",
      "average_score": 3.2,
      "machine_ok": false,
      "source": "link",
      "source_label": "Customer link",
      "review_status": "needs_review",
      "review_reasons": ["same_network_as_technician"],
      "reviewed_by": null,
      "is_current": true
    }
  ],
  "total": 6, "current_page": 1, "last_page": 1
}
```

| Field baru | Untuk |
|---|---|
| `review_reasons` | Teks kecil di bawah badge Review (lihat label di bagian 8) |
| `reviewed_by` | "Approved by rizky" / "Rejected by rizky" |
| `is_current` | `false` = respons lama (history) |

**Tombol aksi per baris (D8):**

| Kondisi | Tombol |
|---|---|
| `review_status === 'needs_review'` dan user punya `satisfaction-survey.review` | **Review** (ke D12) |
| Lainnya | **View** (ke D9) |

Banner aturan Closed (teks statis): *A ticket cannot be set to Closed while its latest response is Needs review or Rejected. Responses that are not Normal or Reviewed are left out of the technician rating.*

### 6.2 `GET /api/aftersales/satisfaction-surveys/{id}`: detail (D9, D12)

**Response 200** (field baru diberi komentar)

```json
{
  "status": "success",
  "data": {
    "id": 88,
    "ticket_id": 12,
    "ticket_code": "DAX-AF-20261012-2",
    "summary": {
      "average_score": 3.2,
      "customer": "PT Customer A",
      "machine_ok": false,
      "source": "link",
      "source_label": "Customer link",
      "admin_input_reason": null
    },
    "feedback": {
      "ratings": [ { "key": "timeliness", "label": "Arrival punctuality", "score": 4 } ],
      "machine_ok": false,
      "machine_issue_note": "Masih bunyi",
      "comments": "...",
      "signer_name": "Budi", "signer_position": "Manager", "signature_url": "/api/aftersales/satisfaction-surveys/88/signature"
    },
    "security_trail": {
      "otp_verified": true,
      "otp_verified_at": "2026-10-14T02:36:00.000000Z",
      "otp_phone_masked": "0812••••1234",
      "link_created_by": "Arya",
      "shared_via": "copy",
      "ip_address": "10.0.0.5",
      "user_agent": "Mozilla/5.0 ...",
      "duration_seconds": 192,
      "review_status": "needs_review",
      "review_reasons": ["same_network_as_technician"]
    },
    "reviewed_by": null,
    "reviewed_at": null,
    "review_note": null,          // baru
    "is_current": true,            // baru
    "responses": [                 // baru: kartu "Responses on this ticket" (D9)
      { "id": 88, "submitted_at": "2026-10-14T02:41:00.000000Z", "average_score": 3.2, "review_status": "needs_review", "review_note": null, "reviewed_by": null, "current": true },
      { "id": 80, "submitted_at": "2026-10-12T05:00:00.000000Z", "average_score": 4.6, "review_status": "rejected", "review_note": "IP sama dengan teknisi", "reviewed_by": "rizky", "current": false }
    ]
  }
}
```

**Layar D12 Review Response** memakai data ini:

| Bagian D12 | Sumber |
|---|---|
| Banner atas | Teks statis: tiket tidak bisa Closed sampai respons di-approve atau customer mengirim respons baru |
| A. Job Information | `GET /satisfaction-surveys/{id}/form` → `job_information` (sudah ada) |
| B. Ratings | `feedback.ratings` (+ `summary.average_score`) |
| C. Machine working properly? | `feedback.machine_ok`, `feedback.machine_issue_note` |
| D. Comments | `feedback.comments` |
| Why this was flagged | `security_trail.review_reasons` (label di bagian 8) |
| Security trail | `security_trail.*` |
| Decision panel | `POST /satisfaction-surveys/{id}/review` (6.3) |

`GET /satisfaction-surveys/{id}/form` (view form read-only) tidak berubah.

### 6.3 `POST /api/aftersales/satisfaction-surveys/{id}/review`: Approve/Reject (D12)

**Siapa:** permission `satisfaction-survey.review`. After Sales **boleh** mereview respons yang ia input sendiri (FR-E30).

**Body**

```json
{ "decision": "approve", "note": "Sudah dikonfirmasi via telepon" }
```

```json
{ "decision": "reject", "note": "IP sama dengan teknisi, minta customer isi ulang" }
```

| Field | Rule |
|---|---|
| `decision` | `approve` atau `reject`. Kalau dikosongkan, BE menganggapnya `approve` (kompatibel dengan tombol lama Mark as reviewed) |
| `note` | **Wajib jika `reject`**, opsional jika `approve`, maks 2000 |

**Efek**

| Decision | Status jadi | Efek lain |
|---|---|---|
| `approve` | `reviewed` | Gate Closed terbuka. Dihitung ke Rating **kecuali** `source = admin` |
| `reject` | `rejected` | Tidak dihitung ke Rating, gate Closed tetap aktif. **Survey link baru berstatus Active** diterbitkan untuk tiket yang sama |

**Response 200**

```json
{
  "message": "Response rejected; a new survey link was issued",
  "data": { "data": { "id": 88, "review_status": "rejected", "review_note": "...", "reviewed_by_user_id": 4, "reviewed_at": "..." } }
}
```

Pesan approve: `Response approved`.

**Error**

| Status | Pesan / field | Kapan |
|---|---|---|
| 422 `errors.note` | | Reject tanpa Note |
| 422 | `Only responses that need review can be reviewed.` | Status bukan `needs_review` (contoh: sudah direview user lain) |
| 422 | `Only the current response can be rejected.` | Reject respons lama |
| 422 | `A new survey link can only be issued after Documentation Published.` | Reject saat tiket tidak di Documentation Published / Satisfaction Submitted (contoh: sedang Recoming) |
| 404 | | Tiket sudah dihapus |

### 6.4 Setelah Reject: link resubmission (D10, FR-E32)

Pakai endpoint yang **sudah ada**:

- `GET /api/aftersales/tickets/{id}/survey-link`: status `active` dengan `url` baru.
- `POST /api/aftersales/tickets/{id}/survey-link/share` dengan body `{ "via": "copy" | "whatsapp" }`.

**Banner "Resubmission link" di D10** tampil jika respons current di `GET /tickets/{id}` (`survey_responses[0]`) berstatus `rejected` **dan** link berstatus `active`. Teks: *The response of {tanggal survey_responses[0].submitted_at} was rejected. Share this new link with the customer.*

Link boleh dibagikan oleh After Sales dan teknisi tiketnya. Respons baru yang masuk otomatis menjadi **current**, dan respons lama menjadi history. Penanda Needs review (IP/perangkat teknisi, < 30 detik, dll.) tetap berlaku.

**Customer tidak mau mengisi ulang (FR-E31):** After Sales memakai **Enter on behalf of customer** (endpoint sudah ada: `POST /tickets/{id}/survey-response`, alasan wajib). Respons ini otomatis Needs review, lalu di-Approve. Tidak ada aksi untuk melewati survey.

### 6.5 Gate Closed: `POST /api/aftersales/tickets/{id}/close` (Update Logs)

Endpoint sudah ada; perilakunya berubah:

| Kondisi | Hasil |
|---|---|
| Belum Documentation Published | 422 `This ticket cannot be closed until documentation is published.` |
| Respons current `needs_review` | **422 khusus** (lihat di bawah) |
| Respons current `rejected` | **422 khusus** |
| **Tidak ada survey sama sekali** | **Boleh Closed** (baru; sebelumnya wajib ada survey) |
| Respons current `normal` / `reviewed` | Boleh Closed |

**Response 422 khusus gate:**

```json
{
  "status": "error",
  "message": "Cannot set to Closed. The latest survey response is Needs review. Review it first.",
  "satisfaction_id": 88
}
```

Kalau statusnya `rejected`, pesannya: `Cannot set to Closed. The latest survey response was rejected; waiting for the customer to submit a new response.`

**Di modal Update Logs:** kalau response 422 punya `satisfaction_id`, tampilkan pesan di kotak merah beserta tombol **Open Review Response** yang menuju D12 untuk respons tersebut. Contoh dengan routing hash halaman survey saat ini: `/satisfaction-survey#88`.

### 6.6 Badge sidebar Needs review (D8, FR-E25)

Tidak ada endpoint; ambil langsung di Blade sidebar (`resources/views/partials/sidebar.blade.php`, menu Satisfaction Survey):

```blade
@php($needsReview = \App\Http\Controllers\AfterSales\Dats\SatisfactionSurveyController::needsReview())
@if ($needsReview > 0)
    <span class="...">{{ $needsReview }}</span>
@endif
```

Nilainya adalah jumlah respons `needs_review` yang tiketnya belum dihapus.

---

## 7. Technician

### 7.1 `GET /api/aftersales/technicians`: Technician list (A6)

**Query params baru**

| Param | Nilai | Keterangan |
|---|---|---|
| `period` | `all` (default), `30`, `90`, `365` | Filter **Period**: semua waktu / 30 / 90 / 365 hari terakhir (FR-E06). Rating memakai `submitted_at` respons; Completion memakai tanggal Closed tiket |

Label usulan dropdown: `All time`, `Last 30 days`, `Last 90 days`, `Last 12 months`.

**Field baru per item**

```json
{
  "id": 7,
  "user_id": 65,
  "user": { "id": 65, "name": "Arya", "grade": "2" },
  "status": "On Duty",
  "total_tickets": 6,

  "rating": 4.4,
  "rating_count": 3,
  "completion": 67,
  "completion_closed": 2,
  "completion_total": 3
}
```

**Format tampilan**

| Kolom | Format | Kalau kosong |
|---|---|---|
| RATING | `{rating} ({rating_count})` contoh `4.4 (3)` | `rating === null` tampilkan `—` |
| FIRST-VISIT COMPLETION | `{completion}% ({completion_closed}/{completion_total})` contoh `67% (2/3)` | `completion === null` tampilkan `—` |

Kolom ini **hanya untuk After Sales** (FR-E05). Teknisi tidak pernah melihat skor.

Tambahkan menu **See Detail** di aksi `•••`, mengarah ke halaman detail teknisi dengan id = **`row.id`** (id profil teknisi, bukan `user_id`).

### 7.2 `GET /api/aftersales/technicians/{id}`: Technician See Detail (A7)

`{id}` = **id profil teknisi** (`row.id` dari list). Menerima `?period=` yang sama.

**Response 200** (field baru: `score`)

```json
{
  "status": "success",
  "data": {
    "id": 7,
    "user_id": 65,
    "user": { "name": "Arya", "grade": "2" },
    "status": "On Duty",
    "skills": [ { "skill_name": "Refrigeration", "level": "intermediate" } ],
    "kpi": { },
    "score": {
      "rating": 4.4,
      "rating_count": 3,
      "completion": 67,
      "completion_closed": 2,
      "completion_total": 3,
      "aspects": [
        { "key": "timeliness", "label": "Arrival punctuality", "average": 4.7 },
        { "key": "technician_attitude", "label": "Technician attitude", "average": 4.3 }
      ],
      "recoming_reasons": [
        { "reason": "further_work_needed",   "label": "Further work needed",      "tickets": 1, "counted": true },
        { "reason": "other",                 "label": "Other",                    "tickets": 0, "counted": true },
        { "reason": "survey",                "label": "Survey (Machine: No)",     "tickets": 0, "counted": true },
        { "reason": "sparepart_unavailable", "label": "Part unavailable",         "tickets": 1, "counted": false },
        { "reason": "site_access",           "label": "Site access or condition", "tickets": 0, "counted": false }
      ],
      "responses": [
        { "id": 88, "ticket_code": "DAX-AF-20261012-2", "submitted_at": "2026-10-14T02:41:00.000000Z", "score": 3.2, "review_status": "needs_review", "counted": false, "not_counted_reason": "Needs review" },
        { "id": 70, "ticket_code": "DAX-AF-20261008-1", "submitted_at": "2026-10-11T03:00:00.000000Z", "score": 4.9, "review_status": "reviewed", "counted": true, "not_counted_reason": null }
      ]
    }
  }
}
```

**Mapping ke kartu A7**

| Kartu | Sumber |
|---|---|
| Rating | `score.rating` + `/ 5` + `({rating_count} counted)`. Jumlah tidak dihitung = `responses.filter(r => !r.counted).length` |
| First-Visit Completion | `score.completion`% + `({completion_closed} of {completion_total})`. Keterangan: `C = completion_closed, R = completion_total - completion_closed` |
| Rating per aspect | `score.aspects` (bar 0–5, `average`) |
| Recoming reasons | `score.recoming_reasons`; badge **Counted** jika `counted`, selain itu **Not counted** (FR-E12) |
| Survey responses | `score.responses`; kolom IN RATING = `Counted`, atau `Not counted · {not_counted_reason}` |
| Tickets (search + Priority) | `GET /api/aftersales/tickets?assigned_technician_id={data.user_id}&search=...&priority[]=...` |

Nilai `not_counted_reason`: `Not current`, `Entered by admin`, `Needs review`, `Rejected`.

**Aturan hitung (info, semua di BE):**

- **Rating:** rata-rata `average_score` dari respons **current** tiap tiket dengan `source = link` dan status `normal` atau `reviewed`.
- **Completion:** C ÷ (C + R).
  - C = tiket Closed yang tidak pernah Recoming.
  - R = tiket yang punya minimal satu Recoming yang dihitung (Further work needed, Other, atau Recoming dari survey).
  - Tiket yang Recoming-nya **hanya** Part unavailable dan/atau Site access dikeluarkan dari C dan R.
  - Tiket yang belum Closed dan belum pernah Recoming, serta tiket yang dihapus, tidak dihitung.
- Kalau teknisi tiket diganti, Rating ikut teknisi yang sekarang tercatat di tiket.

---

## 8. Enum dan label

### Progress tiket (`progress`)

| Value | Label |
|---|---|
| `published` | Published |
| `assigned` | Assigned |
| `on_site` | On Site |
| `repair` | Repair |
| `waiting_sparepart` | Waiting Sparepart |
| `documentation_published` | Documentation Published |
| `satisfaction_submitted` | Satisfaction Submitted |
| `closed` | Closed |
| `recoming` | Recoming (**tambahkan ke map label FE**) |

### Priority (`priority`)

`high` (High), `medium` (Medium), `low` (Low)

### Alasan Recoming (`reason`)

| Value | Label | Dihitung di Completion |
|---|---|---|
| `further_work_needed` | Further work needed | Ya |
| `other` | Other | Ya |
| `sparepart_unavailable` | Part unavailable | Tidak |
| `site_access` | Site access or condition | Tidak |
| (dari survey, `from_step` = `documentation_published` / `satisfaction_submitted`) | Survey (Machine: No) | Ya |

### Status review respons (`review_status`)

| Value | Label | Badge | Dihitung ke Rating | Gate Closed |
|---|---|---|---|---|
| `normal` | Normal | abu-abu | Ya (jika `source = link`) | Terbuka |
| `needs_review` | Needs review | kuning | Tidak | **Blok** |
| `reviewed` | Reviewed | hijau | Ya (jika `source = link`) | Terbuka |
| `rejected` | Rejected (**baru**) | merah | Tidak | **Blok** |

### Alasan Needs review (`review_reasons`)

| Code | Label |
|---|---|
| `fast_completion` | Completed in under 30 seconds |
| `same_network_as_technician` | Same network as the technician |
| `same_browser_as_technician` | Browser/device signature matches a technician login within 24 hours |
| `phone_changed_recently` | Customer WhatsApp number changed in the last 24 hours |
| `admin_input` | Entered by admin |

### Sumber respons (`source`)

`link` (Customer link), `admin` (Entered by admin; **tidak pernah** dihitung ke Rating)

### Status survey link (`status` di `/survey-link`)

`inactive` (Not active), `active` (Active), `used` (Completed), `expired` (Expired), `replaced` (Replaced)

### Tipe stock movement (menu Sparepart, `GET /spareparts/{id}/stock-movements`)

| Value | Label usulan |
|---|---|
| `in` | Stock in |
| `out` | Stock out |
| `adjustment` | Adjustment |
| `ticket_out` (**baru**) | Used on ticket |
| `ticket_return` (**baru**) | Returned from ticket |

Movement `ticket_out` / `ticket_return` membawa `ticket_id` dan `ticket_part_id`; field `note` berisi `Used on ticket DAX-AF-...` atau `Returned from ticket DAX-AF-...`.

---

## 9. Perubahan perilaku

Hal-hal berikut bisa memengaruhi layar yang **sudah ada**:

1. **Qty part wajib bilangan bulat** di `POST/PUT /tickets`, Add part, dan ubah Used. Ganti input qty menjadi `type="number" step="1" min="1"`. Qty desimal sekarang ditolak 422.
2. **Teknisi tidak lagi menerima `satisfaction`** dari `GET /tickets/{id}`, dan `survey_responses` selalu `null` untuk teknisi (BR-S6). Kalau ada layar teknisi yang membaca skor dari situ (contoh: Preview Satisfaction di Update Logs), ganti dengan status `survey_filled`.
3. **Ticket tanpa survey boleh Closed.** Sebelumnya Closed wajib menunggu Satisfaction Submitted.
4. **Reject respons membuat link survey Active lagi** walaupun progress sudah Satisfaction Submitted.
5. **Input stok manual** (`POST /spareparts/{id}/stock-movements` dengan `type: out`) yang melebihi stok sekarang **ditolak 422** (`Not enough stock: {name} available {n}.`). Sebelumnya stok dipaksa jadi 0.
6. **`POST /tickets/{id}/documentation`** (endpoint lama) tidak lagi mengurangi stok. Stok hanya diatur lewat kartu Spareparts.
7. **Save Ticket bisa gagal karena stok.** Tampilkan `errors.parts[0]` dengan jelas di step 5.

---

## 10. Checklist FE per layar

- [ ] **A5 Add Ticket step 5:** banner error `errors.parts[0]` saat stok kurang; qty input integer.
- [ ] **A6 Technician list:** kolom RATING dan FIRST-VISIT COMPLETION, filter Period, menu See Detail. Tabel menjadi lebih lebar, jadi pakai scroll horizontal.
- [ ] **A7 Technician See Detail:** kartu Rating, Completion, Rating per aspect, Recoming reasons, Survey responses; daftar tiket dengan search dan Priority.
- [ ] **A8 All Ticket:** search (placeholder *ticket code, customer, technician*), chip Priority multi, Supervisor, Reset, filter di URL query; kolom Visit Date dari `visit_date`; label Steps Recoming; aksi Delete dan modalnya.
- [ ] **A9 Ticket Detail (After Sales):** Visit Date dari visit terbaru + label "follow-up visit"; kartu Visit History; tombol dan dialog Reschedule; kartu Spareparts (Used editable, Remove, Add part); kartu Satisfaction Survey dengan respons current dan History.
- [ ] **B2 Ticket Detail (teknisi):** Reschedule (jika `can_reschedule`); Spareparts read-only; Survey hanya status (`survey_filled`).
- [ ] **Update Logs, Closed:** kotak merah dari 422 + tombol Open Review Response (`satisfaction_id`).
- [ ] **D8 Satisfaction Survey list:** opsi filter Review (All, Needs review, Rejected, Reviewed, Normal), tombol Review/View, alasan di bawah badge, banner aturan Closed, badge sidebar.
- [ ] **D9 Detail respons:** tombol Review (jika `needs_review`), kartu Responses on this ticket.
- [ ] **D10 Share survey link:** banner Resubmission link setelah Reject.
- [ ] **D12 Review Response (layar baru):** isi read-only, Why this was flagged, Security trail, panel Decision (Approve / Reject + Note wajib saat Reject).
- [ ] **Map label:** tambah `recoming` (progress), `rejected` (review), `ticket_out` dan `ticket_return` (stock movement).
- [ ] **Format tanggal:** semua tanggal diformat dengan zona `Asia/Jakarta`.

Pertanyaan kontrak bisa ditanyakan ke tim BE. Keputusan sementara dari Open Items PRD 10.1:

- Default Period = `all`.
- Teknisi boleh Reschedule tiketnya sendiri.
- Priority = high / medium / low.
