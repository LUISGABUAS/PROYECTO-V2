<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

if(in_array(15, $_SESSION['permisos'])):

  // Definir variables de fechas al inicio
  $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
  $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
  
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

              <!-- Filtro por Fechas -->
              <div class="row mb-4">
                <form method="get" class="w-100">
                  <div class="row justify-content-center align-items-end">
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="fecha_inicio">Desde:</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?= $fecha_inicio?>">
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="fecha_fin">Hasta:</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="<?= $fecha_fin?>">
                      </div>
                    </div>
                    <div class="col-md-6">
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
                        AND v.guia_pdf IS NOT NULL
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

              <!-- Datos del cliente -->
              <div class="alert alert-info text-left">
                <strong>Cliente:</strong> <?= htmlspecialchars($cliente['nombre_completo']) ?><br>
                <strong>Dirección:</strong> <?= htmlspecialchars($cliente['calle_numero'] . ', ' . $cliente['colonia'] . ', ' . $cliente['municipio'] . ', ' . $cliente['estado'] . ', CP ' . $cliente['cp']) ?><br>
                <strong>Teléfono:</strong> <?= htmlspecialchars($cliente['telefono']) ?><br>
                <strong>Envio:</strong> <?= htmlspecialchars(strtoupper($cliente['envio'])) ?><br>
                <strong>Total:</strong> $<?= number_format($cliente['total'],2) ?>
              </div>

              <!-- Botones de Guía (solo para foraneos) -->
              <?php if($tipo_venta === 'foraneo' && !empty($cliente['guia_pdf'])): ?>
              <div class="mb-3 text-center">
                <div class="btn-group" role="group">
                  <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalGuia">
                    <i class="fa fa-eye"></i> Ver Guía
                  </button>
                  <a href="<?= $URL ?>/dashboard/guia_pdf/<?= htmlspecialchars($cliente['guia_pdf']) ?>" class="btn btn-success btn-sm" download>
                    <i class="fa fa-download"></i> Descargar Guía
                  </a>
                </div>
              </div>

              <!-- Modal para previsualizar Guía -->
              <div class="modal fade" id="modalGuia" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Previsualización de Guía - Venta #<?= $id_venta ?></h5>
                      <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                      </button>
                    </div>
                    <div class="modal-body">
                      <iframe src="<?= $URL ?>/dashboard/guia_pdf/<?= htmlspecialchars($cliente['guia_pdf']) ?>#toolbar=0" 
                              style="width:100%; height:500px; border:none;"></iframe>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                      <a href="<?= $URL ?>/dashboard/guia_pdf/<?= htmlspecialchars($cliente['guia_pdf']) ?>" class="btn btn-success" download>
                        <i class="fa fa-download"></i> Descargar
                      </a>
                    </div>
                  </div>
                </div>
              </div>
              <?php endif; ?>

              <!-- Escaneo de productos -->
              <form id="form-salida" action="../app/controllers/stock/salida.php" method="post" autocomplete="off">
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

    </div>
  </div>
</div>

<script>
document.getElementById('form-salida').addEventListener('submit', function(e) {
  e.preventDefault();

  const formData = new FormData(this);
  const alerta = document.getElementById('alerta');

  fetch('../app/controllers/stock/salida.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    alerta.innerHTML = `<div class="alert alert-${data.success ? 'success' : 'danger'} mt-3">${data.message}</div>`;
    
    // 🔊 REPRODUCIR SONIDO
    if(data.success) {
      const soundOk = new Audio('<?= $URL ?>/app/controllers/sounds/ok.mp3');
      soundOk.play();
    } else {
      const soundError = new Audio('<?= $URL ?>/app/controllers/sounds/error.mp3');
      soundError.play();
    }

    if(data.success) {
      this.codigo_unico.value = '';
      this.codigo_unico.focus();

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
</script>


<?php 
unset($_SESSION['mensaje'], $_SESSION['icono']);
include('../layout/parte2.php'); 

else: 
  include('../layout/parte2.php'); 
endif; 
?>
