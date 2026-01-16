<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../../config.php');
session_start();

/* 🔒 Validar método */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $URL . '/dashboard/foraneos.php');
    exit;
}

$id_venta = $_POST['id_venta'] ?? null;
$guia_pdf = $_FILES['guia_pdf'] ?? null;

/* 🔴 Validación básica */
if (!$id_venta || !$guia_pdf || $guia_pdf['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['icono'] = "error";
    $_SESSION['mensaje'] = "No se proporcionó una guía válida.";
    header('Location: ' . $URL . '/dashboard/foraneos.php');
    exit;
}

/* 📄 Validar MIME */
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $guia_pdf['tmp_name']);

if ($mime !== 'application/pdf') {
    $_SESSION['icono'] = "error";
    $_SESSION['mensaje'] = "El archivo debe ser un PDF.";
    header('Location: ' . $URL . '/dashboard/foraneos.php');
    exit;
}

/* 📎 Validar extensión */
$ext = strtolower(pathinfo($guia_pdf['name'], PATHINFO_EXTENSION));
if ($ext !== 'pdf') {
    $_SESSION['icono'] = "error";
    $_SESSION['mensaje'] = "La extensión del archivo debe ser .pdf";
    header('Location: ' . $URL . '/dashboard/foraneos.php');
    exit;
}

/* 🚚 Validar venta foránea */
$stmt = $pdo->prepare("SELECT COUNT(*) 
    FROM tb_ventas 
    WHERE id_venta = :id_venta 
      AND envio = 'foraneo'
");
$stmt->execute([':id_venta' => $id_venta]);

if ($stmt->fetchColumn() == 0) {
    $_SESSION['icono'] = "error";
    $_SESSION['mensaje'] = "La venta no es foránea o no existe.";
    header('Location: ' . $URL . '/dashboard/foraneos.php');
    exit;
}

/* 📂 Subida de archivo */
$uploadDir = __DIR__ . '/../../../dashboard/guia_pdf/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = uniqid('guia_', true) . '.pdf';
$uploadFile = $uploadDir . $filename;

if (move_uploaded_file($guia_pdf['tmp_name'], $uploadFile)) {

    $stmt = $pdo->prepare("
        UPDATE tb_ventas 
        SET guia_pdf = :guia_pdf 
        WHERE id_venta = :id_venta
    ");
    $stmt->execute([
        ':guia_pdf' => $filename,
        ':id_venta' => $id_venta
    ]);

    $_SESSION['icono'] = "success";
    $_SESSION['mensaje'] = "Guía subida correctamente.";
} else {
    $_SESSION['icono'] = "error";
    $_SESSION['mensaje'] = "Error al mover el archivo.";
}

header('Location: ' . $URL . '/dashboard/foraneos.php');
exit;
?>