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
      <h1 class="m-0">Editar Venta #<?= $venta['id_venta'] ?></h1>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="card card-success">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fa fa-edit"></i> Actualización de venta
          </h3>
        </div>

       <?php include_once('../app/controllers/helpers/csrf.php'); ?>
       <form action="../app/controllers/ventas/update_venta.php" method="POST" enctype="multipart/form-data">
         <?= csrf_field() ?>

          <input type="hidden" name="id_venta" value="<?= $venta['id_venta'] ?>">

          <div class="card-body">

            <!-- INFO GENERAL -->
            <div class="row">

              <div class="col-md-3">
                <div class="form-group">
                  <label><strong>Fecha</strong></label>
                  <input type="date" name="fecha" class="form-control"
                         value="<?= date('Y-m-d', strtotime($venta['fecha'])) ?>" required>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <label><strong>Cliente</strong></label>
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

              <div class="col-md-2">
                <div class="form-group">
                  <label><strong>Tipo de envío</strong></label>
                  <select name="envio" id="tipo_envio" class="form-control" required onchange="actualizarPorEnvio()">
                    <option value="local"   <?= $venta['envio']=='local'   ?'selected':'' ?>>Local</option>
                    <option value="foraneo" <?= $venta['envio']=='foraneo' ?'selected':'' ?>>Foráneo</option>
                  </select>
                </div>
              </div>

              <!-- TIPO DE PAGO (solo local) -->
              <div class="col-md-2" id="col_tipo_pago" style="<?= $venta['envio']!='local' ? 'display:none;' : '' ?>">
                <div class="form-group">
                  <label><strong>Tipo de pago</strong></label>
                  <input type="hidden" name="tipo_pago" id="tipo_pago" value="<?= htmlspecialchars($venta['tipo_pago'] ?? 'comprobante') ?>">
                  <div class="d-flex gap-2">
                    <button type="button" id="btn_efectivo"
                            onclick="seleccionarPago('efectivo')"
                            class="btn flex-fill py-2 <?= ($venta['tipo_pago'] ?? '') === 'efectivo' ? 'btn-success' : 'btn-outline-success' ?>"
                            style="border-radius:10px; font-size:13px;">
                      💵<br><small>Efectivo</small>
                    </button>
                    <button type="button" id="btn_comprobante"
                            onclick="seleccionarPago('comprobante')"
                            class="btn flex-fill py-2 <?= ($venta['tipo_pago'] ?? 'comprobante') !== 'efectivo' ? 'btn-primary' : 'btn-outline-primary' ?>"
                            style="border-radius:10px; font-size:13px;">
                      🧾<br><small>Comprobante</small>
                    </button>
                  </div>
                </div>
              </div>

              <div class="col-md-2">
                <div class="form-group">
                  <label><strong>Vendedor</strong></label>
                  <input type="text" class="form-control" value="<?= $venta['vendedor_nombre'] ?? $venta['id_usuario'] ?>" disabled>
                </div>
              </div>

            </div>

            <hr class="my-3">

            <!-- COMPROBANTE -->
            <div class="row mb-4" id="fila_comprobante" style="<?= ($venta['tipo_pago'] ?? 'comprobante') === 'efectivo' ? 'display:none;' : '' ?>">
              <div class="col-md-6">
                <div class="form-group">
                  <label><strong><i class="fa fa-file-pdf text-danger"></i> Comprobante</strong></label>

                  <div id="drop_zone"
                       onclick="document.getElementById('comprobante').click()"
                       style="border: 2px dashed #aaa; border-radius: 10px; padding: 30px; text-align: center; cursor: pointer; background: #f9f9f9; transition: background 0.2s;">
                    <i class="fa fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                    <p class="mb-1 text-muted">Arrastra el archivo aquí o <strong>haz clic para buscar</strong></p>
                    <small class="text-muted">PDF, JPG, PNG, DOC, DOCX | Máx. 5MB</small>
                  </div>

                  <input type="file" name="comprobante" id="comprobante"
                         accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                         style="display:none;">
                  <small class="form-text text-info mt-1">Deja vacío si no deseas cambiar el comprobante actual</small>
                  <small id="file_error" class="text-danger font-weight-bold" style="display:none;"></small>
                </div>

                <!-- PREVISUALIZACIÓN NUEVO -->
                <div id="preview_comprobante" style="display:none;">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong>Nuevo comprobante:</strong>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelarComprobante()">
                      <i class="fa fa-times"></i> Quitar
                    </button>
                  </div>
                  <div class="border rounded p-2" id="preview_contenido"></div>
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
            
            // 🔥 SOLUCIÓN: Siempre construir desde la raíz del proyecto
            // Limpiar la ruta de la BD (quitar "app/" si existe al inicio)
            $archivo = basename($venta['comprobante']);
            
            // Ruta para el navegador (desde ventas/ subir a raíz y bajar a app/comprobantes/)
            $ruta = "../app/comprobantes/" . $archivo;
            
            // Ruta física absoluta para verificar existencia
            $ruta_fisica = realpath(__DIR__ . '/../app/comprobantes/' . $archivo);
          ?>

          <?php if ($ruta_fisica && file_exists($ruta_fisica)): ?>
            <?php if (in_array(strtolower($ext), ['jpg','jpeg','png'])): ?>
              <img src="<?= $ruta ?>" class="img-responsive border" style="max-height:300px; max-width:100%;" alt="Comprobante">
            <?php elseif (strtolower($ext) === 'pdf'): ?>
              <embed src="<?= $ruta ?>" type="application/pdf" width="100%" height="300px">
            <?php else: ?>
              <a href="<?= $ruta ?>" target="_blank" class="btn btn-sm btn-info">
                <i class="fa fa-file"></i> Ver comprobante (<?= strtoupper($ext) ?>)
              </a>
            <?php endif; ?>
          <?php else: ?>
            <div class="alert alert-danger">
              <i class="fa fa-exclamation-triangle"></i> 
              <strong>Archivo no encontrado</strong><br>
              <small class="text-muted">Archivo: <?= htmlspecialchars($archivo) ?></small><br>
              <small class="text-muted">Buscado en: <?= htmlspecialchars(__DIR__ . '/../app/comprobantes/' . $archivo) ?></small>
            </div>
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
            <h5 class="mb-3"><i class="fa fa-box text-primary"></i> Artículos</h5>

            <div class="table-responsive">
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

            </div><!-- /table-responsive -->

            <button type="button" class="btn btn-outline-secondary btn-sm mb-3"
                    onclick="agregarFila()">
              <i class="fa fa-plus"></i> Agregar artículo
            </button>

            <hr class="my-3">

            <div class="row justify-content-end">
              <div class="col-md-3">
                <div class="form-group">
                  <label><strong>Total $</strong></label>
                  <input type="number" name="total" id="total_venta"
                         class="form-control form-control-lg text-center font-weight-bold text-success"
                         value="<?= $venta['total'] ?>" readonly>
                </div>
              </div>
            </div>

          </div>

          <div class="card-footer">
            <a href="index.php" class="btn btn-outline-danger">
              <i class="fa fa-times"></i> Cancelar
            </a>

            <button type="submit" class="btn btn-success float-right">
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
   TIPO DE PAGO Y COMPROBANTE
