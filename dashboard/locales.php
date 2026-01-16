<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

/* =========================
   CONTROLLER
========================= */
include('../app/controllers/dashboard/locales.php');

/* =========================
   MENSAJES
========================= */
if (isset($_SESSION['mensaje'])) {
  $respuesta = $_SESSION['mensaje']; ?>
  <script>
    Swal.fire({
      position: 'top-end',
      icon: 'success',
      title: '<?= $respuesta ?>',
      showConfirmButton: false,
      timer: 2000
    })
  </script>
<?php unset($_SESSION['mensaje']); }

/* =========================
   PERMISO DE ACCESO
========================= */
if (!in_array(20, $_SESSION['permisos'])) {
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
      <h1 class="m-0">Locales Ventas</h1>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <!-- FILTRO FECHAS -->
      <form method="get" class="row mb-3">
        <div class="col-md-3">
          <input type="date" name="desde" class="form-control" value="<?= $desde ?>">
        </div>
        <div class="col-md-3">
          <input type="date" name="hasta" class="form-control" value="<?= $hasta ?>">
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary">Filtrar</button>
        </div>
      </form>

      <?php if (in_array(24, $_SESSION['permisos'])): ?>

      <!-- TABLA VENTAS -->
      <div class="card card-outline card-primary">
        <div class="card-header">
          <h3 class="card-title">Reporte de Ventas Locales</h3>
        </div>

        <div class="card-body">
          <table id="ventas" class="table table-bordered table-striped table-sm">
            <thead>
              <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Domicilio</th>
                <th>Telefono</th>
                <th>Referencia</th>
                <th>Estado</th>


                <?php if (
                  in_array(24, $_SESSION['permisos']) &&
                  (in_array(22, $_SESSION['permisos']) || in_array(28, $_SESSION['permisos']))
                ): ?>
                  <th>Acciones</th>
                <?php endif; ?>
              </tr>
            </thead>

            <tbody>
              <?php $c = 1; foreach ($ventas_locales as $v): ?>
                <tr>
                  <td><?= $c++ ?></td>
                  <td><?= $v['fecha'] ?></td>
                  <td><?= $v['cliente'] ?></td>

                  <?php if (in_array(24, $_SESSION['permisos'])): ?>
                    <td><?= $v['calle'] ?>, <?= $v['colonia'] ?>, <?= $v['municipio'] ?>, <?= $v['estado'] ?>, <?= $v['cp'] ?></td>
                  <?php endif; ?>

                  <td><?= ($v['telefono']) ?></td>
                  <td><?php if (!empty($v['referencia']) && $v['referencia'] !== ''): ?>
                                                    <span class="badge badge-success"><?= $v['referencia'] ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Sin referencia</span>
                                                <?php endif; ?> </td>
                  <td><?php if ($v['estado_logistico'] == 'SIN ENVIO'):   ?>
                <span class="badge badge-warning">Sin envio</span>
                <?php elseif ($v['estado_logistico'] == 'ENVIADA'): ?>
                <span class="badge badge-primary">Enviado</span>
                <?php endif; ?></td>
                

                  <?php if (
                    in_array(24, $_SESSION['permisos']) &&
                    (in_array(22, $_SESSION['permisos']) || in_array(28, $_SESSION['permisos']))
                  ): ?>
                    <td>
                      <center>
                        <?php if (in_array(22, $_SESSION['permisos'])): ?>
                          <a href="<?= $URL ?>/ventas/edit.php?id=<?= $v['id_venta'] ?>"
                             class="btn btn-warning btn-sm">
                            <i class="fa fa-edit"></i>
                          </a>
                        <?php endif; ?>

                        <?php if (in_array(28, $_SESSION['permisos'])): ?>
                          <button class="btn btn-danger btn-sm delete-venta"
                                  data-id="<?= $v['id_venta'] ?>">
                            <i class="fa fa-trash"></i>
                          </button>
                        <?php endif; ?>
                      </center>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
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
// ELIMINAR GUÍA
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
      "responsive": true, "lengthChange": false, "autoWidth": false,
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