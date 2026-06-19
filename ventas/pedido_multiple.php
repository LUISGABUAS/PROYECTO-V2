<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');
include('../app/controllers/almacen/list_almacen.php');
include('../app/controllers/clientes/list_clientes.php');
include('../app/controllers/vendedores/list_vendedores.php');

if (!in_array(21, $_SESSION['permisos'])):
    include('../layout/parte2.php'); exit;
endif;
?>

<?php if (isset($_SESSION['mensaje'])): ?>
<script>
Swal.fire({
    icon: <?= json_encode($_SESSION['mensaje'][0] === '✅' ? 'success' : 'error') ?>,
    title: 'Atención',
    text: <?= json_encode($_SESSION['mensaje']) ?>,
    confirmButtonText: 'OK'
});
</script>
<?php unset($_SESSION['mensaje']); endif; ?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0"><i class="fa fa-layer-group text-primary"></i> Pedido con Múltiples Envíos</h1>
      <small class="text-muted">Un solo comprobante cubre todos los envíos. Cada envío va a un destinatario y dirección diferente.</small>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <?php include_once('../app/controllers/helpers/csrf.php'); ?>
      <form id="form_pedido" action="../app/controllers/ventas/create_pedido_multiple.php"
            method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="envios_json" id="envios_json">

        <div class="card card-primary">
          <div class="card-header"><h3 class="card-title"><i class="fa fa-info-circle"></i> Datos generales</h3></div>
          <div class="card-body">
            <div class="row">

              <div class="col-md-2">
                <div class="form-group">
                  <label><strong>Fecha</strong></label>
                  <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <label><strong>Cliente</strong></label>
                  <select name="id_cliente" id="sel_cliente" class="form-control" required>
                    <option value="">Buscar cliente...</option>
                    <?php foreach($clientes as $c):
                      $tel = preg_replace('/[^0-9]/', '', $c['telefono']); ?>
                      <option value="<?= $c['id_cliente'] ?>" data-tel="<?= $tel ?>">
                        <?= htmlspecialchars($c['nombre_completo']) ?> | <?= $tel ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label><strong>Vendedor</strong></label>
                  <select name="id_usuario" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($vendedores as $v): ?>
                      <option value="<?= $v['id_usuario'] ?>"><?= htmlspecialchars($v['nombres']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="col-md-3">
                <div class="form-group">
                  <label><strong>Comprobante de pago <span class="text-danger">*</span></strong></label>
                  <div id="zona_comprobante"
                       style="border:2px dashed #aaa;border-radius:8px;padding:18px;text-align:center;cursor:pointer;background:#f9f9f9;"
                       onclick="document.getElementById('file_comprobante').click()">
                    <i class="fa fa-cloud-upload-alt fa-lg text-muted"></i>
                    <p class="mb-0 small text-muted mt-1">Arrastra o <strong>haz clic</strong><br>
                    <small>PDF, JPG, PNG | 5MB</small></p>
                  </div>
                  <input type="file" name="comprobante" id="file_comprobante"
                         accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="display:none;">
                  <div id="prev_comprobante" class="mt-2" style="display:none;"></div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- ENVÍOS DINÁMICOS -->
        <div id="contenedor_envios"></div>

        <div class="mb-3">
          <button type="button" class="btn btn-outline-primary" onclick="agregarEnvio()">
            <i class="fa fa-plus"></i> Agregar envío
          </button>
        </div>

        <!-- TOTAL GENERAL -->
        <div class="card">
          <div class="card-body">
            <div class="row justify-content-end">
              <div class="col-md-3">
                <label><strong>Total General $</strong></label>
                <input type="text" id="total_general" class="form-control form-control-lg text-center font-weight-bold text-success" readonly>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <a href="index.php" class="btn btn-outline-danger"><i class="fa fa-times"></i> Cancelar</a>
            <button type="submit" class="btn btn-success float-right">
              <i class="fa fa-save"></i> Guardar Pedido
            </button>
          </div>
        </div>

      </form>
    </div>
  </div>
</div>

<!-- TEMPLATE DE PRODUCTO (oculto) -->
<template id="tpl_producto">
  <tr class="fila-prod">
    <td>
      <select name="_prod_" class="form-control form-control-sm sel-producto" required>
        <option value="">Seleccione...</option>
        <?php foreach($datos_productos as $p): ?>
          <option value="<?= $p['id_producto'] ?>"
                  data-precio="<?= $p['precio_venta'] ?>"
                  data-stock="<?= $p['stock_disponible'] ?>">
            <?= htmlspecialchars($p['codigo'] . ' - ' . $p['nombre']) ?> (<?= $p['stock_disponible'] ?> disp.)
          </option>
        <?php endforeach; ?>
      </select>
    </td>
    <td><input type="number" class="form-control form-control-sm inp-cantidad" min="1" value="1"></td>
    <td><input type="number" class="form-control form-control-sm inp-precio" step="0.01" readonly></td>
    <td><input type="number" class="form-control form-control-sm inp-subtotal" readonly></td>
    <td class="text-center">
      <button type="button" class="btn btn-sm btn-danger btn-quitar-prod"><i class="fa fa-trash"></i></button>
    </td>
  </tr>
</template>

<link rel="stylesheet" href="<?= $URL ?>/public/templates/AdminLTE-3.2.0/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="<?= $URL ?>/public/templates/AdminLTE-3.2.0/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<script src="<?= $URL ?>/public/templates/AdminLTE-3.2.0/plugins/select2/js/select2.full.min.js"></script>

<script>
const URL_APP     = '<?= $URL ?>';
const PRODUCTOS   = <?= json_encode(array_map(fn($p) => [
    'id'     => $p['id_producto'],
    'nombre' => $p['codigo'] . ' - ' . $p['nombre'],
    'precio' => $p['precio_venta'],
    'stock'  => $p['stock_disponible'],
], $datos_productos)) ?>;

let _envioIdx   = 0;
let _dirsByCliente = [];

// ── Comprobante ──────────────────────────────────────────────────────────────
const zonaComp = document.getElementById('zona_comprobante');
const fileComp = document.getElementById('file_comprobante');
zonaComp.addEventListener('dragover',  e => { e.preventDefault(); zonaComp.style.background='#e8f4ff'; });
zonaComp.addEventListener('dragleave', ()=> zonaComp.style.background='#f9f9f9');
zonaComp.addEventListener('drop', e => {
  e.preventDefault(); zonaComp.style.background='#f9f9f9';
  if (e.dataTransfer.files[0]) mostrarComprobante(e.dataTransfer.files[0]);
});
fileComp.addEventListener('change', function(){ if(this.files[0]) mostrarComprobante(this.files[0]); });

function mostrarComprobante(file) {
  const dt = new DataTransfer(); dt.items.add(file); fileComp.files = dt.files;
  zonaComp.innerHTML = `<i class="fa fa-check-circle text-success"></i>
    <p class="mb-0 small text-success font-weight-bold mt-1">${file.name}</p>
    <small class="text-muted">${(file.size/1024).toFixed(1)} KB</small>`;
}

// ── Cargar direcciones del cliente ───────────────────────────────────────────
$('#sel_cliente').select2({ theme:'bootstrap4', placeholder:'Buscar cliente...', width:'100%' })
  .on('change', function(){
    _dirsByCliente = [];
    const id = this.value;
    if (!id) return;
    fetch(`${URL_APP}/app/controllers/clientes/direcciones.php?accion=listar&id_cliente=${id}`, { credentials:'same-origin' })
      .then(r=>r.json())
      .then(data=>{
        if (data.success) {
          _dirsByCliente = data.data;
          document.querySelectorAll('.sel-direccion').forEach(sel => rellenarDirecciones(sel));
        }
      });
  });

function rellenarDirecciones(sel) {
  const valorActual = sel.value;
  sel.innerHTML = '<option value="">— Selecciona dirección —</option>';
  _dirsByCliente.forEach(d => {
    const opt = document.createElement('option');
    opt.value = d.id;
    const etiqueta = d.es_principal == 1
      ? `★ ${d.calle_numero} — ${d.colonia}, ${d.municipio}`
      : `${d.nombre_destinatario ? d.nombre_destinatario+' · ' : ''}${d.calle_numero} — ${d.colonia}, ${d.municipio}`;
    opt.textContent = etiqueta;
    opt.dataset.nombre = d.nombre_destinatario || '';
    opt.dataset.calle  = d.calle_numero;
    opt.dataset.colonia= d.colonia;
    opt.dataset.municipio = d.municipio;
    opt.dataset.estado = d.estado;
    opt.dataset.cp     = d.cp;
    opt.dataset.principal = d.es_principal;
    sel.appendChild(opt);
  });
  if (valorActual) sel.value = valorActual;
  actualizarDestinatario(sel);
}

function actualizarDestinatario(sel) {
  const opt      = sel.options[sel.selectedIndex];
  const bloque   = sel.closest('.envio-block');
  if (!bloque) return;
  const spanDest = bloque.querySelector('.span-destinatario');
  const spanDir  = bloque.querySelector('.span-dir');
  if (!opt || !opt.value) {
    spanDest.textContent = '';
    spanDir.textContent  = '';
    return;
  }
  const esPrincipal = opt.dataset.principal == 1;
  spanDest.textContent = esPrincipal
    ? 'Dirección principal del cliente'
    : (opt.dataset.nombre || 'Sin nombre de destinatario');
  spanDir.textContent  = `${opt.dataset.calle}, ${opt.dataset.colonia}, ${opt.dataset.municipio}, ${opt.dataset.estado} CP ${opt.dataset.cp}`;
}

// ── Agregar envío ─────────────────────────────────────────────────────────────
function agregarEnvio() {
  const idx = _envioIdx++;
  const div = document.createElement('div');
  div.className = 'card card-outline card-success mb-3 envio-block';
  div.dataset.idx = idx;
  div.innerHTML = `
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0"><i class="fa fa-shipping-fast text-success"></i> Envío #${idx+1}</h3>
      <button type="button" class="btn btn-sm btn-outline-danger" onclick="quitarEnvio(this)">
        <i class="fa fa-times"></i> Quitar
      </button>
    </div>
    <div class="card-body">
      <div class="row mb-3">
        <div class="col-md-6">
          <label><strong>Dirección de entrega</strong></label>
          <select class="form-control sel-direccion" onchange="actualizarDestinatario(this)">
            <option value="">— Primero selecciona el cliente —</option>
          </select>
        </div>
        <div class="col-md-6">
          <label><strong>Destinatario</strong></label>
          <div class="form-control bg-light" style="height:auto;min-height:38px;">
            <strong class="span-destinatario text-primary"></strong>
            <div class="span-dir text-muted" style="font-size:.85rem;"></div>
          </div>
        </div>
      </div>

      <table class="table table-bordered table-sm">
        <thead class="thead-light">
          <tr class="text-center">
            <th>Producto</th><th width="90">Cantidad</th>
            <th width="110">Precio $</th><th width="110">Subtotal $</th><th width="50"></th>
          </tr>
        </thead>
        <tbody class="tbody-productos"></tbody>
      </table>
      <button type="button" class="btn btn-sm btn-outline-secondary" onclick="agregarProducto(this)">
        <i class="fa fa-plus"></i> Agregar producto
      </button>
      <div class="text-right mt-2">
        <strong>Subtotal envío: $<span class="subtotal-envio">0.00</span></strong>
      </div>
    </div>`;

  document.getElementById('contenedor_envios').appendChild(div);

  // Rellenar direcciones si ya hay cliente
  const sel = div.querySelector('.sel-direccion');
  if (_dirsByCliente.length) rellenarDirecciones(sel);

  // Agregar primera fila de producto
  agregarProducto(div.querySelector('.btn-outline-secondary'));
}

// ── Agregar producto a un envío ───────────────────────────────────────────────
function agregarProducto(btn) {
  const bloque = btn.closest('.envio-block');
  const tbody  = bloque.querySelector('.tbody-productos');
  const tpl    = document.getElementById('tpl_producto').content.cloneNode(true);
  const fila   = tpl.querySelector('tr');

  const sel = fila.querySelector('.sel-producto');
  sel.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    fila.querySelector('.inp-precio').value = opt?.dataset?.precio || '';
    calcularFila(fila);
  });

  fila.querySelector('.inp-cantidad').addEventListener('input', () => calcularFila(fila));
  fila.querySelector('.btn-quitar-prod').addEventListener('click', () => {
    fila.remove();
    calcularTotales();
  });

  // Inicializar Select2
  tbody.appendChild(fila);
  $(fila.querySelector('.sel-producto')).select2({
    theme:'bootstrap4', placeholder:'Buscar producto...', width:'100%'
  }).on('change', function(){
    const opt = this.options[this.selectedIndex];
    fila.querySelector('.inp-precio').value = opt?.dataset?.precio || '';
    calcularFila(fila);
  });
}

