<?php
include('../app/config.php');
include('../layout/sesion.php');

if (!isset($_SESSION['id_usuario_sesion']) || !in_array(1, $_SESSION['permisos'] ?? [])) {
    header('Location: ' . $URL); exit;
}

include('../layout/parte1.php');

$tipo   = $_GET['tipo']  ?? 'error_500';
$fecha  = $_GET['fecha'] ?? date('Y-m-d');
$limpio = $_GET['limpiar'] ?? false;

if ($limpio && $_SESSION['id_usuario_sesion']) {
    $ruta_logs = __DIR__ . '/../app/../logs';
    foreach (glob($ruta_logs . "/log_{$tipo}_{$fecha}.jsonl") as $archivo) @unlink($archivo);
    $_SESSION['mensaje'] = "Logs limpiados correctamente";
    $_SESSION['icono']   = "success";
    header("Location: ?tipo=$tipo&fecha=$fecha"); exit;
}

$tipos_config = [
    'error_500' => ['label' => 'Errores 500',    'color' => 'danger',  'icon' => 'fa-times-circle'],
    'error_400' => ['label' => 'Errores 400',    'color' => 'warning', 'icon' => 'fa-exclamation-circle'],
    'database'  => ['label' => 'Cambios en BD',  'color' => 'info',    'icon' => 'fa-database'],
    'auth'      => ['label' => 'Autenticación',  'color' => 'success', 'icon' => 'fa-lock'],
    'critical'  => ['label' => 'Críticos',       'color' => 'danger',  'icon' => 'fa-skull-crossbones'],
    'info'      => ['label' => 'Informativos',   'color' => 'primary', 'icon' => 'fa-info-circle'],
];

$logs       = Logger::getLogs($tipo, 500, $fecha);
$cfg_actual = $tipos_config[$tipo] ?? ['label' => $tipo, 'color' => 'secondary', 'icon' => 'fa-file-alt'];

// Conteos para el resumen
$conteos = [];
foreach ($tipos_config as $t => $cfg) {
    $conteos[$t] = count(Logger::getLogs($t, 1000, $fecha));
}

function extraerResumen($log, $tipo) {
    if ($tipo === 'error_500' || $tipo === 'critical') {
        return $log['message'] ?? $log['error'] ?? $log['exception'] ?? '—';
    }
    if ($tipo === 'auth') {
        $accion = $log['action'] ?? $log['event'] ?? '';
        $user   = $log['email'] ?? $log['user_id'] ?? $log['usuario'] ?? '';
        return trim("$accion — $user");
    }
    if ($tipo === 'database') {
        return $log['query'] ?? $log['action'] ?? $log['message'] ?? '—';
    }
    return $log['message'] ?? $log['msg'] ?? json_encode($log);
}
?>

