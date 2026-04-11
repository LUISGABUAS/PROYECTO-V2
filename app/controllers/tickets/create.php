<?php
ini_set('display_errors', 0);
require_once(dirname(__DIR__, 2) . '/config.php');
include(__DIR__ . '/../helpers/csrf.php');
if (session_status() === PHP_SESSION_NONE) session_start();
csrf_verify();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../tickets/create.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario_sesion'] ?? null;
$titulo     = trim($_POST['titulo']      ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$importancia = $_POST['importancia']     ?? 'media';

$importancias_validas = ['baja', 'media', 'alta', 'critica'];
if (empty($titulo) || empty($descripcion) || !in_array($importancia, $importancias_validas)) {
    $_SESSION['mensaje'] = '❌ Completa todos los campos obligatorios';
    header("Location: ../../../tickets/create.php");
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO tb_tickets (id_usuario, titulo, descripcion, importancia, estado)
        VALUES (?, ?, ?, ?, 'pendiente')
    ");
    $stmt->execute([$id_usuario, $titulo, $descripcion, $importancia]);
    $id_ticket = $pdo->lastInsertId();

    // Procesar archivos adjuntos (múltiples)
    if (!empty($_FILES['archivos']['name'][0])) {
        $carpeta = __DIR__ . '/../../tickets_archivos/';
        if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);

        $ext_permitidas = ['pdf','jpg','jpeg','png','gif','doc','docx','xls','xlsx','txt','mp4','mov','avi'];
        $max_size       = 20 * 1024 * 1024; // 20MB por archivo

        foreach ($_FILES['archivos']['tmp_name'] as $i => $tmp) {
            if ($_FILES['archivos']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $nombre_original = $_FILES['archivos']['name'][$i];
            $ext  = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
            $tipo = $_FILES['archivos']['type'][$i];
            $size = $_FILES['archivos']['size'][$i];

            if (!in_array($ext, $ext_permitidas) || $size > $max_size) continue;

            $nombre_guardado = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($tmp, $carpeta . $nombre_guardado)) {
                $stmt2 = $pdo->prepare("
                    INSERT INTO tb_tickets_archivos (id_ticket, nombre_original, ruta, tipo, tamano)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt2->execute([
                    $id_ticket,
                    $nombre_original,
                    'app/tickets_archivos/' . $nombre_guardado,
                    $tipo,
                    $size
                ]);
            }
        }
    }

    $pdo->commit();

    include('../helpers/auditoria.php');
    registrarAuditoria($pdo, $id_usuario, $_SESSION['nombre_usuario'] ?? null,
        'CREAR TICKET', 'tb_tickets', $id_ticket, "Ticket #$id_ticket — $titulo");

    $_SESSION['mensaje'] = "✅ Ticket #$id_ticket enviado correctamente";
    header("Location: ../../../tickets");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['mensaje'] = '❌ Error al crear el ticket: ' . $e->getMessage();
    header("Location: ../../../tickets/create.php");
    exit;
}
