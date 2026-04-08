<?php

require_once(dirname(__DIR__, 2) . '/config.php');
include(__DIR__ . '/../helpers/csrf.php');
include(__DIR__ . '/../helpers/validador.php');
csrf_verify();

$errores = validarDatos(['codigo', 'id_categoria', 'nombre', 'descripcion', 'stock_minimo', 'stock_maximo', 'precio_venta', 'fecha_ingreso', 'id_producto']);
if (!empty($errores)) {
    error400('Faltan datos obligatorios', $errores);
    $_SESSION['mensaje'] = "❌ Faltan datos obligatorios";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/almacen/update.php?id=' . ($_POST['id_producto'] ?? ''));
    exit;
}

$codigo        = $_POST['codigo'];
$id_categoria  = $_POST['id_categoria'];
$nombre        = $_POST['nombre'];
$id_usuario    = $_POST['id_usuario'];
$descripcion   = $_POST['descripcion'];
$calidad       = $_POST['calidad'] ?? '';
$piezas        = !empty($_POST['piezas']) ? (int)$_POST['piezas'] : null;
$stock_minimo  = $_POST['stock_minimo'];
$stock_maximo  = $_POST['stock_maximo'];
$precio_venta  = $_POST['precio_venta'];
$fecha_ingreso = $_POST['fecha_ingreso'];
$id_producto   = $_POST['id_producto'];
$image_text    = $_POST['image_text'];
$fechaHora     = date('Y-m-d H:i:s');

// ===========================
// PRECIO COMPRA SEGURO
// ===========================
if (in_array(34, $_SESSION['permisos'])) {
    $precio_compra = $_POST['precio_compra'] ?? '';
} else {
    $stmt = $pdo->prepare("SELECT precio_compra FROM tb_almacen WHERE id_producto = ?");
    $stmt->execute([$id_producto]);
    $precio_compra = $stmt->fetchColumn();
}

// ===========================
// IMAGEN
// ===========================
if ($_FILES['image']['name'] != null) {
    $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $mimeReal = mime_content_type($_FILES['image']['tmp_name']);
    $extImg = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    
    if (!in_array($mimeReal, $tiposPermitidos) || !in_array($extImg, $extensionesPermitidas)) {
        error400('Tipo de archivo no permitido', ['archivo' => $_FILES['image']['name']]);
        $_SESSION['mensaje'] = "❌ Solo imágenes (JPG, PNG, GIF, WEBP)";
        $_SESSION['icono'] = "error";
        header("Location: " . $URL . "/almacen/update.php?id=" . $id_producto);
        exit;
    }
    
    $maxSize = 5 * 1024 * 1024;
    if ($_FILES['image']['size'] > $maxSize) {
        error400('Archivo demasiado grande', ['tamaño' => $_FILES['image']['size']]);
        $_SESSION['mensaje'] = "❌ Imagen no puede superar 5MB";
        $_SESSION['icono'] = "error";
        header("Location: " . $URL . "/almacen/update.php?id=" . $id_producto);
        exit;
    }
    
    $image_text = date('Y-m-d-H-i-s') . "__" . uniqid() . "." . $extImg;
    $location = "../../../almacen/img_productos/" . $image_text;
    move_uploaded_file($_FILES['image']['tmp_name'], $location);
}

// ===========================
// ACTUALIZAR
// ===========================
try {
    $sentencia = $pdo->prepare("UPDATE tb_almacen
        SET
        nombre             = :nombre,
        descripcion        = :descripcion,
        calidad            = :calidad,
        piezas             = :piezas,
        stock_minimo       = :stock_minimo,
        stock_maximo       = :stock_maximo,
        precio_compra      = :precio_compra,
        precio_venta       = :precio_venta,
        fecha_ingreso      = :fecha_ingreso,
        id_categoria       = :id_categoria,
        id_usuario         = :id_usuario,
        imagen             = :imagen,
        fyh_actualizacion  = :fyh_actualizacion
        WHERE id_producto  = :id_producto");

    $sentencia->bindParam(':nombre', $nombre);
    $sentencia->bindParam(':descripcion', $descripcion);
    $sentencia->bindParam(':calidad', $calidad);
    $sentencia->bindParam(':piezas', $piezas);
    $sentencia->bindParam(':stock_minimo', $stock_minimo);
    $sentencia->bindParam(':stock_maximo', $stock_maximo);
    $sentencia->bindParam(':precio_compra', $precio_compra);
    $sentencia->bindParam(':precio_venta', $precio_venta);
    $sentencia->bindParam(':fecha_ingreso', $fecha_ingreso);
    $sentencia->bindParam(':id_categoria', $id_categoria);
    $sentencia->bindParam(':id_usuario', $id_usuario);
    $sentencia->bindParam(':imagen', $image_text);
    $sentencia->bindParam(':id_producto', $id_producto);
    $sentencia->bindParam(':fyh_actualizacion', $fechaHora);

    if ($sentencia->execute()) {
        include('../helpers/auditoria.php');
        registrarAuditoria($pdo, $id_usuario, $_SESSION['nombre_usuario'] ?? null, 'ACTUALIZAR PRODUCTO', 'tb_almacen', $id_producto, "Producto: $nombre (Código: $codigo)");
        $_SESSION['mensaje'] = "✅ Producto actualizado correctamente";
        $_SESSION['icono'] = "success";
        header("Location: " . $URL . "/almacen");
    } else {
        error500('Error ejecutando query de actualización');
        $_SESSION['mensaje'] = "❌ Error al actualizar producto";
        $_SESSION['icono'] = "error";
        header("Location: " . $URL . "/almacen/update.php?id=" . $id_producto);
    }
} catch (Exception $e) {
    error500('Error actualizando producto', ['error' => $e->getMessage()]);
    $_SESSION['mensaje'] = "❌ Error al actualizar producto";
    $_SESSION['icono'] = "error";
    header("Location: " . $URL . "/almacen/update.php?id=" . $id_producto);
}