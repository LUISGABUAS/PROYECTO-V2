<?php
include('../../config.php');
include(__DIR__ . '/../helpers/csrf.php');
csrf_verify();

$codigo = $_POST['codigo'];
$id_categoria = $_POST['id_categoria'];
$id_proovedor = $_POST['id_proovedor'];
$nombre = $_POST['nombre'];
$id_usuario = $_POST['id_usuario'];
$descripcion = $_POST['descripcion'];
$stock_minimo = $_POST['stock_minimo'];
$stock_maximo = $_POST['stock_maximo'];
$precio_compra = $_POST['precio_compra'];
$precio_venta = $_POST['precio_venta'];
$fecha_ingreso = $_POST['fecha_ingreso'];


$image = $_POST['image'];

$tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$mimeReal = mime_content_type($_FILES['image']['tmp_name']);
$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

if (!in_array($mimeReal, $tiposPermitidos) || !in_array($ext, $extensionesPermitidas)) {
    session_start();
    $_SESSION['mensaje'] = "Solo se permiten imágenes (JPG, PNG, GIF, WEBP)";
    header("Location: " . $URL . "/almacen/create.php");
    exit;
}

$maxSize = 5 * 1024 * 1024;
if ($_FILES['image']['size'] > $maxSize) {
    session_start();
    $_SESSION['mensaje'] = "La imagen no puede superar 5MB";
    header("Location: " . $URL . "/almacen/create.php");
    exit;
}

$nombreDelArchivo = date('Y-m-d-H-i-s');
$filename = $nombreDelArchivo . "__" . uniqid() . "." . $ext;
$location = "../../../almacen/img_productos/" . $filename;

move_uploaded_file($_FILES['image']['tmp_name'], $location);


    $sentencia = $pdo->prepare("INSERT INTO tb_almacen
         (codigo, id_proovedor ,nombre, descripcion, stock_minimo, stock_maximo, precio_compra, precio_venta, fecha_ingreso, imagen, id_categoria, id_usuario ,fyh_creacion, fyh_actualizacion)
  VALUES (:codigo, :id_proovedor, :nombre, :descripcion, :stock_minimo, :stock_maximo, :precio_compra, :precio_venta, :fecha_ingreso, :imagen, :id_categoria, :id_usuario, :fyh_creacion, :fyh_actualizacion)");

    $sentencia->bindParam(':codigo', $codigo);
    $sentencia->bindParam(':id_proovedor', $id_proovedor);
    $sentencia->bindParam(':nombre', $nombre);
    $sentencia->bindParam(':descripcion', $descripcion);
    $sentencia->bindParam(':stock_minimo', $stock_minimo);
    $sentencia->bindParam(':stock_maximo', $stock_maximo);
    $sentencia->bindParam(':precio_compra', $precio_compra);
    $sentencia->bindParam(':precio_venta', $precio_venta);
    $sentencia->bindParam(':fecha_ingreso', $fecha_ingreso);
    $sentencia->bindParam(':imagen', $filename);
    $sentencia->bindParam(':id_categoria', $id_categoria);
    $sentencia->bindParam(':id_proovedor', $id_proovedor);
    $sentencia->bindParam(':id_usuario', $id_usuario);
    $sentencia->bindParam(':fyh_creacion', $fechaHora);
    $sentencia->bindParam(':fyh_actualizacion', $fechaHora);

    if($sentencia ->execute()){
        session_start();
    $_SESSION['mensaje'] = "se ha creado el producto correctamente";
    include('../helpers/auditoria.php');
    registrarAuditoria($pdo, $id_usuario, null, 'CREAR PRODUCTO', 'tb_almacen', $pdo->lastInsertId(), "Producto: $nombre (Código: $codigo)");
    header("Location: " . $URL . "/almacen");
    }else{
        session_start();
    $_SESSION['mensaje'] = "No se ha podido crear el producto";
    header("Location: " . $URL . "/producto/create.php");
    }
