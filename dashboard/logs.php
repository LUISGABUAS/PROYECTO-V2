<?php
include('../app/config.php');
include('../layout/sesion.php');

// Solo para administradores
if (!isset($_SESSION['id_usuario_sesion']) || !in_array(1, $_SESSION['permisos'] ?? [])) {
    header('Location: ' . $URL);
    exit;
}

include('../layout/parte1.php');

// Obtener tipo de log solicitado
$tipo = $_GET['tipo'] ?? 'error_500';
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$limpio = $_GET['limpiar'] ?? false;

// Permitir limpiar logs
if ($limpio && $_SESSION['id_usuario_sesion']) {
    $ruta_logs = __DIR__ . '/../app/../logs';
    $patron = "log_{$tipo}_{$fecha}.jsonl";
    
    foreach (glob($ruta_logs . '/' . $patron) as $archivo) {
        @unlink($archivo);
    }
    
    $_SESSION['mensaje'] = "✅ Logs limpios correctamente";
    $_SESSION['icono'] = "success";
    header("Location: ?tipo=$tipo");
    exit;
}

// Obtener logs
$logs = Logger::getLogs($tipo, 500, $fecha);
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">📊 Observabilidad del Proyecto</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= $URL ?>">Home</a></li>
            <li class="breadcrumb-item active">Logs</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <?php if (isset($_SESSION['mensaje'])): ?>
  <div class="alert alert-<?= $_SESSION['icono'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
    <?= $_SESSION['mensaje'] ?>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
  </div>
  <?php unset($_SESSION['mensaje'], $_SESSION['icono']); endif; ?>

  <div class="content">
    <div class="container-fluid">

      <!-- Filtros -->
      <div class="card card-outline card-primary mb-3">
        <div class="card-header">
          <h3 class="card-title">🔍 Filtros</h3>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label>Tipo de Log</label>
                <select id="tipo" class="form-control" onchange="actualizarFiltro()">
                  <option value="error_500" <?= $tipo === 'error_500' ? 'selected' : '' ?>>❌ Errores 500</option>
                  <option value="error_400" <?= $tipo === 'error_400' ? 'selected' : '' ?>>⚠️ Errores 400</option>
                  <option value="database" <?= $tipo === 'database' ? 'selected' : '' ?>>💾 Cambios en BD</option>
                  <option value="auth" <?= $tipo === 'auth' ? 'selected' : '' ?>>🔐 Autenticación</option>
                  <option value="critical" <?= $tipo === 'critical' ? 'selected' : '' ?>>🚨 Críticos</option>
                  <option value="info" <?= $tipo === 'info' ? 'selected' : '' ?>>ℹ️ Informativos</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>Fecha</label>
                <input type="date" id="fecha" value="<?= $fecha ?>" class="form-control" onchange="actualizarFiltro()">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>&nbsp;</label>
                <div>
                  <button class="btn btn-primary" onclick="actualizarFiltro()">🔄 Actualizar</button>
                  <a href="?tipo=<?= $tipo ?>&fecha=<?= $fecha ?>&limpiar=1" class="btn btn-danger" onclick="return confirm('¿Borrar logs de este día?')">🗑️ Limpiar logs</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Resumen -->
      <div class="row">
        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Errores 500</span>
              <span class="info-box-number"><?= count(Logger::getLogs('error_500', 1000, $fecha)) ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-circle"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Errores 400</span>
              <span class="info-box-number"><?= count(Logger::getLogs('error_400', 1000, $fecha)) ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-database"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Cambios BD</span>
              <span class="info-box-number"><?= count(Logger::getLogs('database', 1000, $fecha)) ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-lock"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Auth</span>
              <span class="info-box-number"><?= count(Logger::getLogs('auth', 1000, $fecha)) ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla de logs -->
      <div class="card card-outline card-primary">
        <div class="card-header">
          <h3 class="card-title">📋 <?= ucfirst($tipo) ?> - <?= $fecha ?></h3>
        </div>
        <div class="card-body" style="overflow-x: auto;">
          <?php if (empty($logs)): ?>
            <div class="alert alert-info">No hay logs para los filtros seleccionados</div>
          <?php else: ?>
            <table class="table table-sm table-striped table-hover">
              <thead>
                <tr>
                  <th>Hora</th>
                  <th>Detalles</th>
                  <th>IP</th>
                  <th>Usuario</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($logs as $log): ?>
                  <tr>
                    <td><small><?= $log['timestamp'] ?? '' ?></small></td>
                    <td>
                      <details>
                        <summary>📄 Ver detalles</summary>
                        <pre style="background:#f5f5f5;padding:10px;border-radius:4px;overflow-x:auto;font-size:11px;"><?= json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
                      </details>
                    </td>
                    <td><small><?= $log['ip'] ?? $log['REMOTE_ADDR'] ?? '-' ?></small></td>
                    <td><small><?= $log['user_id'] ?? $log['email'] ?? '-' ?></small></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
function actualizarFiltro() {
    const tipo = document.getElementById('tipo').value;
    const fecha = document.getElementById('fecha').value;
    window.location.href = `?tipo=${tipo}&fecha=${fecha}`;
}
</script>

<?php include('../layout/parte2.php'); ?>
