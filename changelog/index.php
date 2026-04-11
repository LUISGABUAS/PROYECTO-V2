<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

if (!in_array(39, $_SESSION['permisos'])) {
    include('../layout/parte2.php'); exit;
}

// Forzar actualización del caché antes de cualquier output
if (isset($_GET['flush'])) {
    @unlink(__DIR__ . '/../app/logs/changelog_cache.json');
    header("Location: index.php"); exit;
}

// ============================================================
// Configuración del repositorio GitHub
// ============================================================
$github_owner = 'LUISFR0';
$github_repo  = 'PROYECTO-V2';
$por_pagina   = 100; // max 100 por request de GitHub API
$paginas      = 2;   // 2 páginas = hasta 200 commits

// Cache en archivo para no golpear la API en cada visita (TTL: 5 min)
$cache_file = __DIR__ . '/../app/logs/changelog_cache.json';
$cache_ttl  = 300;
$cache_ok   = file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl;

// ============================================================
// Funciones
// ============================================================
function clasificarCommit($mensaje) {
    if (preg_match('/fix|correg|arregl|solucio|error|bug/i',  $mensaje)) return 'fix';
    if (preg_match('/agrega|añad|nuevo|nueva|crear|add/i',     $mensaje)) return 'feat';
    if (preg_match('/mejora|optim|refactor|actualiz|mejor/i',  $mensaje)) return 'mejora';
    if (preg_match('/quita|elimina|borr|remov|quite/i',        $mensaje)) return 'remove';
    if (preg_match('/security|seguridad|csrf|token|pass/i',    $mensaje)) return 'security';
    return 'chore';
}

function fetchGitHub($url) {
    $opts = [
        'http' => [
            'method'  => 'GET',
            'header'  => "User-Agent: PHP-Changelog\r\nAccept: application/vnd.github+json\r\n",
            'timeout' => 8,
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ];
    $ctx = stream_context_create($opts);
    $raw = @file_get_contents($url, false, $ctx);
    return $raw ? json_decode($raw, true) : null;
}

// ============================================================
// Obtener commits
// ============================================================
$commits = [];

if ($cache_ok) {
    $commits = json_decode(file_get_contents($cache_file), true) ?? [];
} else {
    // Traer commits de la API paginando
    for ($p = 1; $p <= $paginas; $p++) {
        $url  = "https://api.github.com/repos/{$github_owner}/{$github_repo}/commits?per_page={$por_pagina}&page={$p}";
        $data = fetchGitHub($url);

        if (empty($data) || !is_array($data)) break;

        foreach ($data as $item) {
            if (empty($item['sha'])) continue;

            $sha     = $item['sha'];
            $info    = $item['commit'] ?? [];
            $mensaje = trim(explode("\n", $info['message'] ?? '')[0]); // solo primera línea
            $fecha   = substr($info['author']['date'] ?? date('Y-m-d'), 0, 10);
            $autor   = $info['author']['name'] ?? 'Desconocido';

            $commits[] = [
                'hash'    => $sha,
                'short'   => substr($sha, 0, 7),
                'fecha'   => $fecha,
                'autor'   => $autor,
                'mensaje' => $mensaje,
                'tipo'    => clasificarCommit($mensaje),
                'url'     => $item['html_url'] ?? null,
            ];
        }

        if (count($data) < $por_pagina) break; // última página
    }

    // Guardar cache
    if (!empty($commits)) {
        @file_put_contents($cache_file, json_encode($commits));
    }
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
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1 class="m-0">
        <i class="fab fa-git-alt text-danger"></i> Changelog
        <small class="text-muted" style="font-size:.45em;">
          github.com/<?= $github_owner ?>/<?= $github_repo ?>
        </small>
      </h1>
      <?php if (!empty($commits)): ?>
      <a href="?flush=1" class="btn btn-sm btn-outline-secondary" title="Forzar actualización">
        <i class="fas fa-sync-alt"></i> Actualizar
      </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">


      <?php if (empty($commits)): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="fab fa-github fa-4x text-muted mb-3"></i>
          <h4 class="text-muted">No se pudo conectar con GitHub</h4>
          <p class="text-muted">
            Verifica que el repositorio
            <code><?= $github_owner ?>/<?= $github_repo ?></code>
            sea público, o revisa la conexión a internet del servidor.
          </p>
        </div>
      </div>
      <?php else: ?>

      <!-- Estadísticas -->
      <div class="row mb-4">
        <div class="col-6 col-md-3">
          <div class="info-box"><span class="info-box-icon bg-dark"><i class="fab fa-git-alt"></i></span>
            <div class="info-box-content"><span class="info-box-text">Total commits</span><span class="info-box-number"><?= $total_commits ?></span></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="info-box"><span class="info-box-icon bg-success"><i class="fas fa-plus-circle"></i></span>
            <div class="info-box-content"><span class="info-box-text">Nuevas funciones</span><span class="info-box-number"><?= $conteo_tipos['feat'] ?? 0 ?></span></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="info-box"><span class="info-box-icon bg-danger"><i class="fas fa-bug"></i></span>
            <div class="info-box-content"><span class="info-box-text">Correcciones</span><span class="info-box-number"><?= $conteo_tipos['fix'] ?? 0 ?></span></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="info-box"><span class="info-box-icon bg-info"><i class="fas fa-arrow-up"></i></span>
            <div class="info-box-content"><span class="info-box-text">Mejoras</span><span class="info-box-number"><?= $conteo_tipos['mejora'] ?? 0 ?></span></div>
          </div>
        </div>
      </div>

      <!-- Leyenda -->
      <div class="mb-3">
        <?php foreach ($tipo_cfg as $cfg): ?>
        <span class="badge badge-<?= $cfg['color'] ?> mr-1 p-2">
          <i class="fas <?= $cfg['icon'] ?>"></i> <?= $cfg['label'] ?>
        </span>
        <?php endforeach; ?>
        <small class="text-muted ml-2">
          <i class="fas fa-clock"></i> Caché 5 min
          <?php if (file_exists($cache_file)): ?>
          — actualizado <?= date('H:i', filemtime($cache_file)) ?>
          <?php endif; ?>
        </small>
      </div>

      <!-- Timeline -->
      <div class="timeline">
        <?php foreach ($por_fecha as $fecha => $commits_dia):
          $dt = DateTime::createFromFormat('Y-m-d', $fecha);
          $fecha_lbl = $dt ? $dt->format('d \d\e F, Y') : $fecha;
        ?>
        <div class="time-label">
          <span class="bg-dark">
            <i class="fas fa-calendar-day mr-1"></i> <?= $fecha_lbl ?>
            <span class="badge badge-light ml-1"><?= count($commits_dia) ?> cambio<?= count($commits_dia) > 1 ? 's' : '' ?></span>
          </span>
        </div>

        <?php foreach ($commits_dia as $c):
          $cfg = $tipo_cfg[$c['tipo']] ?? $tipo_cfg['chore'];
        ?>
        <div>
          <i class="fas <?= $cfg['icon'] ?> bg-<?= $cfg['color'] ?>"></i>
          <div class="timeline-item">
            <span class="time">
              <?php if (!empty($c['url'])): ?>
              <a href="<?= htmlspecialchars($c['url']) ?>" target="_blank" class="text-muted">
                <code style="font-size:.8em;"><?= $c['short'] ?></code>
              </a>
              <?php else: ?>
              <code class="text-muted" style="font-size:.8em;"><?= $c['short'] ?></code>
              <?php endif; ?>
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
