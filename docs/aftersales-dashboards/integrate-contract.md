# Integration Contract — Technician Dashboard

Kontrak backend untuk slicing **My Dashboard** dari commit `3ba9f07`. Scope: Overall rating, My tickets, SLA on time, Today Task, Closed tickets, To-do, dan Closed per week. Tim FE menghubungkan slicing ke endpoint di bawah. Implementasi ini tidak mengubah view, JavaScript, atau CSS dashboard.

Baseline bisnis: PRD v2.3 bab 7 dan W3; aturan workflow, survey, dan SLA v3 mengikuti [kontrak Aftersales v3.0](../aftersales-v3.0/integrate-contract.md). Definisi tabel sudah dicocokkan dengan `local_daxx (9).sql` serta migration repository. Dokumen ini khusus dashboard technician, bukan pengganti kontrak ticket detail atau dashboard supervisor.

## 1. Setup, autentikasi, dan akses

- Gunakan database development/integrasi dengan seluruh migration repository terpasang. Jalankan `php artisan migrate` pada environment tersebut sesuai proses deployment tim. Jangan import dump development ke staging/prod.
- Penambahan endpoint ini tidak membutuhkan tabel baru. Migration perbaikan role Technician sebelumnya tetap diperlukan bila database mempunyai role Technician dengan `code` kosong.
- Login menggunakan mekanisme session aplikasi. Kirim cookie session dan header `Accept: application/json` pada semua request. GET tidak membutuhkan token CSRF; aksi POST/PUT pada tiket tetap mengikuti kontrak existing.
- Role wajib berkode `technician`, dengan permission `dashboard` dan `aftersales.tickets.view-own`.
- Semua query dibatasi `tickets.assigned_technician_id = user yang login`. Tidak ada selector teknisi. Parameter `user_id`, `technician_id`, dan `assigned_technician_id` dengan nilai terisi ditolak **422**.
- Role Admin, SPV, Head, dan Sales mendapat **403** pada endpoint self-service ini.
- Token survey, jawaban, review note, identitas responden, score per tiket/aspek, dan jumlah respons survey tidak dikirim. Nama customer pada daftar tugas/tiket hanya berasal dari tiket milik teknisi tersebut.

## 2. Daftar endpoint dan filter

Base path: `/api/dashboard/aftersales/technician`.

| Method | Path | Kegunaan |
| --- | --- | --- |
| GET | `/api/dashboard/aftersales/technician` | Ringkasan seluruh panel; `today.items` berisi maksimal 10 tugas pertama |
| GET | `/api/dashboard/aftersales/technician/tasks` | Seluruh tugas hari ini dengan pagination |
| GET | `/api/dashboard/aftersales/technician/tickets` | Drill-down status, to-do, atau Closed |

| Parameter | Berlaku pada | Nilai/default |
| --- | --- | --- |
| `period` | Semua endpoint; memengaruhi Closed/SLA saja | `this_month` (default), `last_month`, `this_year` |
| `page` | Daftar tasks/tickets | Integer >= 1, default 1 |
| `per_page` | Daftar tasks/tickets | Integer 1–100, default 20 |
| `metric` | `/tickets`, wajib | `status`, `documents`, `share_survey`, `recoming`, `closed` |
| `status` | `/tickets?metric=status`, opsional | `assigned`, `on_site`, `repair`, `waiting_sparepart`, `documentation_published`, `satisfaction_submitted`, `happy_call_completed`, `recoming` |
| `week_start` | `/tickets?metric=closed`, opsional | `YYYY-MM-DD`; kirim `closed.weeks[i].key` |

`status` hanya digunakan pada metric `status`; `week_start` hanya pada metric `closed`. Parameter pagination pada summary tidak mengubah preview `today.items`, yang selalu mengambil halaman pertama maksimal 10 tugas. Filter yang tidak dikenal tidak memilih akun lain.

Semua batas hari/bulan/tahun dan jam task memakai **Asia/Jakarta**. `this_month` mencakup tanggal 1 sampai akhir bulan; `last_month` bulan kalender sebelumnya; `this_year` 1 Januari–31 Desember tahun berjalan. Timestamp response memakai ISO 8601 dengan offset `+07:00`. Jangan mengonversi ulang field `time` yang sudah berupa `HH:mm` Jakarta.

### Cakupan Period

