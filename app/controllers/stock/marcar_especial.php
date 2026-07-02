<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/config.php';
header('Content-Type: application/json');

if (!in_array(11, $_SESSION['permisos'] ?? []) && !in_array(24, $_SESSION['permisos'] ?? [])) {
    echo json_encode(['success' => false, 'message' => 'Sin permiso']);
    exit;
}

$tipo      = $_POST['tipo_especial'] ?? '';
$notas     = trim($_POST['notas_especial'] ?? '');
$id_venta  = !empty($_POST['id_venta_origen']) ? (int)$_POST['id_venta_origen'] : null;
$ids_raw   = $_POST['ids'] ?? [];

// Revertir a normal (solo admin)
if ($tipo === 'REVERTIR') {
    if (!in_array(24, $_SESSION['permisos'] ?? [])) {
        echo json_encode(['success' => false, 'message' => 'Solo admin puede revertir']);
        exit;
    }
    $ids = array_map('intval', array_filter($ids_raw));
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("UPDATE stock SET tipo_especial = NULL, notas_especial = NULL, id_venta_origen = NULL WHERE id_stock IN ($ph)")
        ->execute($ids);
    echo json_encode(['success' => true, 'message' => 'Paca(s) revertidas a normal']);
    exit;
}

if (!in_array($tipo, ['VIDEO', 'FLEJADA'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo inválido']);
    exit;
}

$ids = array_map('intval', array_filter($ids_raw));
if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No se seleccionaron pacas']);
    exit;
}

$ph = implode(',', array_fill(0, count($ids), '?'));

// Validar que estén EN BODEGA y no especiales
$stmt = $pdo->prepare("SELECT COUNT(*) FROM stock
    WHERE id_stock IN ($ph) AND estado = 'EN BODEGA' AND tipo_especial IS NULL");
$stmt->execute($ids);
if ((int)$stmt->fetchColumn() !== count($ids)) {
    echo json_encode(['success' => false, 'message' => 'Solo se pueden marcar pacas EN BODEGA que no sean ya especiales']);
    exit;
}

$stmt = $pdo->prepare("UPDATE stock
    SET tipo_especial = ?, notas_especial = ?, id_venta_origen = ?
    WHERE id_stock IN ($ph)");
$params = array_merge([$tipo, $notas ?: null, $id_venta], $ids);
$stmt->execute($params);

echo json_encode(['success' => true, 'message' => count($ids) . ' paca(s) marcada(s) como ' . $tipo, 'cantidad' => count($ids)]);
