<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROYECTO-V2/app/config.php';
$secret = $_ENV['PRINT_SERVER_SECRET'] ?? '';
$auth = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');

if ($auth !== $secret) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$id = intval($_GET['id']);
$pdo->prepare("UPDATE print_queue SET status = 'completado', printed_at = NOW() WHERE id = ?")->execute([$id]);
echo json_encode(['status' => 'success']);