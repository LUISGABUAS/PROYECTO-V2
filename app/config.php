<?php

$host = "switchback.proxy.rlwy.net";       // de Railway
$port = "54275";       // de Railway
$db   = "railway";   // de Railway
$user = "root";       // de Railway
$pass = "***REMOVED***";   // de Railway

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

