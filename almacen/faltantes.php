<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

if (!in_array(11, $_SESSION['permisos'])):
    include('../layout/parte2.php'); exit;
endif;

// Resumen por producto
$stmt = $pdo->query("
    SELECT
        a.id_producto,
        a.nombre  AS nombre_producto,
        a.codigo  AS codigo_producto,
        c.nombre_categoria,
        COUNT(s.id_stock) AS piezas,
        GROUP_CONCAT(s.codigo_unico ORDER BY s.id_stock SEPARATOR '|') AS codigos
    FROM stock s
    INNER JOIN tb_almacen a ON a.id_producto = s.id_producto
    INNER JOIN tb_categorias c ON c.id_categoria = a.id_categoria
    WHERE s.estado = 'SIN ESCANEAR'
    GROUP BY a.id_producto, a.nombre, a.codigo, c.nombre_categoria
    ORDER BY piezas DESC
");
$productos = $stmt->fetchAll();

$total_productos = count($productos);
$total_piezas    = array_sum(array_column($productos, 'piezas'));
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col">
          <h1 class="m-0">
            <i class="fas fa-barcode text-warning"></i> Etiquetas Faltantes por Escanear
          </h1>
        </div>
        <div class="col-auto">
          <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Volver
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <!-- TARJETAS RESUMEN -->
      <div class="row mb-3">
        <div class="col-md-4">
          <div class="small-box bg-warning">
            <div class="inner">
              <h3><?= $total_piezas ?></h3>
              <p>Piezas sin escanear</p>
            </div>
            <div class="icon"><i class="fas fa-tag"></i></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="small-box bg-info">
            <div class="inner">
              <h3><?= $total_productos ?></h3>
              <p>Productos con faltantes</p>
            </div>
            <div class="icon"><i class="fas fa-box-open"></i></div>
          </div>
        </div>
      </div>

      <?php if ($total_piezas === 0): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> ¡Todo escaneado! No hay piezas pendientes.
      </div>
      <?php else: ?>

      <!-- BOTONES EXPORTAR -->
      <div class="mb-3 d-flex gap-2">
        <a href="<?= $URL ?>/app/controllers/stock/export_faltantes.php?tipo=excel"
           class="btn btn-success mr-2">
          <i class="fas fa-file-excel"></i> Exportar Excel
        </a>
        <a href="<?= $URL ?>/app/controllers/stock/export_faltantes.php?tipo=pdf"
           class="btn btn-danger" target="_blank">
          <i class="fas fa-file-pdf"></i> Exportar PDF Etiquetas
        </a>
      </div>

      <!-- TABLA -->
      <div class="card card-outline card-warning">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fas fa-list"></i> Detalle por Producto
          </h3>
        </div>
        <div class="card-body p-0">
          <table id="tblFaltantes" class="table table-bordered table-striped table-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Código</th>
                <th>Producto</th>
                <th>Categoría</th>
                <th class="text-center">Piezas sin escanear</th>
                <th>Códigos de etiqueta</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($productos as $i => $p):
                $lista = explode('|', $p['codigos']);
              ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($p['codigo_producto']) ?></strong></td>
                <td><?= htmlspecialchars($p['nombre_producto']) ?></td>
                <td><?= htmlspecialchars($p['nombre_categoria']) ?></td>
                <td class="text-center">
                  <span class="badge badge-warning badge-lg" style="font-size:.95em;">
                    <?= $p['piezas'] ?>
                  </span>
                </td>
                <td>
                  <!-- Primeros 3 visibles, resto colapsable -->
                  <?php foreach (array_slice($lista, 0, 3) as $cod): ?>
                    <span class="badge badge-secondary mr-1"><?= htmlspecialchars($cod) ?></span>
                  <?php endforeach; ?>
                  <?php if (count($lista) > 3): ?>
                    <a href="#" class="badge badge-light border text-primary ver-mas" data-id="col-<?= $i ?>">
                      +<?= count($lista) - 3 ?> más
                    </a>
                    <span id="col-<?= $i ?>" style="display:none;">
                      <?php foreach (array_slice($lista, 3) as $cod): ?>
                        <span class="badge badge-secondary mr-1"><?= htmlspecialchars($cod) ?></span>
                      <?php endforeach; ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <a href="../stock/index.php?id=<?= $p['id_producto'] ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-box"></i> Ver Stock
                  </a>
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
$(function() {
  $('#tblFaltantes').DataTable({
    responsive: true,
    lengthChange: false,
    autoWidth: false,
    order: [[4, 'desc']],
    language: {
      search: 'Buscar:',
      paginate: { previous: 'Anterior', next: 'Siguiente' },
      info: 'Mostrando _START_ a _END_ de _TOTAL_ productos',
      emptyTable: 'Sin datos'
    }
  });

  // Ver más / ver menos códigos
  $(document).on('click', '.ver-mas', function(e) {
    e.preventDefault();
    const target = $('#' + $(this).data('id'));
    if (target.is(':hidden')) {
      target.show();
      $(this).text('Ver menos');
    } else {
      target.hide();
      const total = target.find('.badge').length;
      $(this).text('+' + total + ' más');
    }
  });
});
</script>

<?php include('../layout/parte2.php'); ?>
