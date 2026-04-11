<?php
ini_set('display_errors', 0);
require_once(dirname(__DIR__, 2) . '/config.php');
include(__DIR__ . '/../helpers/csrf.php');
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
csrf_verify();

$response = ['success' => false, 'message' => ''];
$id_ticket   = (int)($_POST['id_ticket']   ?? 0);
$id_usuario  = $_SESSION['id_usuario_sesion'] ?? 0;
$puede_gestionar = in_array(38, $_SESSION['permisos'] ?? []);

if (!$id_ticket) {
    $response['message'] = 'ID de ticket inválido';
    echo json_encode($response); exit;
}

try {
    // Verificar que el ticket existe y que el usuario tiene derecho a borrarlo
    $stmt = $pdo->prepare("SELECT id_usuario FROM tb_tickets WHERE id_ticket = ?");
    $stmt->execute([$id_ticket]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        $response['message'] = 'Ticket no encontrado';
        echo json_encode($response); exit;
    }

    if (!$puede_gestionar && $ticket['id_usuario'] != $id_usuario) {
        $response['message'] = 'Sin permisos para eliminar este ticket';
        echo json_encode($response); exit;
    }

    // Eliminar archivos físicos
    $stmtA = $pdo->prepare("SELECT ruta FROM tb_tickets_archivos WHERE id_ticket = ?");
    $stmtA->execute([$id_ticket]);
    $archivos = $stmtA->fetchAll();
    foreach ($archivos as $a) {
        $ruta_fisica = dirname(__DIR__, 3) . '/' . $a['ruta'];
        if (file_exists($ruta_fisica)) unlink($ruta_fisica);
    }

    $pdo->prepare("DELETE FROM tb_tickets WHERE id_ticket = ?")->execute([$id_ticket]);

    include('../helpers/auditoria.php');
    registrarAuditoria($pdo, $id_usuario, $_SESSION['nombre_usuario'] ?? null,
        'ELIMINAR TICKET', 'tb_tickets', $id_ticket, "Ticket #$id_ticket eliminado");

    $response['success'] = true;
    $response['message'] = 'Ticket eliminado correctamente';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

ob_clean();
echo json_encode($response);
