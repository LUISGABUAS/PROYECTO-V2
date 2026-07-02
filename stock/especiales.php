<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');
include('../app/controllers/clientes/list_clientes.php');

if (!in_array(11, $_SESSION['permisos']) && !in_array(24, $_SESSION['permisos'])) {
    include('../layout/parte2.php');
    echo "<script>Swal.fire('Acceso denegado','','error')</script>";
    exit;
}

$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_prod = (int)($_GET['id_producto'] ?? 0);

$where = ["s.estado = 'EN BODEGA'", "s.tipo_especial IS NOT NULL"];
$params = [];
if (in_array($filtro_tipo, ['VIDEO','FLEJADA'])) {
    $where[] = "s.tipo_especial = ?"; $params[] = $filtro_tipo;
}
if ($filtro_prod) {
    $where[] = "s.id_producto = ?"; $params[] = $filtro_prod;
}
$sql = "SELECT s.id_stock, s.codigo_unico, s.tipo_especial, s.notas_especial,
               s.id_venta_origen, s.fecha_ingreso,
               a.id_producto, a.nombre AS producto, a.codigo AS cod_prod,
               u.nombres AS creado_por
        FROM stock s
        JOIN tb_almacen a ON s.id_producto = a.id_producto
        JOIN tb_usuario u ON s.creado_por = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY s.tipo_especial, a.nombre";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$especiales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Pacas Especiales</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= $URL ?>">Inicio</a></li>
            <li class="breadcrumb-item active">Pacas Especiales</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <!-- FILTROS -->
      <div class="card card-outline card-secondary mb-3">
        <div class="card-body py-2">
          <form method="get" class="row align-items-end">
            <div class="col-md-3">
              <label>Tipo:</label>
              <select name="tipo" class="form-control" onchange="this.form.submit()">
                <option value="">— Todos —</option>
                <option value="VIDEO"   <?= $filtro_tipo==='VIDEO'   ?'selected':'' ?>>VIDEO</option>
                <option value="FLEJADA" <?= $filtro_tipo==='FLEJADA' ?'selected':'' ?>>FLEJADA</option>
              </select>
            </div>
            <div class="col-md-3">
              <a href="especiales.php" class="btn btn-secondary btn-sm mt-4">
                <i class="fa fa-redo"></i> Limpiar filtros
              </a>
            </div>
            <div class="col-md-6 text-right mt-3">
              <span class="badge badge-danger mr-1" style="font-size:14px;">
                <?= count(array_filter($especiales, fn($e)=>$e['tipo_especial']==='VIDEO')) ?> VIDEO
              </span>
              <span class="badge badge-warning" style="font-size:14px;">
                <?= count(array_filter($especiales, fn($e)=>$e['tipo_especial']==='FLEJADA')) ?> FLEJADA
              </span>
            </div>
          </form>
        </div>
      </div>

      <?php if (empty($especiales)): ?>
        <div class="alert alert-info text-center">
          <i class="fa fa-box-open fa-2x mb-2"></i><br>
          No hay pacas especiales registradas.
        </div>
      <?php else: ?>
      <div class="card card-outline card-warning">
        <div class="card-body p-0">
          <table id="tablaEspeciales" class="table table-bordered table-striped table-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th>Código</th>
                <th>Producto</th>
                <th class="text-center">Tipo</th>
                <th>Notas</th>
                <th>Venta origen</th>
                <th>Fecha ingreso</th>
                <th>Registrado por</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($especiales as $e): ?>
              <tr>
                <td><strong><?= htmlspecialchars($e['codigo_unico']) ?></strong></td>
                <td><?= htmlspecialchars($e['cod_prod'] . ' - ' . $e['producto']) ?></td>
                <td class="text-center">
                  <span class="badge badge-<?= $e['tipo_especial']==='VIDEO' ? 'danger' : 'warning' ?>"
                        style="font-size:13px;"><?= $e['tipo_especial'] ?></span>
                </td>
                <td class="small text-muted"><?= htmlspecialchars($e['notas_especial'] ?? '—') ?></td>
                <td class="text-center">
                  <?php if ($e['id_venta_origen']): ?>
                    <a href="<?= $URL ?>/ventas/edit.php?id=<?= $e['id_venta_origen'] ?>" target="_blank">
                      #<?= $e['id_venta_origen'] ?>
                    </a>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td><?= $e['fecha_ingreso'] ? date('d/m/Y', strtotime($e['fecha_ingreso'])) : '—' ?></td>
                <td><?= htmlspecialchars($e['creado_por']) ?></td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <!-- Vender -->
                    <button class="btn btn-success btn-vender"
                            data-id="<?= $e['id_stock'] ?>"
                            data-codigo="<?= htmlspecialchars($e['codigo_unico']) ?>"
                            data-producto="<?= htmlspecialchars($e['producto']) ?>"
                            title="Registrar venta">
                      <i class="fa fa-dollar-sign"></i> Vender
                    </button>
                    <!-- Revertir (solo admin) -->
                    <?php if (in_array(24, $_SESSION['permisos'])): ?>
                    <button class="btn btn-outline-secondary btn-revertir"
                            data-id="<?= $e['id_stock'] ?>"
                            title="Revertir a paca normal">
                      <i class="fa fa-undo"></i>
                    </button>
                    <?php endif; ?>
                  </div>
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

