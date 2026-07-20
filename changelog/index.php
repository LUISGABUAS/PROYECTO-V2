<?php
include('../app/config.php');
include('../layout/sesion.php');

if (!in_array(39, $_SESSION['permisos'])) {
    include('../layout/parte2.php'); exit;
}

if (isset($_GET['flush'])) {
    @unlink(__DIR__ . '/../app/logs/changelog_cache.json');
    header("Location: index.php"); exit;
}

include('../layout/parte1.php');

$github_owner = 'LUISFR0';
$github_repo  = 'PROYECTO-V2';
$por_pagina   = 100;
$paginas      = 2;

$cache_file = __DIR__ . '/../app/logs/changelog_cache.json';
$cache_ttl  = 300;
$cache_ok   = file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl;

function clasificarCommit($mensaje) {
    if (preg_match('/fix|correg|arregl|solucio|error|bug/i',  $mensaje)) return 'fix';
    if (preg_match('/feat|agrega|añad|nuevo|nueva|crear|add/i', $mensaje)) return 'feat';
    if (preg_match('/mejora|optim|refactor|actualiz|mejor/i',  $mensaje)) return 'mejora';
    if (preg_match('/quita|elimina|borr|remov|quite/i',        $mensaje)) return 'remove';
    if (preg_match('/security|seguridad|csrf|token/i',         $mensaje)) return 'security';
    if (preg_match('/chore|migr|script|config/i',              $mensaje)) return 'chore';
    return 'chore';
}

// Elimina prefijos técnicos tipo "fix:", "feat:", "chore:" y capitaliza
function limpiarMensaje($msg) {
    $msg = preg_replace('/^(feat|fix|chore|refactor|mejora|remove|security|style|docs|test|build|ci)\s*(\(.+?\))?\s*:\s*/i', '', $msg);
    $msg = trim($msg);
    return mb_strtoupper(mb_substr($msg, 0, 1)) . mb_substr($msg, 1);
}

function fetchGitHub($url) {
    $opts = ['http' => ['method' => 'GET',
        'header'  => "User-Agent: PHP-Changelog\r\nAccept: application/vnd.github+json\r\n",
        'timeout' => 8, 'ignore_errors' => true],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]];
    $raw = @file_get_contents($url, false, stream_context_create($opts));
    return $raw ? json_decode($raw, true) : null;
}

$commits = [];
if ($cache_ok) {
    $commits = json_decode(file_get_contents($cache_file), true) ?? [];
} else {
    for ($p = 1; $p <= $paginas; $p++) {
        $url  = "https://api.github.com/repos/{$github_owner}/{$github_repo}/commits?per_page={$por_pagina}&page={$p}";
        $data = fetchGitHub($url);
        if (empty($data) || !is_array($data)) break;
        foreach ($data as $item) {
            if (empty($item['sha'])) continue;
            $raw_msg = trim(explode("\n", $item['commit']['message'] ?? '')[0]);
            $commits[] = [
                'hash'    => $item['sha'],
                'short'   => substr($item['sha'], 0, 7),
                'fecha'   => substr($item['commit']['author']['date'] ?? date('Y-m-d'), 0, 10),
                'autor'   => $item['commit']['author']['name'] ?? 'Desconocido',
                'mensaje' => limpiarMensaje($raw_msg),
                'raw'     => $raw_msg,
                'tipo'    => clasificarCommit($raw_msg),
                'url'     => $item['html_url'] ?? null,
            ];
        }
        if (count($data) < $por_pagina) break;
    }
    if (!empty($commits)) @file_put_contents($cache_file, json_encode($commits));
}

$por_fecha    = [];
foreach ($commits as $c) $por_fecha[$c['fecha']][] = $c;

$tipo_cfg = [
    'feat'     => ['color' => '#28a745', 'bg' => '#d4edda', 'icon' => 'fa-plus-circle',  'label' => 'Nueva función'],
    'fix'      => ['color' => '#dc3545', 'bg' => '#f8d7da', 'icon' => 'fa-bug',           'label' => 'Corrección'],
    'mejora'   => ['color' => '#17a2b8', 'bg' => '#d1ecf1', 'icon' => 'fa-arrow-up',      'label' => 'Mejora'],
    'remove'   => ['color' => '#6c757d', 'bg' => '#e2e3e5', 'icon' => 'fa-minus-circle',  'label' => 'Eliminado'],
    'security' => ['color' => '#856404', 'bg' => '#fff3cd', 'icon' => 'fa-shield-alt',    'label' => 'Seguridad'],
    'chore'    => ['color' => '#495057', 'bg' => '#f1f3f5', 'icon' => 'fa-wrench',        'label' => 'Ajuste técnico'],
];

$total_commits = count($commits);
$conteo_tipos  = array_count_values(array_column($commits, 'tipo'));
?>

