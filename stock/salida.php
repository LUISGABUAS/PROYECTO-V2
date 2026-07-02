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
                        SELECT v.id_venta, v.fecha, v.created_at, c.nombre_completo
                        FROM tb_ventas v
                        JOIN clientes c ON c.id_cliente = v.cliente
                        WHERE c.tipo_cliente = 'LOCAL'
                      ";

                      $params = [];
                      if(isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio'])) {
                        $query .= " AND DATE(v.created_at) >= ?";
                        $params[] = $_GET['fecha_inicio'];
                      }
                      if(isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin'])) {
                        $query .= " AND DATE(v.created_at) <= ?";
                        $params[] = $_GET['fecha_fin'];
                      }

                      $query .= " ORDER BY v.created_at DESC";

                      $stmt = $pdo->prepare($query);
                      $stmt->execute($params);
                      $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                      foreach($ventas as $v) {
                        $selected = (isset($_GET['id_venta']) && $_GET['id_venta'] == $v['id_venta'] && isset($_GET['tipo']) && $_GET['tipo'] == 'local') ? 'selected' : '';
                        $hora = $v['created_at'] ? date('H:i', strtotime($v['created_at'])) . ' hrs' : $v['fecha'];
                        echo "<option value='{$v['id_venta']}' $selected>#{$v['id_venta']} - {$v['nombre_completo']} ({$hora})</option>";
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
                        SELECT v.id_venta, v.fecha, v.created_at, c.nombre_completo
                        FROM tb_ventas v
                        JOIN clientes c ON c.id_cliente = v.cliente
                        WHERE c.tipo_cliente = 'FORANEO'
                      ";

                      $params = [];
                      if(isset($_GET['fecha_inicio']) && !empty($_GET['fecha_inicio'])) {
                        $query .= " AND DATE(v.created_at) >= ?";
                        $params[] = $_GET['fecha_inicio'];
                      }
                      if(isset($_GET['fecha_fin']) && !empty($_GET['fecha_fin'])) {
                        $query .= " AND DATE(v.created_at) <= ?";
                        $params[] = $_GET['fecha_fin'];
                      }

                      $query .= " ORDER BY v.created_at DESC";

                      $stmt = $pdo->prepare($query);
                      $stmt->execute($params);
                      $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                      foreach($ventas as $v) {
                        $selected = (isset($_GET['id_venta']) && $_GET['id_venta'] == $v['id_venta'] && isset($_GET['tipo']) && $_GET['tipo'] == 'foraneo') ? 'selected' : '';
                        $hora = $v['created_at'] ? date('H:i', strtotime($v['created_at'])) . ' hrs' : $v['fecha'];
                        echo "<option value='{$v['id_venta']}' $selected>#{$v['id_venta']} - {$v['nombre_completo']} ({$hora})</option>";
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
                  SELECT c.*, v.envio, v.total, v.guia_pdf, v.notas
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
              <div class="card mb-3" style="border:2px solid #17a2b8; background:#f0faff;">
                <div class="card-body py-2 px-3" style="color:#212529;">
                  <p class="mb-1"><strong style="color:#0c7a8a;">Cliente:</strong> <?= htmlspecialchars($cliente['nombre_completo']) ?></p>
                  <p class="mb-1"><strong style="color:#0c7a8a;">Dirección:</strong> <?= htmlspecialchars($cliente['calle_numero'] . ', ' . $cliente['colonia'] . ', ' . $cliente['municipio'] . ', ' . $cliente['estado'] . ', CP ' . $cliente['cp']) ?></p>
                  <p class="mb-1"><strong style="color:#0c7a8a;">Teléfono:</strong> <?= htmlspecialchars($cliente['telefono']) ?></p>
                  <p class="mb-1"><strong style="color:#0c7a8a;">Envío:</strong> <?= htmlspecialchars(strtoupper($cliente['envio'])) ?></p>
                  <p class="mb-0"><strong style="color:#0c7a8a;">Total:</strong> <span class="font-weight-bold text-success">$<?= number_format($cliente['total'],2) ?></span></p>
                  <?php if (!empty($cliente['notas'])): ?>
                  <hr class="my-2">
                  <p class="mb-0"><strong><i class="fas fa-sticky-note text-warning"></i> Notas:</strong> <?= nl2br(htmlspecialchars($cliente['notas'])) ?></p>
                  <?php endif; ?>
                </div>
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
                    $tdStyle   = "style='color:#000 !important; font-weight:600;'";
                    $barColor  = $completo ? '#28a745' : '#ffc107';
                    $barW      = $porcentaje > 0 ? $porcentaje : 100;
                    $barText   = $porcentaje > 0 ? "{$porcentaje}%" : "0%";
                    $barBg     = $porcentaje > 0 ? $barColor : '#dee2e6';
                    $barTxt    = $porcentaje > 0 ? ($completo ? '#fff' : '#000') : '#666';
                    $html .= "<tr class='text-center " . ($completo ? "table-success" : "table-danger") . "'>
                                <td class='text-left' $tdStyle>".htmlspecialchars($p['nombre'])."</td>
                                <td $tdStyle>{$p['vendidos']}</td>
                                <td $tdStyle>{$p['entregados']}</td>
                                <td $tdStyle>
                                  <div class='progress' style='background:#dee2e6;'>
                                    <div class='progress-bar' style='width:{$barW}%;background:{$barBg};color:{$barTxt};font-weight:bold;'>
                                      {$barText}
                                    </div>
                                  </div>
                                </td>
                                <td $tdStyle>".($completo ? "<span class='badge badge-success'>COMPLETO</span>" : "<span class='badge badge-warning'>PENDIENTE</span>")."</td>
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

      <!-- VENTAS DEL DÍA -->
      <div class="row mt-4">
        <div class="col-md-12">
          <div class="card card-outline card-secondary">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-list text-secondary"></i> Ventas del período</h3>
            </div>
            <div class="card-body p-0">
              <?php
              $sql_ventas = "
                SELECT v.id_venta, v.created_at, v.envio,
                       c.nombre_completo AS cliente,
                       SUM(vd.cantidad)             AS total_pacas,
                       SUM(vd.cantidad_entregada)   AS entregadas,
                       MAX(vs.fecha_scan)            AS ultima_scan
                FROM tb_ventas v
                JOIN clientes c ON c.id_cliente = v.cliente
                JOIN tb_ventas_detalle vd ON vd.id_venta = v.id_venta
                LEFT JOIN tb_ventas_stock vs ON vs.id_venta = v.id_venta
                WHERE 1=1";
              $params_v = [];
              if ($fecha_inicio) {
                  $sql_ventas .= " AND DATE(v.created_at) >= ?";
                  $params_v[] = $fecha_inicio;
              }
              if ($fecha_fin) {
                  if ($hora_hasta) {
                      $sql_ventas .= " AND v.created_at <= ?";
                      $params_v[] = $fecha_fin . ' ' . $hora_hasta . ':00';
                  } else {
                      $sql_ventas .= " AND DATE(v.created_at) <= ?";
                      $params_v[] = $fecha_fin;
                  }
              }
              $sql_ventas .= " GROUP BY v.id_venta ORDER BY v.created_at ASC";
              $stmt_v = $pdo->prepare($sql_ventas);
              $stmt_v->execute($params_v);
              $ventas_lista = $stmt_v->fetchAll(PDO::FETCH_ASSOC);
              ?>

              <?php if (empty($ventas_lista)): ?>
              <div class="alert alert-info m-3">
                <i class="fas fa-info-circle"></i> No hay ventas en este período.
              </div>
              <?php else: ?>
              <table class="table table-bordered table-sm mb-0">
                <thead class="thead-light">
                  <tr>
                    <th>#Venta</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th class="text-center">Hora creación</th>
                    <th class="text-center">Estado / Hora escaneada</th>
                    <th class="text-center">Acción</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($ventas_lista as $vl):
                    $completada  = $vl['entregadas'] >= $vl['total_pacas'];
                    $tipo_link   = $vl['envio'] === 'foraneo' ? 'foraneo' : 'local';
                    $hora_creacion = $vl['created_at'] ? date('H:i', strtotime($vl['created_at'])) : '—';
                  ?>
                  <tr class="<?= $completada ? 'table-success' : '' ?>">
                    <td><strong>#<?= $vl['id_venta'] ?></strong></td>
                    <td><?= htmlspecialchars($vl['cliente']) ?></td>
                    <td>
                      <?php if ($vl['envio'] === 'foraneo'): ?>
                        <span class="badge badge-info"><i class="fas fa-truck"></i> Foráneo</span>
                      <?php else: ?>
                        <span class="badge badge-primary"><i class="fas fa-home"></i> Local</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <strong><?= $hora_creacion ?> hrs</strong>
                    </td>
                    <td class="text-center">
                      <?php if ($completada && $vl['ultima_scan']): ?>
                        <span class="badge badge-success" style="font-size:.9em;">
                          <i class="fas fa-check-circle"></i>
                          <?= date('H:i', strtotime($vl['ultima_scan'])) ?> hrs
                        </span>
                      <?php elseif ($completada): ?>
                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Completada</span>
                      <?php else: ?>
                        <span class="badge badge-warning"><i class="fas fa-clock"></i> Pendiente</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <?php if (!$completada): ?>
                      <a href="salida.php?id_venta=<?= $vl['id_venta'] ?>&tipo=<?= $tipo_link ?>&fecha_inicio=<?= urlencode($fecha_inicio) ?>&fecha_fin=<?= urlencode($fecha_fin) ?><?= $hora_hasta ? '&hora_hasta=' . urlencode($hora_hasta) : '' ?>"
                         class="btn btn-sm btn-danger">
                        <i class="fas fa-barcode"></i> Procesar
                      </a>
                      <?php endif; ?>
                      <a href="hoja_empaque.php?id=<?= $vl['id_venta'] ?>" target="_blank"
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
