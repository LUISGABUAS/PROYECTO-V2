<?php
include('../app/config.php');
include('../layout/sesion.php');

$perms = $_SESSION['permisos'] ?? [];
if (!in_array(41, $perms) && !in_array(24, $perms)) {
    include('../layout/parte1.php');
    include('../layout/parte2.php');
    echo "<script>Swal.fire('Acceso denegado','','error').then(()=>location='<?= $URL ?>')</script>";
    exit;
}

$id_usuario_sesion = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? 0;
$es_admin = in_array(24, $perms);

// Columnas de reparto (defensivo)
$_col_rep = (bool)$pdo->query("SHOW COLUMNS FROM tb_ventas LIKE 'estado_reparto'")->fetchColumn();

// Cargar entregas del día (o fecha seleccionada)
$fecha = $_GET['fecha'] ?? date('Y-m-d');

if ($_col_rep) {
    $stmt = $pdo->prepare("SELECT
        v.id_venta, v.fecha, v.total, v.estado_reparto, v.id_repartidor, v.fecha_entrega,
        c.nombre_completo AS cliente, c.telefono,
        COALESCE(d.calle_numero, c.calle_numero)  AS calle,
        COALESCE(d.colonia,   c.colonia)           AS colonia,
        COALESCE(d.municipio, c.municipio)         AS municipio,
        COALESCE(d.estado,    c.estado)            AS estado_cli,
        c.referencias AS referencias,
        u.nombres AS vendedor,
        ur.nombres AS repartidor_nombre,
        COALESCE(SUM(vd.cantidad), 0) AS total_pacas
        FROM tb_ventas v
        JOIN clientes c ON c.id_cliente = v.cliente
        JOIN tb_usuario u ON u.id = v.id_usuario
        LEFT JOIN tb_usuario ur ON ur.id = v.id_repartidor
        LEFT JOIN clientes_direcciones d ON d.id = v.id_direccion_entrega
        LEFT JOIN tb_ventas_detalle vd ON vd.id_venta = v.id_venta
        WHERE v.envio = 'local'
        AND DATE(v.fecha) = :fecha
        GROUP BY v.id_venta
        ORDER BY FIELD(v.estado_reparto,'PENDIENTE','EN_CAMINO','ENTREGADO'), v.fecha ASC
    ");
    $stmt->execute([':fecha' => $fecha]);
} else {
    $stmt = $pdo->prepare("SELECT
        v.id_venta, v.fecha, v.total, 'PENDIENTE' AS estado_reparto,
        NULL AS id_repartidor, NULL AS fecha_entrega,
        c.nombre_completo AS cliente, c.telefono,
        COALESCE(d.calle_numero, c.calle_numero) AS calle,
        COALESCE(d.colonia, c.colonia)           AS colonia,
        COALESCE(d.municipio, c.municipio)       AS municipio,
        COALESCE(d.estado, c.estado)             AS estado_cli,
        c.referencias AS referencias,
        u.nombres AS vendedor,
        NULL AS repartidor_nombre,
        COALESCE(SUM(vd.cantidad), 0) AS total_pacas
        FROM tb_ventas v
        JOIN clientes c ON c.id_cliente = v.cliente
        JOIN tb_usuario u ON u.id = v.id_usuario
        LEFT JOIN clientes_direcciones d ON d.id = v.id_direccion_entrega
        LEFT JOIN tb_ventas_detalle vd ON vd.id_venta = v.id_venta
        WHERE v.envio = 'local' AND DATE(v.fecha) = :fecha
        GROUP BY v.id_venta ORDER BY v.fecha ASC
    ");
    $stmt->execute([':fecha' => $fecha]);
}
$entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pendientes  = array_filter($entregas, fn($e) => $e['estado_reparto'] === 'PENDIENTE');
$en_camino   = array_filter($entregas, fn($e) => $e['estado_reparto'] === 'EN_CAMINO');
$entregados  = array_filter($entregas, fn($e) => $e['estado_reparto'] === 'ENTREGADO');

include('../layout/parte1.php');
?>

<style>
.reparto-card {
  border-radius: 14px;
  border: none;
  box-shadow: 0 2px 12px rgba(0,0,0,.13);
  margin-bottom: 14px;
  transition: transform .15s;
}
.reparto-card:active { transform: scale(.98); }
.reparto-card .card-header {
  border-radius: 14px 14px 0 0;
  padding: 10px 16px;
  font-weight: 700;
  font-size: 15px;
}
.reparto-card .card-body { padding: 12px 16px; }
.reparto-card .info-row {
  display: flex; align-items: flex-start; gap: 8px;
  margin-bottom: 6px; font-size: 14px;
}
.reparto-card .info-row i { margin-top: 2px; min-width: 16px; }
.reparto-card .btn-accion {
  width: 100%; padding: 12px; font-size: 15px;
  font-weight: 700; border-radius: 10px; margin-top: 6px;
  border: none; cursor: pointer;
}
.seccion-titulo {
  font-size: 18px; font-weight: 800;
  padding: 10px 0 8px 0; margin-bottom: 12px;
  border-bottom: 3px solid currentColor;
}
.badge-pacas {
  background: rgba(255,255,255,.25);
  color: inherit; font-size: 13px;
  padding: 2px 8px; border-radius: 20px;
  font-weight: 600; margin-left: 8px;
}
@media (min-width: 768px) {
  .reparto-grid { columns: 2; column-gap: 16px; }
  .reparto-card { break-inside: avoid; }
}
@media (min-width: 1200px) {
  .reparto-grid { columns: 3; }
}
</style>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h1 class="m-0"><i class="fas fa-motorcycle"></i> Tablero de Repartos</h1>
        <form method="GET" class="d-flex align-items-center gap-2">
          <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($fecha) ?>"
                 onchange="this.form.submit()">
        </form>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <!-- RESUMEN -->
      <div class="row mb-3">
        <div class="col-4">
          <div class="info-box mb-0" style="background:#f8d7da;">
            <span class="info-box-icon" style="background:#dc3545;color:#fff;"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Pendientes</span>
              <span class="info-box-number"><?= count($pendientes) ?></span>
            </div>
          </div>
        </div>
        <div class="col-4">
          <div class="info-box mb-0" style="background:#fff3cd;">
            <span class="info-box-icon" style="background:#ffc107;color:#fff;"><i class="fas fa-motorcycle"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">En camino</span>
              <span class="info-box-number"><?= count($en_camino) ?></span>
            </div>
          </div>
        </div>
        <div class="col-4">
          <div class="info-box mb-0" style="background:#d4edda;">
            <span class="info-box-icon" style="background:#28a745;color:#fff;"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Entregados</span>
              <span class="info-box-number"><?= count($entregados) ?></span>
            </div>
          </div>
        </div>
      </div>

      <?php if (empty($entregas)): ?>
      <div class="alert alert-info text-center py-4">
        <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
        No hay entregas locales para el <?= date('d/m/Y', strtotime($fecha)) ?>
      </div>
      <?php endif; ?>

      <!-- PENDIENTES -->
      <?php if (!empty($pendientes)): ?>
      <div class="seccion-titulo text-danger"><i class="fas fa-clock"></i> Pendientes de asignar</div>
      <div class="reparto-grid mb-4">
        <?php foreach ($pendientes as $e): ?>
        <?= tarjeta_entrega($e, $id_usuario_sesion, $es_admin, $URL) ?>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- EN CAMINO -->
      <?php if (!empty($en_camino)): ?>
      <div class="seccion-titulo text-warning"><i class="fas fa-motorcycle"></i> En camino</div>
      <div class="reparto-grid mb-4">
        <?php foreach ($en_camino as $e): ?>
        <?= tarjeta_entrega($e, $id_usuario_sesion, $es_admin, $URL) ?>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- ENTREGADOS -->
      <?php if (!empty($entregados)): ?>
      <div class="seccion-titulo text-success"><i class="fas fa-check-circle"></i> Entregados</div>
      <div class="reparto-grid mb-4">
        <?php foreach ($entregados as $e): ?>
        <?= tarjeta_entrega($e, $id_usuario_sesion, $es_admin, $URL) ?>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php
function tarjeta_entrega($e, $id_yo, $es_admin, $URL) {
    $estado   = $e['estado_reparto'] ?? 'PENDIENTE';
    $id_venta = $e['id_venta'];
    $es_mia   = (int)$e['id_repartidor'] === (int)$id_yo;

    $colores = [
        'PENDIENTE'  => ['header' => '#dc3545', 'text' => '#fff'],
        'EN_CAMINO'  => ['header' => '#ffc107', 'text' => '#212529'],
        'ENTREGADO'  => ['header' => '#28a745', 'text' => '#fff'],
    ];
    $col = $colores[$estado] ?? $colores['PENDIENTE'];

    $direccion = trim(implode(', ', array_filter([
        $e['calle'], $e['colonia'], $e['municipio'], $e['estado_cli']
    ])));
    $maps_url = 'https://www.google.com/maps/search/' . urlencode($direccion);

    ob_start(); ?>
    <div class="card reparto-card" id="card-<?= $id_venta ?>">
      <div class="card-header d-flex align-items-center justify-content-between"
           style="background:<?= $col['header'] ?>;color:<?= $col['text'] ?>">
        <span>
          <?= $estado === 'PENDIENTE' ? '🔴' : ($estado === 'EN_CAMINO' ? '🟡' : '🟢') ?>
          #<?= $id_venta ?>
          <span class="badge-pacas"><?= $e['total_pacas'] ?> paca(s)</span>
        </span>
        <span style="font-size:13px;opacity:.85">$<?= number_format($e['total'], 2) ?></span>
      </div>
      <div class="card-body">

        <!-- Cliente -->
        <div class="info-row">
          <i class="fas fa-user text-primary"></i>
          <strong><?= htmlspecialchars($e['cliente']) ?></strong>
        </div>

        <!-- Dirección + Maps -->
        <div class="info-row">
          <i class="fas fa-map-marker-alt text-danger"></i>
          <a href="<?= $maps_url ?>" target="_blank" style="color:inherit;text-decoration:underline;">
            <?= htmlspecialchars($direccion) ?>
          </a>
        </div>

        <?php if (!empty($e['referencias'])): ?>
        <div class="info-row">
          <i class="fas fa-info-circle text-secondary"></i>
          <small class="text-muted"><?= htmlspecialchars($e['referencias']) ?></small>
        </div>
        <?php endif; ?>

        <!-- Teléfono -->
        <div class="info-row">
          <i class="fas fa-phone text-success"></i>
          <a href="tel:<?= htmlspecialchars($e['telefono']) ?>" style="font-size:16px;font-weight:700;color:inherit;">
            <?= htmlspecialchars($e['telefono']) ?>
          </a>
        </div>

        <?php if (!empty($e['repartidor_nombre'])): ?>
        <div class="info-row">
          <i class="fas fa-motorcycle text-warning"></i>
          <small><strong><?= htmlspecialchars($e['repartidor_nombre']) ?></strong></small>
        </div>
        <?php endif; ?>

        <?php if (!empty($e['fecha_entrega'])): ?>
        <div class="info-row">
          <i class="fas fa-check text-success"></i>
          <small class="text-muted">Entregado: <?= date('H:i', strtotime($e['fecha_entrega'])) ?></small>
        </div>
        <?php endif; ?>

        <!-- BOTONES DE ACCIÓN -->
        <?php if ($estado === 'PENDIENTE'): ?>
          <button class="btn-accion" style="background:#ffc107;color:#212529"
                  onclick="accionReparto(<?= $id_venta ?>, 'tomar')">
            <i class="fas fa-hand-point-right"></i> Yo lo llevo
          </button>

        <?php elseif ($estado === 'EN_CAMINO'): ?>
          <?php if ($es_mia || $es_admin): ?>
          <button class="btn-accion" style="background:#28a745;color:#fff"
                  onclick="accionReparto(<?= $id_venta ?>, 'entregar')">
            <i class="fas fa-check-circle"></i> Marcar como ENTREGADO
          </button>
          <?php endif; ?>
          <?php if ($es_admin): ?>
          <button class="btn-accion mt-1" style="background:#6c757d;color:#fff;font-size:13px;padding:8px"
                  onclick="accionReparto(<?= $id_venta ?>, 'devolver')">
            <i class="fas fa-undo"></i> Devolver a pendiente
          </button>
          <?php endif; ?>

        <?php elseif ($estado === 'ENTREGADO' && $es_admin): ?>
          <button class="btn-accion mt-1" style="background:#6c757d;color:#fff;font-size:13px;padding:8px"
                  onclick="accionReparto(<?= $id_venta ?>, 'devolver')">
            <i class="fas fa-undo"></i> Revertir
          </button>
        <?php endif; ?>

      </div>
    </div>
    <?php
    return ob_get_clean();
}
?>

<?php include('../layout/parte2.php'); ?>

<script>
const _urlAccion = '<?= $URL ?>/app/controllers/repartos/actualizar_estado.php';

function accionReparto(id_venta, accion) {
  const labels = {
    tomar:    { title: '¿Tomar esta entrega?',         btn: 'Sí, yo la llevo',    color: '#ffc107' },
    entregar: { title: '¿Marcar como entregado?',      btn: '✅ Sí, ya entregué', color: '#28a745' },
    devolver: { title: '¿Devolver a pendiente?',       btn: 'Sí, devolver',       color: '#6c757d' },
  };
  const l = labels[accion];

  Swal.fire({
    title: l.title,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: l.btn,
    confirmButtonColor: l.color,
    cancelButtonText: 'Cancelar'
  }).then(r => {
    if (!r.isConfirmed) return;

    fetch(_urlAccion, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id_venta=${id_venta}&accion=${accion}`,
      credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        Swal.fire('Error', data.message, 'error');
      }
    });
  });
}

// Auto-refresh cada 60 segundos para ver actualizaciones de otros repartidores
setTimeout(() => location.reload(), 60000);
</script>
