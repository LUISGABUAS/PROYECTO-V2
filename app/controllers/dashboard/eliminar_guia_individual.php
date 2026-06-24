<?php
require_once '../../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

$id_guia  = (int)($_POST['id']       ?? 0);
$id_venta = (int)($_POST['id_venta'] ?? 0);

if (!$id_guia || !$id_venta) {
    http_response_code(400);
    echo json_encode(['success' => false, 'msg' => 'Parámetros inválidos']);
    exit;
}

// Obtener el archivo antes de borrar
$stmt = $pdo->prepare("SELECT archivo FROM tb_ventas_guias WHERE id = ? AND id_venta = ?");
$stmt->execute([$id_guia, $id_venta]);
$guia = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$guia) {
    http_response_code(404);
    echo json_encode(['success' => false, 'msg' => 'Guía no encontrada']);
    exit;
}

// Eliminar archivo físico
$ruta = __DIR__ . '/../../../dashboard/guia_pdf/' . $guia['archivo'];
if (file_exists($ruta)) unlink($ruta);

// Eliminar registro
$pdo->prepare("DELETE FROM tb_ventas_guias WHERE id = ?")->execute([$id_guia]);

// Verificar si quedan guías
$stmt_resto = $pdo->prepare("SELECT id, archivo FROM tb_ventas_guias WHERE id_venta = ? ORDER BY numero ASC LIMIT 1");
$stmt_resto->execute([$id_venta]);
$primera = $stmt_resto->fetch(PDO::FETCH_ASSOC);

if ($primera) {
    // Actualizar guia_pdf principal con la primera guía restante
    $pdo->prepare("UPDATE tb_ventas SET guia_pdf = ? WHERE id_venta = ?")
        ->execute([$primera['archivo'], $id_venta]);
} else {
    // No quedan guías — limpiar estado y paquetería
    $pdo->prepare("UPDATE tb_ventas SET guia_pdf = NULL, paqueteria = NULL, estado_logistico = 'PENDIENTE GUIA' WHERE id_venta = ?")
        ->execute([$id_venta]);
}

echo json_encode(['success' => true]);
exit;
