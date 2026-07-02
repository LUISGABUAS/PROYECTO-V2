<?php
require_once __DIR__ . '/../../config.php';
include('../helpers/auditoria.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../ventas');
    exit;
}

/* =========================
   DATOS GENERALES
========================= */
$id_venta             = (int)$_POST['id_venta'];
$fecha                = $_POST['fecha'];
$cliente              = $_POST['cliente'];
$envio                = $_POST['envio'];
$total                = (float)$_POST['total'];
$tipo_pago            = $_POST['tipo_pago'] ?? 'comprobante';
$monto_pendiente      = ($tipo_pago === 'contra_entrega') ? (float)($_POST['monto_pendiente'] ?? 0) : 0.00;
$metodo_pendiente     = ($tipo_pago === 'contra_entrega') ? (trim($_POST['metodo_pendiente'] ?? '')) : null;
$notas                = trim($_POST['notas'] ?? '') ?: null;
$id_direccion_entrega = !empty($_POST['id_direccion_entrega']) ? (int)$_POST['id_direccion_entrega'] : null;

$productos  = $_POST['productos'];
$cantidades = $_POST['cantidades'];
$precios    = $_POST['precios'];

try {
    $pdo->beginTransaction();

    /* =========================
       COMPROBANTE ACTUAL
    ========================= */
    $stmt = $pdo->prepare("SELECT comprobante FROM tb_ventas WHERE id_venta = ?");
    $stmt->execute([$id_venta]);
    $comprobante_actual = $stmt->fetchColumn();

    /* =========================
       1. OBTENER PRODUCTOS YA ENTREGADOS (quedan bloqueados)
    ========================= */
    $stmt = $pdo->prepare("SELECT id_producto FROM tb_ventas_detalle
        WHERE id_venta = ? AND cantidad_entregada > 0");
    $stmt->execute([$id_venta]);
    $ids_bloqueados = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id_producto');

    /* =========================
       2. VALIDAR STOCK (solo productos no entregados)
    ========================= */
    foreach ($productos as $i => $id_producto) {
        $id_producto = (int)$id_producto;
        $cantidad    = (int)$cantidades[$i];

        if (in_array($id_producto, $ids_bloqueados)) continue; // ya entregado, no tocar

        if ($cantidad <= 0) throw new Exception('Cantidad inválida');

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM stock
            WHERE id_producto = ? AND estado = 'EN BODEGA'");
        $stmt->execute([$id_producto]);
        $disponible = (int)$stmt->fetchColumn();

        // Restar el stock que este mismo producto ya tenía en la venta original
        $stmt2 = $pdo->prepare("SELECT COALESCE(SUM(cantidad - cantidad_entregada), 0)
            FROM tb_ventas_detalle
            WHERE id_venta = ? AND id_producto = ? AND cantidad_entregada < cantidad");
        $stmt2->execute([$id_venta, $id_producto]);
        $comprometido_actual = (int)$stmt2->fetchColumn();

        $stock_real = $disponible - ($comprometido_actual > 0 ? 0 : 0); // incluir pendiente del mismo pedido

        if ($disponible < $cantidad) {
            $p = $pdo->prepare("SELECT nombre FROM tb_almacen WHERE id_producto = ?");
            $p->execute([$id_producto]);
            $nombre = $p->fetchColumn();
            throw new Exception("Stock insuficiente para \"$nombre\": disponible $disponible, pedido $cantidad");
        }
    }

    /* =========================
       3. ELIMINAR COMPROBANTES MARCADOS
    ========================= */
    $carpeta_comp = __DIR__ . '/../../comprobantes/';
    $ids_eliminar = array_filter($_POST['delete_comprobantes'] ?? [], fn($v) => $v !== '');

    foreach ($ids_eliminar as $comp_id) {
        if ($comp_id === 'legacy') {
            // Borrar el campo legacy de tb_ventas
            if (!empty($comprobante_actual)) {
                $arch = $carpeta_comp . basename($comprobante_actual);
                if (file_exists($arch)) unlink($arch);
            }
            $pdo->prepare("UPDATE tb_ventas SET comprobante = NULL WHERE id_venta = ?")->execute([$id_venta]);
        } else {
            $comp_id = (int)$comp_id;
            $stmt2 = $pdo->prepare("SELECT ruta FROM tb_ventas_comprobantes WHERE id = ? AND id_venta = ?");
            $stmt2->execute([$comp_id, $id_venta]);
            $ruta_comp = $stmt2->fetchColumn();
            if ($ruta_comp) {
                $arch = $carpeta_comp . basename($ruta_comp);
                if (file_exists($arch)) unlink($arch);
                $pdo->prepare("DELETE FROM tb_ventas_comprobantes WHERE id = ?")->execute([$comp_id]);
            }
        }
    }

    /* =========================
       4. SUBIR NUEVOS COMPROBANTES
    ========================= */
    $nuevos_comprobantes = [];
    $hayArchivos = isset($_FILES['comprobantes']) && is_array($_FILES['comprobantes']['name']);
    $indicesValidos = [];

    if ($hayArchivos) {
        foreach ($_FILES['comprobantes']['error'] as $i => $err) {
            if ($err === UPLOAD_ERR_OK) $indicesValidos[] = $i;
        }
    }

    if (!empty($indicesValidos)) {
        $permitidos = ['pdf','jpg','jpeg','png','doc','docx'];
        if (!is_dir($carpeta_comp)) mkdir($carpeta_comp, 0755, true);

        foreach ($indicesValidos as $i) {
            $ext = strtolower(pathinfo($_FILES['comprobantes']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $permitidos)) throw new Exception('Formato de comprobante no permitido');
            if ($_FILES['comprobantes']['size'][$i] > 5 * 1024 * 1024) throw new Exception('El comprobante supera los 5MB');

            $nombre = 'comp_' . $id_venta . '_' . time() . '_' . $i . '.' . $ext;
            if (!move_uploaded_file($_FILES['comprobantes']['tmp_name'][$i], $carpeta_comp . $nombre)) {
                throw new Exception('Error al subir el comprobante');
            }
            $nuevos_comprobantes[] = 'app/comprobantes/' . $nombre;
        }

        foreach ($nuevos_comprobantes as $ruta) {
            $pdo->prepare("INSERT INTO tb_ventas_comprobantes (id_venta, ruta) VALUES (?, ?)")->execute([$id_venta, $ruta]);
        }
    }

    /* =========================
       5. ACTUALIZAR VENTA
    ========================= */
    $stmt = $pdo->prepare("UPDATE tb_ventas
        SET fecha = ?, cliente = ?, envio = ?, tipo_pago = ?, total = ?,
            monto_pendiente = ?, metodo_pendiente = ?, notas = ?,
            id_direccion_entrega = ?, updated_at = ?
        WHERE id_venta = ?
    ");
    $stmt->execute([$fecha, $cliente, $envio, $tipo_pago, $total,
                    $monto_pendiente, $metodo_pendiente, $notas,
                    $id_direccion_entrega, $fechaHora, $id_venta]);

    /* =========================
       6. REEMPLAZAR SOLO PRODUCTOS NO ENTREGADOS
       Los bloqueados (cantidad_entregada > 0) se conservan intactos
    ========================= */
    // Borrar solo los no entregados
    if (!empty($ids_bloqueados)) {
        $ph = implode(',', array_fill(0, count($ids_bloqueados), '?'));
        $pdo->prepare("DELETE FROM tb_ventas_detalle
            WHERE id_venta = ? AND id_producto NOT IN ($ph) AND cantidad_entregada = 0")
            ->execute(array_merge([$id_venta], $ids_bloqueados));
    } else {
        $pdo->prepare("DELETE FROM tb_ventas_detalle WHERE id_venta = ? AND cantidad_entregada = 0")
            ->execute([$id_venta]);
    }

    foreach ($productos as $i => $id_producto) {
        $id_producto = (int)$id_producto;
        $cantidad    = (int)$cantidades[$i];
        $precio      = (float)$precios[$i];
        $subtotal    = $cantidad * $precio;

        if (in_array($id_producto, $ids_bloqueados)) continue; // no tocar entregados

        $pdo->prepare("INSERT INTO tb_ventas_detalle
            (id_venta, id_producto, cantidad, cantidad_entregada, precio, subtotal)
            VALUES (?, ?, ?, 0, ?, ?)")
            ->execute([$id_venta, $id_producto, $cantidad, $precio, $subtotal]);
    }

    $pdo->commit();

    $id_usuario_audit = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;
    $nombre_audit = $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null;
    registrarAuditoria($pdo, $id_usuario_audit, $nombre_audit, 'ACTUALIZAR VENTA', 'tb_ventas', $id_venta, "Venta ID: $id_venta actualizada");

    $_SESSION['mensaje'] = 'Venta actualizada correctamente';
    header('Location: ../../../ventas');
    exit;

} catch (Exception $e) {

    $pdo->rollBack();
    $_SESSION['mensaje'] = $e->getMessage();
    header('Location: ../../../ventas/edit.php?id=' . $id_venta);
    exit;
}
?>