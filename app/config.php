<?php

// Carga el autoload de Composer (necesario para phpdotenv)
require_once __DIR__ . '/../vendor/autoload.php';

// Carga las variables del .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$host = $_ENV['DB_HOST'] ;
$port = $_ENV['DB_PORT'] ;
$db   = $_ENV['DB_NAME'] ;
$user = $_ENV['DB_USER'] ;
$pass = $_ENV['DB_PASS']; 

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    // echo "Conectado a Railway";
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$URL = $_ENV['APP_URL'];

date_default_timezone_set("America/Mexico_City");
$fechaHora = date("Y-m-d H:i:s");

// ================================================
// MIGRACIONES DE BASE DE DATOS
// ================================================
require_once __DIR__ . '/database/migrations/add_foto_perfil_column.php';
require_once __DIR__ . '/database/migrations/add_id_vendedor_to_clientes.php';
require_once __DIR__ . '/database/migrations/add_clientes_direcciones_table.php';

// ================================================
// LOGGING CENTRALIZADO
// ================================================
require_once __DIR__ . '/controllers/helpers/logger.php';

// ================================================
// LOGGING DE ERRORES (según entorno)
// ================================================
$app_env   = $_ENV['APP_ENV']   ?? 'production';
$app_debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');
error_reporting(E_ALL);

if ($app_debug) {
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
}

// ================================================
// MANEJADOR GLOBAL DE EXCEPCIONES (ERROR 500)
// ================================================
set_exception_handler(function($exception) {
    Logger::error500($exception);
    
    http_response_code(500);
    
    if ($_ENV['APP_DEBUG'] ?? false) {
        echo '<div style="background:#f8d7da;padding:20px;border:1px solid #f5c6cb;border-radius:4px;color:#721c24;">';
        echo '<h3>❌ Error 500 - Excepción no capturada</h3>';
        echo '<pre>' . htmlspecialchars($exception) . '</pre>';
        echo '</div>';
    } else {
        echo '<div style="background:#f8d7da;padding:20px;border:1px solid #f5c6cb;border-radius:4px;color:#721c24;">';
        echo '<h3>❌ Error interno del servidor</h3>';
        echo '<p>El error ha sido registrado. Por favor, contacte con soporte.</p>';
        echo '</div>';
    }
});

// ================================================
// MANEJADOR DE ERRORES (ERROR 500)
// ================================================
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (error_reporting() === 0) return false;
    
    Logger::error500(
        new Exception("$errstr en $errfile:$errline", $errno),
        ['error_type' => $errno]
    );
    
    return false;
});