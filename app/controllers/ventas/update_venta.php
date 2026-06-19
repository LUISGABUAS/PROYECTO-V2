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
       1️⃣ VALIDAR QUE NO HAYA ENTREGAS
    ========================= */
    $stmt = $pdo->prepare("SELECT SUM(cantidad_entregada)
        FROM tb_ventas_detalle
        WHERE id_venta = ?
    ");
    $stmt->execute([$id_venta]);
    $entregadas = (int)$stmt->fetchColumn();

    if ($entregadas > 0) {
        throw new Exception(
            'No se puede editar la venta porque ya tiene productos entregados'
        );
    }

    /* =========================
       2️⃣ VALIDAR STOCK DISPONIBLE
    ========================= */
    foreach ($productos as $i => $id_producto) {

        $id_producto = (int)$id_producto;
        $cantidad    = (int)$cantidades[$i];

        if ($cantidad <= 0) {
            throw new Exception('Cantidad inválida');
        }

        $stmt = $pdo->prepare("SELECT COUNT(*)
            FROM stock
            WHERE id_producto = ?
              AND estado = 'EN BODEGA'
        ");
        $stmt->execute([$id_producto]);
        $disponible = (int)$stmt->fetchColumn();

        if ($disponible < $cantidad) {
            $p = $pdo->prepare("SELECT nombre FROM tb_almacen WHERE id_producto = ?");
            $p->execute([$id_producto]);
            $nombre = $p->fetchColumn();

            throw new Exception("Stock insuficiente para $nombre");
        }
    }

    /* =========================
       3️⃣ ELIMINAR COMPROBANTES MARCADOS
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
       4️⃣ SUBIR NUEVOS COMPROBANTES
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
       5️⃣ ACTUALIZAR VENTA
    ========================= */
    $stmt = $pdo->prepare("UPDATE tb_ventas
        SET fecha = ?, cliente = ?, envio = ?, total = ?, id_direccion_entrega = ?, updated_at = ?
        WHERE id_venta = ?
    ");
    $stmt->execute([$fecha, $cliente, $envio, $total, $id_direccion_entrega, $fechaHora, $id_venta]);

    /* =========================
       6️⃣ REEMPLAZAR DETALLE
    ========================= */
    $stmt = $pdo->prepare("DELETE FROM tb_ventas_detalle WHERE id_venta = ?");
    $stmt->execute([$id_venta]);

    foreach ($productos as $i => $id_producto) {

        $id_producto = (int)$id_producto;
        $cantidad    = (int)$cantidades[$i];
        $precio      = (float)$precios[$i];
        $subtotal    = $cantidad * $precio;

        $stmt = $pdo->prepare("INSERT INTO tb_ventas_detalle
            (id_venta, id_producto, cantidad, cantidad_entregada, precio, subtotal)
            VALUES (?, ?, ?, 0, ?, ?)
        ");
        $stmt->execute([
            $id_venta,
            $id_producto,
            $cantidad,
            $precio,
            $subtotal
        ]);
    }

    $pdo->commit();

    $id_usuario_audit = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;
    $nombre_audit = $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null;
    registrarAuditoria($pdo, $id_usuario_audit, $nombre_audit, 'ACTUALIZAR VENTA', 'tb_ventas', $id_venta, "Venta ID: $id_venta actualizada");

    $_SESSION['mensaje'] = '✅ Venta actualizada correctamente';
    header('Location: ../../../ventas');
    exit;

} catch (Exception $e) {

    $pdo->rollBack();
    $_SESSION['mensaje'] = '❌ ' . $e->getMessage();
    header('Location: ../../../ventas/edit.php?id=' . $id_venta);
    exit;
}
?>