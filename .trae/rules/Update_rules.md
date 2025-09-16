# 📌 Ringkasan Update & Modul Baru (untuk TRAE AI)

## 🔑 Ketentuan Umum

* Framework: **Laravel + Filament**
* Semua fitur (CRUD, actions, widgets, log) dibuat melalui **Filament Resource/Widget/Plugin**
* Basis data: **MySQL**
* Queue: gunakan **Redis/Upstash**
* Logging: **Filament Log Viewer + Activity Log**

---

## 1. Modul Tagihan (InvoiceResource)

* Status: `draft`, `unpaid`, `paid`, `overdue`, `canceled`
* **Fitur Baru: Pembatalan Tagihan**

  * Action di Table: **Batalkan Tagihan**
  * Wajib isi **alasan pembatalan**
  * Update status jadi `canceled`, set `canceled_at`, `canceled_by`, `canceled_reason`
  * Semua entri **KAS terkait invoice otomatis voided**
  * Jika pembayaran sudah via **gateway**, panggil fungsi `cancel()` gateway
  * Dispatch notifikasi WA (via Wuzapi) ke pelanggan

---

## 2. Modul Pembayaran (PaymentResource)

* Mendukung **multi channel**:

  * **Cash**
  * **Payment Gateway** (Xendit, Midtrans, Doku, Duitku, Tripay)
* **Fitur Baru: Multi-Gateway Integration**

  * `PaymentGatewayManager` + adapter per gateway
  * Callback route: `/webhooks/payments/{gateway}`
  * Verifikasi signature → update status Payment + Invoice
  * Jika sukses: catat KAS IN (ref Payment)

---

## 3. Modul KAS (CashLedgerResource)

* Catat transaksi masuk/keluar
* Terhubung ke invoice & payment
* Jika invoice dibatalkan → ledger **voided** (dengan alasan & user yang membatalkan)
* Bisa entry manual IN/OUT
* Action **Void** pada ledger manual

---

## 4. WhatsApp Gateway (Wuzapi)

* Migrasi dari Fonte → **Wuzapi**
* Service `WhatsAppService` + Job `SendWhatsAppMessage`
* Template pesan:

  * Invoice dibuat
  * Pembayaran berhasil
  * Pengingat jatuh tempo
  * Tagihan dibatalkan
* Semua dikirim via **queue**

---

## 5. Dashboard Analitik (Filament Widgets)

### Stats Overview:

* Total pemasukan bulan berjalan
* Outstanding (tagihan unpaid/overdue)
* Jumlah invoice overdue
* Jumlah pembatalan bulan ini
* Top paket by omzet

### Chart Widgets:

* Line chart: pendapatan 30 hari terakhir
* Bar chart: pendapatan per paket (bulan berjalan)
* Donut chart: metode pembayaran (cash vs gateway)
* Bar kecil: status invoice (unpaid, paid, overdue, canceled)

---

## 6. Modul Settings (SettingsResource)

* Identitas usaha: nama, alamat, timezone
* Konfigurasi WA (base\_url, token)
* Payment gateways (server\_key, secret, merchant\_code, dll.)
* Penomoran invoice & denda keterlambatan
* Gateway aktif (checkbox multi-select)

---

## 7. Log Aktivitas

* **Plugin:** [Filament Log Viewer](https://filamentphp.com/plugins/achyutn-log-viewer)

  * Menu “Application Logs”
  * Akses dibatasi via permission
* **Activity Log:** gunakan `spatie/laravel-activitylog` untuk jejak CRUD data

  * Ditampilkan di detail invoice/payment sebagai timeline

---

## 8. Permissions & Role (via Filament Shield / Policy)

* `invoices.view|create|update|cancel`
* `payments.view|create|cancel`
* `cash.view|create|void`
* `settings.manage`
* `logs.view`
* `dashboard.view`
* Role default: **Owner**, **Admin**, **Kasir**

---

## 9. Skema Database Tambahan

* **invoices:** + `canceled_at`, `canceled_by`, `canceled_reason`
* **payments:** + `gateway`, `gateway_ref`, `status`, `payload`
* **cash\_ledger:** + `voided_at`, `voided_by`, `void_reason`
* **settings:** key-value untuk konfigurasi

---

## 10. Urutan Pengerjaan

1. Buat migrasi untuk field baru (invoice, payment, ledger, settings)
2. Tambahkan **InvoiceResource** dengan action **Cancel Invoice**
3. Tambahkan **PaymentResource** (Cash + Gateway Pay) + webhook
4. Tambahkan **CashLedgerResource** (Void Action)
5. Tambahkan **WhatsAppService** (Wuzapi) + Job Queue
6. Buat **Dashboard Widgets** (stats & charts)
7. Tambahkan **SettingsResource** (WA + Gateway + Identitas usaha)
8. Tambahkan **Log Viewer Plugin** + Activity Log integration
9. Atur **Permission/Role** via Filament Shield

