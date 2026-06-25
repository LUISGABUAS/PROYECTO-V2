<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

include('../app/controllers/almacen/list_almacen.php');
include('../app/controllers/clientes/list_clientes.php');
include('../app/controllers/vendedores/list_vendedores.php');

if(in_array(21, $_SESSION['permisos'])):
?>

<?php if (isset($_SESSION['mensaje'])): ?>
<script>
let mensaje = <?= json_encode($_SESSION['mensaje']) ?>;
let icono = mensaje.includes('❌') ? 'error' : 'success';

Swal.fire({
    icon: icono,
    title: icono === 'error' ? 'Atención' : '¡Éxito!',
    text: mensaje,
    confirmButtonText: 'Entendido'
});
</script>
<?php unset($_SESSION['mensaje']); endif; ?>

<div class="content-wrapper">

  <div class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1 class="m-0">Nueva Venta</h1>
      <a href="pedido_multiple.php" class="btn btn-outline-primary btn-sm">
        <i class="fa fa-layer-group"></i> Pedido con múltiples envíos
      </a>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="row">
        <div class="col-md-12">

          <div class="card card-success">

            <div class="card-header">
              <h3 class="card-title">
                <i class="fa fa-shopping-cart"></i> Registro de venta
              </h3>
            </div>

            <?php include_once('../app/controllers/helpers/csrf.php'); ?>
            <form action="../app/controllers/ventas/create.php" method="POST" enctype="multipart/form-data" id="form_venta">
              <?= csrf_field() ?>

              <div class="card-body">

                <!-- DATOS PRINCIPALES -->
                <div class="row mb-4">

                  <!-- FECHA -->
                  <div class="col-md-2">
                    <div class="form-group">
                      <label><strong>Fecha</strong></label>
                      <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                  </div>

                  <!-- CLIENTES -->
                  <div class="col-md-4">
                    <div class="form-group">
                      <label><strong>Cliente</strong></label>
                      <select name="cliente" id="select_cliente" class="form-control select2-cliente" required>
                        <option value="">Buscar cliente por nombre o teléfono...</option>
                        <?php foreach($clientes as $c):
                          $telefono = preg_replace('/[^0-9]/', '', $c['telefono']); ?>
                          <option value="<?= $c['id_cliente'] ?>"
                                  data-envio="<?= htmlspecialchars($c['tipo_cliente']) ?>"
                                  data-telefono="<?= $telefono ?>">
                            <?= htmlspecialchars($c['nombre_completo']) ?> | <?= $telefono ?> | <?= ucfirst($c['tipo_cliente']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <!-- ENVÍO -->
                  <div class="col-md-2">
                    <div class="form-group">
                      <label><strong>Tipo de envío</strong></label>
                      <input type="text" id="tipo_envio_display" class="form-control" readonly>
                      <input type="hidden" name="envio" id="tipo_envio" required>
                    </div>
                  </div>

                  <!-- TIPO DE PAGO -->
                  <div class="col-md-3" id="col_tipo_pago" style="display:none;">
                    <div class="form-group">
                      <label><strong>Tipo de pago</strong></label>
                      <input type="hidden" name="tipo_pago" id="tipo_pago" value="">
                      <div class="row" style="margin:0; gap:4px;">
                        <button type="button" id="btn_efectivo"
                                onclick="seleccionarPago('efectivo')"
                                class="btn btn-outline-success py-2"
                                style="border-radius:10px; font-size:12px; flex:1;">
                          💵<br><small>Efectivo</small>
                        </button>
                        <button type="button" id="btn_comprobante"
                                onclick="seleccionarPago('comprobante')"
                                class="btn btn-outline-primary py-2"
                                style="border-radius:10px; font-size:12px; flex:1;">
                          🧾<br><small>Comprobante</small>
                        </button>
                        <button type="button" id="btn_ambos"
                                onclick="seleccionarPago('ambos')"
                                class="btn btn-outline-warning py-2"
                                style="border-radius:10px; font-size:12px; flex:1;">
                          💵🧾<br><small>Ambos</small>
                        </button>
                        <button type="button" id="btn_contra_entrega"
                                onclick="seleccionarPago('contra_entrega')"
                                class="btn btn-outline-danger py-2"
                                style="border-radius:10px; font-size:12px; flex:1;">
                          🚚<br><small>C. Entrega</small>
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- VENDEDOR -->
                  <div class="col-md-2">
                    <div class="form-group">
                      <label><strong>Vendedor</strong></label>
                      <select name="id_usuario" class="form-control" required>
                        <option value="">Seleccione vendedor</option>
                        <?php foreach ($vendedores as $v): ?>
                          <option value="<?= $v['id_usuario'] ?>">
                            <?= $v['nombres'] ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                </div>

                <!-- SELECTOR DE DIRECCIÓN DE ENTREGA (solo si cliente tiene +1 dirección) -->
                <div class="row" id="fila_direccion_entrega" style="display:none;">
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label><strong><i class="fas fa-map-marker-alt text-danger"></i> Dirección de entrega</strong></label>
                      <select name="id_direccion_entrega" id="select_direccion" class="form-control"></select>
                    </div>
                  </div>
                </div>

                <!-- DIRECCIÓN DEL CLIENTE -->
                <div class="row" id="fila_dir_cliente" style="display:none;">
                  <div class="col-md-12">
                    <div class="alert alert-secondary py-2 mb-0" style="font-size:.9rem;">
                      <i class="fas fa-map-marker-alt text-danger"></i>
                      <strong>Dirección:</strong> <span id="dir_cliente_texto"></span>
                    </div>
                  </div>
                </div>

                <hr class="my-3">

                <!-- CONTRA ENTREGA: MONTO PENDIENTE -->
                <div class="row mb-3" id="fila_contra_entrega" style="display:none;">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><strong><i class="fas fa-clock text-danger"></i> Monto a cobrar en entrega</strong></label>
                      <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                        <input type="number" name="monto_pendiente" id="monto_pendiente"
                               class="form-control" step="0.01" min="0.01" placeholder="0.00">
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><strong>Cobrar mediante</strong></label>
                      <input type="hidden" name="metodo_pendiente" id="metodo_pendiente" value="">
                      <div class="d-flex gap-2">
                        <button type="button" id="btn_mp_efectivo"
                                onclick="seleccionarMetodoPendiente('efectivo')"
                                class="btn btn-outline-success flex-fill py-2"
                                style="border-radius:10px; font-size:13px;">
                          💵<br><small>Efectivo</small>
                        </button>
                        <button type="button" id="btn_mp_comprobante"
                                onclick="seleccionarMetodoPendiente('comprobante')"
                                class="btn btn-outline-primary flex-fill py-2"
                                style="border-radius:10px; font-size:13px;">
                          🧾<br><small>Comprobante</small>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- COMPROBANTE(S) -->
                <div class="row mb-4" id="fila_comprobante" style="display:none;">
                  <div class="col-md-12">
                    <label class="mb-1" id="label_comprobante"><strong><i class="fa fa-file-pdf text-danger"></i> Comprobante(s) <span class="text-danger" id="asterisco_comprobante">*</span></strong></label>
                    <div id="slots_comprobantes" class="row" style="margin:0;"></div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-3" id="btn_agregar_comprobante" onclick="agregarSlotComprobante()">
                      <i class="fa fa-plus"></i> Agregar otro comprobante
                    </button>
                  </div>
                </div>

                <hr class="my-3">

                <!-- ARTICULOS -->
                <h5 class="mb-3"><i class="fa fa-box text-primary"></i> Artículos</h5>

                <div class="table-responsive">
                  <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                      <tr class="text-center">
                        <th>Producto</th>
                        <th width="100">Cantidad</th>
                        <th width="120">Precio $</th>
                        <th width="120">Subtotal $</th>
                        <th width="60">Acción</th>
                      </tr>
                    </thead>
                    <tbody id="detalle_venta">
                      <tr>
                        <td>
                          <select name="productos[]" class="form-control form-control-sm producto select2-producto" required onchange="asignarPrecio(this)">
                            <option value="">Seleccione</option>
                            <?php foreach($datos_productos as $p): ?>
                              <option value="<?= $p['id_producto'] ?>"
                                      data-precio="<?= $p['precio_venta'] ?>"
                                      data-stock="<?= $p['stock_disponible'] ?>">
                                <?= $p['codigo'] ?> - <?= $p['nombre'] ?> (<?= $p['proveedor'] ?>) (<?= $p['stock_disponible'] ?> disp.)
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </td>
                        <td>
                          <input type="number" name="cantidades[]" class="form-control form-control-sm text-center cantidad" min="1" value="1" oninput="calcularFila(this)" required>
                        </td>
                        <td>
                          <input type="number" name="precios[]" class="form-control form-control-sm text-center precio" step="0.01" readonly>
                        </td>
                        <td>
                          <input type="number" class="form-control form-control-sm text-center subtotal" step="0.01" readonly>
                        </td>
                        <td class="text-center">
                          <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this)">
                            <i class="fa fa-trash"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="agregarFila()">
                  <i class="fa fa-plus"></i> Agregar artículo
                </button>

                <hr class="my-3">

                <!-- TOTAL -->
                <div class="row justify-content-end">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label><strong>Total $</strong></label>
                      <input type="number" name="total" id="total_venta" class="form-control form-control-lg text-center font-weight-bold text-success" readonly>
                    </div>
                  </div>
                </div>

                <!-- NOTAS -->
                <div class="row">
                  <div class="col-md-12">
                    <div class="form-group">
                      <label><strong><i class="fas fa-sticky-note text-warning"></i> Notas / Observaciones</strong></label>
                      <textarea name="notas" class="form-control" rows="2"
                                placeholder="Notas adicionales sobre la venta (opcional)..."></textarea>
                    </div>
                  </div>
                </div>

              </div>

              <div class="card-footer">
                <a href="index.php" class="btn btn-outline-danger">
                  <i class="fa fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-success float-right">
                  <i class="fa fa-save"></i> Guardar venta
                </button>
              </div>

            </form>

          </div>

        </div>
      </div>

    </div>
  </div>
