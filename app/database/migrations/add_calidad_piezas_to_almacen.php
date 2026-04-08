<?php
/**
 * Migración: Agrega columnas calidad y piezas a tb_almacen
 * Ejecutar una sola vez desde el navegador o CLI
 */
require_once dirname(__DIR__, 2) . '/config.php';

try {
    $pdo->exec("ALTER TABLE tb_almacen
        ADD COLUMN calidad VARCHAR(60) NULL DEFAULT NULL AFTER descripcion,
        ADD COLUMN piezas INT UNSIGNED NULL DEFAULT NULL AFTER calidad
    ");
    echo "✅ Columnas 'calidad' y 'piezas' agregadas correctamente a tb_almacen.";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "⚠️ Las columnas ya existen, no se hizo nada.";
    } else {
        echo "❌ Error: " . $e->getMessage();
    }
}
