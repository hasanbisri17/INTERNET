# Panduan Implementasi Sistem Penagihan ISP

## Prasyarat

Sebelum mengimplementasikan sistem, pastikan server memenuhi persyaratan berikut:

1. PHP 8.0 atau lebih tinggi
2. MySQL 5.7 atau lebih tinggi
3. Composer
4. Node.js dan NPM
5. Web server (Apache/Nginx)

## Langkah-langkah Instalasi

### 1. Persiapan Server

```bash
# Instal dependensi PHP yang diperlukan
sudo apt-get update
sudo apt-get install php8.0-cli php8.0-common php8.0-mysql php8.0-zip php8.0-gd php8.0-mbstring php8.0-curl php8.0-xml php8.0-bcmath
```

### 2. Konfigurasi Database

1. Buat database MySQL baru
2. Buat pengguna database dengan hak akses penuh ke database tersebut

### 3. Instalasi Aplikasi

```bash
# Clone repositori
git clone [URL_REPOSITORI] internet-billing

# Masuk ke direktori aplikasi
cd internet-billing

# Instal dependensi PHP
composer install

# Instal dependensi JavaScript
npm install
npm run dev

# Salin file .env.example menjadi .env
cp .env.example .env

# Generate kunci aplikasi
php artisan key:generate
```

### 4. Konfigurasi Aplikasi

Edit file `.env` dan sesuaikan konfigurasi berikut:

```
# Konfigurasi Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=username_database
DB_PASSWORD=password_database

# Konfigurasi Payment Gateway
PAYMENT_GATEWAY_API_URL=https://api.payment-gateway.com
PAYMENT_GATEWAY_API_KEY=your_api_key
PAYMENT_GATEWAY_SECRET=your_secret_key

# Konfigurasi WhatsApp
WHATSAPP_API_URL=https://api.whatsapp.com
WHATSAPP_API_TOKEN=your_api_token

# Konfigurasi AAA
AAA_API_URL=https://api.aaa-server.com
AAA_API_USERNAME=your_username
AAA_API_PASSWORD=your_password
```

### 5. Migrasi Database

```bash
# Jalankan migrasi database
php artisan migrate

# Jalankan seeder (opsional)
php artisan db:seed
```

### 6. Konfigurasi Web Server

#### Apache

Buat file konfigurasi virtual host baru:

```apache
<VirtualHost *:80>
    ServerName billing.isp.com
    DocumentRoot /path/to/internet-billing/public

    <Directory /path/to/internet-billing/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/billing-error.log
    CustomLog ${APACHE_LOG_DIR}/billing-access.log combined
</VirtualHost>
```

#### Nginx

Buat file konfigurasi server baru:

```nginx
server {
    listen 80;
    server_name billing.isp.com;
    root /path/to/internet-billing/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    error_log /var/log/nginx/billing-error.log;
    access_log /var/log/nginx/billing-access.log;
}
```

### 7. Konfigurasi Cron Job

Tambahkan cron job berikut untuk menjalankan scheduler Laravel:

```bash
* * * * * cd /path/to/internet-billing && php artisan schedule:run >> /dev/null 2>&1
```

## Konfigurasi Fitur

### 1. Dunning Engine

1. Buat jadwal penagihan melalui panel admin
2. Konfigurasikan langkah-langkah penagihan dengan jenis tindakan yang sesuai
3. Aktifkan jadwal penagihan

### 2. Integrasi Payment Gateway

1. Daftarkan akun di payment gateway yang didukung
2. Dapatkan API key dan secret
3. Konfigurasikan callback URL di dashboard payment gateway
4. Perbarui konfigurasi di file `.env`

### 3. Integrasi WhatsApp

1. Daftarkan akun WhatsApp Business API
2. Buat template pesan yang diperlukan:
   - Template notifikasi tagihan
   - Template pengingat pembayaran
   - Template pembayaran berhasil
   - Template layanan ditangguhkan
3. Dapatkan API token
4. Perbarui konfigurasi di file `.env`

### 4. Integrasi AAA

1. Konfigurasikan koneksi ke server AAA
2. Uji koneksi dengan menjalankan perintah:
   ```bash
   php artisan aaa:test-connection
   ```

### 5. RBAC

1. Buat peran dan izin dasar melalui panel admin
2. Tetapkan peran ke pengguna

## Pengujian

### 1. Pengujian Unit

```bash
# Jalankan pengujian unit
php artisan test --testsuite=Unit
```

### 2. Pengujian Fitur

```bash
# Jalankan pengujian fitur
php artisan test --testsuite=Feature
```

### 3. Pengujian Integrasi

Uji integrasi dengan sistem eksternal:

```bash
# Uji integrasi payment gateway
php artisan test:payment-gateway

# Uji integrasi WhatsApp
php artisan test:whatsapp

# Uji integrasi AAA
php artisan test:aaa
```

## Pemeliharaan

### 1. Backup Database

Konfigurasikan backup database otomatis:

```bash
# Tambahkan ke crontab
0 2 * * * cd /path/to/internet-billing && php artisan backup:run
```

### 2. Pemantauan Log

Pantau log aplikasi secara teratur:

```bash
# Log aplikasi
tail -f storage/logs/laravel.log

# Log aktivitas
php artisan activity:list
```

### 3. Pembaruan Sistem

```bash
# Update kode dari repositori
git pull

# Update dependensi
composer update
npm update

# Jalankan migrasi
php artisan migrate

# Bersihkan cache
php artisan optimize:clear
```

## Troubleshooting

### 1. Masalah Umum

#### Aplikasi Tidak Dapat Diakses
- Periksa konfigurasi web server
- Periksa izin file dan direktori
- Periksa log error web server

#### Error Database
- Periksa koneksi database
- Periksa kredensial database
- Periksa log error database

#### Integrasi Gagal
- Periksa konfigurasi API
- Periksa kredensial API
- Periksa log error aplikasi

### 2. Kontak Dukungan

Jika Anda mengalami masalah yang tidak dapat diselesaikan, hubungi tim dukungan:

- Email: support@isp-billing.com
- Telepon: +62-XXX-XXXXXXX