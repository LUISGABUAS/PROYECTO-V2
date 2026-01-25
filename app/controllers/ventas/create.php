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
       COMPROBANTE 
    ====================== */
    $ruta_comprobante = null;

    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {

        $ext = strtolower(pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION));
        $permitidas = ['pdf','jpg','jpeg','png','doc','docx'];

        if (!in_array($ext, $permitidas)) {
            throw new Exception("❌ Formato de comprobante no permitido");
        }

        // Validar tamaño (5MB)
        if ($_FILES['comprobante']['size'] > 5 * 1024 * 1024) {
            throw new Exception("❌ El archivo excede el tamaño máximo de 5MB");
        }

        // Ruta de carpeta
        $carpeta = __DIR__ . '/../../comprobantes/';
        
        // Crear carpeta si no existe
        if (!is_dir($carpeta)) {
            if (!mkdir($carpeta, 0755, true)) {
                throw new Exception("❌ No se pudo crear la carpeta de comprobantes");
            }
        }

        // Verificar permisos
        if (!is_writable($carpeta)) {
            throw new Exception("❌ No hay permisos de escritura en la carpeta de comprobantes");
        }

        // Nombre único
        $nombre = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $ext;
        
        // Mover archivo
        if (!move_uploaded_file($_FILES['comprobante']['tmp_name'], $carpeta . $nombre)) {
            throw new Exception("❌ Error al subir el comprobante");
        }

        // Ruta para BD
        $ruta_comprobante = 'app/comprobantes/' . $nombre;
    } else {
        throw new Exception("❌ Debe adjuntar un comprobante");
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

    $_SESSION['mensaje'] = "✅ Venta #$id_venta creada correctamente";
    header("Location: ../../../ventas");
    exit;

} catch (Exception $e) {

    $pdo->rollBack();
    
    // Eliminar archivo si se subió pero hubo error
    if (isset($carpeta) && isset($nombre) && file_exists($carpeta . $nombre)) {
        unlink($carpeta . $nombre);
    }
    
    $_SESSION['mensaje'] = $e->getMessage();
    header("Location: ../../../ventas/create.php");
    exit;
}