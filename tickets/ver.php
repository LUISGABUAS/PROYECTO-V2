<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../app/controllers/helpers/csrf.php');
include('../layout/parte1.php');

if (!in_array(35, $_SESSION['permisos']) && !in_array(37, $_SESSION['permisos'])) {
    include('../layout/parte2.php'); exit;
}

$id_ticket = (int)($_GET['id'] ?? 0);
$id_usuario = $_SESSION['id_usuario_sesion'] ?? 0;
$puede_gestionar = in_array(37, $_SESSION['permisos']);

if (!$id_ticket) { header("Location: index.php"); exit; }

// Obtener ticket
$stmt = $pdo->prepare("
    SELECT t.*, u.nombres AS nombre_usuario, tec.nombres AS nombre_tecnico
    FROM tb_tickets t
    LEFT JOIN tb_usuario u   ON u.id  = t.id_usuario
    LEFT JOIN tb_usuario tec ON tec.id = t.id_tecnico
    WHERE t.id_ticket = ?
");
$stmt->execute([$id_ticket]);
$ticket = $stmt->fetch();

if (!$ticket || (!$puede_gestionar && $ticket['id_usuario'] != $id_usuario)) {
    header("Location: index.php"); exit;
}

// Obtener archivos
$stmtA = $pdo->prepare("SELECT * FROM tb_tickets_archivos WHERE id_ticket = ? ORDER BY fecha_subida");
$stmtA->execute([$id_ticket]);
$archivos = $stmtA->fetchAll();

$colores_imp = ['baja'=>'secondary','media'=>'info','alta'=>'warning','critica'=>'danger'];
$colores_est = ['pendiente'=>'warning','en_progreso'=>'primary','resuelto'=>'success'];
$labels_est  = ['pendiente'=>'Pendiente','en_progreso'=>'En Progreso','resuelto'=>'Resuelto'];
$labels_imp  = ['baja'=>'Baja','media'=>'Media','alta'=>'Alta','critica'=>'Crítica'];
$iconos_imp  = ['baja'=>'fa-arrow-down','media'=>'fa-minus','alta'=>'fa-arrow-up','critica'=>'fa-fire'];
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1 class="m-0">
        <i class="fas fa-ticket-alt text-primary"></i> Ticket #<?= $id_ticket ?>
        <span class="badge badge-<?= $colores_est[$ticket['estado']] ?> ml-2" style="font-size:.7em;">
          <?= $labels_est[$ticket['estado']] ?>
        </span>
        <span class="badge badge-<?= $colores_imp[$ticket['importancia']] ?> ml-1" style="font-size:.7em;">
          <i class="fas <?= $iconos_imp[$ticket['importancia']] ?>"></i> <?= $labels_imp[$ticket['importancia']] ?>
        </span>
      </h1>
      <a href="index.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Volver
      </a>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="row">

        <!-- DETALLE DEL TICKET -->
        <div class="col-md-8">
          <div class="card">
            <div class="card-header bg-light">
              <h4 class="card-title mb-0"><?= htmlspecialchars($ticket['titulo']) ?></h4>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="text-muted"><small>Descripción del problema</small></label>
                <div class="border rounded p-3 bg-light" style="white-space:pre-wrap;font-size:.95em;">
                  <?= htmlspecialchars($ticket['descripcion']) ?>
                </div>
              </div>

              <!-- ARCHIVOS ADJUNTOS -->
              <?php if (!empty($archivos)): ?>
              <div class="mb-3">
                <label class="text-muted"><small><i class="fas fa-paperclip"></i> Archivos adjuntos</small></label>
                <div class="row">
                  <?php foreach ($archivos as $a):
                    $ext = strtolower(pathinfo($a['nombre_original'], PATHINFO_EXTENSION));
                    $es_imagen = in_array($ext, ['jpg','jpeg','png','gif']);
                    $es_video  = in_array($ext, ['mp4','mov','avi']);
                    $url_archivo = $URL . '/tickets/archivo.php?id=' . $a['id_archivo'];
                    $kb = round($a['tamano'] / 1024, 1);
                  ?>
                  <div class="col-md-4 mb-2">
                    <div class="border rounded p-2 text-center bg-light h-100 d-flex flex-column align-items-center justify-content-between">
                      <?php if ($es_imagen): ?>
                        <a href="<?= $url_archivo ?>" target="_blank">
                          <img src="<?= $url_archivo ?>" class="img-fluid rounded mb-1" style="max-height:120px;object-fit:cover;">
                        </a>
                      <?php elseif ($es_video): ?>
                        <video src="<?= $url_archivo ?>" class="w-100 rounded mb-1" style="max-height:120px;" controls></video>
                      <?php else: ?>
                        <a href="<?= $url_archivo ?>" target="_blank" class="d-block mb-2">
                          <i class="fas fa-file fa-3x text-muted"></i>
                        </a>
                      <?php endif; ?>
                      <div>
                        <small class="d-block text-truncate" style="max-width:160px;" title="<?= htmlspecialchars($a['nombre_original']) ?>">
                          <?= htmlspecialchars($a['nombre_original']) ?>
                        </small>
                        <small class="text-muted"><?= $kb ?> KB</small>
                      </div>
                      <a href="<?= $url_archivo ?>" download="<?= htmlspecialchars($a['nombre_original']) ?>" class="btn btn-xs btn-outline-primary mt-1">
                        <i class="fas fa-download"></i>
                      </a>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endif; ?>

              <!-- RESPUESTA DEL TÉCNICO -->
              <?php if (!empty($ticket['respuesta'])): ?>
              <div class="mb-2">
                <label class="text-muted"><small><i class="fas fa-reply"></i> Respuesta del técnico</small></label>
                <div class="border rounded p-3" style="background:#f0f8ff;white-space:pre-wrap;font-size:.95em;">
                  <?= htmlspecialchars($ticket['respuesta']) ?>
                </div>
                <?php if (!empty($ticket['nombre_tecnico'])): ?>
                <small class="text-muted ml-1">— <?= htmlspecialchars($ticket['nombre_tecnico']) ?></small>
                <?php endif; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- PANEL LATERAL -->
        <div class="col-md-4">

          <!-- Info del ticket -->
          <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Información</h3></div>
            <div class="card-body p-0">
              <table class="table table-sm mb-0">
                <tr><td class="text-muted">Creado por</td><td><strong><?= htmlspecialchars($ticket['nombre_usuario'] ?? '—') ?></strong></td></tr>
                <tr><td class="text-muted">Fecha</td><td><?= date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])) ?></td></tr>
                <?php if ($ticket['fecha_actualizacion']): ?>
                <tr><td class="text-muted">Actualizado</td><td><?= date('d/m/Y H:i', strtotime($ticket['fecha_actualizacion'])) ?></td></tr>
                <?php endif; ?>
                <?php if ($ticket['nombre_tecnico']): ?>
                <tr><td class="text-muted">Técnico</td><td><?= htmlspecialchars($ticket['nombre_tecnico']) ?></td></tr>
                <?php endif; ?>
              </table>
            </div>
          </div>

          <!-- Panel de gestión (solo técnico) -->
          <?php if ($puede_gestionar): ?>
          <div class="card card-outline card-warning">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-tools"></i> Gestionar Ticket</h3></div>
            <div class="card-body">
              <div class="form-group">
                <label><strong>Estado</strong></label>
                <select id="sel_estado" class="form-control">
                  <option value="pendiente"   <?= $ticket['estado']==='pendiente'   ? 'selected':'' ?>>Pendiente</option>
                  <option value="en_progreso" <?= $ticket['estado']==='en_progreso' ? 'selected':'' ?>>En Progreso</option>
                  <option value="resuelto"    <?= $ticket['estado']==='resuelto'    ? 'selected':'' ?>>Resuelto</option>
                </select>
              </div>
              <div class="form-group">
                <label><strong>Respuesta / Avance</strong></label>
                <textarea id="txt_respuesta" class="form-control" rows="5"
                          placeholder="Escribe la respuesta o avance para el usuario..."><?= htmlspecialchars($ticket['respuesta'] ?? '') ?></textarea>
              </div>
              <button class="btn btn-warning btn-block" id="btn_actualizar">
                <i class="fas fa-save"></i> Guardar cambios
              </button>
            </div>
          </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($puede_gestionar): ?>
<script>
document.getElementById('btn_actualizar').addEventListener('click', function() {
  const estado    = document.getElementById('sel_estado').value;
  const respuesta = document.getElementById('txt_respuesta').value;

  Swal.fire({ title: 'Guardando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

  $.ajax({
    url: '<?= $URL ?>/app/controllers/tickets/update_ticket.php',
    type: 'POST',
    dataType: 'json',
    data: { id_ticket: <?= $id_ticket ?>, estado, respuesta, csrf_token: '<?= csrf_token() ?>' },
    success: r => {
      if (r.success) {
        Swal.fire('Guardado', r.message, 'success').then(() => location.reload());
      } else {
        Swal.fire('Error', r.message, 'error');
      }
    },
    error: () => Swal.fire('Error', 'No se pudo conectar con el servidor', 'error')
  });
});
</script>
<?php endif; ?>

<?php include('../layout/parte2.php'); ?>
