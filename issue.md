# Issue: Notifikasi Available Leads untuk Role Sales

## Ringkasan

Ketika sebuah lead baru masuk dengan `status_id = 1` (PUBLISHED / Available), seluruh user dengan role `sales` harus menerima notifikasi database + broadcast secara real-time. `notifiable_id` di tabel `notifications` adalah `id` dari user sales yang menjadi target notifikasi.

---

## Kondisi Codebase Saat Ini

### File yang Sudah Ada (Baru Ditambahkan — Belum Selesai)

| File | Status | Keterangan |
|---|---|---|
| `app/Http/Controllers/NotificationController.php` | Sudah ada | CRUD endpoint notifikasi (index, unread-count, mark-read, mark-all-read) — **sudah lengkap** |
| `app/Notifications/Leads/LeadCreatedNotification.php` | Sudah ada | Notification class untuk lead baru — broadcast ke `PrivateChannel('branch.{branchId}')` |
| `app/Notifications/Leads/LeadActivityNotification.php` | Sudah ada | Notification untuk activity log — dikirim ke `branch_manager` di branch yang sama |
| `app/Notifications/Leads/LeadTrashedNotification.php` | Sudah ada | Notification ketika lead di-trash |
| `app/Observers/LeadActivityLogObserver.php` | Sudah ada | Observer untuk `LeadActivityLog` — mengirim `LeadActivityNotification` ke BM |
| `database/migrations/2026_05_20_113809_create_notifications_table.php` | Sudah ada | Tabel `notifications` standar Laravel (uuid, type, morphs notifiable, data, read_at) |
| `config/broadcasting.php` | Sudah ada | Konfigurasi broadcaster (Pusher / Reverb / null) |
| `resources/js/echo.js` | Sudah ada | Inisialisasi Laravel Echo + Pusher di frontend |
| `routes/channels.php` | Sudah ada | Auth channel `App.Models.User.{id}` dan `branch.{branchId}` (khusus BM) |
| `routes/api.php` | Sudah ada | Route `/notifications` sudah terdaftar |

### Masalah / Gap yang Harus Diselesaikan

1. **`LeadCreatedNotification` salah target** — saat ini broadcast ke `PrivateChannel('branch.{branchId}')` yang hanya diizinkan untuk `branch_manager`. Harus berubah ke channel per-user (`private-App.Models.User.{id}`) agar sales menerima notifikasi.

2. **Tidak ada trigger pengiriman notifikasi ke sales** — `LeadCreatedNotification` sudah ada tapi tidak pernah di-dispatch. Tidak ada Observer/Event yang memanggil `->notify()` ke user sales ketika lead baru PUBLISHED.

3. **`channels.php` tidak mengizinkan sales** — channel `branch.{branchId}` membatasi akses hanya untuk `branch_manager`. Sales perlu akses ke channel `App.Models.User.{id}` (sudah ada, aman).

4. **Sidebar notifikasi hanya muncul untuk `branch_manager`** — kondisi `@if(hasRole(auth()->user(), 'branch_manager'))` di `sidebar.blade.php` harus diperluas ke role `sales`.

5. **User model belum menggunakan `Notifiable` trait** — perlu diverifikasi apakah `App\Models\User` sudah pakai trait `Illuminate\Notifications\Notifiable`.

---

## Titik-titik Kode yang Menjadi Trigger Lead PUBLISHED

Lead dengan `status_id = 1` dibuat melalui tiga jalur:

| Jalur | File | Method |
|---|---|---|
| Form manual (admin/sales) | `app/Http/Controllers/Leads/ColdLeadController.php` | `save()` — baris ~307, set `status_id = LeadStatus::PUBLISHED` |
| Import spreadsheet | `app/Http/Controllers/Leads/ImportLeadController.php` | `store()` — baris ~913/920, status PUBLISHED ketika `nip_sales` kosong |
| Public form / API register | `app/Http/Controllers/Leads/PublicLeadController.php` | `store()` — baris ~36, langsung PUBLISHED |

