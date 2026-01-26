<?php
$sql_vendedores = $pdo->prepare("SELECT DISTINCT u.id AS id_usuario, u.nombres
    FROM tb_usuario u
    INNER JOIN tb_roles r ON u.id_rol = r.id_rol
    INNER JOIN tb_roles_permisos rp ON r.id_rol = rp.id_rol
    INNER JOIN permisos p ON rp.id_permiso = p.id_permiso
    WHERE p.id_permiso = 21
");
$sql_vendedores->execute();
$vendedores = $sql_vendedores->fetchAll(PDO::FETCH_ASSOC);
?>
