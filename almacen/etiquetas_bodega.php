<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

if (!in_array(11, $_SESSION['permisos'])):
    include('../layout/parte2.php'); exit;
endif;

$SK = 'scan_bodega_ids';
if (!isset($_SESSION[$SK])) $_SESSION[$SK] = [];

$mensaje = null;
$icono   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'scan') {
        $codigo = trim($_POST['codigo'] ?? '');
        if ($codigo) {
            $stmt = $pdo->prepare("
                SELECT s.id_stock, s.estado, a.nombre AS nombre_producto
                FROM stock s
                INNER JOIN tb_almacen a ON a.id_producto = s.id_producto
                WHERE s.codigo_unico = ? LIMIT 1
            ");
            $stmt->execute([$codigo]);
            $stock = $stmt->fetch();

            if (!$stock) {
                $mensaje = "❌ Código no encontrado: " . htmlspecialchars($codigo);
                $icono   = 'error';
            } elseif ($stock['estado'] !== 'EN BODEGA') {
                $mensaje = "⚠️ Estado incorrecto: " . $stock['estado'];
                $icono   = 'warning';
            } elseif (in_array($stock['id_stock'], $_SESSION[$SK])) {
                $mensaje = "ℹ️ Ya está en la lista";
                $icono   = 'info';
            } else {
                $_SESSION[$SK][] = $stock['id_stock'];
                $mensaje = "✅ Agregado: " . htmlspecialchars($stock['nombre_producto']);
                $icono   = 'success';
            }
        }
    } elseif ($action === 'remove') {
        $id = (int)($_POST['id_stock'] ?? 0);
        $_SESSION[$SK] = array_values(array_filter($_SESSION[$SK], fn($i) => $i !== $id));
    } elseif ($action === 'clear') {
        $_SESSION[$SK] = [];
    }
}

// Obtener detalles de los items escaneados
$items = [];
if (!empty($_SESSION[$SK])) {
    $in   = str_repeat('?,', count($_SESSION[$SK]) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT s.id_stock, s.codigo_unico,
               a.nombre AS nombre_producto,
               a.codigo AS codigo_producto,
               c.nombre_categoria
        FROM stock s
        INNER JOIN tb_almacen a ON a.id_producto = s.id_producto
        INNER JOIN tb_categorias c ON c.id_categoria = a.id_categoria
        WHERE s.id_stock IN ($in)
        ORDER BY s.id_stock ASC
    ");
    $stmt->execute($_SESSION[$SK]);
    $items = $stmt->fetchAll();
}

$ids_param = implode(',', $_SESSION[$SK]);
?>

<?php if ($mensaje): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  Swal.fire({
    icon: '<?= $icono ?>',
    title: <?= json_encode($mensaje) ?>,
    timer: 2000,
    showConfirmButton: false,
    position: 'top-end',
    toast: true
  });
});
</script>
<?php endif; ?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col">
          <h1 class="m-0">
            <i class="fas fa-box text-success"></i> Etiquetas en Bodega
          </h1>
        </div>
        <div class="col-auto d-flex" style="gap:.5rem;">
          <?php if (!empty($items)): ?>
          <a href="<?= $URL ?>/app/controllers/helpers/print_zebra_seleccion.php?ids=<?= urlencode($ids_param) ?>"
             class="btn btn-danger btn-sm" target="_blank">
            <i class="fas fa-file-pdf"></i> Exportar PDF Etiquetas
          </a>
          <form method="POST" class="d-inline">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn btn-outline-secondary btn-sm"
                    onclick="return confirm('¿Limpiar toda la lista?')">
              <i class="fas fa-trash-alt"></i> Limpiar lista
            </button>
          </form>
          <?php endif; ?>
          <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Volver
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <!-- SCAN FORM -->
      <div class="card card-outline card-success">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-barcode"></i> Escanear etiqueta</h3>
        </div>
        <div class="card-body">
          <form method="POST" id="form_scan" autocomplete="off">
            <input type="hidden" name="action" value="scan">
            <div class="input-group" style="max-width:420px;">
              <input type="text" name="codigo" id="input_codigo" class="form-control form-control-lg"
                     placeholder="Escanea o escribe el código..." autofocus>
              <div class="input-group-append">
                <button type="submit" class="btn btn-success btn-lg">
                  <i class="fas fa-plus"></i> Agregar
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- RESUMEN -->
      <div class="row mb-3">
        <div class="col-md-4">
          <div class="small-box bg-success">
            <div class="inner">
              <h3><?= count($items) ?></h3>
              <p>Piezas en lista</p>
            </div>
            <div class="icon"><i class="fas fa-tag"></i></div>
          </div>
        </div>
      </div>

      <!-- LISTA -->
      <?php if (empty($items)): ?>
      <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Escanea etiquetas de productos <strong>EN BODEGA</strong> para agregarlas a la lista.
      </div>
      <?php else: ?>
      <div class="card card-outline card-success">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-list"></i> Lista de etiquetas (<?= count($items) ?>)</h3>
        </div>
        <div class="card-body p-0">
          <table class="table table-bordered table-striped table-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Código etiqueta</th>
                <th>Código producto</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th class="text-center">Quitar</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $i => $item): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($item['codigo_unico']) ?></strong></td>
                <td><?= htmlspecialchars($item['codigo_producto']) ?></td>
                <td><?= htmlspecialchars($item['nombre_producto']) ?></td>
                <td><?= htmlspecialchars($item['nombre_categoria']) ?></td>
                <td class="text-center">
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="id_stock" value="<?= $item['id_stock'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Quitar">
                      <i class="fas fa-times"></i>
                    </button>
                  </form>
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
// Auto-submit al presionar Enter en el campo de escaneo
document.getElementById('input_codigo').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    document.getElementById('form_scan').submit();
  }
});
// Mantener foco en el campo tras submit
window.addEventListener('load', () => document.getElementById('input_codigo').focus());
</script>

<?php include('../layout/parte2.php'); ?>
