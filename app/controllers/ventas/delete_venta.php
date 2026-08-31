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
    $stmt = $pdo->prepare("
        SELECT s.id_stock, s.codigo_unico, a.nombre AS nombre_producto
        FROM tb_ventas_stock vs
        JOIN stock s ON vs.id_stock = s.id_stock
        JOIN tb_almacen a ON s.id_producto = a.id_producto
        WHERE vs.id_venta = ?
    ");
    $stmt->execute([$id_venta]);
    $stocks_con_nombre = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stocks_escaneados = array_column($stocks_con_nombre, 'id_stock');

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
       4️⃣ ELIMINAR REGISTROS RELACIONADOS
    =============================== */
    $pdo->prepare("DELETE FROM tb_ventas_detalle WHERE id_venta = ?")->execute([$id_venta]);
    $pdo->prepare("DELETE FROM tb_ventas_comprobantes WHERE id_venta = ?")->execute([$id_venta]);

    // Guías (si existe la tabla)
    if (!isset($_SESSION['_sc_tb_ventas_guias'])) {
        $_SESSION['_sc_tb_ventas_guias'] = (bool)$pdo->query("SHOW TABLES LIKE 'tb_ventas_guias'")->fetchColumn();
    }
    $hayGuias = $_SESSION['_sc_tb_ventas_guias'];
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

    if (!empty($stocks_con_nombre)) {
        $agrupado = [];
        foreach ($stocks_con_nombre as $s) {
            $agrupado[$s['nombre_producto']][] = $s['codigo_unico'];
        }
        $partes = [];
        foreach ($agrupado as $nombre => $codigos) {
            $partes[] = $nombre . ' ×' . count($codigos) . ' (' . implode(', ', $codigos) . ')';
        }
        $detalle_audit = "Venta #$id_venta eliminada — pacas regresadas a bodega: " . implode(' | ', $partes);
    } else {
        $detalle_audit = "Venta #$id_venta eliminada — sin pacas escaneadas que regresar";
    }

    registrarAuditoria($pdo, $id_usuario_sesion, $nombre_usuario_sesion, 'ELIMINAR VENTA', 'tb_ventas', $id_venta, $detalle_audit);

    $response['success'] = true;
    $response['message'] = 'Venta eliminada y stock restaurado correctamente';

} catch (Exception $e) {
    $pdo->rollBack();
    $response['message'] = $e->getMessage();
}

ob_clean();
echo json_encode($response);
