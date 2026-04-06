<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../../config.php');
include('../helpers/auditoria.php');
include('../helpers/csrf.php');

csrf_verify();

$id_proovedor = $_POST['id_proovedor'];

$sentencia = $pdo->prepare("DELETE FROM tb_proveedores WHERE id_proovedor = :id_proovedor");
$sentencia->bindParam(':id_proovedor', $id_proovedor);

if ($sentencia->execute()) {
    $id_usuario_audit = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;
    $nombre_audit = $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null;
    registrarAuditoria($pdo, $id_usuario_audit, $nombre_audit, 'ELIMINAR PROVEEDOR', 'tb_proveedores', $id_proovedor, "Proveedor ID: $id_proovedor eliminado");
    $_SESSION['mensaje'] = "Se ha eliminado el proveedor correctamente";
    ?>
    <script>
        location.href = "<?php echo $URL; ?>/provedores/";
    </script>
    <?php
} else {
    $_SESSION['mensaje'] = "No se ha podido borrar el proveedor, intente nuevamente";
    ?>
    <script>
        location.href = "<?php echo $URL; ?>/provedores/";
    </script>
    <?php
}
