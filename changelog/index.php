<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

if (!in_array(39, $_SESSION['permisos'])) {
    include('../layout/parte2.php'); exit;
}

// ============================================================
// Funciones helper
// ============================================================
function clasificarCommit($mensaje) {
    if (preg_match('/fix|correg|arregl|solucio|error|bug/i',           $mensaje)) return 'fix';
    if (preg_match('/agrega|añad|nuevo|nueva|crear|add/i',              $mensaje)) return 'feat';
    if (preg_match('/mejora|optim|refactor|actualiz|mejor/i',           $mensaje)) return 'mejora';
    if (preg_match('/quita|elimina|borr|remov|quite/i',                 $mensaje)) return 'remove';
    if (preg_match('/security|seguridad|csrf|token|pass/i',             $mensaje)) return 'security';
    return 'chore';
}

function parsearLineaLog($linea) {
    $linea = trim($linea, "' \t\r");
    if (empty($linea)) return null;
    $p = explode('|', $linea, 4);
    if (count($p) < 4) return null;
    [$hash, $fecha, $autor, $mensaje] = $p;
    return ['hash'=>$hash,'short'=>substr($hash,0,7),'fecha'=>$fecha,'autor'=>$autor,'mensaje'=>$mensaje,'tipo'=>clasificarCommit($mensaje)];
}

// Método 2: leer .git/logs/HEAD directamente (sin shell — funciona en hosting compartido)
function leerReflog($repo, $limite) {
    $logFile = $repo . '/.git/logs/HEAD';
    if (!file_exists($logFile) || !is_readable($logFile)) return [];

    $lineas = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lineas) return [];

    $lineas  = array_reverse($lineas); // más reciente primero
    $commits = [];
    $vistos  = [];

    foreach ($lineas as $linea) {
        // Formato: <old-sha> <new-sha> Nombre <email> timestamp tz\tcommit: mensaje
        if (!preg_match(
            '/^\w+ (\w+) (.+?) <[^>]+> (\d+) [+-]\d{4}\tcommit(?:\s+\([^)]+\))?: (.+)$/',
            $linea, $m
        )) continue;

        [, $sha, $autor, $ts, $mensaje] = $m;
        if (isset($vistos[$sha])) continue;
        $vistos[$sha] = true;

        $commits[] = [
            'hash'    => $sha,
            'short'   => substr($sha, 0, 7),
            'fecha'   => date('Y-m-d', (int)$ts),
            'autor'   => trim($autor),
            'mensaje' => trim($mensaje),
            'tipo'    => clasificarCommit($mensaje),
        ];

        if (count($commits) >= $limite) break;
    }
    return $commits;
}

// Método 1: shell_exec / exec (localhost con git nativo)
function leerShellGit($repo, $limite) {
    $fmt  = '%H|%ad|%an|%s';
    $flag = "-c safe.directory='*'";
    $cmd  = "HOME=/tmp /usr/bin/git $flag -C " . escapeshellarg($repo) .
            " log --pretty=format:'$fmt' --date=short -n $limite 2>/dev/null";

    $raw = null;
    if (function_exists('shell_exec'))  $raw = @shell_exec($cmd);
    if (empty($raw) && function_exists('exec')) {
        $out = []; @exec($cmd, $out); $raw = implode("\n", $out);
    }
    if (empty(trim((string)$raw))) return [];

    $commits = [];
    foreach (explode("\n", trim($raw)) as $linea) {
        $c = parsearLineaLog($linea);
        if ($c) $commits[] = $c;
    }
    return $commits;
}

// ============================================================
// Obtener commits (prueba shell primero, cae al reflog si falla)
// ============================================================
$repo   = dirname(__DIR__);
$limite = 200;

$commits = leerShellGit($repo, $limite);
if (empty($commits)) {
    $commits = leerReflog($repo, $limite);
}

// Agrupar por fecha
$por_fecha = [];
foreach ($commits as $c) {
    $por_fecha[$c['fecha']][] = $c;
}

