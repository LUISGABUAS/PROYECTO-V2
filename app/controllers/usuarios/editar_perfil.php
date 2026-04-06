<?php
header('Content-Type: application/json');

include('../../config.php');

// Definir ruta base del proyecto una sola vez
define('PROJECT_ROOT', realpath(__DIR__ . '/../../..'));
define('UPLOADS_DIR', PROJECT_ROOT . '/public/uploads/perfiles/');

// Verificar si el usuario está autenticado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['sesion_email'])) {
    echo json_encode(['success' => false, 'mensaje' => 'No autenticado']);
    exit;
}

$email_sesion = $_SESSION['sesion_email'];
$nombres = $_POST['nombres'] ?? '';
$email = $_POST['email'] ?? '';
$password_actual = $_POST['password_actual'] ?? '';
$password_nueva = $_POST['password_nueva'] ?? '';
$password_confirmacion = $_POST['password_confirmacion'] ?? '';

// Validación de datos básicos
if (empty($nombres) || empty($email)) {
    echo json_encode(['success' => false, 'mensaje' => 'El nombre y email son requeridos']);
    exit;
}

// Validar formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'mensaje' => 'El email no es válido']);
    exit;
}

// Obtener datos del usuario actual
$sql = "SELECT id, password_user, foto_perfil FROM tb_usuario WHERE email = :email LIMIT 1";
$query = $pdo->prepare($sql);
$query->bindParam(':email', $email_sesion);
$query->execute();

$usuario = $query->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo json_encode(['success' => false, 'mensaje' => 'Usuario no encontrado']);
    exit;
}

$id_usuario = $usuario['id'];
$password_hash = $usuario['password_user'];
$foto_perfil_actual = $usuario['foto_perfil'];

// Validar que el email no esté siendo usado por otro usuario (a menos que sea el mismo email actual)
if ($email !== $email_sesion) {
    $sql_check_email = "SELECT COUNT(*) as count FROM tb_usuario WHERE email = :email";
    $query_check = $pdo->prepare($sql_check_email);
    $query_check->bindParam(':email', $email);
    $query_check->execute();
    $result = $query_check->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'mensaje' => 'El email ya está siendo utilizado por otro usuario']);
        exit;
    }
}

// Manejar subida de foto de perfil
$foto_perfil_nueva = $foto_perfil_actual;
if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['profileImage'];
    
    // Verificar errores de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errores_upload = [
            UPLOAD_ERR_INI_SIZE => 'El archivo es demasiado grande (límite del servidor)',
            UPLOAD_ERR_FORM_SIZE => 'El archivo es demasiado grande (límite del formulario)',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió incompleto',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal del servidor',
            UPLOAD_ERR_CANT_WRITE => 'No se puede escribir en la carpeta',
            UPLOAD_ERR_EXTENSION => 'Extensión no permitida',
        ];
        $msg_error = $errores_upload[$file['error']] ?? 'Error desconocido al subir';
        echo json_encode(['success' => false, 'mensaje' => $msg_error]);
        exit;
    }
    
    // Validar que sea una imagen
    $image_info = @getimagesize($file['tmp_name']);
    if (!$image_info) {
        echo json_encode(['success' => false, 'mensaje' => 'El archivo debe ser una imagen válida (JPG, PNG, GIF, etc)']);
        exit;
    }
    
    // Validar tamaño máximo (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'mensaje' => 'La imagen no debe superar 5MB. Tamaño actual: ' . round($file['size'] / 1024 / 1024, 2) . 'MB']);
        exit;
    }
    
    // Crear carpeta si no existe (con ruta absoluta)
    if (!is_dir(UPLOADS_DIR)) {
        $mkdir_result = @mkdir(UPLOADS_DIR, 0777, true);
        if (!$mkdir_result) {
            echo json_encode(['success' => false, 'mensaje' => 'No se pudo crear la carpeta de almacenamiento. Verifica los permisos.']);
            exit;
        }
    }
    
    // Verificar que la carpeta sea escribible
    if (!is_writable(UPLOADS_DIR)) {
        echo json_encode(['success' => false, 'mensaje' => 'La carpeta de almacenamiento no tiene permisos de escritura']);
        exit;
    }
    
    $upload_dir = UPLOADS_DIR;
    
    // Generar nombre único para la imagen
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    // Validar extensión
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($extension, $extensiones_permitidas)) {
        echo json_encode(['success' => false, 'mensaje' => 'Formato de imagen no permitido. Usa: JPG, PNG, GIF o WEBP']);
        exit;
    }
    
    $nombre_archivo = 'perfil_' . $id_usuario . '_' . time() . '.' . $extension;
    $ruta_archivo = $upload_dir . $nombre_archivo;
    
    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $ruta_archivo)) {
        echo json_encode(['success' => false, 'mensaje' => 'Error al guardar la imagen. Verifica los permisos de la carpeta.']);
        exit;
    }
    
    // Establecer permisos de lectura al archivo
    @chmod($ruta_archivo, 0644);
    
    // Eliminar foto anterior si existe
    if (!empty($foto_perfil_actual)) {
        $ruta_foto_anterior = PROJECT_ROOT . '/' . $foto_perfil_actual;
        if (file_exists($ruta_foto_anterior)) {
            @unlink($ruta_foto_anterior);
        }
    }
    
    $foto_perfil_nueva = 'public/uploads/perfiles/' . $nombre_archivo;
}

