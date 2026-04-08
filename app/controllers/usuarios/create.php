<?php
require_once(dirname(__DIR__, 2) . '/config.php');
include(__DIR__ . '/../helpers/csrf.php');
include(__DIR__ . '/../helpers/validador.php');
csrf_verify();
include('../helpers/auditoria.php');

$errores = validarDatos(['nombres', 'email', 'rol', 'password_user', 'password_repeat']);
if (!empty($errores)) {
    error400('Faltan datos obligatorios', $errores);
    $_SESSION['mensaje'] = "❌ Faltan datos obligatorios";
    $_SESSION['icono'] = "error";
    header("Location: " . $URL . "/usuarios/create.php");
    exit;
}

$nombres = $_POST['nombres'];
$email = $_POST['email'];
$rol = $_POST['rol'];
$password_user = $_POST['password_user'];
$password_repeat = $_POST['password_repeat'];
$id_usuario = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;

if (empty($password_user) || empty($password_repeat)) {
    error400('Contraseña requerida');
    $_SESSION['mensaje'] = "❌ Contraseña requerida";
    $_SESSION['icono'] = "error";
    header("Location: " . $URL . "/usuarios/create.php");
    exit;
}

if ($password_user !== $password_repeat) {
    error400('Las contraseñas no coinciden');
    $_SESSION['mensaje'] = "❌ Las contraseñas no coinciden";
    $_SESSION['icono'] = "error";
    header("Location: " . $URL . "/usuarios/create.php");
    exit;
}

try {
    $password_user = password_hash($password_user, PASSWORD_DEFAULT);
    $sentencia = $pdo->prepare("INSERT INTO tb_usuario (nombres, email, id_rol, password_user, fyh_creacion) VALUES (:nombres, :email, :id_rol, :password_user, :fyh_creacion)");
    $sentencia->bindParam(':nombres', $nombres);
    $sentencia->bindParam(':email', $email);
    $sentencia->bindParam(':id_rol', $rol);
    $sentencia->bindParam(':password_user', $password_user);
    $sentencia->bindParam(':fyh_creacion', $fechaHora);
    $sentencia->execute();
    
    $id_nuevo_usuario = $pdo->lastInsertId();
    registrarAuditoria($pdo, $id_usuario, $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null, 'CREAR USUARIO', 'tb_usuario', $id_nuevo_usuario, $nombres);
    
    $_SESSION['icono'] = "success";
    $_SESSION['mensaje'] = "✅ Usuario creado correctamente";
    header("Location: " . $URL . "/usuarios");
} catch (Exception $e) {
    error500('Error creando usuario', ['error' => $e->getMessage()]);
    $_SESSION['icono'] = "error";
    $_SESSION['mensaje'] = "❌ Error al crear usuario";
    header("Location: " . $URL . "/usuarios/create.php");
}

