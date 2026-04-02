<?php
include('../../config.php');
include('../helpers/auditoria.php');
session_start();

// Recibir datos del formulario
$id_rol = isset($_POST['id_rol']) ? (int)$_POST['id_rol'] : 0;
$rol_nombre = isset($_POST['rol']) ? trim($_POST['rol']) : '';
$permisos_seleccionados = isset($_POST['permisos']) ? $_POST['permisos'] : [];

// Validar
if ($id_rol <= 0 || empty($rol_nombre)) {
    $_SESSION['mensaje'] = "Datos inválidos";
    $_SESSION['icono'] = "error";
    header("Location: ../../../roles/index.php");
    exit;
}

// Comenzar transacción
try {
    $pdo->beginTransaction();

    // Actualizar nombre del rol
    $stmt = $pdo->prepare("UPDATE tb_roles SET rol = :rol, fyh_actualizacion = NOW() WHERE id_rol = :id_rol");
    $stmt->execute([
        ':rol' => $rol_nombre,
        ':id_rol' => $id_rol
    ]);

    // Eliminar permisos antiguos del rol
    $stmt = $pdo->prepare("DELETE FROM tb_roles_permisos WHERE id_rol = :id_rol");
    $stmt->execute([':id_rol' => $id_rol]);

    // Insertar permisos nuevos
    if (!empty($permisos_seleccionados)) {
        $stmt = $pdo->prepare("INSERT INTO tb_roles_permisos (id_rol, id_permiso) VALUES (:id_rol, :id_permiso)");
        foreach ($permisos_seleccionados as $permiso_id) {
            $stmt->execute([
                ':id_rol' => $id_rol,
                ':id_permiso' => (int)$permiso_id
            ]);
        }
    }

    $pdo->commit();

    $id_usuario_audit = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;
    $nombre_audit = $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null;
    registrarAuditoria($pdo, $id_usuario_audit, $nombre_audit, 'ACTUALIZAR ROL', 'tb_roles', $id_rol, $rol_nombre);

    $_SESSION['mensaje'] = "Rol actualizado correctamente";
    $_SESSION['icono'] = "success";

} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['mensaje'] = "Error al actualizar el rol";
    $_SESSION['icono'] = "error";
}

// Redirigir a la lista de roles
header("Location: ../../../roles/index.php");
exit;
?>
