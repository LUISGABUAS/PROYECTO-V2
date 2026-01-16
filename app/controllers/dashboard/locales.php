<?php

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$permisos   = $_SESSION['permisos'];

$stmt = $pdo->prepare("SELECT 
        v.id_venta,
        v.fecha,
        c.nombre_completo AS cliente,
        c.calle_numero AS calle,
        c.colonia AS colonia,
        c.municipio AS municipio,
        c.estado AS estado,
        c.cp as cp,
        c.telefono AS telefono,
        c.referencias AS referencia,
        v.estado_logistico as estado_logistico,

        v.id_usuario,
        v.guia_pdf AS guia_pdf,
        u.nombres AS vendedor

    FROM tb_ventas v
    JOIN tb_usuario u ON u.id = v.id_usuario
    JOIN clientes c ON v.cliente = c.id_cliente
    WHERE v.envio = 'local'
    AND DATE(v.fecha) BETWEEN :desde AND :hasta
    ORDER BY u.nombres, v.fecha DESC
");

$stmt->execute([
    ':desde' => $desde,
    ':hasta' => $hasta
]);
$ventas_locales = $stmt->fetchAll(PDO::FETCH_ASSOC);
