<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

include('../app/controllers/almacen/list_almacen.php');
include('../app/controllers/clientes/list_clientes.php');



if(in_array(21, $_SESSION['permisos'])):
?>

<?php if (isset($_SESSION['mensaje'])): ?>
<script>
let mensaje = <?= json_encode($_SESSION['mensaje']) ?>;

// Determinar icono según el contenido
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

            <form action="../app/controllers/ventas/create.php" method="POST" enctype="multipart/form-data">

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
                  <div class="col-md-6">
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
                      <select name="envio" id="tipo_envio" class="form-control" required>
                        <option value="">Seleccione</option>
                        <option value="local">Local</option>
                        <option value="foraneo">Foráneo</option>
                      </select>
                    </div>
                  </div>

                  <!-- VENDEDOR -->
                  <div class="col-md-2">
                    <div class="form-group">
                      <label><strong>Vendedor</strong></label>
                      <input type="text" class="form-control" value="<?= $sesion_nombres ?>" disabled>
                      <input type="hidden" name="id_usuario" value="<?= $id_usuario_sesion ?>">
                    </div>
                  </div>

                </div>

                <hr class="my-3">

                <!-- COMPROBANTE -->
                <div class="row mb-4">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label><strong><i class="fa fa-file-pdf text-danger"></i> Comprobante <span class="text-danger">*</span></strong></label>
                      <input type="file" name="comprobante" id="comprobante" class="form-control-file border rounded p-2" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                      <small class="form-text text-muted d-block mt-2">📋 Formatos: PDF, JPG, PNG, DOC, DOCX | 📦 Máx. 5MB</small>
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
                              <option value="<?= $p['id_producto'] ?>" data-precio="<?= $p['precio_venta'] ?>">
                                <?= $p['codigo'] ?> - <?= $p['nombre'] ?>
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

<script>
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
                  data-precio="<?= $p['precio_venta'] ?>">
            <?= $p['codigo'] ?> - <?= $p['nombre'] ?>
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
  
  document.getElementById('detalle_venta')
          .insertAdjacentHTML('beforeend', fila);
}


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

function calcularFila(elemento){
  const fila = elemento.closest('tr');

  const cantidad = parseFloat(fila.querySelector('.cantidad').value || 0);
  const precio   = parseFloat(fila.querySelector('.precio').value || 0);

  const subtotal = cantidad * precio;

  fila.querySelector('.subtotal').value = subtotal.toFixed(2);

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
  if(filas.length === 1){
    Swal.fire('Debe existir al menos un producto');
    return;
  }
  btn.closest('tr').remove();
  calcularTotal();
}

</script>

<script>
const selectCliente = document.getElementById('select_cliente');
const buscador = document.getElementById('buscar_cliente');
const tipoEnvio = document.getElementById('tipo_envio');

// 🔍 Buscar por nombre o teléfono
buscador.addEventListener('keyup', () => {
  const texto = buscador.value.toLowerCase();

  Array.from(selectCliente.options).forEach(opt => {
    if (!opt.value) return;

    const nombre = (opt.text || '').toLowerCase();
    const telefono = (opt.dataset.telefono || '').toLowerCase();

    opt.hidden = !(nombre.includes(texto) || telefono.includes(texto));
  });
});


// 📦 Filtrar por tipo
function filtrarClientes(tipo){
  Array.from(selectCliente.options).forEach(opt => {
    if (!opt.value) return;

    if (tipo === 'todos') {
      opt.hidden = false;
    } else {
      opt.hidden = opt.dataset.envio !== tipo;
    }
  });

  selectCliente.value = '';
}


// 🔁 Auto asignar envío
selectCliente.addEventListener('change', function(){
  const opt = this.options[this.selectedIndex];
  if(opt && opt.dataset.envio){
    tipoEnvio.value = opt.dataset.envio;
  }
});

</script>

<script>
document.getElementById('comprobante').addEventListener('change', function () {
  const file = this.files[0];
  const preview = document.getElementById('preview_comprobante');
  const contenido = document.getElementById('preview_contenido');

  contenido.innerHTML = '';
  preview.style.display = 'none';

  if (!file) return;

  const tipo = file.type;

  // 📷 IMÁGENES
  if (tipo.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = function (e) {
      contenido.innerHTML = `
        <img src="${e.target.result}" 
             class="img-responsive"
             style="max-height:300px;">
      `;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
  }

  // 📄 PDF
  else if (tipo === 'application/pdf') {
    const url = URL.createObjectURL(file);
    contenido.innerHTML = `
      <embed src="${url}" 
             type="application/pdf"
             width="100%"
             height="300px">
    `;
    preview.style.display = 'block';
  }

  // 📎 DOC / DOCX / otros
  else {
    contenido.innerHTML = `
      <p>
        <i class="fa fa-file"></i> 
        <strong>${file.name}</strong><br>
        <small>Archivo cargado correctamente</small>
      </p>
    `;
    preview.style.display = 'block';
  }
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
}).then(() => {
  window.location = "<?= $URL ?>";
});
</script>
<?php endif; ?>


<?php include('../layout/parte2.php'); ?>