| Panel | Mengikuti Period? | Acuan |
| --- | --- | --- |
| Overall rating | Tidak | Seluruh respons valid pada tiket yang saat ini ditugaskan ke teknisi |
| My tickets | Tidak | Snapshot progress tiket open saat request |
| SLA on time | Ya | Tiket yang saat ini Closed dengan waktu log Closed terakhir di dalam Period |
| Today Task | Tidak | Jadwal terbaru hari ini di Jakarta |
| Closed tickets / Closed per week | Ya | Sama dengan SLA, dikelompokkan berdasarkan tanggal Closed |
| To-do | Tidak | Antrian saat ini; Upcoming Recoming sampai 7 hari ke depan |

Perubahan assignment memengaruhi pemilik metrik karena schema tidak menyimpan snapshot pemilik KPI historis. Tiket soft-deleted atau `is_deleted = 1` tidak dihitung. Log/visit/Recoming/link/survey yang dihapus juga diabaikan. Tidak ada backfill progress atau penulisan data dalam endpoint GET ini.

## 3. Ringkasan response dan pemetaan panel

Response summary **200** menggunakan envelope `{ "data": { ... } }`.

| Field | Bentuk | Pemakaian FE |
| --- | --- | --- |
| `period` | `{ key, start, end }` | Filter Period dan rentang aktif, tanggal `YYYY-MM-DD` |
| `timezone` | `"Asia/Jakarta"` | Zona bisnis |
| `generated_at` | ISO datetime | Waktu snapshot dibuat |
| `rating` | `{ score: number\|null, max: 3 }` | Angka `2.7 / 3`; ring `score / max * 100` |
| `tickets` | `{ total_active, total_open, other_active, statuses: [...] }` | Donut empat status dan angka di tengah |
| `sla` | `{ value, target, on_time, overtime, unknown, total_closed, months: [...] }` | Persentase, target, dan line chart |
| `today` | `{ date, total, has_more, items: [...] }` | Today Task; maksimum 10 row pada summary |
| `closed` | `{ total, weeks: [...] }` | Closed tickets dan Closed per week memakai dataset mingguan yang sama |
| `todo` | `{ total, items: [...] }` | Angka total dan bar Share survey/Documents/Recoming |

### 3.1 Overall rating

Contoh: `"rating": { "score": 2.7, "max": 3 }`.

- Hanya respons current yang review-nya `normal` atau `reviewed` yang dapat dihitung. Respons current `needs_review`/`rejected` tidak menyebabkan fallback ke respons lama.
- Legacy: current per tiket, sumber `link` saja, menggunakan rerata score per respons. v3: current per siklus menurut `filled_at DESC, id DESC`; sumber `link`, atau `admin` yang sudah `reviewed`; rerata berbobot jumlah jawaban rating, sehingga site/phone dengan jumlah pertanyaan berbeda mengikuti aturan existing.
- Template menggunakan `ticket_satisfaction_answers` bertipe `rating`; respons tanpa template memakai kolom aspek legacy. v3 membutuhkan `cycle_id` dan `filled_at` terisi.
- Angka dibulatkan satu desimal. `null` berarti belum ada score yang dapat dihitung: tampilkan `—`, bukan `0 / 3`.
- Slicing berisi dummy `Excellent` dan `24 responses`. Backend tidak memberi kategori label baru atau jumlah respons. Jangan mempertahankan teks dummy itu; pembatasan tampilan teknisi mengikuti PRD: satu Overall Rating agregat, read-only.

### 3.2 My tickets

```json
{
  "total_active": 12,
  "total_open": 19,
  "other_active": 7,
  "statuses": [
    { "key": "assigned", "label": "Assigned", "count": 3 },
    { "key": "on_site", "label": "On Site", "count": 4 },
    { "key": "repair", "label": "Repair", "count": 3 },
    { "key": "waiting_sparepart", "label": "Waiting", "count": 2 }
  ]
}
```

