<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../models/Device.php';
require_once '../models/Result.php';
require_once '../models/Patient.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));

// Remove 'api' from path parts
if ($path_parts[0] === 'api') {
    array_shift($path_parts);
}

$endpoint = $path_parts[0] ?? '';
$action = $path_parts[1] ?? '';

try {
    switch ($endpoint) {
        case 'dashboard':
            handleDashboard($action);
            break;
        case 'devices':
            handleDevices($request_method, $action);
            break;
        case 'results':
            handleResults($request_method, $action);
            break;
        case 'patients':
            handlePatients($request_method, $action);
            break;
        case 'settings':
            handleSettings($request_method, $action);
            break;
        case 'simrs':
            handleSIMRS($request_method, $action);
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleDashboard($action) {
    switch ($action) {
        case 'stats':
            getDashboardStats();
            break;
        case 'activities':
            getRecentActivities();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Action not found']);
    }
}

function getDashboardStats() {
    global $pdo;
    
    try {
        // Get active devices count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM devices WHERE status = 'online'");
        $activeDevices = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Get today's results count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM results WHERE DATE(created_at) = CURDATE()");
        $todayResults = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Get today's patients count
        $stmt = $pdo->query("SELECT COUNT(DISTINCT patient_id) as count FROM results WHERE DATE(created_at) = CURDATE()");
        $todayPatients = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Get pending results count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM results WHERE status = 'pending'");
        $pendingResults = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        echo json_encode([
            'activeDevices' => (int)$activeDevices,
            'todayResults' => (int)$todayResults,
            'todayPatients' => (int)$todayPatients,
            'pendingResults' => (int)$pendingResults
        ]);
    } catch (Exception $e) {
        throw new Exception('Failed to get dashboard stats: ' . $e->getMessage());
    }
}

function getRecentActivities() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT 
                'result' as type,
                CONCAT('Hasil baru dari ', d.name) as title,
                CONCAT('Test: ', r.test_type, ' - Pasien: ', p.name) as description,
                r.created_at as timestamp
            FROM results r
            JOIN devices d ON r.device_id = d.id
            JOIN patients p ON r.patient_id = p.id
            ORDER BY r.created_at DESC
            LIMIT 10
        ");
        
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($activities);
    } catch (Exception $e) {
        throw new Exception('Failed to get recent activities: ' . $e->getMessage());
    }
}

function handleDevices($method, $action) {
    switch ($method) {
        case 'GET':
            getDevices();
            break;
        case 'POST':
            createDevice();
            break;
        case 'PUT':
            updateDevice($action);
            break;
        case 'DELETE':
            deleteDevice($action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function getDevices() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT 
                id, name, type, port, baudrate, databits, stopbits, parity,
                status, last_active, created_at
            FROM devices
            ORDER BY created_at DESC
        ");
        
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($devices);
    } catch (Exception $e) {
        throw new Exception('Failed to get devices: ' . $e->getMessage());
    }
}

function createDevice() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }
    
    $required_fields = ['name', 'type', 'port'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            return;
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO devices (name, type, port, baudrate, databits, stopbits, parity, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'offline')
        ");
        
        $stmt->execute([
            $input['name'],
            $input['type'],
            $input['port'],
            $input['baudrate'] ?? 9600,
            $input['databits'] ?? 8,
            $input['stopbits'] ?? 1,
            $input['parity'] ?? 'none'
        ]);
        
        $device_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Device created successfully',
            'device_id' => $device_id
        ]);
    } catch (Exception $e) {
        throw new Exception('Failed to create device: ' . $e->getMessage());
    }
}

function updateDevice($device_id) {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE devices 
            SET name = ?, type = ?, port = ?, baudrate = ?, databits = ?, stopbits = ?, parity = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $input['name'],
            $input['type'],
            $input['port'],
            $input['baudrate'] ?? 9600,
            $input['databits'] ?? 8,
            $input['stopbits'] ?? 1,
            $input['parity'] ?? 'none',
            $device_id
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Device updated successfully'
        ]);
    } catch (Exception $e) {
        throw new Exception('Failed to update device: ' . $e->getMessage());
    }
}

function deleteDevice($device_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM devices WHERE id = ?");
        $stmt->execute([$device_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Device deleted successfully'
        ]);
    } catch (Exception $e) {
        throw new Exception('Failed to delete device: ' . $e->getMessage());
    }
}

function handleResults($method, $action) {
    switch ($method) {
        case 'GET':
            getResults();
            break;
        case 'POST':
            createResult();
            break;
        case 'PUT':
            updateResult($action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function getResults() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT 
                r.id, r.test_type, r.value, r.unit, r.reference_range, r.status, r.created_at,
                p.name as patient_name, p.id as patient_id,
                d.name as device_name, d.id as device_id
            FROM results r
            JOIN patients p ON r.patient_id = p.id
            JOIN devices d ON r.device_id = d.id
            ORDER BY r.created_at DESC
            LIMIT 100
        ");
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($results);
    } catch (Exception $e) {
        throw new Exception('Failed to get results: ' . $e->getMessage());
    }
}

function createResult() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }
    
    try {
        // First, ensure patient exists or create new one
        $patient_id = ensurePatientExists($input);
        
        // Then, ensure device exists or create new one
        $device_id = ensureDeviceExists($input);
        
        // Create result
        $stmt = $pdo->prepare("
            INSERT INTO results (patient_id, device_id, test_type, value, unit, reference_range, status, raw_data)
            VALUES (?, ?, ?, ?, ?, ?, 'completed', ?)
        ");
        
        $stmt->execute([
            $patient_id,
            $device_id,
            $input['testType'] ?? 'Unknown',
            $input['value'] ?? '',
            $input['unit'] ?? '',
            $input['reference'] ?? '',
            json_encode($input)
        ]);
        
        $result_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Result created successfully',
            'result_id' => $result_id
        ]);
    } catch (Exception $e) {
        throw new Exception('Failed to create result: ' . $e->getMessage());
    }
}

