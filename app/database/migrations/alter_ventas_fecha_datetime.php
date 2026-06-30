<?php
require_once dirname(__DIR__, 2) . '/config.php';

try {
    $pdo->exec("ALTER TABLE tb_ventas MODIFY COLUMN fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    echo "✅ Migración completada: columna 'fecha' en tb_ventas cambiada a DATETIME.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'doesn\'t exist') !== false || strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "⚠️ Ya estaba migrada o no fue necesario.";
    } else {
        echo "❌ Error: " . $e->getMessage();
    }
}
