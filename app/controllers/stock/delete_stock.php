<?php
require_once(dirname(__DIR__, 2) . '/config.php');
include('../helpers/auditoria.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$id_stock = isset($_POST['id_stock']) ? (int)$_POST['id_stock'] : 0;

if ($id_stock <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Stock no válido'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verificar que el stock existe y no está VENDIDO
    $chk = $pdo->prepare("SELECT id_stock, estado FROM stock WHERE id_stock = :id_stock");
    $chk->bindParam(':id_stock', $id_stock, PDO::PARAM_INT);
    $chk->execute();
    $stockRow = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$stockRow) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'No se encontró el stock']);
        exit;
    }

    if ($stockRow['estado'] === 'VENDIDO') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'El stock vendido no puede ser eliminado']);
        exit;
    }

    // Eliminar referencias en tb_ventas_stock antes de borrar el stock
    $delVS = $pdo->prepare("DELETE FROM tb_ventas_stock WHERE id_stock = :id_stock");
    $delVS->bindParam(':id_stock', $id_stock, PDO::PARAM_INT);
    $delVS->execute();

    $stmt = $pdo->prepare("DELETE FROM stock WHERE id_stock = :id_stock");
    $stmt->bindParam(':id_stock', $id_stock, PDO::PARAM_INT);
    $stmt->execute();

    $pdo->commit();

    $id_usuario_audit = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;
    $nombre_audit = $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null;
    registrarAuditoria($pdo, $id_usuario_audit, $nombre_audit, 'ELIMINAR STOCK', 'stock', $id_stock, "Stock ID: $id_stock eliminado");
    echo json_encode([
        'success' => true,
        'message' => 'Stock eliminado correctamente'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos'
    ]);
}

exit;
