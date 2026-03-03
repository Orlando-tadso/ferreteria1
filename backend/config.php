<?php
/**
 * CONFIGURACIÓN BACKEND - FERRETERÍA
 * Separación de Frontend y Backend
 */

// Configuración de zona horaria
date_default_timezone_set('America/Bogota');

// Configurar headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejo de preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en producción
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Crear directorio de logs si no existe
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// === CONFIGURACIÓN DE BASE DE DATOS ===

// Railway proporciona variables individuales
if (getenv('MYSQL_MYSQLHOST') && getenv('MYSQL_MYSQLUSER')) {
    define('DB_HOST', getenv('MYSQL_MYSQLHOST'));
    define('DB_USER', getenv('MYSQL_MYSQLUSER'));
    define('DB_PASS', getenv('MYSQL_MYSQLPASSWORD') ?: '');
    define('DB_NAME', getenv('MYSQL_MYSQLDATABASE') ?: 'fetteria_inventario');
    define('DB_PORT', getenv('MYSQL_MYSQLPORT') ?: 3306);
    $is_remote_db = true;
}
// Heroku con ClearDB
elseif (getenv('CLEARDB_DATABASE_URL')) {
    $url = parse_url(getenv('CLEARDB_DATABASE_URL'));
    define('DB_HOST', $url['host']);
    define('DB_USER', $url['user']);
    define('DB_PASS', $url['pass']);
    define('DB_NAME', ltrim($url['path'], '/'));
    define('DB_PORT', 3306);
    $is_remote_db = true;
}
// Configuración local (XAMPP)
else {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'fetteria_inventario');
    define('DB_PORT', 3306);
    $is_remote_db = false;
}

// Crear conexión
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Verificar conexión
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión a base de datos'
    ]);
    error_log("Error de conexión: " . $conn->connect_error);
    exit;
}

// Configurar charset
$conn->set_charset("utf8mb4");

// Configuración de sesiones TOKEN-BASED (para API)
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'ferreteria_secret_key_2024');
define('SESSION_TIMEOUT', 28800); // 8 horas

/**
 * Función para registrar errores
 */
function logError($titulo, $mensaje) {
    $log = "[" . date('Y-m-d H:i:s') . "] $titulo: $mensaje\n";
    error_log($log);
}

/**
 * Función para responder en JSON
 */
function responder($success, $data = null, $message = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

/**
 * Obtener datos JSON del request
 */
function obtenerJSON() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

/**
 * Validar que sea POST/GET/PUT/DELETE
 */
function validarMetodo($metodos = ['POST']) {
    $metodo = $_SERVER['REQUEST_METHOD'];
    if (!in_array($metodo, $metodos)) {
        responder(false, null, "Método $metodo no permitido", 405);
    }
}
?>
