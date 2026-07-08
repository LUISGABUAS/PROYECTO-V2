<?php
require_once __DIR__ . '/../../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!in_array(22, $_SESSION['permisos'] ?? [])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

header('Content-Type: application/json');

$accion      = $_POST['accion'] ?? '';
$id_producto = (int)($_POST['id_producto'] ?? 0);

if (!$id_producto) {
    echo json_encode(['success' => false, 'message' => 'Producto inválido']);
    exit;
}

if ($accion === 'guardar') {
    $porcentaje  = (float)($_POST['porcentaje'] ?? 0);
    $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
    $fecha_fin    = trim($_POST['fecha_fin']    ?? '');

    if ($porcentaje <= 0 || $porcentaje >= 100) {
        echo json_encode(['success' => false, 'message' => 'El porcentaje debe ser entre 1 y 99']);
        exit;
    }
    if (!$fecha_inicio || !$fecha_fin || $fecha_fin <= $fecha_inicio) {
        echo json_encode(['success' => false, 'message' => 'Fechas inválidas']);
        exit;
    }

    // Precio original del producto
    $stmt = $pdo->prepare("SELECT precio_venta FROM tb_almacen WHERE id_producto = ?");
    $stmt->execute([$id_producto]);
    $precio_original = (float)$stmt->fetchColumn();

    if (!$precio_original) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        exit;
    }

    // No permitir solapamiento con descuento activo
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_descuentos
        WHERE id_producto = ?
        AND fecha_fin > NOW()
        AND fecha_inicio < ?");
    $stmt->execute([$id_producto, $fecha_fin]);
    if ((int)$stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya existe un descuento activo o futuro que se solapa con ese período']);
        exit;
    }

    $precio_descuento = round($precio_original * (1 - $porcentaje / 100), 2);

    $stmt = $pdo->prepare("INSERT INTO tb_descuentos
        (id_producto, precio_original, precio_descuento, porcentaje, fecha_inicio, fecha_fin)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id_producto, $precio_original, $precio_descuento, $porcentaje, $fecha_inicio, $fecha_fin]);

    echo json_encode(['success' => true, 'precio_descuento' => $precio_descuento]);

} elseif ($accion === 'cancelar') {
    $id_descuento = (int)($_POST['id_descuento'] ?? 0);

    $stmt = $pdo->prepare("UPDATE tb_descuentos SET fecha_fin = NOW()
        WHERE id = ? AND id_producto = ?");
    $stmt->execute([$id_descuento, $id_producto]);

    echo json_encode(['success' => true]);

} else {
    echo json_encode(['success' => false, 'message' => 'Acción desconocida']);
}
