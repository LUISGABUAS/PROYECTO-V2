<style>
.stock-selected {
    background-color: #fff3cd !important; /* amarillo suave */
}
</style>

<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

/* =========================
   RECIBIR ID DEL PRODUCTO
========================= */
$id_producto = $_GET['id'] ?? null;
if (!$id_producto) {
    echo "<h3 style='color:red'>Producto no válido</h3>";
    exit;
}

/* =========================
   CONTROLLER STOCK
========================= */
include('../app/controllers/stock/list_stock.php');

if (isset($_SESSION['mensaje'])) {
    $respuesta = $_SESSION['mensaje']; ?>
    <script>
    Swal.fire({
        position: 'top-end',
        icon: 'success',
        title: <?php echo json_encode($respuesta); ?>,
        showConfirmButton: false,
        timer: 2000
    });
    </script>
<?php
    unset($_SESSION['mensaje']);
}

if (!in_array(11, $_SESSION['permisos'])):
    include('../layout/parte2.php');
    echo '<script>
      Swal.fire({
        icon: "error",
        title: "Access Denied",
        text: "No tienes permiso para acceder a esta página.",
        showConfirmButton: false,
        timer: 3000
      }).then(() => { window.location = "'.$URL.'"; });
    </script>';
    exit;
endif;
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Stock</h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <!-- FILTRO -->
            <form method="get" class="row mb-3">
                <input type="hidden" name="id" value="<?= $id_producto ?>">
                <div class="col-md-3">
                    <input type="date" name="desde" class="form-control" value="<?= $_GET['desde'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <input type="date" name="hasta" class="form-control" value="<?= $_GET['hasta'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary">Filtrar</button>
                </div>
            </form>

            <!-- TABLA STOCK -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-6 d-flex align-items-center flex-wrap" style="gap:6px;">
                                    <button id="select-not-scanned" class="btn btn-warning btn-sm">
                                        <i class="fa fa-tag"></i> Seleccionar sin escanear
                                        <span class="badge badge-light text-dark ml-1"><?= count(array_filter($datos_stock, fn($s)=>$s['estado']=='SIN ESCANEAR')) ?></span>
                                    </button>
                                    <span id="badge-seleccion" class="text-muted small" style="display:none;">
                                        — <span id="num-seleccion">0</span> seleccionados
                                        <a href="#" id="deselect-all" class="text-danger ml-1" title="Quitar selección"><i class="fa fa-times-circle"></i></a>
                                    </span>
                                    <div id="btns-imprimir" style="display:none;">
                                        <button id="print-pdf" class="btn btn-primary btn-sm">
                                            <i class="fa fa-file-pdf"></i> PDF
                                        </button>
                                        <button id="print-zebra" class="btn btn-dark btn-sm ml-1">
                                            <i class="fa fa-barcode"></i> Zebra
                                        </button>
                                    </div>
                                    <?php if (in_array(11, $_SESSION['permisos']) || in_array(24, $_SESSION['permisos'])): ?>
                                    <button id="btn-marcar-especial" class="btn btn-warning btn-sm ml-1" style="display:none;"
                                            data-toggle="modal" data-target="#modalEspecial">
                                        <i class="fa fa-exclamation-triangle"></i> Marcar como Especial
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h3 class="card-title">
                                        Total en bodega: <?= count(array_filter($datos_stock, fn($s)=>$s['estado']=='EN BODEGA')) ?><br>
                                        Total sin escanear: <?= count(array_filter($datos_stock, fn($s)=>$s['estado']=='SIN ESCANEAR')) ?>
                                    </h3>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="table table-responsive">
                                <table id="example1" class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Seleccionar</th>
                                            <th>Codigo</th>
                                            <th>Estado</th>
                                            <th>Tipo especial</th>
                                            <th>Categoria</th>
                                            <th>Producto</th>
                                            <th>Fecha Ingreso</th>
                                            <th>Fecha Venta</th>
                                            <th>Creado Por</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $contador = 0;
                                        foreach ($datos_stock as $dato):
                                            $contador++;
                                            $estado = $dato['estado'];
                                            $color = $estado === 'EN BODEGA' ? 'success' : ($estado === 'SIN ESCANEAR' ? 'warning' : ($estado === 'VENDIDO' ? 'danger' : 'secondary'));
                                        ?>
                                        <tr class="stock-row">
                                            <td><?= $contador ?></td>
                                            <td>
                                                <input type="checkbox" class="select-stock" value="<?= $dato['id_stock'] ?>" data-estado="<?= $dato['estado'] ?>">
                                            </td>
                                            <td><?= $dato['codigo_unico'] ?></td>
                                            <td><span class="badge badge-<?= $color ?>"><?= $estado ?></span></td>
                                            <td>
                                              <?php if (!empty($dato['tipo_especial'])): ?>
                                                <span class="badge badge-<?= $dato['tipo_especial']==='VIDEO' ? 'danger' : 'warning' ?>">
                                                  <?= $dato['tipo_especial'] ?>
                                                </span>
                                                <?php if (!empty($dato['notas_especial'])): ?>
                                                  <small class="d-block text-muted"><?= htmlspecialchars($dato['notas_especial']) ?></small>
                                                <?php endif; ?>
                                              <?php else: ?>
                                                <span class="text-muted">—</span>
                                              <?php endif; ?>
                                            </td>
                                            <td><?= $dato['nombre_categoria'] ?></td>
                                            <td><?= $dato['nombre_producto'] ?></td>
                                            <td><?= $dato['fecha_ingreso'] ?></td>
                                            <td>
                                                <?php if (!empty($dato['fecha_salida']) && $dato['fecha_salida'] !== '0000-00-00'): ?>
                                                    <span class="badge badge-success"><?= $dato['fecha_salida'] ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Sin vender</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $dato['creado_por'] ?></td>
                                            <td>
                                                <?php if (in_array(13, $_SESSION['permisos'])): ?>
                                                    <button class="btn btn-danger btn-sm delete-stock" data-id="<?= $dato['id_stock'] ?>" data-estado="<?= $estado; ?>"><i class="fa fa-trash"></i> Eliminar</button>
                                                <?php endif ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </div><!-- /.content -->
</div><!-- /.content-wrapper -->

<!-- MODAL MARCAR ESPECIAL -->
<div class="modal fade" id="modalEspecial" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Marcar como Paca Especial</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3">Pacas seleccionadas: <strong id="me-cantidad">0</strong></p>
        <div class="form-group">
          <label><strong>Tipo</strong></label>
          <div class="d-flex" style="gap:12px;">
            <div class="card border-danger flex-fill text-center p-3" id="card-video" style="cursor:pointer;" onclick="selTipo('VIDEO')">
              <i class="fa fa-video fa-2x text-danger mb-1"></i>
              <strong>VIDEO</strong>
              <small class="text-muted d-block">Abierta para grabar</small>
            </div>
            <div class="card border-warning flex-fill text-center p-3" id="card-flejada" style="cursor:pointer;" onclick="selTipo('FLEJADA')">
              <i class="fa fa-undo fa-2x text-warning mb-1"></i>
              <strong>FLEJADA</strong>
              <small class="text-muted d-block">Rembolsada / abierta</small>
            </div>
          </div>
          <input type="hidden" id="me-tipo" value="">
        </div>
        <div class="form-group mt-3">
          <label>Notas <small class="text-muted">(opcional)</small></label>
          <textarea id="me-notas" class="form-control" rows="2" placeholder="Ej: Abierta para TikTok del 01/07..."></textarea>
        </div>
        <div class="form-group" id="grp-venta-origen">
          <label># Venta de origen <small class="text-muted">(si fue rembolsada, opcional)</small></label>
          <input type="number" id="me-venta-origen" class="form-control" placeholder="ej. 147">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-warning" id="btn-confirmar-especial" onclick="confirmarEspecial()">
          <i class="fa fa-check"></i> Confirmar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
let _tipoEspecial = '';

function selTipo(tipo) {
  _tipoEspecial = tipo;
  document.getElementById('me-tipo').value = tipo;
  document.getElementById('card-video').classList.toggle('bg-danger', tipo === 'VIDEO');
  document.getElementById('card-video').classList.toggle('text-white', tipo === 'VIDEO');
  document.getElementById('card-flejada').classList.toggle('bg-warning', tipo === 'FLEJADA');
  document.getElementById('card-flejada').classList.toggle('text-dark', tipo === 'FLEJADA');
}

// Mostrar botón especial al seleccionar pacas EN BODEGA sin tipo
document.querySelectorAll('.select-stock').forEach(cb => {
  cb.addEventListener('change', actualizarBtnEspecial);
});
document.getElementById('deselect-all')?.addEventListener('click', () => setTimeout(actualizarBtnEspecial, 50));

function actualizarBtnEspecial() {
  const selEN = Array.from(document.querySelectorAll('.select-stock:checked'))
    .filter(cb => cb.dataset.estado === 'EN BODEGA');
  const btn = document.getElementById('btn-marcar-especial');
  if (btn) {
    btn.style.display = selEN.length > 0 ? 'inline-block' : 'none';
    document.getElementById('me-cantidad').textContent = selEN.length;
  }
}

function confirmarEspecial() {
  if (!_tipoEspecial) {
    Swal.fire({ icon: 'warning', title: 'Selecciona el tipo', text: 'VIDEO o FLEJADA' });
    return;
  }
  const ids = Array.from(document.querySelectorAll('.select-stock:checked'))
    .filter(cb => cb.dataset.estado === 'EN BODEGA')
    .map(cb => cb.value);

  if (!ids.length) {
    Swal.fire({ icon: 'warning', title: 'Sin pacas', text: 'Selecciona pacas EN BODEGA' });
    return;
  }

  const fd = new FormData();
  fd.append('tipo_especial', _tipoEspecial);
  fd.append('notas_especial', document.getElementById('me-notas').value);
  fd.append('id_venta_origen', document.getElementById('me-venta-origen').value);
  ids.forEach(id => fd.append('ids[]', id));

  fetch('<?= $URL ?>/app/controllers/stock/marcar_especial.php', { method: 'POST', body: fd, credentials: 'same-origin' })
    .then(r => r.json())
    .then(res => {
      $('#modalEspecial').modal('hide');
      if (res.success) {
        Swal.fire({ icon: 'success', title: res.message, timer: 2000, showConfirmButton: false })
          .then(() => location.reload());
      } else {
        Swal.fire({ icon: 'error', title: 'Error', text: res.message });
      }
    });
}
</script>

<?php include('../layout/mensajes.php')?>
<?php include('../layout/parte2.php'); ?>

<!-- SCRIPTS -->
<script>
// IDs de TODOS los sin escanear (todas las páginas, generado por PHP)
const todosSinEscanear = <?= json_encode(
    array_values(array_map(
        fn($s) => (int)$s['id_stock'],
        array_filter($datos_stock, fn($s) => $s['estado'] === 'SIN ESCANEAR')
    ))
) ?>;

// IDs seleccionados activos (se usa en PDF y Zebra)
let idsSeleccionados = [];

function actualizarBadge() {
    const n = idsSeleccionados.length;
    if (n > 0) {
        $('#num-seleccion').text(n);
        $('#badge-seleccion').show();
        $('#btns-imprimir').show();
    } else {
        $('#badge-seleccion').hide();
        $('#btns-imprimir').hide();
    }
}

function obtenerIds() {
    if (idsSeleccionados.length > 0) return idsSeleccionados;
    let ids = [];
    $('.select-stock:checked').each(function(){ ids.push($(this).val()); });
    return ids;
}

$(function () {
    $("#example1").DataTable({
        "responsive": true, "lengthChange": false, "autoWidth": false,
        "buttons": ["copy", "excel", "pdf", "print", "colvis"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
});

// RESALTAR FILAS
$(document).on('change', '.select-stock', function () {
    $(this).closest('tr').toggleClass('stock-selected', $(this).is(':checked'));
    idsSeleccionados = []; // al marcar manual, limpia la selección masiva
    const n = $('.select-stock:checked').length;
    if (n > 0) {
        $('#num-seleccion').text(n);
        $('#badge-seleccion').show();
        $('#btns-imprimir').show();
    } else {
        $('#badge-seleccion').hide();
        $('#btns-imprimir').hide();
    }
});

// QUITAR SELECCIÓN
$(document).on('click', '#deselect-all', function(e){
    e.preventDefault();
    idsSeleccionados = [];
    $('.select-stock').prop('checked', false);
    $('.stock-row').removeClass('stock-selected');
    actualizarBadge();
});

// SELECCIONAR NO ESCANEADOS — TODOS, incluyendo otras páginas
$('#select-not-scanned').click(function(){
    // Marcar los visibles en el DOM
    $('.select-stock').each(function(){
        const esSinEscanear = $(this).data('estado') === 'SIN ESCANEAR';
        $(this).prop('checked', esSinEscanear);
        $(this).closest('tr').toggleClass('stock-selected', esSinEscanear);
    });
    // Guardar TODOS (incluye páginas no visibles)
    idsSeleccionados = [...todosSinEscanear];
    actualizarBadge();
    Swal.fire('Listo', todosSinEscanear.length + ' sin escanear seleccionados (todas las páginas)', 'success');
});


// ELIMINAR STOCK

$(document).on('click', '.delete-stock', function () {
    let id_stock = $(this).data('id');
    let estado  = $(this).data('estado');

    if(estado === 'VENDIDO'){
        Swal.fire({ 
            icon: 'error',
            title: 'No se puede eliminar',
            text: 'El stock vendido no puede ser eliminado'
        });
        return;
    }

    Swal.fire({
        title: '¿Eliminar stock?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {

            $.post(
                '../app/controllers/stock/delete_stock.php',
                { id_stock: id_stock },
                function (response) {
                    if (response.success) {
                        Swal.fire('Eliminado', response.message, 'success')
                        .then(() => location.reload());
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                'json'
            );

        }
    });
});


// IMPRIMIR ZEBRA
$('#print-zebra').click(function(){
    let selected = obtenerIds();

    if(selected.length === 0){
        Swal.fire('Atención','Debes seleccionar al menos un código','warning');
        return;
    }

    Swal.fire({
        title: 'Imprimir en Zebra',
        text: 'Se enviarán ' + selected.length + ' etiquetas a la impresora',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Imprimir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if(result.isConfirmed){
            let url = <?php echo json_encode($URL . '/app/controllers/helpers/print_zebra_direct_prueba.php'); ?> + '?ids=' + selected.join(',');
            
            fetch(url)
            .then(res => res.json()) // Debe ser JSON
            .then(data => {
                if(data.status === 'success'){
                    Swal.fire('Listo', data.message, 'success');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'No se pudo imprimir: ' + err, 'error');
            });
        }
    });
});

</script>

<!--PDF SELECCIONADOS-->
<script>
  $('#print-pdf').click(function(){
    let selected = obtenerIds();

    if(selected.length === 0){
        Swal.fire('Atención','Debes seleccionar al menos un código','warning');
        return;
    }

    // Si hay más de 50, abrir la página de selección de lotes
    if(selected.length > 50){
        let url = <?= json_encode($URL . '/app/controllers/helpers/print_zebra_seleccion.php') ?>
                  + '?ids=' + selected.join(',');
        window.open(url, '_blank');
        return;
    }

    let url = <?= json_encode($URL . '/app/controllers/helpers/print_zebra_seleccion.php') ?> 
              + '?ids=' + selected.join(',');

    window.open(url, '_blank');
});
</script>

