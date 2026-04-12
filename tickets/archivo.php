<?php
ob_start();
include('../app/config.php');
include('../layout/sesion.php');

if (!in_array(35, $_SESSION['permisos']) && !in_array(37, $_SESSION['permisos'])) {
    ob_end_clean();
    http_response_code(403); exit;
}

$id_archivo = (int)($_GET['id'] ?? 0);
if (!$id_archivo) {
    ob_end_clean();
    http_response_code(400); exit;
}

$stmt = $pdo->prepare("
    SELECT a.*, t.id_usuario
    FROM tb_tickets_archivos a
    JOIN tb_tickets t ON t.id_ticket = a.id_ticket
    WHERE a.id_archivo = ?
");
$stmt->execute([$id_archivo]);
$archivo = $stmt->fetch();

if (!$archivo) {
    ob_end_clean();
    http_response_code(404); exit;
}

// Solo el dueño del ticket o técnico puede ver el archivo
$id_usuario      = $_SESSION['id_usuario_sesion'] ?? 0;
$puede_gestionar = in_array(37, $_SESSION['permisos']);

if (!$puede_gestionar && $archivo['id_usuario'] != $id_usuario) {
    ob_end_clean();
    http_response_code(403); exit;
}

// Ruta física del archivo
// La ruta en BD es: app/tickets_archivos/filename.ext
$ruta_fisica = realpath(__DIR__ . '/../' . $archivo['ruta']);

if (!$ruta_fisica || !file_exists($ruta_fisica)) {
    ob_end_clean();
    http_response_code(404);
    echo "Archivo no encontrado. Ruta: " . htmlspecialchars(__DIR__ . '/../' . $archivo['ruta']);
    exit;
}

// Determinar Content-Type
$ext = strtolower(pathinfo($archivo['nombre_original'], PATHINFO_EXTENSION));
$tipos_mime = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'pdf'  => 'application/pdf',
    'mp4'  => 'video/mp4',
    'mov'  => 'video/quicktime',
    'avi'  => 'video/x-msvideo',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'txt'  => 'text/plain',
];
$mime = $tipos_mime[$ext] ?? 'application/octet-stream';

$inline      = in_array($ext, ['jpg','jpeg','png','gif','pdf','mp4','mov','avi']);
$disposition = $inline ? 'inline' : 'attachment';

ob_end_clean();
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . addslashes($archivo['nombre_original']) . '"');
header('Content-Length: ' . filesize($ruta_fisica));
header('Cache-Control: private, max-age=3600');
readfile($ruta_fisica);
exit;
