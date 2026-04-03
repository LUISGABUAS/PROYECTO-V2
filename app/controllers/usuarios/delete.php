<?php

include('../../config.php');
include(__DIR__ . '/../helpers/csrf.php');
csrf_verify();

$id = $_POST['id'];


    $sentencia = $pdo->prepare("DELETE FROM tb_usuario WHERE id=:id");
    
    $sentencia->bindParam(':id', $id);
    $sentencia->execute();

    session_start();
    $_SESSION['mensaje'] = "se ha eliminado el usuario correctamente";
    include('../helpers/auditoria.php');
    $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
    $nombre_usuario_sesion = $_SESSION['nombre_usuario'] ?? null;
    registrarAuditoria($pdo, $id_usuario_sesion, $nombre_usuario_sesion, 'ELIMINAR USUARIO', 'tb_usuario', $id, "Usuario ID: $id eliminado");
    header("Location: " . $URL . "/usuarios");