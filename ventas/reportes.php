<?php
include('../app/config.php');
include('../layout/sesion.php');

if (!in_array(24, $_SESSION['permisos'] ?? [])) {
    header('Location: ' . $URL);
    exit;
}

include('../layout/parte1.php');

/* =========================
   FILTROS DE FECHA
========================= */
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin    = $_GET['fecha_fin']    ?? date('Y-m-d');

/* =========================
   SECCIÓN 1: RESUMEN GENERAL
========================= */
$stmt = $pdo->prepare("SELECT
    COUNT(*) as total_ventas,
    COALESCE(SUM(total), 0) as monto_total,
    COALESCE(ROUND(AVG(total), 2), 0) as promedio_venta
    FROM tb_ventas
    WHERE DATE(fecha) BETWEEN :desde AND :hasta
");
$stmt->execute([':desde' => $fecha_inicio, ':hasta' => $fecha_fin]);
$resumen = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   SECCIÓN 2: VENTAS POR VENDEDOR
========================= */
$stmt = $pdo->prepare("SELECT
    COALESCE(u.nombres, 'Sin asignar') as vendedor,
    COUNT(DISTINCT v.id_venta) as total_ventas,
    COALESCE(SUM(v.total), 0) as monto_total,
    COALESCE(ROUND(AVG(v.total), 2), 0) as promedio
    FROM tb_ventas v
    LEFT JOIN tb_usuario u ON v.id_usuario = u.id
    WHERE DATE(v.fecha) BETWEEN :desde AND :hasta
    GROUP BY v.id_usuario
    ORDER BY monto_total DESC
");
$stmt->execute([':desde' => $fecha_inicio, ':hasta' => $fecha_fin]);
$ventas_vendedor = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   SECCIÓN 3: PRODUCTOS MÁS VENDIDOS
========================= */
$stmt = $pdo->prepare("SELECT
    a.codigo,
    a.nombre,
    SUM(vd.cantidad) as total_vendido,
    SUM(vd.cantidad * vd.precio) as monto_total
    FROM tb_ventas_detalle vd
    INNER JOIN tb_almacen a ON vd.id_producto = a.id_producto
    INNER JOIN tb_ventas v ON vd.id_venta = v.id_venta
    WHERE DATE(v.fecha) BETWEEN :desde AND :hasta
    GROUP BY vd.id_producto
    ORDER BY total_vendido DESC
");
$stmt->execute([':desde' => $fecha_inicio, ':hasta' => $fecha_fin]);
$productos_vendidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   SECCIÓN 4: VENTAS POR TIPO (LOCAL/FORÁNEO)
========================= */
$stmt = $pdo->prepare("SELECT
    envio as tipo,
    COUNT(*) as total_ventas,
    COALESCE(SUM(total), 0) as monto_total
    FROM tb_ventas
    WHERE DATE(fecha) BETWEEN :desde AND :hasta
    GROUP BY envio
    ORDER BY monto_total DESC
");
$stmt->execute([':desde' => $fecha_inicio, ':hasta' => $fecha_fin]);
$ventas_tipo = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <!-- Content Header -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Reportes Ampliados de Ventas</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= $URL ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= $URL ?>/ventas">Ventas</a></li>
            <li class="breadcrumb-item active">Reportes</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <!-- Main content -->
  <div class="content">
    <div class="container-fluid">

      <!-- FILTROS -->
      <div class="row mb-3">
        <div class="col-md-12">
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title">Filtrar por Período</h3>
            </div>
            <div class="card-body">
              <form method="GET" class="form-inline">
                <div class="form-group mr-2">
                  <label for="fecha_inicio" class="mr-2">Desde:</label>
                  <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control"
                         value="<?= htmlspecialchars($fecha_inicio) ?>" required>
                </div>
                <div class="form-group mr-2">
                  <label for="fecha_fin" class="mr-2">Hasta:</label>
                  <input type="date" id="fecha_fin" name="fecha_fin" class="form-control"
                         value="<?= htmlspecialchars($fecha_fin) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary mr-2">
                  <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="?fecha_inicio=<?= date('Y-m-01') ?>&fecha_fin=<?= date('Y-m-d') ?>" class="btn btn-secondary">
                  <i class="fas fa-redo"></i> Mes actual
                </a>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- SECCIÓN 1: RESUMEN GENERAL -->
      <div class="row mb-4">
        <div class="col-md-4">
          <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fa fa-shopping-cart"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Total Ventas</span>
              <span class="info-box-number"><?= $resumen['total_ventas'] ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-dollar-sign"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Monto Total</span>
              <span class="info-box-number">$<?= number_format($resumen['monto_total'], 2) ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-chart-line"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Promedio por Venta</span>
              <span class="info-box-number">$<?= number_format($resumen['promedio_venta'], 2) ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- SECCIÓN 2: VENTAS POR VENDEDOR -->
      <div class="row mb-4">
        <div class="col-md-12">
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">Ventas por Vendedor</h3>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table id="tablaVendedores" class="table table-bordered table-striped table-sm">
                  <thead class="thead-light">
                    <tr>
                      <th>#</th>
                      <th>Vendedor</th>
                      <th class="text-center"># Ventas</th>
                      <th class="text-right">Total $</th>
                      <th class="text-right">Promedio $</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if(empty($ventas_vendedor)): ?>
                    <tr><td colspan="5" class="text-center text-muted">Sin datos en el período seleccionado</td></tr>
                    <?php else: ?>
                    <?php $num = 1; ?>
                    <?php foreach($ventas_vendedor as $vv): ?>
                    <tr>
                      <td><?= $num++ ?></td>
                      <td><?= htmlspecialchars($vv['vendedor']) ?></td>
                      <td class="text-center"><span class="badge badge-info"><?= $vv['total_ventas'] ?></span></td>
                      <td class="text-right font-weight-bold">$<?= number_format($vv['monto_total'], 2) ?></td>
                      <td class="text-right">$<?= number_format($vv['promedio'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SECCIÓN 3: PRODUCTOS MÁS VENDIDOS -->
      <div class="row mb-4">
        <div class="col-md-12">
          <div class="card card-success">
            <div class="card-header">
              <h3 class="card-title">Productos Más Vendidos</h3>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table id="tablaProductos" class="table table-bordered table-striped table-sm">
                  <thead class="thead-light">
                    <tr>
                      <th>#</th>
                      <th>Código</th>
                      <th>Producto</th>
                      <th class="text-center">Unidades Vendidas</th>
                      <th class="text-right">Monto Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if(empty($productos_vendidos)): ?>
                    <tr><td colspan="5" class="text-center text-muted">Sin datos en el período seleccionado</td></tr>
                    <?php else: ?>
                    <?php $num = 1; ?>
                    <?php foreach($productos_vendidos as $pv): ?>
                    <tr>
                      <td><?= $num++ ?></td>
                      <td><?= htmlspecialchars($pv['codigo']) ?></td>
                      <td><?= htmlspecialchars($pv['nombre']) ?></td>
                      <td class="text-center"><span class="badge badge-success"><?= $pv['total_vendido'] ?></span></td>
                      <td class="text-right font-weight-bold">$<?= number_format($pv['monto_total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SECCIÓN 4: VENTAS POR TIPO -->
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="card card-warning">
            <div class="card-header">
              <h3 class="card-title">Ventas por Tipo (Local / Foráneo)</h3>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table id="tablaTipo" class="table table-bordered table-striped table-sm">
                  <thead class="thead-light">
                    <tr>
                      <th>Tipo</th>
                      <th class="text-center"># Ventas</th>
                      <th class="text-right">Total $</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if(empty($ventas_tipo)): ?>
                    <tr><td colspan="3" class="text-center text-muted">Sin datos en el período seleccionado</td></tr>
                    <?php else: ?>
                    <?php foreach($ventas_tipo as $vt): ?>
                    <tr>
                      <td>
                        <?php if($vt['tipo'] === 'local'): ?>
                          <span class="badge badge-success">Local</span>
                        <?php else: ?>
                          <span class="badge badge-warning">Foráneo</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-center"><?= $vt['total_ventas'] ?></td>
                      <td class="text-right font-weight-bold">$<?= number_format($vt['monto_total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /.container-fluid -->
  </div><!-- /.content -->
</div><!-- /.content-wrapper -->

<?php include('../layout/parte2.php'); ?>

<script>
$(function () {
    var dtOptions = {
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "buttons": [
            {
                extend: 'collection',
                text: 'Exportar',
                buttons: [
                    { text: 'Excel', extend: 'excel' },
                    { text: 'PDF',   extend: 'pdf'   },
                    { text: 'Imprimir', extend: 'print' }
                ]
            }
        ]
    };

    ["#tablaVendedores", "#tablaProductos", "#tablaTipo"].forEach(function(id) {
        $(id).DataTable(dtOptions)
             .buttons().container()
             .appendTo(id + '_wrapper .col-md-6:eq(0)');
    });
});
</script>
