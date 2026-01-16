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

            <form action="../app/controllers/ventas/create.php" method="POST">

                      <div class="row">

                        <!-- FECHA -->
                        <div class="col-md-3">
                          <label>Fecha</label>
                          <input type="date" name="fecha" class="form-control"
                                value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- CLIENTES -->
                        <div class="col-md-5">
                          <label>Cliente</label>

                          <!-- BUSCADOR -->
                          <input type="text" id="buscar_cliente"
                                class="form-control mb-2"
                                placeholder="🔍 Buscar por nombre o 📱 teléfono">

                          <!-- FILTROS -->
                          <div class="btn-group mb-2">
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="filtrarClientes('local')">Locales</button>

                            <button type="button" class="btn btn-outline-warning btn-sm"
                                    onclick="filtrarClientes('foraneo')">Foráneos</button>

                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="filtrarClientes('todos')">Todos</button>
                          </div>

                          <!-- SELECT CLIENTES -->
                          <select name="cliente" id="select_cliente"
                                  class="form-control" required>
                            <option value="">Seleccione cliente</option>

                            <?php foreach($clientes as $c):
                              $telefono = preg_replace('/[^0-9]/', '', $c['telefono']); ?>
                              <option value="<?= $c['id_cliente'] ?>"
                                    data-envio="<?= htmlspecialchars($c['tipo_cliente']) ?>"
                                    data-telefono="<?= $telefono ?>">
                              <?= htmlspecialchars($c['nombre_completo']) ?> |  <?= $telefono ?>
                            </option>

                            <?php endforeach; ?>
                          </select>
                        </div>

                        <!-- ENVÍO -->
                        <div class="col-md-2">
                          <label>Tipo de envío</label>
                          <select name="envio" id="tipo_envio"
                                  class="form-control" required>
                            <option value="">Seleccione</option>
                            <option value="local">Local</option>
                            <option value="foraneo">Foráneo</option>
                          </select>
                        </div>

                        <!-- VENDEDOR -->
                        <div class="col-md-2">
                          <label>Vendedor</label>
                          <input type="text" class="form-control"
                                value="<?= $sesion_nombres ?>" disabled>
                          <input type="hidden" name="id_usuario"
                                value="<?= $id_usuario_sesion ?>">
                        </div>

                      </div>


                <hr>

                <!-- ARTICULOS -->
                <h5><i class="fa fa-box"></i> Artículos</h5>

                <table class="table table-bordered table-sm">
                  <thead class="thead-light">
                    <tr class="text-center">
                      <th>Producto</th>
                      <th width="120">Cantidad</th>
                      <th width="150">Precio  $</th>
                      <th width="150">Subtotal $</th>
                      <th width="50"></th>
                      
                    </tr>
                  </thead>

                  <tbody id="detalle_venta">
                    <tr>
                      <td>
                        <select name="productos[]" class="form-control form-control-sm producto" required onchange="asignarPrecio(this)">
                          <option value="">Seleccione</option>
                          <?php 
                           $dinero = "$";
                          foreach($datos_productos as $p){
                            ?>
                            <option value="<?= $p['id_producto'] ?>" data-precio="<?= $p['precio_venta'] ?>">
                              <?= $p['codigo'] ?> - <?= $p['nombre'] ?>
                            </option>
                          <?php } ?>
                        </select>
                      </td>

                      <td>
                        <input type="number" name="cantidades[]" class="form-control form-control-sm text-center cantidad" min="1" value="1" oninput="calcularFila(this)" required>
                      </td>

                      <td>
                        <input type="number" name="precios[]" class="form-control form-control-sm text-center precio" step="0.01" readonly>
                      </td>

                      <td>
                        <input type="number" 
                              class="form-control form-control-sm text-center subtotal"
                              step="0.01" readonly>
                      </td>


                      <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this)">
                          <i class="fa fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>

                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="agregarFila()">
                  <i class="fa fa-plus"></i> Agregar artículo
                </button>
                
                <div class="row justify-content-end mt-3">
                    <div class="col-md-3">
                      <div class="form-group">
                        <label>Total $</label>
                        <input type="number" 
                              name="total" 
                              id="total_venta"
                              class="form-control text-center font-weight-bold"
                              readonly>
                      </div>
                    </div>
                  </div>


              </div>

              <div class="card-footer">
                <a href="index.php" class="btn btn-outline-danger">
                  <i class="fa fa-times"></i> Cancelar
                </a>

                <button type="submit" class="btn btn-success">
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
