<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/config.php';
include(__DIR__ . '/../helpers/csrf.php');
header('Content-Type: application/json');

if (!in_array(24, $_SESSION['permisos'] ?? []) && !in_array(11, $_SESSION['permisos'] ?? [])) {
    echo json_encode(['success' => false, 'message' => 'Sin permiso']);
    exit;
}

csrf_verify();

$id_stock  = (int)($_POST['id_stock'] ?? 0);
$id_cliente= (int)($_POST['id_cliente'] ?? 0);
$precio    = (float)($_POST['precio'] ?? 0);
$tipo_pago = $_POST['tipo_pago'] ?? 'efectivo';
$id_usuario= (int)($_SESSION['id_usuario_sesion'] ?? 0);
$notas     = trim($_POST['notas'] ?? '');

if (!$id_stock || !$id_cliente || $precio <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verificar que la paca es especial y está EN BODEGA
    $stmt = $pdo->prepare("SELECT s.*, a.id_producto, a.nombre AS nombre_producto
        FROM stock s
        JOIN tb_almacen a ON s.id_producto = a.id_producto
        WHERE s.id_stock = ? AND s.estado = 'EN BODEGA' AND s.tipo_especial IS NOT NULL
        FOR UPDATE");
    $stmt->execute([$id_stock]);
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stock) {
        throw new Exception('Paca no disponible o ya vendida');
    }

    $fecha = date('Y-m-d H:i:s');

    // Crear venta
    $pdo->prepare("INSERT INTO tb_ventas
        (fecha, cliente, envio, tipo_pago, total, monto_pendiente, notas, id_usuario)
        VALUES (?, ?, 'local', ?, ?, 0, ?, ?)")
        ->execute([$fecha, $id_cliente, $tipo_pago, $precio,
            ($notas ?: 'Venta paca especial: ' . $stock['tipo_especial']), $id_usuario]);
    $id_venta = $pdo->lastInsertId();

    // Detalle
    $pdo->prepare("INSERT INTO tb_ventas_detalle
        (id_venta, id_producto, cantidad, cantidad_entregada, precio, subtotal)
        VALUES (?, ?, 1, 1, ?, ?)")
        ->execute([$id_venta, $stock['id_producto'], $precio, $precio]);

    // Link stock→venta
    $pdo->prepare("INSERT INTO tb_ventas_stock (id_venta, id_stock) VALUES (?, ?)")
        ->execute([$id_venta, $id_stock]);

    // Marcar paca como VENDIDO y limpiar tipo_especial
    $pdo->prepare("UPDATE stock
        SET estado = 'VENDIDO', fecha_salida = NOW(), tipo_especial = NULL
        WHERE id_stock = ?")
        ->execute([$id_stock]);

    // Marcar venta como ENVIADA (entrega inmediata)
    $pdo->prepare("UPDATE tb_ventas SET estado_logistico = 'ENVIADA' WHERE id_venta = ?")
        ->execute([$id_venta]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Venta #$id_venta registrada", 'id_venta' => $id_venta]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
