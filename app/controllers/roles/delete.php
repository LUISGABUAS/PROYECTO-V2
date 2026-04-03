<?php
include('../../config.php');
include(__DIR__ . '/../helpers/csrf.php');
csrf_verify();
include('../helpers/auditoria.php');
session_start();

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'icon' => 'error'
];

$id_rol = (int)($_POST['id_rol'] ?? 0);

/* Validación ID */
if ($id_rol <= 0) {
    $response['message'] = 'Rol inválido';
    echo json_encode($response);
    exit;
}

/* ❌ Proteger rol ADMIN */
if ($id_rol === 1) {
    $response['message'] = 'No se puede eliminar el rol administrador';
    $response['icon'] = 'warning';
    echo json_encode($response);
    exit;
}

/* 🔍 Verificar usuarios */
$checkUsuarios = $pdo->prepare("
    SELECT COUNT(*) 
    FROM tb_usuario 
    WHERE id_rol = :id_rol
");
$checkUsuarios->execute([':id_rol' => $id_rol]);

if ($checkUsuarios->fetchColumn() > 0) {
    $response['message'] = 'No se puede eliminar el rol porque tiene usuarios asignados';
    $response['icon'] = 'warning';
    echo json_encode($response);
    exit;
}

try {
    $pdo->beginTransaction();

    /* 🧹 Eliminar permisos */
    $deletePermisos = $pdo->prepare("
        DELETE FROM tb_roles_permisos 
        WHERE id_rol = :id_rol
    ");
    $deletePermisos->execute([':id_rol' => $id_rol]);

    /* 🗑️ Eliminar rol */
    $deleteRol = $pdo->prepare("
        DELETE FROM tb_roles 
        WHERE id_rol = :id_rol
    ");
    $deleteRol->execute([':id_rol' => $id_rol]);

    $pdo->commit();

    $id_usuario_audit = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;
    $nombre_audit = $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null;
    registrarAuditoria($pdo, $id_usuario_audit, $nombre_audit, 'ELIMINAR ROL', 'tb_roles', $id_rol, "Rol ID: $id_rol eliminado");

    $response['success'] = true;
    $response['message'] = 'Rol eliminado correctamente';
    $response['icon'] = 'success';

} catch (PDOException $e) {
    $pdo->rollBack();
    $response['message'] = 'Error al eliminar el rol';
}

echo json_encode($response);
exit;
