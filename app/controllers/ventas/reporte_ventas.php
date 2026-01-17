<?php
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$permisos   = $_SESSION['permisos'];
$id_usuario = $id_usuario_sesion;

/* =====================
   CONSULTA BASE VENTAS
===================== */
$ventas = [];

if (in_array(24, $permisos)) {

    $stmt = $pdo->prepare("SELECT 
            v.id_venta,
            v.fecha,
            c.nombre_completo AS cliente,
            v.total,
            v.id_usuario,
            u.nombres AS vendedor
        FROM tb_ventas v
        JOIN tb_usuario u ON u.id = v.id_usuario
        JOIN clientes c ON v.cliente = c.id_cliente
        WHERE DATE(v.fecha) BETWEEN :desde AND :hasta
        ORDER BY u.nombres, v.fecha DESC
    ");

    $stmt->execute([
        ':desde' => $desde,
        ':hasta' => $hasta
    ]);

    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/* ==============================
   CONSULTA VENTAS DEL USUARIO
============================== */
$mis_ventas = [];



    $stmt = $pdo->prepare("SELECT 
            v.id_venta,
            v.fecha,
            c.nombre_completo AS cliente,
            v.total
        FROM tb_ventas v
        JOIN clientes c On v.cliente = c.id_cliente
        WHERE v.id_usuario = :usuario
        AND DATE(v.fecha) BETWEEN :desde AND :hasta
        ORDER BY v.fecha DESC
    ");

    $stmt->execute([
        ':usuario' => $id_usuario,
        ':desde'   => $desde,
        ':hasta'   => $hasta
    ]);

    $mis_ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================
   CALCULO TOTAL VENDIDO
===================== */

$total_vendido = 0;

foreach ($mis_ventas as $v) {
    $total_vendido += $v['total'];
}

/* =========================
   VENTAS TOTALES POR USUARIO / VENDEDOR
========================= */
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_ventas,
    SUM(total) as monto_total,
    ROUND(AVG(total), 2) as promedio_venta
    FROM tb_ventas v
    JOIN tb_usuario u ON v.id_usuario = u.id
    WHERE DATE(v.fecha) BETWEEN :desde AND :hasta
");
$stmt->execute([
    ':desde' => $desde,
    ':hasta' => $hasta
]);
$ventas_generales = $stmt->fetch(PDO::FETCH_ASSOC);


/* =====================
   DATOS PARA GRAFICAS PROPIAS
===================== */
$ventas_grafica = [];



    $stmt = $pdo->prepare("SELECT 
            DATE(fecha) AS dia,
            count(id_venta) AS total
        FROM tb_ventas
        WHERE id_usuario = :usuario
        AND DATE(fecha) BETWEEN :desde AND :hasta
        GROUP BY DATE(fecha)
        ORDER BY dia
    ");

    $stmt->execute([
        ':usuario' => $id_usuario,
        ':desde'   => $desde,
        ':hasta'   => $hasta
    ]);

    $ventas_grafica = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================
   DATOS PARA GRAFICAS TOTALIDADES
===================== */
$ventas_grafica_total = [];


    $stmt = $pdo->prepare("SELECT 
            DATE(fecha) AS dia,
            count(id_venta) AS total
        FROM tb_ventas
        WHERE DATE(fecha) BETWEEN :desde AND :hasta
        GROUP BY DATE(fecha)
        ORDER BY dia
    ");

    $stmt->execute([
        ':desde'   => $desde,
        ':hasta'   => $hasta
    ]);

    $ventas_grafica_total = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   STOCK DISPONIBLE
========================= */
$stmt = $pdo->prepare("SELECT 
    a.id_producto,
    a.codigo,
    a.nombre,
    cat.nombre_categoria,
    a.precio_venta,
    
    COALESCE((
        SELECT COUNT(*)
        FROM stock s
        WHERE s.id_producto = a.id_producto AND s.estado = 'EN BODEGA'
    ), 0) as stock_bodega,
    
    COALESCE((
        SELECT SUM(vd.cantidad - vd.cantidad_entregada)
        FROM tb_ventas_detalle vd
        WHERE vd.id_producto = a.id_producto AND vd.cantidad_entregada < vd.cantidad
    ), 0) as stock_pendiente,
    
    COALESCE((
        SELECT COUNT(*)
        FROM stock s
        WHERE s.id_producto = a.id_producto AND s.estado = 'EN BODEGA'
    ), 0) - COALESCE((
        SELECT SUM(vd.cantidad - vd.cantidad_entregada)
        FROM tb_ventas_detalle vd
        WHERE vd.id_producto = a.id_producto AND vd.cantidad_entregada < vd.cantidad
    ), 0) as stock_disponible
    
    FROM tb_almacen a
    INNER JOIN tb_categorias cat ON a.id_categoria = cat.id_categoria
    ORDER BY a.nombre ASC
");
$stmt->execute();
$productos_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);