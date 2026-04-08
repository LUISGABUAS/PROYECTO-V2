<?php
require_once(dirname(__DIR__, 2) . '/config.php');

header('Content-Type: application/json');

try {

    $sql = "
        SELECT 
            c.id_cliente,
            c.nombre_completo,
            c.tipo_cliente,
            COUNT(v.id_venta) AS total_compras
        FROM tb_ventas v
        INNER JOIN clientes c ON c.id_cliente = v.cliente
        GROUP BY c.id_cliente, c.nombre_completo, c.tipo_cliente
        ORDER BY total_compras DESC
        LIMIT 10
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => 'Error al generar el reporte'
    ]);
}
exit;