Notifikasi harus dipicu di ketiga jalur ini **atau** via Model Observer pada `Lead::created` + `Lead::updated` dengan filter `status_id === LeadStatus::PUBLISHED`.

---

## Tahap Implementasi

### Tahap 1 — Verifikasi Prasyarat

**File yang diperiksa:**
- `app/Models/User.php` — pastikan ada `use Notifiable;`
- `.env` — pastikan `BROADCAST_CONNECTION=pusher` atau `reverb` (bukan `null`)
- Jalankan migration jika belum: `php artisan migrate`

**Tidak ada perubahan kode**, hanya verifikasi.

---

### Tahap 2 — Buat Notification Class untuk Available Leads

**File baru:** `app/Notifications/Leads/AvailableLeadNotification.php`

Notification ini dikirim ke setiap user `sales` ketika lead baru masuk dengan `status_id = 1`.

```php
// Spesifikasi
class AvailableLeadNotification extends Notification implements ShouldBroadcast
{
    // Channel: PrivateChannel('App.Models.User.' . $notifiable->id)
    // broadcastAs: 'lead.available'
    // via: ['database', 'broadcast']
    // toDatabase: [type, lead_id, lead_name, company, branch_id, region_name, created_at]
}
```

**Kenapa class baru?** `LeadCreatedNotification` yang sudah ada salah channel target (branch, bukan per-user) dan tidak mengandung data yang relevan untuk sales (misal: nama region).

---

### Tahap 3 — Buat Observer untuk Lead Model

**File baru:** `app/Observers/LeadObserver.php`

Observer ini memantau event `created` dan `updated` pada model `Lead`. Ketika `status_id` adalah `LeadStatus::PUBLISHED`, kirim `AvailableLeadNotification` ke semua user sales yang memiliki akses ke branch lead tersebut.

```php
// Logika di method created() dan updated()
if ($lead->status_id === LeadStatus::PUBLISHED) {
    // Ambil semua sales yang branch_id-nya sama dengan lead->branch_id
    User::whereHas('role', fn($q) => $q->where('code', 'sales'))
        ->where('branch_id', $lead->branch_id)
        ->get()
        ->each->notify(new AvailableLeadNotification($lead));
}

// Untuk updated(): hanya kirim jika status_id BARU saja berubah ke PUBLISHED
// Gunakan $lead->isDirty('status_id') && $lead->status_id === LeadStatus::PUBLISHED
```

**Perhatian import:** notifikasi massal dari `ImportLeadController` dapat membuat banyak notifikasi sekaligus. Pertimbangkan throttle atau kirim satu notifikasi batch jika jumlah lead > threshold.

---

### Tahap 4 — Daftarkan Observer di AppServiceProvider

**File yang diubah:** `app/Providers/AppServiceProvider.php`

Tambahkan di method `boot()`:
```php
use App\Models\Leads\Lead;
use App\Observers\LeadObserver;

Lead::observe(LeadObserver::class);
```

---

### Tahap 5 — Update `routes/channels.php`

**File yang diubah:** `routes/channels.php`

Channel `App.Models.User.{id}` sudah ada dan sudah benar. Pastikan tidak ada pembatasan role — channel ini hanya memvalidasi bahwa `$user->id === $id`, jadi sales sudah bisa masuk.

Tidak perlu tambah channel baru. `AvailableLeadNotification` akan broadcast ke `private-App.Models.User.{id}` — satu channel per user sales.

---

### Tahap 6 — Update Sidebar untuk Role Sales

**File yang diubah:** `resources/views/partials/sidebar.blade.php`

Ubah kondisi tampilan notification bell dari:
```blade
@if(auth()->check() && hasRole(auth()->user(), 'branch_manager'))
```
Menjadi:
```blade
@if(auth()->check() && (hasRole(auth()->user(), 'branch_manager') || hasRole(auth()->user(), 'sales')))
```

