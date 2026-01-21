<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

/* =========================
   CONTROLLER
========================= */
include('../app/controllers/ventas/reporte_ventas.php');

/* =========================
   MENSAJES
========================= */
if (isset($_SESSION['mensaje'])) {
  $respuesta = $_SESSION['mensaje']; ?>
  <script>
    Swal.fire({
      position: 'top-end',
      icon: 'success',
      title: '<?= $respuesta ?>',
      showConfirmButton: false,
      timer: 2000
    })
  </script>
<?php unset($_SESSION['mensaje']); }

/* =========================
   PERMISO DE ACCESO
========================= */
if (!in_array(20, $_SESSION['permisos'])) {
  include('../layout/parte2.php');
  echo "<script>Swal.fire('Acceso denegado','','error')</script>";
  exit;
}
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">

      <!-- TARJETAS DE RESUMEN -->
      <?php if (in_array(24, $_SESSION['permisos']) || in_array(25, $_SESSION['permisos'])): ?>
      <div class="row">
        
        <!-- ADMIN: Total Ventas Sistema -->
        <?php if (in_array(24, $_SESSION['permisos'])): ?>
        <div class="col-md-3">
          <div class="info-box bg-gradient-info">
            <span class="info-box-icon"><i class="fa fa-chart-line"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Total Ventas Sistema</span>
              <span class="info-box-number"><?= $ventas_generales['total_ventas'] ?? 0 ?></span>
              <small>Del <?= date('d/m/Y', strtotime($desde)) ?> al <?= date('d/m/Y', strtotime($hasta)) ?></small>
            </div>
          </div>
        </div>
        
        <div class="col-md-3">
          <div class="info-box bg-gradient-success">
            <span class="info-box-icon"><i class="fa fa-cash-register"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Monto Total Sistema</span>
              <span class="info-box-number">$<?= number_format($ventas_generales['monto_total'] ?? 0, 2) ?></span>
              <small>Ingresos del período</small>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- VENDEDOR: Mis Ventas -->
        <?php if (in_array(25, $_SESSION['permisos'])): ?>
        <div class="col-md-3">
          <div class="info-box bg-gradient-success">
            <span class="info-box-icon"><i class="fa fa-shopping-bag"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Mis Ventas</span>
              <span class="info-box-number"><?= $mis_ventas_cantidad ?? 0 ?></span>
              <small>Del <?= date('d/m/Y', strtotime($desde)) ?> al <?= date('d/m/Y', strtotime($hasta)) ?></small>
            </div>
          </div>
        </div>

        <!-- Pacas Vendidas -->
        <div class="col-md-3">
          <div class="info-box bg-gradient-info">
            <span class="info-box-icon"><i class="fa fa-boxes"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Pacas Vendidas</span>
              <span class="info-box-number"><?= $total_pacas_vendidas ?? 0 ?></span>
              <small>Total de pacas del período</small>
            </div>
          </div>
        </div>

        <!-- Monto Total Vendido -->
        <div class="col-md-3">
          <div class="info-box bg-gradient-primary">
            <span class="info-box-icon"><i class="fa fa-dollar-sign"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Total Vendido</span>
              <span class="info-box-number">$<?= number_format($total_vendido ?? 0, 2) ?></span>
              <small>Monto del período</small>
            </div>
          </div>
        </div>

        <!-- Mis Comisiones -->
        <div class="col-md-3">
          <div class="info-box bg-gradient-warning">
            <span class="info-box-icon"><i class="fa fa-hand-holding-usd"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Mis Comisiones</span>
              <span class="info-box-number">$<?= number_format($mis_comisiones ?? 0, 2) ?></span>
              <small>$50 x <?= $total_pacas_vendidas ?? 0 ?> pacas</small>
            </div>
          </div>
        </div>
        <?php endif; ?>

      </div>
      <?php endif; ?>

    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <!-- FILTRO FECHAS -->
      <div class="card card-outline card-secondary mb-3">
        <div class="card-header">
          <h3 class="card-title"><i class="fa fa-calendar-alt"></i> Filtrar por Fecha</h3>
        </div>
        <div class="card-body">
          <form method="get" class="row">
            <div class="col-md-3">
              <label>Desde:</label>
              <input type="date" name="desde" class="form-control" value="<?= $desde ?>" required>
            </div>
            <div class="col-md-3">
              <label>Hasta:</label>
              <input type="date" name="hasta" class="form-control" value="<?= $hasta ?>" required>
            </div>
            <div class="col-md-2">
              <label>&nbsp;</label>
              <button type="submit" class="btn btn-primary btn-block">
                <i class="fa fa-filter"></i> Filtrar
              </button>
            </div>
            <div class="col-md-2">
              <label>&nbsp;</label>
              <a href="?" class="btn btn-secondary btn-block">
                <i class="fa fa-redo"></i> Resetear
              </a>
            </div>
          </form>
        </div>
      </div>

      <!-- TABLA VENTAS (ADMIN) -->
      <?php if (in_array(24, $_SESSION['permisos'])): ?>
      <div class="card card-outline card-primary">
        <div class="card-header">
          <h3 class="card-title"><i class="fa fa-list"></i> Reporte General de Ventas</h3>
        </div>

        <div class="card-body">
          <table id="ventas" class="table table-bordered table-striped table-sm">
            <thead>
              <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th>Pacas</th>
                <th>Total</th>
                <?php if (in_array(22, $_SESSION['permisos']) || in_array(28, $_SESSION['permisos'])): ?>
                  <th>Acciones</th>
                <?php endif; ?>
              </tr>
            </thead>

            <tbody>
              <?php $c = 1; foreach ($ventas as $v): ?>
                <tr>
                  <td><?= $c++ ?></td>
                  <td><?= $v['fecha'] ?></td>
                  <td><?= $v['cliente'] ?></td>
                  <td><?= $v['vendedor'] ?></td>
                  <td class="text-center">
                    <span class="badge badge-info"><?= $v['total_pacas'] ?></span>
                  </td>
                  <td>$<?= number_format($v['total'], 2) ?></td>
                  
                  <?php if (in_array(22, $_SESSION['permisos']) || in_array(28, $_SESSION['permisos'])): ?>
                    <td>
                      <center>
                        <?php if (in_array(22, $_SESSION['permisos'])): ?>
                          <a href="<?= $URL ?>/ventas/edit.php?id=<?= $v['id_venta'] ?>"
                             class="btn btn-warning btn-sm">
                            <i class="fa fa-edit"></i>
                          </a>
                        <?php endif; ?>

                        <?php if (in_array(28, $_SESSION['permisos'])): ?>
                          <button class="btn btn-danger btn-sm delete-venta"
                                  data-id="<?= $v['id_venta'] ?>">
                            <i class="fa fa-trash"></i>
                          </button>
                        <?php endif; ?>
                      </center>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

      <!-- TABLA MIS VENTAS (VENDEDOR) -->
      <?php if (in_array(25, $_SESSION['permisos'])): ?>
      <div class="card card-outline card-success">
        <div class="card-header">
          <h3 class="card-title"><i class="fa fa-user-tag"></i> Mi Reporte de Ventas</h3>
        </div>

        <div class="card-body">
          <table id="mis_ventas" class="table table-bordered table-striped table-sm">
            <thead>
              <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Pacas</th>
                <th>Total</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php $c = 1; foreach ($mis_ventas as $v): ?>
              <tr>
                <td><?= $c++ ?></td>
                <td><?= $v['fecha'] ?></td>
                <td><?= $v['cliente'] ?></td>
                <td class="text-center">
                  <span class="badge badge-info"><?= $v['total_pacas'] ?></span>
                </td>
                <td>$<?= number_format($v['total'],2) ?></td>
                <td>
                  <center>
                    <?php if (in_array(22, $_SESSION['permisos'])): ?>
                      <a href="<?= $URL ?>/ventas/edit.php?id=<?= $v['id_venta'] ?>"
                         class="btn btn-warning btn-sm">
                        <i class="fa fa-edit"></i>
                      </a>
                    <?php endif; ?>

                    <?php if (in_array(28, $_SESSION['permisos'])): ?>
                      <button class="btn btn-danger btn-sm delete-venta"
                              data-id="<?= $v['id_venta'] ?>">
                        <i class="fa fa-trash"></i>
                      </button>
                    <?php endif; ?>
                  </center>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- GRAFICA VENTAS USUARIO -->
      <div class="card card-outline card-info mb-4">
        <div class="card-header">
          <h3 class="card-title">📊 Mis ventas del mes</h3>
        </div>
        <div class="card-body">
          <canvas id="graficaVentas" height="120"></canvas>
        </div>
      </div>
      <?php endif; ?>

      <!-- TABLA DE STOCK -->
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

