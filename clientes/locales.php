<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

/* =========================
   CONTROLLER CLIENTES LOCALES
========================= */
include('../app/controllers/clientes/list_locales.php');

if (isset($_SESSION['mensaje'])) {
    $respuesta = $_SESSION['mensaje']; ?>
    <script>
    Swal.fire({
        position: 'top-end',
        icon: 'success',
        title: <?= json_encode($respuesta) ?>,
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
          <h1 class="m-0">Clientes Locales</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= $URL ?>">Home</a></li>
            <li class="breadcrumb-item active">Clientes</li>
            <li class="breadcrumb-item active">Locales</li>
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
              <h3 class="card-title">Listado de clientes Locales</h3>
            </div>

            <!-- FILTRO DE VENDEDORES (solo para admins) -->

            <div class="card-header bg-light border-bottom">
              <div class="row align-items-center">
                <div class="col">
                  <div class="d-flex align-items-center gap-3">
                    <div>
                      <i class="fas fa-filter" style="font-size: 1.2rem; color: #007bff;"></i>
                    </div>
                    <form method="GET" class="d-flex gap-2 align-items-center" style="flex: 1; max-width: 500px;">
                      <label class="mb-0" style="font-weight: 600; min-width: 120px;">Filtrar Clientes:</label>
                      <select name="id_vendedor" class="form-control form-control-sm" onchange="this.form.submit()" style="flex: 1;">
                        <option value="">📋 Todos los vendedores</option>
                        <?php
                        // Obtener lista de vendedores
                        $sql_vendedores = "SELECT us.id, us.nombres FROM tb_usuario us
                                          INNER JOIN tb_roles_permisos rol ON us.id_rol = rol.id_rol
                                          WHERE rol.id_permiso = 21
                                           ORDER BY us.nombres ASC";
                        $stmt_vendedores = $pdo->prepare($sql_vendedores);
                        $stmt_vendedores->execute();
                        $vendedores = $stmt_vendedores->fetchAll(PDO::FETCH_ASSOC);
                        
                        $filtro_actual = $_GET['id_vendedor'] ?? '';
                        foreach ($vendedores as $vendedor):
                            $selected = ($filtro_actual == $vendedor['id']) ? 'selected' : '';
                        ?>
                            <option value="<?= $vendedor['id'] ?>" <?= $selected ?>>
                                👤 <?= htmlspecialchars($vendedor['nombres']) ?>
                            </option>
                        <?php endforeach; ?>
                      </select>
                      <?php if (!empty($filtro_actual)): ?>
                        <a href="?" class="btn btn-sm btn-outline-secondary" title="Limpiar filtro">
                          <i class="fas fa-times"></i>
                        </a>
                      <?php endif; ?>
                    </form>
                    <?php if (!empty($filtro_actual)): ?>
                      <span class="badge bg-success">
                        Filtro activo
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
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
                    <?php foreach ($clientes_locales as $cliente): ?>
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
            var form = $('<form method="POST" action="../app/controllers/clientes/delete_locales.php">' +
                '<input type="hidden" name="id" value="' + id + '">' +
                '<input type="hidden" name="csrf_token" value="' + $('meta[name="csrf-token"]').attr('content') + '">' +
                '</form>');
            $('body').append(form);
            form.submit();
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
