# Issue Plan: Compare Date untuk Dashboard Branch Manager

## Ringkasan

Dashboard Branch Manager membutuhkan fitur `compare date` seperti yang sudah ada pada:

- `resources/views/pages/dashboard/super-admin/filtering.blade.php`

Namun implementasinya harus khusus untuk Branch Manager, artinya:

- branch tetap otomatis mengikuti `branch_id` user BM yang login,
- compare hanya membaca data dalam branch BM tersebut,
- hasil compare ditampilkan sebagai info kecil di bawah summary card yang relevan,
- compare tidak ditampilkan untuk:
  - Top 10 Provinces
  - chart-chart yang menggunakan ApexCharts

## Tujuan

Menambahkan fitur compare date di dashboard Branch Manager agar user dapat membandingkan dua snapshot tanggal:

- `base`
- `compare`
- `delta`

Hasil compare ini nantinya akan muncul di bawah setiap summary card yang mendukung compare.

## Referensi Implementasi

### UI Filter Compare

Referensi utama:

- `resources/views/pages/dashboard/super-admin/filtering.blade.php`

### Pola Compare di Backend

Referensi utama:

- `app/Http/Controllers/Dashboard/DashSummaryController.php`

Endpoint/method compare yang relevan dari referensi:

- `grid()`
- `leadVolume()`
- `AgentSummary()`
- helper `formatCompareMetric(...)`

## Kondisi Saat Ini

### Frontend BM

File:

- `resources/views/pages/dashboard/branch-manager/filtering.blade.php`

Kondisi sekarang:

- sudah punya filter `sales`
- sudah punya filter `date range`
- belum punya filter `compare date`
- helper filter BM baru mengirim:
  - `branch_id`
  - `sales_id`
  - `start_date_grid`
  - `end_date_grid`

### Backend BM

File:

- `app/Http/Controllers/Dashboard/BMSummaryController.php`

Kondisi sekarang:

- `AgentSummary()` sudah mendukung `compare_start_date` dan `compare_end_date`
- `grid()` belum mirror compare seperti `DashSummaryController::grid()`
- `leadVolume()` belum mirror compare coverage seperti `DashSummaryController::leadVolume()`

### View BM yang Terkena Dampak

1. `resources/views/pages/dashboard/branch-manager/filtering.blade.php`
   Untuk menambahkan UI dan helper compare date.

2. `resources/views/pages/dashboard/branch-manager/sales-kpi.blade.php`
   Untuk menampilkan info compare kecil di bawah card Sales KPI.

3. `resources/views/pages/dashboard/branch-manager/agent.blade.php`
   Compare caption sudah ada, tetapi masih bergantung pada compare filter global BM.

4. `resources/views/pages/dashboard/branch-manager/regional-reach.blade.php`
   Untuk compare pada `Province Coverage` dan `City Coverage`, tetapi bukan untuk `Top 10 Provinces` dan bukan untuk chart donut ApexCharts.

## Scope Implementasi

### Yang Masuk Scope

1. Menambahkan UI compare date di filter BM.
2. Menambahkan state compare di helper filter BM.
3. Mengirim `compare_start_date` dan `compare_end_date` ke endpoint BM yang relevan.
4. Mengimplementasikan compare backend di `BMSummaryController` untuk:
   - `grid()`
   - `leadVolume()`
   - memastikan `AgentSummary()` tetap konsisten
5. Menampilkan compare text kecil di bawah summary cards yang relevan.

### Yang Tidak Masuk Scope

1. Tidak menampilkan compare caption di chart ApexCharts.
2. Tidak menampilkan compare caption di `Top 10 Provinces`.
3. Tidak membuka filter branch manual di BM.
4. Tidak mengubah scope akses data BM menjadi lintas branch.

## Alur Feature

1. Branch Manager login ke dashboard.
2. User memilih:
   - sales opsional
   - date range utama opsional
   - compare base date
   - compare date
