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
   DEBUG: Verificar si el comprobante existe
========================= */
// Puedes descomentar esto temporalmente para debug:
// error_log("Comprobante en BD: " . ($venta['comprobante'] ?? 'NULL'));
// error_log("Ruta esperada: ../../comprobantes/" . $venta['comprobante']);
?>