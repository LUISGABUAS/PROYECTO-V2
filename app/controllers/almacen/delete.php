<?php

include('../../config.php');

$id_producto = $_POST['id_producto'];


    $sentencia = $pdo->prepare("DELETE FROM tb_almacen WHERE id_producto=:id_producto");
    
    $sentencia->bindParam(':id_producto', $id_producto);
    if($sentencia->execute()){ 

    session_start();
    $_SESSION['mensaje'] = "se ha eliminado el producto correctamente";
    header("Location: " . $URL . "/almacen/");
}else{
        session_start();
        $_SESSION['mensaje'] = "Error no se pudo eliminar el producto correctamente";
        header("Location: " . $URL . "/almacen/delete.php?id=" . $id_producto);
    }
