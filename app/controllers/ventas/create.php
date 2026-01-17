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
       PROCESAR COMPROBANTE
    ====================== */
    $ruta_comprobante = null;

    // Validar que el comprobante sea obligatorio
    if (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] === UPLOAD_ERR_NO_FILE) {
        throw new Exception("❌ El comprobante es obligatorio");
    }

    if ($_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("❌ Error al cargar el comprobante");
    }

    $comprobante = $_FILES['comprobante'];
    
    // Validar tamaño (5MB máx)
    $max_size = 5 * 1024 * 1024;
    if ($comprobante['size'] > $max_size) {
        throw new Exception("❌ El archivo no debe superar 5MB");
    }

    // Validar extensión
    $extensiones_permitidas = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    $ext = strtolower(pathinfo($comprobante['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $extensiones_permitidas)) {
        throw new Exception("❌ Formato de archivo no permitido. Usa: PDF, JPG, PNG, DOC, DOCX");
    }

    // Crear directorio si no existe
    $carpeta_comprobantes = __DIR__ . '/../../comprobantes/';
    if (!is_dir($carpeta_comprobantes)) {
        mkdir($carpeta_comprobantes, 0755, true);
    }

    // Generar nombre único
    $nombre_archivo = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $ext;
    $ruta_completa = $carpeta_comprobantes . $nombre_archivo;

    // Mover archivo
    if (!move_uploaded_file($comprobante['tmp_name'], $ruta_completa)) {
        throw new Exception("❌ Error al guardar el comprobante");
    }

    $ruta_comprobante = 'comprobantes/' . $nombre_archivo;

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
    // Determinar estado logístico basado en tipo de envío
    $estado_logistico = ($envio === 'foraneo') ? 'PENDIENTE GUIA' : 'SIN ENVIO';

    $stmt = $pdo->prepare("INSERT INTO tb_ventas (fecha, cliente, envio, total, id_usuario, estado_logistico, comprobante)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$fecha, $cliente, $envio, $total, $id_usuario, $estado_logistico, $ruta_comprobante]);

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
