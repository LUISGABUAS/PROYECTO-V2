<?php
require_once(dirname(__DIR__, 2) . '/config.php');
include(__DIR__ . '/../helpers/csrf.php');
include(__DIR__ . '/../helpers/validador.php');
csrf_verify();
include('../helpers/auditoria.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje'] = 'Acceso no permitido';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/clientes');
    exit;
}

/* =========================
   RECIBIR Y VALIDAR DATOS
========================= */
$errores = validarDatos(['id_cliente', 'tipo_cliente', 'nombre_completo', 'calle_numero', 'cp', 'colonia', 'municipio', 'estado']);
if (!empty($errores)) {
    error400('Faltan datos obligatorios', $errores);
    $_SESSION['mensaje'] = '❌ Faltan datos obligatorios';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/clientes/edit.php?id=' . ($_POST['id_cliente'] ?? ''));
    exit;
}

$id_cliente        = $_POST['id_cliente'] ?? null;
$tipo_cliente      = trim($_POST['tipo_cliente'] ?? '');
$nombre_completo   = trim($_POST['nombre_completo'] ?? '');
$calle_numero      = trim($_POST['calle_numero'] ?? '');
$cp                = trim($_POST['cp'] ?? '');
$colonia           = trim($_POST['colonia'] ?? '');
$municipio         = trim($_POST['municipio'] ?? '');
$estado            = trim($_POST['estado'] ?? '');
$telefono          = trim($_POST['telefono'] ?? '');
$referencias       = trim($_POST['referencias'] ?? '');
$id_direccion_edit = !empty($_POST['id_direccion_edit']) ? (int)$_POST['id_direccion_edit'] : null;
$id_usuario        = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;
$id_rol_sesion     = $_SESSION['id_rol_sesion'] ?? null;

// Determinar id_vendedor según el rol
if ($id_rol_sesion == 21) {
    // Si es vendedor, mantener su propio ID
    $id_vendedor = $id_usuario;
} else {
    // Si es admin, permitir cambiar el vendedor
    $id_vendedor = $_POST['id_vendedor'] ?? null;
    $id_vendedor = ($id_vendedor === '' || $id_vendedor === null) ? null : $id_vendedor;
}

/* =========================
   VERIFICAR CLIENTE EXISTE
========================= */
$check = $pdo->prepare("SELECT id_cliente FROM clientes WHERE id_cliente = ?");
$check->execute([$id_cliente]);

if ($check->rowCount() === 0) {
    error400('Cliente no encontrado');
    $_SESSION['mensaje'] = '❌ Cliente no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/clientes');
    exit;
}

/* =========================
   VERIFICAR NOMBRE DUPLICADO
========================= */
$check_nombre = $pdo->prepare("SELECT id_cliente FROM clientes WHERE nombre_completo = ? AND id_cliente != ? LIMIT 1");
$check_nombre->execute([$nombre_completo, $id_cliente]);
if ($check_nombre->fetch()) {
    $_SESSION['mensaje'] = '⚠️ Ya existe otro cliente con ese nombre';
    $_SESSION['icono'] = 'warning';
    header('Location: ' . $URL . '/clientes/edit.php?id=' . $id_cliente);
    exit;
}

/* =========================
   ACTUALIZAR CLIENTE
========================= */
try {
    // Iniciar transacción
    $pdo->beginTransaction();

    // Determinar si la dirección a editar es la principal
    $es_principal_edit = true;
    if ($id_direccion_edit) {
        $chk = $pdo->prepare("SELECT es_principal FROM clientes_direcciones WHERE id = ? AND id_cliente = ?");
        $chk->execute([$id_direccion_edit, $id_cliente]);
        $dir_info = $chk->fetch();
        $es_principal_edit = !$dir_info || $dir_info['es_principal'] == 1;
    }

    // 1. Actualizar cliente (campos de dirección solo si es la principal)
    if ($es_principal_edit) {
        $stmt = $pdo->prepare("UPDATE clientes SET
                    tipo_cliente = ?, nombre_completo = ?, calle_numero = ?, cp = ?,
                    colonia = ?, municipio = ?, estado = ?, telefono = ?,
                    referencias = ?, id_vendedor = ?, updated_at = NOW()
                WHERE id_cliente = ?");
        $stmt->execute([
            $tipo_cliente, $nombre_completo, $calle_numero, $cp,
            $colonia, $municipio, $estado, $telefono,
            $referencias, $id_vendedor, $id_cliente
        ]);
    } else {
        $stmt = $pdo->prepare("UPDATE clientes SET
                    tipo_cliente = ?, nombre_completo = ?, telefono = ?,
                    id_vendedor = ?, updated_at = NOW()
                WHERE id_cliente = ?");
        $stmt->execute([$tipo_cliente, $nombre_completo, $telefono, $id_vendedor, $id_cliente]);
    }

    // 2. Actualizar la dirección correspondiente en clientes_direcciones
    if ($id_direccion_edit && !$es_principal_edit) {
        // Editar dirección adicional específica
        $stmt_dir = $pdo->prepare("UPDATE clientes_direcciones SET
                    calle_numero = ?, colonia = ?, municipio = ?, estado = ?, cp = ?,
                    referencias = ?, actualizada_en = NOW()
                WHERE id = ? AND id_cliente = ?");
        $stmt_dir->execute([$calle_numero, $colonia, $municipio, $estado, $cp, $referencias, $id_direccion_edit, $id_cliente]);
    } else {
        // Editar dirección principal
        $stmt_dir = $pdo->prepare("UPDATE clientes_direcciones SET
                    calle_numero = ?, colonia = ?, municipio = ?, estado = ?, cp = ?,
                    referencias = ?, actualizada_en = NOW()
                WHERE id_cliente = ? AND es_principal = 1");
        $stmt_dir->execute([$calle_numero, $colonia, $municipio, $estado, $cp, $referencias, $id_cliente]);

        if ($stmt_dir->rowCount() === 0) {
            $stmt_ins = $pdo->prepare("INSERT INTO clientes_direcciones
                        (id_cliente, calle_numero, colonia, municipio, estado, cp, referencias, es_principal, activa)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)");
            $stmt_ins->execute([$id_cliente, $calle_numero, $colonia, $municipio, $estado, $cp, $referencias]);
        }
    }

    // Confirmar transacción
    $pdo->commit();

    registrarAuditoria($pdo, $id_usuario, $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null, 'EDITAR CLIENTE', 'clientes', $id_cliente, $nombre_completo);

    $_SESSION['mensaje'] = '✅ Cliente actualizado correctamente';
    $_SESSION['icono'] = 'success';
    
    // Redirigir a la página anterior según el tipo de cliente
    $redirect = ($tipo_cliente === 'foraneo') 
        ? $URL . '/clientes/foraneos.php'
        : $URL . '/clientes/locales.php';
    
    header('Location: ' . $redirect);
    exit;

} catch (Exception $e) {
    // Revertir si hay error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error500('Error actualizando cliente', ['error' => $e->getMessage()]);
    $_SESSION['mensaje'] = '❌ Error al actualizar el cliente';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/clientes/edit.php?id=' . $id_cliente);
    exit;
}
