<?php
require_once dirname(__DIR__, 2) . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$perms    = $_SESSION['permisos'] ?? [];
$_rol_rep = strtoupper(trim($_SESSION['rol_sesion'] ?? ''));
if (!in_array(41, $perms) && !in_array(24, $perms) && $_rol_rep !== 'REPARTIDOR') {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']); exit;
}

$id_venta = (int)($_POST['id_venta'] ?? 0);
$accion   = $_POST['accion'] ?? '';
$id_yo    = (int)($_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? 0);

if (!$id_venta || !$accion) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit;
}

try {
    if ($accion === 'tomar') {
        $pdo->prepare("UPDATE tb_ventas SET id_repartidor = ?, estado_reparto = 'EN_CAMINO' WHERE id_venta = ? AND envio = 'local'")
            ->execute([$id_yo, $id_venta]);

    } elseif ($accion === 'entregar') {
        $pdo->prepare("UPDATE tb_ventas SET estado_reparto = 'ENTREGADO', fecha_entrega = NOW(), estado_logistico = 'ENVIADA' WHERE id_venta = ? AND envio = 'local'")
            ->execute([$id_venta]);

    } elseif ($accion === 'devolver') {
        $pdo->prepare("UPDATE tb_ventas SET estado_reparto = 'PENDIENTE', id_repartidor = NULL WHERE id_venta = ? AND envio = 'local'")
            ->execute([$id_venta]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Acción desconocida']); exit;
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
