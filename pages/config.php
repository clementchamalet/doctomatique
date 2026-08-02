<?php
if (defined('APP_CONFIG_BOOTSTRAPPED')) {
    return;
}

define('APP_CONFIG_BOOTSTRAPPED', true);

/**
 * Database configuration
 * Supports two connection systems:
 * - MySQLi (legacy functions: get_db())
 * - PDO (newer functions: getDBConnection())
 */

// Database configuration
$defaultConfig = [
    'host' => '127.0.0.1',
    'name' => 'Doctomatique',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
    'timezone' => 'Europe/Paris',
];

$envConfig = [
    'host' => getenv('DB_HOST') ?: null,
    'name' => getenv('DB_NAME') ?: null,
    'user' => getenv('DB_USER') ?: null,
    'pass' => getenv('DB_PASS') ?: null,
    'charset' => getenv('DB_CHARSET') ?: null,
    'timezone' => getenv('APP_TIMEZONE') ?: null,
];

$envConfig = array_filter(
    $envConfig,
    static fn ($value) => $value !== null && $value !== ''
);

$localConfigPath = dirname(__DIR__) . '/config.local.php';
$localConfig = [];

if (is_file($localConfigPath)) {
    $loadedConfig = require $localConfigPath;
    if (is_array($loadedConfig)) {
        $localConfig = $loadedConfig;
    }
}

$appConfig = array_merge($defaultConfig, $envConfig, $localConfig);

define('DB_HOST', $appConfig['host']);
define('DB_NAME', $appConfig['name']);
define('DB_USER', $appConfig['user']);
define('DB_PASS', $appConfig['pass']);
define('DB_CHARSET', $appConfig['charset']);

// Timezone
date_default_timezone_set($appConfig['timezone']);

// Start the session if it's not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// SYSTEM 1: MySQLi (legacy functions)
// =====================================================

/**
 * MySQLi database connection
 * Legacy function - compatible with existing files
 * @return mysqli|null
 */
function get_db() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (!$conn) {
            error_log("MySQLi database connection error: " . mysqli_connect_error());
            die("Erreur de connexion a la base de donnees");
        }
        
        // Set the charset
        mysqli_set_charset($conn, DB_CHARSET);
    }
    
    return $conn;
}

// =====================================================
// SYSTEM 2: PDO (newer functions)
// =====================================================

/**
 * PDO database connection
 * Newer function - for new files
 * @return PDO|null
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("PDO database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    return $pdo;
}

/**
 * Alias for getDBConnection() - allows using get_pdo() as well
 * @return PDO|null
 */
function get_pdo() {
    return getDBConnection();
}

// =====================================================
// UTILITY FUNCTIONS
// =====================================================

/**
 * Checks whether the user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Checks whether the user is a patient
 * @return bool
 */
function isPatient() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'patient';
}

/**
 * Checks whether the user is a practitioner
 * @return bool
 */
function isPractitioner() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'praticien';
}

/**
 * Redirects to the login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /pages/auth.php');
        exit;
    }
}

/**
 * Redirects to the login page if not a patient
 */
function requirePatient() {
    requireLogin();
    if (!isPatient()) {
        header('Location: /index.php');
        exit;
    }
}

/**
 * Sends a JSON response
 * @param mixed $data Data to send
 * @param int $statusCode HTTP status code
 */
function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Sends a JSON error
 * @param string $message Error message
 * @param int $statusCode HTTP status code
 */
function sendError($message, $statusCode = 400) {
    sendJSON(['success' => false, 'error' => $message], $statusCode);
}

/**
 * Sends a JSON success response
 * @param mixed $data Data to send
 */
function sendSuccess($data = null) {
    $response = ['success' => true];
    if ($data !== null) {
        $response['data'] = $data;
    }
    sendJSON($response);
}

/**
 * Cleans and sanitizes a string
 * @param string $string
 * @return string
 */
function cleanString($string) {
    return htmlspecialchars(strip_tags(trim($string)), ENT_QUOTES, 'UTF-8');
}

/**
 * Calculates the distance between two GPS points (in km)
 * @param float $lat1
 * @param float $lon1
 * @param float $lat2
 * @param float $lon2
 * @return float Distance in kilometers
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Earth's radius in km
    
    $latDiff = deg2rad($lat2 - $lat1);
    $lonDiff = deg2rad($lon2 - $lon1);
    
    $a = sin($latDiff / 2) * sin($latDiff / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lonDiff / 2) * sin($lonDiff / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

// =====================================================
// CONSTANTS
// =====================================================

// Appointment status constants
define('APPOINTMENT_STATUS_PENDING', 1);
define('APPOINTMENT_STATUS_CONFIRMED', 2);
define('APPOINTMENT_STATUS_CANCELLED', 3);
define('APPOINTMENT_STATUS_COMPLETED', 4);

/**
 * Translate day names into French
 */
function translateDayToFrench($day) {
    $days = [
        'Monday' => 'Lundi',
        'Tuesday' => 'Mardi',
        'Wednesday' => 'Mercredi',
        'Thursday' => 'Jeudi',
        'Friday' => 'Vendredi',
        'Saturday' => 'Samedi',
        'Sunday' => 'Dimanche',
    ];
    return $days[$day] ?? $day;
}

/**
 * Translate month names into French
 */
function translateMonthToFrench($month) {
    $months = [
        'January' => 'Janvier',
        'February' => 'Fevrier',
        'March' => 'Mars',
        'April' => 'Avril',
        'May' => 'Mai',
        'June' => 'Juin',
        'July' => 'Juillet',
        'August' => 'Aout',
        'September' => 'Septembre',
        'October' => 'Octobre',
        'November' => 'Novembre',
        'December' => 'Decembre',
    ];
    return $months[$month] ?? $month;
}