========================= */
function seleccionarPago(tipo){
  document.getElementById('tipo_pago').value = tipo;

  const btnEfectivo    = document.getElementById('btn_efectivo');
  const btnComprobante = document.getElementById('btn_comprobante');

  if(tipo === 'efectivo'){
    btnEfectivo.className    = 'btn btn-success flex-fill py-2';
    btnComprobante.className = 'btn btn-outline-primary flex-fill py-2';
  } else {
    btnEfectivo.className    = 'btn btn-outline-success flex-fill py-2';
    btnComprobante.className = 'btn btn-primary flex-fill py-2';
  }

  actualizarComprobante();
}

function actualizarComprobante(){
  const pago             = document.getElementById('tipo_pago').value;
  const filaComprobante  = document.getElementById('fila_comprobante');
  const inputComprobante = document.getElementById('comprobante');

  if(pago === 'efectivo'){
    filaComprobante.style.display = 'none';
    inputComprobante.value        = '';
    document.getElementById('preview_comprobante').style.display = 'none';
    document.getElementById('preview_contenido').innerHTML       = '';
  } else {
    filaComprobante.style.display = 'block';
  }
}

function actualizarPorEnvio(){
  const envio           = document.getElementById('tipo_envio').value;
  const colTipoPago     = document.getElementById('col_tipo_pago');
  const filaComprobante = document.getElementById('fila_comprobante');

  if(envio === 'local'){
    colTipoPago.style.display = 'block';
    seleccionarPago(document.getElementById('tipo_pago').value || 'efectivo');
  } else {
    colTipoPago.style.display     = 'none';
    filaComprobante.style.display = 'block';
    document.getElementById('tipo_pago').value = 'comprobante';
  }
}

/* =========================
   DRAG & DROP + PREVIEW COMPROBANTE
========================= */
const dropZone  = document.getElementById('drop_zone');
const inputFile = document.getElementById('comprobante');

dropZone.addEventListener('dragover', (e) => {
  e.preventDefault();
  dropZone.style.background  = '#e8f4ff';
  dropZone.style.borderColor = '#007bff';
});

dropZone.addEventListener('dragleave', () => {
  dropZone.style.background  = '#f9f9f9';
  dropZone.style.borderColor = '#aaa';
});

dropZone.addEventListener('drop', (e) => {
  e.preventDefault();
  dropZone.style.background  = '#f9f9f9';
  dropZone.style.borderColor = '#aaa';
  const file = e.dataTransfer.files[0];
  if (file) procesarArchivo(file);
});

