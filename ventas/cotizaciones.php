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

            <form id="form_cotizacion">

                      <div class="row">
                        <div class="col-md-12">
                          <p class="text-muted"><i class="fa fa-info-circle"></i> Selecciona artículos para cotizar</p>
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

                <button type="button" class="btn btn-info" data-toggle="modal" data-target="#modalPreview">
                  <i class="fa fa-eye"></i> Vista Previa
                </button>

                <button type="button" class="btn btn-warning" id="btnDescargarPdf">
                  <i class="fa fa-file-pdf"></i> Descargar PDF
                </button>
              </div>

            </form>

          </div>

        </div>
      </div>

    </div>
  </div>
</div>

<!-- Modal Preview Cotización -->
<div class="modal fade" id="modalPreview" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Vista Previa de Cotización</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body" id="contenidoPreview" style="max-height: 600px; overflow-y: auto;">
        <!-- Se llena con JavaScript -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-warning" id="btnDescargarPdfModal">
          <i class="fa fa-file-pdf"></i> Descargar PDF
        </button>
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



<!-- Scripts para Cotización Simple -->
<script>
// 📋 Generar vista previa
document.querySelector('[data-target="#modalPreview"]').addEventListener('click', function(){
  generarPreview();
});

function generarPreview(){
  const total = document.getElementById('total_venta').value;
  const fecha = new Date().toLocaleDateString('es-ES');

  let productosHTML = '';
  
  document.querySelectorAll('#detalle_venta tr').forEach((row) => {
    const producto = row.querySelector('.producto').options[row.querySelector('.producto').selectedIndex].text;
    const cantidad = row.querySelector('.cantidad').value;
    const precio = row.querySelector('.precio').value;
    const subtotal = row.querySelector('.subtotal').value;

    if(!producto || producto === 'Seleccione') return;

    productosHTML += `
      <tr class="text-right">
        <td class="text-left">${producto}</td>
        <td>${cantidad}</td>
        <td>$${parseFloat(precio).toFixed(2)}</td>
        <td>$${parseFloat(subtotal).toFixed(2)}</td>
      </tr>
    `;
  });

  if(!productosHTML){
    Swal.fire('Advertencia', 'Agrega al menos un artículo', 'warning');
    return;
  }

  const preview = `
    <div style="padding: 20px; font-family: Arial, sans-serif;">
      <div style="border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px;">
        <h2 style="margin: 0;">COTIZACIÓN</h2>
        <p style="margin: 5px 0; color: #666;">Fecha: ${fecha}</p>
      </div>

      <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <thead>
          <tr style="background-color: #f0f0f0; border: 1px solid #ddd;">
            <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Producto</th>
            <th style="padding: 8px; text-align: center; border: 1px solid #ddd;">Cantidad</th>
            <th style="padding: 8px; text-align: right; border: 1px solid #ddd;">Precio Unitario</th>
            <th style="padding: 8px; text-align: right; border: 1px solid #ddd;">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          ${productosHTML}
        </tbody>
      </table>

      <div style="text-align: right; margin-top: 20px; border-top: 2px solid #333; padding-top: 15px;">
        <h3 style="margin: 0; color: #27ae60;">TOTAL: $${parseFloat(total).toFixed(2)}</h3>
      </div>
    </div>
  `;

  document.getElementById('contenidoPreview').innerHTML = preview;
}

// 📥 Descargar PDF
document.getElementById('btnDescargarPdf').addEventListener('click', function(){
  descargarCotizacionPDF();
});

document.getElementById('btnDescargarPdfModal').addEventListener('click', function(){
  descargarCotizacionPDF();
});

function descargarCotizacionPDF(){
  const total = document.getElementById('total_venta').value;
  const fecha = new Date().toLocaleDateString('es-ES');

  let productos = [];
  document.querySelectorAll('#detalle_venta tr').forEach((row) => {
    const productSelect = row.querySelector('.producto');
    const producto = productSelect.options[productSelect.selectedIndex].text;
    const cantidad = row.querySelector('.cantidad').value;
    const precio = row.querySelector('.precio').value;
    const subtotal = row.querySelector('.subtotal').value;

    if(!producto || producto === 'Seleccione') return;

    productos.push({
      producto,
      cantidad: parseInt(cantidad),
      precio: parseFloat(precio),
      subtotal: parseFloat(subtotal)
    });
  });

  if(!productos.length){
    Swal.fire('Advertencia', 'Agrega al menos un artículo', 'warning');
    return;
  }

  // Enviar datos al controlador
  const formData = new FormData();
  formData.append('total', total);
  formData.append('fecha', fecha);
  formData.append('productos', JSON.stringify(productos));
  formData.append('generar_pdf', 'true');

  fetch('../app/controllers/ventas/generar_cotizacion_pdf.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(html => {
    // Abrir en nueva ventana para imprimir/guardar como PDF
    const ventana = window.open('', '', 'width=900,height=700');
    ventana.document.write(html);
    ventana.document.close();
  })
  .catch(err => {
    Swal.fire('Error', 'No se pudo generar el PDF', 'error');
    console.error(err);
  });
}
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
