<?php
function permitir($permiso) {
    global $pdo;
    $id_rol = $_SESSION['id_rol'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rol_permiso
        WHERE id_rol = :id_rol
        AND id_permiso = (SELECT id_permiso FROM permisos WHERE nombre = :permiso)
    ");
    $stmt->bindParam(':id_rol', $id_rol);
    $stmt->bindParam(':permiso', $permiso);
    $stmt->execute();

    if ($stmt->fetchColumn() == 0) {
        $_SESSION['mensaje'] = "No tienes permiso para esta acción";
        $_SESSION['icono'] = "danger";
        header('Location: '.$GLOBALS['URL'].'/index.php');
        exit;
    }
}
