<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../../config.php');
include('../helpers/auditoria.php');
include('../helpers/csrf.php');

csrf_verify();

// Validar que los datos existan
if(!isset($_POST['nombre_proovedor']) || empty($_POST['nombre_proovedor'])){
    echo json_encode(['success' => false, 'message' => 'El nombre del proveedor es requerido']);
    exit;
}

$nombre_proveedor = $_POST['nombre_proovedor'];
$celular = $_POST['celular'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$empresa = $_POST['empresa'] ?? '';
$email = $_POST['email'] ?? '';
$direccion = $_POST['direccion'] ?? '';

try {
    $sentencia = $pdo->prepare("INSERT INTO tb_proveedores
             (nombre_proveedor, celular, telefono, empresa, email, direccion, fyh_creacion)
      VALUES (:nombre_proveedor,:celular, :telefono, :empresa, :email, :direccion, :fyh_creacion)");

    $sentencia->bindParam(':nombre_proveedor', $nombre_proveedor);
    $sentencia->bindParam(':celular', $celular);
    $sentencia->bindParam(':telefono', $telefono);
    $sentencia->bindParam(':empresa', $empresa);
    $sentencia->bindParam(':email', $email);
    $sentencia->bindParam(':direccion', $direccion);
    $sentencia->bindParam(':fyh_creacion', $fechaHora);
    
    if($sentencia->execute()){
        $id_nuevo_proveedor = $pdo->lastInsertId();
        $id_usuario_audit = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;
        $nombre_audit = $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null;
        registrarAuditoria($pdo, $id_usuario_audit, $nombre_audit, 'CREAR PROVEEDOR', 'tb_proveedores', $id_nuevo_proveedor, $nombre_proveedor);
        $_SESSION['mensaje'] = "Se ha creado el proveedor correctamente";
        echo json_encode(['success' => true, 'message' => 'Proveedor creado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear el proveedor. Intente nuevamente']);
    }
} catch(PDOException $e){
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ']);
}


