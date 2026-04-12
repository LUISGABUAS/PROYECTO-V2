<?php
ob_start();
ini_set('display_errors', 0);
require_once(dirname(__DIR__, 2) . '/config.php');
include(__DIR__ . '/../helpers/csrf.php');
if (session_status() === PHP_SESSION_NONE) session_start();
csrf_verify();

$es_ajax = !empty($_POST['_ajax']);

function responder($es_ajax, $ok, $mensaje, $redirect = '') {
    ob_end_clean();
    if ($es_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok, 'message' => $mensaje, 'redirect' => $redirect]);
        exit;
    }
    if (!$ok) {
        $_SESSION['mensaje'] = $mensaje;
        header("Location: ../../../tickets/create.php");
    } else {
        $_SESSION['mensaje'] = $mensaje;
        header("Location: $redirect");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../../tickets/create.php");
    exit;
}

$id_usuario  = $_SESSION['id_usuario_sesion'] ?? null;
$titulo      = trim($_POST['titulo']      ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$importancia = $_POST['importancia']      ?? 'media';

$importancias_validas = ['baja', 'media', 'alta', 'critica'];
if (empty($titulo) || empty($descripcion) || !in_array($importancia, $importancias_validas)) {
    responder($es_ajax, false, '❌ Completa todos los campos obligatorios');
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
    $debug_archivos = [];
    if (!empty($_FILES['archivos']['name'][0])) {
        $carpeta = realpath(__DIR__ . '/../../') . '/tickets_archivos/';
        $debug_archivos['carpeta'] = $carpeta;
        $debug_archivos['dir_existe'] = is_dir($carpeta) ? 'SI' : 'NO';
        $debug_archivos['writable']   = is_writable($carpeta) ? 'SI' : 'NO';

        if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);

        $ext_permitidas = ['pdf','jpg','jpeg','png','gif','doc','docx','xls','xlsx','txt','mp4','mov','avi'];
        $max_size       = 20 * 1024 * 1024;

        foreach ($_FILES['archivos']['tmp_name'] as $i => $tmp) {
            if ($_FILES['archivos']['error'][$i] !== UPLOAD_ERR_OK) {
                $debug_archivos['error_' . $i] = 'UPLOAD_ERR código ' . $_FILES['archivos']['error'][$i];
                error_log('[TICKETS] Upload error código ' . $_FILES['archivos']['error'][$i] . ' en archivo ' . ($_FILES['archivos']['name'][$i] ?? '?'));
                continue;
            }

            $nombre_original = $_FILES['archivos']['name'][$i];
            $ext  = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
            $tipo = $_FILES['archivos']['type'][$i];
            $size = $_FILES['archivos']['size'][$i];

            if (!in_array($ext, $ext_permitidas) || $size > $max_size) {
                $debug_archivos['rechazado_' . $i] = $nombre_original . ' ext=' . $ext . ' size=' . $size;
                error_log('[TICKETS] Archivo rechazado: ' . $nombre_original . ' ext=' . $ext . ' size=' . $size);
                continue;
            }

            $nombre_guardado = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
            $destino = $carpeta . $nombre_guardado;

            if (move_uploaded_file($tmp, $destino)) {
                $debug_archivos['guardado_' . $i] = $nombre_guardado;
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
            } else {
                $debug_archivos['move_fallo_' . $i] = 'Origen: ' . $tmp . ' Destino: ' . $destino;
                error_log('[TICKETS] move_uploaded_file falló. Origen: ' . $tmp . ' Destino: ' . $destino . ' | Dir existe: ' . (is_dir($carpeta)?'SI':'NO') . ' | Writable: ' . (is_writable($carpeta)?'SI':'NO'));
            }
        }
    } else {
        $debug_archivos['files_vacio'] = true;
        $debug_archivos['files_raw'] = json_encode($_FILES);
    }

    $pdo->commit();

    include('../helpers/auditoria.php');
    registrarAuditoria($pdo, $id_usuario, $_SESSION['nombre_usuario'] ?? null,
        'CREAR TICKET', 'tb_tickets', $id_ticket, "Ticket #$id_ticket — $titulo");

    responder($es_ajax, true, "✅ Ticket #$id_ticket enviado correctamente — debug: " . json_encode($debug_archivos), '../../../tickets');

} catch (Exception $e) {
    $pdo->rollBack();
    responder($es_ajax, false, '❌ Error al crear el ticket: ' . $e->getMessage());
}