<!-- MODAL VENDER -->
<div class="modal fade" id="modalVender" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="fa fa-dollar-sign"></i> Vender Paca Especial</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <?php include_once('../app/controllers/helpers/csrf.php'); ?>
      <form id="formVender">
        <?= csrf_field() ?>
        <input type="hidden" name="id_stock" id="mv-id-stock">
        <div class="modal-body">
          <p class="mb-2"><strong id="mv-titulo"></strong></p>
          <div class="form-group">
            <label>Cliente <span class="text-danger">*</span></label>
            <select name="id_cliente" id="mv-cliente" class="form-control" required>
              <option value="">Buscar cliente...</option>
              <?php foreach ($clientes as $c):
                $tel = preg_replace('/[^0-9]/', '', $c['telefono']); ?>
                <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nombre_completo']) ?> | <?= $tel ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Precio $ <span class="text-danger">*</span></label>
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text">$</span></div>
              <input type="number" name="precio" id="mv-precio" class="form-control" min="1" step="0.01" required>
            </div>
          </div>
          <div class="form-group">
            <label>Tipo de pago <span class="text-danger">*</span></label>
            <select name="tipo_pago" class="form-control" required>
              <option value="efectivo">Efectivo</option>
              <option value="comprobante">Comprobante</option>
              <option value="ambos">Ambos</option>
            </select>
          </div>
          <div class="form-group">
            <label>Notas</label>
            <input type="text" name="notas" class="form-control" placeholder="Opcional">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Confirmar venta</button>
        </div>
      </form>
    </div>
  </div>
</div>

<link rel="stylesheet" href="<?= $URL ?>/public/templates/AdminLTE-3.2.0/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="<?= $URL ?>/public/templates/AdminLTE-3.2.0/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<script src="<?= $URL ?>/public/templates/AdminLTE-3.2.0/plugins/select2/js/select2.full.min.js"></script>

<script>
$(function() {
  // DataTable
  $('#tablaEspeciales').DataTable({
    responsive: true, autoWidth: false, pageLength: 25,
    language: { search: 'Buscar:', lengthMenu: 'Mostrar _MENU_' }
  });

  // Select2 cliente
  $('#mv-cliente').select2({ theme: 'bootstrap4', placeholder: 'Buscar cliente...', width: '100%', dropdownParent: $('#modalVender') });

  // Abrir modal vender
  $(document).on('click', '.btn-vender', function() {
    document.getElementById('mv-id-stock').value = $(this).data('id');
    document.getElementById('mv-titulo').textContent = $(this).data('codigo') + ' — ' + $(this).data('producto');
    $('#mv-cliente').val('').trigger('change');
    document.getElementById('mv-precio').value = '';
    $('#modalVender').modal('show');
  });

  // Submit venta
  $('#formVender').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
      url: '<?= $URL ?>/app/controllers/stock/vender_especial.php',
      method: 'POST',
      data: new FormData(this),
      processData: false,
      contentType: false,
      success: function(r) {
        if (r.success) {
          $('#modalVender').modal('hide');
          Swal.fire({ icon: 'success', title: r.message, timer: 2000, showConfirmButton: false })
            .then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: r.message });
        }
      }
    });
  });

  // Revertir a normal
  $(document).on('click', '.btn-revertir', function() {
    const id = $(this).data('id');
    Swal.fire({
      icon: 'question', title: '¿Revertir a paca normal?',
      text: 'La paca volverá a contarse como EN BODEGA normal.',
      showCancelButton: true, confirmButtonText: 'Sí, revertir', cancelButtonText: 'Cancelar'
    }).then(r => {
      if (!r.isConfirmed) return;
      const fd = new FormData();
      fd.append('tipo_especial', 'REVERTIR');
      fd.append('ids[]', id);
      fetch('<?= $URL ?>/app/controllers/stock/marcar_especial.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            Swal.fire({ icon: 'success', title: 'Revertida', timer: 1500, showConfirmButton: false })
              .then(() => location.reload());
          } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
          }
        });
    });
  });
});
</script>

<?php include('../layout/mensajes.php'); ?>
<?php include('../layout/parte2.php'); ?>
