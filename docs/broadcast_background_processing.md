# 📢 Broadcast WhatsApp - Background Processing

## ✅ **Fitur Baru: Pengiriman di Background**

Sekarang broadcast WhatsApp **tidak perlu menunggu** sampai semua pesan selesai terkirim! Prosesnya berjalan di **background** dengan **progress bar real-time**.

---

## 🚀 **Cara Kerja**

### **1. Kirim Broadcast** (`/admin/whatsapp-broadcast`)
1. Isi form broadcast (judul, penerima, pesan)
2. Klik **"Kirim Broadcast"**
3. Sistem membuat campaign dengan status **"processing"**
4. **Langsung redirect** ke halaman detail campaign
5. Tidak perlu tunggu pengiriman selesai!

### **2. Lihat Progress** (`/admin/broadcast-campaigns/{id}`)
1. Halaman otomatis **auto-refresh setiap 3 detik**
2. Melihat progress bar real-time:
   - **⏳ Sedang mengirim pesan... Progress: 45% (23 + 2 / 50)**
3. Statistik update otomatis:
   - ✅ **Berhasil**: jumlah pesan terkirim
   - ❌ **Gagal**: jumlah pesan gagal
   - 📊 **Percentage**: % kesuksesan
4. Daftar penerima dengan status per-customer
5. Auto-refresh **berhenti** saat status = "completed" atau "failed"

---

## 🎯 **Keunggulan**

### **Sebelum:**
- ❌ Harus tunggu semua pesan terkirim (bisa 5-10 menit)
- ❌ Halaman loading/freeze
- ❌ Tidak bisa tutup browser
- ❌ Tidak tahu progress saat ini

### **Sekarang:**
- ✅ Langsung redirect (instant)
- ✅ Bisa tutup browser, proses tetap jalan
- ✅ Progress real-time dengan auto-refresh
- ✅ Notifikasi database saat selesai
- ✅ Bisa kirim campaign baru sambil campaign lama masih berjalan

---

## ⚙️ **Arsitektur Teknis**

### **1. Background Job**
```
WhatsAppBroadcast (UI)
  ↓
Create Campaign (status: processing)
  ↓
Dispatch Job (SendBroadcastMessagesJob)
  ↓
Redirect to Campaign Detail
  ↓
Job runs in background →
  Update progress in database →
    Status: processing
    Success count: updating...
    Failed count: updating...
  ↓
Job complete →
  Update status: completed/failed
  Send database notification
```

### **2. Real-time Progress**
```
Campaign Detail Page
  ↓
Check status === 'processing'?
  YES → Enable wire:poll.3s (auto-refresh every 3 seconds)
  NO → No auto-refresh
  ↓
Each refresh:
  1. Reload campaign data from database
  2. Calculate progress: (success + failed) / total * 100
  3. Update UI components
  4. Check if status changed to completed
```

---

## 📂 **File yang Dimodifikasi/Dibuat**

### **✅ New File:**
1. **`app/Jobs/SendBroadcastMessagesJob.php`**
   - Job untuk kirim broadcast messages di background
   - Update campaign progress secara real-time
   - Send database notification saat selesai

2. **`docs/broadcast_background_processing.md`**
   - Dokumentasi fitur (file ini)

### **✅ Modified Files:**
3. **`app/Filament/Pages/WhatsAppBroadcast.php`**
   - Dispatch job instead of synchronous sending
   - Redirect ke campaign detail setelah create
   - Notification dengan button "Lihat Progress"

4. **`app/Filament/Resources/BroadcastCampaignResource/Pages/ViewBroadcastCampaign.php`**
   - Added `getRefreshInterval()` untuk auto-refresh
   - Added progress description di section header

5. **`resources/views/filament/resources/broadcast-campaign-resource/pages/view-broadcast-campaign.blade.php`**
   - Added `wire:poll.3s` untuk auto-refresh saat processing
   - Progress ditampilkan di description section

---

## 🧪 **Testing**

### **Manual Test:**

#### 1. Test Broadcast dengan 10 Penerima
```bash
1. Buka /admin/whatsapp-broadcast
2. Pilih "Custom" recipient
3. Pilih 10 customer
4. Isi pesan
5. Klik "Kirim Broadcast"
6. Verifikasi:
   ✅ Langsung redirect ke campaign detail
   ✅ Progress bar muncul
   ✅ Auto-refresh setiap 3 detik
   ✅ Success/Failed count update real-time
   ✅ Status berubah ke "completed" saat selesai
```

