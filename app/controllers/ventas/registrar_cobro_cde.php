<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once(dirname(__DIR__, 2) . '/config.php');
include(__DIR__ . '/../helpers/csrf.php');

header('Content-Type: application/json');

// Permiso: vendedor (25) o admin (24)
$permisos = $_SESSION['permisos'] ?? [];
if (!in_array(24, $permisos) && !in_array(25, $permisos)) {
    echo json_encode(['success' => false, 'message' => 'Sin permiso']);
    exit;
}

try {
    csrf_verify();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Token inválido']);
    exit;
}

$id_venta = intval($_POST['id_venta'] ?? 0);
if (!$id_venta) {
    echo json_encode(['success' => false, 'message' => 'Venta inválida']);
    exit;
}

// Verificar que la venta existe y tiene pendiente
$stmt = $pdo->prepare("SELECT id_venta, monto_pendiente, metodo_pendiente, id_usuario FROM tb_ventas WHERE id_venta = ?");
$stmt->execute([$id_venta]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    echo json_encode(['success' => false, 'message' => 'Venta no encontrada']);
    exit;
}

// Vendedor solo puede registrar sus propias ventas
if (in_array(25, $permisos) && !in_array(24, $permisos)) {
    $id_usuario_sesion = $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? null;
    if ($venta['id_usuario'] != $id_usuario_sesion) {
        echo json_encode(['success' => false, 'message' => 'Sin permiso para esta venta']);
        exit;
    }
}

try {
    $pdo->beginTransaction();

    // Subir comprobante si se adjuntó
    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
        $carpeta    = __DIR__ . '/../../comprobantes/';
        $permitidas = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $ext        = strtolower(pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $permitidas)) {
            throw new Exception("Formato no permitido");
        }
        if ($_FILES['comprobante']['size'] > 5 * 1024 * 1024) {
            throw new Exception("El archivo excede 5MB");
        }
        if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);

        $nombre = date('Y-m-d_H-i-s') . '_cde_' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($_FILES['comprobante']['tmp_name'], $carpeta . $nombre)) {
            throw new Exception("Error al subir el comprobante");
        }

        $pdo->prepare("INSERT INTO tb_ventas_comprobantes (id_venta, ruta) VALUES (?, ?)")
            ->execute([$id_venta, 'app/comprobantes/' . $nombre]);
    }

    // Marcar como cobrado
    $pdo->prepare("UPDATE tb_ventas SET monto_pendiente = 0, metodo_pendiente = NULL WHERE id_venta = ?")
        ->execute([$id_venta]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => "Cobro de venta #$id_venta registrado"]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
