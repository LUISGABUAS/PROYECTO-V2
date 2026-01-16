<?php
include('../../config.php');
session_start();

/* ==========================
   MUESTRA ERRORES PARA DEPURAR
========================== */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ==========================
   DATOS DEL FORMULARIO
========================== */
$id_producto = $_POST['id_producto'] ?? null;
$cantidad    = $_POST['cantidad'] ?? 0;
$id_usuario  = $_POST['id_usuario'] ?? null;

/* ==========================
   VALIDACIÓN DE DATOS
========================== */
if (!$id_producto || $cantidad <= 0 || !$id_usuario) {
    $_SESSION['mensaje'] = "Datos inválidos para generar stock";
    header("Location: ".$URL."/stock/create.php");
    exit;
}

/* ==========================
   OBTENER CÓDIGO DEL PRODUCTO
========================== */
$sql_producto = "SELECT codigo FROM tb_almacen WHERE id_producto = :id_producto";
$query_producto = $pdo->prepare($sql_producto);
$query_producto->bindParam(':id_producto', $id_producto);
$query_producto->execute();

$producto = $query_producto->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    $_SESSION['mensaje'] = "Producto no encontrado";
    header("Location: ".$URL."/stock/create.php");
    exit;
}

$codigo_producto = $producto['codigo']; // Ej: P-00001

/* ==========================
   INSERTAR STOCK CON TRANSACCIÓN
========================== */
$estado = 'SIN ESCANEAR';
$fecha  = date('Y-m-d H:i:s');

$sql = "INSERT INTO stock (id_producto, codigo_unico, estado, fecha_ingreso, creado_por)
        VALUES (:id_producto, '', :estado, :fecha_ingreso, :creado_por)";
$sentencia = $pdo->prepare($sql);

try {
    $pdo->beginTransaction();

    for ($i = 1; $i <= $cantidad; $i++) {
        // Inserta temporalmente sin codigo_unico
        $sentencia->bindValue(':id_producto', $id_producto);
        $sentencia->bindValue(':estado', $estado);
        $sentencia->bindValue(':fecha_ingreso', $fecha);
        $sentencia->bindValue(':creado_por', $id_usuario);
        $sentencia->execute();

        // Obtener id_stock generado automáticamente
        $id_stock = $pdo->lastInsertId();

        // Generar codigo_unico usando id_stock
        $codigo_unico = $codigo_producto . '-' . str_pad($id_stock, 5, '0', STR_PAD_LEFT);

        // Actualizar el registro con el codigo_unico
        $update_sql = "UPDATE stock SET codigo_unico = :codigo_unico WHERE id_stock = :id_stock";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->bindParam(':codigo_unico', $codigo_unico);
        $update_stmt->bindParam(':id_stock', $id_stock);
        $update_stmt->execute();
    }

    $pdo->commit();
    $_SESSION['mensaje'] = "Se generaron $cantidad piezas para $codigo_producto correctamente";
    header("Location: ".$URL."/stock/index.php?id=".$id_producto);
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['icono'] = "error";
    $_SESSION['mensaje'] = "Error al generar stock: " . $e->getMessage();
    header("Location: ".$URL."/stock/create.php");
    exit;
}
