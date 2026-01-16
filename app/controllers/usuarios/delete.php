<?php

include('../../config.php');

$id = $_POST['id'];


    $sentencia = $pdo->prepare("DELETE FROM tb_usuario WHERE id=:id");
    
    $sentencia->bindParam(':id', $id);
    $sentencia->execute();

    session_start();
    $_SESSION['mensaje'] = "se ha eliminado el usuario correctamente";
    header("Location: " . $URL . "/usuarios");