- Urutan empat status tetap. Nilai nol tetap dikirim.
- Donut series = `statuses.map(row => row.count)`; center = `total_active` (jumlah empat status); persentase legenda = `count / total_active * 100`. Saat total nol gunakan 0%, hindari NaN.
- `total_open` mencakup seluruh progress selain Closed. `other_active` mencakup dokumentasi, survey, Happy Call, dan Recoming; `total_open = total_active + other_active`. Jangan memasukkan status itu secara paksa ke empat segmen slicing.
- Progress memakai log aktif dengan ID terbesar. Tanpa log lanjutan, assignment memberi floor `assigned`; visit terbaru yang harinya sudah tiba memberi floor `on_site`, sesuai accessor tiket. Phone-call v3 tidak memperoleh floor On Site dari visit lama. Status Repair/Waiting/Documentation/Closed/Recoming tidak ditimpa floor tersebut.
- Klik status: `GET /api/dashboard/aftersales/technician/tickets?metric=status&status=repair`. Tanpa `status`, metric ini memberi semua tiket open milik sendiri, termasuk `other_active`.

### 3.3 SLA on time dan target

```json
{
  "value": 88.2,
  "target": 96,
  "on_time": 30,
  "overtime": 3,
  "unknown": 1,
  "total_closed": 34,
  "months": [ { "key": "2026-11", "label": "Nov 2026", "value": 88.2 } ]
}
```

- `value = on_time / total_closed * 100`, dibulatkan satu desimal, sesuai denominator semua Closed dalam PRD v2.3. Jika belum ada Closed, `value = null`.
- Legacy membandingkan waktu Closed terakhir dengan `tickets.sla_due_at`.
- v3 membandingkan `sla_stopped_at` pada **siklus terkini** dengan deadline siklus tersebut. SLA harus mempunyai waktu mulai (`sla_started_at`, atau `started_at` untuk siklus pertama). Waktu Closed tidak menggantikan waktu stop survey v3. Siklus lama tidak menggantikan SLA siklus terkini yang belum lengkap.
- Deadline/stop/start yang belum tersedia menghasilkan `sla_result = null`, masuk `unknown`. Nilai ini tetap masuk denominator Closed; jangan menganggapnya Overtime atau membuat deadline pengganti.
- `months` berisi semua bulan kalender di Period, termasuk bulan tanpa Closed (`value: null`). Pada `this_month`/`last_month` hanya ada satu titik; jangan terus menampilkan Jan–Jun dari dummy. Pada `this_year` ada 12 titik.
- `target` berasal dari `config/aftersales.php`, default 96 mengikuti garis target slicing. Ini target tampilan yang dapat dikonfigurasi, bukan angka hasil query atau SLA kontraktual tiap tiket.

### 3.4 Today Task

Contoh satu item `today.items` atau `GET /tasks`:

```json
{
  "id": 101,
  "ticket_code": "EXAMPLE-AF-101",
  "priority": "high",
  "progress": "repair",
  "customer_name": "Example Customer",
  "machine_name": "TD 150",
  "machine_category": "Tube",
  "closed_at": null,
  "sla_result": null,
  "type": "visit_site",
  "visit_id": 51,
  "scheduled_at": "2026-11-05T09:00:00+07:00",
  "time": "09:00",
  "status": "active"
}
```

- Mengambil visit aktif terbaru per tiket menurut `scheduled_at DESC, id DESC`, bukan semua histori visit. Jadwal yang sudah dipindah ke besok tidak muncul sebagai tugas hari ini.
- Visit v3 harus terhubung ke siklus terkini. Visit historis tanpa `cycle_id` tidak dianggap milik siklus terkini.
- Phone-call Recoming v3 pada siklus terkini juga dapat menjadi tugas hari ini: `type: "phone_call"`, jadwal dari `planned_call_at`, `visit_id: null`; tidak membuat visit fisik palsu. Tetap tampil saat call telah memasuki progress Repair atau Closed pada hari tersebut.
- Task hari ini dapat berstatus `done` walaupun tiketnya sudah Closed. Ini berbeda dari antrian My tickets/To-do yang hanya memuat tiket open.
- Urut naik berdasarkan waktu jadwal, lalu ID tiket. `scheduled_at` lebih awal dari sekarang tidak otomatis berarti selesai.
- Status badge: `done` jika Closed atau actual_end visit terisi; `waiting` jika Waiting Sparepart; `active` jika visit sudah actual_start tanpa actual_end atau progress Repair; selain itu `next`.
- `customer_name`, `machine_name`, `machine_category` dapat null. Gunakan fallback `—`; render teks dengan escaping/textContent.
- Summary: `today.total` jumlah semua tugas hari ini, `today.items` 10 pertama, `today.has_more` menandai sisanya. Gunakan `/tasks?page=2&per_page=10` untuk melanjutkan preview atau muat ulang seluruh daftar dengan pagination default 20.
- Klik row memakai ID tiket untuk membuka route detail existing `aftersales.pages.tickets.show` (`/aftersales/tickets/{id}`).

