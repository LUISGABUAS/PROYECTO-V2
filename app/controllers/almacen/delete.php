<?php

include('../../config.php');
include(__DIR__ . '/../helpers/csrf.php');
include(__DIR__ . '/../helpers/validador.php');
csrf_verify();

$errores = validarDatos(['id_producto']);
if (!empty($errores)) {
    error400('ID de producto requerido', $errores);
    $_SESSION['mensaje'] = "❌ Dato inválido";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/almacen/delete.php');
    exit;
}

$id_producto = $_POST['id_producto'];
$id_usuario = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;

try {
    $sentencia = $pdo->prepare("DELETE FROM tb_almacen WHERE id_producto=:id_producto");
    $sentencia->bindParam(':id_producto', $id_producto);
    
    if($sentencia->execute()){
        $_SESSION['mensaje'] = "✅ Producto eliminado correctamente";
        $_SESSION['icono'] = "success";
        include('../helpers/auditoria.php');
        registrarAuditoria($pdo, $id_usuario, $_SESSION['nombre_usuario'] ?? null, 'ELIMINAR PRODUCTO', 'tb_almacen', $id_producto, "Producto ID: $id_producto eliminado");
        header("Location: " . $URL . "/almacen/");
    } else {
        error500('Error al eliminar producto');
        $_SESSION['mensaje'] = "❌ Error al eliminar producto";
        $_SESSION['icono'] = "error";
        header("Location: " . $URL . "/almacen/delete.php?id=" . $id_producto);
    }
} catch (Exception $e) {
    error500('Error eliminando producto', ['error' => $e->getMessage()]);
    $_SESSION['mensaje'] = "❌ Error al eliminar producto";
    $_SESSION['icono'] = "error";
    header("Location: " . $URL . "/almacen/delete.php?id=" . $id_producto);
}
