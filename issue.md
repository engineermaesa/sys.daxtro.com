# Improve : Filtering Range Date For Sales

## Tujuan
Menambahkan **filtering range date** menggunakan **Flatpickr mode `range`** khusus untuk:

- Backend: `LeadSummaryController::grid()`
- Frontend:
  - `resources/views/pages/dashboard/sales/filtering.blade.php`
  - `resources/views/pages/dashboard/sales/personal-kpi.blade.php`

Filter akan mengirim:

- `start_date_grid`
- `end_date_grid`

dengan pengecekan di backend:

- `$request->filled('start_date_grid')`
- `$request->filled('end_date_grid')`

---

## Scope Implementasi
1. **Backend (`LeadSummaryController::grid`)**
   - Tambahkan pembacaan parameter `start_date_grid` dan `end_date_grid`.
   - Jika keduanya terisi, pakai sebagai window query (`startOfDay` dan `endOfDay`).
   - Jika tidak terisi, fallback ke behavior default saat ini (bulan berjalan).
   - Terapkan period tersebut ke query KPI di method `grid()`.

2. **Frontend (`sales/filtering.blade.php`)**
   - Tambahkan UI date range dengan template di bawah.
   - Inisialisasi Flatpickr mode `range`.
   - Simpan state filter date untuk dipakai saat load KPI.
   - Tombol **Reset Filter** wajib meng-clear seluruh date filter (state + input + label).

3. **Frontend (`sales/personal-kpi.blade.php`)**
   - Saat fetch `/api/leads/grid`, kirim query param `start_date_grid` dan `end_date_grid` jika ada.
   - Trigger reload KPI saat date di-apply/reset.

---

## Template UI (Date Filter)
```blade
{{-- DATES --}}
<div
class="border-r border-r-[#CFD5DC] cursor-pointer w-full relative grid grid-cols-1 items-center h-full">

    {{-- TOGGLE --}}
    <div id="openDateDropdown" class="flex justify-center items-center gap-2">
        <p id="dateLabel" class="font-medium text-black">Date</p>
        <i id="iconDate" class="fas fa-chevron-down transition-transform duration-300 text-black" style="font-size: 12px;"></i>
    </div>

    {{-- DATE DROPDOWN --}}
    <div id="dateDropdown"
        class="absolute top-full left-0 mt-2 bg-white rounded-lg shadow-xl w-[350px] p-4 z-50 opacity-0 scale-95 pointer-events-none transition-all duration-200 ease-out origin-top overflow-visible">

        <h3 class="font-semibold mb-2">Select Date Range</h3>

            <div class="flex justify-center items-center">
            <input type="text" id="source-date-range" class="shadow-none w-full" placeholder="Select date range">
            </div>

        <div class="flex justify-end gap-2 mt-3">

            <button id="cancelDate" class="px-3 py-1 text-[#303030]">
                Cancel
            </button>

            <button id="applyDate"
                class="px-3 py-1 bg-[#115640] text-white rounded-lg cursor-pointer">
                Apply
            </button>

        </div>
    </div>
</div>
```

---

## Catatan Cleanup Script Lama (Wajib)
Di `resources/views/pages/dashboard/sales/filtering.blade.php` saat ini masih ada script/penamaan legacy dari role lain (branch manager/super admin), misalnya helper yang tidak relevan.

Perlu dilakukan:

1. Hapus script yang tidak dipakai untuk flow Sales.
2. Rapikan function supaya fokus ke filter Sales saja.
3. Pastikan reset benar-benar clear date filter:
   - clear nilai input flatpickr
   - reset label ke `Date`
   - kosongkan state `start_date_grid` & `end_date_grid`
   - trigger reload KPI tanpa param date

---

## Tahapan Implementasi
1. **Audit**
   - Baca `LeadSummaryController::grid()` dan tandai semua query yang pakai period tanggal.
   - Baca flow fetch KPI di `personal-kpi.blade.php`.
   - Audit script lama di `sales/filtering.blade.php` (identifikasi mana yang obsolete).

2. **Refactor Backend**
   - Tambah handling `start_date_grid`/`end_date_grid`.
   - Buat period terpusat (`periodStart`, `periodEnd`) dari filter atau fallback default.
   - Gunakan period terpusat ke semua query KPI terkait.

3. **Implement Date UI**
   - Sisipkan template date dropdown di `sales/filtering.blade.php`.
   - Init Flatpickr `mode: 'range'`.
   - Apply: set state date + update label + broadcast refresh.
   - Cancel: tutup dropdown tanpa mengubah state.

4. **Integrasi Request KPI**
   - Update `loadDashboardGrid()` di `personal-kpi.blade.php`.
   - Tambahkan `start_date_grid` dan `end_date_grid` ke URL query jika state date terisi.

5. **Reset Behavior**
   - Tombol reset harus clear semua date state + UI + param request.
   - Setelah reset, KPI reload ke default period.

6. **Testing**
   - Tanpa filter date: hasil tetap default.
   - Dengan date range valid: KPI berubah sesuai periode.
   - Start > End: backend handle aman (swap atau normalisasi).
   - Reset: date filter bersih total dan data kembali default.

---

## Acceptance Criteria
- Sales bisa pilih range date via Flatpickr.
- Request `/api/leads/grid` mengirim `start_date_grid` dan `end_date_grid` saat filter terisi.
- `LeadSummaryController::grid()` memproses range date hanya saat dua parameter tersedia.
- Reset filter menghapus seluruh state dan tampilan date filter.
- Script lama yang tidak relevan di `sales/filtering.blade.php` sudah dibersihkan.