### 3.5 Closed tickets dan Closed per week

```json
{
  "total": 34,
  "weeks": [
    { "key": "2026-11-01", "label": "W1", "start": "2026-11-01", "end": "2026-11-01", "total": 1, "on_time": 1, "overtime": 0, "unknown": 0 },
    { "key": "2026-11-02", "label": "W2", "start": "2026-11-02", "end": "2026-11-08", "total": 10, "on_time": 9, "overtime": 1, "unknown": 0 }
  ]
}
```

Contoh di atas memotong array setelah dua bucket untuk menunjukkan struktur. Response asli memuat seluruh bucket Period, termasuk nol. Minggu kalender **Senin–Minggu**, dipotong pada batas Period. Karena November 2026 dimulai Minggu, W1 hanya 1 November dan bulan ini memiliki **6 bucket**, bukan selalu 4.

- Closed tickets: nilai utama `closed.total`, x-axis `weeks[].label`, series `weeks[].total`.
- Closed per week: x-axis yang sama, series `weeks[].on_time` dan `weeks[].overtime`. `unknown` tidak boleh dipindahkan ke salah satunya. Jika ada unknown, beri informasi jumlah belum terklasifikasi pada tooltip/keterangan agar perbedaan jumlah jelas.
- Setiap bucket memenuhi `total = on_time + overtime + unknown`; jumlah semua `weeks[].total` sama dengan `closed.total`.
- Klik bucket: `GET /api/dashboard/aftersales/technician/tickets?metric=closed&period=this_month&week_start=2026-11-02`.
- Endpoint Closed hanya mengirim tiket yang **saat ini** Closed. Tiket yang pernah Closed lalu Recoming lagi tidak dihitung sebagai Closed saat ini.

### 3.6 To-do

```json
{
  "total": 10,
  "items": [
    { "key": "share_survey", "label": "Share survey", "count": 5 },
    { "key": "documents", "label": "Documents", "count": 3 },
    { "key": "recoming", "label": "Recoming", "count": 2 }
  ]
}
```

Urutan tetap. Total adalah **jumlah pekerjaan antar kategori**, bukan distinct tiket; satu tiket dapat masuk lebih dari satu kategori. Panjang bar dapat memakai `count / max(counts) * 100`; jika semuanya nol gunakan 0%.

| Key | Kondisi |
| --- | --- |
| `share_survey` | Tiket open pada Documentation Published / Satisfaction Submitted / Happy Call Completed. Link terbaru dalam siklus terkait berstatus active, belum shared, dan belum expired. Untuk v3 harus siklus terkini. Link used/replaced/expired, link lama yang sudah diganti, dan link siklus sebelumnya tidak dihitung. |
| `documents` | Legacy: Agreement atau Service Form belum tersedia atau belum saved. v3: siklus terkini belum ada atau `documentation_status` masih incomplete; dokumen yang sedang menunggu tanda tangan SPV bukan pekerjaan draft teknisi. |
| `recoming` | Progress Recoming, jadwal lanjutan dari sekarang sampai akhir hari ke-7 setelah hari ini. Visit-site memakai planned_visit_at; phone-call memakai planned_call_at. |

Klik bar menggunakan `/tickets?metric={key}`. Jumlah `total` pada daftar sama dengan count summary ketika dibaca pada kondisi database dan waktu yang sama. Filter Period tidak mengubah antrian ini.

To-do bukan pemberian izin aksi baru. Untuk aksi, buka detail tiket dan ikuti permission/action flags endpoint existing. Teknisi v3 **tidak** diberi tombol membuat/reschedule Recoming, meskipun PRD v2.3 lama menyebutnya. Share menggunakan endpoint existing `POST /api/aftersales/tickets/{id}/survey-link/share` dan tetap membutuhkan `aftersales.survey-link.share`. Form/Agreement mengikuti kontrak v3, termasuk lifecycle submit/tanda tangan/publish; jangan menandai pekerjaan selesai hanya dari klik dashboard.

## 4. Pagination dan bentuk row drill-down

`GET /tasks` dan `/tickets` memakai paginator Laravel, bukan envelope summary:

