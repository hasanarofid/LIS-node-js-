# Troubleshooting HL7Server

Dokumentasi untuk mengatasi masalah umum pada HL7Server.

## Masalah Umum

### 1. Port Sudah Digunakan

**Gejala:**
```
Error: listen EADDRINUSE: address already in use :::6666
```

**Solusi:**
```bash
# Cek process yang menggunakan port
sudo netstat -tulpn | grep :6666

# Kill process
sudo kill -9 <PID>

# Atau gunakan script
./start.sh stop
```

### 2. Dependencies Tidak Terinstall

**Gejala:**
```
Error: Cannot find module 'express'
```

**Solusi:**
```bash
# Install Node.js dependencies
npm install

# Install PHP dependencies
composer install

# Atau gunakan script
./start.sh install
```

### 3. PM2 Tidak Ditemukan

**Gejala:**
```
pm2: command not found
```

**Solusi:**
```bash
# Install PM2 secara global
npm install -g pm2

# Verifikasi instalasi
pm2 --version
```

### 4. Permission Denied

**Gejala:**
```
Error: EACCES: permission denied
```

**Solusi:**
```bash
# Berikan permission execute pada script
chmod +x start.sh

# Jalankan dengan sudo jika diperlukan
sudo ./start.sh dev
```

### 5. PHP Extension Missing

**Gejala:**
```
Fatal error: Class 'React\EventLoop\Factory' not found
```

**Solusi:**
```bash
# Install PHP extensions yang diperlukan
sudo apt-get install php-curl php-json php-mbstring

# Restart PHP service
sudo systemctl restart php8.1-fpm  # Sesuaikan versi PHP
```

### 6. Database Connection Error

**Gejala:**
```
Error: connect ECONNREFUSED 127.0.0.1:5432
```

**Solusi:**
```bash
# Cek status PostgreSQL
sudo systemctl status postgresql

# Start PostgreSQL jika tidak berjalan
sudo systemctl start postgresql

# Cek konfigurasi database di file monitoring.js
```

### 7. Telegram Bot Tidak Berfungsi

**Gejala:**
```
Bot tidak merespon command
```

**Solusi:**
1. Cek token bot di file `monitoring.js`
2. Pastikan bot sudah di-start dengan BotFather
3. Cek chat ID yang diizinkan
4. Restart bot service

### 8. Memory Issues

**Gejala:**
```
FATAL ERROR: Ineffective mark-compacts near heap limit
```

**Solusi:**
```bash
# Tambahkan memory limit untuk Node.js
export NODE_OPTIONS="--max-old-space-size=4096"

# Atau restart dengan PM2
pm2 restart all
```

### 9. Log Files Terlalu Besar

**Gejala:**
```
Disk space full
```

**Solusi:**
```bash
# Rotate log files
pm2 install pm2-logrotate

# Atau manual cleanup
pm2 flush
rm -rf ~/.pm2/logs/*
```

### 10. Service Tidak Start Otomatis

**Gejala:**
```
Service tidak start setelah reboot
```

**Solusi:**
```bash
# Setup PM2 startup
pm2 startup
pm2 save

# Atau tambahkan ke systemd
sudo systemctl enable pm2-hasanarofid
```

## Debug Mode

### Enable Debug Logging

```bash
# Node.js debug
DEBUG=* node monitoring.js

# PHP debug
php -d display_errors=1 -d error_reporting=E_ALL HL7Server.php
```

### Check Logs

```bash
# PM2 logs
pm2 logs

# Specific service logs
pm2 logs HL7Server_php
pm2 logs monitoring

# Real-time logs
pm2 logs --lines 100 --follow
```

## Performance Monitoring

### Check Resource Usage

```bash
# CPU dan Memory usage
pm2 monit

# System resources
htop
iotop

# Network connections
netstat -tuln | grep -E ":(6666|2222|5678)"
```

### Optimize Performance

```bash
# Increase Node.js memory
export NODE_OPTIONS="--max-old-space-size=8192"

# Optimize PHP settings
# Edit php.ini
memory_limit = 512M
max_execution_time = 300
```

## Backup dan Recovery

### Backup Configuration

```bash
# Backup config files
tar -czf hl7server_backup_$(date +%Y%m%d).tar.gz \
    pm2.config.json \
    package.json \
    composer.json \
    *.php \
    *.js
```

### Restore Configuration

```bash
# Extract backup
tar -xzf hl7server_backup_YYYYMMDD.tar.gz

# Reinstall dependencies
npm install
composer install

# Restart services
pm2 restart all
```

## Support

Jika masalah masih berlanjut:

1. Cek log files untuk error detail
2. Pastikan semua dependencies terinstall dengan benar
3. Verifikasi konfigurasi network dan firewall
4. Cek dokumentasi React PHP dan Node.js
5. Buat issue di repository GitHub

## Quick Commands

```bash
# Restart semua service
./start.sh stop && ./start.sh prod

# Check status
./start.sh status

# View logs
pm2 logs --lines 50

# Kill semua process
pkill -f "HL7Server\|monitoring\|bot"

# Clean install
rm -rf node_modules vendor && npm install && composer install
``` 