<?php

include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

include('../app/controllers/almacen/list_almacen.php');
include('../app/controllers/categorias/list_categorias.php');
include('../app/controllers/provedores/list_provedores.php');

?>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Create Product</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo $URL;?>">Home</a></li>
              <li class="breadcrumb-item active">Starter Page</li>
              <li class="breadcrumb-item active">Create Product</li>
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
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Create New Product</h3>

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
                        <form action="../app/controllers/almacen/create.php" method="post" enctype="multipart/form-data">

                        <div class="row">
                          <div class="col-md-9">
                             <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Codigo:</label>
                                        <?php 
                                        function ceros($numero){
                                            $len=0;
                                            $cantidad_ceros = 5;
                                            $aux = $numero;
                                            $pos = strlen($numero);
                                            $len = $cantidad_ceros - $pos;
                                            for($i = 0;$i<$len;$i++){
                                                $aux = "0".$aux;
                                            }
                                            return $aux;
                                        }

                                          $contador_id_producto = 1;
                                          foreach ($datos_productos as $dato) { 
                                            $contador_id_producto = $contador_id_producto + 1;
                        
                          
                        
                                          }
                                        ?>
                                        <input type="text" class="form-control" value="<?php echo "P-".ceros($contador_id_producto) ?>" disabled>
                                        <input type="text" name="codigo" value="<?php echo "P-".ceros($contador_id_producto) ?>" hidden >
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Etiqueta:</label>
                                        <select name="id_proovedor" id="" class="form-control" required>
                                          <?php foreach ($proovedores_datos as $datos_proveedores) {?>
                                    <option value="<?php echo $datos_proveedores['id_proovedor'] ; ?>"><?php echo $datos_proveedores['nombre_proveedor'] ; ?></option>
                                    <?php
                                  }?>
                                          
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Nombre Producto:</label>
                                        <input type="text" name="nombre" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                              <div class="col-md-4">
                                <div class="form-group">
                                  <label for="">Usuario</label>
                                  <input type="text" class="form-control" value="<?php echo $sesion_nombres?>" disabled>
                                  <input type="text" name="id_usuario" value="<?php echo $id_usuario_sesion?>" hidden>
                                </div>
                              </div>
                              <div class="col-md-8">
                                <div class="form-group">
                                        <label for="">Descripcion Producto:</label>
                                        <textarea name="descripcion" id="" cols="30" rows="2"  class="form-control"></textarea>
                                    </div>
                              </div>
                            </div>

                            <div class="row">
                                
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="">Stock Minimo:</label>
                                        <input type="number" name="stock_minimo" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="">Stock Maximo</label>
                                        <input type="number" name="stock_maximo" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="">Precio Compra</label>
                                        <input type="number" name="precio_compra" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="">Precio Venta:</label>
                                        <input type="number" name="precio_venta" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="">Categoria:</label>
                                        <select name="id_categoria" id="" class="form-control" required>
                                          <?php foreach ($datos_categorias as $datos_categoria) {?>
                                    <option value="<?php echo $datos_categoria['id_categoria'] ; ?>"><?php echo $datos_categoria['nombre_categoria'] ; ?></option>
                                    <?php
                                  }?>
                                          
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="">Fecha Ingreso:</label>
                                        <input type="date" name="fecha_ingreso" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                            </div>
                          </div>

                            <div class="col-md-3">
                              <label for="">Imagen</label>
                              <input type="file" name="image" class="form-control" id="file" required>
                              <br>
                              <output id="list"></output>
                              <script>
                                function archivo(evt) {
                                  var files = evt.target.files; // FileList Object
                                  // Obtenemos la imagen del campo "file".
                                  for (var i = 0, f; f = files [i]; i++) {
                                    //solo admitimos imagenes.
                                    if (!f.type.match ('image.*')){
                                      continue;
                                    }
                                    
                                    var reader = new FileReader();
                                    reader.onload = (function(theFile) {
                                      return function(e){
                                          //Insertamos imagen
                                          document.getElementById("list").innerHTML = ['<img class="thumb thumbnail" src="',e.target.result,'" width="100%" title"', escape(theFile.name), '"/>'].join('');
                                          };
                                    }) (f);
                                    reader.readAsDataURL(f);
                                  }
                                } 
                                document.getElementById('file').addEventListener('change', archivo, false);
                              </script>
                            </div>
                        </div>

                      

                            <hr>
                            <div class="form-group">
                                <a href="index.php"class="btn btn-danger" >Cancel</a>
                                <button type="submit" class="btn btn-primary">Create</button>
                            </div>
                        </form>
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