<?php
include('../layout/mensajes.php');
include('../layout/parte2.php');
?>

<!-- DATATABLES -->
<script>
  $(function () {
    $("#ventas").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons": [{ 
        extend: 'collection',
        text: 'Export',
        orientation: 'landscape',
        buttons: [{
          text: 'Copy',
          extend: 'copy',
          exportOptions: {
            columns: ':visible',
          modifier: {
            search: 'applied',
            order: 'applied',
            page: 'all'
          }
        } 
        },{
          text: 'Excel',
          extend: 'excel',
          exportOptions: {
            columns: ':visible',
          modifier: {
            search: 'applied',
            order: 'applied',
            page: 'all'
          }
        } 
        },{
          text: 'PDF',
          extend: 'pdf',
          exportOptions: {
            columns: ':visible',
          modifier: {
            search: 'applied',
            order: 'applied',
            page: 'all'
          }
        } 
        },{
          text: 'Print',
          extend: 'print',
          exportOptions: {
            columns: ':visible',
          modifier: {
            search: 'applied',
            order: 'applied',
            page: 'all'
          }
        } 
        }]
      },
      {
        extend: 'colvis',
        text: 'Columns',
        collectionLayout: 'fixed three-column'
      }
      ],
    }).buttons().container().appendTo('#ventas_wrapper .col-md-6:eq(0)');
  });
