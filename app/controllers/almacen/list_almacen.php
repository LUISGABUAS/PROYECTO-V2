<?php

$proveedores_lista = $pdo->query(
    "SELECT id_proovedor, nombre_proveedor FROM tb_proveedores ORDER BY nombre_proveedor"
)->fetchAll(PDO::FETCH_ASSOC);

$filtro_proveedor = isset($_GET['proveedor']) && $_GET['proveedor'] !== '' ? (int)$_GET['proveedor'] : null;

// Detectar si la columna tipo_especial ya existe (migración puede no haberse corrido)
$_col = $pdo->query("SHOW COLUMNS FROM stock LIKE 'tipo_especial'")->fetchAll();
$_tiene_especial = !empty($_col);

if ($_tiene_especial) {
    $join_bodega  = "WHERE estado = 'EN BODEGA' AND tipo_especial IS NULL";
    $join_especial = "LEFT JOIN (
        SELECT id_producto,
            SUM(tipo_especial = 'VIDEO')   AS stock_video,
            SUM(tipo_especial = 'FLEJADA') AS stock_flejada
        FROM stock
        WHERE estado = 'EN BODEGA' AND tipo_especial IS NOT NULL
        GROUP BY id_producto
    ) se ON se.id_producto = a.id_producto";
    $select_especial = "COALESCE(se.stock_video, 0)   AS stock_video,
    COALESCE(se.stock_flejada, 0) AS stock_flejada,";
} else {
    $join_bodega     = "WHERE estado = 'EN BODEGA'";
    $join_especial   = "";
    $select_especial = "0 AS stock_video, 0 AS stock_flejada,";
}

// Detectar si tb_descuentos existe
$_tiene_descuentos = (bool)$pdo->query("SHOW TABLES LIKE 'tb_descuentos'")->fetchColumn();
$join_descuento  = $_tiene_descuentos ? "LEFT JOIN (
    SELECT id_producto, id AS id_descuento, precio_descuento, porcentaje, fecha_fin
    FROM tb_descuentos
    WHERE NOW() BETWEEN fecha_inicio AND fecha_fin
    ORDER BY id DESC
) d_activo ON d_activo.id_producto = a.id_producto" : "";
$select_descuento = $_tiene_descuentos ? "COALESCE(d_activo.precio_descuento, a.precio_venta) AS precio_efectivo,
    d_activo.id_descuento,
    d_activo.porcentaje   AS descuento_porcentaje,
    d_activo.fecha_fin    AS descuento_fecha_fin," : "a.precio_venta AS precio_efectivo,
    NULL AS id_descuento,
    NULL AS descuento_porcentaje,
    NULL AS descuento_fecha_fin,";

$sql_productos = "SELECT
    a.id_producto,
    a.codigo,
    a.nombre,
    a.descripcion,
    a.imagen,
    a.precio_compra,
    a.precio_venta,
    a.fecha_ingreso,
    a.stock_minimo,
    a.stock_maximo,

    cat.nombre_categoria AS categoria,
    u.nombres AS nombre_usuario,
    et.nombre_proveedor AS proveedor,

    COALESCE(sb.stock_bodega, 0) AS stock_bodega,
    COALESCE(sp.stock_pendiente, 0) AS stock_pendiente,
    COALESCE(sb.stock_bodega, 0) - COALESCE(sp.stock_pendiente, 0) AS stock_disponible,
    $select_especial
    $select_descuento

    a.id_producto AS _id

FROM tb_almacen a
INNER JOIN tb_categorias cat ON a.id_categoria = cat.id_categoria
INNER JOIN tb_proveedores et ON a.id_proovedor = et.id_proovedor
INNER JOIN tb_usuario u ON u.id = a.id_usuario
LEFT JOIN (
    SELECT id_producto, COUNT(*) AS stock_bodega
    FROM stock
    $join_bodega
    GROUP BY id_producto
) sb ON sb.id_producto = a.id_producto
LEFT JOIN (
    SELECT id_producto, SUM(cantidad - cantidad_entregada) AS stock_pendiente
    FROM tb_ventas_detalle
    WHERE cantidad_entregada < cantidad
    GROUP BY id_producto
) sp ON sp.id_producto = a.id_producto
$join_especial
$join_descuento
WHERE 1=1
";

$params_productos = [];
if ($filtro_proveedor) {
    $sql_productos .= " AND a.id_proovedor = :id_proovedor";
    $params_productos[':id_proovedor'] = $filtro_proveedor;
}

$query_productos = $pdo->prepare($sql_productos);
$query_productos->execute($params_productos);
$datos_productos = $query_productos->fetchAll(PDO::FETCH_ASSOC);
