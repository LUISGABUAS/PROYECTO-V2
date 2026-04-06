<?php

include('../../config.php');
include(__DIR__ . '/../helpers/csrf.php');
include(__DIR__ . '/../helpers/validador.php');
csrf_verify();

$errores = validarDatos(['id']);
if (!empty($errores)) {
    error400('ID de usuario requerido', $errores);
    $_SESSION['mensaje'] = "❌ Dato inválido";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/usuarios/delete.php');
    exit;
}

$id = $_POST['id'];
$id_usuario = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;

try {
    $sentencia = $pdo->prepare("DELETE FROM tb_usuario WHERE id=:id");
    $sentencia->bindParam(':id', $id);
    $sentencia->execute();

    include('../helpers/auditoria.php');
    registrarAuditoria($pdo, $id_usuario, $_SESSION['nombre_usuario'] ?? null, 'ELIMINAR USUARIO', 'tb_usuario', $id, "Usuario ID: $id eliminado");
    
    $_SESSION['mensaje'] = "✅ Usuario eliminado correctamente";
    $_SESSION['icono'] = "success";
    header("Location: " . $URL . "/usuarios");
} catch (Exception $e) {
    error500('Error eliminando usuario', ['error' => $e->getMessage()]);
    $_SESSION['mensaje'] = "❌ Error al eliminar usuario";
    $_SESSION['icono'] = "error";
    header("Location: " . $URL . "/usuarios/delete.php?id=" . $id);
}