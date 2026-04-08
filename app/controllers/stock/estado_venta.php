<?php
require_once(dirname(__DIR__, 2) . '/config.php');

if (!isset($_GET['id_venta'])) {
    echo '<div class="text-danger">Venta no válida.</div>';
    exit;
}

$id_venta = (int)$_GET['id_venta'];

$stmt = $pdo->prepare("
    SELECT 
        a.nombre,
        vd.cantidad AS vendidos,
        COUNT(s.id_stock) AS entregados
    FROM tb_ventas_detalle vd
    JOIN tb_almacen a 
        ON a.id_producto = vd.id_producto
    LEFT JOIN tb_ventas_stock vs 
        ON vs.id_venta = vd.id_venta
    LEFT JOIN stock s
        ON s.id_stock = vs.id_stock
       AND s.id_producto = vd.id_producto
       AND s.estado = 'VENDIDO'
    WHERE vd.id_venta = ?
    GROUP BY vd.id_detalle
");

$stmt->execute([$id_venta]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$productos) {
    echo '<div class="text-muted">No hay productos en esta venta.</div>';
    exit;
}
?>

<table class="table table-bordered table-sm">
    <thead class="thead-light">
        <tr class="text-center">
            <th>Producto</th>
            <th>Vendidos</th>
            <th>Entregados</th>
            <th>Progreso</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($productos as $p): 
            $porcentaje = $p['vendidos'] > 0
                ? min(100, round(($p['entregados'] / $p['vendidos']) * 100))
                : 0;

            $completo = $p['entregados'] >= $p['vendidos'];
        ?>
        <tr class="text-center <?= $completo ? 'table-success' : '' ?>">
            <td><?= htmlspecialchars($p['nombre']) ?></td>
            <td><?= $p['vendidos'] ?></td>
            <td><?= $p['entregados'] ?></td>
            <td>
                <div class="progress">
                    <div class="progress-bar <?= $completo ? 'bg-success' : 'bg-warning' ?>"
                         style="width: <?= $porcentaje ?>%">
                        <?= $porcentaje ?>%
                    </div>
                </div>
            </td>
            <td>
                <?= $completo
                    ? '<span class="badge badge-success">COMPLETO</span>'
                    : '<span class="badge badge-warning">PENDIENTE</span>' ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