function ensurePatientExists($input) {
    global $pdo;
    
    $patient_id = $input['patientId'] ?? null;
    
    if ($patient_id) {
        // Check if patient exists
        $stmt = $pdo->prepare("SELECT id FROM patients WHERE id = ?");
        $stmt->execute([$patient_id]);
        
        if ($stmt->fetch()) {
            return $patient_id;
        }
    }
    
    // Create new patient
    $stmt = $pdo->prepare("
        INSERT INTO patients (id, name, birth_date, gender)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $patient_id ?? generatePatientId(),
        $input['patientName'] ?? 'Unknown Patient',
        $input['birthDate'] ?? null,
        $input['gender'] ?? 'Unknown'
    ]);
    
    return $pdo->lastInsertId();
}

function ensureDeviceExists($input) {
    global $pdo;
    
    $device_ip = $input['deviceIP'] ?? null;
    
    if ($device_ip) {
        // Check if device exists
        $stmt = $pdo->prepare("SELECT id FROM devices WHERE ip_address = ?");
        $stmt->execute([$device_ip]);
        
        $device = $stmt->fetch();
        if ($device) {
            // Update last active
            $stmt = $pdo->prepare("UPDATE devices SET last_active = NOW(), status = 'online' WHERE id = ?");
            $stmt->execute([$device['id']]);
            return $device['id'];
        }
    }
    
    // Create new device
    $stmt = $pdo->prepare("
        INSERT INTO devices (name, type, ip_address, status, last_active)
        VALUES (?, ?, ?, 'online', NOW())
    ");
    
    $stmt->execute([
        $input['deviceName'] ?? 'Unknown Device',
        $input['deviceType'] ?? 'Unknown',
        $device_ip
    ]);
    
    return $pdo->lastInsertId();
}

function generatePatientId() {
    return 'P' . date('Ymd') . rand(1000, 9999);
}

function handlePatients($method, $action) {
    switch ($method) {
        case 'GET':
            getPatients();
            break;
        case 'POST':
            createPatient();
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function getPatients() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT 
                p.id, p.name, p.birth_date, p.gender, p.created_at,
                COUNT(r.id) as total_tests
            FROM patients p
            LEFT JOIN results r ON p.id = r.patient_id
            GROUP BY p.id
            ORDER BY p.created_at DESC
            LIMIT 100
        ");
        
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($patients);
    } catch (Exception $e) {
        throw new Exception('Failed to get patients: ' . $e->getMessage());
    }
}

function createPatient() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO patients (id, name, birth_date, gender)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['id'] ?? generatePatientId(),
            $input['name'],
            $input['birthDate'] ?? null,
            $input['gender'] ?? 'Unknown'
        ]);
        
        $patient_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Patient created successfully',
            'patient_id' => $patient_id
        ]);
    } catch (Exception $e) {
        throw new Exception('Failed to create patient: ' . $e->getMessage());
    }
}

function handleSettings($method, $action) {
    switch ($method) {
        case 'GET':
            getSettings();
            break;
        case 'POST':
            saveSettings();
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function getSettings() {
    // Load settings from config file or database
    $settings = [
        'hl7Port' => 6666,
        'wsPort' => 2222,
        'timeout' => 30,
        'simrsUrl' => '',
        'simrsApiKey' => '',
        'simrsEndpoint' => ''
    ];
    
    echo json_encode($settings);
}

function saveSettings() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }
    
    try {
        // Save settings to config file or database
        $config_file = '../config/settings.json';
        file_put_contents($config_file, json_encode($input, JSON_PRETTY_PRINT));
        
        echo json_encode([
            'success' => true,
            'message' => 'Settings saved successfully'
        ]);
    } catch (Exception $e) {
        throw new Exception('Failed to save settings: ' . $e->getMessage());
    }
}

function handleSIMRS($method, $action) {
    switch ($action) {
        case 'test':
            testSIMRSConnection();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Action not found']);
    }
}

function testSIMRSConnection() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }
    
    try {
        $url = $input['url'] ?? '';
        $api_key = $input['apiKey'] ?? '';
        $endpoint = $input['endpoint'] ?? '';
        
        if (empty($url)) {
            throw new Exception('SIMRS URL is required');
        }
        
        // Test connection to SIMRS
        $test_url = rtrim($url, '/') . '/' . ltrim($endpoint, '/');
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $test_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            echo json_encode([
                'success' => true,
                'message' => 'SIMRS connection successful'
            ]);
        } else {
            throw new Exception("SIMRS connection failed with HTTP code: $http_code");
        }
    } catch (Exception $e) {
        throw new Exception('Failed to test SIMRS connection: ' . $e->getMessage());
    }
}
?> 