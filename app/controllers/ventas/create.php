<?php
session_start();
include('../../config.php');
include(__DIR__ . '/../helpers/csrf.php');
csrf_verify();

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
    $fecha      = $_POST['fecha'];
    $cliente    = $_POST['cliente'];
    $envio      = $_POST['envio'];
    $total      = (float)$_POST['total'];
    $tipo_pago  = $_POST['tipo_pago'] ?? 'comprobante'; // ✅ NUEVO

    $productos  = $_POST['productos'];
    $cantidades = $_POST['cantidades'];
    $precios    = $_POST['precios'];

    if (empty($productos)) {
        throw new Exception("❌ No hay productos en la venta");
    }

    /* ======================
       COMPROBANTE 
    ====================== */
    $ruta_comprobante = null;

    $hayArchivo = isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK;

    // ✅ Solo es obligatorio si NO es pago en efectivo
    if (!$hayArchivo && $tipo_pago !== 'efectivo') {
        throw new Exception("❌ Debe adjuntar un comprobante");
    }

    if ($hayArchivo) {

        $ext       = strtolower(pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION));
        $permitidas = ['pdf','jpg','jpeg','png','doc','docx'];

        if (!in_array($ext, $permitidas)) {
            throw new Exception("❌ Formato de comprobante no permitido");
        }

        if ($_FILES['comprobante']['size'] > 5 * 1024 * 1024) {
            throw new Exception("❌ El archivo excede el tamaño máximo de 5MB");
        }

        $carpeta = __DIR__ . '/../../comprobantes/';

        if (!is_dir($carpeta)) {
            if (!mkdir($carpeta, 0755, true)) {
                throw new Exception("❌ No se pudo crear la carpeta de comprobantes");
            }
        }

        if (!is_writable($carpeta)) {
            throw new Exception("❌ No hay permisos de escritura en la carpeta de comprobantes");
        }

        $nombre = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $ext;

        if (!move_uploaded_file($_FILES['comprobante']['tmp_name'], $carpeta . $nombre)) {
            throw new Exception("❌ Error al subir el comprobante");
        }

        $ruta_comprobante = 'app/comprobantes/' . $nombre;
    }

    /* ======================
       INSERTAR VENTA (PADRE)
    ====================== */
    $stmt = $pdo->prepare("
        INSERT INTO tb_ventas (fecha, cliente, envio, tipo_pago, total, comprobante, id_usuario)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $fecha,
        $cliente,
        $envio,
        $tipo_pago,        // ✅ NUEVO
        $total,
        $ruta_comprobante, // NULL si es efectivo sin archivo
        $id_usuario
    ]);

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

    include('../helpers/auditoria.php');
    registrarAuditoria($pdo, $id_usuario, null, 'CREAR VENTA', 'tb_ventas', $id_venta, "Venta #$id_venta — Cliente: $cliente — Total: $total");

    $_SESSION['mensaje'] = "✅ Venta #$id_venta creada correctamente";
    header("Location: ../../../ventas");
    exit;

} catch (Exception $e) {

    $pdo->rollBack();

    // Eliminar archivo si se subió pero hubo error posterior
    if (isset($carpeta) && isset($nombre) && file_exists($carpeta . $nombre)) {
        unlink($carpeta . $nombre);
    }

    $_SESSION['mensaje'] = $e->getMessage();
    header("Location: ../../../ventas/create.php");
    exit;
}