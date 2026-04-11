<?php
ini_set('display_errors', 0);
require_once(dirname(__DIR__, 2) . '/config.php');
include(__DIR__ . '/../helpers/csrf.php');
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
csrf_verify();

$response = ['success' => false, 'message' => ''];

if (!in_array(37, $_SESSION['permisos'] ?? [])) {
    $response['message'] = 'Sin permisos';
    echo json_encode($response); exit;
}

$id_ticket  = (int)($_POST['id_ticket']  ?? 0);
$estado     = $_POST['estado']    ?? '';
$respuesta  = trim($_POST['respuesta'] ?? '');
$id_tecnico = $_SESSION['id_usuario_sesion'] ?? null;

$estados_validos = ['pendiente', 'en_progreso', 'resuelto'];
if (!$id_ticket || !in_array($estado, $estados_validos)) {
    $response['message'] = 'Datos inválidos';
    echo json_encode($response); exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE tb_tickets
        SET estado = ?, respuesta = ?, id_tecnico = ?, fecha_actualizacion = NOW()
        WHERE id_ticket = ?
    ");
    $stmt->execute([$estado, $respuesta ?: null, $id_tecnico, $id_ticket]);

    include('../helpers/auditoria.php');
    registrarAuditoria($pdo, $id_tecnico, $_SESSION['nombre_usuario'] ?? null,
        'ACTUALIZAR TICKET', 'tb_tickets', $id_ticket, "Estado: $estado");

    $response['success'] = true;
    $response['message'] = 'Ticket actualizado correctamente';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

ob_clean();
echo json_encode($response);
