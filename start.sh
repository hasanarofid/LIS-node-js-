#!/bin/bash

# HL7Server Startup Script
# Script untuk menjalankan semua komponen HL7Server

echo "=========================================="
echo "    HL7Server - Laboratory Information System"
echo "=========================================="

# Fungsi untuk mengecek apakah port sudah digunakan
check_port() {
    local port=$1
    # Gunakan ss sebagai alternatif netstat
    if command -v ss &> /dev/null; then
        if ss -tuln | grep -q ":$port "; then
            echo "⚠️  Port $port sudah digunakan!"
            return 1
        else
            echo "✅ Port $port tersedia"
            return 0
        fi
    elif command -v netstat &> /dev/null; then
        if netstat -tuln | grep -q ":$port "; then
            echo "⚠️  Port $port sudah digunakan!"
            return 1
        else
            echo "✅ Port $port tersedia"
            return 0
        fi
    else
        echo "⚠️  Tidak bisa mengecek port (ss/netstat tidak tersedia)"
        return 0
    fi
}

# Fungsi untuk mengecek dependencies
check_dependencies() {
    echo "🔍 Mengecek dependencies..."
    
    if ! command -v node &> /dev/null; then
        echo "❌ Node.js tidak ditemukan. Silakan install Node.js terlebih dahulu."
        exit 1
    fi
    
    if ! command -v php &> /dev/null; then
        echo "❌ PHP tidak ditemukan. Silakan install PHP terlebih dahulu."
        exit 1
    fi
    
    if ! command -v composer &> /dev/null; then
        echo "❌ Composer tidak ditemukan. Silakan install Composer terlebih dahulu."
        exit 1
    fi
    
    if ! command -v pm2 &> /dev/null; then
        echo "⚠️  PM2 tidak ditemukan. Install dengan: npm install -g pm2"
    fi
    
    echo "✅ Semua dependencies tersedia"
}

# Fungsi untuk install dependencies
install_dependencies() {
    echo "📦 Installing dependencies..."
    
    if [ ! -d "node_modules" ]; then
        echo "Installing Node.js dependencies..."
        npm install
    fi
    
    if [ ! -d "vendor" ]; then
        echo "Installing PHP dependencies..."
        composer install
    fi
    
    echo "✅ Dependencies berhasil diinstall"
}

# Fungsi untuk menjalankan dalam mode development
start_dev() {
    echo "🚀 Menjalankan dalam mode development..."
    
    # Hentikan process yang mungkin masih berjalan
    echo "🛑 Menghentikan process yang mungkin masih berjalan..."
    pkill -f "HL7Server.php" 2>/dev/null
    pkill -f "monitoring.js" 2>/dev/null
    pkill -f "bot.js" 2>/dev/null
    sleep 2
    
    # Cek port
    check_port 6666 || exit 1
    check_port 2222 || exit 1
    check_port 5678 || exit 1
    
    echo "Starting HL7Server PHP..."
    php HL7Server.php &
    PHP_PID=$!
    
    echo "Starting monitoring..."
    node monitoring.js &
    MONITORING_PID=$!
    
    echo "Starting bot..."
    node bot.js &
    BOT_PID=$!
    
    echo "✅ Semua service berhasil dijalankan"
    echo "📊 PID: PHP=$PHP_PID, Monitoring=$MONITORING_PID, Bot=$BOT_PID"
    echo "🛑 Tekan Ctrl+C untuk menghentikan semua service"
    
    # Trap untuk cleanup saat script dihentikan
    trap 'echo "🛑 Menghentikan semua service..."; kill $PHP_PID $MONITORING_PID $BOT_PID 2>/dev/null; exit' INT
    
    # Tunggu sampai semua process selesai
    wait
}

# Fungsi untuk menjalankan dengan PM2
start_pm2() {
    echo "🚀 Menjalankan dengan PM2..."
    
    if command -v pm2 &> /dev/null; then
        pm2 start pm2.config.json
        echo "✅ Service berhasil dijalankan dengan PM2"
        echo "📊 Status: pm2 status"
        echo "📋 Logs: pm2 logs"
    else
        echo "❌ PM2 tidak ditemukan. Jalankan dalam mode development..."
        start_dev
    fi
}

# Fungsi untuk menampilkan status
show_status() {
    echo "📊 Status Service:"
    
    if command -v pm2 &> /dev/null; then
        pm2 status
    fi
    
    echo ""
    echo "🔌 Port Status:"
    if command -v ss &> /dev/null; then
        ss -tuln | grep -E ":(6666|2222|5678)" || echo "Tidak ada service yang berjalan"
    elif command -v netstat &> /dev/null; then
        netstat -tuln | grep -E ":(6666|2222|5678)" || echo "Tidak ada service yang berjalan"
    else
        echo "Tidak bisa mengecek port (ss/netstat tidak tersedia)"
    fi
}

# Fungsi untuk menghentikan semua service
stop_all() {
    echo "🛑 Menghentikan semua service..."
    
    if command -v pm2 &> /dev/null; then
        pm2 stop all
        pm2 delete all
    fi
    
    # Kill process yang menggunakan port
    pkill -f "HL7Server.php" 2>/dev/null
    pkill -f "monitoring.js" 2>/dev/null
    pkill -f "bot.js" 2>/dev/null
    
    echo "✅ Semua service berhasil dihentikan"
}

# Main script
case "$1" in
    "dev")
        check_dependencies
        install_dependencies
        start_dev
        ;;
    "prod")
        check_dependencies
        install_dependencies
        start_pm2
        ;;
    "status")
        show_status
        ;;
    "stop")
        stop_all
        ;;
    "install")
        check_dependencies
        install_dependencies
        ;;
    *)
        echo "Usage: $0 {dev|prod|status|stop|install}"
        echo ""
        echo "Commands:"
        echo "  dev     - Jalankan dalam mode development"
        echo "  prod    - Jalankan dengan PM2 (production)"
        echo "  status  - Tampilkan status service"
        echo "  stop    - Hentikan semua service"
        echo "  install - Install dependencies"
        echo ""
        echo "Contoh:"
        echo "  $0 dev    # Jalankan development mode"
        echo "  $0 prod   # Jalankan production mode"
        echo "  $0 status # Cek status"
        exit 1
        ;;
esac 