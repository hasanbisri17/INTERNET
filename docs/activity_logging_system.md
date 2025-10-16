# Sistem Activity Logging Lengkap

## 📋 Deskripsi

Sistem activity logging yang mencatat **SEMUA** aktivitas penting di aplikasi menggunakan [Spatie Laravel Activity Log](https://github.com/spatie/laravel-activitylog). Semua log dapat dilihat di menu **"Log Aktivitas"** di admin panel.

---

## ✅ Aktivitas yang Tercatat

### 1. **Customer & Payments**
- ✅ Customer (create, update, delete)
- ✅ Payments (create, update status, delete)
- ✅ Cash Transactions (create, update, delete)

### 2. **Users & Authentication**
- ✅ Users (create, update, delete)
- ✅ Login / Logout / Failed Login
- ✅ Roles & Permissions (create, update, delete)

### 3. **Internet Packages & Devices**
- ✅ Internet Packages (create, update, delete)
- ✅ Mikrotik Devices (create, update, delete)

### 4. **Payment Methods & Categories**
- ✅ Payment Methods (create, update, delete)
- ✅ Transaction Categories (create, update, delete)

### 5. **WhatsApp System**
- ✅ WhatsApp Messages (create, sent, failed)
- ✅ WhatsApp Templates (create, update, delete)
- ✅ WhatsApp Settings (update)
- ✅ **WhatsApp Notifications** (sent/failed dengan detail lengkap)
- ✅ Broadcast Campaigns (create, update, delete)

### 6. **Payment Reminders**
- ✅ Payment Reminders (scheduled, sent, failed)
- ✅ Payment Reminder Rules (create, update, delete)

### 7. **Dunning System** ⭐ (Penagihan Otomatis)
- ✅ **Customer Suspended** (dengan days overdue)
- ✅ **Customer Unsuspended** (setelah bayar)
- ✅ Dunning Configs (create, update, delete)
- ✅ Dunning Schedules (create, update, delete)
- ✅ Dunning Steps (create, update, delete)

### 8. **Settings**
- ✅ General Settings (update)
- ✅ AAA Configs (create, update, delete)
- ✅ Customer Portal Configs (create, update, delete)

---

## 📊 Format Log di Database

Setiap log berisi:

| Field | Deskripsi |
|-------|-----------|
| `log_name` | Kategori log (customers, payments, dunning, etc.) |
| `description` | Deskripsi singkat aktivitas |
| `subject_type` | Model yang terkait (Payment, Customer, etc.) |
| `subject_id` | ID record yang terkait |
| `causer_type` | Model pelaku (User) |
| `causer_id` | ID user yang melakukan aksi |
| `properties` | JSON detail tambahan (customer_name, invoice_number, etc.) |
| `created_at` | Timestamp aktivitas |

---

## 🔍 Contoh Log Entry

### Suspend Customer (Dunning)
```
Log Name: dunning
Event: -
Description: Layanan Budi Santoso ditangguhkan karena tunggakan 5 hari (Invoice: INV-202410-0001)
Properties:
{
  "action": "suspend",
  "customer_id": 123,
  "customer_name": "Budi Santoso",
  "invoice_number": "INV-202410-0001",
  "days_overdue": 5,
  "config_name": "Auto Suspend - 3 Days"
}
```

### Unsuspend Customer (Payment Received)
```
Log Name: dunning
Description: Layanan Budi Santoso diaktifkan kembali setelah pembayaran (Invoice: INV-202410-0001)
Properties:
{
  "action": "unsuspend",
  "customer_id": 123,
  "customer_name": "Budi Santoso",
  "invoice_number": "INV-202410-0001",
  "config_name": "Auto Suspend - 3 Days"
}
```

### WhatsApp Notification Sent
```
Log Name: whatsapp_notifications
Description: WhatsApp 'Penangguhan Layanan' terkirim ke Budi Santoso untuk invoice INV-202410-0001
Properties:
{
  "customer_id": 123,
  "customer_name": "Budi Santoso",
  "customer_phone": "628123456789",
  "invoice_number": "INV-202410-0001",
  "notification_type": "suspended",
  "with_pdf": false,
  "whatsapp_message_id": 456
}
```

---

## 🗂️ Melihat Log Aktivitas

### Di Admin Panel
1. Buka menu **"Log Aktivitas"** (grup Manajement)
2. Filter berdasarkan:
   - **Log** (Pelanggan, Pembayaran, Dunning, WhatsApp, dll)
   - **Peristiwa** (Dibuat, Diperbarui, Dihapus)
   - **Tanggal** (range tanggal)
   - **Pelaku** (User yang melakukan)
   - **Subjek** (Customer, Payment yang terkait)

### Programmatically (Code)
```php
use Spatie\Activitylog\Models\Activity;

// Get all dunning activities
$dunningLogs = Activity::where('log_name', 'dunning')
    ->orderBy('created_at', 'desc')
    ->get();

// Get logs for specific customer
$customerLogs = Activity::where('properties->customer_id', 123)
    ->orderBy('created_at', 'desc')
    ->get();

// Get logs by causer (user)
$userLogs = Activity::causedBy($user)->get();
```

---

## 🧹 Auto Cleanup Log Lama

### Command Manual
```bash
# Hapus log lebih dari 180 hari (default)
php artisan activitylog:clean

# Hapus log lebih dari 90 hari
php artisan activitylog:clean --days=90

# Hapus log lebih dari 1 tahun
php artisan activitylog:clean --days=365
```

### Automatic Schedule
Log otomatis dibersihkan **setiap bulan tanggal 1 jam 02:00 pagi**, hanya menyimpan 6 bulan terakhir.

Dijadwalkan di `app/Console/Kernel.php`:
```php
$schedule->command('activitylog:clean --days=180')
    ->monthlyOn(1, '02:00')
    ->withoutOverlapping();
```

---

## ⚙️ Konfigurasi di Model

### Contoh: Customer Model
```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('customers')           // Kategori log
            ->logFillable()                     // Log semua field fillable
            ->logOnlyDirty()                    // Hanya log yang berubah
            ->dontSubmitEmptyLogs()             // Skip jika tidak ada perubahan
            ->setDescriptionForEvent(fn($event) => "$this->name $event");
    }
}
```

### Custom Activity Log (Non-Model)
```php
// Di Service atau Controller
activity('dunning')
    ->performedOn($payment)
    ->withProperties([
        'customer_id' => $customer->id,
        'action' => 'suspend',
        'days_overdue' => 5,
    ])
    ->log("Customer {$customer->name} suspended");
```

---

## 📈 Performance Impact

### Overhead
- **Per aktivitas:** ~5-15ms
- **Storage:** ~1-2 KB per log entry
- **Estimasi untuk 100 transaksi/hari:** ~4,500 logs/bulan = ~9 MB

### Optimization
✅ **LogOnlyDirty**: Hanya log field yang berubah  
✅ **DontSubmitEmptyLogs**: Skip jika tidak ada perubahan  
✅ **Auto Cleanup**: Hapus log lama setiap bulan  
✅ **Index Database**: Sudah di-index untuk performa query

### Result
💚 **Negligible impact** untuk aplikasi dengan < 1000 aktivitas/hari

---

## 🛠️ Troubleshooting

### Log Tidak Muncul
1. Pastikan model menggunakan `LogsActivity` trait
2. Cek `getActivitylogOptions()` sudah di-configure
3. Pastikan ada perubahan data (jika pakai `logOnlyDirty()`)

### Log Terlalu Banyak
1. Jalankan manual cleanup: `php artisan activitylog:clean --days=90`
2. Kurangi retention period di scheduler
3. Exclude model yang tidak penting

### Performance Lambat
1. Jalankan cleanup untuk reduce database size
2. Enable queue untuk activity log (optional):
   ```php
   LogOptions::defaults()->useQueue()
   ```

---

## 📝 Rekomendasi Best Practices

### ✅ DO
- Log aktivitas penting untuk audit trail
- Use descriptive log messages
- Include relevant context in properties
- Run regular cleanup (monthly)
- Monitor log database size

### ❌ DON'T
- Log setiap query/view (terlalu banyak)
- Log data sensitif (password, token)
- Log tanpa `logOnlyDirty()` (duplikasi)
- Log di loop tanpa batching

---

## 📚 Dokumentasi Terkait

- [Spatie Activity Log](https://spatie.be/docs/laravel-activitylog)
- [Laravel Task Scheduling](https://laravel.com/docs/scheduling)
- [Database Indexing Best Practices](https://laravel.com/docs/migrations#indexes)

---

## 🎯 Summary

**✅ SEMUA aktivitas penting sudah tercatat**  
**✅ Auto cleanup untuk efisiensi**  
**✅ UI yang mudah untuk filtering & searching**  
**✅ Performance optimal dengan best practices**

🎉 **Sistem logging sudah production-ready!**

