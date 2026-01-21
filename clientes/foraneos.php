<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

/* =========================
   CONTROLLER CLIENTES FORANEOS
========================= */
include('../app/controllers/clientes/list_foraneos.php');

if (isset($_SESSION['mensaje'])) {
    $respuesta = $_SESSION['mensaje']; ?>
    <script>
    Swal.fire({
        position: 'top-end',
        icon: 'success',
        title: '<?= $respuesta ?>',
        showConfirmButton: false,
        timer: 2000
    });
    </script>
<?php
    unset($_SESSION['mensaje']);
}
?>

<?php if (in_array(11, $_SESSION['permisos'])): ?>

<div class="content-wrapper">

  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Clientes Foraneos</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= $URL ?>">Home</a></li>
            <li class="breadcrumb-item active">Clientes</li>
            <li class="breadcrumb-item active">Foraneos</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="row">
        <div class="col-md-12">

          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title">Listado de clientes Foraneos</h3>
            </div>

            <div class="card-body">

              <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped table-sm">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Nombre</th>
                      <th>Teléfono</th>
                      <th>Dirección</th>
                      <th>Referencias</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $contador = 1; ?>
                    <?php foreach ($clientes_foraneos as $cliente): ?>
                      <tr>
                        <td><?= $contador++ ?></td>
                        <td><?= htmlspecialchars($cliente['nombre_completo']) ?></td>
                        <td><?= htmlspecialchars($cliente['telefono']) ?></td>
                        <td>
                          <?= htmlspecialchars($cliente['calle_numero']) ?><br>
                          <?= htmlspecialchars($cliente['colonia']) ?><br>
                          <?= htmlspecialchars($cliente['municipio']) ?>,
                          <?= htmlspecialchars($cliente['estado']) ?><br>
                          CP <?= htmlspecialchars($cliente['cp']) ?>
                        </td>
                        <td>
                          <?= !empty(trim($cliente['referencias'])) 
                                ? nl2br(htmlspecialchars($cliente['referencias'])) 
                                : '<span class="text-muted">Sin referencias</span>' ?>
                        </td>

                        <td>
                           <center> <a href="edit.php?id=<?= $cliente['id_cliente'] ?>" class="btn btn-warning ">
                                <i class="fas fa-edit">Edit</i>
                            </a>

                            <button class="btn btn-danger"
                                onclick="eliminarCliente(<?= $cliente['id_cliente'] ?>)">
                                <i class="fas fa-trash">Eliminate</i>
                            </button>
                            </center>
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

    </div>
  </div>

</div>

<?php include('../layout/parte2.php'); ?>

<script>
$(function () {
  $("#example1").DataTable({
    responsive: true,
    lengthChange: false,
    autoWidth: false,
    buttons: ["copy", "excel", "pdf", "print"]
  }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
});
</script>

<script>
function eliminarCliente(id) {
    Swal.fire({
        title: '¿Eliminar cliente?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href =
                '../app/controllers/clientes/delete_foraneos.php?id=' + id;
        }
    })
}
</script>


<?php else: ?>

<?php include('../layout/parte2.php'); ?>
<script>
Swal.fire({
  icon: "error",
  title: "Access Denied",
  text: "You do not have permission to access this page.",
  showConfirmButton: false,
  timer: 3000
}).then(() => {
  window.location = "<?= $URL ?>";
});
</script>

<?php endif; ?>
