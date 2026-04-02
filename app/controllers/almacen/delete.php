<?php

include('../../config.php');

$id_producto = $_POST['id_producto'];


    $sentencia = $pdo->prepare("DELETE FROM tb_almacen WHERE id_producto=:id_producto");
    
    $sentencia->bindParam(':id_producto', $id_producto);
    if($sentencia->execute()){

    session_start();
    $_SESSION['mensaje'] = "se ha eliminado el producto correctamente";
    include('../helpers/auditoria.php');
    $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
    $nombre_usuario_sesion = $_SESSION['nombre_usuario'] ?? null;
    registrarAuditoria($pdo, $id_usuario_sesion, $nombre_usuario_sesion, 'ELIMINAR PRODUCTO', 'tb_almacen', $id_producto, "Producto ID: $id_producto eliminado");
    header("Location: " . $URL . "/almacen/");
}else{
        session_start();
        $_SESSION['mensaje'] = "Error no se pudo eliminar el producto correctamente";
        header("Location: " . $URL . "/almacen/delete.php?id=" . $id_producto);
    }