3. Filter helper BM menyimpan:
   - `compare_start_date`
   - `compare_end_date`
4. Saat komponen dashboard refresh, helper menyisipkan compare params ke request yang mendukung compare.
5. Backend BM menghitung snapshot `base` dan `compare` untuk branch BM yang login.
6. Backend mengembalikan object compare berisi:
   - `base`
   - `compare`
   - `delta`
7. Frontend membaca object compare dan menampilkan info kecil di bawah card summary.
8. Komponen chart dan Top 10 Provinces tetap hanya menampilkan data utamanya tanpa caption compare kecil.

## Tahapan Implementasi

### Tahap 1: Tambah Compare Date di Filtering BM

File:

- `resources/views/pages/dashboard/branch-manager/filtering.blade.php`

Pekerjaan:

1. Salin pola UI compare date dari `super-admin/filtering.blade.php`.
2. Adaptasi nama ID dan state untuk BM.
3. Tambahkan state compare filter, misalnya:
   - `compare_start_date`
   - `compare_end_date`
4. Tambahkan support `withCompareDate` di helper `applySuperAdminGeneralFilterToParams(...)`.
5. Pastikan `reset filter` ikut menghapus compare date.

Output tahap ini:

- BM memiliki compare date picker sendiri.
- Filter global BM mampu mengirim compare params ke komponen yang membutuhkannya.

### Tahap 2: Mirror Compare Logic dari DashSummaryController ke BMSummaryController

File:

- `app/Http/Controllers/Dashboard/BMSummaryController.php`

Pekerjaan:

1. Telaah pola compare pada:
   - `DashSummaryController::grid()`
   - `DashSummaryController::leadVolume()`
   - `DashSummaryController::AgentSummary()`
2. Tambahkan validasi:
   - `compare_start_date`
   - `compare_end_date`
3. Buat snapshot compare pada endpoint BM yang relevan.
4. Gunakan helper `formatCompareMetric(...)` untuk menjaga format data tetap konsisten.
5. Pastikan seluruh compare tetap di-lock ke branch BM yang login.

Output tahap ini:

- `grid()` BM punya compare KPI seperti super-admin versi BM-scope.
- `leadVolume()` BM punya compare coverage seperti super-admin versi BM-scope.
- `AgentSummary()` BM tetap konsisten dengan pola compare global.

### Tahap 3: Render Compare Kecil di Bawah Summary Cards

Files:

- `resources/views/pages/dashboard/branch-manager/sales-kpi.blade.php`
- `resources/views/pages/dashboard/branch-manager/agent.blade.php`
- `resources/views/pages/dashboard/branch-manager/regional-reach.blade.php`

Pekerjaan:

1. Tambahkan placeholder compare kecil di bawah card yang belum punya.
2. Buat helper render compare text di JS per halaman.
3. Format compare minimal menampilkan:
   - delta
   - label tanggal compare
4. Gunakan warna sesuai tone:
   - hijau untuk naik
   - merah untuk turun
   - abu-abu untuk netral

Output tahap ini:

- Sales KPI cards menampilkan compare kecil.
- Agent cards menampilkan compare kecil dari compare global BM.
- Province Coverage dan City Coverage menampilkan compare kecil.

### Tahap 4: Exclude Chart dan Top 10 Provinces dari Compare Caption

Files:

- `resources/views/pages/dashboard/branch-manager/regional-reach.blade.php`
- view lain yang menggunakan ApexCharts

Pekerjaan:

1. Pastikan chart ApexCharts tidak menampilkan text compare kecil di bawah chart.
2. Pastikan `Top 10 Provinces by Lead Volume` tidak memiliki compare caption kecil.
3. Jika compare params ikut terkirim ke endpoint yang sama, UI tetap hanya menampilkan compare pada card summary yang diperbolehkan.

Output tahap ini:

- Compare tetap fokus pada summary cards.
- Visual chart dan top list tidak menjadi ramai atau membingungkan.

