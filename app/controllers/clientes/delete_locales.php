<?php
include('../../config.php');
include(__DIR__ . '/../helpers/csrf.php');
include(__DIR__ . '/../helpers/validador.php');
csrf_verify();
include('../helpers/auditoria.php');

$errores = validarDatos(['id']);
if (!empty($errores)) {
    error400('ID de cliente requerido', $errores);
    $_SESSION['mensaje'] = '❌ Cliente no válido';
    $_SESSION['icono'] = 'error';
    header("Location: ../../../clientes/locales.php");
    exit;
}

$id = $_POST['id'];
$id_usuario = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;

try {
    $sql = "DELETE FROM clientes WHERE id_cliente = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    registrarAuditoria($pdo, $id_usuario, $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null, 'ELIMINAR CLIENTE LOCAL', 'clientes', $id, "Cliente ID: $id eliminado");
    
    $_SESSION['mensaje'] = '✅ Cliente eliminado correctamente';
    $_SESSION['icono'] = 'success';
    header("Location: ../../../clientes/locales.php");
} catch (Exception $e) {
    error500('Error eliminando cliente', ['error' => $e->getMessage()]);
    $_SESSION['mensaje'] = '❌ Error al eliminar cliente';
    $_SESSION['icono'] = 'error';
    header("Location: ../../../clientes/locales.php");
}
