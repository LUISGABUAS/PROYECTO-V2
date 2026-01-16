<?php
include('../../../app/config.php');
session_start();

/* ==========================
   VALIDAR CÓDIGO
========================== */
$codigo = trim($_POST['codigo_unico'] ?? '');


if (!$codigo) {
    $_SESSION['mensaje'] = "No se recibió ningún código";
    $_SESSION['icono'] = "error";
    header("Location: ".$URL."/stock/scan.php");
    exit;
}

/* ==========================
   BUSCAR EN STOCK
========================== */
$sentencia = $pdo->prepare("SELECT id_stock, estado 
    FROM stock 
    WHERE codigo_unico = :codigo
    LIMIT 1
");
$sentencia->bindParam(':codigo', $codigo);
$sentencia->execute();
$stock = $sentencia->fetch(PDO::FETCH_ASSOC);

if (!$stock) {
    $_SESSION['mensaje'] = "Código no encontrado";
    $_SESSION['icono'] = "error";
    header("Location: ".$URL."/stock/scan.php");
    exit;
}

/* ==========================
   VALIDAR ESTADO
========================== */
if ($stock['estado'] !== 'SIN ESCANEAR') {
    $_SESSION['mensaje'] = "El producto ya está en estado: ".$stock['estado'];
    $_SESSION['icono'] = "warning";
    header("Location: ".$URL."/stock/scan.php");
    exit;
}

/* ==========================
   ACTUALIZAR A EN BODEGA
========================== */
$update = $pdo->prepare("UPDATE stock 
    SET estado = 'EN BODEGA',
        fecha_ingreso = NOW()
    WHERE id_stock = :id_stock
");
$update->bindParam(':id_stock', $stock['id_stock']);
$update->execute();

/* ==========================
   OK
========================== */
$_SESSION['mensaje'] = "Producto ingresado correctamente a bodega";
$_SESSION['icono'] = "success";

header("Location: ".$URL."/stock/scan.php");

exit;
