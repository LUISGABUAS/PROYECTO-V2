<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

if (!in_array(36, $_SESSION['permisos'])):
    include('../layout/parte2.php'); exit;
endif;
?>

<?php if (isset($_SESSION['mensaje'])): ?>
<script>
Swal.fire({
    icon: 'error', title: 'Error',
    text: <?= json_encode($_SESSION['mensaje']) ?>,
    confirmButtonText: 'Entendido'
});
</script>
<?php unset($_SESSION['mensaje']); endif; ?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0"><i class="fas fa-plus-circle text-primary"></i> Nuevo Ticket de Soporte</h1>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-ticket-alt"></i> Descripción del problema</h3>
            </div>

            <?php include_once('../app/controllers/helpers/csrf.php'); ?>
            <form action="../app/controllers/tickets/create.php" method="POST"
                  enctype="multipart/form-data" id="form_ticket">
              <?= csrf_field() ?>

              <div class="card-body">

                <!-- TÍTULO -->
                <div class="form-group">
                  <label><strong>Título del problema <span class="text-danger">*</span></strong></label>
                  <input type="text" name="titulo" class="form-control"
                         placeholder="Ej: Error al guardar ventas con comprobante"
                         maxlength="200" required>
                </div>

                <!-- IMPORTANCIA -->
                <div class="form-group">
                  <label><strong>Nivel de importancia <span class="text-danger">*</span></strong></label>
                  <div class="row">
                    <?php
                    $importancias = [
                        'baja'    => ['color'=>'secondary', 'icon'=>'fa-arrow-down',  'desc'=>'No urgente, puede esperar'],
                        'media'   => ['color'=>'info',      'icon'=>'fa-minus',       'desc'=>'Afecta pero hay alternativa'],
                        'alta'    => ['color'=>'warning',   'icon'=>'fa-arrow-up',    'desc'=>'Afecta operaciones importantes'],
                        'critica' => ['color'=>'danger',    'icon'=>'fa-fire',        'desc'=>'El sistema no funciona correctamente'],
                    ];
                    foreach ($importancias as $val => $info):
                    ?>
                    <div class="col-md-3 mb-2">
                      <label class="w-100">
                        <input type="radio" name="importancia" value="<?= $val ?>"
                               class="d-none imp-radio" <?= $val === 'media' ? 'checked' : '' ?>>
                        <div class="imp-card border rounded p-2 text-center" style="cursor:pointer;"
                             data-color="<?= $info['color'] ?>">
                          <i class="fas <?= $info['icon'] ?> fa-lg text-<?= $info['color'] ?>"></i>
                          <div><strong class="text-<?= $info['color'] ?>"><?= ucfirst($val) ?></strong></div>
                          <small class="text-muted"><?= $info['desc'] ?></small>
                        </div>
                      </label>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </div>

                <!-- DESCRIPCIÓN -->
                <div class="form-group">
                  <label><strong>Descripción detallada <span class="text-danger">*</span></strong></label>
                  <textarea name="descripcion" class="form-control" rows="6"
                            placeholder="Describe el problema con el mayor detalle posible:
- ¿Qué estabas haciendo cuando ocurrió?
- ¿Qué mensaje de error aparece?
- ¿Desde cuándo ocurre?
- ¿En qué sección del sistema?" required></textarea>
                </div>

                <!-- ARCHIVOS -->
                <div class="form-group">
                  <label><strong><i class="fas fa-paperclip text-secondary"></i> Archivos adjuntos</strong>
                    <small class="text-muted ml-1">(capturas de pantalla, videos, documentos — máx. 20MB c/u)</small>
                  </label>

                  <div id="drop_area"
                       style="border:2px dashed #aaa;border-radius:10px;padding:30px;text-align:center;cursor:pointer;background:#f9f9f9;"
                       onclick="document.getElementById('archivos_input').click()">
                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                    <p class="mb-0 text-muted">Arrastra archivos aquí o <strong>haz clic para buscar</strong></p>
                    <small class="text-muted">PDF, JPG, PNG, GIF, MP4, DOC, XLS, TXT</small>
                  </div>

                  <input type="file" id="archivos_input" name="archivos[]" multiple
                         accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx,.txt,.mp4,.mov,.avi"
                         style="display:none;">

                  <div id="lista_archivos" class="mt-2"></div>
                </div>

              </div>

              <div class="card-footer">
                <a href="index.php" class="btn btn-outline-secondary">
                  <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary float-right">
                  <i class="fas fa-paper-plane"></i> Enviar Ticket
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Resaltar tarjeta seleccionada de importancia
document.querySelectorAll('.imp-radio').forEach(radio => {
  radio.addEventListener('change', () => {
    document.querySelectorAll('.imp-card').forEach(c => {
      c.style.borderColor = '';
      c.style.backgroundColor = '';
    });
    const card = radio.nextElementSibling;
    const color = card.dataset.color;
    const colorMap = {secondary:'#6c757d',info:'#17a2b8',warning:'#ffc107',danger:'#dc3545'};
    card.style.borderColor = colorMap[color] || '#aaa';
    card.style.backgroundColor = colorMap[color] + '22';
  });
  if (radio.checked) radio.dispatchEvent(new Event('change'));
});

// Drag & drop + lista de archivos
const dropArea  = document.getElementById('drop_area');
const inputFile = document.getElementById('archivos_input');
const listaDiv  = document.getElementById('lista_archivos');
let archivosSeleccionados = new DataTransfer();

dropArea.addEventListener('dragover', e => { e.preventDefault(); dropArea.style.background = '#e8f4ff'; dropArea.style.borderColor = '#007bff'; });
dropArea.addEventListener('dragleave', () => { dropArea.style.background = '#f9f9f9'; dropArea.style.borderColor = '#aaa'; });
dropArea.addEventListener('drop', e => {
  e.preventDefault();
  dropArea.style.background = '#f9f9f9'; dropArea.style.borderColor = '#aaa';
  [...e.dataTransfer.files].forEach(f => agregarArchivo(f));
});

inputFile.addEventListener('change', function() {
  [...this.files].forEach(f => agregarArchivo(f));
  this.value = '';
});

function agregarArchivo(file) {
  const maxSize = 20 * 1024 * 1024;
  if (file.size > maxSize) {
    Swal.fire('Archivo muy grande', `"${file.name}" supera los 20MB`, 'warning'); return;
  }
  archivosSeleccionados.items.add(file);
  inputFile.files = archivosSeleccionados.files;
  renderLista();
}

function quitarArchivo(idx) {
  const nuevo = new DataTransfer();
  [...archivosSeleccionados.files].forEach((f, i) => { if (i !== idx) nuevo.items.add(f); });
  archivosSeleccionados = nuevo;
  inputFile.files = archivosSeleccionados.files;
  renderLista();
}

function renderLista() {
  listaDiv.innerHTML = '';
  [...archivosSeleccionados.files].forEach((f, i) => {
    const kb = (f.size / 1024).toFixed(1);
    listaDiv.innerHTML += `
      <div class="d-flex align-items-center justify-content-between border rounded px-3 py-2 mb-1 bg-light">
        <span><i class="fas fa-file text-muted mr-2"></i>${f.name} <small class="text-muted">(${kb} KB)</small></span>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="quitarArchivo(${i})">
          <i class="fas fa-times"></i>
        </button>
      </div>`;
  });
}

document.getElementById('form_ticket').addEventListener('submit', function(e) {
  Swal.fire({ title: 'Enviando ticket...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
});
</script>

<?php include('../layout/parte2.php'); ?>
