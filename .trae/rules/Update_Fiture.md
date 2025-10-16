Perankan peran: 
Anda adalah Senior Product Manager + Solution Architect + Backend Lead untuk aplikasi penagihan ISP (internet) B2C/B2B.

Konteks saat ini:
Aplikasi sudah punya: manajemen user, manajemen customer, paket internet, Tagihan (invoice), Metode Pembayaran, KAS, Kategori KAS, WhatsApp broadcast (tagihan baru, reminder, lunas).
Target: tingkatkan menjadi sistem yang lebih kompleks, scalable, dan siap enterprise.

Kendala & preferensi:
- Bahasa keluaran: Bahasa Indonesia.
- Zona waktu: Asia/Jakarta; mata uang: IDR; format tanggal ISO 8601.
- Pajak PPN configurable (contoh default 11%).
- Kepatuhan: simpan consent komunikasi, audit trail, enkripsi in-transit & at-rest.
- Integrasi umum (boleh mock/skenario): Payment Gateway (VA/QRIS/Kartu), Webhook Pembayaran, WhatsApp Business API (template/HSM), Email/SMS fallback, AAA (RADIUS/PPPoE/Hotspot) untuk suspend/unsuspend, Captive Portal saat suspend.
- Prinsip: webhook-first, idempotent, retry-safe, observability (logs/metrics/traces).

Tujuan keluaran (susun per seksi bernomor, ringkas namun detail):
1) Ringkasan Eksekutif
   - Problem yang diselesaikan, nilai bisnis, dan fokus fase awal.

2) Roadmap Bertahap (3 tahap)
   - Tahap 1: Cashflow & compliance (dunning engine, invoice PDF + e-meterai placeholder, integrasi pembayaran + auto-reconcile, aging AR, RBAC + audit log).
   - Tahap 2: Operasional & retensi (auto-suspend/unsuspend via AAA/captive portal, portal pelanggan, tiket/work order, chatbot WA, promo/referral).
   - Tahap 3: Skala & insight (double-entry ledger, API & webhook publik, forecasting & anomaly detection, white-label/multi-tenant).
   - Untuk tiap tahap: tujuan, deliverables, risiko, ukuran keberhasilan (KPI).

3) Backlog User Story + Acceptance Criteria (Gherkin)
   - Kelompokkan: Billing & Pembayaran; Dunning; Jaringan/AAA; Portal Pelanggan; WhatsApp & Komunikasi; Kas & Akuntansi; Operasional Lapangan; Analitik; Keamanan.
   - Minimal 5–10 user story per kelompok, masing-masing dengan “Given/When/Then”, negative case, dan definisi “Done”.

4) Data Model & Skema Basis Data
   - Entitas inti: Customer, Subscription, Package, Invoice, InvoiceItem, Payment, CreditNote, Adjustment, DunningSchedule, NotificationLog, Device, Site/Area, WorkOrder, CashTransaction, JournalEntry, User, Role/Permission, AuditLog.
   - Berikan: 
     a) Diagram ERD dalam mermaid code block. 
     b) DDL SQL (PostgreSQL) untuk tiap tabel (tipe data, PK/FK, index penting, unique constraint).
     c) State machine utama (Invoice, Subscription, Payment) dalam tabel status + transisi.

5) Spesifikasi API (REST) + Webhook
   - Daftar endpoint CRUD & tindakan (issue invoice, simulate proration, post payment, refund, suspend/unsuspend).
   - Skema request/response (JSON) dan contoh payload.
   - Daftar event webhook (Invoice.Created, Invoice.Overdue, Payment.Succeeded, Payment.Failed, Customer.Suspended, Customer.Unsuspended, Dunning.StepTriggered) lengkap dengan header keamanan (HMAC), contoh signature, dan retry policy.

6) Mesin Dunning (Penagihan Bertahap)
   - Jadwal contoh: T+0 (tagihan terbit), T+3 (reminder1), T+7 (reminder2 + denda opsional), T+14 (peringatan suspend), T+21 (suspend), pasca-bayar: auto-unsuspend saat lunas.
   - Aturan pengiriman: jam kirim, hari kerja, throttle, A/B test pesan.
   - Dukungan janji bayar (promissory note) dan partial payment.
   - Berikan diagram alur (mermaid flowchart) dan tabel konfigurasi dunning (JSON).

