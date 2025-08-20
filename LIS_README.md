# 🏥 LIS - Laboratory Information System

Aplikasi **Laboratory Information System (LIS)** modern dengan GUI yang cantik untuk mengkoneksikan sistem dengan alat laboratorium dan menyimpan data hasil lab.

## 🌟 Fitur Utama

### 🔧 **Manajemen Perangkat**
- **Sysmex Integration** - Koneksi dengan alat Sysmex
- **RS232 Support** - Koneksi serial untuk berbagai alat
- **Real-time Monitoring** - Status perangkat real-time
- **Device Configuration** - Konfigurasi port, baudrate, dll

### 📊 **Dashboard Modern**
- **Real-time Statistics** - Statistik perangkat aktif, hasil hari ini
- **Interactive Charts** - Grafik hasil lab dengan Chart.js
- **Activity Feed** - Aktivitas terbaru sistem
- **Device Status** - Status perangkat real-time

### 🧪 **Manajemen Hasil Lab**
- **HL7 Protocol** - Parsing dan parsing data HL7
- **Result Management** - CRUD operasi hasil lab
- **Search & Filter** - Pencarian dan filter hasil
- **Export Function** - Export ke CSV/Excel
- **Print Support** - Cetak hasil lab

### 👥 **Manajemen Pasien**
- **Patient Database** - Database pasien terintegrasi
- **SIMRS Integration** - Koneksi dengan SIMRS via API
- **Patient Search** - Pencarian data pasien

### 📈 **Laporan & Analytics**
- **Daily Reports** - Laporan harian
- **Monthly Reports** - Laporan bulanan
- **Custom Reports** - Laporan kustom
- **Data Analytics** - Analisis data hasil lab

### 🔐 **Sistem & Keamanan**
- **User Management** - Manajemen pengguna
- **Role-based Access** - Akses berbasis role
- **Audit Trail** - Log aktivitas sistem
- **Backup & Recovery** - Backup dan restore data

## 🚀 Cara Menjalankan

### **Prerequisites**
```bash
# Install dependencies
sudo apt update
sudo apt install nodejs npm php mysql-server composer
```

### **Quick Start**
```bash
# Clone repository
git clone https://github.com/hasanarofid/LIS-node-js-.git
cd LIS-node-js-

# Setup dan jalankan aplikasi
./start_lis.sh setup
./start_lis.sh start
```

### **Manual Setup**
```bash
# 1. Install dependencies
npm install
composer install

# 2. Setup database
mysql -u root -e "CREATE DATABASE lis_system;"

# 3. Start services
pm2 start HL7Server.php --name "HL7Server_php" --interpreter php
pm2 start monitoring.js --name "LIS_Monitoring"
pm2 start "php -S localhost:8080 -t public" --name "LIS_WebServer"
```

## 📁 Struktur Proyek

```
LIS-node-js-/
├── 📁 public/                    # Web Interface
│   ├── 📄 index.html            # Main application
│   ├── 📁 assets/
│   │   ├── 📁 css/
│   │   │   └── 📄 style.css     # Modern CSS styling
│   │   └── 📁 js/
│   │       ├── 📄 app.js        # Main application logic
│   │       ├── 📄 dashboard.js  # Dashboard functionality
│   │       ├── 📄 devices.js    # Device management
│   │       └── 📄 results.js    # Results management
│   └── 📄 .htaccess             # URL rewriting
├── 📁 api/                       # Backend API
│   └── 📄 index.php             # REST API endpoints
├── 📁 config/                    # Configuration
│   └── 📄 database.php          # Database configuration
├── 📄 HL7Server.php             # HL7 Server (PHP)
├── 📄 monitoring.js             # Monitoring service
├── 📄 bot.js                    # Telegram bot
├── 📄 start_lis.sh              # Startup script
└── 📄 README.md                 # Documentation
```

## 🔌 Port Configuration

| Service | Port | Description |
|---------|------|-------------|
| **Web Interface** | 8080 | Aplikasi web utama |
| **HL7 Server** | 6666 | Server HL7 untuk alat |
| **WebSocket** | 2222 | Real-time monitoring |
| **HTTP API** | 8080 | REST API endpoints |

## 🗄️ Database Schema

