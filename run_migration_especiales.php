<?php
require_once 'app/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!in_array(24, $_SESSION['permisos'] ?? [])) {
    die('Sin permisos.');
}

$resultados = [];

$columnas = [
    'tipo_especial'   => "ALTER TABLE stock ADD COLUMN tipo_especial VARCHAR(20) NULL DEFAULT NULL",
    'notas_especial'  => "ALTER TABLE stock ADD COLUMN notas_especial TEXT NULL DEFAULT NULL",
    'id_venta_origen' => "ALTER TABLE stock ADD COLUMN id_venta_origen INT NULL DEFAULT NULL",
];

foreach ($columnas as $col => $sql) {
    $existe = (bool)$pdo->query("SHOW COLUMNS FROM stock LIKE '$col'")->fetchColumn();
    if (!$existe) {
        try {
            $pdo->exec($sql);
            $resultados[] = ['ok', "Columna <strong>$col</strong> agregada a stock"];
        } catch (Exception $e) {
            $resultados[] = ['error', "$col: " . $e->getMessage()];
        }
    } else {
        $resultados[] = ['ok', "Columna <strong>$col</strong> ya existía — sin cambios"];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Migración Especiales</title>
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
  <h2>Migración: Pacas Especiales (tipo_especial)</h2>
  <ul>
    <?php foreach ($resultados as [$tipo, $msg]): ?>
      <li class="<?= $tipo ?>">
        <?= $tipo === 'ok' ? '✅' : '❌' ?> <?= $msg ?>
      </li>
    <?php endforeach; ?>
  </ul>
  <a class="btn" href="<?= $URL ?>">Volver al sistema</a>
  <p style="color:#999;font-size:12px;margin-top:30px;">
    Puedes eliminar este archivo del servidor después de correr la migración.
  </p>
</body>
</html>
