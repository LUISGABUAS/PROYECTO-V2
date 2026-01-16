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

    cat.nombre_categoria AS categoria,
    u.nombres AS nombre_usuario,

    et.nombre_proveedor AS proveedor,

    /* 1️⃣ STOCK EN BODEGA */
    COALESCE((
        SELECT COUNT(*)
        FROM stock s
        WHERE s.id_producto = a.id_producto
          AND s.estado = 'EN BODEGA'
    ), 0) AS stock_bodega,

    /* 2️⃣ STOCK PENDIENTE POR ENTREGAR */
    COALESCE((
        SELECT SUM(vd.cantidad - vd.cantidad_entregada)
        FROM tb_ventas_detalle vd
        WHERE vd.id_producto = a.id_producto
          AND vd.cantidad_entregada < vd.cantidad
    ), 0) AS stock_pendiente,

    /* 3️⃣ STOCK REAL DISPONIBLE */
    (
        COALESCE((
            SELECT COUNT(*)
            FROM stock s
            WHERE s.id_producto = a.id_producto
              AND s.estado = 'EN BODEGA'
        ), 0)
        -
        COALESCE((
            SELECT SUM(vd.cantidad - vd.cantidad_entregada)
            FROM tb_ventas_detalle vd
            WHERE vd.id_producto = a.id_producto
              AND vd.cantidad_entregada < vd.cantidad
        ), 0)
    ) AS stock_disponible

FROM tb_almacen a
INNER JOIN tb_categorias cat ON a.id_categoria = cat.id_categoria
Inner JOIN tb_proveedores et ON a.id_proovedor = et.id_proovedor
INNER JOIN tb_usuario u ON u.id = a.id_usuario
";


$query_productos = $pdo->prepare($sql_productos);
$query_productos->execute();
$datos_productos = $query_productos->fetchAll(PDO::FETCH_ASSOC);