#### 2. Test Multiple Campaigns
```bash
1. Kirim campaign A (100 penerima)
2. Langsung kirim campaign B (50 penerima)
3. Verifikasi:
   ✅ Kedua campaign berjalan parallel di queue
   ✅ Bisa lihat progress kedua campaign
   ✅ Notifikasi muncul untuk masing-masing campaign
```

#### 3. Test Queue Worker
```bash
# Cek queue worker status
php artisan queue:work --tries=3 --timeout=300

# Monitor logs
tail -f storage/logs/laravel.log

# Verifikasi job diproses
```

---

## 🛠️ **Setup Requirements**

### **1. Queue Worker HARUS Running**

```bash
# Start queue worker (development)
php artisan queue:work --tries=3 --timeout=300

# atau di background
php artisan queue:work --tries=3 --timeout=300 &
```

### **2. Production: Supervisor**

Create file `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

Reload supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

---

## 📊 **Database Changes**

### **Campaign Status Flow:**
```
pending → processing → completed/failed
  ↑           ↓              ↓
Create    Job running    Job done
           Update         Send notification
           progress
```

### **Campaign Fields Used:**
- `status`: pending/processing/completed/failed
- `success_count`: Updated real-time by job
- `failed_count`: Updated real-time by job
- `total_recipients`: Set saat create
- `sent_at`: Set saat job selesai

---

## 🔔 **Notifications**

### **1. Saat Kirim (Flash Notification)**
```
📢 Broadcast Sedang Diproses
Campaign 'Promo Ramadan' sedang dikirim ke 100 penerima.
Proses berlangsung di background.

[Lihat Progress]
```

### **2. Saat Selesai (Database Notification)**
```
📢 Broadcast WhatsApp Selesai
Campaign 'Promo Ramadan' selesai dikirim.
Berhasil: 95, Gagal: 5

[Lihat Detail]
```

---

## ⚡ **Performance**

### **Throughput:**
- **1 pesan ≈ 0.5 detik** (with API call + db write)
- **100 pesan ≈ 50 detik** (tanpa rate limiting)
- **Delay 0.1s per pesan** (untuk avoid rate limit)
- **100 pesan ≈ 60-70 detik** (dengan delay)

### **Queue Capacity:**
- **1 worker**: Process 1 campaign at a time
- **Multiple workers**: Process multiple campaigns parallel
- **Recommended**: 2-4 workers untuk optimal throughput

---

## 🐛 **Troubleshooting**

### **Progress Tidak Update:**
1. ✅ Cek queue worker running: `ps aux | grep queue:work`
2. ✅ Cek logs: `tail -f storage/logs/laravel.log`
3. ✅ Cek failed jobs: `php artisan queue:failed`
4. ✅ Retry failed: `php artisan queue:retry all`

### **Auto-refresh Tidak Jalan:**
1. ✅ Cek status campaign: harus "processing"
2. ✅ Hard refresh browser (Ctrl+F5)
3. ✅ Cek console browser untuk error

### **Notifikasi Tidak Muncul:**
1. ✅ Cek user `is_admin = true`
2. ✅ Cek table `notifications`
3. ✅ Clear cache: `php artisan cache:clear`

---

## 📝 **Best Practices**

### **✅ DO:**
- Keep queue worker running 24/7 di production
- Monitor queue worker dengan Supervisor
- Set reasonable timeout (300 seconds)
- Use delay between messages untuk avoid rate limit

### **❌ DON'T:**
- Jangan kirim broadcast tanpa queue worker
- Jangan set timeout terlalu rendah
- Jangan kirim ke 1000+ penerima sekaligus
- Jangan restart queue worker saat job running

---

## 🎉 **Summary**

✅ **Broadcast sekarang tidak blocking UI**  
✅ **Progress real-time dengan auto-refresh**  
✅ **Notifikasi database saat selesai**  
✅ **Bisa kirim multiple campaigns parallel**  
✅ **Production-ready dengan queue system**

**Enjoy your new broadcast system!** 🚀

