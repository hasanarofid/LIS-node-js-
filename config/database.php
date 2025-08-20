<?php
// Database configuration for LIS Application

// Database settings
$db_host = 'localhost';
$db_name = 'lis_system';
$db_user = 'root';
$db_pass = 'hasanitki';

// PDO connection
try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Create tables if they don't exist
function createTables($pdo) {
    $tables = [
        // Devices table
        "CREATE TABLE IF NOT EXISTS devices (
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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // Patients table
        "CREATE TABLE IF NOT EXISTS patients (
            id VARCHAR(50) PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            birth_date DATE,
            gender ENUM('Male', 'Female', 'Unknown') DEFAULT 'Unknown',
            address TEXT,
            phone VARCHAR(20),
            email VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // Results table
        "CREATE TABLE IF NOT EXISTS results (
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
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
        )",
        
        // Activities table
        "CREATE TABLE IF NOT EXISTS activities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            device_id INT,
            patient_id VARCHAR(50),
            result_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL,
            FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
            FOREIGN KEY (result_id) REFERENCES results(id) ON DELETE SET NULL
        )",
        
        // Settings table
        "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($tables as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            echo "Error creating table: " . $e->getMessage() . "\n";
        }
    }
    
    // Insert default settings
    $default_settings = [
        ['hl7_port', '6666'],
        ['ws_port', '2222'],
        ['timeout', '30'],
        ['simrs_url', ''],
        ['simrs_api_key', ''],
        ['simrs_endpoint', '/api/patients']
    ];
    
    foreach ($default_settings as $setting) {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute($setting);
        } catch (PDOException $e) {
            // Setting might already exist
        }
    }
}

// Create tables on first run
createTables($pdo);

// Helper function to get setting value
function getSetting($key, $default = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

// Helper function to set setting value
function setSetting($key, $value) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
?> 