#!/bin/bash

# LIS Application Startup Script
# Script untuk menjalankan aplikasi LIS lengkap dengan GUI dan backend

echo "=========================================="
echo "    LIS - Laboratory Information System"
echo "    Modern Web Application"
echo "=========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check dependencies
check_dependencies() {
    print_status "Checking dependencies..."
    
    local missing_deps=()
    
    # Check Node.js
    if ! command_exists node; then
        missing_deps+=("Node.js")
    else
        NODE_VERSION=$(node --version)
        print_success "Node.js found: $NODE_VERSION"
    fi
    
    # Check PHP
    if ! command_exists php; then
        missing_deps+=("PHP")
    else
        PHP_VERSION=$(php --version | head -n1)
        print_success "PHP found: $PHP_VERSION"
    fi
    
    # Check Composer
    if ! command_exists composer; then
        missing_deps+=("Composer")
    else
        print_success "Composer found"
    fi
    
    # Check MySQL/MariaDB
    if ! command_exists mysql; then
        print_warning "MySQL/MariaDB not found - you'll need to install it separately"
    else
        print_success "MySQL/MariaDB found"
    fi
    
    # Check PM2
    if ! command_exists pm2; then
        print_warning "PM2 not found - will install globally"
    else
        print_success "PM2 found"
    fi
    
    # Install missing dependencies
    if [ ${#missing_deps[@]} -ne 0 ]; then
        print_error "Missing dependencies: ${missing_deps[*]}"
        print_status "Please install the missing dependencies and run this script again"
        exit 1
    fi
}

# Function to install PM2 if not present
install_pm2() {
    if ! command_exists pm2; then
        print_status "Installing PM2 globally..."
        npm install -g pm2
        if [ $? -eq 0 ]; then
            print_success "PM2 installed successfully"
        else
            print_error "Failed to install PM2"
            exit 1
        fi
    fi
}

# Function to setup database
setup_database() {
    print_status "Setting up database..."
    
    # Check if MySQL is running
    if command_exists mysql; then
        # Try to connect to MySQL
        if mysql -u root -phasanitki -e "SELECT 1;" >/dev/null 2>&1; then
            print_success "MySQL connection successful"
            
            # Create database if it doesn't exist
            mysql -u root -phasanitki -e "CREATE DATABASE IF NOT EXISTS lis_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
            if [ $? -eq 0 ]; then
                print_success "Database 'lis_system' created/verified"
            else
                print_error "Failed to create database"
                exit 1
            fi
        else
            print_warning "Cannot connect to MySQL. Please ensure MySQL is running and accessible"
            print_status "You may need to:"
            print_status "1. Start MySQL service: sudo systemctl start mysql"
            print_status "2. Set MySQL root password: sudo mysql_secure_installation"
            print_status "3. Update database credentials in config/database.php"
        fi
    else
        print_warning "MySQL not found. Please install MySQL/MariaDB and configure it"
    fi
}

# Function to install dependencies
install_dependencies() {
    print_status "Installing dependencies..."
    
    # Install Node.js dependencies
    if [ -f "package.json" ]; then
        print_status "Installing Node.js dependencies..."
        npm install
        if [ $? -eq 0 ]; then
            print_success "Node.js dependencies installed"
        else
            print_error "Failed to install Node.js dependencies"
            exit 1
        fi
    fi
    
    # Install PHP dependencies
    if [ -f "composer.json" ]; then
        print_status "Installing PHP dependencies..."
        composer install --no-dev --optimize-autoloader
        if [ $? -eq 0 ]; then
            print_success "PHP dependencies installed"
        else
            print_error "Failed to install PHP dependencies"
            exit 1
        fi
    fi
}

# Function to start services
start_services() {
    print_status "Starting LIS services..."
    
    # Stop any existing services
    stop_services
    
    # Start HL7 Server (PHP)
    print_status "Starting HL7 Server..."
    pm2 start HL7Server.php --name "HL7Server_php" --interpreter php
    
    # Start Monitoring
    print_status "Starting Monitoring Service..."
    pm2 start monitoring.js --name "LIS_Monitoring"
    
    # Start Bot (if exists)
    if [ -f "bot.js" ]; then
        print_status "Starting Telegram Bot..."
        pm2 start bot.js --name "LIS_Bot"
    fi
    
    # Start Web Server (PHP built-in server for development)
    print_status "Starting Web Server..."
    pm2 start "php -S localhost:8080 -t public" --name "LIS_WebServer"
    
    print_success "All services started successfully"
}

# Function to stop services
stop_services() {
    print_status "Stopping existing services..."
    
    # Stop PM2 processes
    pm2 stop all 2>/dev/null
    pm2 delete all 2>/dev/null
    
    # Kill any remaining processes
    pkill -f "HL7Server.php" 2>/dev/null
    pkill -f "monitoring.js" 2>/dev/null
    pkill -f "bot.js" 2>/dev/null
    pkill -f "php -S" 2>/dev/null
    
    print_success "All services stopped"
}

# Function to show status
show_status() {
    print_status "Service Status:"
    pm2 status
    
    echo ""
    print_status "Port Status:"
    if command_exists ss; then
        ss -tuln | grep -E ":(6666|2222|8080)" || echo "No services running on expected ports"
    elif command_exists netstat; then
        netstat -tuln | grep -E ":(6666|2222|8080)" || echo "No services running on expected ports"
    else
        echo "Cannot check port status (ss/netstat not available)"
    fi
}

# Function to show logs
show_logs() {
    print_status "Recent logs:"
    pm2 logs --lines 20
}

# Function to open web interface
open_web_interface() {
    print_status "Opening web interface..."
    
    if command_exists xdg-open; then
        xdg-open "http://localhost:8080"
    elif command_exists open; then
        open "http://localhost:8080"
    else
        print_status "Please open your browser and navigate to: http://localhost:8080"
    fi
}

# Function to create sample data
create_sample_data() {
    print_status "Creating sample data..."
    
    # Create sample devices
    mysql -u root -phasanitki lis_system -e "
    INSERT IGNORE INTO devices (name, type, port, baudrate, status) VALUES 
    ('Sysmex XN-1000', 'sysmex', 'COM1', 9600, 'online'),
    ('RS232 Analyzer', 'rs232', 'COM2', 19200, 'online');
    " 2>/dev/null
    
    # Create sample patients
    mysql -u root -phasanitki lis_system -e "
    INSERT IGNORE INTO patients (id, name, birth_date, gender) VALUES 
    ('P20241201001', 'John Doe', '1985-03-15', 'Male'),
    ('P20241201002', 'Jane Smith', '1990-07-22', 'Female');
    " 2>/dev/null
    
    print_success "Sample data created"
}

# Function to show help
show_help() {
    echo "Usage: $0 [COMMAND]"
    echo ""
    echo "Commands:"
    echo "  start     - Start all LIS services"
    echo "  stop      - Stop all LIS services"
    echo "  restart   - Restart all LIS services"
    echo "  status    - Show service status"
    echo "  logs      - Show recent logs"
    echo "  setup     - Setup database and dependencies"
    echo "  sample    - Create sample data"
    echo "  web       - Open web interface"
    echo "  help      - Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 start     # Start all services"
    echo "  $0 status    # Check service status"
    echo "  $0 web       # Open web interface"
}

# Main script logic
case "$1" in
    "start")
        check_dependencies
        install_pm2
        setup_database
        install_dependencies
        start_services
        print_success "LIS Application started successfully!"
        print_status "Web interface: http://localhost:8080"
        print_status "HL7 Server: localhost:6666"
        print_status "WebSocket: localhost:2222"
        ;;
    "stop")
        stop_services
        print_success "LIS Application stopped"
        ;;
    "restart")
        stop_services
        sleep 2
        start_services
        print_success "LIS Application restarted"
        ;;
    "status")
        show_status
        ;;
    "logs")
        show_logs
        ;;
    "setup")
        check_dependencies
        install_pm2
        setup_database
        install_dependencies
        print_success "Setup completed"
        ;;
    "sample")
        create_sample_data
        ;;
    "web")
        open_web_interface
        ;;
    "help"|"")
        show_help
        ;;
    *)
        print_error "Unknown command: $1"
        echo ""
        show_help
        exit 1
        ;;
esac 