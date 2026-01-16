<?php
require_once __DIR__ . '/../../config.php';

$id_venta = $_GET['id'] ?? null;

if (!$id_venta) {
    header('Location: ../../../ventas');
    exit;
}

/* =========================
   DATOS DE LA VENTA
========================= */
$stmt = $pdo->prepare("SELECT *
    FROM tb_ventas
    WHERE id_venta = ?
");
$stmt->execute([$id_venta]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    header('Location: ../../../ventas');
    exit;
}

/* =========================
   DETALLE DE VENTA
========================= */
$stmt = $pdo->prepare("SELECT d.id_producto, d.cantidad, d.precio
    FROM tb_ventas_detalle d
    WHERE d.id_venta = ?
");
$stmt->execute([$id_venta]);
$detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);
