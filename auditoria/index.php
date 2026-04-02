<?php
include('../app/config.php');
include('../layout/sesion.php');

if (!in_array(24, $_SESSION['permisos'])) {
    header("Location: " . $URL);
    exit;
}

include('../layout/parte1.php');

// Filtros de fecha (default: mes actual)
$desde = isset($_GET['desde']) && $_GET['desde'] !== '' ? $_GET['desde'] : date('Y-m-01');
$hasta = isset($_GET['hasta']) && $_GET['hasta'] !== '' ? $_GET['hasta'] : date('Y-m-d');

// Query con filtros
$stmt = $pdo->prepare("SELECT * FROM tb_auditoria WHERE DATE(fecha_hora) BETWEEN :desde AND :hasta ORDER BY fecha_hora DESC");
$stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">

  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0"><i class="fas fa-history text-primary"></i> Historial de Cambios</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?php echo $URL; ?>">Home</a></li>
            <li class="breadcrumb-item active">Auditoría</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <!-- Filtros -->
      <div class="card card-outline card-primary mb-3">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-filter"></i> Filtros</h3>
        </div>
        <div class="card-body">
          <form method="GET" action="" class="form-inline">
            <div class="form-group mr-3">
              <label class="mr-2"><strong>Desde:</strong></label>
              <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>">
            </div>
            <div class="form-group mr-3">
              <label class="mr-2"><strong>Hasta:</strong></label>
              <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>">
            </div>
            <button type="submit" class="btn btn-primary mr-2">
              <i class="fas fa-search"></i> Filtrar
            </button>
            <a href="index.php" class="btn btn-outline-secondary">
              <i class="fas fa-undo"></i> Reiniciar
            </a>
          </form>
        </div>
      </div>

      <!-- Tabla -->
      <div class="card card-outline card-info">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fas fa-list"></i> Registros de Auditoría
            <span class="badge badge-info ml-2"><?= count($registros) ?></span>
          </h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tablaAuditoria" class="table table-bordered table-striped table-sm">
              <thead class="thead-light">
                <tr>
                  <th>#</th>
                  <th>Usuario</th>
                  <th>Acción</th>
                  <th>Tabla</th>
                  <th>ID Registro</th>
                  <th>Detalle</th>
                  <th>IP</th>
                  <th>Fecha/Hora</th>
                </tr>
              </thead>
              <tbody>
                <?php $num = 1; foreach ($registros as $reg): ?>
                <tr>
                  <td><?= $num++ ?></td>
                  <td><?= htmlspecialchars($reg['nombre_usuario'] ?? '-') ?></td>
                  <td>
                    <span class="badge badge-<?= strpos($reg['accion'], 'LOGIN') !== false ? 'success' : (strpos($reg['accion'], 'ELIMIN') !== false ? 'danger' : 'primary') ?>">
                      <?= htmlspecialchars($reg['accion']) ?>
                    </span>
                  </td>
                  <td><?= htmlspecialchars($reg['tabla'] ?? '-') ?></td>
                  <td class="text-center"><?= htmlspecialchars($reg['id_registro'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($reg['detalle'] ?? '-') ?></td>
                  <td><small><?= htmlspecialchars($reg['ip'] ?? '-') ?></small></td>
                  <td><small><?= htmlspecialchars($reg['fecha_hora']) ?></small></td>
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

<script>
$(function () {
  $("#tablaAuditoria").DataTable({
    "responsive": true,
    "lengthChange": true,
    "autoWidth": false,
    "order": [[7, "desc"]],
    "buttons": [
      {
        extend: 'collection',
        text: 'Exportar',
        buttons: [
          { extend: 'excel', text: 'Excel', title: 'Auditoría <?= $desde ?> al <?= $hasta ?>' },
          { extend: 'pdf',   text: 'PDF',   title: 'Auditoría <?= $desde ?> al <?= $hasta ?>', orientation: 'landscape', pageSize: 'A4' },
          { extend: 'print', text: 'Imprimir' }
        ]
      },
      { extend: 'colvis', text: 'Columnas' }
    ],
    "language": {
      "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json"
    }
  }).buttons().container().appendTo('#tablaAuditoria_wrapper .col-md-6:eq(0)');
});
</script>

<?php include('../layout/parte2.php'); ?>
