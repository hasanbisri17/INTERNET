# Sistem Database Notifications (Bell Icon) 🔔

## 📋 Deskripsi

Sistem notifikasi lengkap yang menampilkan **semua event penting** di aplikasi melalui **icon lonceng** (bell icon) di header admin panel. Notifikasi bersifat **persistent** (tidak hilang) sampai di-read atau dismiss oleh user.

---

## ✅ Notifikasi yang Tersedia

### 1️⃣ **Payment Notifications** 💰

#### A. Pembayaran Diterima
- **Trigger:** Saat payment status berubah ke `paid` atau `confirmed`
- **Title:** "💰 Pembayaran Diterima"
- **Content:** "Pembayaran {invoice_number} dari {customer_name} sebesar Rp {amount} telah dikonfirmasi."
- **Action Button:** "Lihat Detail" → Link ke payment detail
- **Color:** Success (Hijau)
- **Icon:** `heroicon-o-check-circle`

#### B. Tagihan Mendekati Jatuh Tempo
- **Trigger:** Setiap hari jam 08:00 pagi (via scheduler)
- **Condition:** Tagihan pending yang jatuh tempo 2 hari lagi
- **Title:** "⏰ Tagihan Mendekati Jatuh Tempo"
- **Content:** "{count} tagihan akan jatuh tempo dalam 2 hari (Total: Rp {total}). Customer: {list}."
- **Action Button:** "Lihat Tagihan" → Link ke payment list (filtered)
- **Color:** Warning (Kuning)
- **Icon:** `heroicon-o-clock`

#### C. Tagihan Terlambat
- **Trigger:** Setiap hari jam 08:00 pagi (via scheduler)
- **Condition:** Tagihan pending yang melewati due date
- **Title:** "🔴 Tagihan Terlambat"
- **Content:** "{count} tagihan melewati jatuh tempo (Total: Rp {total}). Customer: {list}."
- **Action Button:** "Lihat Tagihan" → Link ke payment list (filtered)
- **Color:** Danger (Merah)
- **Icon:** `heroicon-o-exclamation-circle`

---

### 2️⃣ **Dunning Notifications** ⚠️

#### A. Layanan Ditangguhkan (Suspend)
- **Trigger:** Saat customer di-suspend via dunning system
- **Title:** "⚠️ Layanan Ditangguhkan"
- **Content:** "Layanan {customer_name} ditangguhkan karena tunggakan {days} hari. Invoice: {invoice_number} (Rp {amount})"
- **Action Buttons:**
  - "Lihat Tagihan" → Link ke payment detail
  - "Lihat Customer" → Link ke customer detail
- **Color:** Warning (Kuning)
- **Icon:** `heroicon-o-exclamation-triangle`

#### B. Layanan Diaktifkan Kembali (Unsuspend)
- **Trigger:** Saat customer di-unsuspend setelah bayar
- **Title:** "✅ Layanan Diaktifkan Kembali"
- **Content:** "Layanan {customer_name} telah diaktifkan kembali setelah pembayaran {invoice_number}."
- **Action Button:** "Lihat Detail" → Link ke payment detail
- **Color:** Success (Hijau)
- **Icon:** `heroicon-o-check-badge`

---

### 3️⃣ **WhatsApp Notifications** 📢

#### A. Broadcast WhatsApp Selesai
- **Trigger:** Saat broadcast campaign selesai dikirim
- **Title:** "📢 Broadcast WhatsApp Selesai"
- **Content:** "Campaign '{title}' selesai dikirim. Berhasil: {success}, Gagal: {failed}"
- **Action Button:** "Lihat Detail" → Link ke broadcast campaign detail
- **Color:** Success (Hijau)
- **Icon:** `heroicon-o-megaphone`

#### B. Broadcast WhatsApp Gagal
- **Trigger:** Saat broadcast campaign gagal total
- **Title:** "❌ Broadcast WhatsApp Gagal"
- **Content:** "Campaign '{title}' gagal dikirim ke semua {count} penerima. Silakan cek konfigurasi WhatsApp Gateway."
- **Action Buttons:**
  - "Lihat Detail" → Link ke broadcast campaign detail
  - "Cek Pengaturan" → Link ke WhatsApp settings
