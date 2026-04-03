<?php
include('../../config.php');
include(__DIR__ . '/../helpers/csrf.php');
csrf_verify();
include('../helpers/auditoria.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ".$URL."/clientes/create.php");
    exit;
}

$tipo_cliente = $_POST['tipo_cliente'] ?? null;
$nombre       = $_POST['nombre_completo'] ?? null;
$telefono     = $_POST['telefono'] ?? null;
$calle        = $_POST['calle_numero'] ?? null;
$colonia      = $_POST['colonia'] ?? null;
$municipio    = $_POST['municipio'] ?? null;
$estado       = $_POST['estado'] ?? null;
$cp           = $_POST['cp'] ?? null;
$referencias  = trim($_POST['referencias'] ?? null);

if (
    !$tipo_cliente || !$nombre || !$telefono ||
    !$calle || !$colonia || !$municipio || !$estado || !$cp
) {
    $_SESSION['mensaje'] = "❌ Faltan datos obligatorios";
    $_SESSION['icono']   = "error";
    header("Location: ".$URL."/clientes/create.php");
    exit;
}

try {
    $sql = "INSERT INTO clientes 
    (tipo_cliente, nombre_completo, telefono, calle_numero, colonia, municipio, estado, cp, referencias)
    VALUES (?,?,?,?,?,?,?,?,?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $tipo_cliente,
        $nombre,
        $telefono,
        $calle,
        $colonia,
        $municipio,
        $estado,
        $cp,
        $referencias
    ]);

    $id_usuario_audit = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;
    $nombre_audit = $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null;
    registrarAuditoria($pdo, $id_usuario_audit, $nombre_audit, 'CREAR CLIENTE', 'clientes', $pdo->lastInsertId(), $nombre);

    $_SESSION['mensaje'] = "✅ Cliente registrado correctamente";
    $_SESSION['icono']   = "success";

    header("Location: ".$URL."/clientes/create.php");
    exit;

} catch (Exception $e) {
    $_SESSION['mensaje'] = "❌ Error al guardar el cliente";
    $_SESSION['icono']   = "error";
    header("Location: ".$URL."/clientes/create.php");
    exit;
}
