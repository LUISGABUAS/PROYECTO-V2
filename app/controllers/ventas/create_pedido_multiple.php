<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once(dirname(__DIR__, 2) . '/config.php');
include(__DIR__ . '/../helpers/csrf.php');
csrf_verify();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../ventas/pedido_multiple.php");
    exit;
}

$id_cliente = (int)($_POST['id_cliente'] ?? 0);
$fecha      = $_POST['fecha'] ?? date('Y-m-d');
$id_usuario = (int)($_POST['id_usuario'] ?? 0);
$envios_raw = $_POST['envios_json'] ?? '';

if (!$id_cliente || !$id_usuario || !$envios_raw) {
    $_SESSION['mensaje'] = '❌ Faltan datos obligatorios';
    header("Location: ../../../ventas/pedido_multiple.php");
    exit;
}

$envios = json_decode($envios_raw, true);
if (!$envios || !is_array($envios) || count($envios) === 0) {
    $_SESSION['mensaje'] = '❌ No se recibieron envíos válidos';
    header("Location: ../../../ventas/pedido_multiple.php");
    exit;
}

// Subir comprobantes (múltiples)
$rutas_comprobantes = [];
$carpeta    = __DIR__ . '/../../comprobantes/';
$permitidas = ['pdf','jpg','jpeg','png','doc','docx'];

$hayArchivos    = isset($_FILES['comprobantes']) && is_array($_FILES['comprobantes']['name']);
$indicesValidos = [];
if ($hayArchivos) {
    foreach ($_FILES['comprobantes']['error'] as $i => $err) {
        if ($err === UPLOAD_ERR_OK) $indicesValidos[] = $i;
    }
}

if (empty($indicesValidos)) {
    $_SESSION['mensaje'] = '❌ Debe adjuntar al menos un comprobante';
    header("Location: ../../../ventas/pedido_multiple.php");
    exit;
}

if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);
foreach ($indicesValidos as $i) {
    $ext = strtolower(pathinfo($_FILES['comprobantes']['name'][$i], PATHINFO_EXTENSION));
    if (!in_array($ext, $permitidas)) {
        $_SESSION['mensaje'] = '❌ Formato no permitido: ' . htmlspecialchars($_FILES['comprobantes']['name'][$i]);
        header("Location: ../../../ventas/pedido_multiple.php"); exit;
    }
    if ($_FILES['comprobantes']['size'][$i] > 5 * 1024 * 1024) {
        $_SESSION['mensaje'] = '❌ Un comprobante supera 5MB';
        header("Location: ../../../ventas/pedido_multiple.php"); exit;
    }
    $nombre_archivo = date('Y-m-d_H-i-s') . '_pedido_' . uniqid() . '.' . $ext;
    move_uploaded_file($_FILES['comprobantes']['tmp_name'][$i], $carpeta . $nombre_archivo);
    $rutas_comprobantes[] = 'app/comprobantes/' . $nombre_archivo;
}

try {
    $pdo->beginTransaction();

    // Calcular total general
    $total_general = 0;
    foreach ($envios as $envio) {
        foreach ($envio['productos'] as $prod) {
            $total_general += (float)$prod['precio'] * (int)$prod['cantidad'];
        }
    }

    // Insertar pedido padre
    $stmt = $pdo->prepare("INSERT INTO tb_pedidos (id_cliente, id_usuario, fecha, comprobante, total)
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$id_cliente, $id_usuario, $fecha, $rutas_comprobantes[0] ?? null, $total_general]);
    $id_pedido = $pdo->lastInsertId();

    // Crear una venta por cada envío
    foreach ($envios as $envio) {
        $id_direccion = !empty($envio['id_direccion']) ? (int)$envio['id_direccion'] : null;

        $total_envio = 0;
        foreach ($envio['productos'] as $prod) {
            $total_envio += (float)$prod['precio'] * (int)$prod['cantidad'];
        }

        $stmt_v = $pdo->prepare("INSERT INTO tb_ventas
            (id_pedido, fecha, cliente, envio, tipo_pago, total, comprobante, id_usuario, id_direccion_entrega)
            VALUES (?, ?, ?, 'foraneo', 'comprobante', ?, NULL, ?, ?)");
        $stmt_v->execute([$id_pedido, $fecha, $id_cliente, $total_envio, $id_usuario, $id_direccion]);
        $id_venta = $pdo->lastInsertId();

        // Guardar todos los comprobantes en tb_ventas_comprobantes
        foreach ($rutas_comprobantes as $ruta) {
            $pdo->prepare("INSERT INTO tb_ventas_comprobantes (id_venta, ruta) VALUES (?, ?)")
                ->execute([$id_venta, $ruta]);
        }

        // Detalle de productos
        foreach ($envio['productos'] as $prod) {
            $id_prod  = (int)$prod['id_producto'];
            $cantidad = (int)$prod['cantidad'];
            $precio   = (float)$prod['precio'];
            $subtotal = $cantidad * $precio;

            $pdo->prepare("INSERT INTO tb_ventas_detalle (id_venta, id_producto, cantidad, precio, subtotal)
                           VALUES (?, ?, ?, ?, ?)")
                ->execute([$id_venta, $id_prod, $cantidad, $precio, $subtotal]);
        }
    }

    $pdo->commit();

    $_SESSION['mensaje'] = "✅ Pedido múltiple registrado — {$total_general} total — " . count($envios) . " envíos creados";
    header("Location: ../../../ventas/index.php");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['mensaje'] = '❌ Error al guardar el pedido: ' . $e->getMessage();
    header("Location: ../../../ventas/pedido_multiple.php");
    exit;
}
