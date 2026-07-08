<?php

include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

include('../app/controllers/categorias/list_categorias.php');
include('../app/controllers/provedores/list_provedores.php');
include('../app/controllers/almacen/cargar_producto.php');



?>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Edit Product</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo $URL;?>">Home</a></li>
              <li class="breadcrumb-item active">Starter Page</li>
              <li class="breadcrumb-item active">Edit Product</li>
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
            <div class="card card-success">
              <div class="card-header">
                <h3 class="card-title">Edit Product</h3>

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
                        <form action="../app/controllers/almacen/update.php" method="post" enctype="multipart/form-data">
                          <?= csrf_field() ?>
                        <input type="text" name="id_producto" value="<?php echo $id_producto_get;?>" hidden>
                        <input type="hidden" name="fp" value="<?= (int)($_GET['fp'] ?? 0) ?>">

                        <div class="row">
                          <div class="col-md-9">
                             <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Codigo:</label>
                                        <input type="text" class="form-control" value="<?php echo $codigo;?>" disabled>
                                        <input type="text" name="codigo" value="<?php echo $codigo ?>" hidden >
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Categoria:</label>
                                        <select name="id_categoria" id="" class="form-control" required>
                                          <?php foreach ($datos_categorias as $datos_categoria) {
                                            $nombre_categoria_tabla = $datos_categoria['nombre_categoria']; ;
                                            $id_categoria = $datos_categoria['id_categoria'] ?>

                                    <option value="<?php echo $id_categoria;?>"<?php if ($nombre_categoria_tabla == $categoria){ ?> selected="selected" <?php } ?> >
                                      <?php echo $nombre_categoria_tabla; ?>

                                    </option><?php
                                  }?>


                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Proveedor:</label>
                                        <select name="id_proovedor" class="form-control" required>
                                          <?php foreach ($proovedores_datos as $prov): ?>
                                            <option value="<?= $prov['id_proovedor'] ?>"
                                              <?= $prov['id_proovedor'] == $id_proovedor ? 'selected' : '' ?>>
                                              <?= htmlspecialchars($prov['nombre_proveedor']) ?>
                                            </option>
                                          <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Nombre Producto:</label>
                                        <input type="text" name="nombre" value="<?php echo $nombre?>" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                              <div class="col-md-4">
                                <div class="form-group">
                                  <label for="">Usuario</label>
                                  <input type="text" class="form-control" value="<?php echo $nombre_usuario?>" disabled>
                                  <input type="text" name="id_usuario" value="<?php echo $id_usuario?>" hidden>
                                </div>
                              </div>
                              <div class="col-md-8">
                                <div class="form-group">
                                        <label for="">Descripcion Producto:</label>
                                        <textarea name="descripcion" id="" cols="30" rows="2"  class="form-control"><?php echo $descripcion?></textarea>
                                    </div>
                              </div>
                            </div>

                            <?php
                            $calidades = [
                                'Calidad 1',
                                'Calidad 2',
                                'Calidad 3 - económico',
                                'Calidad premium',
                                'Calidad 1 y 2',
                                'Calidad premium con 1',
                                'Calidad Mixtas',
                            ];
                            ?>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Calidad:</label>
                                        <select name="calidad" class="form-control" required>
                                            <option value="">— Selecciona —</option>
                                            <?php foreach ($calidades as $c): ?>
                                                <option value="<?= $c ?>" <?= $calidad === $c ? 'selected' : '' ?>>
                                                    <?= $c ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Piezas: <small class="text-muted">(por paca)</small></label>
                                        <input type="number" name="piezas" class="form-control" min="1"
                                               value="<?= htmlspecialchars($piezas) ?>" placeholder="Ej: 12">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Fecha Ingreso:</label>
                                        <input type="date" name="fecha_ingreso" class="form-control" value="<?= $fecha_ingreso ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Stock Mínimo:</label>
                                        <input type="number" name="stock_minimo" class="form-control" value="<?= $stock_minimo ?>" min="0">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Stock Máximo:</label>
                                        <input type="number" name="stock_maximo" class="form-control" value="<?= $stock_maximo ?>" min="0">
                                    </div>
                                </div>
                                <?php if (in_array(34, $_SESSION['permisos'])): ?>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Precio Compra:</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number" name="precio_compra" class="form-control" step="0.01" min="0" value="<?= $precio_compra ?>">
                                        </div>
                                        <input type="hidden" name="precio_compra_anterior" value="<?= $precio_compra ?>">
                                    </div>
                                </div>
                                <?php else: ?>
                                <input type="hidden" name="precio_compra_anterior" value="<?= $precio_compra ?>">
                                <?php endif; ?>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Precio Venta:</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number" name="precio_venta" class="form-control" step="0.01" min="0" value="<?= $precio_venta ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                          </div>

                            <div class="col-md-3">
                              <label for="">Imagen</label>
                              <input type="file" name="image" class="form-control" id="file" >
                              <input type="text" name="image_text" value="<?php echo $imagen;?>" hidden>
                              <br>
                              <output id="list">
                                <img src="<?= $URL . "/almacen/img_productos/" . $imagen;?>" width="100%" alt="">
                              </output>
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
                                <button type="submit" class="btn btn-success">Update</button>
                            </div>
                        </form>
                    </div>
                </div>

              </div>
              <!-- /.card-body -->
            </div>
        </div>
      </div>


      <!-- SECCIÓN DESCUENTOS -->
      <div class="row">
        <div class="col-md-12">
          <div class="card card-warning">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-tags"></i> Descuento del Producto</h3>
            </div>
            <div class="card-body">

              <?php if ($descuento_activo): ?>
              <!-- Descuento activo -->
              <div class="alert alert-warning d-flex align-items-center justify-content-between">
                <div>
                  <strong><i class="fas fa-tag"></i> Descuento activo:</strong>
                  <?= $descuento_activo['porcentaje'] ?>% — Precio con descuento:
                  <strong>$<?= number_format($descuento_activo['precio_descuento'], 2) ?></strong>
                  &nbsp;|&nbsp; Termina: <strong><?= date('d/m/Y H:i', strtotime($descuento_activo['fecha_fin'])) ?></strong>
                </div>
                <button type="button" class="btn btn-danger btn-sm ml-3"
                        onclick="cancelarDescuento(<?= $descuento_activo['id'] ?>)">
                  <i class="fas fa-times"></i> Cancelar descuento
                </button>
              </div>
              <?php else: ?>
              <p class="text-muted mb-3">Sin descuento activo. Precio de venta actual: <strong>$<?= number_format($precio_venta, 2) ?></strong></p>
              <?php endif; ?>

              <!-- Formulario nuevo descuento -->
              <?php if (!$descuento_activo): ?>
              <div class="row align-items-end">
                <div class="col-md-2">
                  <div class="form-group mb-0">
                    <label><strong>Descuento %</strong></label>
                    <div class="input-group">
                      <input type="number" id="desc_porcentaje" class="form-control"
                             min="1" max="99" step="0.01" placeholder="Ej: 15">
                      <div class="input-group-append"><span class="input-group-text">%</span></div>
                    </div>
                  </div>
                </div>
                <div class="col-md-2">
                  <div class="form-group mb-0">
                    <label><strong>Precio resultante</strong></label>
                    <div class="input-group">
                      <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                      <input type="text" id="desc_precio_resultado" class="form-control" readonly>
                    </div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group mb-0">
                    <label><strong>Inicio</strong></label>
                    <input type="datetime-local" id="desc_inicio" class="form-control"
                           value="<?= date('Y-m-d\TH:i') ?>">
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group mb-0">
                    <label><strong>Fin</strong></label>
                    <input type="datetime-local" id="desc_fin" class="form-control">
                  </div>
                </div>
                <div class="col-md-2">
                  <button type="button" class="btn btn-warning btn-block"
                          onclick="guardarDescuento()">
                    <i class="fas fa-tag"></i> Aplicar descuento
                  </button>
                </div>
              </div>
              <?php endif; ?>

            </div>
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

