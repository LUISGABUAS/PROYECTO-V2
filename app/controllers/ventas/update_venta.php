<?php
require_once __DIR__ . '/../../config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../ventas');
    exit;
}

/* =========================
   DATOS GENERALES
========================= */
$id_venta   = (int)$_POST['id_venta'];
$fecha      = $_POST['fecha'];
$cliente    = $_POST['cliente'];
$envio      = $_POST['envio'];
$total      = (float)$_POST['total'];

$productos  = $_POST['productos'];
$cantidades = $_POST['cantidades'];
$precios    = $_POST['precios'];

try {
    $pdo->beginTransaction();

    /* =========================
       1️⃣ VALIDAR QUE NO HAYA ENTREGAS
    ========================= */
    $stmt = $pdo->prepare("SELECT SUM(cantidad_entregada) 
        FROM tb_ventas_detalle
        WHERE id_venta = ?
    ");
    $stmt->execute([$id_venta]);
    $entregadas = (int)$stmt->fetchColumn();

    if ($entregadas > 0) {
        throw new Exception(
            'No se puede editar la venta porque ya tiene productos entregados'
        );
    }

    /* =========================
       2️⃣ VALIDAR STOCK DISPONIBLE
       (SIN TOCAR CODIGOS)
    ========================= */
    foreach ($productos as $i => $id_producto) {

        $id_producto = (int)$id_producto;
        $cantidad    = (int)$cantidades[$i];

        if ($cantidad <= 0) {
            throw new Exception('Cantidad inválida');
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) 
            FROM stock
            WHERE id_producto = ?
              AND estado = 'EN BODEGA'
        ");
        $stmt->execute([$id_producto]);
        $disponible = (int)$stmt->fetchColumn();

        if ($disponible < $cantidad) {
            $p = $pdo->prepare("SELECT nombre FROM tb_almacen WHERE id_producto = ?");
            $p->execute([$id_producto]);
            $nombre = $p->fetchColumn();

            throw new Exception("Stock insuficiente para $nombre");
        }
    }

    /* =========================
       3️⃣ ACTUALIZAR VENTA
    ========================= */
    $stmt = $pdo->prepare("UPDATE tb_ventas
        SET fecha = ?, cliente = ?, envio = ?, total = ?
        WHERE id_venta = ?
    ");
    $stmt->execute([
        $fecha,
        $cliente,
        $envio,
        $total,
        $id_venta
    ]);

    /* =========================
       4️⃣ REEMPLAZAR DETALLE
       (SOLO CANTIDADES)
    ========================= */
    $stmt = $pdo->prepare("DELETE FROM tb_ventas_detalle WHERE id_venta = ?");
    $stmt->execute([$id_venta]);

    foreach ($productos as $i => $id_producto) {

        $id_producto = (int)$id_producto;
        $cantidad    = (int)$cantidades[$i];
        $precio      = (float)$precios[$i];
        $subtotal    = $cantidad * $precio;

        $stmt = $pdo->prepare("INSERT INTO tb_ventas_detalle
            (id_venta, id_producto, cantidad, cantidad_entregada, precio, subtotal)
            VALUES (?, ?, ?, 0, ?, ?)
        ");
        $stmt->execute([
            $id_venta,
            $id_producto,
            $cantidad,
            $precio,
            $subtotal
        ]);
    }

    $pdo->commit();

    $_SESSION['mensaje'] = '✅ Venta actualizada correctamente';
    header('Location: ../../../ventas');
    exit;

} catch (Exception $e) {

    $pdo->rollBack();
    $_SESSION['mensaje'] = '❌ ' . $e->getMessage();
    header('Location: ../../../ventas/edit.php?id=' . $id_venta);
    exit;
}
