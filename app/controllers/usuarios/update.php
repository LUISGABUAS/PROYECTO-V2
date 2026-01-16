<?php

include('../../config.php');

$nombres = $_POST['nombres'];
$email = $_POST['email'];
$password_user = $_POST['password_user'];
$password_repeat = $_POST['password_repeat'];
$id = $_POST['id'];
$rol = $_POST['rol'];

if($password_user == ""){

    if ($password_user == $password_repeat) {
        $password_user = password_hash($password_user, PASSWORD_DEFAULT);
        $sentencia = $pdo->prepare("UPDATE tb_usuario 
        SET nombres=:nombres ,
        email=:email,
        id_rol=:id_rol,
       fyh_actualizacion=:fyh_actualizacion 
        WHERE id=:id");
    
        $sentencia->bindParam(':nombres', $nombres);
        $sentencia->bindParam(':email', $email);
        $sentencia->bindParam(':id_rol', $rol);
        $sentencia->bindParam(':fyh_actualizacion', $fechaHora);
        $sentencia->bindParam(':id', $id);
        $sentencia->execute();
        session_start();
        $_SESSION['mensaje'] = "se ha actualizado el usuario correctamente";
         header("Location: " . $URL . "/usuarios");
} else {
    session_start();
    $_SESSION['mensaje'] = "Error las contraseñas no coinciden, intente nuevamente";
    header("Location: " . $URL . "/usuarios/update.php?id=".$id);
}

}else{
    if ($password_user == $password_repeat) {
        $password_user = password_hash($password_user, PASSWORD_DEFAULT);
        $sentencia = $pdo->prepare("UPDATE tb_usuario 
        SET nombres=:nombres ,
        email=:email,
        id_rol=:id_rol,
       password_user= :password_user,
       fyh_actualizacion=:fyh_actualizacion 
        WHERE id=:id");
    
        $sentencia->bindParam(':nombres', $nombres);
        $sentencia->bindParam(':email', $email);
        $sentencia->bindParam(':id_rol', $rol);
        $sentencia->bindParam(':password_user', $password_user);
        $sentencia->bindParam(':fyh_actualizacion', $fechaHora);
        $sentencia->bindParam(':id', $id);
        $sentencia->execute();
        session_start();
        $_SESSION['mensaje'] = "se ha actualizado el usuario correctamente";
         header("Location: " . $URL . "/usuarios");
} else {
    session_start();
    $_SESSION['mensaje'] = "Error las contraseñas no coinciden, intente nuevamente";
    header("Location: " . $URL . "/usuarios/update.php?id=".$id);
} }