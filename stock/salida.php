<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

if(in_array(15, $_SESSION['permisos'])):

  // Definir variables de fechas al inicio
  $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
  $fecha_fin    = $_GET['fecha_fin']    ?? date('Y-m-d');
  $hora_hasta   = $_GET['hora_hasta']   ?? '';
  
?>

<div class="content-wrapper">

  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">Salida de Bodega</h1>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="row justify-content-center">
        <div class="col-md-10">

          <div class="card card-danger">
            <div class="card-header">
              <h3 class="card-title"><i class="fa fa-barcode"></i> Escanear productos vendidos</h3>
            </div>

            <div class="card-body text-center">

              <!-- Filtro por Fechas y Hora -->
              <div class="row mb-4">
                <form method="get" class="w-100">
                  <div class="row justify-content-center align-items-end">
                    <div class="col-md-3">
                      <div class="form-group">
                        <label>Desde:</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?= $fecha_inicio ?>">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label>Hasta fecha:</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?= $fecha_fin ?>">
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label>Hasta hora:</label>
                        <input type="time" name="hora_hasta" class="form-control" value="<?= $hora_hasta ?>"
                               placeholder="ej. 14:00">
                        <small class="text-muted">Hora Monterrey</small>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Filtrar</button>
                        <a href="salida.php" class="btn btn-secondary btn-sm"><i class="fa fa-times"></i> Limpiar</a>
                      </div>
                    </div>
                  </div>
                </form>
              </div>

              <!-- Selección de Venta - LOCALES -->
              <div class="row">
                <div class="col-md-6">
                  <h5 class="text-primary"><i class="fa fa-home"></i> Salida de Ventas Locales</h5>
                  <form method="get" class="mb-3">
                    <input type="hidden" name="tipo" value="local">
                    <?php if(isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio'])): ?>
                      <input type="hidden" name="fecha_inicio" value="<?= htmlspecialchars($_GET['fecha_inicio']) ?>">
                    <?php endif; ?>
                    <?php if(isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin'])): ?>
                      <input type="hidden" name="fecha_fin" value="<?= htmlspecialchars($_GET['fecha_fin']) ?>">
                    <?php endif; ?>
                    <label>Selecciona la venta:</label>
                    <select name="id_venta" class="form-control" onchange="this.form.submit()">
                      <option value="">-- Elige una venta local --</option>
                      <?php
                      $query = "
                        SELECT v.id_venta, v.fecha, c.nombre_completo 
                        FROM tb_ventas v
                        JOIN clientes c ON c.id_cliente = v.cliente
                        WHERE c.tipo_cliente = 'LOCAL'
                      ";
                      
                      $params = [];
                      if(isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio'])) {
                        $query .= " AND DATE(v.fecha) >= ?";
                        $params[] = $_GET['fecha_inicio'];
                      }
                      if(isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin'])) {
                        $query .= " AND DATE(v.fecha) <= ?";
                        $params[] = $_GET['fecha_fin'];
                      }
                      
                      $query .= " ORDER BY v.id_venta DESC";
                      
                      $stmt = $pdo->prepare($query);
                      $stmt->execute($params);
                      $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                      
                      foreach($ventas as $v) {
                        $selected = (isset($_GET['id_venta']) && $_GET['id_venta'] == $v['id_venta'] && isset($_GET['tipo']) && $_GET['tipo'] == 'local') ? 'selected' : '';
                        echo "<option value='{$v['id_venta']}' $selected>{$v['id_venta']} - {$v['nombre_completo']} ({$v['fecha']})</option>";
                      }
                      ?>
                    </select>
                  </form>
                </div>

                <!-- Selección de Venta - FORANEAS -->
                <div class="col-md-6">
                  <h5 class="text-info"><i class="fa fa-truck"></i> Salida de Ventas Foraneas</h5>
                  <form method="get" class="mb-3">
                    <input type="hidden" name="tipo" value="foraneo">
                    <?php if(isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio'])): ?>
                      <input type="hidden" name="fecha_inicio" value="<?= htmlspecialchars($_GET['fecha_inicio']) ?>">
                    <?php endif; ?>
                    <?php if(isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin'])): ?>
                      <input type="hidden" name="fecha_fin" value="<?= htmlspecialchars($_GET['fecha_fin']) ?>">
                    <?php endif; ?>
                    <label>Selecciona la venta:</label>
                    <select name="id_venta" class="form-control" onchange="this.form.submit()">
                      <option value="">-- Elige una venta foranea --</option>
                      <?php
                      $query = "
                        SELECT v.id_venta, v.fecha, c.nombre_completo
                        FROM tb_ventas v
                        JOIN clientes c ON c.id_cliente = v.cliente
                        WHERE c.tipo_cliente = 'FORANEO'
                      ";
                      
                      $params = [];
                      if(isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio'])) {
                        $query .= " AND DATE(v.fecha) >= ?";
                        $params[] = $_GET['fecha_inicio'];
                      }
                      if(isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin'])) {
                        $query .= " AND DATE(v.fecha) <= ?";
                        $params[] = $_GET['fecha_fin'];
                      }
                      
                      $query .= " ORDER BY v.id_venta DESC";
                      
                      $stmt = $pdo->prepare($query);
                      $stmt->execute($params);
                      $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                      
                      foreach($ventas as $v) {
                        $selected = (isset($_GET['id_venta']) && $_GET['id_venta'] == $v['id_venta'] && isset($_GET['tipo']) && $_GET['tipo'] == 'foraneo') ? 'selected' : '';
                        echo "<option value='{$v['id_venta']}' $selected>{$v['id_venta']} - {$v['nombre_completo']} ({$v['fecha']})</option>";
                      }
                      ?>
                    </select>
                  </form>
                </div>
              </div>

              <?php if(isset($_GET['id_venta']) && !empty($_GET['id_venta'])): 
                $id_venta = (int)$_GET['id_venta'];
                $tipo_venta = isset($_GET['tipo']) ? htmlspecialchars($_GET['tipo']) : '';

                // Datos del cliente
                $stmt = $pdo->prepare("
                  SELECT c.*, v.envio, v.total, v.guia_pdf
                  FROM tb_ventas v
                  JOIN clientes c ON c.id_cliente = v.cliente
                  WHERE v.id_venta = ?
                ");
                $stmt->execute([$id_venta]);
                $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
              ?>

              <!-- Hoja de empaque -->
              <div class="mb-2 text-right">
                <a href="hoja_empaque.php?id=<?= $id_venta ?>" target="_blank"
                   class="btn btn-outline-dark btn-sm">
                  <i class="fas fa-print"></i> Hoja de empaque / Guías
                </a>
              </div>

              <!-- Datos del cliente -->
              <div class="alert alert-info text-left">
                <strong>Cliente:</strong> <?= htmlspecialchars($cliente['nombre_completo']) ?><br>
                <strong>Dirección:</strong> <?= htmlspecialchars($cliente['calle_numero'] . ', ' . $cliente['colonia'] . ', ' . $cliente['municipio'] . ', ' . $cliente['estado'] . ', CP ' . $cliente['cp']) ?><br>
                <strong>Teléfono:</strong> <?= htmlspecialchars($cliente['telefono']) ?><br>
                <strong>Envio:</strong> <?= htmlspecialchars(strtoupper($cliente['envio'])) ?><br>
                <strong>Total:</strong> $<?= number_format($cliente['total'],2) ?>
              </div>

              <!-- Guías (solo foráneos) -->
              <?php if($tipo_venta === 'foraneo'):
                $stmt_g = $pdo->prepare("SELECT id, numero, archivo FROM tb_ventas_guias WHERE id_venta = ? ORDER BY numero ASC");
                $stmt_g->execute([$id_venta]);
                $guias_salida = $stmt_g->fetchAll(PDO::FETCH_ASSOC);

                // Fallback: guía antigua en tb_ventas si no hay registros en tb_ventas_guias
                if (empty($guias_salida) && !empty($cliente['guia_pdf'])) {
                    $guias_salida = [['id' => 0, 'numero' => 1, 'archivo' => $cliente['guia_pdf']]];
                }
              ?>
              <?php if (!empty($guias_salida)): ?>
              <div class="mb-3">
                <strong><i class="fas fa-file-pdf text-danger"></i> Guías de envío (<?= count($guias_salida) ?>):</strong>
                <div class="d-flex flex-wrap mt-2" style="gap:.5rem;">
                  <?php foreach ($guias_salida as $g): ?>
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-warning"
                            onclick="verGuia('<?= $URL ?>/dashboard/guia_pdf/<?= htmlspecialchars($g['archivo']) ?>', <?= $g['numero'] ?>)">
                      <i class="fa fa-eye"></i> Guía <?= $g['numero'] ?>
                    </button>
                    <a href="<?= $URL ?>/dashboard/guia_pdf/<?= htmlspecialchars($g['archivo']) ?>"
                       class="btn btn-success" download>
                      <i class="fa fa-download"></i>
                    </a>
                    <a href="<?= $URL ?>/dashboard/guia_pdf/<?= htmlspecialchars($g['archivo']) ?>"
                       class="btn btn-info" target="_blank" title="Imprimir">
                      <i class="fa fa-print"></i>
                    </a>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php else: ?>
              <div class="alert alert-warning mb-3">
                <i class="fas fa-exclamation-triangle"></i> Esta venta foránea aún no tiene guía registrada.
                <a href="<?= $URL ?>/dashboard/subir_guia.php?id=<?= $id_venta ?>" class="btn btn-sm btn-primary ml-2">
                  <i class="fa fa-upload"></i> Subir guía
                </a>
              </div>
              <?php endif; ?>

              <!-- Modal para previsualizar Guía -->
              <div class="modal fade" id="modalGuia" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-xl" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalGuiaTitulo">Guía - Venta #<?= $id_venta ?></h5>
                      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body p-0">
                      <iframe id="modalGuiaIframe" src="" style="width:100%; height:600px; border:none;"></iframe>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                      <a id="modalGuiaDescargar" href="#" class="btn btn-success" download>
                        <i class="fa fa-download"></i> Descargar
                      </a>
                      <a id="modalGuiaImprimir" href="#" class="btn btn-info" target="_blank">
                        <i class="fa fa-print"></i> Abrir para imprimir
                      </a>
                    </div>
                  </div>
                </div>
              </div>
              <?php endif; ?>

              <!-- Escaneo de productos -->
              <?php include_once('../app/controllers/helpers/csrf.php'); ?>
              <form id="form-salida" action="../app/controllers/stock/salida.php" method="post" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="id_venta" value="<?= $id_venta ?>">
                <input type="hidden" name="tipo" value="<?= isset($_GET['tipo']) ? htmlspecialchars($_GET['tipo']) : '' ?>">
                <input type="text" name="codigo_unico" class="form-control form-control-lg text-center" placeholder="Escanea aquí..." autofocus required>
              </form>

              <!-- Mensaje AJAX -->
              <div id="alerta"></div>

              <hr>

              <!-- Progreso de entrega -->
              <?php 
              $tipo_venta = isset($_GET['tipo']) ? htmlspecialchars($_GET['tipo']) : '';
              $titulo_tabla = ($tipo_venta === 'foraneo') ? 'Progreso de entrega - Venta Foranea' : 'Progreso de entrega - Venta Local';
              $icono_tabla = ($tipo_venta === 'foraneo') ? 'fa-truck' : 'fa-home';
              ?>
              <h5 class="text-left"><i class="fa <?= $icono_tabla ?>"></i> <?= $titulo_tabla ?></h5>
              <div id="tabla-progreso">
                <?php
                // Función para generar la tabla inicial
                function mostrarProgreso($pdo, $id_venta) {
                  $stmt = $pdo->prepare("
    SELECT 
        a.nombre,
        vd.cantidad AS vendidos,
        COUNT(s.id_stock) AS entregados
    FROM tb_ventas_detalle vd
    JOIN tb_almacen a 
        ON a.id_producto = vd.id_producto
    LEFT JOIN tb_ventas_stock vs 
        ON vs.id_venta = vd.id_venta
    LEFT JOIN stock s
        ON s.id_stock = vs.id_stock
       AND s.id_producto = vd.id_producto
       AND s.estado = 'VENDIDO'
    WHERE vd.id_venta = ?
    GROUP BY vd.id_detalle
");


                  $stmt->execute([$id_venta]);
                  $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                  
                  if(!$productos) return '<div class="text-muted">No hay productos en esta venta.</div>';

                  $html = '<table class="table table-bordered table-sm">
                    <thead class="thead-light">
                      <tr class="text-center">
                        <th>Producto</th>
                        <th>Vendidos</th>
                        <th>Entregados</th>
                        <th>Progreso</th>
                        <th>Estado</th>
                      </tr>
                    </thead>
                    <tbody>';

                  foreach($productos as $p){
                    $porcentaje = min(100, round(($p['entregados'] / $p['vendidos']) * 100));
                    $completo = $p['entregados'] >= $p['vendidos'];
                    $html .= "<tr class='text-center " . ($completo ? "table-success" : "table-danger") . "'>
                                <td>".htmlspecialchars($p['nombre'])."</td>
                                <td>{$p['vendidos']}</td>
                                <td>{$p['entregados']}</td>
                                <td>
                                  <div class='progress'>
                                    <div class='progress-bar ".($completo ? "bg-success" : "bg-warning")."' style='width: {$porcentaje}%'>
                                      {$porcentaje}%
                                    </div>
                                  </div>
                                </td>
                                <td>".($completo ? "<span class='badge badge-success'>COMPLETO</span>" : "<span class='badge badge-warning'>PENDIENTE</span>")."</td>
                              </tr>";
                  }

                  $html .= '</tbody></table>';
                  return $html;
                }

                echo mostrarProgreso($pdo, $id_venta);
                ?>
              </div>

              <?php endif; ?>

            </div>

            <div class="card-footer text-center">
              <a href="<?= $URL ?>/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-arrow-left"></i> Volver
              </a>
            </div>

          </div>

        </div>
      </div>

      <!-- VENTAS PENDIENTES -->
      <div class="row mt-4">
        <div class="col-md-12">
          <div class="card card-outline card-warning">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-clock text-warning"></i> Ventas pendientes de entrega</h3>
            </div>
            <div class="card-body p-0">
              <?php
              $sql_pend  = "SELECT v.id_venta, v.fecha, v.created_at, v.envio,
                              c.nombre_completo AS cliente,
                              SUM(vd.cantidad)                         AS total_pacas,
                              SUM(vd.cantidad_entregada)               AS entregadas,
                              SUM(vd.cantidad - vd.cantidad_entregada) AS pendientes
                            FROM tb_ventas v
                            JOIN clientes c ON c.id_cliente = v.cliente
                            JOIN tb_ventas_detalle vd ON vd.id_venta = v.id_venta
                            WHERE vd.cantidad_entregada < vd.cantidad";
              $params_pend = [];
              if ($fecha_inicio) {
                  $sql_pend .= " AND DATE(v.created_at) >= ?";
                  $params_pend[] = $fecha_inicio;
              }
              if ($fecha_fin) {
                  if ($hora_hasta) {
                      $sql_pend .= " AND v.created_at <= ?";
                      $params_pend[] = $fecha_fin . ' ' . $hora_hasta . ':00';
                  } else {
                      $sql_pend .= " AND DATE(v.created_at) <= ?";
                      $params_pend[] = $fecha_fin;
                  }
              }
              $sql_pend .= " GROUP BY v.id_venta ORDER BY v.created_at ASC";
              $stmt_pend = $pdo->prepare($sql_pend);
              $stmt_pend->execute($params_pend);
              $ventas_pendientes = $stmt_pend->fetchAll(PDO::FETCH_ASSOC);
              ?>

              <?php if (empty($ventas_pendientes)): ?>
              <div class="alert alert-success m-3">
                <i class="fas fa-check-circle"></i> No hay ventas pendientes de entrega.
              </div>
              <?php else: ?>
              <table class="table table-bordered table-striped table-sm mb-0">
                <thead class="thead-light">
                  <tr>
                    <th>#Venta</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th class="text-center">Total pacas</th>
                    <th class="text-center">Entregadas</th>
                    <th class="text-center">Pendientes</th>
                    <th class="text-center">Progreso</th>
                    <th class="text-center">Acción</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($ventas_pendientes as $vp):
                    $pct = $vp['total_pacas'] > 0 ? round(($vp['entregadas'] / $vp['total_pacas']) * 100) : 0;
                    $tipo_link = $vp['envio'] === 'foraneo' ? 'foraneo' : 'local';
                  ?>
                  <tr>
                    <td><strong><?= $vp['id_venta'] ?></strong></td>
                    <td>
                      <?= $vp['fecha'] ?>
                      <?php if ($vp['created_at']): ?>
                      <br><small class="text-muted"><?= date('H:i', strtotime($vp['created_at'])) ?> hrs</small>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($vp['cliente']) ?></td>
                    <td>
                      <?php if ($vp['envio'] === 'foraneo'): ?>
                        <span class="badge badge-info"><i class="fas fa-truck"></i> Foráneo</span>
                      <?php else: ?>
                        <span class="badge badge-primary"><i class="fas fa-home"></i> Local</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center"><?= $vp['total_pacas'] ?></td>
                    <td class="text-center text-success"><strong><?= $vp['entregadas'] ?></strong></td>
                    <td class="text-center text-danger"><strong><?= $vp['pendientes'] ?></strong></td>
                    <td style="min-width:120px;">
                      <div class="progress" style="height:18px;">
                        <div class="progress-bar <?= $pct > 0 ? 'bg-warning' : 'bg-danger' ?>"
                             style="width:<?= $pct ?>%;">
                          <?= $pct ?>%
                        </div>
                      </div>
                    </td>
                    <td class="text-center">
                      <a href="salida.php?id_venta=<?= $vp['id_venta'] ?>&tipo=<?= $tipo_link ?>&fecha_inicio=<?= urlencode($fecha_inicio) ?>&fecha_fin=<?= urlencode($fecha_fin) ?><?= $hora_hasta ? '&hora_hasta=' . urlencode($hora_hasta) : '' ?>"
                         class="btn btn-sm btn-danger">
                        <i class="fas fa-barcode"></i> Procesar
                      </a>
                      <a href="hoja_empaque.php?id=<?= $vp['id_venta'] ?>" target="_blank"
                         class="btn btn-sm btn-outline-dark ml-1" title="Hoja de empaque">
                        <i class="fas fa-print"></i>
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
const formSalida = document.getElementById('form-salida');
if (formSalida) formSalida.addEventListener('submit', function(e) {
  e.preventDefault();

  const formData = new FormData(this);
  const alerta = document.getElementById('alerta');

  fetch('../app/controllers/stock/salida.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    // 🔊 REPRODUCIR SONIDO
    if(data.success) {
      new Audio('<?= $URL ?>/app/controllers/sounds/ok.mp3').play();
    } else {
      new Audio('<?= $URL ?>/app/controllers/sounds/error.mp3').play();
    }

    if(data.success) {
      // Construir alerta con guía asignada
      let guiaHtml = '';
      if (data.guias_paca && data.guias_paca.length > 0) {
        guiaHtml = `<div class="mt-2 p-2" style="background:#dc3545;border-radius:6px;color:#fff;">
          <strong>📦 Paca #${data.num_paca} — Pegar esta(s) guía(s):</strong><br>
          ${data.guias_paca.map(g =>
            `<a href="<?= $URL ?>/dashboard/guia_pdf/${g.archivo}" target="_blank"
                style="display:inline-block;margin:4px;padding:4px 12px;background:#fff;color:#dc3545;border-radius:4px;font-weight:bold;text-decoration:none;">
               📄 Ver Guía ${g.numero}
             </a>
             <a href="<?= $URL ?>/dashboard/guia_pdf/${g.archivo}" target="_blank"
                onclick="setTimeout(()=>document.querySelector('[name=codigo_unico]').focus(),500)"
                style="display:inline-block;margin:4px;padding:4px 12px;background:#ffc107;color:#000;border-radius:4px;font-weight:bold;text-decoration:none;">
               🖨️ Imprimir Guía ${g.numero}
             </a>`
          ).join('')}
        </div>`;
      } else if (data.paqueteria) {
        guiaHtml = `<div class="alert alert-warning mt-2 mb-0">
          ⚠️ Paca #${data.num_paca} — <strong>Sin guía asignada aún</strong> para esta paca
        </div>`;
      }

      alerta.innerHTML = `<div class="alert alert-success mt-3 mb-0">
        ✅ ${data.message}
        ${guiaHtml}
      </div>`;

      this.codigo_unico.value = '';
      // Solo re-enfocar si no hay guía que ver (para dar tiempo a hacer clic)
      if (!data.guias_paca || data.guias_paca.length === 0) {
        this.codigo_unico.focus();
      }
    } else {
      alerta.innerHTML = `<div class="alert alert-danger mt-3">${data.message}</div>`;
    }

    if(data.success) {

      // Actualizar tabla de progreso sin recargar
      fetch(`../app/controllers/stock/estado_venta.php?id_venta=${formData.get('id_venta')}`)
      .then(res => res.text())
      .then(html => {
        document.getElementById('tabla-progreso').innerHTML = html;
      });
    }
  })
  .catch(err => {
    alerta.innerHTML = `<div class="alert alert-danger mt-3">Error en la solicitud.</div>`;
    const soundError = new Audio('<?= $URL ?>/app/controllers/sounds/error.mp3');
    soundError.play();
    console.error(err);
  });
});

// Mantener foco en el input al cargar
setTimeout(() => {
  const input = document.querySelector('input[name="codigo_unico"]');
  if(input) input.focus();
}, 300);

// Ver guía en modal
function verGuia(url, numero) {
  document.getElementById('modalGuiaTitulo').textContent = 'Guía ' + numero;
  document.getElementById('modalGuiaIframe').src = url + '#toolbar=0';
  document.getElementById('modalGuiaDescargar').href = url;
  document.getElementById('modalGuiaImprimir').href = url;
  $('#modalGuia').modal('show');
}
</script>


<?php 
unset($_SESSION['mensaje'], $_SESSION['icono']);
include('../layout/parte2.php'); 

else: 
  include('../layout/parte2.php'); 
endif; 
?>
