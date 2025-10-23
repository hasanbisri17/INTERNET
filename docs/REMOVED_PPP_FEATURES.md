# ⚠️ PPP Features Removal Notice

## 📌 Overview
PPP Profile dan PPP Secret features telah dihapus dari sistem karena tidak lagi dibutuhkan. Sistem sekarang menggunakan **IP Binding** sebagai metode utama untuk manajemen akses customer.

## 🗑️ Features yang Dihapus

### 1. **PPP Profile Management**
- ❌ CRUD PPP Profile
- ❌ Sync profil dari/ke MikroTik
- ❌ Rate limit management via PPP
- ❌ Address pool configuration

### 2. **PPP Secret Management**
- ❌ CRUD PPP Secret (Users)
- ❌ PPPoE username/password management
- ❌ Auto-sync PPP secrets
- ❌ Enable/Disable PPP user
- ❌ Disconnect active PPP session

## 🔄 Migration ke IP Binding

### Metode Suspend Customer Baru:
**Sebelumnya (PPP):**
```
Customer overdue → Change PPP Profile to "isolir" → Bandwidth reduced
```

**Sekarang (IP Binding):**
```
Customer overdue → Disable IP Binding → Customer tidak bisa akses internet
```

### Commands yang Berubah:

**Suspend System:**
- ❌ `php artisan auto-isolir` (via PPP profile change)
- ✅ `php artisan suspend:ip-binding` (via IP Binding disable)

**Monitoring:**
- ❌ Active PPP sessions monitoring
- ✅ IP Binding status monitoring
- ✅ Netwatch online/offline monitoring

## 📋 Database Changes

### Tables Dihapus:
- `mikrotik_ppp_secrets`
- `mikrotik_profiles`

### Kolom Dihapus dari `customers`:
- `pppoe_username`
- `pppoe_password`
- `ppp_secret_id`

### Kolom Dihapus dari `internet_packages`:
- `mikrotik_profile_id`

### Kolom Dihapus dari `auto_isolir_configs`:
- `isolir_profile_id`
- `isolir_profile_name`

## 🔧 Code Changes

### Files Dihapus:
```
app/Models/
├── ❌ MikrotikPppSecret.php
└── ❌ MikrotikProfile.php

app/Services/
├── ❌ MikrotikPppService.php
└── ❌ MikrotikProfileService.php

app/Observers/
└── ❌ MikrotikProfileObserver.php

app/Filament/Resources/
├── ❌ MikrotikPppSecretResource.php
└── ❌ MikrotikProfileResource.php
```

### Models Updated:
- ✅ `Customer` - Removed PPP relations
- ✅ `InternetPackage` - Removed PPP profile relation
- ✅ `MikrotikDevice` - Removed PPP relations
- ✅ `AutoIsolirConfig` - Removed PPP profile reference

### Services Updated:
- ✅ `AutoIsolirService` - Simplified, no longer uses PPP

## 🎯 New Recommended Flow

### Customer Activation:
```
1. Create Customer
2. Add IP Binding(s) di tab "IP Bindings"
3. Sync ke MikroTik
4. Customer langsung bisa akses internet
```

### Customer Suspension:
```
1. Auto detect overdue (via scheduler)
2. Disable IP Binding di MikroTik
3. Customer tidak bisa akses internet
4. Send WhatsApp notification
```

### Customer Restoration:
```
1. Customer bayar tagihan
2. Auto-enable IP Binding
3. Customer bisa akses internet lagi
4. Send WhatsApp confirmation
```

## 📚 Related Documentation

Untuk panduan lengkap fitur IP Binding, lihat:
- [IP Bindings Feature](./mikrotik_ip_bindings_feature.md)
- [Suspend via IP Binding](./suspend_via_ip_binding_feature.md)
- [Mikrotik Integration Guide](../MIKROTIK_INTEGRATION_GUIDE.md)

## ⚠️ Important Notes

1. **Migration**: Jika Anda memiliki data PPP existing, jalankan migration untuk cleanup:
   ```bash
   php artisan migrate
   ```

2. **Backup**: Pastikan backup database sebelum menjalankan migration

3. **Testing**: Test suspend/restore functionality dengan IP Binding setelah migration

4. **Scheduler**: Update cron jobs untuk menggunakan command baru:
   ```bash
   # Hapus atau disable:
   # php artisan auto-isolir

   # Gunakan:
   php artisan suspend:ip-binding
   ```

---

**Tanggal Perubahan:** 23 Oktober 2025
**Alasan:** Simplifikasi sistem, IP Binding lebih reliable dan mudah di-maintain