</div>

<!-- ========================================= -->
<!-- SCRIPTS JAVASCRIPT -->
<!-- ========================================= -->

<script>
// ============ GESTIÓN DE FILAS DE PRODUCTOS ============
function agregarFila(){
  let fila = `
  <tr>
    <td>
      <select name="productos[]"
              class="form-control form-control-sm producto select2-producto"
              required
              onchange="asignarPrecio(this)">
        <option value="">Seleccione</option>
        <?php foreach($datos_productos as $p){ ?>
          <option value="<?= $p['id_producto'] ?>"
                  data-precio="<?= $p['precio_venta'] ?>"
                  data-stock="<?= $p['stock_disponible'] ?>">
            <?= $p['codigo'] ?> - <?= $p['nombre'] ?> (<?= $p['proveedor'] ?>) (<?= $p['stock_disponible'] ?> disp.)
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
      <button type="button" class="btn btn-danger btn-sm"
              onclick="eliminarFila(this)">
        <i class="fa fa-trash"></i>
      </button>
    </td>
  </tr>`;

  document.getElementById('detalle_venta').insertAdjacentHTML('beforeend', fila);
  // Inicializar Select2 en el nuevo select
  const nuevaFila = document.querySelector('#detalle_venta tr:last-child');
  $(nuevaFila.querySelector('.select2-producto')).select2({
    theme: 'bootstrap4',
    placeholder: 'Buscar por nombre, código o proveedor...',
    width: '100%'
  }).on('change', function(){ asignarPrecio(this); });
}

