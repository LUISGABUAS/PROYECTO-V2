<?php
ini_set('display_errors', 0);
require_once(dirname(__DIR__, 2) . '/config.php');
include(__DIR__ . '/../helpers/csrf.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
csrf_verify();

$response = ['success' => false, 'message' => ''];

if (empty($_POST['id_venta'])) {
    $response['message'] = 'ID de venta no recibido';
    echo json_encode($response);
    exit;
}

$id_venta = (int)$_POST['id_venta'];

try {
    $pdo->beginTransaction();

    /* ===============================
       1️⃣ VERIFICAR QUE LA VENTA EXISTA
    =============================== */
    $stmt = $pdo->prepare("SELECT id_venta FROM tb_ventas WHERE id_venta = ?");
    $stmt->execute([$id_venta]);
    if (!$stmt->fetch()) {
        throw new Exception('La venta no existe');
    }

    /* ===============================
       2️⃣ DEVOLVER PACAS ESCANEADAS (tb_ventas_stock)
       — Regresa a EN BODEGA las pacas que ya habían salido
    =============================== */
    $stmt = $pdo->prepare("SELECT id_stock FROM tb_ventas_stock WHERE id_venta = ?");
    $stmt->execute([$id_venta]);
    $stocks_escaneados = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($stocks_escaneados)) {
        $in = implode(',', array_fill(0, count($stocks_escaneados), '?'));
        $pdo->prepare("UPDATE stock
            SET estado = 'EN BODEGA',
                fecha_salida = NULL,
                tipo_especial = NULL,
                notas_especial = NULL,
                id_venta_origen = NULL
            WHERE id_stock IN ($in)")
            ->execute($stocks_escaneados);

        $pdo->prepare("DELETE FROM tb_ventas_stock WHERE id_venta = ?")
            ->execute([$id_venta]);
    }

    /* ===============================
       3️⃣ DEVOLVER STOCK PENDIENTE (no escaneado aún)
       — Pacas reservadas pero no salidas (cantidad_entregada = 0)
    =============================== */
    $stmt = $pdo->prepare("SELECT id_producto, cantidad, cantidad_entregada
        FROM tb_ventas_detalle WHERE id_venta = ?");
    $stmt->execute([$id_venta]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($detalles as $d) {
        $pendiente = max(0, (int)$d['cantidad'] - (int)$d['cantidad_entregada']);
        if ($pendiente <= 0) continue;

        // Busca pacas VENDIDO que NO estén en tb_ventas_stock (reservadas pero no escaneadas)
        $stmt2 = $pdo->prepare("SELECT s.id_stock
            FROM stock s
            WHERE s.id_producto = ?
              AND s.estado = 'VENDIDO'
              AND s.id_stock NOT IN (
                  SELECT id_stock FROM tb_ventas_stock WHERE id_venta = ?
              )
            LIMIT $pendiente");
        $stmt2->execute([$d['id_producto'], $id_venta]);
        $stocks_pendientes = $stmt2->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($stocks_pendientes)) {
            $in = implode(',', array_fill(0, count($stocks_pendientes), '?'));
            $pdo->prepare("UPDATE stock SET estado = 'EN BODEGA', fecha_salida = NULL
                WHERE id_stock IN ($in)")
                ->execute($stocks_pendientes);
        }
    }

    /* ===============================
       4️⃣ ELIMINAR REGISTROS RELACIONADOS
    =============================== */
    $pdo->prepare("DELETE FROM tb_ventas_detalle WHERE id_venta = ?")->execute([$id_venta]);
    $pdo->prepare("DELETE FROM tb_ventas_comprobantes WHERE id_venta = ?")->execute([$id_venta]);

    // Guías (si existe la tabla)
    $hayGuias = (bool)$pdo->query("SHOW TABLES LIKE 'tb_ventas_guias'")->fetchColumn();
    if ($hayGuias) {
        $pdo->prepare("DELETE FROM tb_ventas_guias WHERE id_venta = ?")->execute([$id_venta]);
    }

    /* ===============================
       5️⃣ ELIMINAR LA VENTA
    =============================== */
    $pdo->prepare("DELETE FROM tb_ventas WHERE id_venta = ?")->execute([$id_venta]);

    $pdo->commit();

    include('../helpers/auditoria.php');
    $id_usuario_sesion     = $_SESSION['id_usuario'] ?? null;
    $nombre_usuario_sesion = $_SESSION['nombre_usuario'] ?? null;
    registrarAuditoria($pdo, $id_usuario_sesion, $nombre_usuario_sesion, 'ELIMINAR VENTA', 'tb_ventas', $id_venta,
        "Venta #$id_venta eliminada por cancelación de cliente — stock restaurado");

    $response['success'] = true;
    $response['message'] = 'Venta eliminada y stock restaurado correctamente';

} catch (Exception $e) {
    $pdo->rollBack();
    $response['message'] = $e->getMessage();
}

ob_clean();
echo json_encode($response);
