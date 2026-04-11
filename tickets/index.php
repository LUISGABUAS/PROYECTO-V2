<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../app/controllers/helpers/csrf.php');
include('../layout/parte1.php');
include('../app/controllers/tickets/list_tickets.php');

if (!in_array(35, $_SESSION['permisos']) && !in_array(37, $_SESSION['permisos'])):
    include('../layout/parte2.php'); exit;
endif;

$puede_gestionar = in_array(37, $_SESSION['permisos']);

// Colores e iconos por importancia y estado
$colores_imp = ['baja'=>'secondary','media'=>'info','alta'=>'warning','critica'=>'danger'];
$colores_est = ['pendiente'=>'warning','en_progreso'=>'primary','resuelto'=>'success'];
$iconos_imp  = ['baja'=>'fa-arrow-down','media'=>'fa-minus','alta'=>'fa-arrow-up','critica'=>'fa-fire'];
$labels_est  = ['pendiente'=>'Pendiente','en_progreso'=>'En Progreso','resuelto'=>'Resuelto'];
$labels_imp  = ['baja'=>'Baja','media'=>'Media','alta'=>'Alta','critica'=>'Crítica'];
?>

<?php if (isset($_SESSION['mensaje'])): ?>
<script>
Swal.fire({
    icon: <?= json_encode(str_contains($_SESSION['mensaje'], '❌') ? 'error' : 'success') ?>,
    title: <?= json_encode(str_contains($_SESSION['mensaje'], '❌') ? 'Error' : '¡Listo!') ?>,
    text: <?= json_encode($_SESSION['mensaje']) ?>,
    timer: 3000, showConfirmButton: false
});
</script>
<?php unset($_SESSION['mensaje']); endif; ?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1 class="m-0"><i class="fas fa-ticket-alt text-primary"></i> <?= $puede_gestionar ? 'Gestión de Tickets' : 'Mis Tickets' ?></h1>
      <?php if (in_array(36, $_SESSION['permisos'])): ?>
      <a href="create.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nuevo Ticket
      </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <?php if (empty($tickets)): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="fas fa-ticket-alt fa-4x text-muted mb-3"></i>
          <h4 class="text-muted">No hay tickets registrados</h4>
          <?php if (in_array(36, $_SESSION['permisos'])): ?>
          <a href="create.php" class="btn btn-primary mt-2">Crear mi primer ticket</a>
          <?php endif; ?>
        </div>
      </div>
      <?php else: ?>

      <!-- Estadísticas rápidas (solo técnico) -->
      <?php if ($puede_gestionar):
        $total     = count($tickets);
        $pendientes = count(array_filter($tickets, fn($t) => $t['estado'] === 'pendiente'));
        $en_prog   = count(array_filter($tickets, fn($t) => $t['estado'] === 'en_progreso'));
        $resueltos = count(array_filter($tickets, fn($t) => $t['estado'] === 'resuelto'));
        $criticos  = count(array_filter($tickets, fn($t) => $t['importancia'] === 'critica'));
      ?>
      <div class="row mb-3">
        <div class="col-md-3">
          <div class="info-box bg-warning"><span class="info-box-icon"><i class="fas fa-clock"></i></span>
            <div class="info-box-content"><span class="info-box-text">Pendientes</span><span class="info-box-number"><?= $pendientes ?></span></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info-box bg-primary"><span class="info-box-icon"><i class="fas fa-spinner"></i></span>
            <div class="info-box-content"><span class="info-box-text">En Progreso</span><span class="info-box-number"><?= $en_prog ?></span></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info-box bg-success"><span class="info-box-icon"><i class="fas fa-check"></i></span>
            <div class="info-box-content"><span class="info-box-text">Resueltos</span><span class="info-box-number"><?= $resueltos ?></span></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="info-box bg-danger"><span class="info-box-icon"><i class="fas fa-fire"></i></span>
            <div class="info-box-content"><span class="info-box-text">Críticos</span><span class="info-box-number"><?= $criticos ?></span></div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-body p-0">
          <table class="table table-hover table-sm mb-0" id="tabla_tickets">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <?php if ($puede_gestionar): ?><th>Usuario</th><?php endif; ?>
                <th>Título</th>
                <th>Importancia</th>
                <th>Estado</th>
                <th>Archivos</th>
                <th>Fecha</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($tickets as $t): ?>
            <tr>
              <td><strong>#<?= $t['id_ticket'] ?></strong></td>
              <?php if ($puede_gestionar): ?>
              <td><?= htmlspecialchars($t['nombre_usuario'] ?? '—') ?></td>
              <?php endif; ?>
              <td><?= htmlspecialchars($t['titulo']) ?></td>
              <td>
                <span class="badge badge-<?= $colores_imp[$t['importancia']] ?>">
                  <i class="fas <?= $iconos_imp[$t['importancia']] ?>"></i>
                  <?= $labels_imp[$t['importancia']] ?>
                </span>
              </td>
              <td>
                <span class="badge badge-<?= $colores_est[$t['estado']] ?>">
                  <?= $labels_est[$t['estado']] ?>
                </span>
              </td>
              <td class="text-center">
                <?php if ($t['total_archivos'] > 0): ?>
                <span class="badge badge-secondary"><i class="fas fa-paperclip"></i> <?= $t['total_archivos'] ?></span>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td><small><?= date('d/m/Y H:i', strtotime($t['fecha_creacion'])) ?></small></td>
              <td>
                <a href="ver.php?id=<?= $t['id_ticket'] ?>" class="btn btn-sm btn-info" title="Ver detalle">
                  <i class="fas fa-eye"></i>
                </a>
                <?php if ($puede_gestionar || $t['id_usuario'] == $_SESSION['id_usuario_sesion']): ?>
                <?php if (in_array(38, $_SESSION['permisos']) || $t['id_usuario'] == $_SESSION['id_usuario_sesion']): ?>
                <button class="btn btn-sm btn-danger btn-delete-ticket"
                        data-id="<?= $t['id_ticket'] ?>"
                        data-csrf="<?= csrf_token() ?>"
                        title="Eliminar">
                  <i class="fas fa-trash"></i>
                </button>
                <?php endif; ?>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<script>
$(document).ready(function(){
  $('#tabla_tickets').DataTable({
    language: { url: '<?= $URL ?>/public/templates/AdminLTE-3.2.0/plugins/datatables/Spanish.json' },
    order: [[0,'desc']],
    pageLength: 25
  });
});

$(document).on('click', '.btn-delete-ticket', function(){
  const id    = $(this).data('id');
  const csrf  = $(this).data('csrf');

  Swal.fire({
    title: '¿Eliminar ticket #' + id + '?',
    text: 'Esta acción no se puede deshacer',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc3545'
  }).then(result => {
    if (!result.isConfirmed) return;
    $.ajax({
      url: '<?= $URL ?>/app/controllers/tickets/delete_ticket.php',
      type: 'POST',
      dataType: 'json',
      data: { id_ticket: id, csrf_token: csrf },
      success: r => {
        if (r.success) {
          Swal.fire('Eliminado', r.message, 'success').then(() => location.reload());
        } else {
          Swal.fire('Error', r.message, 'error');
        }
      },
      error: () => Swal.fire('Error', 'No se pudo conectar con el servidor', 'error')
    });
  });
});
</script>

<?php include('../layout/parte2.php'); ?>