### Tahap 5: Verifikasi

Pekerjaan:

1. Login sebagai Branch Manager.
2. Pilih compare base date dan compare date.
3. Pastikan request BM mengirim compare params.
4. Pastikan response backend BM memiliki object compare pada endpoint yang relevan.
5. Pastikan compare kecil tampil di bawah:
   - Sales KPI cards
   - Agent KPI cards
   - Province Coverage
   - City Coverage
6. Pastikan compare kecil tidak tampil pada:
   - Top 10 Provinces
   - chart ApexCharts
7. Pastikan seluruh data tetap scoped ke branch BM yang login.

## Mapping Komponen yang Perlu Compare

### Sales KPI

File:

- `resources/views/pages/dashboard/branch-manager/sales-kpi.blade.php`

Compare kecil di bawah:

- Achievement vs Target Sale
- Achievement vs Target Leads
- Achievement vs Target Visits
- Closed Deal
- Total Active Leads
- Potential Dealing

Backend sumber:

- `BMSummaryController::grid()`

### Agent KPI

File:

- `resources/views/pages/dashboard/branch-manager/agent.blade.php`

Compare kecil di bawah:

- Agent Achievement
- Total Active Agents
- Total Leads Agents

Backend sumber:

- `BMSummaryController::AgentSummary()`

### Regional Reach Coverage

File:

- `resources/views/pages/dashboard/branch-manager/regional-reach.blade.php`

Compare kecil di bawah:

- Province Coverage
- City Coverage

Tidak ditampilkan pada:

- Top 10 Provinces by Lead Volume
- Donut chart ApexCharts

Backend sumber:

- `BMSummaryController::leadVolume()`

## Format Response Compare yang Diharapkan

Setiap metric compare harus memakai format:

```json
{
  "base": 10,
  "compare": 15,
  "delta": 5
}
```

Wrapper compare per endpoint minimal memuat:

```json
{
  "enabled": true,
  "start_date": "2026-04-01",
  "end_date": "2026-04-24",
  "..."
}
```

## Catatan Teknis Penting

1. Branch Manager tidak boleh bisa membandingkan branch lain.
   Walaupun compare params dikirim dari frontend, backend tetap harus memakai `Auth::user()->branch_id`.

2. Compare global BM harus reusable.
   Sekali compare dipilih dari filter BM, komponen yang mendukung compare harus membaca source filter yang sama.

3. Compare caption jangan dicampur ke chart.
   Untuk chart ApexCharts, cukup data utama chart saja agar tampilan tetap bersih.

4. Agent page saat ini sudah paling dekat siap.
   Karena:
   - `AgentSummary()` BM sudah punya compare backend
   - `agent.blade.php` BM sudah punya render compare caption
   Yang kurang adalah wiring compare date dari filter BM global.

## Acceptance Criteria

- `branch-manager/filtering.blade.php` memiliki compare date UI.
- Helper filter BM dapat mengirim `compare_start_date` dan `compare_end_date`.
- `BMSummaryController::grid()` mendukung compare.
- `BMSummaryController::leadVolume()` mendukung compare coverage.
- `BMSummaryController::AgentSummary()` tetap konsisten dengan compare BM global.
- Sales KPI cards menampilkan compare kecil di bawah card.
- Agent KPI cards menampilkan compare kecil di bawah card.
- Province Coverage dan City Coverage menampilkan compare kecil.
- Top 10 Provinces tidak menampilkan compare kecil.
- Chart ApexCharts tidak menampilkan compare kecil.
- Seluruh compare tetap scoped ke branch BM yang login.

## Hasil Akhir yang Diharapkan

Setelah implementasi selesai, dashboard Branch Manager akan memiliki compare date yang:

- konsisten dengan pola super-admin,
- aman secara branch scope,
- informatif di summary cards,
- tetapi tetap bersih karena chart dan Top 10 Provinces tidak dibebani caption compare tambahan.