```json
{
  "current_page": 1,
  "data": [],
  "first_page_url": "https://example.test/api/dashboard/aftersales/technician/tickets?page=1",
  "from": null,
  "last_page": 1,
  "last_page_url": "https://example.test/api/dashboard/aftersales/technician/tickets?page=1",
  "links": [],
  "next_page_url": null,
  "path": "https://example.test/api/dashboard/aftersales/technician/tickets",
  "per_page": 20,
  "prev_page_url": null,
  "to": null,
  "total": 0
}
```

URL/link dan isi `links` di atas hanya ilustrasi. Simpan filter FE ketika mengganti halaman: kirim ulang `metric`, `status`/`week_start`, `period`, `per_page`, dan `page`. Jangan bergantung pada filter tersalin ke pagination URL.

Row `/tickets` hanya berisi `id`, `ticket_code`, `priority`, `progress`, `customer_name`, `machine_name`, `machine_category`, `closed_at`, `sla_result`. Row `/tasks` menambah field task pada §3.4. Daftar tiket diurutkan ID descending; tasks diurutkan jadwal ascending lalu ID ascending. Halaman melewati batas menghasilkan `data: []`, bukan error.

## 5. Empty, loading, dan error states

- Counts selalu integer >= 0; array status/to-do/minggu/bulan tetap diberikan meski tidak ada data. Tasks kosong berupa `[]`.
- Rating dan persentase tanpa denominator memakai `null`, tampilkan `—`. `0` adalah hasil sah dan harus ditampilkan sebagai 0, bukan dianggap kosong.
- **401**: belum login/session expired; arahkan ke alur login existing.
- **403**: role/permission tidak sesuai. Tampilkan akses ditolak, jangan fallback ke data dummy atau endpoint supervisor.
- **422**: query invalid; envelope Laravel `{ "message": "...", "errors": { "period": ["..."] } }`. Misalnya `period=custom`, `per_page=101`, atau selector teknisi terisi.
- Kegagalan jaringan/5xx: tampilkan error/retry sesuai pola aplikasi. Jangan menyajikan dummy seolah data live.
- Saat Period berubah, batalkan/abaikan response request lama agar data periode terdahulu tidak menimpa pilihan terakhir. Endpoint summary tidak mengubah assignment, workflow, dokumen, ataupun survey.

Contoh pemanggilan FE:

```js
const response = await fetch(
  `/api/dashboard/aftersales/technician?${new URLSearchParams({ period })}`,
  { credentials: 'same-origin', headers: { Accept: 'application/json' }, signal }
);
if (!response.ok) throw new Error(`Dashboard request failed: ${response.status}`);
const { data } = await response.json();
// Map field di §3 ke renderer slicing; tidak perlu menghitung KPI ulang di FE.
```

## 6. Checklist handoff FE

- Ganti objek dummy `techData` dengan response API; sesuaikan renderer pada field kontrak ini, bukan menyimpan angka dummy 2.7/12/92/34/10 atau Jan–Jun.
- Hubungkan filter Period ke summary; Today/To-do/My tickets/Overall rating tetap snapshot sesuai aturan, Closed/SLA mengikuti Period.
- Gunakan `rating.score` dan `rating.max`; hilangkan jumlah respons dummy dan jangan mengambil rincian survey untuk mengisi kartu ini.
- Gunakan empat status, termasuk case semua nol. Tangani `other_active` sesuai definisi tanpa mengubah status tiket.
- Gunakan bucket minggu dinamis; verifikasi bulan dengan 5/6 minggu dan tahun penuh.
- Gunakan endpoint tasks untuk pagination, dan metric/status/week_start untuk drill-down. Klik detail tetap memakai layar tiket existing.
- Verifikasi akun teknisi A tidak dapat melihat data teknisi B; response 401/403/422 dan empty state tertangani.

## 7. Validasi backend

Feature tests: `php artisan test --compact --filter=TechnicianDashboardTest`. Pengujian mencakup auth/role/permission, validasi filter, isolasi kepemilikan, soft delete, progress turunan, periode Closed, stop SLA v3, bucket mingguan, visit terbaru/current cycle, phone-call, pagination, konsistensi count to-do dengan daftar, dan pembatasan rating.

Smoke test MySQL lokal juga telah dijalankan secara read-only untuk summary dan semua metric drill-down. Tidak ada perubahan schema atau FE yang diperlukan oleh endpoint ini; integrasi UI dikerjakan tim FE.
