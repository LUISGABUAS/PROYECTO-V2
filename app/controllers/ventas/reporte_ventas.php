<?php
/* =========================
   FILTROS DE FECHA
========================= */
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$permisos   = $_SESSION['permisos'];
$id_usuario = $id_usuario_sesion;

/* =========================
   VALIDAR FECHAS
========================= */
if (strtotime($desde) > strtotime($hasta)) {
    $temp = $desde;
    $desde = $hasta;
    $hasta = $temp;
}

/* =========================
   📊 ESTADÍSTICAS GENERALES DEL SISTEMA (FILTRADAS)
========================= */
$ventas_generales = [];

if (in_array(24, $permisos)) {
    $stmt = $pdo->prepare("SELECT 
        COUNT(DISTINCT v.id_venta) as total_ventas,
        COALESCE(SUM(v.total), 0) as monto_total,
        ROUND(AVG(v.total), 2) as promedio_venta,
        COALESCE(SUM(vd.cantidad), 0) as total_pacas_sistema
        FROM tb_ventas v
        LEFT JOIN tb_ventas_detalle vd ON v.id_venta = vd.id_venta
        WHERE DATE(v.fecha) BETWEEN :desde AND :hasta
    ");
    $stmt->execute([
        ':desde' => $desde,
        ':hasta' => $hasta
    ]);
    $ventas_generales = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =========================
   👤 MIS ESTADÍSTICAS (FILTRADAS)
========================= */
$mis_ventas_cantidad = 0;
$total_vendido = 0;
$total_pacas_vendidas = 0;
$mis_comisiones = 0;

if (in_array(25, $permisos)) {
    $stmt = $pdo->prepare("SELECT 
        COUNT(DISTINCT v.id_venta) as cantidad_ventas,
        COALESCE(SUM(v.total), 0) as monto_vendido,
        COALESCE(SUM(vd.cantidad), 0) as total_pacas
        FROM tb_ventas v
        LEFT JOIN tb_ventas_detalle vd ON v.id_venta = vd.id_venta
        WHERE v.id_usuario = :usuario
        AND DATE(v.fecha) BETWEEN :desde AND :hasta
    ");
    $stmt->execute([
        ':usuario' => $id_usuario,
        ':desde'   => $desde,
        ':hasta'   => $hasta
    ]);
    $mis_estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $mis_ventas_cantidad = $mis_estadisticas['cantidad_ventas'];
    $total_vendido = $mis_estadisticas['monto_vendido'];
    $total_pacas_vendidas = $mis_estadisticas['total_pacas'];
    $mis_comisiones = $total_pacas_vendidas * 50; // $50 por PACA vendida
}

/* =========================
   📋 REPORTE GENERAL DE VENTAS (ADMIN)
========================= */
$ventas = [];

if (in_array(24, $permisos)) {
    $stmt = $pdo->prepare("SELECT 
        v.id_venta,
        v.fecha,
        c.nombre_completo AS cliente,
        v.total,
        v.id_usuario,
        u.nombres AS vendedor,
        COALESCE(SUM(vd.cantidad), 0) as total_pacas
        FROM tb_ventas v
        JOIN tb_usuario u ON u.id = v.id_usuario
        JOIN clientes c ON v.cliente = c.id_cliente
        LEFT JOIN tb_ventas_detalle vd ON v.id_venta = vd.id_venta
        WHERE DATE(v.fecha) BETWEEN :desde AND :hasta
        GROUP BY v.id_venta
        ORDER BY v.fecha DESC
    ");
    $stmt->execute([
        ':desde' => $desde,
        ':hasta' => $hasta
    ]);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================
   📋 MIS VENTAS (VENDEDOR)
========================= */
$mis_ventas = [];

if (in_array(25, $permisos)) {
    $stmt = $pdo->prepare("SELECT 
        v.id_venta,
        v.fecha,
        c.nombre_completo AS cliente,
        v.total,
        COALESCE(SUM(vd.cantidad), 0) as total_pacas
        FROM tb_ventas v
        JOIN clientes c ON v.cliente = c.id_cliente
        LEFT JOIN tb_ventas_detalle vd ON v.id_venta = vd.id_venta
        WHERE v.id_usuario = :usuario
        AND DATE(v.fecha) BETWEEN :desde AND :hasta
        GROUP BY v.id_venta
        ORDER BY v.fecha DESC
    ");
    $stmt->execute([
        ':usuario' => $id_usuario,
        ':desde'   => $desde,
        ':hasta'   => $hasta
    ]);
    $mis_ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================
   📊 GRÁFICA MIS VENTAS (POR MONTO)
========================= */
$ventas_grafica = [];

if (in_array(25, $permisos)) {
    $stmt = $pdo->prepare("SELECT 
        DATE(v.fecha) AS dia,
        SUM(v.total) AS total
        FROM tb_ventas v
        WHERE v.id_usuario = :usuario
        AND DATE(v.fecha) BETWEEN :desde AND :hasta
        GROUP BY DATE(v.fecha)
        ORDER BY dia
    ");
    $stmt->execute([
        ':usuario' => $id_usuario,
        ':desde'   => $desde,
        ':hasta'   => $hasta
    ]);
    $ventas_grafica = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================
   📊 GRÁFICA VENTAS TOTALES
========================= */
$ventas_grafica_total = [];

if (in_array(24, $permisos)) {
    $stmt = $pdo->prepare("SELECT 
        DATE(fecha) AS dia,
        SUM(total) AS total
        FROM tb_ventas
        WHERE DATE(fecha) BETWEEN :desde AND :hasta
        GROUP BY DATE(fecha)
        ORDER BY dia
    ");
    $stmt->execute([
        ':desde' => $desde,
        ':hasta' => $hasta
    ]);
    $ventas_grafica_total = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================
   📦 ESTADO DE STOCK
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
?>