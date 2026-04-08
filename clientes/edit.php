<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

/* =========================
   OBTENER CLIENTE
========================= */
$id_cliente = $_GET['id'] ?? null;

if (!$id_cliente) {
    echo "<h4 class='text-danger'>Cliente no válido</h4>";
    exit;
}

$sql = "SELECT * FROM clientes WHERE id_cliente = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_cliente]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo "<h4 class='text-danger'>Cliente no encontrado</h4>";
    exit;
}
?>

<?php if (isset($_SESSION['mensaje'])): ?>
<script>
Swal.fire({
    icon: '<?= $_SESSION['icono'] ?>',
    title: 'Atención',
    text: <?= json_encode($_SESSION['mensaje']) ?>,
    confirmButtonText: 'Entendido'
});
</script>
<?php
unset($_SESSION['mensaje'], $_SESSION['icono']);
endif;
?>

<style>
.dir-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 10px;
    background: #fff;
    transition: box-shadow .15s;
}
.dir-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
}
.dir-card.is-principal {
    border-left: 4px solid #007bff;
    background: #f0f7ff;
}
.dir-card .dir-address {
    font-weight: 600;
    color: #212529;
    margin-bottom: 2px;
}
.dir-card .dir-detail {
    color: #6c757d;
    font-size: .85rem;
    line-height: 1.5;
}
.dir-card .dir-refs {
    font-size: .8rem;
    color: #868e96;
    margin-top: 4px;
    font-style: italic;
}
.dir-empty {
    text-align: center;
    padding: 32px 16px;
    color: #adb5bd;
}
.dir-empty i {
    font-size: 2.5rem;
    display: block;
    margin-bottom: 8px;
}
#nd_cp_spinner {
    background: transparent;
    border-left: none;
}
.modal-header.bg-primary .close {
    opacity: .8;
}
.modal-header.bg-primary .close:hover {
    opacity: 1;
}
</style>

<div class="content-wrapper">
<section class="content">
<div class="container-fluid">