- **Color:** Danger (Merah)
- **Icon:** `heroicon-o-x-circle`

---

### 4️⃣ **System Notifications** 📄

#### Generate Tagihan Bulanan
- **Trigger:** Saat command `bills:generate` selesai
- **Title:** "📄 Tagihan Bulanan Dibuat"
- **Content:** "Tagihan bulan {month} berhasil dibuat untuk {count} customer, {skipped} dilewati (sudah ada)."
- **Action Button:** "Lihat Tagihan" → Link ke payment list
- **Color:** Success (Hijau)
- **Icon:** `heroicon-o-document-text`

---

## 🔄 Alur Kerja Notifikasi

### Payment Paid Flow
```
Payment status → 'paid' 
  ↓
PaymentObserver::updated()
  ↓
1. Trigger n8n unsuspend
2. Send WhatsApp to customer
3. Send database notification to all admins ✅
  ↓
Notification muncul di bell icon
```

### Dunning Suspend Flow
```
DunningService::processDunningWithConfig()
  ↓
Customer overdue > threshold
  ↓
1. Trigger n8n suspend
2. Send WhatsApp to customer
3. Log to activity log
4. Send database notification to all admins ✅
  ↓
Notification muncul di bell icon
```

### Broadcast Flow
```
WhatsAppBroadcast::sendBroadcast()
  ↓
Send messages to all recipients
  ↓
Campaign completed
  ↓
1. Flash notification (temporary)
2. Database notification to all admins ✅
  ↓
Notification muncul di bell icon
```

### Daily Check Flow
```
Scheduler → 08:00 AM daily
  ↓
CheckPaymentDueDates command runs
  ↓
Find upcoming (2 days) & overdue payments
  ↓
Send database notifications to all admins ✅
  ↓
Notification muncul di bell icon
```

---

## ⚙️ Konfigurasi

### Polling Interval
Di `app/Providers/Filament/AdminPanelProvider.php`:
```php
->databaseNotifications()
->databaseNotificationsPolling('30s')  // Check every 30 seconds
```

### Scheduler
Di `app/Console/Kernel.php`:
```php
// Daily check at 08:00 AM
$schedule->command('payments:check-due-dates')
    ->dailyAt('08:00')
    ->withoutOverlapping();
```

---

## 🎨 UI Features

### Bell Icon (Top Right Header)
- 🔔 **Badge Count:** Jumlah notifikasi unread
- 📋 **Dropdown List:** Click icon untuk lihat semua notifikasi
- ✅ **Mark as Read:** Click notifikasi untuk mark as read
- 🗑️ **Dismiss All:** Button untuk clear semua notifikasi
- 🔗 **Action Buttons:** Setiap notifikasi punya button untuk aksi cepat

### Notification Item
```
┌─────────────────────────────────────────┐
│ 💰 Pembayaran Diterima           [•]    │
│ Pembayaran INV-202410-0001 dari Budi    │
│ Santoso sebesar Rp 150,000 telah        │
│ dikonfirmasi.                            │
│                                          │
│ [Lihat Detail]                           │
│                                          │
│ 5 menit yang lalu                        │
└─────────────────────────────────────────┘
```

---

## 📊 Database Schema

### Table: `notifications`
```sql
CREATE TABLE `notifications` (
  `id` char(36) PRIMARY KEY,
  `type` varchar(255),
  `notifiable_type` varchar(255),
  `notifiable_id` bigint unsigned,
  `data` text,           -- JSON: {title, body, icon, actions, etc}
  `read_at` timestamp NULL,
  `created_at` timestamp,
  `updated_at` timestamp,
  INDEX `idx_notifiable` (`notifiable_type`, `notifiable_id`),
  INDEX `idx_read_at` (`read_at`)
);
```

### JSON Data Structure
```json
{
  "title": "💰 Pembayaran Diterima",
  "body": "Pembayaran INV-202410-0001 dari Budi Santoso sebesar Rp 150,000 telah dikonfirmasi.",
  "icon": "heroicon-o-check-circle",
  "iconColor": "success",
  "actions": [
    {
      "name": "view",
      "label": "Lihat Detail",
      "url": "/admin/payments/123/edit",
      "button": true
    }
  ],
  "format": "filament"
}
```

