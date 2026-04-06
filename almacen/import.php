<?php
include('../app/config.php');
include('../layout/sesion.php');

if (!in_array(9, $_SESSION['permisos'] ?? [])) {
    header('Location: ' . $URL);
    exit;
}

include('../layout/parte1.php');
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Importar Productos por CSV</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= $URL ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= $URL ?>/almacen">Almacén</a></li>
            <li class="breadcrumb-item active">Importar CSV</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <?php if (isset($_SESSION['mensaje'])): ?>
      <div class="alert alert-info alert-dismissible fade show">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?= htmlspecialchars($_SESSION['mensaje']) ?>
      </div>
      <?php unset($_SESSION['mensaje']); ?>
      <?php endif; ?>

      <div class="row">

        <!-- INSTRUCCIONES -->
        <div class="col-md-6">
          <div class="card card-outline card-info">
            <div class="card-header">
              <h3 class="card-title"><i class="fa fa-info-circle"></i> ¿Cómo funciona?</h3>
            </div>
            <div class="card-body">
              <p>El CSV debe tener estas columnas en orden:</p>
              <table class="table table-bordered table-sm">
                <thead class="thead-light">
                  <tr><th>Columna</th><th>Obligatorio</th><th>Notas</th></tr>
                </thead>
                <tbody>
                  <tr><td><strong>nombre</strong></td><td><span class="badge badge-danger">Sí</span></td><td>Nombre del producto</td></tr>
                  <tr><td><strong>precio_venta</strong></td><td><span class="badge badge-danger">Sí</span></td><td>Ej: 250.00</td></tr>
                  <tr><td><strong>precio_compra</strong></td><td><span class="badge badge-secondary">No</span></td><td>Si se omite, queda en 1</td></tr>
                  <tr><td><strong>stock_minimo</strong></td><td><span class="badge badge-danger">Sí</span></td><td>Número entero mayor a 0</td></tr>
                  <tr><td><strong>proveedor</strong></td><td><span class="badge badge-danger">Sí</span></td><td>Nombre del proveedor — el sistema busca el más parecido</td></tr>
                  <tr><td><strong>categoria</strong></td><td><span class="badge badge-danger">Sí</span></td><td>Nombre de la categoría — el sistema busca la más parecida</td></tr>
                </tbody>
              </table>

              <div class="alert alert-warning mt-2 mb-2">
                <i class="fa fa-lightbulb"></i>
                <strong>El código se genera automáticamente.</strong> No tienes que ponerlo.
                El proveedor y la categoría se buscan por similitud de nombre — no necesitas saber el ID.
              </div>

              <a href="<?= $URL ?>/app/controllers/almacen/download_template.php" class="btn btn-success btn-sm">
                <i class="fa fa-download"></i> Descargar plantilla de ejemplo
              </a>
            </div>
          </div>
        </div>

        <!-- FORMULARIO -->
        <div class="col-md-6">
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title"><i class="fa fa-upload"></i> Subir archivo CSV</h3>
            </div>
            <div class="card-body">
              <?php include_once('../app/controllers/helpers/csrf.php'); ?>
              <form action="<?= $URL ?>/app/controllers/almacen/import_csv.php" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="form-group">
                  <div id="drop_csv"
                       onclick="document.getElementById('archivo_csv').click()"
                       style="border: 2px dashed #aaa; border-radius: 10px; padding: 40px; text-align: center; cursor: pointer; background: #f9f9f9; transition: background 0.2s;">
                    <i class="fa fa-file-csv fa-3x text-muted mb-2"></i>
                    <p class="mb-0 text-muted">Arrastra el CSV aquí o <strong>haz clic para buscar</strong></p>
                    <small class="text-muted">Solo .csv — máx. 2MB</small>
                  </div>
                  <input type="file" id="archivo_csv" name="archivo_csv" accept=".csv" style="display:none;" required>
                </div>

                <div class="d-flex">
                  <button type="submit" class="btn btn-primary mr-2">
                    <i class="fa fa-upload"></i> Importar
                  </button>
                  <a href="<?= $URL ?>/almacen" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Cancelar
                  </a>
                </div>

              </form>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
const dropCsv  = document.getElementById('drop_csv');
const inputCsv = document.getElementById('archivo_csv');

dropCsv.addEventListener('dragover', e => {
  e.preventDefault();
  dropCsv.style.background   = '#e8f4ff';
  dropCsv.style.borderColor  = '#007bff';
});
dropCsv.addEventListener('dragleave', () => {
  dropCsv.style.background  = '#f9f9f9';
  dropCsv.style.borderColor = '#aaa';
});
dropCsv.addEventListener('drop', e => {
  e.preventDefault();
  dropCsv.style.background  = '#f9f9f9';
  dropCsv.style.borderColor = '#aaa';
  const file = e.dataTransfer.files[0];
  if (file) asignarArchivo(file);
});
inputCsv.addEventListener('change', function() {
  if (this.files[0]) asignarArchivo(this.files[0]);
});

function asignarArchivo(file) {
  if (!file.name.endsWith('.csv')) {
    Swal.fire('Error', 'Solo se permiten archivos .csv', 'error');
    return;
  }
  const dt = new DataTransfer();
  dt.items.add(file);
  inputCsv.files = dt.files;
  dropCsv.innerHTML = `
    <i class="fa fa-check-circle fa-3x text-success mb-2"></i>
    <p class="mb-0 text-success font-weight-bold">${file.name}</p>
    <small class="text-muted">${(file.size/1024).toFixed(1)} KB — haz clic para cambiar</small>
  `;
}
</script>

<?php include('../layout/parte2.php'); ?>
