<?php

$desde             = $_GET['desde'] ?? date('Y-m-01');
$hasta             = $_GET['hasta'] ?? date('Y-m-d');
$paqueteria_filtro = $_GET['paqueteria_filtro'] ?? '';

$permisos   = $_SESSION['permisos'];

$sql_base = "SELECT
        v.id_venta,
        v.fecha,
        c.nombre_completo AS cliente,
        COALESCE(d.nombre_destinatario, c.nombre_completo) AS destinatario,
        COALESCE(d.calle_numero, c.calle_numero) AS calle,
        COALESCE(d.colonia,     c.colonia)       AS colonia,
        COALESCE(d.municipio,   c.municipio)     AS municipio,
        COALESCE(d.estado,      c.estado)        AS estado,
        COALESCE(d.cp,          c.cp)            AS cp,
        c.telefono AS telefono,
        COALESCE(d.referencias, c.referencias)   AS referencia,
        v.estado_logistico AS estado_logistico,
        v.id_pedido,
        v.id_usuario,
        v.guia_pdf AS guia_pdf,
        v.paqueteria,
        u.nombres AS vendedor
    FROM tb_ventas v
    JOIN tb_usuario u ON u.id = v.id_usuario
    JOIN clientes c ON v.cliente = c.id_cliente
    LEFT JOIN clientes_direcciones d ON d.id = v.id_direccion_entrega
    WHERE v.envio = 'foraneo'
    AND DATE(v.fecha) BETWEEN :desde AND :hasta";

$params = [':desde' => $desde, ':hasta' => $hasta];

if ($paqueteria_filtro === 'sin_paqueteria') {
    $sql_base .= " AND (v.paqueteria IS NULL OR v.paqueteria = '')";
} elseif ($paqueteria_filtro) {
    $sql_base .= " AND v.paqueteria = :paqueteria_filtro";
    $params[':paqueteria_filtro'] = $paqueteria_filtro;
}

$sql_base .= " ORDER BY u.nombres, v.fecha DESC";

$stmt = $pdo->prepare($sql_base);
$stmt->execute($params);
$ventas_foraneos = $stmt->fetchAll(PDO::FETCH_ASSOC);
