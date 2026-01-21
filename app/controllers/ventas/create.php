<?php
session_start();
include('../../config.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../ventas");
    exit;
}

try {
    $pdo->beginTransaction();

    /* ======================
       DATOS GENERALES
    ====================== */
    $id_venta   = (int)$_POST['id_venta'];
    $fecha      = $_POST['fecha'];
    $cliente    = $_POST['cliente'];
    $envio      = $_POST['envio'];
    $total      = (float)$_POST['total'];

    $productos  = $_POST['productos'];
    $cantidades = $_POST['cantidades'];
    $precios    = $_POST['precios'];

    /* ======================
       COMPROBANTE ACTUAL
    ====================== */
    $stmt = $pdo->prepare("SELECT comprobante FROM tb_ventas WHERE id_venta = ?");
    $stmt->execute([$id_venta]);
    $comprobante_actual = $stmt->fetchColumn();

    /* ======================
       VALIDAR ENTREGAS
    ====================== */
    $stmt = $pdo->prepare("SELECT SUM(cantidad_entregada)
        FROM tb_ventas_detalle
        WHERE id_venta = ?
    ");
    $stmt->execute([$id_venta]);
    $entregadas = (int)$stmt->fetchColumn();

    if ($entregadas > 0) {
        throw new Exception("❌ No se puede editar la venta porque ya tiene entregas");
    }

    /* ======================
       PROCESAR COMPROBANTE
    ====================== */
    $ruta_comprobante = $comprobante_actual;

    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("❌ Error al cargar el comprobante");
        }

        $comprobante = $_FILES['comprobante'];

        // Tamaño máx 5MB
        if ($comprobante['size'] > 5 * 1024 * 1024) {
            throw new Exception("❌ El archivo no debe superar 5MB");
        }

        // Extensión
        $extensiones_permitidas = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $ext = strtolower(pathinfo($comprobante['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $extensiones_permitidas)) {
            throw new Exception("❌ Formato de archivo no permitido");
        }

        // Carpeta
        $carpeta = __DIR__ . '/../../comprobantes/';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        // Nombre único
        $nombre_archivo = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $ext;
        $ruta_fisica = $carpeta . $nombre_archivo;

        if (!move_uploaded_file($comprobante['tmp_name'], $ruta_fisica)) {
            throw new Exception("❌ Error al guardar el comprobante");
        }

        // Borrar anterior
        if (!empty($comprobante_actual)) {
            $ruta_anterior = __DIR__ . '/../../' . $comprobante_actual;
            if (file_exists($ruta_anterior)) {
                unlink($ruta_anterior);
            }
        }

        $ruta_comprobante = 'comprobantes/' . $nombre_archivo;
    }

    /* ======================
       VALIDAR STOCK REAL
    ====================== */
    foreach ($productos as $i => $id_producto) {

        $id_producto = (int)$id_producto;
        $cantidad    = (int)$cantidades[$i];

        // Stock en bodega
        $stmt = $pdo->prepare("SELECT COUNT(*)
            FROM stock
            WHERE id_producto = ?
              AND estado = 'EN BODEGA'
        ");
        $stmt->execute([$id_producto]);
        $stock_bodega = (int)$stmt->fetchColumn();

        // Pendientes globales
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(cantidad - cantidad_entregada), 0)
            FROM tb_ventas_detalle
            WHERE id_producto = ?
              AND id_venta != ?
        ");
        $stmt->execute([$id_producto, $id_venta]);
        $pendientes = (int)$stmt->fetchColumn();

        $disponible_real = $stock_bodega - $pendientes;

        if ($disponible_real < $cantidad) {

            $p = $pdo->prepare("SELECT nombre FROM tb_almacen WHERE id_producto = ?");
            $p->execute([$id_producto]);
            $nombre_producto = $p->fetchColumn();

            throw new Exception("❌ Stock insuficiente para $nombre_producto");
        }
    }

    /* ======================
       ACTUALIZAR VENTA
    ====================== */
    $stmt = $pdo->prepare("UPDATE tb_ventas
        SET fecha = ?, cliente = ?, envio = ?, total = ?, comprobante = ?
        WHERE id_venta = ?
    ");
    $stmt->execute([
        $fecha,
        $cliente,
        $envio,
        $total,
        $ruta_comprobante,
        $id_venta
    ]);

    /* ======================
       REEMPLAZAR DETALLE
    ====================== */
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

    /* ======================
       CONFIRMAR
    ====================== */
    $pdo->commit();

    $_SESSION['mensaje'] = "✅ Venta actualizada correctamente";
    header("Location: ../../../ventas");
    exit;

} catch (Exception $e) {

    $pdo->rollBack();
    $_SESSION['mensaje'] = $e->getMessage();
    header("Location: ../../../ventas/edit.php?id=" . $id_venta);
    exit;
}
