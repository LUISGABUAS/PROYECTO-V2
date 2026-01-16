<?php
session_start();

if (!isset($_SESSION['sesion_email'])) {
    header("Location: ".$URL."/login/index.php");
    exit;
}

$email_sesion = $_SESSION['sesion_email'];

// Obtener datos del usuario y su rol
$sql = "SELECT 
            us.id,
            us.nombres,
            us.email,
            rol.id_rol,
            rol.rol
        FROM tb_usuario us
        INNER JOIN tb_roles rol ON us.id_rol = rol.id_rol
        WHERE us.email = :email
        LIMIT 1";

$query = $pdo->prepare($sql);
$query->bindParam(':email', $email_sesion);
$query->execute();

$usuario = $query->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    session_destroy();
    header("Location: ".$URL."/login/index.php");
    exit;
}

$id_usuario_sesion = $usuario['id'];
$sesion_nombres    = $usuario['nombres'];
$rol_sesion        = $usuario['rol'];
$id_rol_sesion     = $usuario['id_rol']; // Guardamos el id_rol

// ================================
// Cargar permisos del rol
// ================================
$sentencia = $pdo->prepare("SELECT p.id_permiso
    FROM permisos p
    INNER JOIN tb_roles_permisos rp ON rp.id_permiso = p.id_permiso
    WHERE rp.id_rol = :id_rol
");
$sentencia->bindParam(':id_rol', $id_rol_sesion);
$sentencia->execute();
$permisos_usuario = $sentencia->fetchAll(PDO::FETCH_COLUMN);

// Guardar permisos en sesión
$_SESSION['permisos'] = $permisos_usuario;
?>