function calcularFila(fila) {
  const qty   = parseFloat(fila.querySelector('.inp-cantidad').value || 0);
  const price = parseFloat(fila.querySelector('.inp-precio').value || 0);
  fila.querySelector('.inp-subtotal').value = (qty * price).toFixed(2);
  calcularTotales();
}

function calcularTotales() {
  let total = 0;
  document.querySelectorAll('.envio-block').forEach(bloque => {
    let sub = 0;
    bloque.querySelectorAll('.inp-subtotal').forEach(el => sub += parseFloat(el.value || 0));
    bloque.querySelector('.subtotal-envio').textContent = sub.toFixed(2);
    total += sub;
  });
  document.getElementById('total_general').value = '$' + total.toFixed(2);
}

function quitarEnvio(btn) {
  const bloque = btn.closest('.envio-block');
  if (document.querySelectorAll('.envio-block').length <= 1) {
    Swal.fire('Atención','Debe haber al menos un envío','warning'); return;
  }
  bloque.remove();
  renumerarEnvios();
  calcularTotales();
}

function renumerarEnvios() {
  document.querySelectorAll('.envio-block').forEach((b, i) => {
    b.querySelector('.card-title').innerHTML =
      `<i class="fa fa-shipping-fast text-success"></i> Envío #${i+1}`;
  });
}