<div class="card card-warning">
    <div class="card-header">
        <h3 class="card-title">Editar Cliente</h3>
    </div>

    <?php include_once('../app/controllers/helpers/csrf.php'); ?>
    <form id="form_editar_cliente" action="<?= $URL ?>/app/controllers/clientes/edit.php" method="POST">
        <?= csrf_field() ?>

        <input type="hidden" name="id_cliente" value="<?= $cliente['id_cliente'] ?>">

        <div class="card-body">

            <!-- TIPO CLIENTE -->
            <div class="form-group">
                <label>Tipo de cliente</label>
                <select name="tipo_cliente" class="form-control" required>
                    <option value="">Seleccione</option>
                    <option value="local"   <?= $cliente['tipo_cliente']=='local'?'selected':'' ?>>Local</option>
                    <option value="foraneo" <?= $cliente['tipo_cliente']=='foraneo'?'selected':'' ?>>Foráneo</option>
                </select>
            </div>

            <!-- VENDEDOR (solo para admin) -->
            <?php if ($_SESSION['id_rol_sesion'] != 21): ?>
            <div class="form-group">
                <label>Vendedor <span class="text-muted">(Opcional)</span></label>
                <select name="id_vendedor" class="form-control">
                    <option value="">-- Sin vendedor asignado --</option>
                    <?php
                    $sql_vendedores = "SELECT us.id, us.nombres FROM tb_usuario us
                                       INNER JOIN tb_roles_permisos rol ON us.id_rol = rol.id_rol
                                       WHERE rol.id_permiso = 21
                                       ORDER BY us.nombres ASC";
                    $stmt_vendedores = $pdo->prepare($sql_vendedores);
                    $stmt_vendedores->execute();
                    $vendedores = $stmt_vendedores->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($vendedores as $vendedor):
                        $selected = ($cliente['id_vendedor'] == $vendedor['id']) ? 'selected' : '';
                    ?>
                        <option value="<?= $vendedor['id'] ?>" <?= $selected ?>>
                            <?= htmlspecialchars($vendedor['nombres']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- NOMBRE -->
            <div class="form-group">
                <label>Nombre completo</label>
                <input type="text" name="nombre_completo" class="form-control"
                       value="<?= htmlspecialchars($cliente['nombre_completo']) ?>" required>
            </div>

            <!-- CALLE -->
            <div class="form-group">
                <label>Calle y número</label>
                <input type="text" name="calle_numero" class="form-control"
                       value="<?= htmlspecialchars($cliente['calle_numero']) ?>" required>
            </div>

            <!-- CP -->
            <div class="form-group">
                <label>Código Postal</label>
                <input type="text" id="cp" name="cp" class="form-control"
                       value="<?= htmlspecialchars($cliente['cp']) ?>" maxlength="5" required>
            </div>

            <!-- COLONIA -->
            <div class="form-group">
                <label>Colonia</label>
                <select id="colonia" name="colonia" class="form-control" required>
                    <option value="<?= htmlspecialchars($cliente['colonia']) ?>" selected>
                        <?= htmlspecialchars($cliente['colonia']) ?>
                    </option>
                </select>
            </div>

            <!-- MUNICIPIO -->
            <div class="form-group">
                <label>Municipio</label>
                <input type="text" id="municipio" name="municipio" class="form-control"
                       value="<?= htmlspecialchars($cliente['municipio']) ?>" readonly>
            </div>

            <!-- ESTADO -->
            <div class="form-group">
                <label>Estado</label>
                <input type="text" id="estado" name="estado" class="form-control"
                       value="<?= htmlspecialchars($cliente['estado']) ?>" readonly>
            </div>

            <!-- TELÉFONO -->
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" class="form-control"
                       value="<?= htmlspecialchars($cliente['telefono']) ?>">
            </div>

            <!-- REFERENCIAS -->
            <div class="form-group">
                <label>Referencias del domicilio</label>
                <textarea name="referencias" class="form-control" rows="3"><?= htmlspecialchars($cliente['referencias']) ?></textarea>
            </div>

            <hr>

            <!-- ==============================
                 DIRECCIONES ADICIONALES
            ============================== -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0">
                        <i class="fas fa-map-marker-alt text-danger"></i>
                        Direcciones de entrega
                    </h5>
                    <small class="text-muted">La dirección principal se actualiza con los campos de arriba</small>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="abrirModalDireccion()">
                    <i class="fas fa-plus"></i> Agregar dirección
                </button>
            </div>

            <div id="direcciones_list">
                <div class="dir-empty">
                    <i class="fas fa-circle-notch fa-spin"></i>
                    Cargando direcciones...
                </div>
            </div>

        </div><!-- card-body -->

        <div class="card-footer">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
            <a href="index.php" class="btn btn-secondary ml-2">
                Cancelar
            </a>
        </div>

    </form>
</div>

</div>
</section>
</div>

<!-- ==============================
     MODAL: AGREGAR DIRECCIÓN
============================== -->
<div class="modal fade" id="modalDireccion" tabindex="-1" role="dialog" aria-labelledby="modalDireccionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDireccionLabel">
                    <i class="fas fa-map-marker-alt"></i> Nueva Dirección
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Código Postal</strong> <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" id="nd_cp" class="form-control" maxlength="5"
                                       placeholder="Ej: 44100" autocomplete="off">
                                <div class="input-group-append">
                                    <span class="input-group-text" id="nd_cp_spinner" style="display:none; background:white; border-left:none;">
                                        <i class="fas fa-spinner fa-spin text-primary"></i>
                                    </span>
                                </div>
                            </div>
                            <small class="form-text text-muted">5 dígitos — autocompletar</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Municipio</label>
                            <input type="text" id="nd_municipio" class="form-control bg-light" readonly
                                   placeholder="Se llena automático">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Estado</label>
                            <input type="text" id="nd_estado" class="form-control bg-light" readonly
                                   placeholder="Se llena automático">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><strong>Colonia</strong> <span class="text-danger">*</span></label>
                    <select id="nd_colonia" class="form-control" disabled>
                        <option value="">— Ingresa el CP primero —</option>
                    </select>
                </div>

                <div class="form-group">
                    <label><strong>Calle y número</strong> <span class="text-danger">*</span></label>
                    <input type="text" id="nd_calle" class="form-control"
                           placeholder="Ej: Av. Hidalgo #123 Int. 4">
                </div>

                <div class="form-group mb-0">
                    <label>Referencias <span class="text-muted small">(opcional)</span></label>
                    <textarea id="nd_referencias" class="form-control" rows="2"
                              placeholder="Ej: Entre calles Morelos y Juárez, fachada azul"></textarea>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btn_guardar_dir" onclick="guardarDireccion()">
                    <i class="fas fa-save"></i> Guardar Dirección
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==============================
     JAVASCRIPT
============================== -->
<script>
const ID_CLIENTE = <?= $cliente['id_cliente'] ?>;
const URL_APP    = '<?= $URL ?>';

// ─── Utilidad: buscar CP en Sepomex ──────────────────────────────────────────
function buscarCP(cpValue, opts) {
    // opts: { coloniaId, municipioId, estadoId, spinnerId }
    if (cpValue.length !== 5) return;

    const spinner = opts.spinnerId ? document.getElementById(opts.spinnerId) : null;
    if (spinner) spinner.style.display = 'inline-flex';

    fetch(`https://sepomex.icalialabs.com/api/v1/zip_codes?zip_code=${cpValue}`)
        .then(r => r.json())
        .then(data => {
            if (spinner) spinner.style.display = 'none';

            if (!data.zip_codes || data.zip_codes.length === 0) {
                Swal.fire('Código Postal no encontrado', 'Verifica que sea correcto', 'warning');
                return;
            }

            const coloniaEl   = document.getElementById(opts.coloniaId);
            const municipioEl = document.getElementById(opts.municipioId);
            const estadoEl    = document.getElementById(opts.estadoId);

            if (coloniaEl) {
                coloniaEl.innerHTML = '<option value="">— Selecciona colonia —</option>';
                data.zip_codes.forEach(item => {
                    coloniaEl.innerHTML += `<option value="${item.d_asenta}">${item.d_asenta}</option>`;
                });
                coloniaEl.disabled = false;
            }
            if (municipioEl) municipioEl.value = data.zip_codes[0].d_mnpio;
            if (estadoEl)    estadoEl.value    = data.zip_codes[0].d_estado;
        })
        .catch(() => {
            if (spinner) spinner.style.display = 'none';
            Swal.fire('Error', 'No se pudo consultar el Código Postal', 'error');
        });
}

// CP del formulario principal
document.getElementById('cp').addEventListener('input', function () {
    buscarCP(this.value.trim(), {
        coloniaId:   'colonia',
        municipioId: 'municipio',
        estadoId:    'estado'
    });
});

// CP del modal de nueva dirección
document.getElementById('nd_cp').addEventListener('input', function () {
    const cp = this.value.trim();

    // Resetear si el usuario borra
    if (cp.length < 5) {
        const coloniaEl = document.getElementById('nd_colonia');
        coloniaEl.innerHTML = '<option value="">— Ingresa el CP primero —</option>';
        coloniaEl.disabled  = true;
        document.getElementById('nd_municipio').value = '';
        document.getElementById('nd_estado').value    = '';
        return;
    }

    buscarCP(cp, {
        coloniaId:   'nd_colonia',
        municipioId: 'nd_municipio',
        estadoId:    'nd_estado',
        spinnerId:   'nd_cp_spinner'
    });
});

// ─── Modal ────────────────────────────────────────────────────────────────────
function abrirModalDireccion() {
    // Limpiar campos
    document.getElementById('nd_cp').value         = '';
    document.getElementById('nd_calle').value       = '';
    document.getElementById('nd_referencias').value = '';
    document.getElementById('nd_municipio').value   = '';
    document.getElementById('nd_estado').value      = '';

    const coloniaEl = document.getElementById('nd_colonia');
    coloniaEl.innerHTML = '<option value="">— Ingresa el CP primero —</option>';
    coloniaEl.disabled  = true;

    $('#modalDireccion').modal('show');
}

// ─── Guardar dirección ────────────────────────────────────────────────────────
function guardarDireccion() {
    const cp         = document.getElementById('nd_cp').value.trim();
    const colonia    = document.getElementById('nd_colonia').value.trim();
    const municipio  = document.getElementById('nd_municipio').value.trim();
    const estado     = document.getElementById('nd_estado').value.trim();
    const calle      = document.getElementById('nd_calle').value.trim();
    const referencias = document.getElementById('nd_referencias').value.trim();

    if (!cp || !colonia || !municipio || !estado || !calle) {
        Swal.fire('Campos incompletos', 'Completa CP, colonia y calle antes de guardar', 'warning');
        return;
    }

    const btn = document.getElementById('btn_guardar_dir');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    const formData = new FormData();
    formData.append('accion',       'crear');
    formData.append('id_cliente',   ID_CLIENTE);
    formData.append('calle_numero', calle);
    formData.append('colonia',      colonia);
    formData.append('municipio',    municipio);
    formData.append('estado',       estado);
    formData.append('cp',           cp);
    formData.append('referencias',  referencias);
    formData.append('csrf_token',   document.querySelector('input[name="csrf_token"]').value);

    fetch(`${URL_APP}/app/controllers/clientes/direcciones.php`, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Guardar Dirección';

        if (data.success) {
            $('#modalDireccion').modal('hide');
            Swal.fire({
                icon: 'success',
                title: 'Dirección agregada',
                text: 'La nueva dirección fue guardada correctamente',
                timer: 1800,
                showConfirmButton: false
            });
            cargarDirecciones();
        } else {
            Swal.fire('No se pudo guardar', data.message || 'Error desconocido', 'error');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Guardar Dirección';
        console.error(err);
        Swal.fire('Error', 'Ocurrió un problema al guardar la dirección', 'error');
    });
}

// ─── Cargar y mostrar direcciones ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', cargarDirecciones);

function cargarDirecciones() {
    fetch(`${URL_APP}/app/controllers/clientes/direcciones.php?accion=listar&id_cliente=${ID_CLIENTE}`, {
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderDirecciones(data.data);
        } else {
            document.getElementById('direcciones_list').innerHTML =
                `<div class="alert alert-warning">No se pudieron cargar las direcciones</div>`;
        }
    })
    .catch(() => {
        document.getElementById('direcciones_list').innerHTML =
            `<div class="alert alert-danger">Error al conectar con el servidor</div>`;
    });
}

