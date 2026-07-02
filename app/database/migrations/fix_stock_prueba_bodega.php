<?php
require_once dirname(__DIR__, 2) . '/config.php';

// Buscar el producto por código
$stmt = $pdo->prepare("SELECT id_producto, nombre, codigo FROM tb_almacen WHERE codigo = ?");
$stmt->execute(['00497']);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    echo "❌ No se encontró el producto con código 00497";
    exit;
}

echo "<strong>Producto:</strong> " . htmlspecialchars($producto['nombre']) . " (" . $producto['codigo'] . ")<br><br>";

// Ver estado actual
$stmt = $pdo->prepare("SELECT estado, COUNT(*) as total FROM stock WHERE id_producto = ? GROUP BY estado");
$stmt->execute([$producto['id_producto']]);
$estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<strong>Estado actual del stock:</strong><br>";
foreach ($estados as $e) {
    echo "- " . $e['estado'] . ": " . $e['total'] . " unidades<br>";
}
echo "<br>";

// Actualizar a EN BODEGA solo los que NO están VENDIDO
$stmt = $pdo->prepare("
    UPDATE stock
    SET estado = 'EN BODEGA', fecha_salida = NULL
    WHERE id_producto = ?
      AND estado NOT IN ('VENDIDO')
");
$stmt->execute([$producto['id_producto']]);
$actualizados = $stmt->rowCount();

echo "<strong>Actualizados a EN BODEGA:</strong> $actualizados unidades<br><br>";

// Verificar resultado final
$stmt = $pdo->prepare("SELECT estado, COUNT(*) as total FROM stock WHERE id_producto = ? GROUP BY estado");
$stmt->execute([$producto['id_producto']]);
$estados_nuevo = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<strong>Estado final:</strong><br>";
foreach ($estados_nuevo as $e) {
    echo "- " . $e['estado'] . ": " . $e['total'] . " unidades<br>";
}

echo "<br>✅ Listo.";