// ── Submit: serializar envíos ──────────────────────────────────────────────────
document.getElementById('form_pedido').addEventListener('submit', function(e) {
  e.preventDefault();

  // Validar comprobante
  if (!fileComp.files || !fileComp.files[0]) {
    Swal.fire('Falta comprobante', 'Debes adjuntar el comprobante de pago antes de guardar', 'warning');
    return;
  }

  // Validar cliente
  if (!document.getElementById('sel_cliente').value) {
    Swal.fire('Falta cliente', 'Selecciona un cliente', 'warning');
    return;
  }

  const envios = [];
  let valido = true;

  document.querySelectorAll('.envio-block').forEach(bloque => {
    const idDir = bloque.querySelector('.sel-direccion').value;
    if (!idDir) { valido = false; Swal.fire('Falta dirección','Selecciona la dirección de cada envío','warning'); return; }

    const productos = [];
    bloque.querySelectorAll('.tbody-productos tr').forEach(fila => {
      const id   = fila.querySelector('.sel-producto').value;
      const qty  = parseInt(fila.querySelector('.inp-cantidad').value || 0);
      const prec = parseFloat(fila.querySelector('.inp-precio').value || 0);
      if (id && qty > 0 && prec > 0) productos.push({ id_producto: id, cantidad: qty, precio: prec });
    });

    if (!productos.length) { valido = false; Swal.fire('Sin productos','Agrega al menos un producto por envío','warning'); return; }
    envios.push({ id_direccion: idDir, productos });
  });

  if (!valido || !envios.length) return;

  document.getElementById('envios_json').value = JSON.stringify(envios);

  Swal.fire({
    title: 'Guardar pedido',
    html: `Se crearán <strong>${envios.length} envíos</strong> con un solo comprobante.<br>¿Confirmar?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, guardar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#28a745'
  }).then(r => { if (r.isConfirmed) this.submit(); });
});

// Iniciar con un envío
document.addEventListener('DOMContentLoaded', () => agregarEnvio());
</script>

<?php include('../layout/parte2.php'); ?>