function renderDirecciones(dirs) {
    const lista = document.getElementById('direcciones_list');

    if (!dirs.length) {
        lista.innerHTML = `
            <div class="dir-empty">
                <i class="fas fa-map-marker-alt"></i>
                <p class="mb-0">Sin direcciones registradas</p>
            </div>`;
        return;
    }

    lista.innerHTML = dirs.map(dir => {
        const esPrincipal = dir.es_principal == 1;

        // La dirección principal no se puede eliminar nunca
        // Las adicionales solo si hay más de 1 dirección en total (siempre cumple si llegamos aquí)
        const puedeEliminar = !esPrincipal;

        const badgePrincipal = esPrincipal
            ? `<span class="badge badge-primary ml-2"><i class="fas fa-star"></i> Principal</span>`
            : '';

        const btnEliminar = puedeEliminar
            ? `<button type="button"
                    class="btn btn-sm btn-outline-danger"
                    onclick="eliminarDireccion(${dir.id})"
                    title="Eliminar esta dirección">
                    <i class="fas fa-trash-alt"></i>
               </button>`
            : `<button type="button"
                    class="btn btn-sm btn-secondary"
                    disabled
                    title="La dirección principal no se puede eliminar aquí. Edítala en los campos de arriba">
                    <i class="fas fa-lock"></i>
               </button>`;

        const referencias = dir.referencias
            ? `<div class="dir-refs"><i class="fas fa-sticky-note"></i> ${dir.referencias}</div>`
            : '';

        return `
            <div class="dir-card ${esPrincipal ? 'is-principal' : ''}">
                <div class="d-flex justify-content-between align-items-start">
                    <div style="flex:1; min-width:0;">
                        <div class="dir-address">
                            <i class="fas fa-map-pin ${esPrincipal ? 'text-primary' : 'text-secondary'}"></i>
                            ${dir.calle_numero}
                            ${badgePrincipal}
                        </div>
                        <div class="dir-detail">
                            ${dir.colonia} &mdash; ${dir.municipio}, ${dir.estado} &nbsp;
                            <span class="badge badge-light border">CP ${dir.cp}</span>
                        </div>
                        ${referencias}
                    </div>
                    <div class="ml-3 flex-shrink-0">
                        ${btnEliminar}
                    </div>
                </div>
            </div>`;
    }).join('');
}

