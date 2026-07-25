<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

/* =========================
   CONTROLLER
========================= */
include('../app/controllers/dashboard/foraneos.php');

/* =========================
   MENSAJES
========================= */
if (isset($_SESSION['mensaje'])) {
  $respuesta = $_SESSION['mensaje']; ?>
  <script>
    Swal.fire({
      position: 'top-end',
      icon: 'success',
      title: <?= json_encode($respuesta) ?>,
      showConfirmButton: false,
      timer: 2000
    })
  </script>
<?php unset($_SESSION['mensaje']); }

/* =========================
   PERMISO DE ACCESO
========================= */
if (!in_array(20, $_SESSION['permisos']) && !in_array(40, $_SESSION['permisos'])) {
  include('../layout/parte2.php');
  echo "<script>Swal.fire('Acceso denegado','','error')</script>";
  exit;
}
?>

<style>
.modal-header {
  text-align: center;
}

.modal-header .close {
  position: absolute;
  right: 15px;
  top: 15px;
}
</style>


<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
      integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer" />

      <!-- MODAL REEMPLAZAR GUÍA -->
<div class="modal fade" id="modalReemplazarGuia" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title text-center">
          <i class="fa-solid fa-file-pdf"></i> Reemplazar guía PDF
        </h4>
      </div>

      <form id="formReemplazarGuia" enctype="multipart/form-data">
        <div class="modal-body">

          <input type="hidden" name="id_venta" id="modal_id_venta">

          <div class="form-group">
            <label>Selecciona nueva guía (PDF)</label>
            
            <input type="file"
                   name="guia_pdf"
                   class="form-control"
                   accept="application/pdf"
                   required>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">
            Cancelar
          </button>
          <button type="submit" class="btn btn-warning">
            <i class="fa-solid fa-rotate-right"></i> Reemplazar
          </button>
        </div>
      </form>

    </div>
  </div>
</div>



