<?php

include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

/* =========================
   CONTROLLERS
========================= */
include('../app/controllers/almacen/list_almacen.php');
include('../app/controllers/ventas/reporte_ventas.php');



if (isset($_SESSION['mensaje'])) {
    $respuesta = $_SESSION['mensaje'];?>
    <script>
    Swal.fire({
            position: 'top-end',
            icon: 'success',
            title: <?php echo json_encode($respuesta); ?>,
            showConfirmButton: false,
            timer: 2000
   })
   </script>
    
<?php
    unset($_SESSION['mensaje']);
     }
     
     if (in_array(8, $_SESSION['permisos'])):
?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Stock</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo $URL;?>">Home</a></li>
              <li class="breadcrumb-item active">Starter Page</li>
              <li class="breadcrumb-item active">Stock</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
      <div class="container-fluid">

        <?php
        $alertas_stock = array_filter($datos_productos, fn($p) =>
            isset($p['stock_minimo']) && $p['stock_disponible'] <= $p['stock_minimo']
        );
        if (!empty($alertas_stock)):
        ?>
        <div class="row mb-3">
          <div class="col-md-12">
            <div class="alert alert-warning alert-dismissible fade show">
              <button type="button" class="close" data-dismiss="alert">&times;</button>
              <h5><i class="fa fa-exclamation-triangle"></i> <strong>Alerta de Stock Bajo</strong></h5>
              <p>Los siguientes productos están en o por debajo del stock mínimo:</p>
              <ul class="mb-0">
                <?php foreach($alertas_stock as $alerta): ?>
                  <li>
                    <strong><?= htmlspecialchars($alerta['nombre']) ?></strong>
                    (<?= htmlspecialchars($alerta['codigo']) ?>) —
                    Disponible: <strong class="text-danger"><?= $alerta['stock_disponible'] ?></strong>,
                    Mínimo: <?= $alerta['stock_minimo'] ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
        <?php endif; ?>

      <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
              <div class="card-header">
                <h3 class="card-title">Productos</h3>

                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                  </button>
                </div>
                <!-- /.card-tools -->
              </div>
              <!-- /.card-header -->
              <div class="card-body" style="display: block;">
                
                <div class="table table-responsive">


                  <table id="example1" class="table table-bordered table-striped table-sm">
                  <thead>
                  <tr>
                        <th><center>ID</center></th>
                        <th><center>Codigo</center></th>
                        <th><center>Categoria</center></th>
                        <th><center>Etiqueta</center></th>
                        <th><center>Imagen</center></th>
                        <th><center>Nombre</center></th>
                        <th><center>Descripcion</center></th>
                        <th><center>En Bodega</center></th> 
                        <th><center>Pendiente</center></th> 
                        <th><center>Disponible</center></th>
                        <?php if(in_array(34, $_SESSION['permisos'])):?>
                        <th><center>Precio Compra</center></th>
                        <?php endif;  ?>
                        <th><center>Precio Venta</center></th>
                        <th><center>Fecha</center></th>
                        <th><center>Quien?</center></th>
                        <th><center>Acciones</center></th>
                    </tr>
                  </thead>
                  <tbody>
                        
                        <?php
                        $contador = 0;
                        $dinero = "$";
                        foreach ($datos_productos as $dato) {
                          $id_producto = $dato['id_producto'];
                          ?>

                        <tr>
                          <td><?php echo $contador = $contador + 1?></td>
                          <td><?php echo htmlspecialchars($dato['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?php echo htmlspecialchars($dato['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?php echo htmlspecialchars($dato['proveedor'], ENT_QUOTES, 'UTF-8') ?></td>
                          <td>
                            <img src="<?php echo $URL."/almacen/img_productos/".htmlspecialchars($dato['imagen'], ENT_QUOTES, 'UTF-8') ?>" width="75px" alt="">
                          </td>
                          <td><?php echo htmlspecialchars($dato['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?php echo htmlspecialchars($dato['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?= (int)$dato['stock_bodega'] ?></td>
                          <td><?= (int)$dato['stock_pendiente'] ?></td>
                          <td>
                            <span class="<?= $dato['stock_disponible'] <= 0 ? 'text-danger' : 'text-success' ?>">
                              <?= (int)$dato['stock_disponible'] ?>
                            </span>
                          </td>


                          <?php if(in_array(34, $_SESSION['permisos'])):?>
                          <td>
                              <?php echo $dinero.htmlspecialchars($dato['precio_compra'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <?php endif; ?>
                          <td><?php echo $dinero.htmlspecialchars($dato['precio_venta'], ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?php echo htmlspecialchars($dato['fecha_ingreso'], ENT_QUOTES, 'UTF-8') ?></td>
                          <td><?php echo htmlspecialchars($dato['nombre_usuario'], ENT_QUOTES, 'UTF-8') ?></td>
                          <td>
                              <center>
                                <div class="btn-group">
                                <a href="show.php?id=<?php echo $id_producto;?>" type="button" class="btn btn-info btn-sm"><i class="fa fa-eye"></i> Show</a>
                                <?php if(in_array(10, $_SESSION['permisos'])):?>
                                <a href="update.php?id=<?php echo $id_producto;?>" type="button" class="btn btn-success btn-sm"><i class="fa fa-pencil-alt"></i> Edit</a>
                                <?php endif; ?>
                                <?php if(in_array(13, $_SESSION['permisos'])):?>
                                <button type="button" class="btn btn-danger btn-sm" onclick="confirmarEliminar(<?= $id_producto ?>, '<?= addslashes($dato['nombre']) ?>')"><i class="fa fa-trash"></i> Eliminate</button>
                                <?php endif; ?>
                                <?php if(in_array(11, $_SESSION['permisos'])):?>
                                <a href="../stock/index.php?id=<?php echo $id_producto; ?>" type="button" class="btn btn-primary btn-sm"><i class="fa fa-box"></i> Stock</a>
                                <?php endif; ?>
                              </div>
                              </center>
                          </td>
                        </tr>
                          
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
                </div>


              </div>
              <!-- /.card-body -->
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


      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

<?php include('../layout/mensajes.php')?>

<script>
function confirmarEliminar(id, nombre) {
  Swal.fire({
    icon: 'warning',
    title: '¿Eliminar producto?',
    html: 'Estás a punto de eliminar <strong>' + nombre + '</strong>.<br>Esta acción no se puede deshacer.',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then(result => {
    if (result.isConfirmed) {
      window.location = 'delete.php?id=' + id;
    }
  });
}
</script>

<?php include('../layout/parte2.php'); ?>

<!-- Page specific script -->
<script>
  $(function () {
    $("#example1").DataTable({
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
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
  });
</script>
<?php 
  else:

    include('../layout/parte2.php'); 
    echo '<script>
      Swal.fire({
        icon: "error",
        title: "Access Denied",
        text: "You do not have permission to access this page.",
        showConfirmButton: false,
        timer: 3000
      }).then(() => {
        window.location = "'.$URL.'";
      });
    </script>';
  endif;