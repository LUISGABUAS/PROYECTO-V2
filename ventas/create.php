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
    <div class="container-fluid">
      <h1 class="m-0">Nueva Venta</h1>
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

            <form action="../app/controllers/ventas/create.php" method="POST" enctype="multipart/form-data" id="form_venta">

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
                      <input type="text" id="buscar_cliente" class="form-control mb-2" placeholder="🔍 Buscar por nombre o 📱 teléfono">
                      <div class="btn-group btn-group-sm mb-2" role="group">
                        <button type="button" class="btn btn-outline-primary" onclick="filtrarClientes('local')">Locales</button>
                        <button type="button" class="btn btn-outline-warning" onclick="filtrarClientes('foraneo')">Foráneos</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="filtrarClientes('todos')">Todos</button>
                      </div>
                      <select name="cliente" id="select_cliente" class="form-control" required>
                        <option value="">Seleccione cliente</option>
                        <?php foreach($clientes as $c):
                          $telefono = preg_replace('/[^0-9]/', '', $c['telefono']); ?>
                          <option value="<?= $c['id_cliente'] ?>" data-envio="<?= htmlspecialchars($c['tipo_cliente']) ?>" data-telefono="<?= $telefono ?>">
                            <?= htmlspecialchars($c['nombre_completo']) ?> | <?= $telefono ?>
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

                  <!-- TIPO DE PAGO (solo visible si es local) -->
                  <div class="col-md-2" id="col_tipo_pago" style="display:none;">
                    <div class="form-group">
                      <label><strong>Tipo de pago</strong></label>
                      <input type="hidden" name="tipo_pago" id="tipo_pago" value="efectivo">
                      <div class="d-flex gap-2">
                        <button type="button" id="btn_efectivo"
                                onclick="seleccionarPago('efectivo')"
                                class="btn btn-success flex-fill py-2"
                                style="border-radius:10px; font-size:13px;">
                          💵<br><small>Efectivo</small>
                        </button>
                        <button type="button" id="btn_comprobante"
                                onclick="seleccionarPago('comprobante')"
                                class="btn btn-outline-primary flex-fill py-2"
                                style="border-radius:10px; font-size:13px;">
                          🧾<br><small>Comprobante</small>
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

                <hr class="my-3">

                <!-- COMPROBANTE -->
                <div class="row mb-4" id="fila_comprobante">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><strong><i class="fa fa-file-pdf text-danger"></i> Comprobante <span class="text-danger">*</span></strong></label>
                      <input type="file"
                             name="comprobante"
                             id="comprobante"
                             class="form-control-file border rounded p-2"
                             accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                             required>
                      <small class="form-text text-muted d-block mt-2">📋 Formatos: PDF, JPG, PNG, DOC, DOCX | 📦 Máx. 5MB</small>
                      <small id="file_error" class="text-danger font-weight-bold" style="display:none;"></small>
                    </div>
                  </div>
                </div>

                <!-- PREVISUALIZACIÓN -->
                <div class="row">
                  <div class="col-md-6">
                    <div id="preview_comprobante" style="display:none;">
                      <label><strong>Previsualización:</strong></label>
                      <div class="border p-2" id="preview_contenido"></div>
                    </div>
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
                          <select name="productos[]" class="form-control form-control-sm producto" required onchange="asignarPrecio(this)">
                            <option value="">Seleccione</option>
                            <?php foreach($datos_productos as $p): ?>
                              <option value="<?= $p['id_producto'] ?>"
                                      data-precio="<?= $p['precio_venta'] ?>"
                                      data-stock="<?= $p['stock_disponible'] ?>">
                                <?= $p['codigo'] ?> - <?= $p['nombre'] ?> (<?= $p['stock_disponible'] ?> disp.)
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
              class="form-control form-control-sm producto"
              required
              onchange="asignarPrecio(this)">
        <option value="">Seleccione</option>
        <?php foreach($datos_productos as $p){ ?>
          <option value="<?= $p['id_producto'] ?>"
                  data-precio="<?= $p['precio_venta'] ?>"
                  data-stock="<?= $p['stock_disponible'] ?>">
            <?= $p['codigo'] ?> - <?= $p['nombre'] ?> (<?= $p['stock_disponible'] ?> disp.)
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
// ============ GESTIÓN DE CLIENTES ============
const selectCliente    = document.getElementById('select_cliente');
const buscador         = document.getElementById('buscar_cliente');
const tipoEnvioHidden  = document.getElementById('tipo_envio');
const colTipoPago      = document.getElementById('col_tipo_pago');
const filaComprobante  = document.getElementById('fila_comprobante');
const inputComprobante = document.getElementById('comprobante');

// 🔍 Buscar por nombre o teléfono
buscador.addEventListener('keyup', () => {
  const texto = buscador.value.toLowerCase();
  Array.from(selectCliente.options).forEach(opt => {
    if (!opt.value) return;
    const nombre   = (opt.text || '').toLowerCase();
    const telefono = (opt.dataset.telefono || '').toLowerCase();
    opt.hidden = !(nombre.includes(texto) || telefono.includes(texto));
  });
});

// 📦 Filtrar por tipo
function filtrarClientes(tipo){
  Array.from(selectCliente.options).forEach(opt => {
    if (!opt.value) return;
    opt.hidden = tipo === 'todos' ? false : opt.dataset.envio !== tipo;
  });
  selectCliente.value = '';
}

// 🔁 Auto asignar envío + lógica tipo de pago
selectCliente.addEventListener('change', function(){
  const opt   = this.options[this.selectedIndex];
  const envio = opt?.dataset.envio || '';

  tipoEnvioHidden.value = envio;

  const displayField = document.getElementById('tipo_envio_display');

  if(envio === 'local'){
    displayField.value     = 'Local';
    displayField.className = 'form-control bg-light';
    colTipoPago.style.display = 'block';
    seleccionarPago('efectivo');

  } else if(envio === 'foraneo'){
    displayField.value     = 'Foráneo';
    displayField.className = 'form-control bg-light';
    colTipoPago.style.display     = 'none';
    filaComprobante.style.display = 'block';
    inputComprobante.required     = true;
    document.getElementById('tipo_pago').value = 'comprobante';
  }
});

// 🎨 Selector visual de tipo de pago
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
  const pago = document.getElementById('tipo_pago').value;

  if(pago === 'efectivo'){
    filaComprobante.style.display = 'none';
    inputComprobante.required     = false;
    inputComprobante.value        = '';
    document.getElementById('preview_comprobante').style.display = 'none';
    document.getElementById('preview_contenido').innerHTML       = '';
  } else {
    filaComprobante.style.display = 'block';
    inputComprobante.required     = true;
  }
}
</script>