<style>
.log-row { transition: background .1s; }
.log-row:hover { background: rgba(0,0,0,.03); }
.log-detail {
  background: #1e1e2e; color: #cdd6f4;
  font-size: 11px; border-radius: 8px;
  padding: 12px 16px; margin-top: 8px;
  overflow-x: auto; max-height: 320px;
  overflow-y: auto;
}
.log-detail .key   { color: #89b4fa; }
.log-detail .str   { color: #a6e3a1; }
.log-detail .num   { color: #fab387; }
.log-detail .null  { color: #f38ba8; }
.tipo-tab {
  border: none; background: transparent;
  padding: 8px 14px; border-radius: 8px;
  font-size: 13px; font-weight: 600;
  cursor: pointer; transition: all .15s;
  display: flex; align-items: center; gap: 6px;
}
.tipo-tab:hover     { background: rgba(0,0,0,.07); }
.tipo-tab.active    { color: #fff; }
.badge-count {
  font-size: 11px; padding: 1px 7px;
  border-radius: 20px; font-weight: 700;
}
</style>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0"><i class="fas fa-satellite-dish text-primary"></i> Observabilidad del Sistema</h1>
        <ol class="breadcrumb m-0">
          <li class="breadcrumb-item"><a href="<?= $URL ?>">Home</a></li>
          <li class="breadcrumb-item active">Logs</li>
        </ol>
      </div>
    </div>
  </div>

  <?php if (isset($_SESSION['mensaje'])): ?>
  <div class="alert alert-<?= $_SESSION['icono'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mx-3">
    <i class="fas fa-check-circle mr-2"></i><?= $_SESSION['mensaje'] ?>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
  </div>
  <?php unset($_SESSION['mensaje'], $_SESSION['icono']); endif; ?>

  <div class="content">
    <div class="container-fluid">

      <!-- RESUMEN CARDS -->
      <div class="row mb-3">
        <?php foreach ($tipos_config as $t => $cfg): ?>
        <div class="col-6 col-md-4 col-lg-2">
          <a href="?tipo=<?= $t ?>&fecha=<?= $fecha ?>" style="text-decoration:none;">
            <div class="info-box mb-2 <?= $t === $tipo ? 'shadow' : '' ?>"
                 style="<?= $t === $tipo ? 'border:2px solid var(--' . $cfg['color'] . ')' : 'opacity:.75' ?>">
              <span class="info-box-icon bg-<?= $cfg['color'] ?>" style="font-size:18px;">
                <i class="fas <?= $cfg['icon'] ?>"></i>
              </span>
              <div class="info-box-content">
                <span class="info-box-text" style="font-size:11px;"><?= $cfg['label'] ?></span>
                <span class="info-box-number"><?= $conteos[$t] ?></span>
              </div>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- FILTROS -->
      <div class="card card-outline card-<?= $cfg_actual['color'] ?> mb-3">
        <div class="card-body py-2">
          <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
            <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
            <div class="d-flex align-items-center gap-2">
              <label class="mb-0 font-weight-bold">Fecha:</label>
              <input type="date" name="fecha" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($fecha) ?>"
                     onchange="this.form.submit()">
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-sm btn-<?= $cfg_actual['color'] ?>">
                <i class="fas fa-sync-alt"></i> Actualizar
              </button>
              <a href="?tipo=<?= $tipo ?>&fecha=<?= $fecha ?>&limpiar=1"
                 class="btn btn-sm btn-outline-danger"
                 onclick="return confirm('¿Borrar los logs de <?= $cfg_actual['label'] ?> del <?= $fecha ?>?')">
                <i class="fas fa-trash"></i> Limpiar
              </a>
            </div>
          </form>
        </div>
      </div>

      <!-- LISTA DE LOGS -->
      <div class="card card-outline card-<?= $cfg_actual['color'] ?>">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fas <?= $cfg_actual['icon'] ?> text-<?= $cfg_actual['color'] ?> mr-2"></i>
            <?= $cfg_actual['label'] ?>
            <span class="badge badge-<?= $cfg_actual['color'] ?> ml-2"><?= count($logs) ?></span>
            <small class="text-muted ml-2" style="font-size:12px;"><?= date('d/m/Y', strtotime($fecha)) ?></small>
          </h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
          </div>
        </div>
        <div class="card-body p-0">

          <?php if (empty($logs)): ?>
          <div class="text-center py-5 text-muted">
            <i class="fas fa-check-circle fa-3x mb-3 text-success d-block"></i>
            <strong>Sin registros</strong><br>
            <small>No hay logs de <?= $cfg_actual['label'] ?> para el <?= date('d/m/Y', strtotime($fecha)) ?></small>
          </div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" id="tablaLogs">
              <thead style="background:#343a40;color:#fff;">
                <tr>
                  <th style="width:100px">Hora</th>
                  <th>Resumen</th>
                  <th style="width:120px">IP</th>
                  <th style="width:130px">Usuario</th>
                  <th style="width:60px" class="text-center">Detalle</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_reverse($logs) as $i => $log): ?>
                <tr class="log-row">
                  <td>
                    <code style="font-size:11px;">
                      <?= isset($log['timestamp']) ? date('H:i:s', strtotime($log['timestamp'])) : '—' ?>
                    </code>
                  </td>
                  <td style="max-width:500px;">
                    <span style="font-size:13px;"><?= htmlspecialchars(extraerResumen($log, $tipo)) ?></span>
                  </td>
                  <td><small class="text-muted"><?= htmlspecialchars($log['ip'] ?? $log['REMOTE_ADDR'] ?? '—') ?></small></td>
                  <td><small><?= htmlspecialchars($log['user_id'] ?? $log['email'] ?? $log['usuario'] ?? '—') ?></small></td>
                  <td class="text-center">
                    <button class="btn btn-xs btn-outline-secondary"
                            onclick="toggleDetalle(this)"
                            data-json="<?= htmlspecialchars(json_encode($log, JSON_UNESCAPED_UNICODE)) ?>">
                      <i class="fas fa-code"></i>
                    </button>
                  </td>
                </tr>
                <tr class="detalle-row" style="display:none;" id="det-<?= $i ?>">
                  <td colspan="5" class="p-0">
                    <pre class="log-detail" id="pre-<?= $i ?>"></pre>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>

        </div>
      </div>

    </div>
  </div>
</div>

<script>
function toggleDetalle(btn) {
  const tr   = btn.closest('tr');
  const next = tr.nextElementSibling;
  const idx  = btn.closest('tr').querySelector('button').getAttribute('onclick')
               .match(/\d+/)?.[0]; // no es necesario, usamos DOM

  // Buscar la fila de detalle siguiente
  if (!next || !next.classList.contains('detalle-row')) return;

  if (next.style.display === 'none') {
    const preEl = next.querySelector('pre');
    if (!preEl.textContent.trim()) {
      const raw = JSON.parse(btn.dataset.json);
      preEl.textContent = JSON.stringify(raw, null, 2);
    }
    next.style.display = '';
    btn.innerHTML = '<i class="fas fa-times"></i>';
  } else {
    next.style.display = 'none';
    btn.innerHTML = '<i class="fas fa-code"></i>';
  }
}
</script>

<?php include('../layout/parte2.php'); ?>
