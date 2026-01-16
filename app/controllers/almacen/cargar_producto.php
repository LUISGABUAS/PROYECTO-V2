<?php

$id_producto_get = $_GET['id'];



$sql_productos = "SELECT *, cat.nombre_categoria as categoria , u.nombres as nombre_usuario , u.id as id_usuario
                FROM tb_almacen as a INNER JOIN tb_categorias as cat ON a.id_categoria = cat.id_categoria
                INNER JOIN tb_usuario as u ON u.id = a.id_usuario 
                WHERE id_producto = $id_producto_get ";
$query_productos = $pdo->prepare($sql_productos);
$query_productos->execute();
$datos_productos = $query_productos->fetchAll(PDO::FETCH_ASSOC);

foreach ($datos_productos as $pro) { 
    $id = $pro['id_producto'];
    $codigo = $pro['codigo'];
    $categoria = $pro['categoria'];
    $nombre = $pro['nombre'];
    $descripcion = $pro['descripcion'];
    $stock_minimo = $pro['stock_minimo'];
    $stock_maximo = $pro['stock_maximo'];
    $precio_compra = $pro['precio_compra'];
    $precio_venta = $pro['precio_venta'];
    $fecha_ingreso = $pro['fecha_ingreso'];
    $imagen = $pro['imagen'];
    $nombre_usuario = $pro['nombre_usuario'];
    $id_usuario = $pro['id_usuario'];
}