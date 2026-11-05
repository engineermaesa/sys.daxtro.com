# Integration Contract - Aftersales Dashboards

Kontrak backend untuk integrasi dashboard Aftersales oleh tim FE. Dokumen ini mencakup [Technician Dashboard](#technician-dashboard) dan [Admin Dashboard](#admin-dashboard). Endpoint, role, bentuk response, dan aturan perhitungan dibedakan pada masing-masing bagian.

## Technician Dashboard

Kontrak backend untuk slicing **My Dashboard** dari commit `3ba9f07`. Scope: Overall rating, My tickets, SLA on time, Today Task, Closed tickets, To-do, dan Closed per week. Tim FE menghubungkan slicing ke endpoint di bawah. Implementasi ini tidak mengubah view, JavaScript, atau CSS dashboard.

Baseline bisnis: PRD v2.3 bab 7 dan W3; aturan workflow, survey, dan SLA v3 mengikuti [kontrak Aftersales v3.0](../aftersales-v3.0/integrate-contract.md). Definisi tabel sudah dicocokkan dengan `local_daxx (9).sql` serta migration repository. Dokumen ini khusus dashboard technician, bukan pengganti kontrak ticket detail atau dashboard supervisor.

### 1. Setup, autentikasi, dan akses

- Gunakan database development/integrasi dengan seluruh migration repository terpasang. Jalankan `php artisan migrate` pada environment tersebut sesuai proses deployment tim. Jangan import dump development ke staging/prod.
- Penambahan endpoint ini tidak membutuhkan tabel baru. Migration perbaikan role Technician sebelumnya tetap diperlukan bila database mempunyai role Technician dengan `code` kosong.
- Login menggunakan mekanisme session aplikasi. Kirim cookie session dan header `Accept: application/json` pada semua request. GET tidak membutuhkan token CSRF; aksi POST/PUT pada tiket tetap mengikuti kontrak existing.
- Role wajib berkode `technician`, dengan permission `dashboard` dan `aftersales.tickets.view-own`.
- Semua query dibatasi `tickets.assigned_technician_id = user yang login`. Tidak ada selector teknisi. Parameter `user_id`, `technician_id`, dan `assigned_technician_id` dengan nilai terisi ditolak **422**.
- Role Admin, SPV, Head, dan Sales mendapat **403** pada endpoint self-service ini.
- Token survey, jawaban, review note, identitas responden, score per tiket/aspek, dan jumlah respons survey tidak dikirim. Nama customer pada daftar tugas/tiket hanya berasal dari tiket milik teknisi tersebut.

### 2. Daftar endpoint dan filter

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

#### Cakupan Period

| Panel | Mengikuti Period? | Acuan |
| --- | --- | --- |
| Overall rating | Tidak | Seluruh respons valid pada tiket yang saat ini ditugaskan ke teknisi |
| My tickets | Tidak | Snapshot progress tiket open saat request |
| SLA on time | Ya | Tiket yang saat ini Closed dengan waktu log Closed terakhir di dalam Period |
| Today Task | Tidak | Jadwal terbaru hari ini di Jakarta |
| Closed tickets / Closed per week | Ya | Sama dengan SLA, dikelompokkan berdasarkan tanggal Closed |
| To-do | Tidak | Antrian saat ini; Upcoming Recoming sampai 7 hari ke depan |

Perubahan assignment memengaruhi pemilik metrik karena schema tidak menyimpan snapshot pemilik KPI historis. Tiket soft-deleted atau `is_deleted = 1` tidak dihitung. Log/visit/Recoming/link/survey yang dihapus juga diabaikan. Tidak ada backfill progress atau penulisan data dalam endpoint GET ini.

### 3. Ringkasan response dan pemetaan panel

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

#### 3.1 Overall rating

Contoh: `"rating": { "score": 2.7, "max": 3 }`.

- Hanya respons current yang review-nya `normal` atau `reviewed` yang dapat dihitung. Respons current `needs_review`/`rejected` tidak menyebabkan fallback ke respons lama.
- Legacy: current per tiket, sumber `link` saja, menggunakan rerata score per respons. v3: current per siklus menurut `filled_at DESC, id DESC`; sumber `link`, atau `admin` yang sudah `reviewed`; rerata berbobot jumlah jawaban rating, sehingga site/phone dengan jumlah pertanyaan berbeda mengikuti aturan existing.
- Template menggunakan `ticket_satisfaction_answers` bertipe `rating`; respons tanpa template memakai kolom aspek legacy. v3 membutuhkan `cycle_id` dan `filled_at` terisi.
- Angka dibulatkan satu desimal. `null` berarti belum ada score yang dapat dihitung: tampilkan `—`, bukan `0 / 3`.
- Slicing berisi dummy `Excellent` dan `24 responses`. Backend tidak memberi kategori label baru atau jumlah respons. Jangan mempertahankan teks dummy itu; pembatasan tampilan teknisi mengikuti PRD: satu Overall Rating agregat, read-only.

#### 3.2 My tickets

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

#### 3.3 SLA on time dan target

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

#### 3.4 Today Task

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

#### 3.5 Closed tickets dan Closed per week

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

#### 3.6 To-do

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

### 4. Pagination dan bentuk row drill-down

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

### 5. Empty, loading, dan error states

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

### 6. Checklist handoff FE

- Ganti objek dummy `techData` dengan response API; sesuaikan renderer pada field kontrak ini, bukan menyimpan angka dummy 2.7/12/92/34/10 atau Jan–Jun.
- Hubungkan filter Period ke summary; Today/To-do/My tickets/Overall rating tetap snapshot sesuai aturan, Closed/SLA mengikuti Period.
- Gunakan `rating.score` dan `rating.max`; hilangkan jumlah respons dummy dan jangan mengambil rincian survey untuk mengisi kartu ini.
- Gunakan empat status, termasuk case semua nol. Tangani `other_active` sesuai definisi tanpa mengubah status tiket.
- Gunakan bucket minggu dinamis; verifikasi bulan dengan 5/6 minggu dan tahun penuh.
- Gunakan endpoint tasks untuk pagination, dan metric/status/week_start untuk drill-down. Klik detail tetap memakai layar tiket existing.
- Verifikasi akun teknisi A tidak dapat melihat data teknisi B; response 401/403/422 dan empty state tertangani.

### 7. Validasi backend

Feature tests: `php artisan test --compact --filter=TechnicianDashboardTest`. Pengujian mencakup auth/role/permission, validasi filter, isolasi kepemilikan, soft delete, progress turunan, periode Closed, stop SLA v3, bucket mingguan, visit terbaru/current cycle, phone-call, pagination, konsistensi count to-do dengan daftar, dan pembatasan rating.

Smoke test MySQL lokal juga telah dijalankan secara read-only untuk summary dan semua metric drill-down. Tidak ada perubahan schema atau FE yang diperlukan oleh endpoint ini; integrasi UI dikerjakan tim FE.

## Admin Dashboard

Kontrak BE → FE untuk **Admin Dashboard** pada slicing: Open tickets by progress, SLA of open tickets, Visits next 3 days, Follow-up, Setup and data hygiene, dan Technician workload. Seluruh endpoint di dokumen ini sudah tersedia di backend. Tim FE menghubungkan renderer slicing ke response; perubahan BE ini tidak mengubah Blade, CSS, atau JavaScript dashboard.

Baseline: PRD v2.3 bab 6/W2, workflow Aftersales v3 yang sudah berjalan, serta definisi tabel dan relasi `local_daxx (9).sql`. Untuk aksi dokumen/survey/Close tetap gunakan [kontrak v3.0](../aftersales-v3.0/integrate-contract.md). Kontrak technician tersedia pada [bagian Technician Dashboard](#technician-dashboard) dalam dokumen yang sama.

### 1. Endpoint, autentikasi, dan scope

Base: `/api/dashboard/aftersales/admin`.

| Method | Endpoint | Tujuan |
| --- | --- | --- |
| GET | `/api/dashboard/aftersales/admin` | Ringkasan seluruh panel; workload 20 teknisi pertama |
| GET | `/api/dashboard/aftersales/admin/tickets` | Daftar hasil klik bar progress/SLA/follow-up atau tanggal jadwal |
| GET | `/api/dashboard/aftersales/admin/hygiene` | Daftar tiket/customer/mesin/sparepart yang perlu perbaikan |
| GET | `/api/dashboard/aftersales/admin/technicians` | Workload teknisi lengkap dengan pagination |

Gunakan session login aplikasi, cookie session, dan `Accept: application/json`. GET tidak membutuhkan token CSRF. Tidak ada selector role atau user untuk mengganti identitas peminta.

Akses memerlukan role `after_sales`, `supervisor_after_sales`, `head_after_sales`, atau `super_admin`, permission `dashboard`, dan hak daftar tiket global atau scope SPV sesuai `TicketDocuments`. Nama role saja tidak cukup. User Technician/Sales mendapat 403.

- Admin/Head yang memiliki `tickets.manage` atau `tickets.view-all` memperoleh scope `all` sesuai permission existing.
- SPV menggunakan scope `supervisor`: seluruh agregat/list tiket dibatasi `tickets.supervisor_id = user login`. Customer, mesin, dan sparepart hygiene hanya mencakup record yang terkait tiket dalam scope tersebut; workload hanya teknisi yang terkait tiket scope, dengan jumlah aktif dari tiket scope.
- Parameter `technician_id` pada daftar tiket hanya mempersempit hasil yang sudah diotorisasi; tidak memperluas scope.
- Response tidak mengirim jawaban survey, score, review note, token survey, email/nomor telepon asli, atau credential. Data responden yang diperlukan untuk menentukan antrian hanya dipakai secara internal.

Tidak ada filter Period pada slicing Admin. Semua antrian adalah **snapshot saat ini**. `period` bernilai terisi ditolak 422 agar tidak disalahartikan sebagai filter historis. Semua waktu bisnis menggunakan **Asia/Jakarta**; timestamp response ISO 8601 dengan offset `+07:00`.

### 2. Ringkasan response

Summary 200 memakai envelope `{ "data": { ... } }`. Struktur berikut bersifat pemetaan field, bukan angka dummy yang harus dipertahankan:

| Field | Bentuk | Panel |
| --- | --- | --- |
| `timezone` | `"Asia/Jakarta"` | Zona waktu bisnis |
| `generated_at` | ISO datetime | Waktu ringkasan dibuat |
| `scope` | `"all"` atau `"supervisor"` | Cakupan data yang diizinkan |
| `progress` | `{ total_open, recoming, items: [{ key, label, count }] }` | Open tickets by progress |
| `sla` | `{ on_track, at_risk, overdue, not_configured, total_open, classified_open }` | SLA of open tickets |
| `visits` | `{ start, end, days: [{ date, label, visits, recomings, total, phone_calls }] }` | Visits next 3 days dan empat kartu harian |
| `follow_up` | `[{ key, label, count }]` | Enam bar Follow-up |
| `hygiene` | `[{ key, label, entity, count }]` | Empat bar Setup and data hygiene |
| `technicians` | `{ total, has_more, max_active, items: [{ id, name, rank, active_tickets, status }] }` | Technician workload |

Semua count integer >= 0. Array progress/follow-up/hygiene dan empat tanggal selalu diberikan walaupun count nol. `items` workload dapat kosong. Response tidak menjamin satu transaksi snapshot lintas seluruh query; perubahan tiket setelah summary dapat membuat hasil drill-down berikutnya berbeda.

#### 2.1 Open tickets by progress

`progress.items` selalu berurutan:

| Key | Label FE | Definisi |
| --- | --- | --- |
| `published` | Published | Belum ada assignment/progress lanjutan |
| `assigned` | Assigned | Sudah ditugaskan, belum mencapai floor On Site/progress lanjutan |
| `on_site` | On Site | Log On Site atau floor otomatis karena hari visit terbaru sudah tiba |
| `repair` | Report | Key backend tetap `repair`; label Report mengikuti slicing |
| `waiting_sparepart` | Waiting Sparepart | Progress menunggu sparepart |
| `documentation_published` | Docs Published | Dokumentasi dipublikasikan |
| `satisfaction_submitted` | Survey Submitted | Survey submitted |
| `happy_call_completed` | Happy Call Completed | Happy Call sudah dicatat |

Progress dihitung dari log aktif terakhir menurut ID. Assignment dan visit terbaru memberikan floor otomatis sesuai model tiket; log Repair/Waiting/Documentation/Survey/Happy Call/Closed/Recoming tetap menang. Visit phone-call v3 tidak memberi floor On Site dari visit lama.

- `total_open` mencakup semua tiket dalam scope yang progress **saat ini** bukan Closed.
- `recoming` dikirim terpisah karena slicing hanya memiliki delapan bar. `sum(items[].count) + recoming = total_open`.
- Tiket yang pernah Closed lalu Recoming kembali menjadi tiket open. Tiket soft-deleted atau `is_deleted = 1` tidak masuk angka mana pun.
- Bar Published boleh memakai warna peringatan sesuai slicing; warna bukan field/status bisnis baru.

Klik bar: `GET /api/dashboard/aftersales/admin/tickets?queue=progress&progress=repair`. Untuk semua open: `?queue=all`. Untuk Recoming: `?queue=progress&progress=recoming`.

#### 2.2 SLA of open tickets

Contoh response:

```json
{
  "on_track": 3,
  "at_risk": 2,
  "overdue": 1,
  "not_configured": 1,
  "total_open": 7,
  "classified_open": 6
}
```

| Bucket | Kondisi |
| --- | --- |
| `on_track` | SLA berjalan dengan sisa waktu >= 24 jam; atau SLA v3 sudah berhenti tepat waktu |
| `at_risk` | SLA berjalan dengan sisa waktu >= 0 dan **< 24 jam** |
| `overdue` | SLA berjalan dengan due < sekarang; atau SLA v3 sudah berhenti setelah deadline |
| `not_configured` | Deadline belum tersedia, atau SLA v3 belum mempunyai waktu mulai yang diperlukan |

- Tepat 24 jam masih On track; tepat pada deadline masih At risk, baru Overdue setelah deadline terlewati.
- Legacy memakai `tickets.sla_due_at`. v3 memakai deadline dan `sla_stopped_at` pada **siklus terkini**, dengan `sla_started_at` atau `started_at` siklus pertama sebagai konfigurasi mulai. Data siklus lama tidak mengisi SLA siklus terkini yang kosong.
- SLA v3 yang sudah stopped tidak terus dibandingkan dengan waktu sekarang. Stopped tepat waktu tetap On track; stopped terlambat masuk Overdue. Status tiketnya tetap open sampai Closed dicatat.
- Donut slicing: series `[on_track, at_risk, overdue]`; angka tengah dan denominator legenda memakai **`classified_open`**. Dengan demikian jumlah tiga segmen selalu cocok dengan angka tengah.
- `not_configured` tidak otomatis dianggap Overdue/On track. Tampilkan keterangannya bila > 0; `classified_open + not_configured = total_open`.
- Jika classified_open nol, legenda 0%, hindari NaN/Infinity.

Klik segmen: `GET /api/dashboard/aftersales/admin/tickets?queue=sla&sla=at_risk`. Bucket `not_configured` juga dapat dibuka dari keterangan.

#### 2.3 Visits, next 3 days

Mengikuti subtitle dan empat kartu pada gambar: **hari ini, +1, +2, +3**, bukan tiga tanggal total. `visits.start` dan `visits.end` berupa `YYYY-MM-DD`.

```json
{
  "start": "2026-11-05",
  "end": "2026-11-08",
  "days": [
    { "date": "2026-11-05", "label": "Today", "visits": 4, "recomings": 2, "total": 6, "phone_calls": 1 },
    { "date": "2026-11-06", "label": "+1 day", "visits": 0, "recomings": 0, "total": 0, "phone_calls": 0 },
    { "date": "2026-11-07", "label": "+2 days", "visits": 0, "recomings": 0, "total": 0, "phone_calls": 0 },
    { "date": "2026-11-08", "label": "+3 days", "visits": 0, "recomings": 0, "total": 0, "phone_calls": 0 }
  ]
}
```

- Chart Visits = `days[].visits`, chart Recoming = `days[].recomings`; x-axis `days[].label`. Angka utama kartu harian = `days[].total`.
- Hanya tiket open. Maksimal satu jadwal aktif per tiket agar Recoming tidak dihitung dua kali sebagai visit biasa dan Recoming sekaligus.
- Kunjungan biasa memakai visit aktif terbaru menurut `scheduled_at DESC, id DESC`. Jadwal yang sudah dipindah tidak dihitung pada tanggal lama.
- Recoming fisik memakai `planned_visit_at` Recoming terbaru. Untuk v3 harus terkait siklus terkini. Recoming tetap dikategorikan Recoming ketika pekerjaan telah dilanjutkan ke Repair pada siklus tersebut.
- Visit v3 tanpa mapping siklus atau milik siklus lama tidak dianggap visit siklus terkini.
- Phone-call v3 memakai `planned_call_at` dan dikirim sebagai `phone_calls`. Tidak dimasukkan ke kedua garis kunjungan fisik; `total = visits + recomings`. Bila ada phone call, FE dapat memberi keterangan terpisah, tanpa memalsukan kunjungan fisik.

Klik kartu harian: `GET /api/dashboard/aftersales/admin/tickets?queue=visits&date=2026-11-05`. Ini memuat visit fisik biasa dan Recoming fisik. Tambahkan `type=visit`, `type=recoming`, atau `type=phone_call` untuk daftar masing-masing.

#### 2.4 Follow-up

`follow_up` selalu enam item berurutan dengan key stabil berikut. Count adalah jumlah **tiket**, bukan jumlah log/link/respons. Satu tiket dapat berada pada lebih dari satu antrian sehingga jangan menjumlahkan semua count sebagai total distinct tiket.

| Key | Label | Kondisi |
| --- | --- | --- |
| `survey_due` | Survey due | Progress Documentation Published, publikasi aktif sudah **lebih dari 3 hari**, belum ada link yang pernah shared pada scope siklus terkait |
| `link_expired` | Link expired | Link aktif terbaru sudah expired (`expires_at <= now`); link replaced/used/inactive dan link yang dihapus tidak menjadi antrian |
| `need_recoming` | Need Recoms | Respons current valid menyatakan `machine_ok = false`, dan progress bukan Recoming |
| `blocked_closed` | Blocked Closed | Review respons current adalah `needs_review` atau `rejected` |
| `happy_call_due` | Happy Call due | v3: dokumentasi siklus published, respons current valid, belum Happy Call untuk respons tersebut, bukan Recoming. Legacy: happy_call_required, progress Survey Submitted, belum Happy Call pada rangkaian log setelah Recoming terakhir |
| `ready_to_close` | Ready to Close | v3: dokumentasi siklus published, respons valid, Happy Call untuk respons itu selesai, bukan Recoming. Legacy: dokumentasi published pada rangkaian log terkini, Happy Call selesai atau tidak wajib, respons current tidak Needs review/Rejected |

Catatan workflow:

- Respons current v3 dipilih per siklus terkini berdasarkan `filled_at DESC, id DESC`; legacy memakai ID respons terakhir sesuai gate Close legacy.
- Respons valid: `link` dengan review Normal/Reviewed; v3 juga mengizinkan `admin` yang Reviewed dan memiliki mapping siklus/waktu submit. Admin Normal belum valid. Needs review/Rejected tidak fallback ke respons lama.
- Log publikasi/Happy Call v3 harus milik siklus terkini; Happy Call juga harus terkait **ID respons current**. Respons baru membuat Happy Call untuk respons lama tidak memenuhi gate.
- Legacy tidak memakai log publikasi/Happy Call sebelum log Recoming terakhir.
- Happy Call due adalah **antrian pekerjaan yang belum selesai**, tidak hanya yang lewat deadline. Untuk v3 Happy Call tetap wajib sebelum Close meskipun flag legacy happy_call_required bernilai false.
- Need Recoms adalah sinyal follow-up untuk admin, bukan aksi otomatis membuat Recoming. Backend tidak membuat jadwal/log dari GET ini.
- Ready to Close pada dashboard bukan pemberian izin Close kepada Admin/SPV. Aksi tetap mengikuti permission dan gate pada detail tiket; v3 hanya Head dengan `aftersales.tickets.close` yang menutup tiket.

Klik bar: `GET /api/dashboard/aftersales/admin/tickets?queue=survey_due` (ganti nilai queue dengan key lain pada tabel).

#### 2.5 Setup and data hygiene

`hygiene` selalu empat item. Setiap item mengandung `key`, `label`, `entity`, `count`.

| Key | Label | Entity | Kondisi |
| --- | --- | --- | --- |
| `docs_rework` | Docs Rework | `ticket` | Tiket open dengan Service Form atau Agreement aktif yang statusnya Draft dan `unlocked_at` terisi |
| `missing_contact` | No phone or email | `customer` | Customer aktif dengan phone **atau** email null/kosong/whitespace |
| `missing_warranty` | No warranty date | `machine` | Mesin aktif Daxtro (`is_daxtro = 1`, termasuk null untuk data Daxtro legacy) yang `warranty_end` null |
| `spareparts_reorder` | Spareparts REORDER | `sparepart` | Sparepart aktif dengan `min_stock > 0` dan `stock <= min_stock` |

- Slicing berlabel dummy **Spareparts BADREEN**. Schema tidak mempunyai status BADREEN. Key/label kontrak menggunakan **REORDER** sesuai aturan stok; FE perlu mengganti teks dummy tersebut.
- Draft dokumen pertama tanpa unlocked_at bukan Docs Rework. Dua dokumen rework pada tiket yang sama dihitung satu tiket. Dokumen saved atau soft-deleted tidak dihitung.
- Customer/machine/sparepart dihitung sebagai distinct record master, bukan jumlah tiket yang memakai record tersebut. Dalam scope all, record master tanpa tiket juga dihitung. Scope SPV hanya record terkait tiket yang dapat dilihatnya.
- Mesin non-Daxtro tidak memerlukan warranty Daxtro. Warranty yang sudah lewat tetapi terisi tidak termasuk missing_warranty.
- Batas min_stock 0 tidak membanjiri antrian reorder; ini mengikuti usulan ambang pada PRD dashboard.

Klik bar: `GET /api/dashboard/aftersales/admin/hygiene?queue=missing_contact`. Response membawa `entity` pada level teratas untuk menentukan layar tujuan.

#### 2.6 Technician workload

```json
{
  "total": 2,
  "has_more": false,
  "max_active": 6,
  "items": [
    { "id": 10, "name": "Example Technician A", "rank": 1, "active_tickets": 6, "status": "on_duty" },
    { "id": 11, "name": "Example Technician B", "rank": 2, "active_tickets": 0, "status": "standby" }
  ]
}
```

- Roster adalah user aktif dengan role berkode `technician` yang belum dihapus. Profil optional; teknisi yang belum punya ticket tetap tampil dalam scope all.
- Workload adalah jumlah tiket yang **saat ini** open dan assigned ke teknisi. Tiket pernah Closed lalu Recoming dihitung aktif kembali.
- Status turunan: `active_tickets > 0` → `on_duty` (label On duty), nol → `standby` (label Standby). Status tidak disimpan ke DB. Dummy Standby dengan dua tiket aktif pada gambar tidak menjadi aturan backend.
- Urutan `active_tickets DESC, users.id ASC`, rank 1-based berlanjut antar halaman. Ini urutan workload, bukan peringkat kualitas/rating.
- Bar = `active_tickets / max_active * 100`. Gunakan `max_active` summary sebagai acuan global agar skala tidak berubah saat scroll halaman selanjutnya; jika nol, lebar 0%.
- Summary memuat 20 pertama, `total` jumlah teknisi, `has_more` untuk load more. Panggil `/technicians?page=2&per_page=20` untuk melanjutkan; `page` pada summary tidak menggeser preview.
- Klik teknisi: `/tickets?queue=all&technician_id=10`.

### 3. Parameter daftar dan pagination

| Parameter | Endpoint | Aturan |
| --- | --- | --- |
| `page` | Semua list | Integer >= 1, default 1 |
| `per_page` | Semua list | Integer 1–100, default 20 |
| `queue` | `/tickets`, wajib | `all`, `progress`, `sla`, `visits`, `docs_rework`, atau salah satu key Follow-up |
| `progress` | `/tickets?queue=progress`, wajib | Salah satu delapan progress pada §2.1, atau `recoming` |
| `sla` | `/tickets?queue=sla`, wajib | `on_track`, `at_risk`, `overdue`, `not_configured` |
| `date` | `/tickets?queue=visits`, wajib | `YYYY-MM-DD`, tanggal lokal Jakarta |
| `type` | `/tickets?queue=visits`, opsional | `visit`, `recoming`, `phone_call`; default dua jenis kunjungan fisik |
| `technician_id` | `/tickets`, opsional | ID user teknisi integer >= 1; hanya mempersempit hasil. ID tanpa tiket menghasilkan total 0 |
| `queue` | `/hygiene`, wajib | Salah satu empat key Hygiene |

`progress`, `sla`, `date`, dan `type` hanya dipakai oleh queue yang terkait. `/tickets` dan `/hygiene` diurutkan ID descending; `/technicians` memakai urutan workload. Semua list memakai paginator Laravel:

```json
{
  "current_page": 1,
  "data": [],
  "first_page_url": "https://example.test/api/dashboard/aftersales/admin/tickets?queue=all&page=1",
  "from": null,
  "last_page": 1,
  "last_page_url": "https://example.test/api/dashboard/aftersales/admin/tickets?queue=all&page=1",
  "links": [],
  "next_page_url": null,
  "path": "https://example.test/api/dashboard/aftersales/admin/tickets",
  "per_page": 20,
  "prev_page_url": null,
  "to": null,
  "total": 0
}
```

Link dan isi array `links` di atas hanya ilustrasi. Backend mempertahankan query filter pada URL paginator. FE juga boleh menyimpan filter dan mengirim ulang dengan page baru. Halaman di luar range menghasilkan `data: []`, bukan error. `/hygiene` menambahkan field top-level `entity`.

### 4. Bentuk row dan navigasi detail

#### Ticket row

```json
{
  "id": 101,
  "ticket_code": "EXAMPLE-AF-101",
  "priority": "high",
  "progress": "repair",
  "customer": { "id": 201, "name": "Example Customer" },
  "machine": { "id": 301, "name": "TD 150" },
  "technician": { "id": 10, "name": "Example Technician" },
  "sla": { "bucket": "at_risk", "due_at": "2026-11-06T09:00:00+07:00", "stopped_at": null }
}
```

Row jadwal menambah `scheduled_at` (ISO datetime) dan `schedule_type` (`visit`, `recoming`, `phone_call`). Technician dapat null untuk Published. Nama customer/mesin/teknisi dapat null bila master hilang/dihapus; render fallback `—`. Nilai due/stopped dapat null. Tidak ada field jawaban survey pada row.

Gunakan ID untuk membuka ticket detail existing `/aftersales/tickets/{id}`, route `aftersales.pages.tickets.show`. Aksi follow-up tetap mengambil action flags/state dari detail. Tombol **Supervisor view** pada header harus mengikuti role/permission existing; adanya endpoint Admin tidak memberikan akses KPI Supervisor kepada role After Sales.

#### Hygiene row

| Entity | Field row | Navigasi |
| --- | --- | --- |
| `ticket` | Sama dengan ticket row | Detail tiket, lalu dokumen rework |
| `customer` | `id`, `name`, `phone_missing`, `email_missing` | Edit customer memakai ID; nomor/email asli diambil dari layar berizin existing |
| `machine` | `id`, `customer_id`, `machine_name`, `warranty_end: null` | Mesin pada customer terkait |
| `sparepart` | `id`, `name`, `part_number`, `stock`, `min_stock` | Sparepart detail/edit sesuai izin |

Endpoint dashboard adalah read-only. Count hygiene tidak memberikan hak edit master kepada SPV/Head; layar dan endpoint edit tetap menerapkan permission aslinya.

### 5. Sumber data dan keputusan implementasi

| Panel | Sumber utama |
| --- | --- |
| Progress/open/SLA | `tickets`, log terakhir, visit terbaru, siklus terkini, Recoming terbaru |
| Visits | `ticket_visits.scheduled_at`, `ticket_recomings.planned_visit_at` / `planned_call_at`, mapping cycle |
| Follow-up | Publikasi/log aktif pada cycle/rangkaian legacy, link terbaru/shared_at, respons current/review/machine_ok, Happy Call yang sesuai response |
| Docs Rework | `ticket_service_forms` / `ticket_agreements`, status Draft + unlocked_at |
| Contact / warranty | `service_customers`, `service_customer_products` |
| Reorder | `ref_spareparts.stock`, `min_stock` |
| Workload | `users`, `user_roles`, agregat tiket open |

Log/link/visit/Recoming/survey yang soft-deleted atau ditandai is_deleted diabaikan. Tabel yang memang tidak mempunyai is_deleted (user_roles, users, Agreement/Form, cycles) tidak diquery memakai kolom fiktif. Tidak ada perubahan schema, seed demo, atau backfill dari request dashboard ini.

PRD v2.3 memiliki ide lain seperti Deals Review, warranty expiring, dan PM due. Endpoint ini mengikuti **panel yang ditampilkan pada gambar**, sehingga tidak menambahkan card tersebut ke scope implementasi sekarang.

### 6. Error, loading, dan integrasi renderer

- **401**: session belum login/expired. Gunakan alur login existing.
- **403**: role atau permission tidak sesuai. Jangan fallback ke endpoint role lain atau data dummy.
- **422**: validasi query gagal; bentuk Laravel `{ "message": "...", "errors": { "queue": ["..."] } }`. Contoh: queue tidak dikenal, sla/date/progress wajib belum diisi, period terisi, atau per_page > 100.
- Kegagalan jaringan/5xx: tampilkan retry/error dengan pola aplikasi. Hindari mempertahankan angka dummy seolah data live.
- Saat refresh/load more, abaikan response lama bila sudah ada request yang lebih baru. Reset renderer atau tampilkan loading sesuai pola FE, tanpa mengubah status tiket melalui GET.
- Escape nama master/kode tiket sebelum dirender atau gunakan textContent.

Contoh pemanggilan summary:

```js
const response = await fetch('/api/dashboard/aftersales/admin', {
  credentials: 'same-origin',
  headers: { Accept: 'application/json' },
  signal,
});
if (!response.ok) throw new Error(`Admin dashboard request failed: ${response.status}`);
const { data } = await response.json();

// Adapter ke renderer slicing:
const progress = data.progress.items.map(row => [
  row.label, row.count, row.key === 'published' ? 'warn' : undefined,
]);
const followUp = data.follow_up.map(row => [row.label, row.count]);
const hygiene = data.hygiene.map(row => [row.label, row.count]);
// Simpan key pada event klik; jangan memakai label terpotong pada chart sebagai filter.
```

### 7. Checklist handoff dan verifikasi

- Ganti seluruh objek dummy `adminData`: 8 bar progress, donut SLA/legend, dua series kunjungan dan empat day stats, 6 Follow-up, 4 Hygiene, serta workload.
- Gunakan key backend untuk event klik, label hanya untuk tampilan. Sediakan semua state nol/tidak ada data.
- Jangan paksa total donut menjadi total_open jika not_configured > 0; jangan hitung phone-call sebagai visit fisik.
- Jangan memakai status Standby dari dummy bila active_tickets > 0. Render rank dan scale workload dari backend.
- Test sebagai Admin dan SPV; filter/click/load more harus tetap berada pada scope yang sama. Jangan membuka aksi Close/edit master tanpa izin existing.
- Pengujian backend: `php artisan test --compact --filter=AdminDashboardTest`. Mencakup izin, validasi, progress turunan, soft delete, reopened ticket, batas SLA, stop SLA v3, deduplikasi/reschedule visit, phone-call, cycle isolation, follow-up current response, count/list hygiene, pagination workload, dan scope SPV.
- Query summary serta seluruh list/serializer juga diverifikasi pada MySQL lokal secara read-only. Handoff ini belum mengintegrasikan FE dan tidak membuat commit otomatis.
