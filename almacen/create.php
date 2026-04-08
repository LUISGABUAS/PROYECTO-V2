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
                        <?php include_once('../app/controllers/helpers/csrf.php'); ?>
                        <form action="../app/controllers/almacen/create.php" method="post" enctype="multipart/form-data">
                          <?= csrf_field() ?>

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
                                        <input type="text" class="form-control" value="<?php echo ceros($contador_id_producto) ?>" disabled>
                                        <input type="text" name="codigo" value="<?php echo ceros($contador_id_producto) ?>" hidden >
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
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Calidad:</label>
                                        <select name="calidad" class="form-control" required>
                                            <option value="">— Selecciona —</option>
                                            <option value="Calidad 1">Calidad 1</option>
                                            <option value="Calidad 2">Calidad 2</option>
                                            <option value="Calidad 3 - económico">Calidad 3 - económico</option>
                                            <option value="Calidad premium">Calidad premium</option>
                                            <option value="Calidad 1 y 2">Calidad 1 y 2</option>
                                            <option value="Calidad premium con 1">Calidad premium con 1</option>
                                            <option value="Calidad Mixtas">Calidad Mixtas</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Piezas: <small class="text-muted">(por paca)</small></label>
                                        <input type="number" name="piezas" class="form-control" min="1" placeholder="Ej: 12">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Categoria:</label>
                                        <select name="id_categoria" class="form-control" required>
                                            <?php foreach ($datos_categorias as $datos_categoria): ?>
                                                <option value="<?= $datos_categoria['id_categoria'] ?>">
                                                    <?= $datos_categoria['nombre_categoria'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Fecha Ingreso:</label>
                                        <input type="date" name="fecha_ingreso" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Stock Mínimo:</label>
                                        <input type="number" name="stock_minimo" class="form-control" min="0">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Stock Máximo:</label>
                                        <input type="number" name="stock_maximo" class="form-control" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Precio Compra:</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number" name="precio_compra" class="form-control" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Precio Venta:</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number" name="precio_venta" class="form-control" step="0.01" min="0" required>
                                        </div>
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