<style>
.cl-card {
  border-radius: 10px;
  border: none;
  box-shadow: 0 1px 6px rgba(0,0,0,.1);
  margin-bottom: 10px;
  transition: box-shadow .15s;
}
.cl-card:hover { box-shadow: 0 3px 14px rgba(0,0,0,.15); }
.cl-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 10px; border-radius: 20px;
  font-size: 11px; font-weight: 700; letter-spacing: .3px;
}
.cl-hash {
  font-family: monospace; font-size: 11px;
  background: rgba(0,0,0,.07); padding: 1px 6px;
  border-radius: 4px; text-decoration: none;
}
.cl-hash:hover { background: rgba(0,0,0,.13); }
.cl-fecha-label {
  display: flex; align-items: center; gap: 10px;
  font-size: 15px; font-weight: 800;
  padding: 18px 0 10px; margin-bottom: 8px;
  border-bottom: 2px solid rgba(0,0,0,.08);
  color: inherit;
}
.cl-mensaje {
  font-size: 14px; font-weight: 600; margin: 0;
  line-height: 1.4;
}
.cl-autor { font-size: 12px; opacity: .65; }
.stat-pill {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 6px 14px; border-radius: 30px;
  font-size: 13px; font-weight: 700;
  margin: 3px;
}
</style>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
          <i class="fab fa-git-alt text-danger"></i> Changelog
          <small class="text-muted" style="font-size:.4em;font-weight:400;">
            <?= $github_owner ?>/<?= $github_repo ?>
          </small>
        </h1>
        <a href="?flush=1" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-sync-alt"></i> Actualizar
        </a>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

    <?php if (empty($commits)): ?>
      <div class="card"><div class="card-body text-center py-5">
        <i class="fab fa-github fa-4x text-muted mb-3 d-block"></i>
        <h4 class="text-muted">No se pudo conectar con GitHub</h4>
        <p class="text-muted">Verifica que el repositorio sea público.</p>
      </div></div>
    <?php else: ?>

      <!-- ESTADÍSTICAS -->
      <div class="card mb-4">
        <div class="card-body py-3">
          <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="stat-pill" style="background:#343a40;color:#fff;">
              <i class="fab fa-git-alt"></i> <?= $total_commits ?> commits
            </span>
            <?php foreach ($tipo_cfg as $tipo => $cfg): if (($conteo_tipos[$tipo] ?? 0) === 0) continue; ?>
            <span class="stat-pill" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;">
              <i class="fas <?= $cfg['icon'] ?>"></i>
              <?= $conteo_tipos[$tipo] ?> <?= $cfg['label'] ?>
            </span>
            <?php endforeach; ?>
            <?php if (file_exists($cache_file)): ?>
            <small class="text-muted ml-auto"><i class="fas fa-clock"></i> Actualizado <?= date('H:i', filemtime($cache_file)) ?></small>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- COMMITS POR FECHA -->
      <?php foreach ($por_fecha as $fecha => $commits_dia):
        $dt = DateTime::createFromFormat('Y-m-d', $fecha);
        $fecha_lbl = $dt ? $dt->format('d \d\e F, Y') : $fecha;
        $es_hoy    = $fecha === date('Y-m-d');
        $es_ayer   = $fecha === date('Y-m-d', strtotime('-1 day'));
        $sufijo    = $es_hoy ? ' — Hoy' : ($es_ayer ? ' — Ayer' : '');
      ?>
      <div class="cl-fecha-label">
        <i class="fas fa-calendar-day text-muted"></i>
        <span><?= $fecha_lbl ?><span class="text-primary"><?= $sufijo ?></span></span>
        <span class="badge badge-secondary ml-1" style="font-size:12px;font-weight:600;">
          <?= count($commits_dia) ?> cambio<?= count($commits_dia) > 1 ? 's' : '' ?>
        </span>
      </div>

      <?php foreach ($commits_dia as $c):
        $cfg = $tipo_cfg[$c['tipo']] ?? $tipo_cfg['chore'];
      ?>
      <div class="cl-card card">
        <div class="card-body py-2 px-3">
          <div class="d-flex align-items-start justify-content-between gap-2">

            <div class="d-flex align-items-start gap-2 flex-grow-1">
              <!-- Icono tipo -->
              <span class="cl-badge mt-1" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;flex-shrink:0;">
                <i class="fas <?= $cfg['icon'] ?>"></i>
                <?= $cfg['label'] ?>
              </span>
              <!-- Mensaje -->
              <p class="cl-mensaje"><?= htmlspecialchars($c['mensaje']) ?></p>
            </div>

            <div class="d-flex align-items-center gap-2 flex-shrink-0">
              <?php if (!empty($c['url'])): ?>
              <a href="<?= htmlspecialchars($c['url']) ?>" target="_blank"
                 class="cl-hash text-muted" title="Ver en GitHub">
                <?= $c['short'] ?> <i class="fas fa-external-link-alt" style="font-size:9px;"></i>
              </a>
              <?php endif; ?>
              <span class="cl-autor"><i class="fas fa-user-circle mr-1"></i><?= htmlspecialchars($c['autor']) ?></span>
            </div>

          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <div class="mb-4"></div>
      <?php endforeach; ?>

    <?php endif; ?>
    </div>
  </div>
</div>

<?php include('../layout/parte2.php'); ?>
