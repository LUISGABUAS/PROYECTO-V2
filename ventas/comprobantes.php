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
           v.id_venta, v.fecha, v.total, v.tipo_pago,
           CONVERT(c.nombre_completo USING utf8mb4) COLLATE utf8mb4_general_ci AS cliente,
           CONVERT(u.nombres USING utf8mb4) COLLATE utf8mb4_general_ci AS vendedor
    FROM tb_ventas_comprobantes vc
    JOIN tb_ventas v  ON vc.id_venta = v.id_venta
    JOIN clientes  c  ON v.cliente   = c.id_cliente
    JOIN tb_usuario u ON v.id_usuario = u.id
    WHERE v.fecha BETWEEN :desde AND :hasta

    UNION ALL

    SELECT CONVERT(v.comprobante USING utf8mb4) COLLATE utf8mb4_general_ci AS ruta,
           v.id_venta, v.fecha, v.total, v.tipo_pago,
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
$stmt->execute([
    ':desde'  => $desde,
    ':hasta'  => $hasta,
    ':desde2' => $desde,
    ':hasta2' => $hasta,
]);
$comprobantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <?= count($comprobantes) ?> comprobante<?= count($comprobantes) !== 1 ? 's' : '' ?>
              </span>
            </div>
          </form>
        </div>
      </div>

      <?php if (empty($comprobantes)): ?>
        <div class="alert alert-warning text-center">
          <i class="fa fa-inbox fa-2x mb-2"></i><br>
          No hay comprobantes en este período.
        </div>
      <?php else: ?>
      <div class="row">
        <?php foreach ($comprobantes as $c):
          $ruta    = $c['ruta'];
          $ext     = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
          $esPDF   = $ext === 'pdf';
          $url_archivo = $URL . '/' . ltrim($ruta, '/');
        ?>
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-4">
          <div class="card shadow-sm h-100">

            <!-- Vista previa -->
            <?php
              $titulo_modal = '#' . $c['id_venta'] . ' - ' . htmlspecialchars($c['cliente'], ENT_QUOTES);
              $onclick_attr = $esPDF ? '' : 'onclick="abrirImg(\'' . $url_archivo . '\', \'' . $titulo_modal . '\')"';
            ?>
            <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                 style="height:160px; overflow:hidden; <?= $esPDF ? '' : 'cursor:pointer;' ?>"
                 <?= $onclick_attr ?>>
              <?php if ($esPDF): ?>
                <a href="<?= $url_archivo ?>" target="_blank" class="text-center text-danger p-3">
                  <i class="fa fa-file-pdf fa-5x"></i><br>
                  <small class="text-muted">Ver PDF</small>
                </a>
              <?php else: ?>
                <img src="<?= $url_archivo ?>" alt="comprobante"
                     style="width:100%;height:160px;object-fit:cover;">
              <?php endif; ?>
            </div>

            <!-- Info venta -->
            <div class="card-body p-2">
              <p class="mb-1" style="font-size:12px;">
                <strong>#<?= $c['id_venta'] ?></strong>
                <span class="float-right text-success font-weight-bold">
                  $<?= number_format($c['total'], 0, '.', ',') ?>
                </span>
              </p>
              <p class="mb-1 text-truncate" style="font-size:11px;" title="<?= htmlspecialchars($c['cliente']) ?>">
                <i class="fa fa-user text-muted"></i> <?= htmlspecialchars($c['cliente']) ?>
              </p>
              <p class="mb-1" style="font-size:11px;">
                <i class="fa fa-user-tag text-muted"></i> <?= htmlspecialchars($c['vendedor']) ?>
              </p>
              <p class="mb-0 text-muted" style="font-size:10px;">
                <?= date('d/m/Y H:i', strtotime($c['fecha'])) ?>
              </p>
            </div>

            <div class="card-footer p-1 text-center">
              <?php if ($esPDF): ?>
                <a href="<?= $url_archivo ?>" target="_blank" class="btn btn-danger btn-sm btn-block">
                  <i class="fa fa-file-pdf"></i> Abrir PDF
                </a>
              <?php else: ?>
                <button class="btn btn-info btn-sm btn-block"
                        onclick="abrirImg('<?= $url_archivo ?>', '<?= $titulo_modal ?>')">
                  <i class="fa fa-search-plus"></i> Ver imagen
                </button>
              <?php endif; ?>
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
        <img id="modalImgSrc" src="" alt="" style="max-width:100%; max-height:80vh; object-fit:contain;">
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
