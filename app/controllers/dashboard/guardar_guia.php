<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(dirname(__DIR__, 2) . '/config.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* 🔒 Validar método */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $URL . '/dashboard/foraneos.php');
    exit;
}

$id_venta   = (int)($_POST['id_venta'] ?? 0);
$paqueteria = trim($_POST['paqueteria'] ?? '');
$archivos   = $_FILES['guias'] ?? null;

if (!$id_venta) {
    $_SESSION['icono'] = "error";
    $_SESSION['mensaje'] = "Venta no válida.";
    header('Location: ' . $URL . '/dashboard/foraneos.php');
    exit;
}

/* 🚚 Validar venta foránea */
$stmt = $pdo->prepare("SELECT envio FROM tb_ventas WHERE id_venta = ?");
$stmt->execute([$id_venta]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$venta || $venta['envio'] !== 'foraneo') {
    $_SESSION['icono'] = "error";
    $_SESSION['mensaje'] = "Solo se pueden registrar guías para ventas foráneas.";
    header('Location: ' . $URL . '/dashboard/foraneos.php');
    exit;
}

/* 📂 Directorio de subida */
$uploadDir = __DIR__ . '/../../../dashboard/guia_pdf/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

/* 🔢 Número de guía siguiente */
$stmt_num = $pdo->prepare("SELECT COALESCE(MAX(numero), 0) FROM tb_ventas_guias WHERE id_venta = ?");
$stmt_num->execute([$id_venta]);
$siguiente = (int)$stmt_num->fetchColumn() + 1;

$subidas = 0;
$finfo   = finfo_open(FILEINFO_MIME_TYPE);

if ($archivos && is_array($archivos['name'])) {
    foreach ($archivos['name'] as $i => $nombre) {
        if ($archivos['error'][$i] !== UPLOAD_ERR_OK) continue;

        $mime = finfo_file($finfo, $archivos['tmp_name'][$i]);
        $ext  = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        if ($mime !== 'application/pdf' || $ext !== 'pdf') continue;

        $filename = uniqid('guia_', true) . '.pdf';
        if (!move_uploaded_file($archivos['tmp_name'][$i], $uploadDir . $filename)) continue;

        $pdo->prepare("INSERT INTO tb_ventas_guias (id_venta, numero, archivo) VALUES (?, ?, ?)")
            ->execute([$id_venta, $siguiente, $filename]);

        // Primera guía también en tb_ventas.guia_pdf (compatibilidad con vista existente)
        if ($siguiente === 1) {
            $pdo->prepare("UPDATE tb_ventas SET guia_pdf = ? WHERE id_venta = ?")
                ->execute([$filename, $id_venta]);
        }

        $siguiente++;
        $subidas++;
    }
}

/* Guardar paquetería y actualizar estado */
if ($paqueteria || $subidas > 0) {
    $campos = [];
    $params = [];
    if ($paqueteria) { $campos[] = 'paqueteria = ?'; $params[] = $paqueteria; }
    if ($subidas > 0) { $campos[] = "estado_logistico = 'GUIA REGISTRADA'"; }
    if ($campos) {
        $params[] = $id_venta;
        $pdo->prepare("UPDATE tb_ventas SET " . implode(', ', $campos) . " WHERE id_venta = ?")
            ->execute($params);
    }
}

if ($subidas > 0) {
    $_SESSION['icono']   = "success";
    $_SESSION['mensaje'] = "✅ {$subidas} guía(s) subida(s) correctamente.";
} else {
    $_SESSION['icono']   = "error";
    $_SESSION['mensaje'] = "No se subió ninguna guía válida.";
}

header('Location: ' . $URL . '/dashboard/foraneos.php');
exit;
?>