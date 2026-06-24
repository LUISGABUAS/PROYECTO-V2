<?php

require_once '../../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   VALIDACIONES BÁSICAS
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (!isset($_POST['id_venta'])) {
    http_response_code(400);
    exit;
}

$id_venta = intval($_POST['id_venta']);

/* =========================
   OBTENER GUÍA ACTUAL
========================= */
$sql = "SELECT guia_pdf 
        FROM tb_ventas 
        WHERE id_venta = :id_venta 
        AND envio = 'foraneo'";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id_venta' => $id_venta]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    http_response_code(404);
    echo json_encode(['success' => false, 'msg' => 'Venta no encontrada']);
    exit;
}

/* =========================
   ELIMINAR TODOS LOS PDFs
========================= */
$ruta = __DIR__ . '/../../../dashboard/guia_pdf/';

// Eliminar archivo de tb_ventas.guia_pdf
if (!empty($venta['guia_pdf'])) {
    $archivo = $ruta . $venta['guia_pdf'];
    if (file_exists($archivo)) unlink($archivo);
}

// Eliminar todos los archivos de tb_ventas_guias
$stmt_guias = $pdo->prepare("SELECT archivo FROM tb_ventas_guias WHERE id_venta = ?");
$stmt_guias->execute([$id_venta]);
foreach ($stmt_guias->fetchAll(PDO::FETCH_COLUMN) as $arch) {
    $f = $ruta . $arch;
    if (file_exists($f)) unlink($f);
}
$pdo->prepare("DELETE FROM tb_ventas_guias WHERE id_venta = ?")->execute([$id_venta]);

/* =========================
   ACTUALIZAR BD
========================= */
$stmt = $pdo->prepare("UPDATE tb_ventas SET guia_pdf = NULL, estado_logistico = 'PENDIENTE GUIA' WHERE id_venta = ?");

if ($stmt->execute([$id_venta])) {
    http_response_code(200);
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false]);
}

exit;