<script>
// ============ PREVISUALIZACIÓN DE COMPROBANTE ============
document.getElementById('comprobante').addEventListener('change', function () {
  const file      = this.files[0];
  const preview   = document.getElementById('preview_comprobante');
  const contenido = document.getElementById('preview_contenido');
  const errorMsg  = document.getElementById('file_error');

  contenido.innerHTML    = '';
  preview.style.display  = 'none';
  errorMsg.style.display = 'none';
  errorMsg.textContent   = '';

  if (!file) return;

  const maxSize = 5 * 1024 * 1024;
  if (file.size > maxSize) {
    errorMsg.textContent   = '❌ El archivo excede el tamaño máximo de 5MB';
    errorMsg.style.display = 'block';
    this.value = '';
    return;
  }

  const tiposPermitidos = [
    'image/jpeg', 'image/jpg', 'image/png',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
  ];

  if (!tiposPermitidos.includes(file.type)) {
    errorMsg.textContent   = '❌ Formato de archivo no permitido';
    errorMsg.style.display = 'block';
    this.value = '';
    return;
  }

  if (file.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = e => {
      contenido.innerHTML   = `<img src="${e.target.result}" class="img-fluid" style="max-height:300px; max-width:100%;">`;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(file);

  } else if (file.type === 'application/pdf') {
    const url             = URL.createObjectURL(file);
    contenido.innerHTML   = `<embed src="${url}" type="application/pdf" width="100%" height="300px">`;
    preview.style.display = 'block';

  } else {
    contenido.innerHTML = `
      <div class="alert alert-success mb-0">
        <i class="fa fa-file"></i> <strong>${file.name}</strong><br>
        <small>Tamaño: ${(file.size / 1024).toFixed(2)} KB</small>
      </div>`;
    preview.style.display = 'block';
  }
});
</script>

<script>
// ============ VALIDACIÓN FINAL ANTES DE ENVIAR ============
document.getElementById('form_venta').addEventListener('submit', function(e) {

  // ✅ Validar comprobante (solo si no es efectivo)
  const comprobante = document.getElementById('comprobante');
  const tipoPagoVal = document.getElementById('tipo_pago')?.value || 'comprobante';
  const esEfectivo  = tipoPagoVal === 'efectivo';

  if (!esEfectivo && !comprobante.files.length) {
    e.preventDefault();
    Swal.fire({ icon: 'error', title: 'Falta comprobante', text: 'Debe adjuntar un archivo de comprobante' });
    return false;
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

<?php include('../layout/parte2.php'); ?>