function asignarPrecio(select){
  const producto = select.value;
  const selects  = document.querySelectorAll('.producto');

  let repetidos = 0;
  selects.forEach(s => { if(s.value === producto) repetidos++; });

  if(repetidos > 1){
    Swal.fire({ icon:'warning', title:'Producto duplicado', text:'Este producto ya fue agregado' });
    select.value = '';
    return;
  }

  const opt    = select.options[select.selectedIndex];
  const precio = opt.dataset.precio || 0;
  const stock  = parseInt(opt.dataset.stock ?? 0);
  const fila   = select.closest('tr');

  // ✅ Bloquear si no hay stock
  if(stock <= 0){
    Swal.fire({
      icon: 'warning',
      title: 'Sin stock',
      text: 'Este producto no tiene unidades disponibles en bodega'
    });
    select.value = '';
    return;
  }

  fila.querySelector('.precio').value    = precio;
  fila.querySelector('.cantidad').max    = stock;
  calcularFila(select);
}

function calcularFila(elemento){
  const fila          = elemento.closest('tr');
  const inputCantidad = fila.querySelector('.cantidad');
  const precio        = parseFloat(fila.querySelector('.precio').value || 0);
  const maxStock      = parseInt(inputCantidad.max || 0);
  let   cantidad      = parseFloat(inputCantidad.value || 0);

  // ✅ Corregir si supera el stock
  if(maxStock > 0 && cantidad > maxStock){
    inputCantidad.value = maxStock;
    cantidad = maxStock;
    Swal.fire({
      icon: 'warning',
      title: 'Stock insuficiente',
      text: `Solo hay ${maxStock} unidad(es) disponibles en bodega`
    });
  }

  fila.querySelector('.subtotal').value = (cantidad * precio).toFixed(2);
  calcularTotal();
}