inputFile.addEventListener('change', function () {
  if (this.files[0]) procesarArchivo(this.files[0]);
});

async function comprimirImagen(file, maxWidth = 1200, quality = 0.82) {
  return new Promise((resolve) => {
    if (!file.type.startsWith('image/')) { resolve(file); return; }
    const reader = new FileReader();
    reader.onload = (ev) => {
      const img = new Image();
      img.onload = () => {
        let { width, height } = img;
        if (width <= maxWidth && file.size < 500 * 1024) { resolve(file); return; }
        const scale  = width > maxWidth ? maxWidth / width : 1;
        const canvas = document.createElement('canvas');
        canvas.width  = Math.round(width  * scale);
        canvas.height = Math.round(height * scale);
        canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
        canvas.toBlob((blob) => {
          const resultado = blob && blob.size < file.size
            ? new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg', lastModified: Date.now() })
            : file;
          resolve(resultado);
        }, 'image/jpeg', quality);
      };
      img.src = ev.target.result;
    };
    reader.readAsDataURL(file);
  });
}

async function procesarArchivo(file) {
  dropZone.innerHTML = `
    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
    <span class="text-muted ms-2"> Procesando...</span>`;

  const fileOptimizado = await comprimirImagen(file);
  const dt = new DataTransfer();
  dt.items.add(fileOptimizado);
  inputFile.files = dt.files;
  mostrarPreview(fileOptimizado);
}

function mostrarPreview(file) {
  const preview   = document.getElementById('preview_comprobante');
  const contenido = document.getElementById('preview_contenido');
  const errorMsg  = document.getElementById('file_error');
  const actual    = document.getElementById('comprobante_actual');

  contenido.innerHTML    = '';
  preview.style.display  = 'none';
  errorMsg.style.display = 'none';

  const maxSize = 5 * 1024 * 1024;
  if (file.size > maxSize) {
    errorMsg.textContent   = '❌ El archivo excede el tamaño máximo de 5MB';
    errorMsg.style.display = 'block';
    inputFile.value = '';
    return;
  }

  const tiposPermitidos = [
    'image/jpeg', 'image/jpg', 'image/png', 'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
  ];
  if (!tiposPermitidos.includes(file.type)) {
    errorMsg.textContent   = '❌ Formato de archivo no permitido';
    errorMsg.style.display = 'block';
    inputFile.value = '';
    return;
  }

  // Ocultar comprobante actual y mostrar nuevo
  if (actual) actual.style.display = 'none';

  dropZone.innerHTML = `
    <i class="fa fa-check-circle fa-2x text-success mb-2"></i>
    <p class="mb-0 text-success font-weight-bold">${file.name}</p>
    <small class="text-muted">${(file.size / 1024).toFixed(2)} KB — haz clic para cambiar</small>`;

  if (file.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = e => {
      contenido.innerHTML   = `<img src="${e.target.result}" class="img-fluid rounded" style="max-height:300px;">`;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
  } else if (file.type === 'application/pdf') {
    const url             = URL.createObjectURL(file);
    contenido.innerHTML   = `<embed src="${url}" type="application/pdf" width="100%" height="300px">`;
    preview.style.display = 'block';
  } else {
    contenido.innerHTML   = `
      <div class="alert alert-success mb-0">
        <i class="fa fa-file"></i> <strong>${file.name}</strong><br>
        <small>${(file.size / 1024).toFixed(2)} KB</small>
      </div>`;
    preview.style.display = 'block';
  }
}

function cancelarComprobante() {
  inputFile.value = '';
  document.getElementById('preview_comprobante').style.display = 'none';
  document.getElementById('preview_contenido').innerHTML       = '';
  document.getElementById('file_error').style.display         = 'none';
  const actual = document.getElementById('comprobante_actual');
  if (actual) actual.style.display = 'block';
  dropZone.innerHTML = `
    <i class="fa fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
    <p class="mb-1 text-muted">Arrastra el archivo aquí o <strong>haz clic para buscar</strong></p>
    <small class="text-muted">PDF, JPG, PNG, DOC, DOCX | Máx. 5MB</small>`;
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
  const filas = document.querySelectorAll('#detalle_venta tr');
  if(filas.length === 1){
    Swal.fire({
      icon: 'warning',
      title: 'Atención',
      text: 'Debe existir al menos un producto'
    });
    return;
  }
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


<link rel="stylesheet" href="<?= $URL ?>/public/templates/AdminLTE-3.2.0/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="<?= $URL ?>/public/templates/AdminLTE-3.2.0/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<script src="<?= $URL ?>/public/templates/AdminLTE-3.2.0/plugins/select2/js/select2.full.min.js"></script>
<script>
$(document).ready(function(){
  $('.producto').select2({
    theme: 'bootstrap4',
    placeholder: 'Buscar por nombre o código...',
    width: '100%'
  }).on('change', function(){ asignarPrecio(this); });
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