<?php

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
    COALESCE(sb.stock_bodega, 0) - COALESCE(sp.stock_pendiente, 0) AS stock_disponible

FROM tb_almacen a
INNER JOIN tb_categorias cat ON a.id_categoria = cat.id_categoria
INNER JOIN tb_proveedores et ON a.id_proovedor = et.id_proovedor
INNER JOIN tb_usuario u ON u.id = a.id_usuario
LEFT JOIN (
    SELECT id_producto, COUNT(*) AS stock_bodega
    FROM stock
    WHERE estado = 'EN BODEGA'
    GROUP BY id_producto
) sb ON sb.id_producto = a.id_producto
LEFT JOIN (
    SELECT id_producto, SUM(cantidad - cantidad_entregada) AS stock_pendiente
    FROM tb_ventas_detalle
    WHERE cantidad_entregada < cantidad
    GROUP BY id_producto
) sp ON sp.id_producto = a.id_producto
";


$query_productos = $pdo->prepare($sql_productos);
$query_productos->execute();
$datos_productos = $query_productos->fetchAll(PDO::FETCH_ASSOC);