function calcularTotal(){
  let total = 0;
  document.querySelectorAll('.subtotal').forEach(sub => {
    total += parseFloat(sub.value || 0);
  });
  document.getElementById('total_venta').value = total.toFixed(2);
}

function eliminarFila(btn){
  const filas = document.querySelectorAll('#detalle_venta tr');
  if(filas.length === 1){ Swal.fire('Debe existir al menos un producto'); return; }
  btn.closest('tr').remove();
  calcularTotal();
}
</script>

<script>
// ============ TIPO DE PAGO Y COMPROBANTE ============

const _btnsPago = {
  efectivo:       { id: 'btn_efectivo',       base: 'btn-outline-success', active: 'btn-success'   },
  comprobante:    { id: 'btn_comprobante',     base: 'btn-outline-primary', active: 'btn-primary'   },
  ambos:          { id: 'btn_ambos',           base: 'btn-outline-warning', active: 'btn-warning'   },
  contra_entrega: { id: 'btn_contra_entrega',  base: 'btn-outline-danger',  active: 'btn-danger'    },
};

function seleccionarPago(tipo) {
  const envioActual = document.getElementById('tipo_envio')?.value || '';
  if (envioActual === 'foraneo' && tipo !== 'comprobante') return;

  document.getElementById('tipo_pago').value = tipo;

  Object.entries(_btnsPago).forEach(([t, cfg]) => {
    const btn = document.getElementById(cfg.id);
    if (!btn) return;
    const isActive = t === tipo;
    btn.className = btn.className
      .replace(cfg.base, '').replace(cfg.active, '').trim();
    btn.className += ' ' + (isActive ? cfg.active : cfg.base);
  });

  const filaComprobante   = document.getElementById('fila_comprobante');
  const filaContraEntrega = document.getElementById('fila_contra_entrega');
  const labelComp         = document.getElementById('label_comprobante');
  const asterisco         = document.getElementById('asterisco_comprobante');
  const inputPendiente    = document.getElementById('monto_pendiente');

  // Reset
  filaContraEntrega.style.display = 'none';
  inputPendiente.required = false;

  if (tipo === 'efectivo') {
    filaComprobante.style.display = 'none';
    document.querySelectorAll('[name="comprobantes[]"]').forEach(fi => fi.value = '');
  } else if (tipo === 'comprobante' || tipo === 'ambos') {
    filaComprobante.style.display = 'block';
    labelComp.innerHTML = '<strong><i class="fa fa-file-pdf text-danger"></i> Comprobante(s) <span class="text-danger">*</span></strong>';
  } else if (tipo === 'contra_entrega') {
    filaContraEntrega.style.display = 'flex';
    filaComprobante.style.display   = 'block';
    labelComp.innerHTML = '<strong><i class="fa fa-file-pdf text-secondary"></i> Comprobante de anticipo <small class="text-muted">(opcional)</small></strong>';
    inputPendiente.required = true;
  }
}

