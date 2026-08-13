<?php
require_once(dirname(__DIR__, 2) . '/config.php');
require_once(__DIR__ . '/../helpers/licencia.php');
include(__DIR__ . '/../helpers/csrf.php');

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
csrf_verify();

if (!isset($_SESSION['sesion_email'])) {
    echo json_encode(['success' => false, 'message' => 'Sin sesión']); exit;
}

if (!es_propietario()) {
    echo json_encode(['success' => false, 'message' => 'Sin permiso']); exit;
}

$periodo = licencia_periodo_actual();

try {
    $stmt = $pdo->prepare("
        INSERT INTO tb_licencia (periodo, pagado, fecha_pago, pagado_por)
        VALUES (?, 1, NOW(), ?)
        ON DUPLICATE KEY UPDATE
            pagado     = 1,
            fecha_pago = NOW(),
            pagado_por = VALUES(pagado_por)
    ");
    $stmt->execute([$periodo, $_SESSION['id_usuario_sesion']]);

    include(__DIR__ . '/../helpers/auditoria.php');
    registrarAuditoria(
        $pdo,
        $_SESSION['id_usuario_sesion'],
        $_SESSION['sesion_nombres'] ?? null,
        'PAGO LICENCIA',
        'tb_licencia',
        null,
        "Mensualidad registrada: $periodo"
    );

    echo json_encode(['success' => true, 'message' => "Mensualidad $periodo registrada."]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
