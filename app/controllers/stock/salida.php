<?php
session_start();
include('../../config.php');

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
    exit;
}

$id_venta = $_POST['id_venta'] ?? null;
$codigo_unico = trim($_POST['codigo_unico'] ?? '');

if (!$id_venta || !$codigo_unico) {
    $response['message'] = 'Faltan datos.';
    echo json_encode($response);
    exit;
}

try {
    $pdo->beginTransaction();

    /**
     * 1️⃣ Validar que el código exista, esté EN BODEGA
     *    y pertenezca a ESTA venta
     */
    $stmt = $pdo->prepare("
        SELECT 
            s.id_stock,
            s.id_producto,
            vd.id_detalle,
            vd.cantidad,
            vd.estado
        FROM stock s
        INNER JOIN tb_ventas_detalle vd 
            ON vd.id_producto = s.id_producto
           AND vd.id_venta = ?
        WHERE s.codigo_unico = ?
          AND s.estado = 'EN BODEGA'
        LIMIT 1
    ");
    $stmt->execute([$id_venta, $codigo_unico]);
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stock) {
        throw new Exception("El código no existe, no está en bodega o no pertenece a esta venta.");
    }

    $id_stock    = $stock['id_stock'];
    $id_producto = $stock['id_producto'];
    $id_detalle  = $stock['id_detalle'];
    $cantidad_vendida = (int)$stock['cantidad'];

    /**
     * 2️⃣ Bloquear si el producto ya está COMPLETADO
     */
    if ($stock['estado'] === 'COMPLETADO') {
        throw new Exception("Este producto ya fue entregado completamente.");
    }

    /**
     * 3️⃣ Bloquear doble escaneo del mismo código
     */
    $stmt = $pdo->prepare("
        SELECT 1 
        FROM tb_ventas_stock
        WHERE id_venta = ? AND id_stock = ?
    ");
    $stmt->execute([$id_venta, $id_stock]);

    if ($stmt->fetch()) {
        throw new Exception("Este producto ya fue escaneado en esta venta.");
    }

    /**
     * 4️⃣ Registrar la salida del producto
     */
    $stmt = $pdo->prepare("
        INSERT INTO tb_ventas_stock (id_venta, id_stock)
        VALUES (?, ?)
    ");
    $stmt->execute([$id_venta, $id_stock]);

    /**
     * 5️⃣ Marcar el stock como VENDIDO
     */
    $stmt = $pdo->prepare("
        UPDATE stock
        SET estado = 'VENDIDO',
            fecha_salida = NOW()
        WHERE id_stock = ?
    ");
    $stmt->execute([$id_stock]);

    /**
     * 6️⃣ Contar cuántos de ESTE producto
     *    se han entregado SOLO en esta venta
     */
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM tb_ventas_stock vs
        INNER JOIN stock s ON s.id_stock = vs.id_stock
        WHERE vs.id_venta = ?
          AND s.id_producto = ?
    ");
    $stmt->execute([$id_venta, $id_producto]);
    $cantidad_entregada = (int)$stmt->fetchColumn();

    /**
     * 7️⃣ Actualizar estado del detalle
     */
    $estado = ($cantidad_entregada >= $cantidad_vendida)
        ? 'COMPLETADO'
        : 'PENDIENTE';

    $stmt = $pdo->prepare("
        UPDATE tb_ventas_detalle
        SET cantidad_entregada = ?,
            estado = ?
        WHERE id_detalle = ?
    ");
    $stmt->execute([$cantidad_entregada, $estado, $id_detalle]);

    $pdo->commit();

    $response['success'] = true;
    $response['message'] = 'Producto entregado correctamente.';

} catch (Exception $e) {
    $pdo->rollBack();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
