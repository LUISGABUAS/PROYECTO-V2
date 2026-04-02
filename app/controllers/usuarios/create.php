<?php
include('../../config.php');
include('../helpers/auditoria.php');

$nombres = $_POST['nombres'];
$email = $_POST['email'];
$rol = $_POST['rol'];
$password_user = $_POST['password_user'];
$password_repeat = $_POST['password_repeat'];

if ($password_user == $password_repeat) {
    $password_user = password_hash($password_user, PASSWORD_DEFAULT);
    $sentencia = $pdo->prepare("INSERT INTO tb_usuario
 (nombres, email, id_rol, password_user, fyh_creacion)
  VALUES (:nombres, :email, :id_rol, :password_user, :fyh_creacion)");
    $sentencia->bindParam(':nombres', $nombres);
    $sentencia->bindParam(':email', $email);
    $sentencia->bindParam(':id_rol', $rol);
    $sentencia->bindParam(':password_user', $password_user);
    $sentencia->bindParam(':fyh_creacion', $fechaHora);
    $sentencia->execute();
    $id_nuevo_usuario = $pdo->lastInsertId();
    session_start();
    $id_usuario_audit = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;
    $nombre_audit = $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null;
    registrarAuditoria($pdo, $id_usuario_audit, $nombre_audit, 'CREAR USUARIO', 'tb_usuario', $id_nuevo_usuario, $nombres);
    $_SESSION['icon'] = "success";
    $_SESSION['mensaje'] = "se ha creado el usuario correctamente";
    header("Location: " . $URL . "/usuarios");
} else {
    session_start();
    $_SESSION['icon'] = "error";
    $_SESSION['mensaje'] = "Error las contraseñas no coinciden, intente nuevamente";
    header("Location: " . $URL . "/usuarios/create.php");
}