---

## 🧪 Testing

### Manual Test Commands

#### 1. Test Payment Due Dates Notification
```bash
php artisan payments:check-due-dates
```

#### 2. Simulate Payment Paid (via Tinker)
```bash
php artisan tinker

# Get a pending payment
$payment = \App\Models\Payment::where('status', 'pending')->first();

# Mark as paid (will trigger notification)
$payment->status = 'paid';
$payment->payment_date = now();
$payment->save();
```

#### 3. Test Generate Monthly Bills
```bash
php artisan bills:generate --month=2025-11
```

#### 4. Check Notifications in Database
```bash
php artisan tinker

# Get all notifications for user ID 1
$user = \App\Models\User::find(1);
$notifications = $user->notifications;
$notifications->count();

# Mark all as read
$user->unreadNotifications->markAsRead();
```

---

## 🎯 Who Receives Notifications?

**Semua admin users** (users dengan `is_admin = true`)

Query untuk mendapatkan recipients:
```php
$adminUsers = User::where('is_admin', true)->get();

Notification::make()
    ->title('...')
    ->body('...')
    ->sendToDatabase($adminUsers);
```

---

## 📝 Cara Menambahkan Notifikasi Baru

### Step 1: Di Service/Observer/Command
```php
use App\Models\User;
use Filament\Notifications\Notification;

// Get admin users
$adminUsers = User::where('is_admin', true)->get();

// Send notification
Notification::make()
    ->title('📌 Custom Title')
    ->body('Your notification message here')
    ->success()  // or ->warning() or ->danger() or ->info()
    ->icon('heroicon-o-bell')
    ->actions([
        \Filament\Notifications\Actions\Action::make('view')
            ->label('View Details')
            ->url(route('filament.admin.resources.xyz.index'))
            ->button(),
    ])
    ->sendToDatabase($adminUsers);
```

### Step 2: Test
```bash
php artisan tinker

# Trigger your action manually
# Check bell icon in admin panel
```

---

## 🔧 Troubleshooting

### Notifikasi Tidak Muncul
1. ✅ Cek user `is_admin = true`
2. ✅ Refresh browser (Ctrl+F5)
3. ✅ Cek polling interval: `->databaseNotificationsPolling('30s')`
4. ✅ Cek database table `notifications`
5. ✅ Clear Laravel cache: `php artisan cache:clear`

### Badge Count Salah
```bash
php artisan tinker

$user = auth()->user();
$unread = $user->unreadNotifications->count();
echo "Unread: $unread";
```

### Notifikasi Lama Tidak Terhapus
```bash
# Manual cleanup (via tinker)
php artisan tinker

# Delete notifications older than 30 days
\Illuminate\Notifications\DatabaseNotification::where('created_at', '<', now()->subDays(30))->delete();
```

---

## 📈 Performance

### Polling Impact
- **Interval:** 30 detik
- **Query:** 1 query per poll (hanya count unread)
- **Impact:** Negligible untuk < 100 concurrent users

### Storage
- **Per notification:** ~500 bytes
- **1000 notifications/month:** ~0.5 MB
- **Retention:** Unlimited (manual cleanup recommended)

### Optimization Tips
1. ✅ Index on `notifiable_id` dan `read_at`
2. ✅ Periodic cleanup of old read notifications
3. ✅ Avoid sending duplicate notifications

---

## 📚 Dokumentasi Terkait

- [Filament Database Notifications](https://filamentphp.com/docs/3.x/notifications/database-notifications)
- [Laravel Notifications](https://laravel.com/docs/notifications)
- [Activity Logging System](./activity_logging_system.md)

---

## 🎯 Summary

**✅ SEMUA event penting tercatat di bell icon**  
**✅ Persistent notifications (tidak hilang)**  
**✅ Action buttons untuk aksi cepat**  
**✅ Proactive alerts (upcoming & overdue)**  
**✅ Real-time polling setiap 30 detik**

🎉 **Sistem notifikasi sudah production-ready!**

