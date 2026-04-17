<?php
if (defined('APP_CONFIG_BOOTSTRAPPED')) {
    return;
}

define('APP_CONFIG_BOOTSTRAPPED', true);

/**
 * Configuration de la base de donnees
 * Support des deux systemes de connexion :
 * - MySQLi (anciennes fonctions : get_db())
 * - PDO (nouvelles fonctions : getDBConnection())
 */

// Configuration de la base de donnees
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

// Fuseau horaire
date_default_timezone_set($appConfig['timezone']);

// Demarrage de la session si elle n'est pas deja active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// SYSTEME 1 : MySQLi (anciennes fonctions)
// =====================================================

/**
 * Connexion MySQLi a la base de donnees
 * Fonction ancienne - compatible avec les fichiers existants
 * @return mysqli|null
 */
function get_db() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (!$conn) {
            error_log("Erreur de connexion MySQLi a la base de donnees : " . mysqli_connect_error());
            die("Erreur de connexion a la base de donnees");
        }
        
        // Definir le charset
        mysqli_set_charset($conn, DB_CHARSET);
    }
    
    return $conn;
}

// =====================================================
// SYSTEME 2 : PDO (nouvelles fonctions)
// =====================================================

/**
 * Connexion PDO a la base de donnees
 * Fonction nouvelle - pour les nouveaux fichiers
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
            error_log("Erreur de connexion PDO a la base de donnees : " . $e->getMessage());
            return null;
        }
    }
    
    return $pdo;
}

/**
 * Alias pour getDBConnection() - permet d'utiliser get_pdo() aussi
 * @return PDO|null
 */
function get_pdo() {
    return getDBConnection();
}

// =====================================================
// FONCTIONS UTILITAIRES
// =====================================================

/**
 * Verifie si l'utilisateur est connecte
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verifie si l'utilisateur est un patient
 * @return bool
 */
function isPatient() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'patient';
}

/**
 * Verifie si l'utilisateur est un praticien
 * @return bool
 */
function isPractitioner() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'praticien';
}

/**
 * Redirige vers la page de connexion si non authentifie
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /pages/auth.php');
        exit;
    }
}

/**
 * Redirige vers la page de connexion si non patient
 */
function requirePatient() {
    requireLogin();
    if (!isPatient()) {
        header('Location: /index.php');
        exit;
    }
}

/**
 * Envoie une reponse JSON
 * @param mixed $data Donnees a envoyer
 * @param int $statusCode Code HTTP
 */
function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Envoie une erreur JSON
 * @param string $message Message d'erreur
 * @param int $statusCode Code HTTP
 */
function sendError($message, $statusCode = 400) {
    sendJSON(['success' => false, 'error' => $message], $statusCode);
}

/**
 * Envoie un succes JSON
 * @param mixed $data Donnees a envoyer
 */
function sendSuccess($data = null) {
    $response = ['success' => true];
    if ($data !== null) {
        $response['data'] = $data;
    }
    sendJSON($response);
}

/**
 * Nettoie et securise une chaine
 * @param string $string
 * @return string
 */
function cleanString($string) {
    return htmlspecialchars(strip_tags(trim($string)), ENT_QUOTES, 'UTF-8');
}

/**
 * Calcule la distance entre deux points GPS (en km)
 * @param float $lat1
 * @param float $lon1
 * @param float $lat2
 * @param float $lon2
 * @return float Distance en kilometres
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Rayon de la Terre en km
    
    $latDiff = deg2rad($lat2 - $lat1);
    $lonDiff = deg2rad($lon2 - $lon1);
    
    $a = sin($latDiff / 2) * sin($latDiff / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lonDiff / 2) * sin($lonDiff / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

// =====================================================
// CONSTANTES
// =====================================================

// Constantes de statuts des rendez-vous
define('APPOINTMENT_STATUS_PENDING', 1);
define('APPOINTMENT_STATUS_CONFIRMED', 2);
define('APPOINTMENT_STATUS_CANCELLED', 3);
define('APPOINTMENT_STATUS_COMPLETED', 4);

/**
 * Traduire les noms de jours en francais
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
 * Traduire les noms de mois en francais
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
