<?php
require_once dirname(__DIR__, 2) . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario_sesion'])) {
    echo json_encode(['success' => false, 'message' => 'Sin sesión']);
    exit;
}

$id_producto = (int)($_GET['id_producto'] ?? 0);
if (!$id_producto) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        v.id_venta,
        v.fecha,
        c.nombre_completo AS cliente,
        c.telefono,
        vd.cantidad,
        vd.cantidad_entregada,
        (vd.cantidad - vd.cantidad_entregada) AS pendiente
    FROM tb_ventas_detalle vd
    JOIN tb_ventas v ON vd.id_venta = v.id_venta
    JOIN clientes c ON v.cliente = c.id_cliente
    WHERE vd.id_producto = ?
      AND vd.cantidad_entregada < vd.cantidad
    ORDER BY v.fecha ASC
");
$stmt->execute([$id_producto]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nombre_producto = $pdo->prepare("SELECT nombre, codigo FROM tb_almacen WHERE id_producto = ?");
$nombre_producto->execute([$id_producto]);
$producto = $nombre_producto->fetch(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'producto' => $producto, 'pendientes' => $rows]);
