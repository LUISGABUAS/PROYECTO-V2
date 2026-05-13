<?php
require_once __DIR__ . '/../../config.php';

$id_venta = $_GET['id'] ?? null;

if (!$id_venta) {
    header('Location: ../../../ventas');
    exit;
}

/* =========================
   DATOS DE LA VENTA CON INFORMACIÓN DEL CLIENTE Y COMPROBANTE
========================= */
$stmt = $pdo->prepare("SELECT v.*,
    c.nombre_completo AS cliente_nombre,
    u.nombres AS vendedor_nombre
    FROM tb_ventas v
    LEFT JOIN clientes c ON v.cliente = c.id_cliente
    LEFT JOIN tb_usuario u ON v.id_usuario = u.id
    WHERE v.id_venta = ?
");
$stmt->execute([$id_venta]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    header('Location: ../../../ventas');
    exit;
}

/* =========================
   LISTA DE CLIENTES
========================= */
$stmt = $pdo->prepare("SELECT id_cliente, nombre_completo FROM clientes ORDER BY nombre_completo");
$stmt->execute();
$clientes_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   DETALLE DE VENTA
========================= */
$stmt = $pdo->prepare("SELECT d.id_producto, d.cantidad, d.precio,
    a.nombre AS nombre_producto,
    a.codigo
    FROM tb_ventas_detalle d
    INNER JOIN tb_almacen a ON d.id_producto = a.id_producto
    WHERE d.id_venta = ?
");
$stmt->execute([$id_venta]);
$detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   COMPROBANTES (nueva tabla + legacy)
========================= */
$stmt = $pdo->prepare("SELECT id, ruta FROM tb_ventas_comprobantes WHERE id_venta = ? ORDER BY id ASC");
$stmt->execute([$id_venta]);
$comprobantes_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fallback: si no hay en nueva tabla pero sí en columna legacy, mostrarla igual
if (empty($comprobantes_lista) && !empty($venta['comprobante'])) {
    $comprobantes_lista = [['id' => 'legacy', 'ruta' => $venta['comprobante']]];
}
?>