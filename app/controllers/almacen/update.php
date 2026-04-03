<?php

include('../../config.php');
include(__DIR__ . '/../helpers/csrf.php');
csrf_verify();
session_start();

$codigo = $_POST['codigo'];
$id_categoria = $_POST['id_categoria'];
$nombre = $_POST['nombre'];
$id_usuario = $_POST['id_usuario'];
$descripcion = $_POST['descripcion'];
$stock_minimo = $_POST['stock_minimo'];
$stock_maximo = $_POST['stock_maximo'];
$precio_venta = $_POST['precio_venta'];
$fecha_ingreso = $_POST['fecha_ingreso'];
$id_producto = $_POST['id_producto'];
$image_text = $_POST['image_text'];
$fechaHora = date('Y-m-d H:i:s');

// ===========================
// PRECIO COMPRA SEGURO
// ===========================
if (in_array(34, $_SESSION['permisos'])) {
    // Tiene permiso, usar el valor del form
    $precio_compra = $_POST['precio_compra'] ?? '';
} else {
    // No tiene permiso, traer directo de la BD (ignorar POST)
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
        $_SESSION['mensaje'] = "Solo se permiten imágenes (JPG, PNG, GIF, WEBP)";
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
$sentencia = $pdo->prepare("UPDATE tb_almacen 
    SET 
    nombre=:nombre,
    descripcion=:descripcion,
    stock_minimo=:stock_minimo,
    stock_maximo=:stock_maximo,
    precio_compra=:precio_compra,
    precio_venta=:precio_venta,
    fecha_ingreso=:fecha_ingreso,
    id_categoria=:id_categoria,
    id_usuario=:id_usuario,
    imagen=:imagen,
    fyh_actualizacion=:fyh_actualizacion 
    WHERE id_producto=:id_producto");

$sentencia->bindParam(':nombre', $nombre);
$sentencia->bindParam(':descripcion', $descripcion);
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
    registrarAuditoria($pdo, $id_usuario, null, 'ACTUALIZAR PRODUCTO', 'tb_almacen', $id_producto, "Producto ID: $id_producto actualizado");
    $_SESSION['mensaje'] = "Se ha actualizado el producto correctamente";
    header("Location: " . $URL . "/almacen");
} else {
    $_SESSION['mensaje'] = "Error, no se pudo actualizar el producto, intente nuevamente";
    header("Location: " . $URL . "/almacen/update.php?id=" . $id_producto);
}