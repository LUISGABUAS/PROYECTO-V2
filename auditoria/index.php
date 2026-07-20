<?php
include('../app/config.php');
include('../layout/sesion.php');

if (!in_array(24, $_SESSION['permisos'])) {
    header("Location: " . $URL); exit;
}

include('../layout/parte1.php');

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$buscar_usuario = trim($_GET['usuario'] ?? '');
$buscar_accion  = trim($_GET['accion']  ?? '');

$params = [':desde' => $desde, ':hasta' => $hasta];
$where  = "DATE(fecha_hora) BETWEEN :desde AND :hasta";

if ($buscar_usuario !== '') {
    $where .= " AND nombre_usuario LIKE :usuario";
    $params[':usuario'] = "%$buscar_usuario%";
}
if ($buscar_accion !== '') {
    $where .= " AND accion LIKE :accion";
    $params[':accion'] = "%$buscar_accion%";
}

$stmt = $pdo->prepare("SELECT * FROM tb_auditoria WHERE $where ORDER BY fecha_hora DESC");
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Conteos por tipo
$conteos = ['CREAR' => 0, 'EDITAR' => 0, 'ELIMINAR' => 0, 'LOGIN' => 0, 'OTRO' => 0];
foreach ($registros as $r) {
    $a = strtoupper($r['accion'] ?? '');
    if (str_contains($a, 'CREAR') || str_contains($a, 'CREAR')) $conteos['CREAR']++;
    elseif (str_contains($a, 'ACTUALIZ') || str_contains($a, 'EDITAR') || str_contains($a, 'UPDATE')) $conteos['EDITAR']++;
    elseif (str_contains($a, 'ELIMIN') || str_contains($a, 'BORR') || str_contains($a, 'DELETE')) $conteos['ELIMINAR']++;
    elseif (str_contains($a, 'LOGIN') || str_contains($a, 'INGRESO')) $conteos['LOGIN']++;
    else $conteos['OTRO']++;
}

