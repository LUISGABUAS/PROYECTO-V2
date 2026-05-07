<?php
require_once __DIR__ . '/../config.php';
$secret = $_ENV['PRINT_SERVER_SECRET'] ?? '';

// Hostinger a veces manda el header diferente
$auth = '';
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $auth = $headers['Authorization'] ?? '';
}

$auth = str_replace('Bearer ', '', $auth);

if ($auth !== $secret) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado', 'auth_recibido' => $auth]);
    exit;
}

// Fetch + marcar como 'procesando' en una sola transacción
// Así el siguiente ciclo no vuelve a tomar el mismo job
$pdo->beginTransaction();

$stmt = $pdo->query("SELECT id, zpl FROM print_queue WHERE status = 'pendiente' ORDER BY created_at ASC");
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($jobs)) {
    $ids = implode(',', array_map('intval', array_column($jobs, 'id')));
    $pdo->query("UPDATE print_queue SET status = 'procesando' WHERE id IN ($ids)");
}

$pdo->commit();
echo json_encode($jobs);