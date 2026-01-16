<?php
include('../../config.php');

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

// Arreglo de permisos por secciones
$permisos = [
    'Usuarios' => [
        ['id_permiso' => 1, 'nombre' => 'Ver Usuarios'],
        ['id_permiso' => 2, 'nombre' => 'Crear Usuarios']
    ],
    'Roles' => [
        ['id_permiso' => 3, 'nombre' => 'Ver Roles'],
        ['id_permiso' => 4, 'nombre' => 'Crear Roles']
    ],
    'Categorias' => [
        ['id_permiso' => 5, 'nombre' => 'Ver Categorias'],
        ['id_permiso' => 6, 'nombre' => 'Crear Categorias'],
        ['id_permiso' => 7, 'nombre' => 'Editar Categorias']
    ],
    'Productos' => [
        ['id_permiso' => 8, 'nombre' => 'Ver Productos'],
        ['id_permiso' => 9, 'nombre' => 'Crear Productos'],
        ['id_permiso' => 10, 'nombre' => 'Editar Productos']
    ],
    'Stock' => [
        ['id_permiso' => 11, 'nombre' => 'Ver Total Stock'],
        ['id_permiso' => 12, 'nombre' => 'Crear Stock'],
        ['id_permiso' => 13, 'nombre' => 'Eliminar Stock']
    ],
    'Entrada y Salida de Productos' => [
        ['id_permiso' => 14, 'nombre' => 'Entrada de Productos'],
        ['id_permiso' => 15, 'nombre' => 'Salida de Productos']
    ],
    'Proveedores' => [
        ['id_permiso' => 16, 'nombre' => 'Ver Proveedores'],
        ['id_permiso' => 17, 'nombre' => 'Crear Proveedores'],
        ['id_permiso' => 18, 'nombre' => 'Editar Proveedores'],
        ['id_permiso' => 19, 'nombre' => 'Eliminar Proveedores']
    ],
    'Ventas' => [
        ['id_permiso' => 20, 'nombre' => 'Ver Ventas'],
        ['id_permiso' => 21, 'nombre' => 'Crear Ventas'],
        ['id_permiso' => 22, 'nombre' => 'Editar Ventas'],
        ['id_permiso' => 28, 'nombre' => 'Eliminar Ventas']
    ],
    'Clientes' => [
        ['id_permiso' => 23, 'nombre' => 'Ver Clientes']
    ],
    'Reportes' => [
        ['id_permiso' => 24, 'nombre' => 'Ver Reportes'],
        ['id_permiso' => 25, 'nombre' => 'Ver Reportes Propios']
    ]
];

// Traer permisos actuales del rol
$stmt = $pdo->prepare("SELECT id_permiso FROM tb_roles_permisos WHERE id_rol = :id_rol");
$stmt->execute([':id_rol' => $id_rol]);
$permisos_rol = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
 // Array con los IDs de permisos asignados

