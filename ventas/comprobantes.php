<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

if (!in_array(24, $_SESSION['permisos'])) {
    include('../layout/parte2.php');
    echo "<script>Swal.fire('Acceso denegado','','error')</script>";
    exit;
}

$desde_raw = $_GET['desde'] ?? date('Y-m-01') . 'T00:00';
$hasta_raw  = $_GET['hasta']  ?? date('Y-m-d')  . 'T23:59';
$desde = str_replace('T', ' ', $desde_raw);
if (strlen($desde) === 16) $desde .= ':00';
$hasta = str_replace('T', ' ', $hasta_raw);
if (strlen($hasta) === 16) $hasta .= ':59';

$stmt = $pdo->prepare("
    SELECT CONVERT(vc.ruta USING utf8mb4) COLLATE utf8mb4_general_ci AS ruta,
           v.id_venta, v.fecha, v.total, v.tipo_pago, v.monto_pendiente, v.metodo_pendiente,
           CONVERT(c.nombre_completo USING utf8mb4) COLLATE utf8mb4_general_ci AS cliente,
           CONVERT(u.nombres USING utf8mb4) COLLATE utf8mb4_general_ci AS vendedor
    FROM tb_ventas_comprobantes vc
    JOIN tb_ventas v  ON vc.id_venta = v.id_venta
    JOIN clientes  c  ON v.cliente   = c.id_cliente
    JOIN tb_usuario u ON v.id_usuario = u.id
    WHERE v.fecha BETWEEN :desde AND :hasta

    UNION ALL

    SELECT CONVERT(v.comprobante USING utf8mb4) COLLATE utf8mb4_general_ci AS ruta,
           v.id_venta, v.fecha, v.total, v.tipo_pago, v.monto_pendiente, v.metodo_pendiente,
           CONVERT(c.nombre_completo USING utf8mb4) COLLATE utf8mb4_general_ci AS cliente,
           CONVERT(u.nombres USING utf8mb4) COLLATE utf8mb4_general_ci AS vendedor
    FROM tb_ventas v
    JOIN clientes  c  ON v.cliente    = c.id_cliente
    JOIN tb_usuario u ON v.id_usuario = u.id
    WHERE v.comprobante IS NOT NULL AND v.comprobante != ''
      AND v.id_venta NOT IN (SELECT DISTINCT id_venta FROM tb_ventas_comprobantes)
      AND v.fecha BETWEEN :desde2 AND :hasta2

    ORDER BY fecha DESC
");
$stmt->execute([':desde' => $desde, ':hasta' => $hasta, ':desde2' => $desde, ':hasta2' => $hasta]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar comprobantes por venta
$ventas = [];
foreach ($rows as $row) {
    $id = $row['id_venta'];
    if (!isset($ventas[$id])) {
        $ventas[$id] = [
            'id_venta'        => $row['id_venta'],
            'fecha'           => $row['fecha'],
            'total'           => $row['total'],
            'tipo_pago'       => $row['tipo_pago'],
            'monto_pendiente' => $row['monto_pendiente'],
            'metodo_pendiente'=> $row['metodo_pendiente'],
            'cliente'         => $row['cliente'],
            'vendedor'        => $row['vendedor'],
            'archivos'        => [],
        ];
    }
    $ventas[$id]['archivos'][] = $row['ruta'];
}
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Comprobantes de Depósito</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= $URL ?>">Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $URL ?>/ventas/">Ventas</a></li>
            <li class="breadcrumb-item active">Comprobantes</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <!-- FILTRO -->
      <div class="card card-outline card-secondary mb-3">
        <div class="card-body py-2">
          <form method="get" class="row align-items-end">
            <div class="col-md-3">
              <label class="mb-1">Desde:</label>
              <input type="datetime-local" name="desde" class="form-control form-control-sm"
                     value="<?= date('Y-m-d\TH:i', strtotime($desde)) ?>"
                     onchange="this.form.submit()">
            </div>
            <div class="col-md-3">
              <label class="mb-1">Hasta:</label>
              <input type="datetime-local" name="hasta" class="form-control form-control-sm"
                     value="<?= date('Y-m-d\TH:i', strtotime($hasta)) ?>"
                     onchange="this.form.submit()">
            </div>
            <div class="col-md-2">
              <a href="?" class="btn btn-secondary btn-sm btn-block mt-3">
                <i class="fa fa-redo"></i> Resetear
              </a>
            </div>
            <div class="col-md-4 text-right mt-3">
              <span class="badge badge-info" style="font-size:14px;">
                <?= count($ventas) ?> venta<?= count($ventas) !== 1 ? 's' : '' ?> con comprobante
              </span>
            </div>
          </form>
        </div>
      </div>

      <?php if (empty($ventas)): ?>
        <div class="alert alert-warning text-center">
          <i class="fa fa-inbox fa-2x mb-2"></i><br>
          No hay comprobantes en este período.
        </div>
      <?php else: ?>
      <div class="row">
        <?php foreach ($ventas as $v):
          $tipo       = $v['tipo_pago'];
          $total      = $v['total'];
          $pendiente  = (float)($v['monto_pendiente'] ?? 0);

          // Etiqueta y color del tipo de pago
          if ($tipo === 'efectivo') {
              $badge_pago = '<span class="badge badge-success">💵 Efectivo</span>';
              $info_pago  = '<span class="text-success font-weight-bold">$' . number_format($total, 0, '.', ',') . ' en efectivo</span>';
          } elseif ($tipo === 'comprobante') {
              $badge_pago = '<span class="badge badge-primary">🧾 Comprobante</span>';
              $info_pago  = '';
          } elseif ($tipo === 'ambos') {
              $badge_pago = '<span class="badge badge-warning">💵+🧾 Efectivo y comprobante</span>';
              $info_pago  = '<span class="text-warning">Pago mixto</span>';
          } elseif ($tipo === 'contra_entrega') {
              $badge_pago = '<span class="badge badge-danger">🚚 Contra entrega</span>';
              $info_pago  = $pendiente > 0
                  ? '<span class="text-danger">Pendiente: $' . number_format($pendiente, 0, '.', ',') . '</span>'
                  : '<span class="text-success">Liquidado</span>';
          } else {
              $badge_pago = '<span class="badge badge-secondary">' . htmlspecialchars($tipo) . '</span>';
              $info_pago  = '';
          }
        ?>
        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
          <div class="card shadow-sm h-100">

            <!-- Encabezado venta -->
            <div class="card-header p-2" style="background:#f8f9fa;">
              <div class="d-flex justify-content-between align-items-center">
                <strong style="font-size:13px;">#<?= $v['id_venta'] ?></strong>
                <strong class="text-success" style="font-size:13px;">
                  $<?= number_format($total, 0, '.', ',') ?>
                </strong>
              </div>
              <div style="font-size:11px; margin-top:2px;">
                <i class="fa fa-user text-muted"></i>
                <span class="text-truncate" title="<?= htmlspecialchars($v['cliente']) ?>">
                  <?= htmlspecialchars($v['cliente']) ?>
                </span>
              </div>
              <div style="font-size:11px;">
                <i class="fa fa-user-tag text-muted"></i> <?= htmlspecialchars($v['vendedor']) ?>
                <span class="float-right text-muted"><?= date('d/m/Y', strtotime($v['fecha'])) ?></span>
              </div>
            </div>

            <!-- Tipo de pago -->
            <div class="px-2 pt-2" style="font-size:11px;">
              <?= $badge_pago ?>
              <?php if ($info_pago): ?>
                <div class="mt-1"><?= $info_pago ?></div>
              <?php endif; ?>
            </div>

            <!-- Comprobantes (miniaturas) -->
            <div class="card-body p-2">
              <?php if (count($v['archivos']) === 1): ?>
                <?php
                  $ruta = $v['archivos'][0];
                  $ext  = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
                  $url  = $URL . '/' . ltrim($ruta, '/');
                  $titulo = '#' . $v['id_venta'] . ' - ' . htmlspecialchars($v['cliente'], ENT_QUOTES);
                ?>
                <?php if ($ext === 'pdf'): ?>
                  <a href="<?= $url ?>" target="_blank"
                     class="d-flex align-items-center justify-content-center bg-light"
                     style="height:150px; border-radius:4px; color:#c0392b; text-decoration:none;">
                    <div class="text-center">
                      <i class="fa fa-file-pdf fa-4x"></i><br>
                      <small>Ver PDF</small>
                    </div>
                  </a>
                <?php else: ?>
                  <img src="<?= $url ?>" alt="comprobante"
                       style="width:100%;height:150px;object-fit:cover;border-radius:4px;cursor:pointer;"
                       onclick="abrirImg('<?= $url ?>', '<?= $titulo ?>')">
                <?php endif; ?>

              <?php else: ?>
                <!-- Múltiples comprobantes: galería en fila -->
                <div class="d-flex flex-wrap" style="gap:4px;">
                  <?php foreach ($v['archivos'] as $i => $ruta):
                    $ext  = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
                    $url  = $URL . '/' . ltrim($ruta, '/');
                    $titulo = '#' . $v['id_venta'] . ' - ' . htmlspecialchars($v['cliente'], ENT_QUOTES) . ' (' . ($i+1) . ')';
                  ?>
                    <?php if ($ext === 'pdf'): ?>
                      <a href="<?= $url ?>" target="_blank"
                         class="d-flex align-items-center justify-content-center bg-light"
                         style="width:70px;height:70px;border-radius:4px;color:#c0392b;text-decoration:none;flex-shrink:0;">
                        <div class="text-center" style="font-size:10px;">
                          <i class="fa fa-file-pdf fa-2x"></i><br>PDF
                        </div>
                      </a>
                    <?php else: ?>
                      <img src="<?= $url ?>" alt="comprobante <?= $i+1 ?>"
                           style="width:70px;height:70px;object-fit:cover;border-radius:4px;cursor:pointer;flex-shrink:0;"
                           onclick="abrirImg('<?= $url ?>', '<?= $titulo ?>')">
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
                <small class="text-muted"><?= count($v['archivos']) ?> comprobantes</small>
              <?php endif; ?>
            </div>

            <!-- Botón ver venta -->
            <div class="card-footer p-1">
              <a href="<?= $URL ?>/ventas/edit.php?id=<?= $v['id_venta'] ?>"
                 target="_blank"
                 class="btn btn-outline-primary btn-sm btn-block">
                <i class="fa fa-eye"></i> Ver venta completa
              </a>
            </div>

          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- MODAL IMAGEN -->
<div class="modal fade" id="modalImg" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content bg-dark">
      <div class="modal-header border-0 pb-0">
        <h6 class="modal-title text-white" id="modalImgTitulo"></h6>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body text-center p-1">
        <img id="modalImgSrc" src="" alt=""
             style="max-width:100%; max-height:80vh; object-fit:contain;">
      </div>
    </div>
  </div>
</div>

<script>
function abrirImg(src, titulo) {
  document.getElementById('modalImgSrc').src = src;
  document.getElementById('modalImgTitulo').textContent = titulo;
  $('#modalImg').modal('show');
}
</script>

<?php
include('../layout/mensajes.php');
include('../layout/parte2.php');
?>
