<?php

include('../../config.php');
include(__DIR__ . '/../helpers/csrf.php');
include(__DIR__ . '/../helpers/validador.php');
csrf_verify();
include('../helpers/auditoria.php');

$errores = validarDatos(['nombres', 'email', 'id', 'rol']);
if (!empty($errores)) {
    error400('Faltan datos obligatorios', $errores);
    $_SESSION['mensaje'] = "❌ Faltan datos obligatorios";
    $_SESSION['icono'] = "error";
    header("Location: " . $URL . "/usuarios/update.php?id=" . ($_POST['id'] ?? ''));
    exit;
}

$nombres = $_POST['nombres'];
$email = $_POST['email'];
$password_user = $_POST['password_user'] ?? '';
$password_repeat = $_POST['password_repeat'] ?? '';
$id = $_POST['id'];
$rol = $_POST['rol'];
$id_usuario = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;

// Si se intenta cambiar la contraseña, validar que coincida
if (!empty($password_user) || !empty($password_repeat)) {
    if ($password_user !== $password_repeat) {
        error400('Las contraseñas no coinciden');
        $_SESSION['mensaje'] = "❌ Las contraseñas no coinciden";
        $_SESSION['icono'] = "error";
        header("Location: " . $URL . "/usuarios/update.php?id=" . $id);
        exit;
    }
    if (empty($password_user)) {
        error400('Contraseña no puede estar vacía');
        $_SESSION['mensaje'] = "❌ Contraseña no puede estar vacía";
        $_SESSION['icono'] = "error";
        header("Location: " . $URL . "/usuarios/update.php?id=" . $id);
        exit;
    }
    $password_user = password_hash($password_user, PASSWORD_DEFAULT);
}

try {
    if (!empty($password_user)) {
        // Actualizar con nueva contraseña
        $sentencia = $pdo->prepare("UPDATE tb_usuario SET nombres=:nombres, email=:email, id_rol=:id_rol, password_user=:password_user, fyh_actualizacion=:fyh_actualizacion WHERE id=:id");
        $sentencia->bindParam(':password_user', $password_user);
    } else {
        // Actualizar sin contraseña
        $sentencia = $pdo->prepare("UPDATE tb_usuario SET nombres=:nombres, email=:email, id_rol=:id_rol, fyh_actualizacion=:fyh_actualizacion WHERE id=:id");
    }
    
    $sentencia->bindParam(':nombres', $nombres);
    $sentencia->bindParam(':email', $email);
    $sentencia->bindParam(':id_rol', $rol);
    $sentencia->bindParam(':fyh_actualizacion', $fechaHora);
    $sentencia->bindParam(':id', $id);
    $sentencia->execute();
    
    registrarAuditoria($pdo, $id_usuario, $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null, 'ACTUALIZAR USUARIO', 'tb_usuario', $id, $nombres);
    
    $_SESSION['mensaje'] = "✅ Usuario actualizado correctamente";
    $_SESSION['icono'] = "success";
    header("Location: " . $URL . "/usuarios");
} catch (Exception $e) {
    error500('Error actualizando usuario', ['error' => $e->getMessage()]);
    $_SESSION['mensaje'] = "❌ Error al actualizar usuario";
    $_SESSION['icono'] = "error";
    header("Location: " . $URL . "/usuarios/update.php?id=" . $id);
}