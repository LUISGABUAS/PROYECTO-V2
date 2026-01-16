<?php

$desde = $_GET['desde'] ?? null;
$hasta = $_GET['hasta'] ?? null;

$whereFecha = '';
$params = [':id_producto' => $id_producto];

if ($desde && $hasta) {
    $whereFecha = " AND DATE(s.fecha_ingreso) BETWEEN :desde AND :hasta";
    $params[':desde'] = $desde;
    $params[':hasta'] = $hasta;
}

$sql = "SELECT 
    s.id_stock,
    s.codigo_unico,
    s.estado,
    s.fecha_ingreso,
    s.fecha_salida,
    a.codigo AS codigo_producto,
    a.nombre AS nombre_producto,
    c.nombre_categoria,
    u.nombres AS creado_por

FROM stock s
INNER JOIN tb_almacen a ON s.id_producto = a.id_producto
INNER JOIN tb_categorias c ON a.id_categoria = c.id_categoria
INNER JOIN tb_usuario u ON s.creado_por = u.id

WHERE s.id_producto = :id_producto
$whereFecha

ORDER BY s.fecha_ingreso DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$datos_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
