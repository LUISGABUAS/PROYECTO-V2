<?php
$secret = 'PacasYadira';
$auth = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');

if ($auth !== $secret) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

include('config.php');
$id = intval($_GET['id']);
$pdo->prepare("UPDATE print_queue SET status = 'completado', printed_at = NOW() WHERE id = ?")->execute([$id]);
echo json_encode(['status' => 'success']);