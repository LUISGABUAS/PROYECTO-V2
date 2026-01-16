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

if (!isset($_POST['id_venta']) || !isset($_FILES['guia_pdf'])) {
    http_response_code(400);
    exit;
}

$id_venta = intval($_POST['id_venta']);

/* =========================
   VALIDAR ARCHIVO
========================= */
$archivo = $_FILES['guia_pdf'];

if ($archivo['error'] !== 0) {
    http_response_code(400);
    exit;
}

$ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
if ($ext !== 'pdf') {
    http_response_code(400);
    exit;
}

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
    exit;
}

/* =========================
   ELIMINAR PDF ANTERIOR
========================= */
$ruta = __DIR__ . '/../../../dashboard/guia_pdf/';

if (!empty($venta['guia_pdf']) && file_exists($ruta . $venta['guia_pdf'])) {
    unlink($ruta . $venta['guia_pdf']);
}

if (!is_uploaded_file($archivo['tmp_name'])) {
    http_response_code(400);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $archivo['tmp_name']);

if ($mime !== 'application/pdf') {
    http_response_code(400);
    exit;
}


/* =========================
   GUARDAR NUEVO PDF
========================= */
$nuevo_nombre = 'guia_' . $id_venta . '_' . time() . '.pdf';
move_uploaded_file($archivo['tmp_name'], $ruta . $nuevo_nombre);

/* =========================
   ACTUALIZAR BD
========================= */
$update = "UPDATE tb_ventas 
           SET guia_pdf = :guia_pdf 
           WHERE id_venta = :id_venta";

$stmt = $pdo->prepare($update);
$stmt->execute([
    'guia_pdf' => $nuevo_nombre,
    'id_venta' => $id_venta
]);

echo json_encode(['success' => true]);
exit;
?>