<?php
include('../../config.php');
session_start();


// Datos del formulario
$rol = trim($_POST['rol'] ?? '');
$permisos = $_POST['permisos'] ?? []; // Array de IDs de permisos

$permisos = array_unique(array_map('intval', $permisos));



// Validaciones
if(!$rol){
    $_SESSION['mensaje'] = "El nombre del rol es obligatorio";
    header("Location: ../../../roles/create.php");
    exit;
}

if(empty($permisos)){
    $_SESSION['mensaje'] = "Debe asignar al menos un permiso al rol";
    header("Location: ../../../roles/create.php");
    exit;
}

// Validar que el nombre del rol sea único
$check = $pdo->prepare("SELECT COUNT(*) FROM tb_roles WHERE rol = :nombre");
$check->execute([':nombre' => $rol]);
if($check->fetchColumn() > 0){
    $_SESSION['mensaje'] = "Ya existe un rol con ese nombre";
    header("Location: ../../../roles/create.php");
    exit;
}

// Insertar rol
try {
    $pdo->beginTransaction();

    $sentencia = $pdo->prepare("INSERT INTO tb_roles (rol, fyh_creacion) VALUES (:nombre, NOW())");
    $sentencia->bindParam(':nombre', $rol);
    $sentencia->execute();
    $id_rol = $pdo->lastInsertId();

    // Insertar permisos asignados
    $sent_permiso = $pdo->prepare("INSERT INTO tb_roles_permisos (id_rol, id_permiso) VALUES (:id_rol, :id_permiso)");
    foreach($permisos as $permiso_id){
    $sent_permiso->execute([
        ':id_rol' => $id_rol,
        ':id_permiso' => (int)$permiso_id
    ]);
}


    $pdo->commit();
    $_SESSION['icon'] = "success";
    $_SESSION['mensaje'] = "Rol creado correctamente";
    header("Location: ../../../roles/index.php");
} catch(PDOException $e){
    $pdo->rollBack();
    $_SESSION['icon'] = "error";
    $_SESSION['mensaje'] = "Error al crear el rol: " . $e->getMessage();
    header("Location: ../../../roles/create.php");
}
?>
