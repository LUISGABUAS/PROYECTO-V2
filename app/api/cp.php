<?php
header('Content-Type: application/json; charset=utf-8');

// Leer .env directamente sin cargar config.php
$env = [];
foreach (file(dirname(__DIR__, 2) . '/.env') as $line) {
    $line = trim($line);
    if (!$line || str_starts_with($line, '#')) continue;
    if (!str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v);
}

try {
    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_NAME']};charset=utf8mb4",
        $env['DB_USER'],
        $env['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    echo json_encode([]);
    exit;
}

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
echo json_encode($stmt->fetchAll());