UI dropdown notifikasi yang sudah ada bisa digunakan ulang tanpa perubahan struktur.

---

### Tahap 7 — Update Frontend Echo Listener

**File yang diubah:** `resources/views/layouts/app.blade.php` (atau file JS yang memuat Echo)

Tambahkan listener untuk event `lead.available` di channel `App.Models.User.{userId}`:

```javascript
// Sudah ada listener untuk BM (branch channel)
// Tambahkan untuk sales (user private channel)
Echo.private(`App.Models.User.${userId}`)
    .notification((notification) => {
        if (notification.type === 'lead.available') {
            // Increment badge, tambahkan item ke dropdown
            incrementNotifBadge();
            prependNotifItem(notification);
        }
    });
```

`userId` diambil dari Blade: `{{ auth()->id() }}`.

---

### Tahap 8 — Update `LeadCreatedNotification` (Opsional / Cleanup)

**File yang diubah:** `app/Notifications/Leads/LeadCreatedNotification.php`

Class ini saat ini tidak pernah di-dispatch. Dua pilihan:
- **Hapus** jika fungsionalitasnya digantikan sepenuhnya oleh `AvailableLeadNotification`.
- **Pertahankan** untuk kebutuhan lain (misal: notifikasi ke admin ketika lead dibuat dari public form).

Jika dipertahankan, **jangan** broadcast ke `branch.{branchId}` karena channel itu untuk BM, bukan sales.

---

## Ringkasan File yang Tersentuh

### File Baru (2 file)

| File | Keterangan |
|---|---|
| `app/Notifications/Leads/AvailableLeadNotification.php` | Notification class untuk sales ketika lead PUBLISHED |
| `app/Observers/LeadObserver.php` | Observer Lead model, trigger notifikasi saat status = PUBLISHED |

### File yang Diubah (3 file)

| File | Perubahan |
|---|---|
| `app/Providers/AppServiceProvider.php` | Daftarkan `LeadObserver` di `boot()` |
| `resources/views/partials/sidebar.blade.php` | Tampilkan notification bell untuk role `sales` |
| `resources/views/layouts/app.blade.php` | Tambah Echo listener event `lead.available` untuk channel user |

### File yang Tidak Perlu Diubah

| File | Alasan |
|---|---|
| `routes/channels.php` | Channel `App.Models.User.{id}` sudah ada dan benar |
| `routes/api.php` | Route `/notifications` sudah terdaftar |
| `app/Http/Controllers/NotificationController.php` | Sudah lengkap |
| `database/migrations/*_create_notifications_table.php` | Sudah sesuai standar Laravel |
| `config/broadcasting.php` | Hanya perlu `.env` yang benar |

---

## Risiko & Catatan Penting

1. **Notifikasi massal saat import** — Import ratusan leads sekaligus akan trigger ratusan notifikasi. Tambahkan flag `$skipNotification = true` di `ImportLeadController` dan lewatkan via Observer condition, atau gunakan `withoutObservers()` di import lalu kirim satu notifikasi summary setelah selesai.

2. **Queue harus aktif untuk broadcast** — `ShouldBroadcast` butuh queue driver yang bukan `sync` agar tidak memblokir response HTTP. Cek `.env`: `QUEUE_CONNECTION=database` atau `redis`. Jika tetap `sync`, broadcast masih jalan tapi lebih lambat.

3. **BROADCAST_CONNECTION** — Jika di production menggunakan Pusher, pastikan key/secret/cluster sudah diisi. Jika lokal, gunakan Laravel Reverb sebagai alternatif self-hosted.

4. **Scope branch sales** — Sales hanya boleh menerima notifikasi untuk leads di branch-nya sendiri (`users.branch_id = leads.branch_id`). Jangan kirim notifikasi lintas branch.
