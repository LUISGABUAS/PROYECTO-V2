<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');
include('../app/controllers/dashboard/foraneos.php');

$id_venta = $_GET['id'] ?? null;
if (!$id_venta) {
    die('Venta no válida');
}
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

            <div class="form-group">
              <label>Guía (PDF)</label>
              <input type="file"
                     name="guia_pdf"
                     class="form-control"
                     accept="application/pdf"
                     required>
            </div>
        </div>


            <button type="submit" class="btn btn-primary">
              <i class="fa fa-upload"></i> Subir guía
            </button>

            <a href="../ventas" class="btn btn-secondary">Cancelar</a>
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
