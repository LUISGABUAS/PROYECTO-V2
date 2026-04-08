<?php
// ESTE ARCHIVO SOLO TRAE DATOS

// Si el usuario es vendedor, solo ver sus propios clientes
// Si es admin u otro rol, ver todos
$id_usuario_sesion = $_SESSION['id_usuario_sesion'] ?? null;
$id_rol_sesion = $_SESSION['id_rol_sesion'] ?? null;
$filtro_vendedor = $_GET['id_vendedor'] ?? null; // Filtro enviado desde la vista

$sql = "SELECT *
        FROM clientes
        WHERE tipo_cliente = 'local'";

// Si el rol es vendedor (id_rol = 21), filtrar por su id
if ($id_rol_sesion == 21) {
    $sql .= " AND (id_vendedor = :id_usuario OR id_vendedor IS NULL)";
}
// Si es admin y selecciona un vendedor específico, filtrar por ese vendedor
elseif ($id_rol_sesion == 3 && !empty($filtro_vendedor)) {
    $sql .= " AND id_vendedor = :filtro_vendedor";
}

$sql .= " ORDER BY id_cliente DESC";

$stmt = $pdo->prepare($sql);

if ($id_rol_sesion == 21) {
    $stmt->bindParam(':id_usuario', $id_usuario_sesion, PDO::PARAM_INT);
} elseif ($id_rol_sesion == 3 && !empty($filtro_vendedor)) {
    $stmt->bindParam(':filtro_vendedor', $filtro_vendedor, PDO::PARAM_INT);
}

$stmt->execute();

$clientes_locales = $stmt->fetchAll(PDO::FETCH_ASSOC);
