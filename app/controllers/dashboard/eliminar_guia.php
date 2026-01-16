<?php

require_once '../../config.php';
session_start();

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
   ELIMINAR PDF FÍSICO
========================= */
$ruta = __DIR__ . '/../../../dashboard/guia_pdf/';

if (!empty($venta['guia_pdf'])) {
    $archivo = $ruta . $venta['guia_pdf'];

    if (file_exists($archivo)) {
        unlink($archivo);
    }
}

/* =========================
   ACTUALIZAR BD
========================= */
$update = "UPDATE tb_ventas 
           SET guia_pdf = NULL 
           WHERE id_venta = :id_venta";

$stmt = $pdo->prepare($update);

if ($stmt->execute(['id_venta' => $id_venta])) {

    $_SESSION['icono'] = 'success';
    $_SESSION['mensaje'] = 'Guía eliminada correctamente';
    http_response_code(200);

    echo json_encode(['success' => true]);

} else {

    $_SESSION['icono'] = 'error';
    $_SESSION['mensaje'] = 'Error al eliminar la guía';
    http_response_code(500);

    echo json_encode(['success' => false]);
}

exit;
