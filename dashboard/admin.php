<?php
include('../app/config.php');
include('../layout/sesion.php');

// Verificar permiso ANTES de enviar headers
if (!in_array(24, $_SESSION['permisos'] ?? [])) {
    header('Location: ' . $URL);
    exit;
}

include('../layout/parte1.php');

/* =========================
   VENTAS TOTALES
========================= */
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_ventas,
    SUM(total) as monto_total,
    ROUND(AVG(total), 2) as promedio_venta
    FROM tb_ventas
");
$stmt->execute();
$ventas_generales = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   VENTAS POR TIPO (LOCAL/FORANEO)
========================= */
$stmt = $pdo->prepare("SELECT 
    envio,
    COUNT(*) as cantidad,
    SUM(total) as monto
    FROM tb_ventas
    GROUP BY envio
");
$stmt->execute();
$ventas_por_tipo = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   DATOS PARA GRÁFICA (ÚLTIMOS 7 DÍAS)
========================= */
$stmt = $pdo->prepare("SELECT 
    DATE(fecha) as fecha,
    SUM(total) as total
    FROM tb_ventas
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(fecha)
    ORDER BY fecha ASC
");
$stmt->execute();
$ventas_ultimos_7 = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

/* =========================
   TABLA DE VENTAS CON FILTRO POR FECHAS
========================= */
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Ventas por día en el período
$stmt = $pdo->prepare("SELECT 
    DATE(fecha) as fecha,
    SUM(total) as total,
    COUNT(*) as cantidad
    FROM tb_ventas
    WHERE DATE(fecha) BETWEEN ? AND ?
    GROUP BY DATE(fecha)
    ORDER BY fecha ASC
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$ventas_por_dia = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ventas por tipo en el período
$stmt = $pdo->prepare("SELECT 
    envio,
    COUNT(*) as cantidad,
    SUM(total) as monto
    FROM tb_ventas
    WHERE DATE(fecha) BETWEEN ? AND ?
    GROUP BY envio
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$ventas_periodo_tipo = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tabla de ventas filtrada
$stmt = $pdo->prepare("SELECT 
    v.id_venta,
    v.fecha,
    COALESCE(c.nombre_completo, 'S/N') as cliente,
    COALESCE(u.nombres, 'S/N') as vendedor,
    v.envio,
    (SELECT COUNT(*) FROM tb_ventas_detalle WHERE id_venta = v.id_venta) as cantidad_items,
    v.total,
    v.estado_logistico
    FROM tb_ventas v
    LEFT JOIN clientes c ON v.cliente = c.id_cliente
    LEFT JOIN tb_usuario u ON v.id_usuario = u.id
    WHERE DATE(v.fecha) BETWEEN ? AND ?
    ORDER BY v.fecha DESC
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$ventas_tabla = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Preparar datos para gráfica de línea del período
$fechas_periodo = json_encode(array_map(fn($d) => $d['fecha'], $ventas_por_dia));
$montos_periodo = json_encode(array_map(fn($d) => (float)$d['total'], $ventas_por_dia));
$cantidades_periodo = json_encode(array_map(fn($d) => (int)$d['cantidad'], $ventas_por_dia));

// Preparar datos para gráfica de pastel del período
$tipos_envio_periodo = [];
$cantidades_envio_periodo = [];
$colores_envio = ['#28a745', '#ffc107'];
foreach ($ventas_periodo_tipo as $vt) {
    $tipos_envio_periodo[] = ucfirst($vt['envio']);
    $cantidades_envio_periodo[] = (int)$vt['cantidad'];
}
$tipos_periodo_json = json_encode($tipos_envio_periodo);
$cantidades_periodo_json = json_encode($cantidades_envio_periodo);

// Preparar datos para gráfica de línea
$fechas_json = json_encode(array_map(fn($d) => $d['fecha'], $ventas_ultimos_7));
$montos_json = json_encode(array_map(fn($d) => (float)$d['total'], $ventas_ultimos_7));

// Preparar datos para gráfica de pastel
$tipos_envio = [];
$cantidades_envio = [];
$montos_envio = [];
foreach ($ventas_por_tipo as $vt) {
    $tipos_envio[] = ucfirst($vt['envio']);
    $cantidades_envio[] = (int)$vt['cantidad'];
    $montos_envio[] = (float)$vt['monto'];
}
$tipos_json = json_encode($tipos_envio);
$cantidades_json = json_encode($cantidades_envio);
$montos_json_tipos = json_encode($montos_envio);
?>

<div class="content-wrapper">
  <div class="content-header">

    <!-- FILTRO POR FECHAS -->
              <form method="GET" class="form-inline mb-3">
                <div class="form-group mr-2">
                  <label for="fecha_inicio" class="mr-2">Fecha Inicio:</label>
                  <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" 
                         value="<?= htmlspecialchars($fecha_inicio) ?>" required>
                </div>
                <div class="form-group mr-2">
                  <label for="fecha_fin" class="mr-2">Fecha Fin:</label>
                  <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" 
                         value="<?= htmlspecialchars($fecha_fin) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="?fecha_inicio=<?= date('Y-m-01') ?>&fecha_fin=<?= date('Y-m-d') ?>" class="btn btn-secondary ml-2">
                  <i class="fas fa-redo"></i> Limpiar
                </a>
              </form>

    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">📊 Dashboard - Análisis de Ventas</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= $URL ?>">Home</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <!-- TARJETAS DE RESUMEN -->
      <div class="row">
        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fa fa-shopping-cart"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Total de Ventas</span>
              <span class="info-box-number"><?= $ventas_generales['total_ventas'] ?? 0 ?></span>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-dollar-sign"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Monto Total</span>
              <span class="info-box-number">$<?= number_format($ventas_generales['monto_total'] ?? 0, 2) ?></span>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-chart-line"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Promedio Venta</span>
              <span class="info-box-number">$<?= number_format($ventas_generales['promedio_venta'] ?? 0, 2) ?></span>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-danger"><i class="fas fa-boxes"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Productos</span>
              <span class="info-box-number"><?= count($productos_stock) ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- GRÁFICAS -->
      <div class="row">
        <!-- Gráfica de línea: Ventas últimos 7 días -->
        <div class="col-md-8">
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">📈 Ventas Últimos 7 Días</h3>
            </div>
            <div class="card-body">
              <canvas id="chartVentas" height="80"></canvas>
            </div>
          </div>
        </div>

        <!-- Gráfica de pastel: Local vs Foráneo -->
        <div class="col-md-4">
          <div class="card card-success">
            <div class="card-header">
              <h3 class="card-title">📍 Ventas por Tipo</h3>
            </div>
            <div class="card-body">
              <canvas id="chartTipoEnvio" height="120"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- GRÁFICAS POR PERÍODO -->
      <div class="row mt-4">
        <div class="col-md-8">
          <div class="card card-warning">
            <div class="card-header">
              <h3 class="card-title">📊 Ventas por Período Seleccionado</h3>
            </div>
            <div class="card-body">
              <canvas id="chartVentasPeriodo" height="80"></canvas>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card card-danger">
            <div class="card-header">
              <h3 class="card-title">🚚 Tipo de Envío por Período</h3>
            </div>
            <div class="card-body">
              <canvas id="chartTipoEnvioPeriodo" height="120"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- TABLA DE VENTAS CON FILTRO -->
      <div class="row mt-4">
        <div class="col-md-12">
          <div class="card card-info">
            <div class="card-header">
              <h3 class="card-title">📋 Registro de Ventas</h3>
            </div>
            <div class="card-body">
              

              <div class="table-responsive">
                <table id="tablaVentas" class="table table-bordered table-striped table-sm">
                  <thead class="thead-light">
                    <tr>
                      <th>#</th>
                      <th>Fecha</th>
                      <th>Cliente</th>
                      <th>Vendedor</th>
                      <th>Tipo Envío</th>
                      <th class="text-center">Items</th>
                      <th class="text-right">Total</th>
                      <th>Estado Logístico</th>
                      <th>Acción</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if(empty($ventas_tabla)): ?>
                    <tr>
                      <td colspan="9" class="text-center text-muted">
                        No hay ventas en el período seleccionado
                      </td>
                    </tr>
                    <?php else: ?>
                      <?php $num = 1; ?>
                      <?php foreach($ventas_tabla as $venta): ?>
                      <tr>
                        <td><?= $num++ ?></td>
                        <td><?= date('d/m/Y', strtotime($venta['fecha'])) ?></td>
                        <td><?= htmlspecialchars($venta['cliente'] ?? 'S/N') ?></td>
                        <td><?= htmlspecialchars($venta['vendedor'] ?? 'S/N') ?></td>
                        <td>
                          <?php if($venta['envio'] === 'local'): ?>
                            <span class="badge badge-success">Local</span>
                          <?php else: ?>
                            <span class="badge badge-warning">Foráneo</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-center">
                          <span class="badge badge-info"><?= $venta['cantidad_items'] ?></span>
                        </td>
                        <td class="text-right font-weight-bold">
                          $<?= number_format($venta['total'], 2) ?>
                        </td>
                        <td>
                          <?php 
                          $estados = [
                              'SIN ENVIO' => 'secondary',
                              'PENDIENTE GUIA' => 'warning',
                              'GUIA REGISTRADA' => 'info',
                              'ENVIADA' => 'success'
                          ];
                          $estado = $venta['estado_logistico'] ?? 'SIN ENVIO';
                          $color = $estados[$estado] ?? 'secondary';
                          ?>
                          <span class="badge badge-<?= $color ?>"><?= $estado ?></span>
                        </td>
                        <td>
                          <a href="../ventas/edit.php?id=<?= $venta['id_venta'] ?>" class="btn btn-sm btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                          </a>
                        </td>
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

      <!-- TABLA DE STOCK -->
      <div class="row mt-4">
        <div class="col-md-12">
          <div class="card card-warning">
            <div class="card-header">
              <h3 class="card-title">📦 Estado de Stock</h3>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table id="tablaStock" class="table table-bordered table-striped table-sm">
                  <thead class="thead-light">
                    <tr>
                      <th>#</th>
                      <th>Código</th>
                      <th>Producto</th>
                      <th>Categoría</th>
                      <th class="text-center">Stock Bodega</th>
                      <th class="text-center">Pendiente Entregar</th>
                      <th class="text-center">Disponible</th>
                      <th>Precio Venta</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $num = 1; ?>
                    <?php foreach($productos_stock as $prod): ?>
                    <tr>
                      <td><?= $num++ ?></td>
                      <td><strong><?= htmlspecialchars($prod['codigo']) ?></strong></td>
                      <td><?= htmlspecialchars($prod['nombre']) ?></td>
                      <td><?= htmlspecialchars($prod['nombre_categoria']) ?></td>
                      <td class="text-center">
                        <span class="badge badge-info"><?= $prod['stock_bodega'] ?></span>
                      </td>
                      <td class="text-center">
                        <span class="badge badge-warning"><?= $prod['stock_pendiente'] ?></span>
                      </td>
                      <td class="text-center">
                        <?php if($prod['stock_disponible'] <= 0): ?>
                          <span class="badge badge-danger">0</span>
                        <?php elseif($prod['stock_disponible'] <= 5): ?>
                          <span class="badge badge-warning"><?= $prod['stock_disponible'] ?></span>
                        <?php else: ?>
                          <span class="badge badge-success"><?= $prod['stock_disponible'] ?></span>
                        <?php endif; ?>
                      </td>
                      <td>$<?= number_format($prod['precio_venta'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- CHART.JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
// Gráfica de línea: Ventas últimos 7 días
const ctxVentas = document.getElementById('chartVentas').getContext('2d');
const chartVentas = new Chart(ctxVentas, {
    type: 'line',
    data: {
        labels: <?= $fechas_json ?>,
        datasets: [{
            label: 'Ventas Diarias ($)',
            data: <?= $montos_json ?>,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#007bff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toFixed(2);
                    }
                }
            }
        }
    }
});

// Gráfica de línea: Ventas por día en el período seleccionado
const ctxVentasPeriodo = document.getElementById('chartVentasPeriodo').getContext('2d');
const chartVentasPeriodo = new Chart(ctxVentasPeriodo, {
    type: 'bar',
    data: {
        labels: <?= $fechas_periodo ?>,
        datasets: [{
            label: 'Ventas ($)',
            data: <?= $montos_periodo ?>,
            backgroundColor: 'rgba(0, 123, 255, 0.7)',
            borderColor: '#007bff',
            borderWidth: 1
        },
        {
            label: 'Cantidad',
            data: <?= $cantidades_periodo ?>,
            backgroundColor: 'rgba(40, 167, 69, 0.7)',
            borderColor: '#28a745',
            borderWidth: 1,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                position: 'left',
                ticks: {
                    callback: function(value) {
                        return '$' + value.toFixed(0);
                    }
                }
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false
                },
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Gráfica de pastel: Local vs Foráneo
const ctxTipo = document.getElementById('chartTipoEnvio').getContext('2d');
const chartTipo = new Chart(ctxTipo, {
    type: 'doughnut',
    data: {
        labels: <?= $tipos_json ?>,
        datasets: [{
            label: 'Cantidad de Ventas',
            data: <?= $cantidades_json ?>,
            backgroundColor: [
                '#28a745',
                '#ffc107'
            ],
            borderColor: [
                '#1e7e34',
                '#e0a800'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Gráfica de pastel: Tipo envío en período
const ctxTipoPeriodo = document.getElementById('chartTipoEnvioPeriodo').getContext('2d');
const chartTipoPeriodo = new Chart(ctxTipoPeriodo, {
    type: 'doughnut',
    data: {
        labels: <?= $tipos_periodo_json ?>,
        datasets: [{
            label: 'Cantidad de Ventas',
            data: <?= $cantidades_periodo_json ?>,
            backgroundColor: [
                '#28a745',
                '#ffc107'
            ],
            borderColor: [
                '#1e7e34',
                '#e0a800'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// DataTable para la tabla de stock
$(function () {
    $("#tablaStock").DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "buttons": [
            {
                extend: 'collection',
                text: 'Exportar',
                buttons: [
                    {
                        text: 'Excel',
                        extend: 'excel'
                    },
                    {
                        text: 'PDF',
                        extend: 'pdf'
                    },
                    {
                        text: 'Imprimir',
                        extend: 'print'
                    }
                ]
            }
        ]
    }).buttons().container().appendTo('#tablaStock_wrapper .col-md-6:eq(0)');
});
</script>

<?php include('../layout/parte2.php'); ?>
