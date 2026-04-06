<?php

include('app/config.php');
include('layout/sesion.php');
include('layout/parte1.php');

include('app/controllers/usuarios/listado_de_usuarios.php');
include('app/controllers/almacen/list_almacen.php');
include('app/controllers/provedores/list_provedores.php');
include('app/controllers/ventas/reporte_ventas.php');
?>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Welcome to the system - <?php echo $rol_sesion;?></h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo $URL;?>">Home</a></li>
              <li class="breadcrumb-item active">Starter Page</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
      <div class="container-fluid">
        
      Content goes here 
      <br><br>
     <div class="row">
         
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-warning">
              <div class="inner">
                <?php 
                $contador_de_usuarios = 0;
                foreach($datos_usuarios as $usuario){
                  // Aquí podrías procesar cada usuario si es necesario
                  $contador_de_usuarios = $contador_de_usuarios + 1;
                }
                ?>
                <h3><?php echo $contador_de_usuarios;?></h3>

                <p>User Registrations</p>
              </div>
              <a href="<?php echo $URL?>/usuarios/create.php">
                <div class="icon">
                <i class="fa fas fa-user-plus"></i>
              </div>
              </a>
              <a href="<?php echo $URL?>/usuarios/" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>

          <!-- PRODUCTOS -->

                <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-info">
              <div class="inner">
                <?php 
                $contador_de_productos = 0;
                foreach($datos_productos as $producto){
                  // Aquí podrías procesar cada usuario si es necesario
                  $contador_de_productos = $contador_de_productos + 1;
                }
                ?>
                <h3><?php echo $contador_de_productos;?></h3>

                <p>Products Registered</p>
              </div>
              <a href="<?php echo $URL?>/almacen/create.php">
                <div class="icon">
                <i class="nav-icon fas fa-list"></i>
              </div>
              </a>
              <a href="<?php echo $URL?>/almacen/" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>

          <!-- PROVEEDORES -->

                <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-dark">
              <div class="inner">
                <?php 
                $contador_de_usuarios = 0;
                foreach($proovedores_datos as $proovedor){
                  // Aquí podrías procesar cada usuario si es necesario
                  $contador_de_usuarios = $contador_de_usuarios + 1;
                }
                ?>
                <h3><?php echo $contador_de_usuarios;?></h3>

                <p>Prooveders Registered</p>
              </div>
              
                <div class="icon">
                <i class="nav-icon fas fa-building"></i>
              </div>
              </a>
              <a href="<?php echo $URL?>/provedores/" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>

         <?php if (in_array(24, $permisos)) { ?>
          <!-- Ventas Totales -->

                <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-success">
              <div class="inner">
                <?php 
                $contador_de_ventas = 0;
                foreach($ventas_grafica_total as $fila){
                  // Aquí podrías procesar cada usuario si es necesario
                  $contador_de_ventas += $fila['total'];
                }
                ?>
                <h3><?php echo $contador_de_ventas;?></h3>

                <p>Ventas Totales</p>
              </div>
              
                <div class="icon">
                <i class="nav-icon fas fa-dollar-sign"></i>
              </div>
              </a>
              <a href="<?php echo $URL?>/ventas/" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <?php } ?>

                <!-- RESUMEN VENTAS USUARIO -->

                  <?php if (in_array(25, $_SESSION['permisos'])): ?>
            <div class="col-lg-12 col-6 mb-4">
                <div class="small-box bg-success">
                  <div class="inner">
                    <center>
                    <h3>$<?= number_format($total_vendido, 2) ?></h3> 
                    <p>Total vendido</p> </center>
                  </div>
                  <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                  </div>
                </div>
              </div>
            </div>
            <?php endif; ?>

        <div class="row">
                

                <!-- GRAFICA VENTAS USUARIO -->
                

              <?php if (in_array(25, $_SESSION['permisos'])): ?>
              <div class="card card-outline card-info mb-4 col-lg-6">
                <div class="card-header">
                  <h3 class="card-title">📊 Mis ventas del mes</h3>
                </div>
                <div class="card-body">
                  <canvas id="graficaVentas" height="120"></canvas>
                </div>
              </div>
              <?php endif; ?>

               <!-- GRAFICA VENTAS TOTAL -->
               


              <?php if (in_array(24, $_SESSION['permisos'])): ?>
              <div class="card card-outline card-info mb-4 col-lg-6">
                <div class="card-header">
                  <h3 class="card-title">📊 Ventas totales del mes</h3>
                </div>
                <div class="card-body">
                  <canvas id="graficaVentasTotal" height="120"></canvas>
                </div>
              </div>
              <?php endif; ?>



          <!-- ./col -->
        </div>


        


      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
<?php include('layout/parte2.php'); ?>

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

<script>
<?php if (in_array(24, $_SESSION['permisos'])): ?>
const labelsVentasTotal = <?= json_encode(array_column($ventas_grafica_total, 'dia')) ?>;
const dataVentasTotal = <?= json_encode(array_column($ventas_grafica_total, 'total')) ?>;

new Chart(document.getElementById('graficaVentasTotal'), {
    type: 'line',
    data: {
        labels: labelsVentasTotal,
        datasets: [{
            label: 'Ventas Totales',
            data: dataVentasTotal,
            fill: false,
            tension: 0.3
        }]
    }
});
<?php endif; ?>
</script>