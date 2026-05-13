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

            <!-- COMPROBANTES -->
            <div id="fila_comprobante" style="<?= ($venta['tipo_pago'] ?? 'comprobante') === 'efectivo' ? 'display:none;' : '' ?>">

              <h6 class="mb-2"><i class="fa fa-file-pdf text-danger"></i> Comprobantes</h6>

              <?php if (!empty($comprobantes_lista)): ?>
              <!-- COMPROBANTES EXISTENTES -->
              <div class="row mb-3" id="comprobantes_existentes">
                <?php foreach ($comprobantes_lista as $comp):
                  $arch = basename($comp['ruta']);
                  $ext  = strtolower(pathinfo($arch, PATHINFO_EXTENSION));
                  $ruta = "../app/comprobantes/" . $arch;
                ?>
                <div class="col-md-4 mb-3" id="comp_card_<?= htmlspecialchars($comp['id']) ?>">
                  <div class="card border">
                    <div class="card-body p-2">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted font-weight-bold">Comprobante</small>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="marcarEliminar('<?= htmlspecialchars($comp['id']) ?>')"
                                id="btndelete_<?= htmlspecialchars($comp['id']) ?>">
                          <i class="fa fa-trash"></i>
                        </button>
                      </div>
                      <input type="hidden" name="delete_comprobantes[]" id="del_<?= htmlspecialchars($comp['id']) ?>" value="" disabled>
                      <?php if (in_array($ext, ['jpg','jpeg','png'])): ?>
                        <img src="<?= $ruta ?>" class="img-fluid rounded border" style="max-height:120px; width:100%; object-fit:cover;"
                             onerror="this.replaceWith(document.getElementById('tpl_broken').content.cloneNode(true))">
                      <?php elseif ($ext === 'pdf'): ?>
                        <embed src="<?= $ruta ?>" type="application/pdf" width="100%" height="120px">
                        <a href="<?= $ruta ?>" target="_blank" class="btn btn-sm btn-outline-secondary btn-block mt-1">
                          <i class="fa fa-external-link-alt"></i> Abrir PDF
                        </a>
                      <?php else: ?>
                        <a href="<?= $ruta ?>" target="_blank" class="btn btn-sm btn-info btn-block mt-1">
                          <i class="fa fa-file"></i> Ver (<?= strtoupper($ext) ?>)
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>

                <!-- Template para imagen rota -->
                <template id="tpl_broken">
                  <div class="alert alert-secondary p-1 mb-0">
                    <small><i class="fa fa-image"></i> Vista previa no disponible en este entorno</small>
                  </div>
                </template>
              </div>
              <?php else: ?>
              <div class="alert alert-warning mb-3">
                <i class="fa fa-exclamation-triangle"></i> No hay comprobantes registrados
              </div>
              <?php endif; ?>

              <!-- NUEVOS COMPROBANTES -->
              <label class="mb-1"><strong>Agregar comprobante(s):</strong></label>
              <div id="slots_comprobantes" class="row" style="margin:0;"></div>
              <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="agregarSlotComprobante()">
                <i class="fa fa-plus"></i> Agregar comprobante
              </button>
              <small class="form-text text-info d-block mt-1">Los campos vacíos se ignoran</small>

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
  const pago            = document.getElementById('tipo_pago').value;
  const filaComprobante = document.getElementById('fila_comprobante');

  if(pago === 'efectivo'){
    filaComprobante.style.display = 'none';
    document.querySelectorAll('[name="comprobantes[]"]').forEach(fi => fi.value = '');
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
   MULTI-COMPROBANTE: SLOTS DINÁMICOS
========================= */

let _slotIdx = 0;

function _htmlSlot(idx) {
  return `
  <div class="col-md-4 mb-3 comprobante-slot" id="cslot_${idx}">
    <div class="card border h-100">
      <div class="card-body p-2">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <small class="text-muted font-weight-bold numero-slot"></small>
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarSlot('cslot_${idx}')">
            <i class="fa fa-times"></i>
          </button>
        </div>
        <div id="cdz_${idx}"
             onclick="document.getElementById('cfile_${idx}').click()"
             style="border:2px dashed #aaa; border-radius:8px; padding:20px; text-align:center; cursor:pointer; background:#f9f9f9; transition:background .2s;">
          <i class="fa fa-cloud-upload-alt fa-lg text-muted mb-1"></i>
          <p class="mb-0 small text-muted">Arrastra o <strong>haz clic</strong></p>
          <small class="text-muted">PDF, JPG, PNG, DOC | 5MB</small>
        </div>
        <input type="file" name="comprobantes[]" id="cfile_${idx}"
               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="display:none;">
        <div id="cprev_${idx}" style="display:none;" class="mt-2">
          <div id="cpcont_${idx}" class="border rounded p-1"></div>
        </div>
        <small id="cerr_${idx}" class="text-danger" style="display:none;"></small>
      </div>
    </div>
  </div>`;
}

function agregarSlotComprobante() {
  _slotIdx++;
  const idx = _slotIdx;
  document.getElementById('slots_comprobantes').insertAdjacentHTML('beforeend', _htmlSlot(idx));
  _renumerarSlots();

  const dz = document.getElementById('cdz_' + idx);
  const fi = document.getElementById('cfile_' + idx);

  dz.addEventListener('dragover',  e => { e.preventDefault(); dz.style.background = '#e8f4ff'; dz.style.borderColor = '#007bff'; });
  dz.addEventListener('dragleave', ()=> { dz.style.background = '#f9f9f9'; dz.style.borderColor = '#aaa'; });
  dz.addEventListener('drop',      e => {
    e.preventDefault(); dz.style.background = '#f9f9f9'; dz.style.borderColor = '#aaa';
    if (e.dataTransfer.files[0]) _procesarArchivoSlot(e.dataTransfer.files[0], idx);
  });
  fi.addEventListener('change', function() {
    if (this.files[0]) _procesarArchivoSlot(this.files[0], idx);
  });
}

function eliminarSlot(slotId) {
  document.getElementById(slotId).remove();
  _renumerarSlots();
}

function _renumerarSlots() {
  document.querySelectorAll('.comprobante-slot .numero-slot').forEach((el, i) => {
    el.textContent = 'Nuevo #' + (i + 1);
  });
}

function marcarEliminar(compId) {
  const card  = document.getElementById('comp_card_' + compId);
  const input = document.getElementById('del_' + compId);
  const btn   = document.getElementById('btndelete_' + compId);

  if (input.disabled) {
    // Marcar para borrar
    input.value    = compId;
    input.disabled = false;
    card.style.opacity = '0.4';
    card.style.border  = '2px solid red';
    btn.innerHTML  = '<i class="fa fa-undo"></i>';
    btn.title      = 'Deshacer';
  } else {
    // Desmarcar
    input.value    = '';
    input.disabled = true;
    card.style.opacity = '1';
    card.style.border  = '';
    btn.innerHTML  = '<i class="fa fa-trash"></i>';
    btn.title      = '';
  }
}

async function comprimirImagen(file, maxWidth = 1200, quality = 0.82) {
  return new Promise(resolve => {
    if (!file.type.startsWith('image/')) { resolve(file); return; }
    const reader = new FileReader();
    reader.onload = ev => {
      const img = new Image();
      img.onload = () => {
        let { width, height } = img;
        if (width <= maxWidth && file.size < 500 * 1024) { resolve(file); return; }
        const scale  = width > maxWidth ? maxWidth / width : 1;
        const canvas = document.createElement('canvas');
        canvas.width  = Math.round(width  * scale);
        canvas.height = Math.round(height * scale);
        canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
        canvas.toBlob(blob => {
          const res = blob && blob.size < file.size
            ? new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg', lastModified: Date.now() })
            : file;
          resolve(res);
        }, 'image/jpeg', quality);
      };
      img.src = ev.target.result;
    };
    reader.readAsDataURL(file);
  });
}

async function _procesarArchivoSlot(file, idx) {
  const dz = document.getElementById('cdz_' + idx);
  const fi = document.getElementById('cfile_' + idx);
  dz.innerHTML = `<div class="spinner-border spinner-border-sm text-primary"></div> <span class="small text-muted">Procesando...</span>`;
  const opt = await comprimirImagen(file);
  const dt  = new DataTransfer();
  dt.items.add(opt);
  fi.files = dt.files;
  _mostrarPreviewSlot(opt, idx);
}

function _mostrarPreviewSlot(file, idx) {
  const dz   = document.getElementById('cdz_'   + idx);
  const fi   = document.getElementById('cfile_' + idx);
  const prev = document.getElementById('cprev_' + idx);
  const cont = document.getElementById('cpcont_'+ idx);
  const err  = document.getElementById('cerr_'  + idx);
  cont.innerHTML = ''; prev.style.display = 'none'; err.style.display = 'none';

  if (file.size > 5 * 1024 * 1024) {
    err.textContent = '❌ Máximo 5MB'; err.style.display = 'block'; fi.value = ''; return;
  }
  const tipos = ['image/jpeg','image/jpg','image/png','application/pdf',
                 'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
  if (!tipos.includes(file.type)) {
    err.textContent = '❌ Formato no permitido'; err.style.display = 'block'; fi.value = ''; return;
  }
  dz.innerHTML = `
    <i class="fa fa-check-circle fa-lg text-success mb-1"></i>
    <p class="mb-0 text-success small font-weight-bold">${file.name}</p>
    <small class="text-muted">${(file.size/1024).toFixed(1)} KB — clic para cambiar</small>`;
  if (file.type.startsWith('image/')) {
    const r = new FileReader();
    r.onload = e => { cont.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded" style="max-height:130px;">`; prev.style.display = 'block'; };
    r.readAsDataURL(file);
  } else if (file.type === 'application/pdf') {
    cont.innerHTML = `<embed src="${URL.createObjectURL(file)}" type="application/pdf" width="100%" height="130px">`;
    prev.style.display = 'block';
  } else {
    cont.innerHTML = `<div class="alert alert-success mb-0 p-2"><i class="fa fa-file"></i> <strong>${file.name}</strong></div>`;
    prev.style.display = 'block';
  }
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