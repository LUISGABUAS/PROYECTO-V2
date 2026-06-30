<?php
require_once dirname(__DIR__, 2) . '/config.php';

$indexes = [
    // Stock: la query más pesada del sistema (cuenta EN BODEGA por producto)
    "CREATE INDEX IF NOT EXISTS idx_stock_producto_estado ON stock (id_producto, estado)",

    // Ventas detalle: pendientes de entrega (usada en cada carga de almacén y stock)
    "CREATE INDEX IF NOT EXISTS idx_vd_producto_entrega ON tb_ventas_detalle (id_producto, cantidad_entregada, cantidad)",

    // Ventas: filtros por fecha (reporte de ventas)
    "CREATE INDEX IF NOT EXISTS idx_ventas_fecha ON tb_ventas (fecha)",

    // Ventas: filtros por usuario (mis ventas)
    "CREATE INDEX IF NOT EXISTS idx_ventas_usuario ON tb_ventas (id_usuario)",

    // Ventas: filtros por usuario + fecha combinados
    "CREATE INDEX IF NOT EXISTS idx_ventas_usuario_fecha ON tb_ventas (id_usuario, fecha)",

    // Comprobantes: join con ventas
    "CREATE INDEX IF NOT EXISTS idx_comprobantes_venta ON tb_ventas_comprobantes (id_venta)",

    // Almacén: búsqueda por proveedor (filtro nuevo)
    "CREATE INDEX IF NOT EXISTS idx_almacen_proveedor ON tb_almacen (id_proovedor)",
];

// MySQL no soporta IF NOT EXISTS en CREATE INDEX — usamos la alternativa
$indexesMysql = [
    ['stock',                'idx_stock_producto_estado',  '(id_producto, estado)'],
    ['tb_ventas_detalle',    'idx_vd_producto_entrega',    '(id_producto, cantidad_entregada, cantidad)'],
    ['tb_ventas',            'idx_ventas_fecha',           '(fecha)'],
    ['tb_ventas',            'idx_ventas_usuario',         '(id_usuario)'],
    ['tb_ventas',            'idx_ventas_usuario_fecha',   '(id_usuario, fecha)'],
    ['tb_ventas_comprobantes','idx_comprobantes_venta',    '(id_venta)'],
    ['tb_almacen',           'idx_almacen_proveedor',      '(id_proovedor)'],
];

$ok = 0; $skip = 0; $errors = [];

foreach ($indexesMysql as [$tabla, $nombre, $columnas]) {
    // Verificar si ya existe
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = ?
          AND index_name = ?
    ");
    $stmt->execute([$tabla, $nombre]);
    if ($stmt->fetchColumn() > 0) {
        echo "⏭️  Ya existe: $nombre<br>";
        $skip++;
        continue;
    }

    try {
        $pdo->exec("CREATE INDEX $nombre ON $tabla $columnas");
        echo "✅ Creado: $nombre en $tabla $columnas<br>";
        $ok++;
    } catch (PDOException $e) {
        echo "❌ Error en $nombre: " . $e->getMessage() . "<br>";
        $errors[] = $nombre;
    }
}

echo "<hr><strong>Resumen: $ok creados, $skip ya existían, " . count($errors) . " errores.</strong>";