### **Devices Table**
```sql
CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100) NOT NULL,
    port VARCHAR(50),
    baudrate INT DEFAULT 9600,
    databits INT DEFAULT 8,
    stopbits INT DEFAULT 1,
    parity VARCHAR(10) DEFAULT 'none',
    ip_address VARCHAR(45),
    status ENUM('online', 'offline', 'error') DEFAULT 'offline',
    last_active TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### **Patients Table**
```sql
CREATE TABLE patients (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    birth_date DATE,
    gender ENUM('Male', 'Female', 'Unknown') DEFAULT 'Unknown',
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### **Results Table**
```sql
CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(50) NOT NULL,
    device_id INT NOT NULL,
    test_type VARCHAR(255) NOT NULL,
    value TEXT,
    unit VARCHAR(50),
    reference_range VARCHAR(255),
    status ENUM('pending', 'completed', 'error') DEFAULT 'pending',
    raw_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (device_id) REFERENCES devices(id)
);
```

## 🔧 Konfigurasi Perangkat

### **Sysmex Configuration**
```javascript
{
    "name": "Sysmex XN-1000",
    "type": "sysmex",
    "port": "COM1",
    "baudrate": 9600,
    "databits": 8,
    "stopbits": 1,
    "parity": "none"
}
```

### **RS232 Configuration**
```javascript
{
    "name": "RS232 Analyzer",
    "type": "rs232",
    "port": "COM2",
    "baudrate": 19200,
    "databits": 8,
    "stopbits": 1,
    "parity": "none"
}
```

## 📡 API Endpoints

### **Dashboard**
- `GET /api/dashboard/stats` - Get dashboard statistics
- `GET /api/dashboard/activities` - Get recent activities
- `GET /api/dashboard/chart` - Get chart data

### **Devices**
- `GET /api/devices` - Get all devices
- `POST /api/devices` - Create new device
- `PUT /api/devices/{id}` - Update device
- `DELETE /api/devices/{id}` - Delete device

### **Results**
- `GET /api/results` - Get all results
- `POST /api/results` - Create new result
- `PUT /api/results/{id}` - Update result
- `DELETE /api/results/{id}` - Delete result
- `GET /api/results/export` - Export results

### **Patients**
- `GET /api/patients` - Get all patients
- `POST /api/patients` - Create new patient

### **Settings**
- `GET /api/settings` - Get system settings
- `POST /api/settings` - Save system settings

## 🎨 UI Components

### **Modern Design**
- **Responsive Layout** - Works on desktop, tablet, mobile
- **Dark/Light Theme** - Toggle between themes
- **Interactive Elements** - Hover effects, animations
- **Toast Notifications** - Real-time feedback

### **Dashboard Widgets**
- **Statistics Cards** - Key metrics with animations
- **Device Status** - Real-time device monitoring
- **Activity Feed** - Recent system activities
- **Charts** - Interactive data visualization

### **Data Tables**
- **Sortable Columns** - Click to sort
- **Search & Filter** - Real-time search
- **Pagination** - Navigate through data
- **Bulk Actions** - Select multiple items

## 🔒 Security Features

### **Authentication & Authorization**
- **User Authentication** - Login/logout system
- **Role-based Access** - Different permissions per role
- **Session Management** - Secure session handling

### **Data Protection**
- **Input Validation** - Sanitize all inputs
- **SQL Injection Prevention** - Prepared statements
- **XSS Protection** - Output encoding
- **CSRF Protection** - Cross-site request forgery prevention

## 📊 Monitoring & Logging

### **System Monitoring**
- **Real-time Status** - Device and service status
- **Performance Metrics** - CPU, memory, disk usage
- **Error Tracking** - Log and track errors
- **Alert System** - Notifications for issues

### **Audit Trail**
- **User Actions** - Log all user activities
- **Data Changes** - Track data modifications
- **System Events** - Log system events
- **Export Logs** - Export audit logs

## 🚀 Deployment

### **Development**
```bash
# Start development server
./start_lis.sh start

# Access web interface
http://localhost:8080
```

### **Production**
```bash
# Setup production environment
./start_lis.sh setup

# Start production services
pm2 start ecosystem.config.js

# Setup auto-start
pm2 startup
pm2 save
```

### **Docker Deployment**
```bash
# Build Docker image
docker build -t lis-app .

# Run container
docker run -d -p 8080:8080 -p 6666:6666 -p 2222:2222 lis-app
```

## 🛠️ Troubleshooting

### **Common Issues**

#### **Port Already in Use**
```bash
# Check port usage
sudo netstat -tulpn | grep :6666

# Kill process using port
sudo kill -9 <PID>
```

#### **Database Connection Error**
```bash
# Check MySQL status
sudo systemctl status mysql

# Start MySQL if stopped
sudo systemctl start mysql
```

#### **Permission Issues**
```bash
# Fix file permissions
chmod +x start_lis.sh
chmod 755 public/
chmod 644 config/database.php
```

### **Log Files**
```bash
# View PM2 logs
pm2 logs

# View specific service logs
pm2 logs HL7Server_php
pm2 logs LIS_Monitoring
```

## 📞 Support

### **Documentation**
- **API Documentation** - `/api/docs`
- **User Manual** - `/docs/user-manual.pdf`
- **Developer Guide** - `/docs/developer-guide.md`

### **Contact**
- **Email**: support@lis-system.com
- **Telegram**: @LIS_Support_Bot
- **GitHub Issues**: [Create Issue](https://github.com/hasanarofid/LIS-node-js-/issues)

## 🤝 Contributing

1. **Fork** the repository
2. **Create** a feature branch
3. **Commit** your changes
4. **Push** to the branch
5. **Create** a Pull Request

## 📄 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **React PHP** - For async PHP server
- **Chart.js** - For data visualization
- **Font Awesome** - For icons
- **Inter Font** - For typography

---

**Made with ❤️ for better healthcare systems** 