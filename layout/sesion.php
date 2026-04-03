<?php
session_start();

require_once __DIR__ . '/../app/controllers/helpers/csrf.php';
csrf_token(); // Genera el token si no existe

if (!isset($_SESSION['sesion_email'])) {
    header("Location: ".$URL."/login/index.php");
    exit;
}

$email_sesion = $_SESSION['sesion_email'];

// ================================
// Caché de sesión (evita 2 queries por página)
// Se refresca cada 5 minutos o si se fuerza con _session_refresh
// ================================
$cache_valido = isset($_SESSION['_cache_time'])
    && (time() - $_SESSION['_cache_time']) < 300
    && isset($_SESSION['id_usuario_sesion'])
    && empty($_SESSION['_session_refresh']);

if ($cache_valido) {
    $id_usuario_sesion = $_SESSION['id_usuario_sesion'];
    $sesion_nombres    = $_SESSION['sesion_nombres'];
    $rol_sesion        = $_SESSION['rol_sesion'];
    $id_rol_sesion     = $_SESSION['id_rol_sesion'];
    $sesion_foto       = $_SESSION['sesion_foto'];
} else {
    $sql = "SELECT
                us.id,
                us.nombres,
                us.email,
                us.foto_perfil,
                us.session_token,
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

    // Verificar token de sesión (invalidar si cambió contraseña)
    if (isset($_SESSION['session_token']) && isset($usuario['session_token'])
        && !empty($usuario['session_token'])
        && $_SESSION['session_token'] !== $usuario['session_token']) {
        session_destroy();
        header("Location: ".$URL."/login/index.php?msg=session_expired");
        exit;
    }

    $id_usuario_sesion = $usuario['id'];
    $sesion_nombres    = $usuario['nombres'];
    $rol_sesion        = $usuario['rol'];
    $id_rol_sesion     = $usuario['id_rol'];
    $sesion_foto       = $usuario['foto_perfil'];

    // Cargar permisos del rol
    $sentencia = $pdo->prepare("SELECT p.id_permiso
        FROM permisos p
        INNER JOIN tb_roles_permisos rp ON rp.id_permiso = p.id_permiso
        WHERE rp.id_rol = :id_rol
    ");
    $sentencia->bindParam(':id_rol', $id_rol_sesion);
    $sentencia->execute();
    $permisos_usuario = $sentencia->fetchAll(PDO::FETCH_COLUMN);

    // Guardar en caché de sesión
    $_SESSION['permisos']          = $permisos_usuario;
    $_SESSION['id_usuario_sesion'] = $id_usuario_sesion;
    $_SESSION['sesion_nombres']    = $sesion_nombres;
    $_SESSION['rol_sesion']        = $rol_sesion;
    $_SESSION['id_rol_sesion']     = $id_rol_sesion;
    $_SESSION['sesion_foto']       = $sesion_foto;
    $_SESSION['_cache_time']       = time();
    unset($_SESSION['_session_refresh']);
}
?>
