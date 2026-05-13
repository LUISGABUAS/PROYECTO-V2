<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(dirname(__DIR__, 2) . '/config.php');
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
       COMPROBANTES (múltiples)
    ====================== */
    $rutas_comprobantes = [];

    $hayArchivos = isset($_FILES['comprobantes']) && is_array($_FILES['comprobantes']['name']);
    $indicesValidos = [];

    if ($hayArchivos) {
        foreach ($_FILES['comprobantes']['error'] as $i => $err) {
            if ($err === UPLOAD_ERR_OK) $indicesValidos[] = $i;
        }
    }

    if (empty($indicesValidos) && $tipo_pago !== 'efectivo') {
        throw new Exception("❌ Debe adjuntar al menos un comprobante");
    }

    if (!empty($indicesValidos)) {
        $carpeta  = __DIR__ . '/../../comprobantes/';
        $permitidas = ['pdf','jpg','jpeg','png','doc','docx'];

        if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true)) {
            throw new Exception("❌ No se pudo crear la carpeta de comprobantes");
        }
        if (!is_writable($carpeta)) {
            throw new Exception("❌ No hay permisos de escritura en la carpeta de comprobantes");
        }

        foreach ($indicesValidos as $i) {
            $ext = strtolower(pathinfo($_FILES['comprobantes']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $permitidas)) {
                throw new Exception("❌ Formato no permitido: " . htmlspecialchars($_FILES['comprobantes']['name'][$i]));
            }
            if ($_FILES['comprobantes']['size'][$i] > 5 * 1024 * 1024) {
                throw new Exception("❌ El archivo excede el tamaño máximo de 5MB");
            }
            $nombre = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $ext;
            if (!move_uploaded_file($_FILES['comprobantes']['tmp_name'][$i], $carpeta . $nombre)) {
                throw new Exception("❌ Error al subir el comprobante");
            }
            $rutas_comprobantes[] = 'app/comprobantes/' . $nombre;
        }
    }

    /* ======================
       INSERTAR VENTA (PADRE)
    ====================== */
    $stmt = $pdo->prepare("
        INSERT INTO tb_ventas (fecha, cliente, envio, tipo_pago, total, comprobante, id_usuario)
        VALUES (?, ?, ?, ?, ?, NULL, ?)
    ");
    $stmt->execute([$fecha, $cliente, $envio, $tipo_pago, $total, $id_usuario]);

    $id_venta = $pdo->lastInsertId();

    /* ======================
       INSERTAR COMPROBANTES
    ====================== */
    foreach ($rutas_comprobantes as $ruta) {
        $stmt = $pdo->prepare("INSERT INTO tb_ventas_comprobantes (id_venta, ruta) VALUES (?, ?)");
        $stmt->execute([$id_venta, $ruta]);
    }

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

    // Eliminar archivos subidos si hubo error posterior
    if (!empty($rutas_comprobantes) && isset($carpeta)) {
        foreach ($rutas_comprobantes as $ruta) {
            $archivo = $carpeta . basename($ruta);
            if (file_exists($archivo)) unlink($archivo);
        }
    }

    $_SESSION['mensaje'] = $e->getMessage();
    header("Location: ../../../ventas/create.php");
    exit;
}