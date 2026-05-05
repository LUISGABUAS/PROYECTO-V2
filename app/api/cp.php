<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config.php';

$cp = $_GET['cp'] ?? '';
if (!preg_match('/^\d{5}$/', $cp)) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT colonia, municipio, estado
     FROM sepomex_colonias
     WHERE cp = ?
     ORDER BY colonia ASC"
);
$stmt->execute([$cp]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
