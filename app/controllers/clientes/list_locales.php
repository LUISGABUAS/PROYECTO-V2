<?php
// ESTE ARCHIVO SOLO TRAE DATOS

$id_usuario_sesion = $_SESSION['id_usuario_sesion'] ?? null;
$id_rol_sesion     = $_SESSION['id_rol_sesion'] ?? null;
$filtro_vendedor   = $_GET['id_vendedor'] ?? ''; // Filtro enviado desde la vista

$sql = "SELECT * FROM clientes WHERE tipo_cliente = 'local'";

$params = [];

if ($id_rol_sesion == 21) {
    // Vendedor: solo ve sus propios clientes
    $sql .= " AND id_vendedor = :id_usuario";
    $params[':id_usuario'] = $id_usuario_sesion;
} elseif (!empty($filtro_vendedor)) {
    // Cualquier otro rol con filtro activo
    $sql .= " AND id_vendedor = :filtro_vendedor";
    $params[':filtro_vendedor'] = (int)$filtro_vendedor;
}

$sql .= " ORDER BY id_cliente DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$clientes_locales = $stmt->fetchAll(PDO::FETCH_ASSOC);
