<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/config.php';
header('Content-Type: application/json');

if (!in_array(11, $_SESSION['permisos'] ?? []) && !in_array(24, $_SESSION['permisos'] ?? [])) {
    echo json_encode(['success' => false, 'message' => 'Sin permiso']);
    exit;
}

$id_stock = (int)($_POST['id_stock'] ?? 0);
$tipo     = $_POST['tipo'] ?? ''; // 'FLEJADA' o 'normal'
$notas    = trim($_POST['notas'] ?? '');

if (!$id_stock || !in_array($tipo, ['FLEJADA', 'normal'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verificar que la paca esté VENDIDA
    $stmt = $pdo->prepare("SELECT id_stock, id_producto FROM stock WHERE id_stock = ? AND estado = 'VENDIDO' FOR UPDATE");
    $stmt->execute([$id_stock]);
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stock) {
        throw new Exception('La paca no está en estado VENDIDO');
    }

    // Buscar en tb_ventas_stock para saber a qué venta pertenece
    $stmt = $pdo->prepare("SELECT id_venta FROM tb_ventas_stock WHERE id_stock = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$id_stock]);
    $vs = $stmt->fetch(PDO::FETCH_ASSOC);
    $id_venta = $vs ? $vs['id_venta'] : null;

    // Revertir estado del stock
    $_colCheck = $pdo->query("SHOW COLUMNS FROM stock LIKE 'tipo_especial'")->fetchAll();
    $_hasTipoEspecial = count($_colCheck) > 0;

    $tipo_especial = $tipo === 'FLEJADA' ? 'FLEJADA' : null;
    if ($_hasTipoEspecial) {
        $pdo->prepare("UPDATE stock
            SET estado = 'EN BODEGA', fecha_salida = NULL,
                tipo_especial = ?, notas_especial = ?
            WHERE id_stock = ?")
            ->execute([$tipo_especial, ($notas ?: null), $id_stock]);
    } else {
        $pdo->prepare("UPDATE stock SET estado = 'EN BODEGA', fecha_salida = NULL WHERE id_stock = ?")
            ->execute([$id_stock]);
    }

    if ($id_venta) {
        // Eliminar de tb_ventas_stock
        $pdo->prepare("DELETE FROM tb_ventas_stock WHERE id_stock = ? AND id_venta = ?")
            ->execute([$id_stock, $id_venta]);

        // Decrementar cantidad_entregada en detalle de esa venta para ese producto
        $pdo->prepare("UPDATE tb_ventas_detalle
            SET cantidad_entregada = GREATEST(0, cantidad_entregada - 1),
                estado = CASE WHEN GREATEST(0, cantidad_entregada - 1) >= cantidad THEN 'COMPLETADO' ELSE 'PENDIENTE' END
            WHERE id_venta = ? AND id_producto = ?")
            ->execute([$id_venta, $stock['id_producto']]);

        // Si la venta ya no está completa, revertir estado_logistico
        $stmt = $pdo->prepare("SELECT SUM(cantidad) AS total, SUM(cantidad_entregada) AS entregadas
            FROM tb_ventas_detalle WHERE id_venta = ?");
        $stmt->execute([$id_venta]);
        $totales = $stmt->fetch(PDO::FETCH_ASSOC);
        if ((int)$totales['entregadas'] < (int)$totales['total']) {
            $pdo->prepare("UPDATE tb_ventas SET estado_logistico = 'PENDIENTE GUIA' WHERE id_venta = ?")
                ->execute([$id_venta]);
        }
    }

    $pdo->commit();

    $msg = $tipo === 'FLEJADA'
        ? 'Paca marcada como FLEJADA y regresada a bodega especial'
        : 'Paca regresada a bodega normal (EN BODEGA)';

    echo json_encode(['success' => true, 'message' => $msg]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
