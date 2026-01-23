<?php
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$sqlt = $pdo->prepare("SELECT 
        v.id_venta AS id,
        v.fecha,
        c.nombre_completo AS cliente_nombre,
        u.nombres AS vendedor_nombre,
        v.total

    FROM tb_ventas v
    JOIN tb_usuario u ON u.id = v.id_usuario
    JOIN clientes c ON v.cliente = c.id_cliente
    WHERE v.estado_logistico = 'ENVIADA'
    AND DATE(v.fecha) BETWEEN :desde AND :hasta
    ORDER BY v.fecha DESC");

$sqlt->execute([
    ':desde' => $desde,
    ':hasta' => $hasta
]);
$vendidos = $sqlt->fetchAll(PDO::FETCH_ASSOC);