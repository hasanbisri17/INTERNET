# Dokumentasi Teknis Sistem Penagihan ISP

## Arsitektur Sistem

Sistem penagihan ISP ini dibangun dengan arsitektur MVC (Model-View-Controller) menggunakan framework Laravel. Berikut adalah komponen utama sistem:

1. **Model**: Representasi data dan logika bisnis
2. **View**: Antarmuka pengguna (UI)
3. **Controller**: Menangani permintaan HTTP dan logika aplikasi
4. **Service**: Layanan yang menangani logika bisnis kompleks
5. **Migration**: Skema database

## Komponen Utama

### 1. Dunning Engine

Dunning Engine adalah sistem penagihan bertahap yang mengelola proses penagihan pelanggan yang belum membayar tagihan.

**Komponen Utama:**
- `DunningSchedule`: Model untuk jadwal penagihan
- `DunningStep`: Model untuk langkah-langkah penagihan
- `DunningService`: Service untuk menjalankan proses penagihan

**Alur Kerja:**
1. Sistem memeriksa pembayaran yang belum lunas
2. Menerapkan langkah-langkah penagihan sesuai konfigurasi
3. Mengirim notifikasi, menerapkan denda, atau menangguhkan layanan

### 2. Integrasi Pembayaran

Sistem terintegrasi dengan gateway pembayaran untuk memproses pembayaran pelanggan.

**Komponen Utama:**
- `PaymentGatewayService`: Service untuk integrasi dengan gateway pembayaran
- `Payment`: Model untuk data pembayaran

**Fitur:**
- Pembuatan transaksi pembayaran
- Pemrosesan webhook dari gateway pembayaran
- Rekonsiliasi otomatis pembayaran

### 3. Integrasi Jaringan (AAA)

Sistem terintegrasi dengan infrastruktur jaringan untuk mengelola akses pelanggan.

**Komponen Utama:**
- `AAAService`: Service untuk integrasi dengan sistem AAA
- `CaptivePortalController`: Controller untuk menangani Captive Portal

**Fitur:**
- Penangguhan dan pengaktifan layanan
- Captive Portal untuk pelanggan yang layanannya ditangguhkan

### 4. RBAC dan Sistem Keamanan

Sistem mengimplementasikan Role-Based Access Control (RBAC) untuk manajemen akses.

**Komponen Utama:**
- `Role`: Model untuk peran pengguna
- `Permission`: Model untuk izin akses
- Migrasi untuk tabel roles, permissions, role_permission, dan user_role

### 5. Portal Pelanggan dan Chatbot WhatsApp

Sistem menyediakan portal pelanggan dan integrasi WhatsApp untuk komunikasi dengan pelanggan.

**Komponen Utama:**
- `CustomerPortalController`: Controller untuk portal pelanggan
- `WhatsAppService`: Service untuk integrasi dengan WhatsApp

**Fitur Portal Pelanggan:**
- Dashboard pelanggan
- Riwayat pembayaran
- Profil pelanggan
- Pembayaran tagihan

**Fitur WhatsApp:**
- Notifikasi tagihan
- Pengingat pembayaran
- Notifikasi pembayaran berhasil
- Notifikasi layanan ditangguhkan

### 6. Sistem Analitik dan Dashboard

Sistem menyediakan dashboard analitik untuk monitoring dan analisis data.

**Komponen Utama:**
- `AnalyticsController`: Controller untuk dashboard analitik

**Fitur:**
- Dashboard analitik
- Laporan pendapatan
- Laporan pelanggan
- Laporan tagihan

## Skema Database

### Tabel Utama:
1. `customers`: Data pelanggan
2. `internet_packages`: Paket internet
3. `payments`: Data pembayaran
4. `dunning_schedules`: Jadwal penagihan
5. `dunning_steps`: Langkah-langkah penagihan
6. `roles`: Peran pengguna
7. `permissions`: Izin akses
8. `role_permission`: Relasi peran dan izin
9. `user_role`: Relasi pengguna dan peran

## API dan Integrasi

### API Internal:
1. **Payment Gateway API**: Untuk integrasi pembayaran
2. **AAA API**: Untuk integrasi dengan sistem jaringan
3. **WhatsApp API**: Untuk integrasi dengan WhatsApp

## Keamanan

1. **Autentikasi**: Menggunakan Laravel Sanctum
2. **Otorisasi**: Menggunakan RBAC
3. **Validasi Input**: Validasi semua input pengguna
4. **CSRF Protection**: Perlindungan terhadap serangan CSRF
5. **Logging**: Pencatatan aktivitas pengguna menggunakan Spatie Activity Log

## Konfigurasi Sistem

Konfigurasi sistem disimpan dalam file `.env` dan file konfigurasi Laravel:

1. **Database**: Konfigurasi koneksi database
2. **Payment Gateway**: Konfigurasi integrasi payment gateway
3. **WhatsApp**: Konfigurasi integrasi WhatsApp
4. **AAA**: Konfigurasi integrasi sistem AAA

## Monitoring dan Logging

1. **Activity Log**: Pencatatan aktivitas pengguna
2. **Error Log**: Pencatatan error sistem
3. **Payment Log**: Pencatatan transaksi pembayaran
4. **Notification Log**: Pencatatan notifikasi yang dikirim