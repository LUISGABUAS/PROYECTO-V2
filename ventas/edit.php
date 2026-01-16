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

        <form action="../app/controllers/ventas/update_venta.php" method="POST">

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
                  <input type="text" name="cliente" class="form-control"
                         value="<?= $venta['cliente'] ?>" required>
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
                  <input type="text" class="form-control" value="<?= $venta['id_usuario'] ?>" disabled>
                </div>
              </div>

            </div>

            <hr>

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
                          <?= $p['codigo'] ?> - <?= $p['nombre'] ?>
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
