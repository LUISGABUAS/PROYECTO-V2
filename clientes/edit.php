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

<div class="content-wrapper">
<section class="content">
<div class="container-fluid">

<div class="card card-warning">
    <div class="card-header">
        <h3 class="card-title">Editar Cliente</h3>
    </div>


    <?php include_once('../app/controllers/helpers/csrf.php'); ?>
    <form action="<?= $URL ?>/app/controllers/clientes/edit.php" method="POST">
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
        <?php if ($_SESSION['id_rol_sesion'] != 21): // No mostrar si es vendedor ?>
        <div class="form-group">
            <label>Vendedor <span class="text-muted">(Opcional - dejar vacío si no pertenece a ninguno)</span></label>
            <select name="id_vendedor" class="form-control">
                <option value="">-- Sin vendedor asignado --</option>
                <?php
                // Obtener lista de vendedores
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

    </div>

    <div class="card-footer">
        <button type="submit" class="btn btn-success">
            Guardar Cambios
        </button>
        <a href="clientes.php" class="btn btn-secondary">
            Cancelar
        </a>
    </div>

    </form>
</div>

</div>
</section>
</div>

<!-- AUTOCOMPLETAR CP -->
<script>
document.getElementById('cp').addEventListener('keyup', function () {

    const cp = this.value.trim();

    if (cp.length !== 5) return;

    fetch(`https://sepomex.icalialabs.com/api/v1/zip_codes?zip_code=${cp}`)
        .then(res => res.json())
        .then(data => {

            if (!data.zip_codes || data.zip_codes.length === 0) {
                Swal.fire('Error','Código Postal no encontrado','error');
                return;
            }

            const coloniaSelect = document.getElementById('colonia');
            coloniaSelect.innerHTML = '<option value="">Seleccione colonia</option>';

            data.zip_codes.forEach(item => {
                coloniaSelect.innerHTML += `
                    <option value="${item.d_asenta}">
                        ${item.d_asenta}
                    </option>
                `;
            });

            document.getElementById('municipio').value = data.zip_codes[0].d_mnpio;
            document.getElementById('estado').value    = data.zip_codes[0].d_estado;
        })
        .catch(() => {
            Swal.fire('Error','No se pudo consultar el CP','error');
        });
});
</script>

<?php include('../layout/parte2.php'); ?>
<?php include('../layout/mensajes.php'); ?>