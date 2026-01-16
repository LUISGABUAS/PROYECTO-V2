<?php

include('../../config.php');

$id_proovedor = $_GET['id_proovedor'];  // corregido

$sentencia = $pdo->prepare("DELETE FROM tb_proveedores WHERE id_proovedor = :id_proovedor");

$sentencia->bindParam(':id_proovedor', $id_proovedor);  // corregido también

if ($sentencia->execute()) {
    session_start();
    $_SESSION['mensaje'] = "Se ha eliminado el proveedor correctamente";
    ?>
    <script>
        location.href = "<?php echo $URL; ?>/provedores/";
    </script>
    <?php
} else {
    session_start();
    $_SESSION['mensaje'] = "No se ha podido borrar el proveedor, intente nuevamente";
    ?>
    <script>
        location.href = "<?php echo $URL; ?>/provedores/";
    </script>
    <?php
}

