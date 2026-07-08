<?php
require_once 'app/config.php';

// Protección mínima: solo admin logueado
if (session_status() === PHP_SESSION_NONE) session_start();
if (!in_array(24, $_SESSION['permisos'] ?? [])) {
    die('Sin permisos.');
}

$resultados = [];

// 1. Crear tabla tb_descuentos
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS tb_descuentos (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        id_producto     INT            NOT NULL,
        precio_original DECIMAL(10,2)  NOT NULL,
        precio_descuento DECIMAL(10,2) NOT NULL,
        porcentaje      DECIMAL(5,2)   NOT NULL,
        fecha_inicio    DATETIME       NOT NULL,
        fecha_fin       DATETIME       NOT NULL,
        created_at      DATETIME       DEFAULT NOW(),
        INDEX idx_producto (id_producto),
        INDEX idx_vigencia (id_producto, fecha_inicio, fecha_fin)
    )");
    $resultados[] = ['ok', 'Tabla tb_descuentos creada (o ya existía)'];
} catch (Exception $e) {
    $resultados[] = ['error', 'tb_descuentos: ' . $e->getMessage()];
}

// 2. Agregar columna id_descuento a tb_ventas_detalle (solo si no existe)
$existe = (bool)$pdo->query("SHOW COLUMNS FROM tb_ventas_detalle LIKE 'id_descuento'")->fetchColumn();
if (!$existe) {
    try {
        $pdo->exec("ALTER TABLE tb_ventas_detalle ADD COLUMN id_descuento INT NULL DEFAULT NULL");
        $resultados[] = ['ok', 'Columna id_descuento agregada a tb_ventas_detalle'];
    } catch (Exception $e) {
        $resultados[] = ['error', 'id_descuento: ' . $e->getMessage()];
    }
} else {
    $resultados[] = ['ok', 'Columna id_descuento ya existía — sin cambios'];
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Migración Descuentos</title>
  <style>
    body { font-family: sans-serif; max-width: 600px; margin: 60px auto; }
    .ok    { color: green; }
    .error { color: red; }
    li { margin: 8px 0; font-size: 15px; }
    .btn { display:inline-block; margin-top:20px; padding:10px 20px;
           background:#28a745; color:#fff; text-decoration:none; border-radius:5px; }
  </style>
</head>
<body>
  <h2>Migración: Sistema de Descuentos</h2>
  <ul>
    <?php foreach ($resultados as [$tipo, $msg]): ?>
      <li class="<?= $tipo ?>">
        <?= $tipo === 'ok' ? '✅' : '❌' ?> <?= htmlspecialchars($msg) ?>
      </li>
    <?php endforeach; ?>
  </ul>
  <a class="btn" href="<?= $URL ?>">Volver al sistema</a>
  <p style="color:#999;font-size:12px;margin-top:30px;">
    Puedes eliminar este archivo del servidor después de correr la migración.
  </p>
</body>
</html>
