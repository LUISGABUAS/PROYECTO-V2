<?php
include('../../config.php');
include(__DIR__ . '/../helpers/csrf.php');
include(__DIR__ . '/../helpers/validador.php');
csrf_verify();
include('../helpers/auditoria.php');


// Datos del formulario
$rol = trim($_POST['rol'] ?? '');
$permisos = $_POST['permisos'] ?? []; // Array de IDs de permisos

$permisos = array_unique(array_map('intval', $permisos));

// Validaciones
if(!$rol){
    error400('El nombre del rol es obligatorio');
    $_SESSION['mensaje'] = "❌ El nombre del rol es obligatorio";
    $_SESSION['icono'] = "error";
    header("Location: ../../../roles/create.php");
    exit;
}

if(empty($permisos)){
    error400('Debe asignar al menos un permiso al rol');
    $_SESSION['mensaje'] = "❌ Debe asignar al menos un permiso";
    $_SESSION['icono'] = "error";
    header("Location: ../../../roles/create.php");
    exit;
}

// Validar que el nombre del rol sea único
$check = $pdo->prepare("SELECT COUNT(*) FROM tb_roles WHERE rol = :nombre");
$check->execute([':nombre' => $rol]);
if($check->fetchColumn() > 0){
    error400('El rol ya existe');
    $_SESSION['mensaje'] = "❌ Ya existe un rol con ese nombre";
    $_SESSION['icono'] = "error";
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
    
    $id_usuario_audit = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;
    $nombre_audit = $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null;
    registrarAuditoria($pdo, $id_usuario_audit, $nombre_audit, 'CREAR ROL', 'tb_roles', $id_rol, $rol);
    
    $_SESSION['icono'] = "success";
    $_SESSION['mensaje'] = "✅ Rol creado correctamente";
    header("Location: ../../../roles/index.php");
} catch (Exception $e){
    $pdo->rollBack();
    error500('Error creando rol', ['error' => $e->getMessage()]);
    $_SESSION['icono'] = "error";
    $_SESSION['mensaje'] = "❌ Error al crear el rol";
    header("Location: ../../../roles/create.php");
}
?>
