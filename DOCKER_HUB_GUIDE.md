# 🐳 Panduan Upload ke Docker Hub

## Prerequisites

1. **Docker Desktop** sudah terinstall dan berjalan
2. **Akun Docker Hub** sudah dibuat
3. **Repository** sudah dibuat di Docker Hub

## Langkah-langkah Upload

### 1. Persiapan

```bash
# Pastikan Docker berjalan
docker --version
docker info

# Jika Docker tidak berjalan, jalankan Docker Desktop
```

### 2. Konfigurasi Script

Edit file `docker-upload.sh` dan isi:

```bash
DOCKER_HUB_USERNAME="yourusername"  # Ganti dengan username Docker Hub Anda
DOCKER_HUB_REPOSITORY="internet-management-system"  # Ganti dengan nama repository
```

### 3. Jalankan Script

```bash
# Berikan permission execute
chmod +x docker-upload.sh

# Jalankan script
bash docker-upload.sh
```

### 4. Manual Upload (Alternatif)

Jika script tidak berfungsi, lakukan manual:

```bash
# 1. Build image
docker build -t internet-management-system:latest .

# 2. Tag untuk Docker Hub
docker tag internet-management-system:latest yourusername/internet-management-system:latest

# 3. Login ke Docker Hub
docker login

# 4. Push image
docker push yourusername/internet-management-system:latest
```

## Konfigurasi Repository Docker Hub

### 1. Buat Repository di Docker Hub

1. Login ke [Docker Hub](https://hub.docker.com)
2. Klik "Create Repository"
3. Isi detail:
   - **Repository Name**: `internet-management-system`
   - **Description**: `Laravel-based Internet Management System`
   - **Visibility**: Public atau Private
   - **Build Settings**: Skip (kita akan push manual)

### 2. Upload README

1. Copy isi file `DOCKER_HUB_README.md`
2. Paste ke bagian "Full Description" di Docker Hub
3. Save changes

## Verifikasi Upload

### 1. Cek di Docker Hub

- Buka: `https://hub.docker.com/r/yourusername/internet-management-system`
- Pastikan image muncul dengan tag `latest`

### 2. Test Pull Image

```bash
# Test pull dari Docker Hub
docker pull yourusername/internet-management-system:latest

# Test run
docker run -p 8000:80 yourusername/internet-management-system:latest
```

## Troubleshooting

### Error: Docker tidak berjalan

```bash
# Jalankan Docker Desktop
# Atau restart Docker service
sudo systemctl restart docker  # Linux
```

### Error: Login gagal

```bash
# Coba login ulang
docker logout
docker login

# Pastikan username dan password benar
```

### Error: Push gagal

```bash
# Cek apakah repository sudah dibuat
# Pastikan nama repository benar
# Cek koneksi internet
```

### Error: Permission denied

```bash
# Pastikan user dalam group docker
sudo usermod -aG docker $USER
# Logout dan login ulang
```

## Production Deployment

### 1. Multi-arch Build (Opsional)

```bash
# Install buildx
docker buildx create --name multiarch --use

# Build untuk multiple architecture
docker buildx build --platform linux/amd64,linux/arm64 -t yourusername/internet-management-system:latest --push .
```

### 2. Automated Build (Opsional)

1. Connect GitHub repository ke Docker Hub
2. Enable automated builds
3. Set build rules untuk branches/tags

## Monitoring Upload

### 1. Cek Image Size

```bash
docker images yourusername/internet-management-system
```

### 2. Cek Image Details

```bash
docker inspect yourusername/internet-management-system:latest
```

### 3. Test Image

```bash
# Test dengan environment variables
docker run -d \
  --name test-app \
  -p 8000:80 \
  -e APP_ENV=production \
  -e DB_HOST=localhost \
  yourusername/internet-management-system:latest
```

## Best Practices

### 1. Tagging Strategy

```bash
# Tag dengan version
docker tag internet-management-system:latest yourusername/internet-management-system:v1.0.0

# Tag dengan date
docker tag internet-management-system:latest yourusername/internet-management-system:2024-01-15

# Push multiple tags
docker push yourusername/internet-management-system:latest
docker push yourusername/internet-management-system:v1.0.0
```

### 2. Security

```bash
# Scan image untuk vulnerabilities
docker scan yourusername/internet-management-system:latest

# Use specific tags instead of latest
docker pull yourusername/internet-management-system:v1.0.0
```

### 3. Documentation

- Update README di Docker Hub
- Tambahkan usage examples
- Dokumentasikan environment variables
- Tambahkan troubleshooting guide

## Next Steps

Setelah berhasil upload:

1. **Share Repository**: Bagikan link Docker Hub
2. **Documentation**: Update dokumentasi dengan Docker Hub info
3. **CI/CD**: Setup automated builds
4. **Monitoring**: Setup monitoring untuk image usage
5. **Updates**: Plan untuk regular updates

## Support

Jika mengalami masalah:

1. Cek Docker logs: `docker logs container_name`
2. Cek Docker Hub status
3. Cek network connectivity
4. Cek Docker Hub documentation

---

**Happy Dockerizing! 🐳**
