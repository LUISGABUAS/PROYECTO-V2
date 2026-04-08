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

$id_cliente       = $_POST['id_cliente'] ?? null;
$tipo_cliente     = trim($_POST['tipo_cliente'] ?? '');
$nombre_completo  = trim($_POST['nombre_completo'] ?? '');
$calle_numero     = trim($_POST['calle_numero'] ?? '');
$cp               = trim($_POST['cp'] ?? '');
$colonia          = trim($_POST['colonia'] ?? '');
$municipio        = trim($_POST['municipio'] ?? '');
$estado           = trim($_POST['estado'] ?? '');
$telefono         = trim($_POST['telefono'] ?? '');
$referencias      = trim($_POST['referencias'] ?? '');
$id_usuario       = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;
$id_rol_sesion    = $_SESSION['id_rol_sesion'] ?? null;

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
   ACTUALIZAR CLIENTE
========================= */
try {

    $sql = "UPDATE clientes SET
                tipo_cliente     = :tipo_cliente,
                nombre_completo  = :nombre_completo,
                calle_numero     = :calle_numero,
                cp               = :cp,
                colonia          = :colonia,
                municipio        = :municipio,
                estado           = :estado,
                telefono         = :telefono,
                referencias      = :referencias,
                id_vendedor      = :id_vendedor,
                updated_at       = NOW()
            WHERE id_cliente = :id_cliente";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':tipo_cliente'    => $tipo_cliente,
        ':nombre_completo' => $nombre_completo,
        ':calle_numero'    => $calle_numero,
        ':cp'              => $cp,
        ':colonia'         => $colonia,
        ':municipio'       => $municipio,
        ':estado'          => $estado,
        ':telefono'        => $telefono,
        ':referencias'     => $referencias,
        ':id_vendedor'     => $id_vendedor,
        ':id_cliente'      => $id_cliente
    ]);

    registrarAuditoria($pdo, $id_usuario, $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null, 'EDITAR CLIENTE', 'clientes', $id_cliente, $nombre_completo);

    $_SESSION['mensaje'] = '✅ Cliente actualizado correctamente';
    $_SESSION['icono'] = 'success';
    header('Location: ' . $URL . '/clientes/index.php');
    exit;

} catch (Exception $e) {

    error500('Error actualizando cliente', ['error' => $e->getMessage()]);
    $_SESSION['mensaje'] = '❌ Error al actualizar el cliente';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/clientes/edit.php?id=' . $id_cliente);
    exit;
}