// ─── Eliminar dirección ───────────────────────────────────────────────────────
function eliminarDireccion(idDireccion) {
    Swal.fire({
        title: '¿Eliminar dirección?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash-alt"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (!result.isConfirmed) return;

        const formData = new FormData();
        formData.append('accion',      'eliminar');
        formData.append('id',          idDireccion);
        formData.append('id_cliente',  ID_CLIENTE);
        formData.append('csrf_token',  document.querySelector('input[name="csrf_token"]').value);

        Swal.fire({
            title: 'Eliminando...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });

        fetch(`${URL_APP}/app/controllers/clientes/direcciones.php`, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Dirección eliminada',
                    timer: 1500,
                    showConfirmButton: false
                });
                cargarDirecciones(); // Solo refresca la lista, sin recargar toda la página
            } else {
                Swal.fire('No se pudo eliminar', data.message || 'Error desconocido', 'warning');
            }
        })
        .catch(() => {
            Swal.fire('Error', 'Ocurrió un problema al eliminar la dirección', 'error');
        });
    });
}

// ─── Submit del formulario principal ─────────────────────────────────────────
document.getElementById('form_editar_cliente').addEventListener('submit', function(e) {
    e.preventDefault();

    Swal.fire({
        title: '¿Guardar cambios?',
        text: 'Se actualizarán los datos del cliente',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-save"></i> Sí, guardar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            this.submit();
        }
    });
});
</script>

<?php include('../layout/parte2.php'); ?>
<?php include('../layout/mensajes.php'); ?>
