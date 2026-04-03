<?php
include('../../config.php');
include(__DIR__ . '/../helpers/csrf.php');
csrf_verify();
include('../helpers/auditoria.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje'] = 'Acceso no permitido';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/clientes');
    exit;
}

/* =========================
   RECIBIR Y VALIDAR DATOS
========================= */
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

if (
    !$id_cliente ||
    $tipo_cliente === '' ||
    $nombre_completo === '' ||
    $calle_numero === '' ||
    $cp === '' ||
    $colonia === '' ||
    $municipio === '' ||
    $estado === ''
) {
    $_SESSION['mensaje'] = 'Todos los campos obligatorios deben completarse';
    $_SESSION['icono'] = 'warning';
    header('Location: ' . $URL . '/clientes/edit.php?id=' . $id_cliente);
    exit;
}

/* =========================
   VERIFICAR CLIENTE EXISTE
========================= */
$check = $pdo->prepare("SELECT id_cliente FROM clientes WHERE id_cliente = ?");
$check->execute([$id_cliente]);

if ($check->rowCount() === 0) {
    $_SESSION['mensaje'] = 'Cliente no encontrado';
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
        ':id_cliente'      => $id_cliente
    ]);

    $id_usuario_audit = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;
    $nombre_audit = $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null;
    registrarAuditoria($pdo, $id_usuario_audit, $nombre_audit, 'EDITAR CLIENTE', 'clientes', $id_cliente, $nombre_completo);

    $_SESSION['mensaje'] = 'Cliente actualizado correctamente';
    $_SESSION['icono'] = 'success';
    header('Location: ' . $URL . '/clientes/index.php');
    exit;

} catch (PDOException $e) {

    $_SESSION['mensaje'] = 'Error al actualizar el cliente';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/clientes/edit.php?id=' . $id_cliente);
    exit;
}
