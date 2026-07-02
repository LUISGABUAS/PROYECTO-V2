<?php
require_once dirname(__DIR__, 2) . '/config.php';

$pasos = [];

// 1. Agregar columnas si no existen
$cols = $pdo->query("SHOW COLUMNS FROM stock LIKE 'tipo_especial'")->fetchAll();
if (empty($cols)) {
    $pdo->exec("ALTER TABLE stock
        ADD COLUMN tipo_especial ENUM('VIDEO','FLEJADA') NULL DEFAULT NULL AFTER estado,
        ADD COLUMN notas_especial VARCHAR(500) NULL AFTER tipo_especial,
        ADD COLUMN id_venta_origen INT NULL AFTER notas_especial");
    $pasos[] = "Columnas tipo_especial, notas_especial, id_venta_origen agregadas.";
} else {
    $pasos[] = "Columnas ya existen, sin cambios.";
}

// 2. Índice
try {
    $pdo->exec("CREATE INDEX idx_stock_especial ON stock (tipo_especial)");
    $pasos[] = "Índice idx_stock_especial creado.";
} catch (PDOException $e) {
    $pasos[] = "Índice ya existe o no fue necesario.";
}

foreach ($pasos as $p) echo "- $p<br>";
echo "<br><strong>Migración completada.</strong>";
