<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');
include('../app/controllers/dashboard/foraneos.php');

$id_venta = $_GET['id'] ?? null;
if (!$id_venta) {
    die('Venta no válida');
}

// Total de pacas vendidas en esta venta
$stmt_pacas = $pdo->prepare("SELECT COALESCE(SUM(cantidad), 1) AS total_pacas FROM tb_ventas_detalle WHERE id_venta = ?");
$stmt_pacas->execute([$id_venta]);
$total_pacas = (int)$stmt_pacas->fetchColumn();
if ($total_pacas < 1) $total_pacas = 1;

// Guías ya subidas
$stmt_guias = $pdo->prepare("SELECT id, numero, archivo FROM tb_ventas_guias WHERE id_venta = ? ORDER BY numero ASC");
$stmt_guias->execute([$id_venta]);
$guias_existentes = $stmt_guias->fetchAll(PDO::FETCH_ASSOC);
$guias_subidas = count($guias_existentes);
?>

<div class="content-wrapper">
  <div class="content-header">
    <h1>Subir guía de envío</h1>
  </div>

  <section class="content">
    <div class="container-fluid">

      <div class="card card-primary">
        <div class="card-body">

          <form action="../app/controllers/dashboard/guardar_guia.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_venta" value="<?= $id_venta ?>">
            <div class="form-group">
                <?php foreach ($ventas_foraneos as $venta) {
                    if ($venta['id_venta'] == $id_venta) {
                        $cliente = $venta['cliente'];
                        $fecha = $venta['fecha'];
                        $vendedor = $venta['vendedor'];
                        $referencia = $venta['referencia'];
                        $telefono = $venta['telefono'];
                        $domicilio = $venta['calle'] . ', ' . $venta['colonia'] . ', ' . $venta['municipio'] . ', ' . $venta['estado'] . ', CP ' . $venta['cp'];
                        break;
                    }
                }?>
                <div class="row col-md-12">
                    <div class="col-md-3">
                        <div class="form-group">
                        <label>Fecha de venta</label>
                        <input type="text"
                               class="form-control"
                               value="<?= $fecha ?>"
                               disabled>
                    </div>
                </div>
                    <div class="col-md-3">
                <div class="form-group">
                    <label>ID VENTA</label>
                    <input type="text"
                           class="form-control"
                           name="id_venta"
                           value="<?= $id_venta ?>"
                           disabled>
                </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                    <label>Domicilio</label>
                    <textarea class="form-control" rows="2" disabled><?= $domicilio ?></textarea>
                    </div>
                </div> 
            </div>
        <div class="row col-md-12">
            <div class="col-md-2"><div class="form-group">
                <label>Vendedor</label>
                <input type="text"
                       class="form-control"
                       value="<?= $vendedor ?>"
                       disabled>
                </div>
            </div>

             <div class="col-md-2"><div class="form-group">
                        <label>Cliente</label>
                        <input type="text"
                               class="form-control"
                               value="<?= $cliente ?>"
                               disabled>
                </div>
    </div>
            
            
            <div class="col-md-2">
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text"
                           class="form-control"
                           value="<?= $telefono ?>"
                           disabled>

            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Referencia</label>
                <textarea 
                       class="form-control"
                    disabled><?php if($referencia == null){ echo 'Sin Referencia'; } else { echo $referencia; } ?></textarea>
            </div>      
            
        </div>

        </div>

            <!-- PAQUETERÍA -->
            <div class="form-group mt-3">
              <label><strong>Paquetería</strong></label>
              <input type="hidden" name="paqueteria" id="paqueteria_value">
              <div class="d-flex flex-wrap" style="gap:.5rem;">
                <?php
                $paqueterias = [
                    ['valor' => 'DHL',                  'color' => 'warning',   'icono' => 'fa-shipping-fast'],
                    ['valor' => 'Estafeta',             'color' => 'primary',   'icono' => 'fa-truck'],
                    ['valor' => 'FedEx',                'color' => 'danger',    'icono' => 'fa-box'],
                    ['valor' => 'Paquetería Express',   'color' => 'success',   'icono' => 'fa-bolt'],
                    ['valor' => 'J&T Express',          'color' => 'info',      'icono' => 'fa-truck-moving'],
                    ['valor' => 'Otra',                 'color' => 'secondary', 'icono' => 'fa-ellipsis-h'],
                ];
                foreach ($paqueterias as $p): ?>
                <button type="button"
                        class="btn btn-outline-<?= $p['color'] ?> btn-paqueteria"
                        data-valor="<?= $p['valor'] ?>"
                        style="min-width:130px;">
                  <i class="fas <?= $p['icono'] ?>"></i> <?= $p['valor'] ?>
                </button>
                <?php endforeach; ?>
              </div>
              <div id="div_otra_paqueteria" class="mt-2" style="display:none;">
                <input type="text" id="input_otra_paqueteria" class="form-control"
                       placeholder="Escribe el nombre de la paquetería..." style="max-width:300px;">
              </div>
              <small id="paqueteria_seleccionada" class="text-success mt-1" style="display:none;">
                <i class="fas fa-check-circle"></i> <span></span>
              </small>
            </div>

            <!-- GUÍAS -->
            <div class="mt-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="mb-0"><strong>Guías de envío</strong></label>
                <span class="badge badge-info" style="font-size:.9rem;">
                  <?= $guias_subidas ?> / <?= $total_pacas ?> guías subidas
                </span>
              </div>

              <?php if ($guias_subidas > 0): ?>
              <div class="mb-3">
                <small class="text-muted">Ya subidas:</small>
                <div class="d-flex flex-wrap mt-1" style="gap:.5rem;">
                  <?php foreach ($guias_existentes as $g): ?>
                  <a href="<?= $URL ?>/dashboard/guia_pdf/<?= $g['archivo'] ?>" target="_blank"
                     class="btn btn-sm btn-outline-success">
                    <i class="fas fa-file-pdf"></i> Guía <?= $g['numero'] ?>
                  </a>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endif; ?>

              <?php
              $faltantes = $total_pacas - $guias_subidas;
              if ($faltantes > 0):
                for ($n = $guias_subidas + 1; $n <= $total_pacas; $n++):
              ?>
              <div class="form-group">
                <label>Guía <?= $n ?> de <?= $total_pacas ?> (PDF)</label>
                <input type="file" name="guias[]" class="form-control"
                       accept="application/pdf" <?= $n === ($guias_subidas + 1) ? 'required' : '' ?>>
              </div>
              <?php endfor; ?>
              <?php else: ?>
              <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Todas las guías ya fueron subidas (<?= $total_pacas ?>).
              </div>
              <?php endif; ?>
            </div>
        </div>

            <?php if ($faltantes > 0): ?>
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-upload"></i> Subir guías
            </button>
            <?php endif; ?>

            <a href="../dashboard/foraneos.php" class="btn btn-secondary">Cancelar</a>
          </form>
            </div>
      </div>


        </div>
      </div>

    
  </section>


