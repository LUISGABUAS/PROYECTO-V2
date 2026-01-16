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
            title: '<?php echo $respuesta ?>',
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
                        <?php if(in_array(9, $_SESSION['permisos'])):?>
                        <th>
                          <center>Precio Compra</center></th>
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
                          <td><?php echo $dato['codigo'] ?></td>
                          <td><?php echo $dato['categoria'] ?></td>
                          <td><?= $dato['proveedor'] ?>?</td>
                          <td>
                            <img src="<?php echo $URL."/almacen/img_productos/".$dato['imagen'] ?>" width="75px" alt="">
                          </td>
                          <td><?php echo $dato['nombre'] ?></td>
                          <td><?php echo $dato['descripcion'] ?></td>
                          <td><?= $dato['stock_bodega'] ?></td>
                          <td><?= $dato['stock_pendiente'] ?></td>
                          <td>
                            <span class="<?= $dato['stock_disponible'] <= 0 ? 'text-danger' : 'text-success' ?>">
                              <?= $dato['stock_disponible'] ?>
                            </span>
                          </td>

                          
                          <?php if(in_array(9, $_SESSION['permisos'])):?>
                          <td>
                              <?php echo $dinero.$dato['precio_compra'] ?>
                        </td><?php endif; ?>
                          
                          <td><?php echo $dinero.$dato['precio_venta'] ?></td>
                          <td><?php echo $dato['fecha_ingreso'] ?></td>
                          <td><?php echo $dato['nombre_usuario'] ?></td>
                          <td>
                              <center>
                                <div class="btn-group">
                                <a href="show.php?id=<?php echo $id_producto;?>" type="button" class="btn btn-info btn-sm"><i class="fa fa-eye"></i> Show</a>
                                <?php if(in_array(10, $_SESSION['permisos'])):?>
                                <a href="update.php?id=<?php echo $id_producto;?>" type="button" class="btn btn-success btn-sm"><i class="fa fa-pencil-alt"></i> Edit</a>
                                <?php endif; ?>
                                <?php if(in_array(13, $_SESSION['permisos'])):?>
                                <a href="delete.php?id=<?php echo $id_producto;?>" type="button" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Eliminate</a>
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

            <!-- TABLA STOCK -->
      <div class="card card-outline card-warning mt-4">
        <div class="card-header">
          <h3 class="card-title">Stock Actual</h3>
        </div>
        <div class="card-body">

          <table id="stock" class="table table-bordered table-striped table-sm">
            <thead>
              <tr>
                <th>Producto</th>
                <th>Stock</th>
                <th>Mínimo</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($stock as $s): ?>
              <tr class="<?= ($s['stock_actual'] <= $s['stock_minimo']) ? 'table-danger' : '' ?>">
                <td><?= $s['nombre'] ?></td>
                <td><?= $s['stock_actual'] ?></td>
                <td><?= $s['stock_minimo'] ?></td>
              </tr>
              <?php endforeach ?>
            </tbody>
          </table>

        </div>
      </div>


      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

<?php include('../layout/mensajes.php')?>
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