<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">Foraneos Ventas</h1>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <!-- FILTRO -->
      <form method="get" class="row mb-3 align-items-end">
        <div class="col-md-2">
          <label class="mb-1">Desde</label>
          <input type="date" name="desde" class="form-control" value="<?= $desde ?>">
        </div>
        <div class="col-md-2">
          <label class="mb-1">Hasta</label>
          <input type="date" name="hasta" class="form-control" value="<?= $hasta ?>">
        </div>
        <div class="col-md-3">
          <label class="mb-1">Paquetería</label>
          <select name="paqueteria_filtro" class="form-control">
            <option value="">Todas</option>
            <?php foreach(['DHL','Estafeta','FedEx','Paquetería Express','J&T Express','Otra'] as $p): ?>
              <option value="<?= $p ?>" <?= ($_GET['paqueteria_filtro'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
            <?php endforeach; ?>
            <option value="sin_paqueteria" <?= ($_GET['paqueteria_filtro'] ?? '') === 'sin_paqueteria' ? 'selected' : '' ?>>Sin paquetería</option>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary btn-block">Filtrar</button>
        </div>
        <div class="col-md-1">
          <a href="foraneos.php" class="btn btn-secondary btn-block">Reset</a>
        </div>
      </form>

      <!-- LEYENDA COLORES -->
      <div class="mb-2 d-flex flex-wrap" style="gap:.4rem;">
        <span class="badge" style="background:#ffcc00;color:#000;padding:6px 10px;">DHL</span>
        <span class="badge" style="background:#003087;color:#fff;padding:6px 10px;">Estafeta</span>
        <span class="badge" style="background:#4d148c;color:#fff;padding:6px 10px;">FedEx</span>
        <span class="badge" style="background:#28a745;color:#fff;padding:6px 10px;">Paquetería Express</span>
        <span class="badge" style="background:#d40511;color:#fff;padding:6px 10px;">J&T Express</span>
        <span class="badge" style="background:#e9ecef;color:#212529;padding:6px 10px;">Otra / Sin asignar</span>
      </div>

      <?php if (in_array(24, $_SESSION['permisos']) || in_array(40, $_SESSION['permisos'])): ?>

      <!-- TABLA VENTAS -->
      <div class="card card-outline card-primary">
        <div class="card-header">
          <h3 class="card-title">Reporte de Ventas Foraneos</h3>
        </div>

        <div class="card-body">
          <div class="table-responsive">
          <table id="ventas" class="table table-bordered table-striped table-sm">
            <thead>
              <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Domicilio</th>
                <th>Telefono</th>
                <th>Referencia</th>
                <th>Paquetería</th>
                <th>Guia</th>
                <th>Estado</th>


                <?php if (
                  (in_array(24, $_SESSION['permisos']) || in_array(40, $_SESSION['permisos'])) &&
                  (in_array(22, $_SESSION['permisos']) || in_array(28, $_SESSION['permisos']) || in_array(40, $_SESSION['permisos']))
                ): ?>
                  <th>Acciones</th>
                <?php endif; ?>
              </tr>
            </thead>

            <tbody>
              <?php
              $badges_paq = [
                  'DHL'                => ['bg'=>'#ffcc00','fg'=>'#000','txt'=>'DHL'],
                  'Estafeta'           => ['bg'=>'#003087','fg'=>'#fff','txt'=>'Estafeta'],
                  'FedEx'              => ['bg'=>'#4d148c','fg'=>'#fff','txt'=>'FedEx'],
                  'Paquetería Express' => ['bg'=>'#28a745','fg'=>'#fff','txt'=>'Paq. Express'],
                  'J&T Express'        => ['bg'=>'#d40511','fg'=>'#fff','txt'=>'J&T'],
              ];
              $puede_acciones = (in_array(24, $_SESSION['permisos']) || in_array(40, $_SESSION['permisos'])) &&
                                (in_array(22, $_SESSION['permisos']) || in_array(28, $_SESSION['permisos']) || in_array(40, $_SESSION['permisos']));
              $c = 1; foreach ($ventas_foraneos as $v):
                $paq         = $v['paqueteria'] ?? '';
                $guias_venta = $guias_por_venta[$v['id_venta']] ?? [];
                $pacas_v     = $pacas_por_venta[$v['id_venta']] ?? 1;
                $mult_v      = ($paq === 'Estafeta') ? 2 : 1;
                $requeridas_v = $pacas_v * $mult_v;
                $subidas_v    = count($guias_venta);
                $faltantes_v  = $requeridas_v - $subidas_v;
              ?>
              <tr>
                <td class="text-center text-muted" style="width:40px"><?= $c++ ?></td>
                <td style="white-space:nowrap;width:150px">
                  <small><?= date('d/m/Y', strtotime($v['fecha'])) ?></small><br>
                  <small class="text-muted"><?= date('H:i', strtotime($v['fecha'])) ?></small>
                </td>
                <td>
                  <strong><?= htmlspecialchars($v['cliente']) ?></strong>
                  <?php if (!empty($v['destinatario']) && $v['destinatario'] !== $v['cliente']): ?>
                    <br><small class="text-muted"><i class="fas fa-user mr-1"></i><?= htmlspecialchars($v['destinatario']) ?></small>
                  <?php endif; ?>
                  <?php if (!empty($v['id_pedido'])): ?>
                    <br><span class="badge badge-primary" style="font-size:10px;"><i class="fa fa-layer-group"></i> Pedido #<?= $v['id_pedido'] ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <small>
                    <?= htmlspecialchars($v['calle']) ?>,
                    <?= htmlspecialchars($v['colonia']) ?>,<br>
                    <?= htmlspecialchars($v['municipio']) ?>,
                    <?= htmlspecialchars($v['estado']) ?>
                    CP <?= htmlspecialchars($v['cp']) ?>
                  </small>
                </td>
                <td style="white-space:nowrap">
                  <a href="tel:<?= htmlspecialchars($v['telefono']) ?>" class="font-weight-bold" style="font-size:13px;">
                    <?= htmlspecialchars($v['telefono']) ?>
                  </a>
                </td>
                <td>
                  <?php if (!empty($v['referencia'])): ?>
                    <span class="badge badge-success" style="white-space:normal;text-align:left;"><?= htmlspecialchars($v['referencia']) ?></span>
                  <?php else: ?>
                    <span class="badge badge-secondary">Sin referencia</span>
                  <?php endif; ?>
                </td>
                <td class="text-center" style="width:110px">
                  <?php if ($paq && isset($badges_paq[$paq])): $bp = $badges_paq[$paq]; ?>
                    <span class="badge" style="background:<?= $bp['bg'] ?>;color:<?= $bp['fg'] ?>;font-size:12px;padding:4px 8px;"><?= $bp['txt'] ?></span>
                  <?php elseif ($paq): ?>
                    <span class="badge badge-secondary"><?= htmlspecialchars($paq) ?></span>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td class="text-center" style="width:160px">
                  <?php if (!empty($guias_venta)): ?>
                    <?php foreach ($guias_venta as $g): ?>
                    <div class="d-flex align-items-center justify-content-center gap-1 mb-1">
                      <span class="badge badge-info" style="font-size:11px;">G<?= $g['numero'] ?></span>
                      <a href="<?= $URL ?>/dashboard/guia_pdf/<?= $g['archivo'] ?>" target="_blank"
                         class="btn btn-xs btn-outline-info" title="Ver guía"><i class="fas fa-eye"></i></a>
                      <button class="btn btn-xs btn-outline-danger btn-eliminar-guia-individual"
                              data-id="<?= $g['id'] ?>" data-venta="<?= $v['id_venta'] ?>" title="Eliminar">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <span class="badge badge-warning" style="font-size:11px;">Sin guía</span>
                  <?php endif; ?>
                  <a href="subir_guia.php?id=<?= $v['id_venta'] ?>" class="btn btn-xs btn-primary mt-1">
                    <i class="fas fa-plus"></i> <?= empty($guias_venta) ? 'Agregar' : 'Más' ?>
                  </a>
                  <?php if ($subidas_v > 1): ?>
                  <button class="btn btn-xs btn-danger btn-eliminar-guia mt-1" data-id="<?= $v['id_venta'] ?>">
                    <i class="fas fa-trash"></i> Todas
                  </button>
                  <?php endif; ?>
                </td>
                <td class="text-center" style="width:120px">
                  <?php if ($v['estado_logistico'] === 'ENVIADA'): ?>
                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Enviado</span>
                  <?php elseif ($faltantes_v > 0): ?>
                    <span class="badge badge-danger">Faltan <?= $faltantes_v ?> guía<?= $faltantes_v > 1 ? 's' : '' ?></span>
                  <?php else: ?>
                    <span class="badge badge-info"><i class="fas fa-check-double mr-1"></i>Guías OK</span>
                  <?php endif; ?>
                </td>
                <?php if ($puede_acciones): ?>
                <td class="text-center" style="width:90px;white-space:nowrap">
                  <?php if (in_array(22, $_SESSION['permisos']) || in_array(40, $_SESSION['permisos'])): ?>
                    <a href="<?= $URL ?>/ventas/edit.php?id=<?= $v['id_venta'] ?>"
                       class="btn btn-warning btn-sm" title="Editar">
                      <i class="fa fa-edit"></i>
                    </a>
                  <?php endif; ?>
                  <?php if (in_array(28, $_SESSION['permisos']) || in_array(40, $_SESSION['permisos'])): ?>
                    <button class="btn btn-danger btn-sm delete-venta"
                            data-id="<?= $v['id_venta'] ?>" data-escaneadas="0" title="Eliminar">
                      <i class="fa fa-trash"></i>
                    </button>
                  <?php endif; ?>
                </td>
                <?php endif; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </div><!-- /.table-responsive -->
        </div>
      </div>

      <?php endif; ?>
    </div>
  </div>
</div>

<script>
$(document).on('click', '.btn-reemplazar-guia', function () {
  let id = $(this).data('id');

  $('#modal_id_venta').val(id);
  $('#modalReemplazarGuia').modal('show');
});
</script>

<script>
$('#formReemplazarGuia').on('submit', function(e){
  e.preventDefault();

  let formData = new FormData(this);

  $.ajax({
    url: '<?= $URL ?>/app/controllers/dashboard/reemplazar_guia.php',
    type: 'POST',
    data: formData,
    contentType: false,
    processData: false,

    success: function(){
      Swal.fire('Listo','Guía reemplazada correctamente','success')
        .then(()=>location.reload());
    },

    error: function(){
      Swal.fire('Error','No se pudo reemplazar la guía','error');
    }
  });
});
</script>




<?php
include('../layout/mensajes.php');
include('../layout/parte2.php');
?>

<script>
// ELIMINAR GUÍA INDIVIDUAL (tb_ventas_guias)
$(document).on('click', '.btn-eliminar-guia-individual', function () {
  const id       = $(this).data('id');
  const id_venta = $(this).data('venta');

  Swal.fire({
    title: '¿Eliminar esta guía?',
    text: 'Esta acción no se puede deshacer',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc3545'
  }).then(result => {
    if (!result.isConfirmed) return;
    $.ajax({
      url: '<?= $URL ?>/app/controllers/dashboard/eliminar_guia_individual.php',
      type: 'POST',
      dataType: 'json',
      data: { id, id_venta },
      success: function(r) {
        if (r.success) {
          Swal.fire({ icon: 'success', title: 'Guía eliminada', timer: 1500, showConfirmButton: false })
            .then(() => location.reload());
        } else {
          Swal.fire('Error', r.msg || 'No se pudo eliminar', 'error');
        }
      },
      error: function() {
        Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
      }
    });
  });
});

// ELIMINAR GUÍA (flujo antiguo - compatibilidad)
$(document).on('click', '.btn-eliminar-guia', function () {
  let id_venta = $(this).data('id');

  Swal.fire({
    title: '¿Eliminar guía?',
    text: 'Esta acción no se puede deshacer',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {

    if (result.isConfirmed) {

      $.ajax({
        url: '<?= $URL ?>/app/controllers/dashboard/eliminar_guia.php',
        type: 'POST',
        dataType: 'json',
        data: { id_venta: id_venta },

        success: function (r) {
          if (r.success) {
            Swal.fire('Guía eliminada correctamente', r.message, 'success')
              .then(() => location.reload());
          } else {
            Swal.fire('Error al eliminar guia', r.message, 'error');
          }
        },

        error: function () {
          Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        }
      });

    }

  });

});

</script>

<!-- DATATABLES -->
<script>
  $(function () {
    $("#ventas").DataTable({
      "responsive": false, "lengthChange": false, "autoWidth": false, "scrollX": true,
      "buttons": [{ 
        extend: 'collection',
        text: 'Export',
        orientation: 'landscape',
        buttons: [{
          text: 'Copy',
          extend: 'copy',
          exportOptions: {
            columns: ':visible',
          modifier: {
            search: 'applied',
            order: 'applied',
            page: 'all'
          }
        } 
        },{
          text: 'Excel',
          extend: 'excel',
          exportOptions: {
            columns: ':visible',
          modifier: {
            search: 'applied',
            order: 'applied',
            page: 'all'
          }
        } 
        },{
          text: 'PDF',
          extend: 'pdf',
          exportOptions: {
            columns: ':visible',
          modifier: {
            search: 'applied',
            order: 'applied',
            page: 'all'
          }
        } 
        },{
          text: 'Print',
          extend: 'print',
          exportOptions: {
            columns: ':visible',
          modifier: {
            search: 'applied',
            order: 'applied',
            page: 'all'
          }
        } 
        }]
      },
      {
        extend: 'colvis',
        text: 'Columns',
        collectionLayout: 'fixed three-column'
      }
      ],
    }).buttons().container().appendTo('#ventas_wrapper .col-md-6:eq(0)');
  });
</script>

<!-- ELIMINAR VENTA -->
<script>
$(document).on('click', '.delete-venta', function () {

  let id_venta = $(this).data('id');

  Swal.fire({
    title: '¿Eliminar venta?',
    text: 'El stock será devuelto a bodega',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {

    if (result.isConfirmed) {

      $.ajax({
        url: '<?= $URL ?>/app/controllers/ventas/delete_venta.php',
        type: 'POST',
        dataType: 'json',
        data: { id_venta: id_venta },

        success: function (r) {
          if (r.success) {
            Swal.fire('Eliminada', r.message, 'success');
            $('button[data-id="'+id_venta+'"]').closest('tr').fadeOut();
          } else {
            Swal.fire('Error', r.message, 'error');
          }
        },

        error: function () {
          Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        }
      });

    }

  });

});
</script>