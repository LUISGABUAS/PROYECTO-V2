<?php
$secret = 'PacasYadira';
$auth = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');

if ($auth !== $secret) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

include('config.php');
$stmt = $pdo->query("SELECT id, zpl FROM print_queue WHERE status = 'pendiente' ORDER BY created_at ASC");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));