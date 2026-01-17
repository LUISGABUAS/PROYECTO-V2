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
      <div class="row">
        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fa fa-shopping-cart"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Mis Ventas</span>
              <span class="info-box-number"><?= $ventas_generales['total_ventas'] ?? 0 ?></span>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fa fa-dollar-sign"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Mis Comisiones</span>
              <span class="info-box-number">$<?= number_format($ventas_generales['total_ventas'] * 50) ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <!-- FILTRO FECHAS -->
      <form method="get" class="row mb-3">
        <div class="col-md-3">
          <input type="date" name="desde" class="form-control" value="<?= $desde ?>">
        </div>
        <div class="col-md-3">
          <input type="date" name="hasta" class="form-control" value="<?= $hasta ?>">
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary">Filtrar</button>
        </div>
      </form>

      <?php if (in_array(24, $_SESSION['permisos'])): ?>

      <!-- TABLA VENTAS -->
      <div class="card card-outline card-primary">
        <div class="card-header">
          <h3 class="card-title">Reporte de Ventas</h3>
        </div>

        <div class="card-body">
          <table id="ventas" class="table table-bordered table-striped table-sm">
            <thead>
              <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Cliente</th>

                <?php if (in_array(24, $_SESSION['permisos'])): ?>
                  <th>Vendedor</th>
                <?php endif; ?>

                <th>Total</th>

                <?php if (
                  in_array(24, $_SESSION['permisos']) &&
                  (in_array(22, $_SESSION['permisos']) || in_array(28, $_SESSION['permisos']))
                ): ?>
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

                  <?php if (in_array(24, $_SESSION['permisos'])): ?>
                    <td><?= $v['vendedor'] ?></td>
                  <?php endif; ?>

                  <td>$<?= number_format($v['total'], 2) ?></td>

                  <?php if (
                    in_array(24, $_SESSION['permisos']) &&
                    (in_array(22, $_SESSION['permisos']) || in_array(28, $_SESSION['permisos']))
                  ): ?>
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

      <!-- RESUMEN VENTAS USUARIO -->

      <?php if (in_array(25, $_SESSION['permisos'])): ?>
<div class="row mb-3">
  <div class="col-md-12">
    <div class="small-box bg-success">
      <div class="inner">
        <h3>$<?= number_format($total_vendido, 2) ?></h3>
        <p>Total vendido</p>
      </div>
      <div class="icon">
        <i class="fas fa-dollar-sign"></i>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>




      <?php if (in_array(25, $_SESSION['permisos'])): ?>
<div class="card card-outline card-success mt-4">
  <div class="card-header">
    <h3 class="card-title">👤 Mi Reporte de Ventas</h3>
  </div>

  <!--TABLA MIS VENTAS-->
  <div class="card-body">
    <table id="mis_ventas" class="table table-bordered table-striped table-sm">
      <thead>
        <tr>
          <th>#</th>
          <th>Fecha</th>
          <th>Cliente</th>
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
<?php endif; ?>


      <!-- GRAFICA VENTAS USUARIO -->

<?php if (in_array(25, $_SESSION['permisos'])): ?>
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
            Swal.fire('Eliminada', r.message, 'success');
            $('button[data-id="'+id_venta+'"]').closest('tr').fadeOut();
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
<?php if (in_array(25, $_SESSION['permisos'])): ?>
const labels = <?= json_encode(array_column($ventas_grafica, 'dia')) ?>;
const dataVentas = <?= json_encode(array_column($ventas_grafica, 'total')) ?>;

new Chart(document.getElementById('graficaVentas'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Ventas',
            data: dataVentas,
            fill: false,
            tension: 0.3
        }]
    }
});
<?php endif; ?>
</script>