function seleccionarMetodoPendiente(metodo) {
  document.getElementById('metodo_pendiente').value = metodo;
  const btnEf = document.getElementById('btn_mp_efectivo');
  const btnCo = document.getElementById('btn_mp_comprobante');
  if (metodo === 'efectivo') {
    btnEf.className = btnEf.className.replace('btn-outline-success', 'btn-success');
    btnCo.className = btnCo.className.replace('btn-primary', 'btn-outline-primary');
  } else {
    btnCo.className = btnCo.className.replace('btn-outline-primary', 'btn-primary');
    btnEf.className = btnEf.className.replace('btn-success', 'btn-outline-success');
  }
}
</script>

<script>
// ============ MULTI-COMPROBANTE: SLOTS DINÁMICOS ============

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

  const dz   = document.getElementById('cdz_' + idx);
  const fi   = document.getElementById('cfile_' + idx);

  dz.addEventListener('dragover',  e => { e.preventDefault(); dz.style.background = '#e8f4ff'; dz.style.borderColor = '#007bff'; });
  dz.addEventListener('dragleave', ()=> { dz.style.background = '#f9f9f9';  dz.style.borderColor = '#aaa'; });
  dz.addEventListener('drop',      e => {
    e.preventDefault();
    dz.style.background = '#f9f9f9'; dz.style.borderColor = '#aaa';
    if (e.dataTransfer.files[0]) _procesarArchivoSlot(e.dataTransfer.files[0], idx);
  });
  fi.addEventListener('change', function() {
    if (this.files[0]) _procesarArchivoSlot(this.files[0], idx);
  });
}

function eliminarSlot(slotId) {
  const slots = document.querySelectorAll('.comprobante-slot');
  if (slots.length === 1) {
    Swal.fire({ icon: 'warning', title: 'Atención', text: 'Debe haber al menos un comprobante' });
    return;
  }
  document.getElementById(slotId).remove();
  _renumerarSlots();
}