function badgeAccion($accion) {
    $a = strtoupper($accion);
    if (str_contains($a, 'LOGIN') || str_contains($a, 'INGRESO'))
        return ['success', 'fa-sign-in-alt'];
    if (str_contains($a, 'CREAR') || str_contains($a, 'CREAR') || str_contains($a, 'REGISTR'))
        return ['primary', 'fa-plus-circle'];
    if (str_contains($a, 'ACTUALIZ') || str_contains($a, 'EDITAR') || str_contains($a, 'UPDATE') || str_contains($a, 'MODIF'))
        return ['warning', 'fa-edit'];
    if (str_contains($a, 'ELIMIN') || str_contains($a, 'BORR') || str_contains($a, 'DELETE'))
        return ['danger', 'fa-trash'];
    return ['secondary', 'fa-circle'];
}
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
            <li class="breadcrumb-item"><a href="<?= $URL ?>">Home</a></li>
            <li class="breadcrumb-item active">Auditoría</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <!-- RESUMEN -->
      <div class="row mb-3">
        <div class="col-6 col-md col-lg">
          <div class="info-box mb-2">
            <span class="info-box-icon bg-dark"><i class="fas fa-list"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Total</span>
              <span class="info-box-number"><?= count($registros) ?></span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md col-lg">
          <div class="info-box mb-2">
            <span class="info-box-icon bg-success"><i class="fas fa-sign-in-alt"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Ingresos</span>
              <span class="info-box-number"><?= $conteos['LOGIN'] ?></span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md col-lg">
          <div class="info-box mb-2">
            <span class="info-box-icon bg-primary"><i class="fas fa-plus-circle"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Creados</span>
              <span class="info-box-number"><?= $conteos['CREAR'] ?></span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md col-lg">
          <div class="info-box mb-2">
            <span class="info-box-icon bg-warning"><i class="fas fa-edit"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Editados</span>
              <span class="info-box-number"><?= $conteos['EDITAR'] ?></span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md col-lg">
          <div class="info-box mb-2">
            <span class="info-box-icon bg-danger"><i class="fas fa-trash"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Eliminados</span>
              <span class="info-box-number"><?= $conteos['ELIMINAR'] ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- FILTROS -->
      <div class="card card-outline card-primary mb-3">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-filter"></i> Filtros</h3></div>
        <div class="card-body">
          <form method="GET" class="row align-items-end g-2">
            <div class="col-md-2">
              <label class="mb-1"><strong>Desde</strong></label>
              <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>">
            </div>
            <div class="col-md-2">
              <label class="mb-1"><strong>Hasta</strong></label>
              <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>">
            </div>
            <div class="col-md-3">
              <label class="mb-1"><strong>Usuario</strong></label>
              <input type="text" name="usuario" class="form-control" placeholder="Nombre del usuario..."
                     value="<?= htmlspecialchars($buscar_usuario) ?>">
            </div>
            <div class="col-md-3">
              <label class="mb-1"><strong>Acción</strong></label>
              <input type="text" name="accion" class="form-control" placeholder="LOGIN, CREAR, ELIMINAR..."
                     value="<?= htmlspecialchars($buscar_accion) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
              <button type="submit" class="btn btn-primary mr-1"><i class="fas fa-search"></i> Filtrar</button>
              <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-undo"></i></a>
            </div>
          </form>
        </div>
      </div>

      <!-- TABLA -->
      <div class="card card-outline card-info">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fas fa-list"></i> Registros
            <span class="badge badge-info ml-2"><?= count($registros) ?></span>
          </h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table id="tablaAuditoria" class="table table-bordered table-hover mb-0" style="font-size:13px;">
              <thead>
                <tr class="text-center" style="background:#343a40;color:#fff;">
                  <th style="width:40px">#</th>
                  <th style="width:140px">Usuario</th>
                  <th style="width:160px">Acción</th>
                  <th style="width:130px">Tabla</th>
                  <th style="width:70px">ID</th>
                  <th>Detalle</th>
                  <th style="width:110px">IP</th>
                  <th style="width:140px">Fecha / Hora</th>
                </tr>
              </thead>
              <tbody>
                <?php $num = 1; foreach ($registros as $reg):
                  [$color, $icon] = badgeAccion($reg['accion'] ?? '');
                  $hora = date('d/m/Y H:i:s', strtotime($reg['fecha_hora']));
                ?>
                <tr>
                  <td class="text-center text-muted"><?= $num++ ?></td>
                  <td>
                    <i class="fas fa-user-circle text-secondary mr-1"></i>
                    <strong><?= htmlspecialchars($reg['nombre_usuario'] ?? '—') ?></strong>
                  </td>
                  <td class="text-center">
                    <span class="badge badge-<?= $color ?>" style="font-size:11px;padding:5px 8px;white-space:normal;">
                      <i class="fas <?= $icon ?> mr-1"></i><?= htmlspecialchars($reg['accion']) ?>
                    </span>
                  </td>
                  <td class="text-center">
                    <code style="font-size:11px;background:rgba(0,0,0,.06);padding:2px 6px;border-radius:4px;">
                      <?= htmlspecialchars($reg['tabla'] ?? '—') ?>
                    </code>
                  </td>
                  <td class="text-center">
                    <?php if (!empty($reg['id_registro'])): ?>
                    <span class="badge badge-light border">#<?= htmlspecialchars($reg['id_registro']) ?></span>
                    <?php else: ?>—<?php endif; ?>
                  </td>
                  <td style="max-width:300px;"><?= htmlspecialchars($reg['detalle'] ?? '—') ?></td>
                  <td class="text-center"><small class="text-muted"><?= htmlspecialchars($reg['ip'] ?? '—') ?></small></td>
                  <td class="text-center">
                    <small><i class="far fa-clock mr-1 text-muted"></i><?= $hora ?></small>
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

<script>
$(function () {
  $("#tablaAuditoria").DataTable({
    "responsive": true, "lengthChange": true, "autoWidth": false,
    "order": [[7, "desc"]],
    "pageLength": 25,
    "buttons": [
      { extend: 'collection', text: '<i class="fas fa-download"></i> Exportar', buttons: [
        { extend: 'excel', text: '<i class="fas fa-file-excel"></i> Excel', title: 'Auditoría <?= $desde ?> al <?= $hasta ?>' },
        { extend: 'pdf',   text: '<i class="fas fa-file-pdf"></i> PDF', orientation: 'landscape', pageSize: 'A4', title: 'Auditoría <?= $desde ?> al <?= $hasta ?>' },
        { extend: 'print', text: '<i class="fas fa-print"></i> Imprimir' }
      ]},
      { extend: 'colvis', text: '<i class="fas fa-columns"></i> Columnas' }
    ],
    "language": { "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json" }
  }).buttons().container().appendTo('#tablaAuditoria_wrapper .col-md-6:eq(0)');
});
</script>

<?php include('../layout/parte2.php'); ?>
