<?php
/**
 * Migración: Agregar columna foto_perfil a tb_usuario
 * Esta migración se ejecuta automáticamente desde app/config.php
 */

function migrate_add_foto_perfil($pdo) {
    try {
        // Verificar si la columna ya existe
        $check = $pdo->query("SHOW COLUMNS FROM tb_usuario LIKE 'foto_perfil'");
        
        if ($check->rowCount() === 0) {
            // Columna no existe, crearla
            $sql = "ALTER TABLE tb_usuario ADD COLUMN foto_perfil VARCHAR(255) NULL DEFAULT NULL AFTER email";
            $pdo->exec($sql);
            error_log("[MIGRATION] ✅ Columna 'foto_perfil' agregada a tb_usuario");
            return true;
        } else {
            // Columna ya existe
            return true;
        }
    } catch (Exception $e) {
        error_log("[MIGRATION] ❌ Error agregando columna foto_perfil: " . $e->getMessage());
        return false;
    }
}

// Ejecutar migración
migrate_add_foto_perfil($pdo);
?>
