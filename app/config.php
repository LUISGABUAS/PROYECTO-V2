<?php

// Carga el autoload de Composer (necesario para phpdotenv)
require_once __DIR__ . '/../vendor/autoload.php';

// Carga las variables del .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? "switchback.proxy.rlwy.net";       // de Railway
$port = $_ENV['DB_PORT'] ?? "54275";       // de Railway
$db   = $_ENV['DB_NAME'] ?? "railway";   // de Railway
$user = $_ENV['DB_USER'] ?? "root";       // de Railway
$pass = $_ENV['DB_PASSWORD'] ?? "***REMOVED***";   // de Railway

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

$URL = "http://localhost/PROYECTO";

date_default_timezone_set("America/Mexico_City");
$fechaHora = date("Y-m-d H:i:s");

