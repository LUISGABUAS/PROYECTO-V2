<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(dirname(__DIR__, 2) . '/config.php');
include(__DIR__ . '/../helpers/csrf.php');
csrf_verify();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $pdo->beginTransaction();

    /* ======================
       DATOS GENERALES
    ====================== */
    $id_usuario           = $_POST['id_usuario'];
    $fecha                = date('Y-m-d H:i:s'); // hora exacta del servidor (America/Mexico_City)
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

    if (empty($productos)) {
        throw new Exception("❌ No hay productos en la venta");
    }

    /* ======================
       COMPROBANTES (múltiples)
    ====================== */
    $rutas_comprobantes = [];

    $hayArchivos = isset($_FILES['comprobantes']) && is_array($_FILES['comprobantes']['name']);
    $indicesValidos = [];

    if ($hayArchivos) {
        foreach ($_FILES['comprobantes']['error'] as $i => $err) {
            if ($err === UPLOAD_ERR_OK) $indicesValidos[] = $i;
        }
    }

    $requiere_comprobante = in_array($tipo_pago, ['comprobante', 'ambos']);
    if (empty($indicesValidos) && $requiere_comprobante) {
        throw new Exception("❌ Debe adjuntar al menos un comprobante");
    }

    if (!empty($indicesValidos)) {
        $carpeta  = __DIR__ . '/../../comprobantes/';
        $permitidas = ['pdf','jpg','jpeg','png','doc','docx'];

        if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true)) {
            throw new Exception("❌ No se pudo crear la carpeta de comprobantes");
        }
        if (!is_writable($carpeta)) {
            throw new Exception("❌ No hay permisos de escritura en la carpeta de comprobantes");
        }

        foreach ($indicesValidos as $i) {
            $ext = strtolower(pathinfo($_FILES['comprobantes']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $permitidas)) {
                throw new Exception("❌ Formato no permitido: " . htmlspecialchars($_FILES['comprobantes']['name'][$i]));
            }
            if ($_FILES['comprobantes']['size'][$i] > 5 * 1024 * 1024) {
                throw new Exception("❌ El archivo excede el tamaño máximo de 5MB");
            }
            $nombre = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $ext;
            if (!move_uploaded_file($_FILES['comprobantes']['tmp_name'][$i], $carpeta . $nombre)) {
                throw new Exception("❌ Error al subir el comprobante");
            }
            $rutas_comprobantes[] = 'app/comprobantes/' . $nombre;
        }
    }

    /* ======================
       INSERTAR VENTA (PADRE)
    ====================== */
    $stmt = $pdo->prepare("
        INSERT INTO tb_ventas (fecha, cliente, envio, tipo_pago, total, monto_pendiente, metodo_pendiente, notas, comprobante, id_usuario, id_direccion_entrega)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?)
    ");
    $stmt->execute([$fecha, $cliente, $envio, $tipo_pago, $total, $monto_pendiente, $metodo_pendiente, $notas, $id_usuario, $id_direccion_entrega]);

    $id_venta = $pdo->lastInsertId();

    /* ======================
       INSERTAR COMPROBANTES
    ====================== */
    if (!empty($rutas_comprobantes)) {
        $vals   = implode(',', array_fill(0, count($rutas_comprobantes), '(?,?)'));
        $params = [];
        foreach ($rutas_comprobantes as $ruta) { $params[] = $id_venta; $params[] = $ruta; }
        $pdo->prepare("INSERT INTO tb_ventas_comprobantes (id_venta, ruta) VALUES $vals")->execute($params);
    }

    /* ======================
       VALIDAR STOCK EN BATCH (1 query por tabla, con FOR UPDATE)
       Previene que dos ventas simultáneas vendan la misma pieza
    ====================== */
    $ids_productos = array_map('intval', array_unique($productos));
    $placeholders  = implode(',', array_fill(0, count($ids_productos), '?'));

    // Bloquea todas las filas EN BODEGA de los productos de esta venta
    $stmt_lock = $pdo->prepare("
        SELECT id_producto, COUNT(*) AS en_bodega
        FROM stock
        WHERE id_producto IN ($placeholders) AND estado = 'EN BODEGA'
        GROUP BY id_producto
        FOR UPDATE
    ");
    $stmt_lock->execute($ids_productos);
    $bodega_map = array_column($stmt_lock->fetchAll(PDO::FETCH_ASSOC), 'en_bodega', 'id_producto');

    // Pendiente de entrega en batch
    $stmt_pend = $pdo->prepare("
        SELECT id_producto, COALESCE(SUM(cantidad - cantidad_entregada), 0) AS pendiente
        FROM tb_ventas_detalle
        WHERE id_producto IN ($placeholders) AND cantidad_entregada < cantidad
        GROUP BY id_producto
    ");
    $stmt_pend->execute($ids_productos);
    $pendiente_map = array_column($stmt_pend->fetchAll(PDO::FETCH_ASSOC), 'pendiente', 'id_producto');

    // Nombres en batch para mensajes de error
    $stmt_nom = $pdo->prepare("SELECT id_producto, nombre FROM tb_almacen WHERE id_producto IN ($placeholders)");
    $stmt_nom->execute($ids_productos);
    $nombres_map = array_column($stmt_nom->fetchAll(PDO::FETCH_ASSOC), 'nombre', 'id_producto');

    foreach ($productos as $i => $id_producto) {
        $id_producto = (int)$id_producto;
        $cantidad    = (int)$cantidades[$i];
        $en_bodega   = (int)($bodega_map[$id_producto]   ?? 0);
        $pendiente   = (int)($pendiente_map[$id_producto] ?? 0);
        $disponible  = $en_bodega - $pendiente;

        if ($cantidad > $disponible) {
            $nombre = $nombres_map[$id_producto] ?? "Producto #$id_producto";
            throw new Exception("❌ Stock insuficiente para \"$nombre\": pedido $cantidad, disponible $disponible");
        }
    }

    /* ======================
       INSERTAR DETALLE
    ====================== */
    foreach ($productos as $i => $id_producto) {

        $cantidad = (int)$cantidades[$i];
        $precio   = (float)$precios[$i];
        $subtotal = $cantidad * $precio;

        $stmt = $pdo->prepare("
            INSERT INTO tb_ventas_detalle
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

    include('../helpers/auditoria.php');
    registrarAuditoria($pdo, $id_usuario, null, 'CREAR VENTA', 'tb_ventas', $id_venta, "Venta #$id_venta — Cliente: $cliente — Total: $total");

    echo json_encode(['success' => true, 'message' => "✅ Venta #$id_venta creada correctamente", 'id_venta' => $id_venta]);
    exit;

} catch (Exception $e) {

    if ($pdo->inTransaction()) $pdo->rollBack();

    if (!empty($rutas_comprobantes) && isset($carpeta)) {
        foreach ($rutas_comprobantes as $ruta) {
            $archivo = $carpeta . basename($ruta);
            if (file_exists($archivo)) unlink($archivo);
        }
    }

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}