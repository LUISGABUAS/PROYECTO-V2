<?php
session_start();
include('../../config.php');

try {
    $pdo->beginTransaction();

    /* ======================
       DATOS GENERALES
    ====================== */
    $fecha      = $_POST['fecha'];
    $cliente    = $_POST['cliente'];
    $envio      = $_POST['envio'];
    $id_usuario = (int)$_POST['id_usuario'];
    $total      = (float)$_POST['total'];

    $productos  = $_POST['productos'];
    $cantidades = $_POST['cantidades'];
    $precios    = $_POST['precios'];

    /* ======================
       1️⃣ VALIDAR STOCK REAL
    ====================== */
    foreach ($productos as $i => $id_producto) {

        $id_producto = (int)$id_producto;
        $cantidad    = (int)$cantidades[$i];

        /* 🔹 STOCK EN BODEGA */
        $stmt = $pdo->prepare("SELECT COUNT(*) 
            FROM stock 
            WHERE id_producto = ?
              AND estado = 'EN BODEGA'
        ");
        $stmt->execute([$id_producto]);
        $stock_bodega = (int)$stmt->fetchColumn();

        /* 🔹 PENDIENTES POR ENTREGAR */
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(cantidad - cantidad_entregada), 0)
            FROM tb_ventas_detalle
            WHERE id_producto = ?
        ");
        $stmt->execute([$id_producto]);
        $pendientes = (int)$stmt->fetchColumn();

        /* 🔹 STOCK REAL DISPONIBLE */
        $disponible_real = $stock_bodega - $pendientes;

        if ($disponible_real <= 0 || $disponible_real < $cantidad) {

            $p = $pdo->prepare("SELECT nombre FROM tb_almacen WHERE id_producto = ?");
            $p->execute([$id_producto]);
            $nombre_producto = $p->fetchColumn();

            throw new Exception("❌ Stock insuficiente para $nombre_producto");
        }
    }

    /* ======================
       2️⃣ CREAR VENTA
    ====================== */
    $stmt = $pdo->prepare("INSERT INTO tb_ventas (fecha, cliente, envio, total, id_usuario)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$fecha, $cliente, $envio, $total, $id_usuario]);

    $id_venta = $pdo->lastInsertId();

    /* ======================
       3️⃣ DETALLE (SOLO CANTIDADES)
    ====================== */
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

    /* ======================
       4️⃣ CONFIRMAR
    ====================== */
    $pdo->commit();

    $_SESSION['mensaje'] = "✅ Venta registrada correctamente";
    header("Location: ../../../ventas/create.php");
    exit;

} catch (Exception $e) {

    $pdo->rollBack();
    $_SESSION['mensaje'] = $e->getMessage();
    header("Location: ../../../ventas/create.php");
    exit;
}
