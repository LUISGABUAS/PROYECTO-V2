<?php
/**
 * Migración: Crear tabla clientes_direcciones
 * Esta migración se ejecuta automáticamente desde app/config.php
 */

function migrate_create_clientes_direcciones_table($pdo) {
    try {
        // Verificar si la tabla ya existe
        $check = $pdo->query("SHOW TABLES LIKE 'clientes_direcciones'");
        
        if ($check->rowCount() === 0) {
            // Tabla no existe, crearla
            $sql = "CREATE TABLE clientes_direcciones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_cliente INT NOT NULL,
                calle_numero VARCHAR(255) NOT NULL,
                colonia VARCHAR(100) NOT NULL,
                municipio VARCHAR(100) NOT NULL,
                estado VARCHAR(100) NOT NULL,
                cp VARCHAR(5) NOT NULL,
                referencias TEXT,
                es_principal BOOLEAN DEFAULT 0,
                activa BOOLEAN DEFAULT 1,
                creada_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                actualizada_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE,
                INDEX idx_cliente_direcciones (id_cliente),
                INDEX idx_principal (es_principal)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            error_log("[MIGRATION] ✅ Tabla 'clientes_direcciones' creada correctamente");
            return true;
        } else {
            // Tabla ya existe
            return true;
        }
    } catch (Exception $e) {
        error_log("[MIGRATION] ❌ Error creando tabla clientes_direcciones: " . $e->getMessage());
        return false;
    }
}

// Ejecutar migración
migrate_create_clientes_direcciones_table($pdo);
?>
