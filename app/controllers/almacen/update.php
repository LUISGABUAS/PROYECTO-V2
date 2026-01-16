<?php

include('../../config.php');

$codigo = $_POST['codigo'];
$id_categoria = $_POST['id_categoria'];
$nombre = $_POST['nombre'];
$id_usuario = $_POST['id_usuario'];
$descripcion = $_POST['descripcion'];

$stock_minimo = $_POST['stock_minimo'];
$stock_maximo = $_POST['stock_maximo'];
$precio_compra = $_POST['precio_compra'];
$precio_venta = $_POST['precio_venta'];
$fecha_ingreso = $_POST['fecha_ingreso'];
$id_producto = $_POST['id_producto'];

$image_text = $_POST['image_text'];


if($_FILES['image']['name'] != null){
    $nombreDelArchivo = date('Y-m-d-H-i-s');
    $image_text = $nombreDelArchivo . "__" . $_FILES['image']['name'];
    $location = "../../../almacen/img_productos/" . $image_text;

    move_uploaded_file($_FILES['image']['tmp_name'], $location);

}else{

}

    
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
        WHERE id_producto =:id_producto");
    
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
        $sentencia->execute();
        if ($sentencia->execute()) {
            session_start();
            $_SESSION['mensaje'] = "se ha actualizado el producto correctamente";
            header("Location: " . $URL . "/almacen"); 
        }else{ 
        

    session_start();
    $_SESSION['mensaje'] = "Error no se pudo actualizar el producto, intente nuevamente";
    header("Location: " . $URL . "/almacen/update.php?id=".$id_producto);
}
   