<?php

include('../../config.php');

$nombre_proveedor = $_GET['nombre_proovedor'];
$id_proovedor = $_GET['id_proovedor'];
$celular = $_GET['celular'];
$telefono = $_GET['telefono'];
$empresa = $_GET['empresa'];
$email = $_GET['email'];
$direccion = $_GET['direccion'];
$id_proovedor = $_GET['id_proovedor'];


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
    session_start();
    $_SESSION['mensaje'] = "Proveedor actualizado correctamente";
    ?>
        <script>
            location.href = "<?php echo $URL; ?>/provedores/";
        </script>
    <?php
} else {
    session_start();
    $_SESSION['mensaje'] = "Error: no se pudo actualizar el proveedor";
    ?>
        <script>
            location.href = "<?php echo $URL; ?>/provedores/";
        </script>
    <?php
}

