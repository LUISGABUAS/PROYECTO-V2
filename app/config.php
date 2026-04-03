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