</script>

<script>
  $(function () {
    $("#mis_ventas").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons": [{ 
        extend: 'collection',
        text: 'Export',
        orientation: 'landscape',
        buttons: [{
          text: 'Copy',
          extend: 'copy',
          exportOptions: {
            columns: ':visible',
          modifier: {
            search: 'applied',
            order: 'applied',
            page: 'all'
          }
        } 
        },{
          text: 'Excel',
          extend: 'excel',
          exportOptions: {
            columns: ':visible',
          modifier: {
            search: 'applied',
            order: 'applied',
            page: 'all'
          }
        } 
        },{
          text: 'PDF',
          extend: 'pdf',
          exportOptions: {
            columns: ':visible',
          modifier: {
            search: 'applied',
            order: 'applied',
            page: 'all'
          }
        } 
        },{
          text: 'Print',
          extend: 'print',
          exportOptions: {
            columns: ':visible',
          modifier: {
            search: 'applied',
            order: 'applied',
            page: 'all'
          }
        } 
        }]
      },
      {
        extend: 'colvis',
        text: 'Columns',
        collectionLayout: 'fixed three-column'
      }
      ],
    }).buttons().container().appendTo('#mis_ventas_wrapper .col-md-6:eq(0)');
  });
</script>

<!-- ELIMINAR VENTA -->
<script>
$(document).on('click', '.delete-venta', function () {
  let id_venta = $(this).data('id');

  Swal.fire({
    title: '¿Eliminar venta?',
    text: 'El stock será devuelto a bodega',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: '<?= $URL ?>/app/controllers/ventas/delete_venta.php',
        type: 'POST',
        dataType: 'json',
        data: { id_venta: id_venta },
        success: function (r) {
          if (r.success) {
            Swal.fire('Eliminada', r.message, 'success').then(() => {
              location.reload();
            });
          } else {
            Swal.fire('Error', r.message, 'error');
          }
        },
        error: function () {
          Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        }
      });
    }
  });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (in_array(25, $_SESSION['permisos']) && isset($ventas_grafica)): ?>
const labels = <?= json_encode(array_column($ventas_grafica, 'dia')) ?>;
const dataVentas = <?= json_encode(array_column($ventas_grafica, 'total')) ?>;

new Chart(document.getElementById('graficaVentas'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Ventas ($)',
            data: dataVentas,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true
            }
        }
    }
});
<?php endif; ?>
</script>