<?php

include('../../config.php');

$nombre_categoria = $_GET['nombre_categoria'];

$sentencia = $pdo->prepare("INSERT INTO tb_categorias
         (nombre_categoria, fyh_creacion)
  VALUES (:nombre_categoria, :fyh_creacion)");

    $sentencia->bindParam(':nombre_categoria', $nombre_categoria);
    $sentencia->bindParam(':fyh_creacion', $fechaHora);
    if($sentencia ->execute()){
        session_start();
    $_SESSION['mensaje'] = "se ha creado la categoria correctamente";
   // header("Location: " . $URL . "/categorias");
   ?>   <script>
        location.href = "<?php echo $URL;?>/categorias/";
         </script>
    <?php
    }else{
        session_start();
    $_SESSION['mensaje'] = "No se ha podido crear la categoria, intente nuevamente";
   // header("Location: " . $URL . "/categorias");
    }


