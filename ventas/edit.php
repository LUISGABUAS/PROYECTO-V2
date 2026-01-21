<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');
include('../app/controllers/almacen/list_almacen.php');
/* =========================
   CONTROLLER
========================= */
include('../app/controllers/ventas/edit_venta.php');

if(in_array(22, $_SESSION['permisos'])):
?>

<?php if (isset($_SESSION['mensaje'])): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Atención',
    text: <?= json_encode($_SESSION['mensaje']) ?>,
    confirmButtonText: 'Entendido'
});
</script>
<?php unset($_SESSION['mensaje']); endif; ?>

<div class="content-wrapper">

  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">✏️ Editar Venta #<?= $venta['id_venta'] ?></h1>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="card card-warning">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fa fa-edit"></i> Actualización de venta
          </h3>
        </div>

       <form action="../app/controllers/ventas/update_venta.php" method="POST" enctype="multipart/form-data">

          <input type="hidden" name="id_venta" value="<?= $venta['id_venta'] ?>">

          <div class="card-body">

            <!-- INFO GENERAL -->
            <div class="row">

              <div class="col-md-3">
                <div class="form-group">
                  <label>Fecha</label>
                  <input type="date" name="fecha" class="form-control"
                         value="<?= date('Y-m-d', strtotime($venta['fecha'])) ?>" required>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <label>Cliente</label>
                  <select name="cliente" class="form-control" required>
                    <option value="">Seleccione cliente</option>
                    <?php foreach($clientes_lista as $cli): ?>
                      <option value="<?= $cli['id_cliente'] ?>" <?= $venta['cliente'] == $cli['id_cliente'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cli['nombre_completo']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label>Tipo de envío</label>
                  <select name="envio" class="form-control" required>
                    <option value="local" <?= $venta['envio']=='local'?'selected':'' ?>>Local</option>
                    <option value="foraneo" <?= $venta['envio']=='foraneo'?'selected':'' ?>>Foráneo</option>
                  </select>
                </div>
              </div>

              <div class="col-md-2">
                <div class="form-group">
                  <label>Vendedor</label>
                  <input type="text" class="form-control" value="<?= $venta['vendedor_nombre'] ?? $venta['id_usuario'] ?>" disabled>
                </div>
              </div>

            </div>

            <hr class="my-3">

            <!-- COMPROBANTE -->
            <div class="row mb-4">
              <div class="col-md-6">
                <div class="form-group">
                  <label><strong><i class="fa fa-file-pdf text-danger"></i> Comprobante</strong></label>
                  <input type="file" name="comprobante" id="comprobante" 
                         class="form-control-file border rounded p-2" 
                         accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                         onchange="previsualizarComprobante(this)">
                  <small class="form-text text-muted d-block mt-2">📋 Formatos: PDF, JPG, PNG, DOC, DOCX | 📦 Máx. 5MB</small>
                </div>
              </div>
            </div>

            <!-- COMPROBANTE ACTUAL -->
            <?php if (!empty($venta['comprobante'])): ?>
            <div class="row mb-3" id="comprobante_actual">
              <div class="col-md-6">
                <label><strong>Comprobante actual:</strong></label>
                <hr>

                <?php
                  $ext = pathinfo($venta['comprobante'], PATHINFO_EXTENSION);
                  $ruta = "../app/comprobantes/" . $venta['comprobante'];
                ?>

                <?php if (in_array(strtolower($ext), ['jpg','jpeg','png'])): ?>
                  <img src="<?= $ruta ?>" class="img-responsive border" style="max-height:300px; max-width:100%;">
                <?php elseif ($ext === 'pdf'): ?>
                  <embed src="<?= $ruta ?>" type="application/pdf" width="100%" height="300px">
                <?php else: ?>
                  <a href="<?= $ruta ?>" target="_blank" class="btn btn-sm btn-info">
                    <i class="fa fa-file"></i> Ver comprobante
                  </a>
                <?php endif; ?>
              </div>
            </div>
            <?php else: ?>
            <div class="row mb-3" id="comprobante_actual">
              <div class="col-md-6">
                <div class="alert alert-warning">
                  <i class="fa fa-exclamation-triangle"></i> No hay comprobante registrado
                </div>
              </div>
            </div>
            <?php endif; ?>

            <!-- PREVISUALIZACIÓN NUEVO COMPROBANTE -->
            <div class="row mb-3" id="preview_comprobante" style="display:none;">
              <div class="col-md-6">
                <label><strong><i class="fa fa-eye text-success"></i> Nuevo comprobante:</strong></label>
                <hr>
                <div id="preview_contenido" class="border p-2 bg-light"></div>
                <button type="button" class="btn btn-sm btn-danger mt-2" onclick="cancelarComprobante()">
                  <i class="fa fa-times"></i> Cancelar cambio
                </button>
              </div>
            </div>

            <hr class="my-3">

            <!-- ARTÍCULOS -->
            <h5><i class="fa fa-box"></i> Artículos</h5>

            <table class="table table-bordered table-sm">
              <thead class="thead-light">
                <tr class="text-center">
                  <th>Producto</th>
                  <th width="120">Cantidad</th>
                  <th width="150">Precio $</th>
                  <th width="150">Subtotal $</th>
                  <th width="50"></th>
                </tr>
              </thead>

              <tbody id="detalle_venta">
                <?php foreach($detalle as $d): ?>
                <tr>
                  <td>
                    <select name="productos[]" class="form-control form-control-sm producto"
                            onchange="asignarPrecio(this)" required>
                      <?php foreach($datos_productos as $p): ?>
                        <option value="<?= $p['id_producto'] ?>"
                                data-precio="<?= $p['precio_venta'] ?>"
                                <?= $p['id_producto']==$d['id_producto']?'selected':'' ?>>
                          <?= htmlspecialchars($p['codigo'] . ' - ' . $p['nombre']) ?>
                        </option>
                      <?php endforeach ?>
                    </select>
                  </td>

                  <td>
                    <input type="number" name="cantidades[]"
                           class="form-control form-control-sm text-center cantidad"
                           value="<?= $d['cantidad'] ?>"
                           min="1" oninput="calcularFila(this)" required>
                  </td>

                  <td>
                    <input type="number" name="precios[]"
                           class="form-control form-control-sm text-center precio"
                           value="<?= $d['precio'] ?>" readonly>
                  </td>

                  <td>
                    <input type="number"
                           class="form-control form-control-sm text-center subtotal"
                           value="<?= number_format($d['cantidad']*$d['precio'],2,'.','') ?>"
                           readonly>
                  </td>

                  <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm"
                            onclick="eliminarFila(this)">
                      <i class="fa fa-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endforeach ?>
              </tbody>
            </table>

            <button type="button" class="btn btn-outline-secondary btn-sm"
                    onclick="agregarFila()">
              <i class="fa fa-plus"></i> Agregar artículo
            </button>

            <div class="row justify-content-end mt-3">
              <div class="col-md-3">
                <div class="form-group">
                  <label>Total $</label>
                  <input type="number" name="total" id="total_venta"
                         class="form-control text-center font-weight-bold"
                         value="<?= $venta['total'] ?>" readonly>
                </div>
              </div>
            </div>

          </div>

          <div class="card-footer">
            <a href="index.php" class="btn btn-outline-danger">
              <i class="fa fa-times"></i> Cancelar
            </a>

            <button type="submit" class="btn btn-warning">
              <i class="fa fa-save"></i> Actualizar venta
            </button>
          </div>

        </form>

      </div>

    </div>
  </div>
</div>


<script>
/* =========================
   PREVISUALIZAR COMPROBANTE
========================= */
function previsualizarComprobante(input) {
  const preview = document.getElementById('preview_comprobante');
  const contenido = document.getElementById('preview_contenido');
  const actual = document.getElementById('comprobante_actual');
  
  if (input.files && input.files[0]) {
    const file = input.files[0];
    const fileSize = file.size / 1024 / 1024; // en MB
    
    // Validar tamaño
    if (fileSize > 5) {
      Swal.fire({
        icon: 'error',
        title: 'Archivo muy grande',
        text: 'El comprobante no puede superar los 5MB'
      });
      input.value = '';
      return;
    }
    
    // Obtener extensión
    const ext = file.name.split('.').pop().toLowerCase();
    const permitidos = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    
    if (!permitidos.includes(ext)) {
      Swal.fire({
        icon: 'error',
        title: 'Formato no permitido',
        text: 'Solo se permiten: PDF, JPG, PNG, DOC, DOCX'
      });
      input.value = '';
      return;
    }
    
    // Ocultar comprobante actual
    actual.style.display = 'none';
    
    // Mostrar preview
    preview.style.display = 'block';
    
    // Leer y mostrar el archivo
    const reader = new FileReader();
    
    reader.onload = function(e) {
      if (['jpg', 'jpeg', 'png'].includes(ext)) {
        // Previsualizar imagen
        contenido.innerHTML = `<img src="${e.target.result}" class="img-fluid" style="max-height:300px;">`;
      } else if (ext === 'pdf') {
        // Previsualizar PDF
        contenido.innerHTML = `<embed src="${e.target.result}" type="application/pdf" width="100%" height="300px">`;
      } else {
        // Para documentos
        contenido.innerHTML = `
          <div class="alert alert-info">
            <i class="fa fa-file-word"></i> <strong>${file.name}</strong><br>
            <small>Tamaño: ${fileSize.toFixed(2)} MB</small><br>
            <small class="text-muted">El documento se cargará al guardar</small>
          </div>
        `;
      }
    };
    
    reader.readAsDataURL(file);
  }
}

/* =========================
   CANCELAR CAMBIO DE COMPROBANTE
========================= */
function cancelarComprobante() {
  const input = document.getElementById('comprobante');
  const preview = document.getElementById('preview_comprobante');
  const actual = document.getElementById('comprobante_actual');
  
  // Limpiar input
  input.value = '';
  
  // Ocultar preview
  preview.style.display = 'none';
  
  // Mostrar comprobante actual
  actual.style.display = 'block';
}

/* =========================
   AGREGAR FILA
========================= */
function agregarFila(){
  let fila = `
  <tr>
    <td>
      <select name="productos[]"
              class="form-control form-control-sm producto"
              onchange="asignarPrecio(this)" required>
        <option value="">Seleccione</option>
        <?php foreach($datos_productos as $p){ ?>
          <option value="<?= $p['id_producto'] ?>"
                  data-precio="<?= $p['precio_venta'] ?>">
            <?= htmlspecialchars($p['codigo'] . ' - ' . $p['nombre']) ?>
          </option>
        <?php } ?>
      </select>
    </td>

    <td>
      <input type="number"
             name="cantidades[]"
             class="form-control form-control-sm text-center cantidad"
             min="1" value="1"
             oninput="calcularFila(this)" required>
    </td>

    <td>
      <input type="number"
             name="precios[]"
             class="form-control form-control-sm text-center precio"
             step="0.01" readonly>
    </td>

    <td>
      <input type="number"
             class="form-control form-control-sm text-center subtotal"
             step="0.01" readonly>
    </td>

    <td class="text-center">
      <button type="button"
              class="btn btn-danger btn-sm"
              onclick="eliminarFila(this)">
        <i class="fa fa-trash"></i>
      </button>
    </td>
  </tr>`;

  document.getElementById('detalle_venta')
          .insertAdjacentHTML('beforeend', fila);
}


/* =========================
   ASIGNAR PRECIO AL PRODUCTO
========================= */
function asignarPrecio(select){
  const producto = select.value;
  const selects = document.querySelectorAll('.producto');

  let repetidos = 0;
  selects.forEach(s => {
    if(s.value === producto) repetidos++;
  });

  if(repetidos > 1){
    Swal.fire({
      icon:'warning',
      title:'Producto duplicado',
      text:'Este producto ya fue agregado'
    });
    select.value = '';
    return;
  }

  const precio = select.options[select.selectedIndex].dataset.precio || 0;
  const fila = select.closest('tr');
  fila.querySelector('.precio').value = precio;
  calcularFila(select);
}


/* =========================
   CALCULAR SUBTOTAL
========================= */
function calcularFila(elemento){
  const fila = elemento.closest('tr');
  const cantidad = parseFloat(fila.querySelector('.cantidad').value || 0);
  const precio   = parseFloat(fila.querySelector('.precio').value || 0);

  fila.querySelector('.subtotal').value = (cantidad * precio).toFixed(2);

  calcularTotal();
}


/* =========================
   CALCULAR TOTAL
========================= */
function calcularTotal(){
  let total = 0;
  document.querySelectorAll('.subtotal').forEach(sub => {
    total += parseFloat(sub.value || 0);
  });
  document.getElementById('total_venta').value = total.toFixed(2);
}


/* =========================
   ELIMINAR FILA
========================= */
function eliminarFila(btn){
  btn.closest('tr').remove();
  calcularTotal();
}


/* =========================
   🔥 SINCRONIZAR AL CARGAR (CLAVE)
========================= */
document.addEventListener('DOMContentLoaded', () => {

  document.querySelectorAll('.producto').forEach(select => {
    asignarPrecio(select); // 👈 fuerza el precio correcto
  });

});
</script>


<?php include('../layout/parte2.php'); ?>

<?php else: include('../layout/parte2.php'); ?>
<script>
Swal.fire({
  icon: "error",
  title: "Access Denied",
  text: "No tienes permisos.",
  timer: 3000,
  showConfirmButton: false
}).then(() => {
  window.location = "<?= $URL ?>";
});
</script>
<?php endif; ?>