<script>
const _precioVenta   = <?= (float)$precio_venta ?>;
const _idProducto    = <?= (int)$id_producto_get ?>;
const _urlDescuento  = '<?= $URL ?>/app/controllers/almacen/guardar_descuento.php';

document.getElementById('desc_porcentaje')?.addEventListener('input', function(){
  const pct = parseFloat(this.value);
  const res  = document.getElementById('desc_precio_resultado');
  if (pct > 0 && pct < 100) {
    res.value = (_precioVenta * (1 - pct / 100)).toFixed(2);
  } else {
    res.value = '';
  }
});

function guardarDescuento() {
  const pct    = parseFloat(document.getElementById('desc_porcentaje').value);
  const inicio = document.getElementById('desc_inicio').value;
  const fin    = document.getElementById('desc_fin').value;

  if (!pct || pct <= 0 || pct >= 100) {
    Swal.fire({ icon: 'warning', title: 'Descuento inválido', text: 'Ingresa un porcentaje entre 1 y 99' });
    return;
  }
  if (!fin || fin <= inicio) {
    Swal.fire({ icon: 'warning', title: 'Fechas inválidas', text: 'La fecha de fin debe ser posterior al inicio' });
    return;
  }

  const fd = new FormData();
  fd.append('accion', 'guardar');
  fd.append('id_producto', _idProducto);
  fd.append('porcentaje', pct);
  fd.append('fecha_inicio', inicio.replace('T', ' '));
  fd.append('fecha_fin',    fin.replace('T', ' '));

  fetch(_urlDescuento, { method: 'POST', body: fd, credentials: 'same-origin' })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        Swal.fire({ icon: 'success', title: '¡Descuento aplicado!',
          text: 'Precio con descuento: $' + parseFloat(data.precio_descuento).toFixed(2),
          confirmButtonText: 'OK'
        }).then(() => location.reload());
      } else {
        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
      }
    });
}

function cancelarDescuento(idDescuento) {
  Swal.fire({
    icon: 'question',
    title: '¿Cancelar descuento?',
    text: 'El precio volverá al precio de venta original.',
    showCancelButton: true,
    confirmButtonText: 'Sí, cancelar',
    cancelButtonText: 'No'
  }).then(res => {
    if (!res.isConfirmed) return;
    const fd = new FormData();
    fd.append('accion', 'cancelar');
    fd.append('id_producto', _idProducto);
    fd.append('id_descuento', idDescuento);
    fetch(_urlDescuento, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          Swal.fire({ icon: 'success', title: 'Descuento cancelado', timer: 1500, showConfirmButton: false })
            .then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
      });
  });
}
</script>