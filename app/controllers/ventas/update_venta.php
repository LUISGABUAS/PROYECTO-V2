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
       COMPROBANTE ACTUAL
    ========================= */
    $stmt = $pdo->prepare("SELECT comprobante FROM tb_ventas WHERE id_venta = ?");
    $stmt->execute([$id_venta]);
    $comprobante_actual = $stmt->fetchColumn();

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
       3️⃣ PROCESAR COMPROBANTE (SI VIENE NUEVO)
    ========================= */
    $nuevo_comprobante = $comprobante_actual;

    // ✅ VALIDACIÓN CORREGIDA
    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {

        $archivo = $_FILES['comprobante'];

        // Tamaño máximo 5MB
        if ($archivo['size'] > 5 * 1024 * 1024) {
            throw new Exception('El comprobante supera los 5MB');
        }

        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $permitidos = ['pdf','jpg','jpeg','png','doc','docx'];

        if (!in_array($ext, $permitidos)) {
            throw new Exception('Formato de comprobante no permitido');
        }

        // Crear carpeta si no existe
        $carpeta_destino = __DIR__ . '/../../comprobantes/';
        if (!is_dir($carpeta_destino)) {
            mkdir($carpeta_destino, 0755, true);
        }

        // Nombre único
        $nuevo_comprobante = 'comp_' . $id_venta . '_' . time() . '.' . $ext;
        $ruta_destino = $carpeta_destino . $nuevo_comprobante;

        if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
            throw new Exception('Error al subir el comprobante');
        }

        // Eliminar comprobante anterior
        if (!empty($comprobante_actual)) {
            $ruta_vieja = $carpeta_destino . $comprobante_actual;
            if (file_exists($ruta_vieja)) {
                unlink($ruta_vieja);
            }
        }
    }

    /* =========================
       4️⃣ ACTUALIZAR VENTA
    ========================= */
    $stmt = $pdo->prepare("UPDATE tb_ventas
        SET fecha = ?, cliente = ?, envio = ?, total = ?, comprobante = ?,
            updated_at = ?
        WHERE id_venta = ?
    ");
    $stmt->execute([
        $fecha,
        $cliente,
        $envio,
        $total,
        $nuevo_comprobante,
        $fechaHora,
        $id_venta
    ]);

    /* =========================
       5️⃣ REEMPLAZAR DETALLE
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
?>