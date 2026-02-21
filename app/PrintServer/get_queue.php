<?php
$secret = 'PacasYadira';

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

include($_SERVER['DOCUMENT_ROOT'] . '/app/config.php');
$stmt = $pdo->query("SELECT id, zpl FROM print_queue WHERE status = 'pendiente' ORDER BY created_at ASC");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));