// Si se quiere cambiar contraseña
if (!empty($password_nueva)) {
    // Validar que se ingresó la contraseña actual
    if (empty($password_actual)) {
        echo json_encode(['success' => false, 'mensaje' => 'Debes ingresar tu contraseña actual para cambiarla']);
        exit;
    }
    
    // Verificar que la contraseña actual sea correcta
    if (!password_verify($password_actual, $password_hash)) {
        echo json_encode(['success' => false, 'mensaje' => 'La contraseña actual es incorrecta']);
        exit;
    }
    
    // Validar que las nuevas contraseñas coincidan
    if ($password_nueva !== $password_confirmacion) {
        echo json_encode(['success' => false, 'mensaje' => 'Las nuevas contraseñas no coinciden']);
        exit;
    }
    
    // Validar longitud mínima de contraseña
    if (strlen($password_nueva) < 6) {
        echo json_encode(['success' => false, 'mensaje' => 'La contraseña debe tener al menos 6 caracteres']);
        exit;
    }
    
    $password_nueva = password_hash($password_nueva, PASSWORD_DEFAULT);
    
    // Actualizar con contraseña
    $sentencia = $pdo->prepare("UPDATE tb_usuario 
                                SET nombres = :nombres,
                                    email = :email,
                                    foto_perfil = :foto_perfil,
                                    password_user = :password_user,
                                    fyh_actualizacion = :fyh_actualizacion
                                WHERE id = :id");
    
    $sentencia->bindParam(':password_user', $password_nueva);
} else {
    // Actualizar sin contraseña
    $sentencia = $pdo->prepare("UPDATE tb_usuario 
                                SET nombres = :nombres,
                                    email = :email,
                                    foto_perfil = :foto_perfil,
                                    fyh_actualizacion = :fyh_actualizacion
                                WHERE id = :id");
}

$sentencia->bindParam(':nombres', $nombres);
$sentencia->bindParam(':email', $email);
$sentencia->bindParam(':foto_perfil', $foto_perfil_nueva);
$sentencia->bindParam(':fyh_actualizacion', $fechaHora);
$sentencia->bindParam(':id', $id_usuario);

try {
    $sentencia->execute();
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Perfil actualizado correctamente',
        'nombres' => $nombres,
        'imagen' => $foto_perfil_nueva
    ]);
} catch (PDOException $e) {
    // Verificar si es error de email duplicado
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo json_encode(['success' => false, 'mensaje' => 'El email ya está siendo utilizado por otro usuario']);
    } else {
        echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar el perfil: ' . $e->getMessage()]);
    }
}
?>