$tipo_cfg = [
    'feat'     => ['color'=>'success',   'icon'=>'fa-plus-circle',  'label'=>'Nueva función'],
    'fix'      => ['color'=>'danger',    'icon'=>'fa-bug',          'label'=>'Corrección'],
    'mejora'   => ['color'=>'info',      'icon'=>'fa-arrow-up',     'label'=>'Mejora'],
    'remove'   => ['color'=>'secondary', 'icon'=>'fa-minus-circle', 'label'=>'Eliminado'],
    'security' => ['color'=>'warning',   'icon'=>'fa-shield-alt',   'label'=>'Seguridad'],
    'chore'    => ['color'=>'dark',      'icon'=>'fa-wrench',       'label'=>'Ajuste'],
];

$total_commits = count($commits);
$conteo_tipos  = array_count_values(array_column($commits, 'tipo'));
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">
        <i class="fab fa-git-alt text-danger"></i> Changelog del Sistema
        <small class="text-muted" style="font-size:.5em;">Historial automático de cambios</small>
      </h1>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <?php if (empty($commits)): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="fab fa-git-alt fa-4x text-muted mb-3"></i>
          <h4 class="text-muted">No se encontró historial de git</h4>
          <p class="text-muted">
            Asegúrate de que el proyecto esté desplegado desde un repositorio git<br>
            y que la carpeta <code>.git/</code> exista en el servidor.
          </p>
        </div>
      </div>
      <?php else: ?>

      <div class="row mb-4">
        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-dark"><i class="fab fa-git-alt"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Total commits</span>
              <span class="info-box-number"><?= $total_commits ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-plus-circle"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Nuevas funciones</span>
              <span class="info-box-number"><?= $conteo_tipos['feat'] ?? 0 ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-danger"><i class="fas fa-bug"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Correcciones</span>
              <span class="info-box-number"><?= $conteo_tipos['fix'] ?? 0 ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-arrow-up"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Mejoras</span>
              <span class="info-box-number"><?= $conteo_tipos['mejora'] ?? 0 ?></span>
            </div>
          </div>
        </div>
      </div>

      <div class="mb-3">
        <?php foreach ($tipo_cfg as $tipo => $cfg): ?>
        <span class="badge badge-<?= $cfg['color'] ?> mr-1 p-2">
          <i class="fas <?= $cfg['icon'] ?>"></i> <?= $cfg['label'] ?>
        </span>
        <?php endforeach; ?>
      </div>

      <div class="timeline">
        <?php foreach ($por_fecha as $fecha => $commits_dia):
          $dt = DateTime::createFromFormat('Y-m-d', $fecha);
          $fecha_display = $dt ? $dt->format('d \d\e F, Y') : $fecha;
        ?>
        <div class="time-label">
          <span class="bg-dark">
            <i class="fas fa-calendar-day mr-1"></i>
            <?= $fecha_display ?>
            <span class="badge badge-light ml-2"><?= count($commits_dia) ?> cambio<?= count($commits_dia) > 1 ? 's' : '' ?></span>
          </span>
        </div>

        <?php foreach ($commits_dia as $c):
          $cfg = $tipo_cfg[$c['tipo']] ?? $tipo_cfg['chore'];
        ?>
        <div>
          <i class="fas <?= $cfg['icon'] ?> bg-<?= $cfg['color'] ?>"></i>
          <div class="timeline-item">
            <span class="time">
              <code class="text-muted" style="font-size:.8em;"><?= $c['short'] ?></code>
              <span class="badge badge-<?= $cfg['color'] ?> ml-1"><?= $cfg['label'] ?></span>
            </span>
            <h3 class="timeline-header"><?= htmlspecialchars($c['mensaje']) ?></h3>
            <div class="timeline-footer">
              <small class="text-muted">
                <i class="fas fa-user-circle mr-1"></i><?= htmlspecialchars($c['autor']) ?>
              </small>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endforeach; ?>
        <div><i class="fas fa-flag bg-secondary"></i></div>
      </div>

      <?php endif; ?>
    </div>
  </div>
</div>

<style>
.timeline-item .timeline-header { font-size:.95rem; font-weight:500; margin:0; padding:8px 0 4px; }
.timeline > div > .timeline-item { margin-left:50px; }
</style>

<?php include('../layout/parte2.php'); ?>