<script>
document.querySelectorAll('.btn-paqueteria').forEach(btn => {
    btn.addEventListener('click', function() {
        // Quitar selección anterior
        document.querySelectorAll('.btn-paqueteria').forEach(b => {
            b.classList.remove('active');
            b.style.fontWeight = '';
        });

        const valor = this.dataset.valor;
        this.classList.add('active');
        this.style.fontWeight = 'bold';

        const divOtra = document.getElementById('div_otra_paqueteria');
        const inputOtra = document.getElementById('input_otra_paqueteria');

        if (valor === 'Otra') {
            divOtra.style.display = 'block';
            inputOtra.focus();
            document.getElementById('paqueteria_value').value = '';
            inputOtra.addEventListener('input', function() {
                document.getElementById('paqueteria_value').value = this.value;
                mostrarSeleccionada(this.value);
            });
        } else {
            divOtra.style.display = 'none';
            document.getElementById('paqueteria_value').value = valor;
            mostrarSeleccionada(valor);
        }
    });
});

function mostrarSeleccionada(nombre) {
    const el = document.getElementById('paqueteria_seleccionada');
    if (nombre) {
        el.querySelector('span').textContent = nombre + ' seleccionada';
        el.style.display = 'block';
    } else {
        el.style.display = 'none';
    }
}
</script>

<?php include('../layout/parte2.php'); ?>
