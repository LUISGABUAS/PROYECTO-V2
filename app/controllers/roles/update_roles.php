<?php
require_once(dirname(__DIR__, 2) . '/config.php');
include(__DIR__ . '/../helpers/csrf.php');

$id_rol = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_rol <= 0) {
    header("Location: ../../roles/index.php");
    exit;
}

// Traer datos del rol
$stmt = $pdo->prepare("SELECT * FROM tb_roles WHERE id_rol = :id_rol");
$stmt->execute([':id_rol' => $id_rol]);
$rol_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rol_data) {
    header("Location: ../../roles/index.php");
    exit;
}

$id_rol_get = $rol_data['id_rol'];
$rol = $rol_data['rol'];

// Usar el archivo central de permisos (único punto de verdad)
include(__DIR__ . '/../permisos/permisos.php');

// Traer permisos actuales del rol
$stmt = $pdo->prepare("SELECT id_permiso FROM tb_roles_permisos WHERE id_rol = :id_rol");
$stmt->execute([':id_rol' => $id_rol]);
$permisos_rol = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
 // Array con los IDs de permisos asignados