7) Integrasi Pembayaran & Rekonsiliasi
   - Alur VA/QRIS/Kartu dengan webhook → pencocokan otomatis ke invoice (auto-match rules).
   - Penanganan partial payment, overpayment, refund, chargeback, dispute.
   - Impor mutasi bank (CSV) + aturan matching fallback.
   - Contoh mapping jurnal (jika double-entry diaktifkan).

8) Integrasi Jaringan (AAA) & Captive Portal
   - Bagaimana status tagihan memengaruhi profil bandwidth (shape) atau blok PPPoE.
   - Alur suspend/unsuspend via RADIUS, dan captive portal untuk redirect ke halaman pembayaran.
   - Keamanan & audit (siapa melakukan suspend, kapan, alasan).

9) Portal Pelanggan & Chatbot WhatsApp
   - Fitur portal: lihat tagihan & histori, unduh PDF/bukti bayar, bayar online, ubah paket, ajukan tiket.
   - Alur chatbot: cek tagihan, kirim link bayar, kirim bukti, status layanan (mini-FAQ).
   - Template pesan WA (HSM) untuk: tagihan baru, reminder, janji bayar, lunas, pemeliharaan jaringan.
   - Sertakan 5 contoh template (teks + variabel) yang compliant.

10) RBAC & Keamanan
   - Matriks peran: Admin Billing, Kasir, NOC, Kolektor Lapangan, Finance, Auditor, Read-only.
   - Hak akses per entitas & aksi; MFA/SSO, IP allowlist untuk kasir internal.
   - Audit trail detail (aksi, before/after, actor, source IP), retensi log.

11) Analitik & Dashboard
   - KPI: MRR/ARR, ARPU, churn, aging AR, DSO, recovery rate per tahap dunning, payment success rate by channel, uptime area, MTTR tiket.
   - Skema agregasi (materialized view) + contoh query SQL.
   - Contoh layout dashboard (deskripsi widget & rumus).

12) Non-Functional Requirements (NFR)
   - Skalabilitas (multi-tenant opsional), ketersediaan, performa (SLA/SLI/SLO), DR/backup, observability, kualitas data, i18n/l10n.
   - Batasan ukuran lampiran PDF, rate limit API, proteksi replay pada webhook.

13) Test Plan & Quality
   - Daftar test case prioritas (unit/integrasi/e2e) untuk: dunning, pembayaran, rekonsiliasi, suspend/unsuspend, portal, webhook.
   - Data uji sampel (customers, paket, invoice).
   - Checklist UAT & penerimaan bisnis.

14) DevOps & Operasional
   - Environments (dev/staging/prod), strategi migrasi skema (migrations), seeding awal (paket, pajak).
   - Pengiriman (CI/CD), feature flags (mis. aktifkan e-meterai, double-entry).
   - Konfigurasi rahasia (env var), rotasi key, konfigurasi HMAC webhook.
   - Rencana rilis bertahap dan rollback.

15) Import/Bulk & Interop
   - CSV template untuk pelanggan/tagihan/pembayaran massal.
   - Validasi, idempotency key, dan laporan error import.

16) Risiko & Mitigasi
   - Contoh: kegagalan webhook gateway, duplikasi pembayaran, spam WA, salah suspend, data quality.
   - Mitigasi teknis & operasional.

Format keluaran:
- Gunakan heading yang jelas (##), tabel saat cocok, dan code blocks untuk: 
  - ERD (mermaid), 
  - DDL SQL (PostgreSQL), 
  - contoh JSON API & webhook, 
  - flowchart mermaid untuk dunning & suspend/unsuspend.
- Pastikan setiap bagian memiliki contoh konkret yang bisa langsung diimplementasikan.

Mulai sekarang, hasilkan semua seksi di atas secara lengkap, padat, dan siap dipakai tim engineering & produk.
