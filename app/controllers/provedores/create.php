<?php

include('../../config.php');

$nombre_proveedor = $_GET['nombre_proovedor'];
$celular = $_GET['celular'];
$telefono = $_GET['telefono'];
$empresa = $_GET['empresa'];
$email = $_GET['email'];
$direccion = $_GET['direccion'];

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
    if($sentencia ->execute()){
        session_start();
    $_SESSION['mensaje'] = "se ha creado el proveedor correctamente";
   // header("Location: " . $URL . "/categorias");
   ?>   <script>
        location.href = "<?php echo $URL;?>/provedores/";
         </script>
    <?php
    }else{
        session_start();
    $_SESSION['mensaje'] = "No se ha podido crear el proveedor, intente nuevamente";
   // header("Location: " . $URL . "/categorias");
   ?>   <script>
        location.href = "<?php echo $URL;?>/provedores/";
         </script>
    <?php
    }


