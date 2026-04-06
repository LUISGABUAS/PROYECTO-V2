<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../../config.php');

// Definir ruta base del proyecto una sola vez
define('PROJECT_ROOT', realpath(__DIR__ . '/../../..'));

// Verificar si el usuario está autenticado
if (!isset($_SESSION['sesion_email'])) {
    echo json_encode(['success' => false, 'mensaje' => 'No autenticado']);
    exit;
}

$email_sesion = $_SESSION['sesion_email'];

// Obtener datos del usuario actual con su rol
$sql = "SELECT us.id, us.nombres, us.email, us.foto_perfil, rol.rol 
        FROM tb_usuario us
        LEFT JOIN tb_roles rol ON us.id_rol = rol.id_rol
        WHERE us.email = :email LIMIT 1";
$query = $pdo->prepare($sql);
$query->bindParam(':email', $email_sesion);
$query->execute();

$usuario = $query->fetch(PDO::FETCH_ASSOC);

if ($usuario) {
    // Verificar si existe imagen de perfil
    $imagen_path = '';
    if (!empty($usuario['foto_perfil'])) {
        $ruta_foto = PROJECT_ROOT . '/' . $usuario['foto_perfil'];
        if (file_exists($ruta_foto)) {
            // Retornar la ruta sin la barra inicial para que el frontend la construya correctamente
            $imagen_path = trim($usuario['foto_perfil'], '/');
        }
    }
    
    echo json_encode([
        'success' => true,
        'id' => $usuario['id'],
        'nombres' => $usuario['nombres'],
        'email' => $usuario['email'],
        'rol' => $usuario['rol'] ?? 'Sin rol asignado',
        'imagen' => $imagen_path
    ]);
} else {
    echo json_encode(['success' => false, 'mensaje' => 'Usuario no encontrado']);
}
?>
