<?php
include('../../config.php');
include(__DIR__ . '/../helpers/csrf.php');
csrf_verify();
include('../helpers/auditoria.php');

$id = $_POST['id'] ?? null;

if (!$id) {
    session_start();
    $_SESSION['mensaje'] = '❌ Cliente no válido';
    $_SESSION['icono'] = 'error';
    header("Location: ../../../clientes/clientes.php");
    exit;
}

$sql = "DELETE FROM clientes WHERE id_cliente = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

session_start();
$id_usuario_audit = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;
$nombre_audit = $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null;
registrarAuditoria($pdo, $id_usuario_audit, $nombre_audit, 'ELIMINAR CLIENTE FORÁNEO', 'clientes', $id, "Cliente ID: $id eliminado");

$_SESSION['mensaje'] = '✅ Cliente eliminado correctamente';
$_SESSION['icono'] = 'success';

header("Location: ../../../clientes/foraneos.php");
