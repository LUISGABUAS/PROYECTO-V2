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

            <!-- TOGGLE DOBLE GUÍA FEDEX -->
            <div id="div_fedex_doble" class="mt-2" style="display:none;">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="chk_fedex_doble">
                <label class="custom-control-label" for="chk_fedex_doble">
                  Esta guía FedEx requiere <strong>doble guía</strong>
                </label>
              </div>
            </div>

            <!-- GUÍAS -->
            <div class="mt-3">
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

              <div id="contenedor_guias"></div>
            </div>
        </div>

            <button type="submit" class="btn btn-primary" id="btn_subir" style="display:none;">
              <i class="fa fa-upload"></i> Subir guías
            </button>

            <a href="../dashboard/foraneos.php" class="btn btn-secondary">Cancelar</a>
          </form>
            </div>
      </div>


        </div>
      </div>

    
  </section>


<script>
const TOTAL_PACAS   = <?= $total_pacas ?>;
const GUIAS_SUBIDAS = <?= $guias_subidas ?>;

let paqueteriaActual = '';

document.querySelectorAll('.btn-paqueteria').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.btn-paqueteria').forEach(b => {
            b.classList.remove('active');
            b.style.fontWeight = '';
        });
        this.classList.add('active');
        this.style.fontWeight = 'bold';

        const valor = this.dataset.valor;
        paqueteriaActual = valor;

        const divOtra    = document.getElementById('div_otra_paqueteria');
        const inputOtra  = document.getElementById('input_otra_paqueteria');
        const divFedex   = document.getElementById('div_fedex_doble');
        const chkFedex   = document.getElementById('chk_fedex_doble');

        // Mostrar/ocultar controles especiales
        divOtra.style.display  = (valor === 'Otra')  ? 'block' : 'none';
        divFedex.style.display = (valor === 'FedEx') ? 'block' : 'none';

        if (valor === 'Otra') {
            inputOtra.focus();
            document.getElementById('paqueteria_value').value = '';
            inputOtra.oninput = function() {
                document.getElementById('paqueteria_value').value = this.value;
                mostrarSeleccionada(this.value);
            };
        } else {
            document.getElementById('paqueteria_value').value = valor;
            mostrarSeleccionada(valor);
        }

        // Reconstruir campos de guía
        const multiplicador = getMultiplicador(valor, chkFedex.checked);
        renderGuias(multiplicador);
    });
});

// Cuando cambia el toggle de FedEx doble
document.getElementById('chk_fedex_doble').addEventListener('change', function() {
    const mult = getMultiplicador('FedEx', this.checked);
    renderGuias(mult);
});

function getMultiplicador(paqueteria, fedexDoble) {
    if (paqueteria === 'Estafeta') return 2;
    if (paqueteria === 'FedEx' && fedexDoble) return 2;
    return 1;
}

function renderGuias(multiplicador) {
    const totalRequeridas = TOTAL_PACAS * multiplicador;
    const faltantes = totalRequeridas - GUIAS_SUBIDAS;
    const contenedor = document.getElementById('contenedor_guias');
    const btnSubir   = document.getElementById('btn_subir');

    contenedor.innerHTML = '';

    if (faltantes <= 0) {
        contenedor.innerHTML = `<div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Todas las guías ya fueron subidas (${totalRequeridas}).
        </div>`;
        btnSubir.style.display = 'none';
        return;
    }

    // Badge contador
    const badge = document.createElement('div');
    badge.className = 'd-flex justify-content-between align-items-center mb-2';
    badge.innerHTML = `<label class="mb-0"><strong>Guías de envío</strong></label>
        <span class="badge badge-info" style="font-size:.9rem;">
          ${GUIAS_SUBIDAS} / ${totalRequeridas} guías
          ${multiplicador === 2 ? '<span class="ml-1 badge badge-warning">×2</span>' : ''}
        </span>`;
    contenedor.appendChild(badge);

    for (let n = GUIAS_SUBIDAS + 1; n <= totalRequeridas; n++) {
        const div = document.createElement('div');
        div.className = 'form-group';
        div.innerHTML = `<label>Guía ${n} de ${totalRequeridas} (PDF)</label>
            <input type="file" name="guias[]" class="form-control"
                   accept="application/pdf" ${n === GUIAS_SUBIDAS + 1 ? 'required' : ''}>`;
        contenedor.appendChild(div);
    }

    btnSubir.style.display = 'inline-block';
}

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
