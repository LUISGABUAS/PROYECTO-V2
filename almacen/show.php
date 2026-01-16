<?php

include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

include('../app/controllers/almacen/cargar_producto.php');


?>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Product Data : <?php echo $nombre;?></h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo $URL;?>">Home</a></li>
              <li class="breadcrumb-item active">Starter Page</li>
              <li class="breadcrumb-item active">Product Data</li>
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
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">Product Data</h3>

                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                  </button>
                </div>
                <!-- /.card-tools -->
              </div>
              <!-- /.card-header -->
              <div class="card-body" style="display: block;">
                
                <div class="row">
                    <div class="col-md-12">
                       

                        <div class="row">
                          <div class="col-md-9">
                             <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Codigo:</label>
                                        <input type="text" class="form-control" value="<?php echo $codigo ?>" disabled>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Categoria:</label>
                                        <input type="text" class="form-control" value="<?php echo $categoria ?>" disabled>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Nombre Producto:</label>
                                        <input type="text" name="nombre" class="form-control" disabled value="<?php echo $nombre ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                              <div class="col-md-4">
                                <div class="form-group">
                                  <label for="">Usuario</label>
                                  <input type="text" class="form-control" value="<?php echo $nombre_usuario?>" disabled>
                                </div>
                              </div>
                              <div class="col-md-8">
                                <div class="form-group">
                                        <label for="">Descripcion Producto:</label>
                                        <textarea name="descripcion" id="" cols="30" rows="2"  class="form-control" disabled><?php echo $descripcion;?></textarea>
                                    </div>
                              </div>
                            </div>

                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="">Stock Minimo:</label>
                                        <input type="number" name="stock_minimo" class="form-control" disabled value="<?php echo $stock_minimo; ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="">Stock Maximo</label>
                                        <input type="number" name="stock_maximo" class="form-control" disabled value="<?php echo $stock_maximo; ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="">Precio Compra</label>
                                        <input type="number" name="precio_compra" class="form-control" disabled value="<?php echo $precio_compra; ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="">Precio Venta:</label>
                                        <input type="number" name="precio_venta" class="form-control" disabled value="<?php echo $precio_venta; ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="">Fecha Ingreso:</label>
                                        <input type="date" name="fecha_ingreso" class="form-control" disabled value="<?php echo $fecha_ingreso; ?>">
                                    </div>
                                </div>
                            </div>
                          </div>

                            <div class="col-md-3">
                              <label for="">Imagen</label>
                              <center>
                                <img src="<?php echo $URL."/almacen/img_productos/".$imagen;?>" width="100%" alt="">
                              </center>
                            </div>
                        </div>

                      

                            <hr>
                            <div class="form-group">
                                <a href="index.php"class="btn btn-secondary" >Cancel</a>
                            </div>
                        
                    </div>
                </div>

              </div>
              <!-- /.card-body -->
            </div>
        </div>
      </div>


      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
<?php include('../layout/mensajes.php') ?>
<?php include('../layout/parte2.php'); ?>