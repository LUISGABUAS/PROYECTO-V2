<?php
$id_usuario_get = $_GET['id'];

$sql_usuarios = "SELECT us.id as id, us.nombres as nombres, us.email as email, rol.rol as rol
                FROM tb_usuario as us INNER JOIN tb_roles as rol ON us.id_rol = rol.id_rol
                WHERE us.id = :id";
$query_usuarios = $pdo->prepare($sql_usuarios);
$query_usuarios->bindParam(':id', $id_usuario_get);
$query_usuarios->execute();
$dato = $query_usuarios->fetch(PDO::FETCH_ASSOC);

$nombres = $dato['nombres'] ?? '';
$email = $dato['email'] ?? '';
$rol = $dato['rol'] ?? '';