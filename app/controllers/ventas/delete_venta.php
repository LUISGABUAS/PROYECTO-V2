<?php
include('../../config.php');
session_start();

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => ''
];

if (empty($_POST['id_venta'])) {
    $response['message'] = 'ID de venta no recibido';
    echo json_encode($response);
    exit;
}

$id_venta = (int)$_POST['id_venta'];

try {

    $pdo->beginTransaction();

    /* ===============================
       0️⃣ VALIDAR QUE NO HAYA ENTREGA
    =============================== */
    $stmt = $pdo->prepare("SELECT SUM(cantidad_entregada) AS entregadas
        FROM tb_ventas_detalle
        WHERE id_venta = ?
    ");
    $stmt->execute([$id_venta]);
    $entregadas = (int)$stmt->fetchColumn();

    if ($entregadas > 0) {
        throw new Exception('No se puede eliminar una venta con productos ya entregados');
    }

    /* ===============================
       1️⃣ OBTENER DETALLE DE VENTA
    =============================== */
    $stmt = $pdo->prepare("SELECT id_producto, cantidad
        FROM tb_ventas_detalle
        WHERE id_venta = ?
    ");
    $stmt->execute([$id_venta]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$detalles) {
        throw new Exception('La venta no tiene detalle o no existe');
    }

    /* ===============================
       2️⃣ DEVOLVER STOCK (POR UNIDAD)
    =============================== */
    foreach ($detalles as $d) {

        $cantidad = (int)$d['cantidad'];

        if ($cantidad <= 0) continue;

        $stmt = $pdo->prepare("SELECT id_stock
            FROM stock
            WHERE id_producto = ?
              AND estado = 'EN BODEGA'
            ORDER BY fecha_salida DESC
            LIMIT $cantidad
        ");
        $stmt->execute([$d['id_producto']]);
        $stocks = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($stocks) !== $cantidad) {
            throw new Exception('Inconsistencia en el stock vendido');
        }

        $in = implode(',', array_fill(0, count($stocks), '?'));

        $upd = $pdo->prepare("UPDATE stock
            SET estado = 'EN BODEGA',
                fecha_salida = NULL
            WHERE id_stock IN ($in)
        ");
        $upd->execute($stocks);
    }

    /* ===============================
       3️⃣ ELIMINAR DETALLE
    =============================== */
    $stmt = $pdo->prepare("DELETE FROM tb_ventas_detalle WHERE id_venta = ?");
    $stmt->execute([$id_venta]);

    /* ===============================
       4️⃣ ELIMINAR VENTA
    =============================== */
    $stmt = $pdo->prepare("DELETE FROM tb_ventas WHERE id_venta = ?");
    $stmt->execute([$id_venta]);

    $pdo->commit();

    $response['success'] = true;
    $response['message'] = 'Venta pendiente eliminada y stock restaurado correctamente';

} catch (Exception $e) {

    $pdo->rollBack();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
