<?php
include('../app/config.php');
require_once('../app/controllers/helpers/licencia.php');

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['sesion_email'])) {
    echo json_encode(['desbloqueado' => false]);
    exit;
}

echo json_encode(['desbloqueado' => !licencia_bloqueada($pdo)]);
