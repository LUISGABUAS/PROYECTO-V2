<?php

session_start();
include('../../config.php');
include('../helpers/auditoria.php');
include('../helpers/csrf.php');

csrf_verify();

$nombre_proveedor = $_POST['nombre_proovedor'];
$id_proovedor = $_POST['id_proovedor'];
$celular = $_POST['celular'];
$telefono = $_POST['telefono'];
$empresa = $_POST['empresa'];
$email = $_POST['email'];
$direccion = $_POST['direccion'];

$sentencia = $pdo->prepare("
    UPDATE tb_proveedores SET
        nombre_proveedor = :nombre_proveedor,
        celular = :celular,
        telefono = :telefono,
        empresa = :empresa,
        email = :email,
        direccion = :direccion,
        fyh_actualizacion = :fyh_actualizacion
    WHERE id_proovedor = :id_proovedor
");

$sentencia->bindParam(':nombre_proveedor', $nombre_proveedor);
$sentencia->bindParam(':celular', $celular);
$sentencia->bindParam(':telefono', $telefono);
$sentencia->bindParam(':empresa', $empresa);
$sentencia->bindParam(':email', $email);
$sentencia->bindParam(':direccion', $direccion);
$sentencia->bindParam(':fyh_actualizacion', $fechaHora);
$sentencia->bindParam(':id_proovedor', $id_proovedor);

if ($sentencia->execute()) {
    $id_usuario_audit = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;
    $nombre_audit = $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null;
    registrarAuditoria($pdo, $id_usuario_audit, $nombre_audit, 'ACTUALIZAR PROVEEDOR', 'tb_proveedores', $id_proovedor, $nombre_proveedor);
    $_SESSION['mensaje'] = "Proveedor actualizado correctamente";
    ?>
        <script>
            location.href = "<?php echo $URL; ?>/provedores/";
        </script>
    <?php
} else {
    $_SESSION['mensaje'] = "Error: no se pudo actualizar el proveedor";
    ?>
        <script>
            location.href = "<?php echo $URL; ?>/provedores/";
        </script>
    <?php
}
