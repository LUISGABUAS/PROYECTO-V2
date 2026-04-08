<?php
/**
 * Migración: Agregar columna id_vendedor a tabla clientes
 * Esta migración se ejecuta automáticamente desde app/config.php
 */

function migrate_add_id_vendedor_to_clientes($pdo) {
    try {
        // Verificar si la columna ya existe
        $check = $pdo->query("SHOW COLUMNS FROM clientes LIKE 'id_vendedor'");
        
        if ($check->rowCount() === 0) {
            // Columna no existe, crearla
            $sql = "ALTER TABLE clientes ADD COLUMN id_vendedor INT NULL DEFAULT NULL AFTER id_cliente";
            $pdo->exec($sql);
            
            // Agregar índice para optimizar búsquedas
            $pdo->exec("CREATE INDEX idx_clientes_vendedor ON clientes(id_vendedor)");
            
            error_log("[MIGRATION] ✅ Columna 'id_vendedor' agregada a clientes");
            return true;
        } else {
            // Columna ya existe
            return true;
        }
    } catch (Exception $e) {
        error_log("[MIGRATION] ❌ Error agregando columna id_vendedor: " . $e->getMessage());
        return false;
    }
}

// Ejecutar migración
migrate_add_id_vendedor_to_clientes($pdo);
?>
