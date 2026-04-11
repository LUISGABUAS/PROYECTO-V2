<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

if (!in_array(39, $_SESSION['permisos'])) {
    include('../layout/parte2.php'); exit;
}

// ============================================================
// Leer commits de git automáticamente
// ============================================================
$repo   = dirname(__DIR__);
$limite = 200;

// Git 2.35+ bloquea repos de otro usuario (safe.directory).
// Se pasa -c safe.directory=* para permitir cualquier repo bajo Apache (daemon).
$raw = shell_exec(
    "HOME=/tmp /usr/bin/git -c safe.directory='*' -C " . escapeshellarg($repo) .
    " log --pretty=format:'%H|%ad|%an|%s' --date=short -n $limite 2>/dev/null"
);

$raw = $raw ?? ''; // Evita trim(null) si shell_exec devuelve null

$commits = [];
if (!empty(trim($raw))) {
    foreach (explode("\n", trim($raw)) as $linea) {
        $linea = trim($linea, "' \t\r");
        if (empty($linea)) continue;
        $partes = explode('|', $linea, 4);
        if (count($partes) < 4) continue;
        [$hash, $fecha, $autor, $mensaje] = $partes;

        // Clasificar el commit por palabras clave en el mensaje
        $msg_lower = strtolower($mensaje);
        if      (preg_match('/fix|correg|arregl|solucio|error|bug/i', $mensaje))    $tipo = 'fix';
        elseif  (preg_match('/agrega|añad|nuevo|nueva|crear|add/i', $mensaje))       $tipo = 'feat';
        elseif  (preg_match('/mejora|optim|refactor|actualiz|mejor/i', $mensaje))    $tipo = 'mejora';
        elseif  (preg_match('/quita|elimina|borr|remov|quite/i', $mensaje))          $tipo = 'remove';
        elseif  (preg_match('/security|seguridad|csrf|token|pass/i', $mensaje))      $tipo = 'security';
        else                                                                          $tipo = 'chore';

        $commits[] = [
            'hash'    => $hash,
            'short'   => substr($hash, 0, 7),
            'fecha'   => $fecha,
            'autor'   => $autor,
            'mensaje' => $mensaje,
            'tipo'    => $tipo,
        ];
    }
}

// Agrupar por fecha
$por_fecha = [];
foreach ($commits as $c) {
    $por_fecha[$c['fecha']][] = $c;
}

// Configuración visual por tipo
$tipo_cfg = [
    'feat'     => ['color'=>'success',   'icon'=>'fa-plus-circle',    'label'=>'Nueva función'],
    'fix'      => ['color'=>'danger',    'icon'=>'fa-bug',            'label'=>'Corrección'],
    'mejora'   => ['color'=>'info',      'icon'=>'fa-arrow-up',       'label'=>'Mejora'],
    'remove'   => ['color'=>'secondary', 'icon'=>'fa-minus-circle',   'label'=>'Eliminado'],
    'security' => ['color'=>'warning',   'icon'=>'fa-shield-alt',     'label'=>'Seguridad'],
    'chore'    => ['color'=>'dark',      'icon'=>'fa-wrench',         'label'=>'Ajuste'],
];

// Estadísticas
$total_commits = count($commits);
$conteo_tipos  = array_count_values(array_column($commits, 'tipo'));
$autores       = array_unique(array_column($commits, 'autor'));
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
          <h4 class="text-muted">No se pudo leer el historial de git</h4>
          <p class="text-muted">Asegúrate de que el proyecto esté en un repositorio git</p>
        </div>
      </div>
      <?php else: ?>

      <!-- Estadísticas -->
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-dark"><i class="fab fa-git-alt"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Total de commits</span>
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

      <!-- Leyenda de tipos -->
      <div class="mb-3">
        <?php foreach ($tipo_cfg as $tipo => $cfg): ?>
        <span class="badge badge-<?= $cfg['color'] ?> mr-1 p-2">
          <i class="fas <?= $cfg['icon'] ?>"></i> <?= $cfg['label'] ?>
        </span>
        <?php endforeach; ?>
      </div>

      <!-- Timeline de commits agrupados por fecha -->
      <div class="timeline">
        <?php foreach ($por_fecha as $fecha => $commits_dia):
          $dt = DateTime::createFromFormat('Y-m-d', $fecha);
          $fecha_display = $dt ? $dt->format('d \d\e F, Y') : $fecha;
        ?>

        <!-- Separador de fecha -->
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
            <h3 class="timeline-header">
              <?= htmlspecialchars($c['mensaje']) ?>
            </h3>
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
      <!-- /Timeline -->

      <?php endif; ?>
    </div>
  </div>
</div>

<style>
.timeline-item .timeline-header {
  font-size: .95rem;
  font-weight: 500;
  margin: 0;
  padding: 8px 0 4px;
}
.timeline > div > .timeline-item {
  margin-left: 50px;
}
</style>

<?php include('../layout/parte2.php'); ?>
