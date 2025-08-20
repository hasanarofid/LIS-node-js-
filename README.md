# HL7Server - Laboratory Information System Server

Server HL7 (Health Level 7) untuk sistem informasi laboratorium yang mendukung protokol HL7 untuk komunikasi antar sistem kesehatan.

## Fitur Utama

- **Server HL7 TCP** - Menangani protokol HL7 menggunakan React PHP
- **Server WebSocket** - Monitoring real-time dan komunikasi
- **Bot Telegram** - Kontrol server melalui Telegram
- **Sistem Monitoring** - Monitoring status server dan koneksi
- **PM2 Integration** - Process management untuk production

## Komponen

### Server HL7
- `HL7Server.php` - Server HL7 utama menggunakan React PHP
- `HL7Server.js` - Server HL7 alternatif menggunakan Node.js
- `server.js` - Server WebSocket sederhana

### Monitoring & Control
- `monitoring.js` - Sistem monitoring dengan WebSocket
- `bot.js` - Bot Telegram untuk kontrol server
- `backup_monitoring.js` - Backup sistem monitoring

### Konfigurasi
- `pm2.config.json` - Konfigurasi PM2 untuk production
- `pm2.config_dev.json` - Konfigurasi PM2 untuk development

## Instalasi

### Prerequisites
- Node.js (v14+)
- PHP (v7.4+)
- Composer
- PM2 (untuk production)

### Setup

1. **Clone repository**
```bash
git clone https://github.com/hasanarofid/LIS-node-js-.git
cd LIS-node-js-
```

2. **Install dependencies**
```bash
# Node.js dependencies
npm install

# PHP dependencies
composer install
```

## Cara Menjalankan

### Development Mode

#### Server HL7 PHP
```bash
php HL7Server.php
```

#### Server HL7 Node.js
```bash
node HL7Server.js
```

#### Server WebSocket
```bash
node server.js
```

#### Monitoring
```bash
node monitoring.js
```

### Production Mode

#### Menggunakan PM2
```bash
# Start semua service
pm2 start pm2.config.json

# Monitor status
pm2 status

# View logs
pm2 logs
```

#### Restart Service
```bash
pm2 restart HL7Server_php
pm2 restart monitoring
```

## Port Configuration

- **6666** - Server HL7 TCP
- **2222** - WebSocket Monitoring
- **5678** - HTTP Server

## Bot Telegram Commands

- `/start` - Memulai Server HL7
- `/restart` - Memulai ulang Server HL7
- `/status` - Cek Status Server HL7
- `/h` - Menampilkan bantuan

## Struktur Proyek

```
HL7Server/
├── HL7Server.php          # Server HL7 utama (PHP)
├── HL7Server.js           # Server HL7 alternatif (Node.js)
├── server.js              # Server WebSocket
├── monitoring.js          # Sistem monitoring
├── bot.js                 # Bot Telegram
├── backup_monitoring.js   # Backup monitoring
├── pm2.config.json        # Konfigurasi PM2 production
├── pm2.config_dev.json    # Konfigurasi PM2 development
├── package.json           # Dependencies Node.js
├── composer.json          # Dependencies PHP
└── vendor/                # PHP dependencies
```

## Dependencies

### Node.js
- express
- socket.io
- pm2
- node-telegram-bot-api
- pg (PostgreSQL)
- axios
- node-schedule
- ws

### PHP
- react/socket
- react/http
- cboden/ratchet
- ratchet/pawl

## API Endpoints

### External API
- `https://e-mon.rsudrsoetomo.jatimprov.go.id/api/settings` - Pengaturan server
- `https://e-mon.rsudrsoetomo.jatimprov.go.id/api/getmessage` - Kirim pesan

## Monitoring

Sistem monitoring menyediakan:
- Status real-time server HL7
- Monitoring koneksi klien
- Notifikasi melalui Telegram
- Log aktivitas server

## Troubleshooting

### Port Already in Use
```bash
# Cek port yang digunakan
netstat -tuln | grep :6666

# Kill process yang menggunakan port
sudo kill -9 <PID>
```

### PM2 Issues
```bash
# Reset PM2
pm2 kill
pm2 start pm2.config.json
```

## License

ISC License

## Author

Hasan Arofid 