function _renumerarSlots() {
  document.querySelectorAll('.comprobante-slot .numero-slot').forEach((el, i) => {
    el.textContent = 'Comprobante #' + (i + 1);
  });
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
  const dz      = document.getElementById('cdz_'   + idx);
  const fi      = document.getElementById('cfile_' + idx);
  const prev    = document.getElementById('cprev_' + idx);
  const cont    = document.getElementById('cpcont_'+ idx);
  const err     = document.getElementById('cerr_'  + idx);
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

// Inicializar con un slot al cargar
document.addEventListener('DOMContentLoaded', () => agregarSlotComprobante());
</script>

<script>
// ============ VALIDACIÓN FINAL ANTES DE ENVIAR ============
document.getElementById('form_venta').addEventListener('submit', function(e) {

  // ✅ Validar tipo de pago seleccionado
  const tipoPagoVal = document.getElementById('tipo_pago')?.value || '';
  if (!tipoPagoVal) {
    e.preventDefault();
    Swal.fire({ icon: 'error', title: 'Tipo de pago', text: 'Debe seleccionar un tipo de pago' });
    return false;
  }

  // ✅ Validar comprobante (obligatorio para comprobante y ambos, opcional para contra_entrega)
  if (tipoPagoVal === 'comprobante' || tipoPagoVal === 'ambos') {
    const inputs    = document.querySelectorAll('[name="comprobantes[]"]');
    const hayAlguno = Array.from(inputs).some(fi => fi.files.length > 0);
    if (!hayAlguno) {
      e.preventDefault();
      Swal.fire({ icon: 'error', title: 'Falta comprobante', text: 'Debe adjuntar al menos un comprobante' });
      return false;
    }
  }

  // ✅ Validar contra entrega: monto y método
  if (tipoPagoVal === 'contra_entrega') {
    const monto   = parseFloat(document.getElementById('monto_pendiente')?.value || 0);
    const metodo  = document.getElementById('metodo_pendiente')?.value || '';
    if (!monto || monto <= 0) {
      e.preventDefault();
      Swal.fire({ icon: 'error', title: 'Contra entrega', text: 'Debe indicar el monto pendiente a cobrar en entrega' });
      return false;
    }
    if (!metodo) {
      e.preventDefault();
      Swal.fire({ icon: 'error', title: 'Contra entrega', text: 'Debe seleccionar el método de cobro en entrega (Efectivo o Comprobante)' });
      return false;
    }
  }

  // ✅ Validar que haya productos seleccionados
  const productos = document.querySelectorAll('.producto');
  let hayProducto  = false;
  productos.forEach(p => { if (p.value) hayProducto = true; });

  if (!hayProducto) {
    e.preventDefault();
    Swal.fire({ icon: 'error', title: 'Sin productos', text: 'Debe agregar al menos un producto a la venta' });
    return false;
  }

  // ✅ Validar stock de cada producto
  let stockOk      = true;
  let mensajeStock = '';

  document.querySelectorAll('#detalle_venta tr').forEach(fila => {
    const selectProd = fila.querySelector('.producto');
    const inputCant  = fila.querySelector('.cantidad');
    if(!selectProd || !selectProd.value) return;

    const opt      = selectProd.options[selectProd.selectedIndex];
    const stock    = parseInt(opt.dataset.stock ?? 0);
    const cantidad = parseInt(inputCant.value || 0);
    const nombre   = opt.text.split('(')[0].trim();

    if(cantidad > stock){
      stockOk      = false;
      mensajeStock = `"${nombre}" solo tiene ${stock} unidad(es) disponibles y se pidieron ${cantidad}`;
    }
  });

  if(!stockOk){
    e.preventDefault();
    Swal.fire({ icon: 'error', title: 'Stock insuficiente', text: mensajeStock });
    return false;
  }

  // ✅ Validar que el total sea mayor a 0
  const total = parseFloat(document.getElementById('total_venta').value || 0);
  if (total <= 0) {
    e.preventDefault();
    Swal.fire({ icon: 'error', title: 'Total inválido', text: 'El total de la venta debe ser mayor a $0' });
    return false;
  }

  // ✅ Todo correcto - mostrar loading
  Swal.fire({
    title: 'Guardando venta...',
    text: 'Por favor espere',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });
});
</script>

<?php else: include('../layout/parte2.php'); ?>
<script>
Swal.fire({
  icon: "error",
  title: "Access Denied",
  text: "No tienes permisos para acceder.",
  timer: 3000,
  showConfirmButton: false
}).then(() => { window.location = "<?= $URL ?>"; });
</script>
<?php endif; ?>

<link rel="stylesheet" href="<?= $URL ?>/public/templates/AdminLTE-3.2.0/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="<?= $URL ?>/public/templates/AdminLTE-3.2.0/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<script src="<?= $URL ?>/public/templates/AdminLTE-3.2.0/plugins/select2/js/select2.full.min.js"></script>
<script>
$(document).ready(function(){
  $('.select2-producto').select2({
    theme: 'bootstrap4',
    placeholder: 'Buscar por nombre, código o proveedor...',
    width: '100%'
  }).on('change', function(){ asignarPrecio(this); });

  $('#select_cliente').select2({
    theme: 'bootstrap4',
    placeholder: 'Buscar cliente por nombre o teléfono...',
    allowClear: true,
    width: '100%'
  }).on('change', function(){
    const opt = this.options[this.selectedIndex];
    const envio = opt?.dataset?.envio || '';
    const tipoEnvioHidden  = document.getElementById('tipo_envio');
    const displayField     = document.getElementById('tipo_envio_display');
    const colTipoPago      = document.getElementById('col_tipo_pago');
    const filaComprobante  = document.getElementById('fila_comprobante');

    tipoEnvioHidden.value = envio;

    if(envio === 'local'){
      displayField.value     = 'Local';
      displayField.className = 'form-control bg-light';
      colTipoPago.style.display = 'block';
      seleccionarPago('efectivo');
    } else if(envio === 'foraneo'){
      displayField.value     = 'Foráneo';
      displayField.className = 'form-control bg-light';
      colTipoPago.style.display = 'none';
      seleccionarPago('comprobante');
    }

    cargarDireccionesCliente(this.value);
  });

  let _dirData = [];

  function mostrarDireccion(dir) {
    const fila = document.getElementById('fila_dir_cliente');
    const txt  = document.getElementById('dir_cliente_texto');
    if (!dir) { fila.style.display = 'none'; return; }
    const nombre = (!dir.es_principal && dir.nombre_destinatario) ? ` — <strong>${dir.nombre_destinatario}</strong>` : '';
    txt.innerHTML = `${dir.calle_numero}, ${dir.colonia}, ${dir.municipio}, ${dir.estado} CP ${dir.cp}${nombre}`;
    fila.style.display = 'block';
  }

  document.getElementById('select_direccion').addEventListener('change', function() {
    const id  = parseInt(this.value);
    const dir = _dirData.find(d => d.id == id);
    mostrarDireccion(dir || null);
  });

  function cargarDireccionesCliente(idCliente) {
    const filaDir = document.getElementById('fila_direccion_entrega');
    const selDir  = document.getElementById('select_direccion');
    _dirData = [];

    if (!idCliente) {
      filaDir.style.display = 'none';
      selDir.innerHTML = '';
      mostrarDireccion(null);
      return;
    }

    fetch(`<?= $URL ?>/app/controllers/clientes/direcciones.php?accion=listar&id_cliente=${idCliente}`, {
      credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
      if (!data.success || !data.data.length) {
        filaDir.style.display = 'none';
        selDir.innerHTML = '';
        mostrarDireccion(null);
        return;
      }

      _dirData = data.data;
      const principal = data.data.find(d => d.es_principal == 1) || data.data[0];
      mostrarDireccion(principal);

      if (data.data.length <= 1) {
        filaDir.style.display = 'none';
        selDir.innerHTML = `<option value="${data.data[0].id}" selected></option>`;
        return;
      }

      selDir.innerHTML = data.data.map(dir => {
        const label = dir.es_principal == 1
          ? `★ ${dir.calle_numero} — ${dir.colonia}, ${dir.municipio}`
          : `${dir.nombre_destinatario ? dir.nombre_destinatario + ' · ' : ''}${dir.calle_numero} — ${dir.colonia}, ${dir.municipio}`;
        return `<option value="${dir.id}" ${dir.es_principal == 1 ? 'selected' : ''}>${label}</option>`;
      }).join('');
      filaDir.style.display = 'block';
    })
    .catch(() => {
      filaDir.style.display = 'none';
      mostrarDireccion(null);
    });
  }
});
</script>

<?php include('../layout/parte2.php'); ?>