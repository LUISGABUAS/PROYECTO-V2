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

    $id_usuario = $_POST['id_usuario'];
    $fecha   = $_POST['fecha'];
    $cliente = $_POST['cliente'];
    $envio   = $_POST['envio'];
    $total   = (float)$_POST['total'];

    $productos  = $_POST['productos'];
    $cantidades = $_POST['cantidades'];
    $precios    = $_POST['precios'];

    if (empty($productos)) {
        throw new Exception("❌ No hay productos en la venta");
    }

    /* ======================
       COMPROBANTE (OPCIONAL)
    ====================== */
    $ruta_comprobante = null;

    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {

        $ext = strtolower(pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION));
        $permitidas = ['pdf','jpg','jpeg','png'];

        if (!in_array($ext, $permitidas)) {
            throw new Exception("❌ Formato de comprobante no permitido");
        }

        $carpeta = __DIR__ . '/../../comprobantes/';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        $nombre = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['comprobante']['tmp_name'], $carpeta.$nombre);

        $ruta_comprobante = 'comprobantes/'.$nombre;
    }

    /* ======================
       INSERTAR VENTA (PADRE)
    ====================== */
    $stmt = $pdo->prepare("
        INSERT INTO tb_ventas (fecha, cliente, envio, total, comprobante, id_usuario)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $fecha,
        $cliente,
        $envio,
        $total,
        $ruta_comprobante,
        $id_usuario
    ]);

    // 🔥 ID REAL DE LA VENTA
    $id_venta = $pdo->lastInsertId();

    /* ======================
       INSERTAR DETALLE
    ====================== */
    foreach ($productos as $i => $id_producto) {

        $cantidad = (int)$cantidades[$i];
        $precio   = (float)$precios[$i];
        $subtotal = $cantidad * $precio;

        $stmt = $pdo->prepare("
            INSERT INTO tb_ventas_detalle
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

    $_SESSION['mensaje'] = "✅ Venta creada correctamente";
    header("Location: ../../../ventas");
    exit;

} catch (Exception $e) {

    $pdo->rollBack();
    $_SESSION['mensaje'] = $e->getMessage();
    header("Location: ../../../ventas/create.php");
    exit;
}
